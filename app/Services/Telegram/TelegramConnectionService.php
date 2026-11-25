<?php

namespace App\Services\Telegram;

use App\Models\Business;
use App\Models\SecurityLog;
use App\Models\TelegramConnection;
use App\Models\TelegramVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TelegramConnectionService
{
    public function __construct(private readonly TelegramBotService $botService)
    {
    }

    /**
     * Generate a cryptographically random, single-use verification code valid for 15 minutes.
     *
     * @return array{code: string, expires_at: \Illuminate\Support\Carbon, bot_username: string, deep_link: string}
     */
    public function generateVerificationCode(Business $business, User $user): array
    {
        $ttlMinutes = max(5, (int) config('services.telegram.verification_ttl_minutes', 15));
        $plainCode = 'SBYR' . Str::upper(bin2hex(random_bytes(8)));
        $expiresAt = now()->addMinutes($ttlMinutes);

        DB::transaction(function () use ($business, $user, $plainCode, $expiresAt): void {
            // Invalidate previous unredeemed codes for this business
            TelegramVerificationCode::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            TelegramVerificationCode::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'code_hash' => hash('sha256', $plainCode),
                'expires_at' => $expiresAt,
            ]);
        });

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'event_type' => 'telegram_link_initiated',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => ['expires_at' => $expiresAt->toIso8601String()],
        ]);

        $botUsername = ltrim((string) config('services.telegram.bot_username', 'SabayraBot'), '@');

        return [
            'code' => $plainCode,
            'expires_at' => $expiresAt,
            'bot_username' => $botUsername,
            'deep_link' => "https://t.me/{$botUsername}?start={$plainCode}",
        ];
    }

    /**
     * Verify a one-time code received from the official Telegram bot webhook and link the chat.
     *
     * @param  array<string, mixed>  $updatePayload
     * @return array{ok: bool, status: string, business_id?: string}
     */
    public function verifyAndLinkFromWebhook(array $updatePayload): array
    {
        $message = $updatePayload['message'] ?? $updatePayload['edited_message'] ?? null;
        if (!is_array($message)) {
            return ['ok' => false, 'status' => 'ignored_non_message'];
        }

        $chat = $message['chat'] ?? null;
        $chatId = is_array($chat) && isset($chat['id']) ? (string) $chat['id'] : null;
        $text = trim((string) ($message['text'] ?? ''));

        if (!$chatId || $text === '') {
            return ['ok' => false, 'status' => 'ignored_empty_payload'];
        }

        $code = $this->extractVerificationCode($text);
        if (!$code) {
            $this->replyViaOfficialBot($chatId, "Welcome to SABAYRA Bot.\nPlease copy your verification command from Settings -> Notifications in SABAYRA (example: /start SBYR...) to connect your store.");

            return ['ok' => false, 'status' => 'missing_code'];
        }

        $outcome = DB::transaction(function () use ($code, $chatId, $chat, $message): array {
            $record = TelegramVerificationCode::withoutGlobalScopes()
                ->where('code_hash', hash('sha256', $code))
                ->lockForUpdate()
                ->first();

            if (!$record) {
                return ['ok' => false, 'status' => 'invalid_code'];
            }

            if ($record->isUsed()) {
                return ['ok' => false, 'status' => 'code_already_used'];
            }

            if ($record->isExpired()) {
                return ['ok' => false, 'status' => 'code_expired'];
            }

            $business = Business::find($record->business_id);
            $user = User::find($record->user_id);

            if (!$business || !$user || !$user->canManageBusinessSettings($business)) {
                return ['ok' => false, 'status' => 'unauthorized_link'];
            }

            $record->update(['used_at' => now()]);

            $from = is_array($message['from'] ?? null) ? $message['from'] : [];
            $telegramUserId = isset($from['id']) ? (string) $from['id'] : null;
            $telegramUsername = isset($from['username']) ? (string) $from['username'] : (is_array($chat) && isset($chat['username']) ? (string) $chat['username'] : null);
            $displayName = $this->resolveDisplayName($from, is_array($chat) ? $chat : []);

            $connection = TelegramConnection::withoutGlobalScopes()->updateOrCreate(
                ['business_id' => $business->id],
                [
                    'user_id' => $user->id,
                    'connection_mode' => 'official_bot',
                    'bot_token' => null,
                    'bot_username' => ltrim((string) config('services.telegram.bot_username', 'SabayraBot'), '@'),
                    'chat_id' => $chatId,
                    'telegram_user_id' => $telegramUserId,
                    'telegram_username' => $telegramUsername,
                    'telegram_display_name' => $displayName,
                    'is_active' => true,
                    'notifications_enabled' => true,
                    'connected_at' => now(),
                    'last_error' => null,
                ]
            );

            SecurityLog::create([
                'user_id' => $user->id,
                'business_id' => $business->id,
                'event_type' => 'telegram_connected',
                'metadata' => [
                    'connection_mode' => 'official_bot',
                    'masked_chat_id' => $connection->masked_chat_id,
                ],
            ]);

            return [
                'ok' => true,
                'status' => 'connected',
                'business' => $business,
                'connection' => $connection,
            ];
        });

        if (!$outcome['ok']) {
            $reasonMessage = match ($outcome['status']) {
                'code_expired' => 'This verification code has expired. Please generate a new code inside SABAYRA Settings -> Notifications.',
                'code_already_used' => 'This one-time verification code has already been used. Please generate a new code in SABAYRA if you need to re-link.',
                default => 'Invalid verification code. Please open SABAYRA Settings -> Notifications and click Connect Telegram.',
            };
            $this->replyViaOfficialBot($chatId, $reasonMessage);

            return ['ok' => false, 'status' => $outcome['status']];
        }

        /** @var Business $business */
        $business = $outcome['business'];
        /** @var TelegramConnection $connection */
        $connection = $outcome['connection'];

        $welcomeText = "<b>SABAYRA Connected</b>\nWorkspace: " . e($business->name) . "\nYou can now return to your SABAYRA dashboard to manage notifications.";
        $this->replyViaOfficialBot($chatId, $welcomeText, $connection);

        return [
            'ok' => true,
            'status' => 'connected',
            'business_id' => $business->id,
        ];
    }

    /**
     * Poll Telegram Bot API getUpdates as a local fallback when webhooks cannot reach localhost.
     */
    public function syncPendingVerificationFromUpdates(Business $business): bool
    {
        if (! config('services.telegram.polling_fallback', false)) {
            return false;
        }

        $officialToken = config('services.telegram.bot_token');
        if (! is_string($officialToken) || $officialToken === '') {
            return false;
        }

        $pendingHashes = TelegramVerificationCode::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->pluck('code_hash')
            ->all();

        if (empty($pendingHashes)) {
            return false;
        }

        $updatesResult = $this->botService->getUpdates($officialToken);
        if (! $updatesResult['ok'] || empty($updatesResult['updates'])) {
            return false;
        }

        foreach (array_reverse($updatesResult['updates']) as $update) {
            if (! is_array($update)) {
                continue;
            }

            $message = $update['message'] ?? $update['edited_message'] ?? null;
            if (! is_array($message)) {
                continue;
            }

            $code = $this->extractVerificationCode(trim((string) ($message['text'] ?? '')));
            if (! $code || ! in_array(hash('sha256', $code), $pendingHashes, true)) {
                continue;
            }

            $outcome = $this->verifyAndLinkFromWebhook($update);

            if ($outcome['ok']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate a manual custom Telegram Bot Token + Chat ID via Telegram API before saving as active.
     *
     * @return array{ok: bool, error: string|null, connection?: TelegramConnection}
     */
    public function saveCustomBotConfiguration(
        Business $business,
        User $user,
        ?string $submittedToken,
        string $chatId,
        bool $notificationsEnabled
    ): array {
        $existing = TelegramConnection::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->first();

        $botToken = trim((string) $submittedToken);
        if ($botToken === '' && $existing && $existing->connection_mode === 'custom_bot') {
            $botToken = (string) ($existing->bot_token ?? '');
        }

        if ($botToken === '') {
            return [
                'ok' => false,
                'error' => 'Please enter a valid Telegram Bot Token.',
            ];
        }

        // 1. Validate bot token with Telegram getMe
        $meResult = $this->botService->getMe($botToken);
        if (!$meResult['ok']) {
            return [
                'ok' => false,
                'error' => 'Bot verification failed: ' . ($meResult['error'] ?? 'Invalid Bot Token.'),
            ];
        }

        $botUsername = isset($meResult['bot']['username']) ? (string) $meResult['bot']['username'] : null;

        // 2. Validate chat delivery before activating connection
        $verifyMessage = "<b>SABAYRA Custom Bot Verified</b>\nWorkspace: " . e($business->name) . "\nBot: @" . e($botUsername ?? 'custom_bot');
        $sendResult = $this->botService->sendMessage($botToken, $chatId, $verifyMessage);

        if (!$sendResult['ok']) {
            return [
                'ok' => false,
                'error' => 'Chat verification failed (bot token valid, but message delivery failed): ' . ($sendResult['error'] ?? 'Check Chat ID and ensure the bot was started or added to the chat.'),
            ];
        }

        // 3. Save verified credentials encrypted at rest
        $connection = TelegramConnection::withoutGlobalScopes()->updateOrCreate(
            ['business_id' => $business->id],
            [
                'user_id' => $user->id,
                'connection_mode' => 'custom_bot',
                'bot_token' => $botToken,
                'bot_username' => $botUsername,
                'chat_id' => $chatId,
                'telegram_user_id' => null,
                'telegram_username' => $botUsername ? "@{$botUsername}" : null,
                'telegram_display_name' => $meResult['bot']['first_name'] ?? $business->name,
                'is_active' => true,
                'notifications_enabled' => $notificationsEnabled,
                'connected_at' => now(),
                'last_tested_at' => now(),
                'last_notification_at' => now(),
                'last_error' => null,
            ]
        );

        $this->botService->recordLog($connection, 'custom_bot_verified', 'sent', $verifyMessage, null);

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'event_type' => 'telegram_custom_bot_saved',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => [
                'bot_username' => $botUsername,
                'masked_chat_id' => $connection->masked_chat_id,
            ],
        ]);

        return [
            'ok' => true,
            'error' => null,
            'connection' => $connection,
        ];
    }

    /**
     * Send a test notification for the active business Telegram connection.
     *
     * @return array{ok: bool, error: string|null}
     */
    public function sendTestNotification(Business $business, User $user): array
    {
        $connection = TelegramConnection::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->first();

        if (!$connection || !$connection->is_active) {
            return [
                'ok' => false,
                'error' => 'Please connect Telegram before sending a test notification.',
            ];
        }

        $connection->forceFill(['last_tested_at' => now()])->save();

        $text = "<b>SABAYRA Test Notification</b>\nWorkspace: " . e($business->name) . "\nTriggered by: " . e($user->name) . "\nTime: " . now()->format('Y-m-d H:i');

        $result = $this->botService->deliverForConnection(
            $connection,
            'connection_test',
            $text,
            "Test notification for {$business->name}",
            ignoreDisabledToggle: true
        );

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'event_type' => 'telegram_test_sent',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => ['status' => $result['ok'] ? 'sent' : 'failed'],
        ]);

        return $result;
    }

    /**
     * Enable or disable Telegram notification delivery for a connected business.
     */
    public function toggleNotifications(Business $business, User $user, bool $enabled): ?TelegramConnection
    {
        $connection = TelegramConnection::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->first();

        if (!$connection || !$connection->is_active) {
            return null;
        }

        $connection->update(['notifications_enabled' => $enabled]);

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'event_type' => $enabled ? 'telegram_notifications_enabled' : 'telegram_notifications_disabled',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return $connection;
    }

    /**
     * Disconnect Telegram and clear stored credentials for a business.
     */
    public function disconnect(Business $business, User $user): void
    {
        $connection = TelegramConnection::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->first();

        if ($connection) {
            $connection->update([
                'bot_token' => null,
                'chat_id' => null,
                'bot_username' => null,
                'telegram_user_id' => null,
                'telegram_username' => null,
                'telegram_display_name' => null,
                'is_active' => false,
                'notifications_enabled' => false,
                'connected_at' => null,
                'last_error' => null,
            ]);
        }

        TelegramVerificationCode::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'event_type' => 'telegram_disconnected',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Extract a one-time SABAYRA verification code from a bot message (/start CODE, /verify CODE, or CODE).
     */
    private function extractVerificationCode(string $text): ?string
    {
        if (preg_match('/^\/(?:start|verify)(?:@[A-Za-z0-9_]+)?\s+([A-Za-z0-9_-]{8,64})$/i', $text, $matches)) {
            return strtoupper($matches[1]);
        }

        if (preg_match('/^(SBYR[A-Z0-9]{12,40})$/i', $text, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    /**
     * Build a safe display name from Telegram user/chat fields.
     *
     * @param  array<string, mixed>  $from
     * @param  array<string, mixed>  $chat
     */
    private function resolveDisplayName(array $from, array $chat): ?string
    {
        if (!empty($chat['title'])) {
            return (string) $chat['title'];
        }

        $parts = array_filter([
            $from['first_name'] ?? null,
            $from['last_name'] ?? null,
        ]);

        if (!empty($parts)) {
            return implode(' ', $parts);
        }

        return isset($from['username']) ? '@' . $from['username'] : null;
    }

    /**
     * Send a confirmation/error reply back using the platform's official bot token when configured.
     */
    private function replyViaOfficialBot(string $chatId, string $message, ?TelegramConnection $connection = null): void
    {
        $officialToken = config('services.telegram.bot_token');
        if (!is_string($officialToken) || $officialToken === '') {
            if ($connection) {
                $this->botService->recordLog($connection, 'connection_linked', 'skipped', $message, 'Platform TELEGRAM_BOT_TOKEN is not configured for reply.');
            }

            return;
        }

        $result = $this->botService->sendMessage($officialToken, $chatId, $message);
        if ($connection) {
            if ($result['ok']) {
                $connection->forceFill(['last_notification_at' => now()])->save();
            }
            $this->botService->recordLog(
                $connection,
                'connection_linked',
                $result['ok'] ? 'sent' : 'failed',
                $message,
                $result['error']
            );
        }
    }
}
