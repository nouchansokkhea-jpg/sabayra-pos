<?php

namespace App\Services\Telegram;

use App\Models\TelegramConnection;
use App\Models\TelegramNotificationLog;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramBotService
{
    /**
     * Register a webhook endpoint URL with Telegram Bot API for the official platform bot.
     *
     * @return array{ok: bool, error: string|null}
     */
    public function setWebhook(string $botToken, string $webhookUrl, ?string $secretToken = null): array
    {
        $url = $this->endpoint($botToken, 'setWebhook');
        $payload = [
            'url' => $webhookUrl,
            'allowed_updates' => ['message', 'edited_message'],
        ];

        if ($secretToken !== null && $secretToken !== '') {
            $payload['secret_token'] = $secretToken;
        }

        try {
            $response = $this->httpClient()->post($url, $payload);
            $json = $response->json();

            if ($response->successful() && is_array($json) && ($json['ok'] ?? false) === true) {
                return ['ok' => true, 'error' => null];
            }

            $description = is_array($json)
                ? ($json['description'] ?? 'Unable to set Telegram webhook.')
                : 'Telegram API returned HTTP ' . $response->status() . '.';

            return ['ok' => false, 'error' => $this->sanitizeError((string) $description, $botToken)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Could not reach Telegram API: ' . $this->sanitizeError($e->getMessage(), $botToken)];
        }
    }

    /**
     * Remove any active webhook from Telegram Bot API (required when using local getUpdates polling).
     *
     * @return array{ok: bool, error: string|null}
     */
    public function deleteWebhook(string $botToken, bool $dropPendingUpdates = false): array
    {
        $url = $this->endpoint($botToken, 'deleteWebhook');

        try {
            $response = $this->httpClient()->post($url, [
                'drop_pending_updates' => $dropPendingUpdates,
            ]);
            $json = $response->json();

            if ($response->successful() && is_array($json) && ($json['ok'] ?? false) === true) {
                return ['ok' => true, 'error' => null];
            }

            $description = is_array($json)
                ? ($json['description'] ?? 'Unable to delete Telegram webhook.')
                : 'Telegram API returned HTTP ' . $response->status() . '.';

            return ['ok' => false, 'error' => $this->sanitizeError((string) $description, $botToken)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Could not reach Telegram API: ' . $this->sanitizeError($e->getMessage(), $botToken)];
        }
    }

    /**
     * Retrieve current webhook configuration info from Telegram Bot API.
     *
     * @return array{ok: bool, info: array<string, mixed>|null, error: string|null}
     */
    public function getWebhookInfo(string $botToken): array
    {
        $url = $this->endpoint($botToken, 'getWebhookInfo');

        try {
            $response = $this->httpClient()->get($url);
            $json = $response->json();

            if ($response->successful() && is_array($json) && ($json['ok'] ?? false) === true) {
                return [
                    'ok' => true,
                    'info' => is_array($json['result'] ?? null) ? $json['result'] : [],
                    'error' => null,
                ];
            }

            $description = is_array($json)
                ? ($json['description'] ?? 'Unable to query Telegram webhook info.')
                : 'Telegram API returned HTTP ' . $response->status() . '.';

            return ['ok' => false, 'info' => null, 'error' => $this->sanitizeError((string) $description, $botToken)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'info' => null, 'error' => 'Could not reach Telegram API: ' . $this->sanitizeError($e->getMessage(), $botToken)];
        }
    }

    /**
     * Fetch recent incoming updates via Bot API polling when webhooks are unavailable locally.
     *
     * @return array{ok: bool, updates: array<int, array<string, mixed>>, error: string|null}
     */
    public function getUpdates(string $botToken, ?int $offset = null, int $limit = 25): array
    {
        $url = $this->endpoint($botToken, 'getUpdates');
        $query = [
            'limit' => $limit,
            'timeout' => 0,
            'allowed_updates' => json_encode(['message', 'edited_message']),
        ];

        if ($offset !== null) {
            $query['offset'] = $offset;
        }

        try {
            $response = $this->httpClient()->get($url, $query);
            $json = $response->json();

            if ($response->successful() && is_array($json) && ($json['ok'] ?? false) === true) {
                return [
                    'ok' => true,
                    'updates' => is_array($json['result'] ?? null) ? $json['result'] : [],
                    'error' => null,
                ];
            }

            $apiDescription = is_array($json)
                ? ($json['description'] ?? 'Unable to fetch Telegram updates.')
                : 'Telegram API returned HTTP ' . $response->status() . '.';

            return [
                'ok' => false,
                'updates' => [],
                'error' => $this->sanitizeError((string) $apiDescription, $botToken),
            ];
        } catch (\Throwable $e) {
            $sanitized = $this->sanitizeError($e->getMessage(), $botToken);
            Log::warning('Telegram getUpdates failed', ['error' => $sanitized]);

            return [
                'ok' => false,
                'updates' => [],
                'error' => 'Could not reach Telegram API: ' . $sanitized,
            ];
        }
    }

    /**
     * Validate a Telegram bot token by calling the getMe endpoint.
     *
     * @return array{ok: bool, bot: array<string, mixed>|null, error: string|null}
     */
    public function getMe(string $botToken): array
    {
        $url = $this->endpoint($botToken, 'getMe');

        try {
            $response = $this->httpClient()->get($url);
            $json = $response->json();

            if ($response->successful() && is_array($json) && ($json['ok'] ?? false) === true) {
                return [
                    'ok' => true,
                    'bot' => $json['result'] ?? [],
                    'error' => null,
                ];
            }

            $apiDescription = is_array($json) ? ($json['description'] ?? 'Invalid bot token or unauthorized request.') : 'Unable to verify bot token.';

            return [
                'ok' => false,
                'bot' => null,
                'error' => $this->sanitizeError((string) $apiDescription, $botToken),
            ];
        } catch (\Throwable $e) {
            $sanitized = $this->sanitizeError($e->getMessage(), $botToken);
            Log::warning('Telegram getMe failed', ['error' => $sanitized]);

            return [
                'ok' => false,
                'bot' => null,
                'error' => 'Could not reach Telegram API: ' . $sanitized,
            ];
        }
    }

    /**
     * Send a message to a Telegram chat using the supplied bot token.
     *
     * @return array{ok: bool, message_id: int|null, error: string|null}
     */
    public function sendMessage(
        string $botToken,
        string $chatId,
        string $text,
        string $parseMode = 'HTML'
    ): array {
        $url = $this->endpoint($botToken, 'sendMessage');

        try {
            $response = $this->httpClient()
                ->post($url, [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => $parseMode,
                    'disable_web_page_preview' => true,
                ]);

            $json = $response->json();

            if ($response->successful() && is_array($json) && ($json['ok'] ?? false) === true) {
                return [
                    'ok' => true,
                    'message_id' => isset($json['result']['message_id']) ? (int) $json['result']['message_id'] : null,
                    'error' => null,
                ];
            }

            $apiDescription = is_array($json)
                ? ($json['description'] ?? 'Telegram rejected the message.')
                : 'Telegram API returned HTTP ' . $response->status() . '.';

            return [
                'ok' => false,
                'message_id' => null,
                'error' => $this->sanitizeError((string) $apiDescription, $botToken),
            ];
        } catch (\Throwable $e) {
            $sanitized = $this->sanitizeError($e->getMessage(), $botToken);
            Log::warning('Telegram sendMessage failed', ['error' => $sanitized]);

            return [
                'ok' => false,
                'message_id' => null,
                'error' => 'Could not reach Telegram API: ' . $sanitized,
            ];
        }
    }

    /**
     * Deliver a notification through a verified business Telegram connection and record delivery status.
     *
     * @return array{ok: bool, error: string|null}
     */
    public function deliverForConnection(
        TelegramConnection $connection,
        string $eventType,
        string $message,
        ?string $preview = null,
        bool $ignoreDisabledToggle = false
    ): array {
        if (!$connection->is_active || empty($connection->chat_id)) {
            return [
                'ok' => false,
                'error' => 'Telegram connection is not verified or active.',
            ];
        }

        if (!$ignoreDisabledToggle && !$connection->notifications_enabled) {
            return [
                'ok' => false,
                'error' => 'Telegram notifications are currently paused for this business.',
            ];
        }

        $botToken = $connection->resolveEffectiveBotToken();
        if (empty($botToken)) {
            $error = 'Telegram bot token is missing for the selected connection mode.';
            $this->recordLog($connection, $eventType, 'failed', $preview ?? $message, $error);

            return ['ok' => false, 'error' => $error];
        }

        $result = $this->sendMessage($botToken, (string) $connection->chat_id, $message);

        if ($result['ok']) {
            $connection->forceFill([
                'last_notification_at' => now(),
                'last_error' => null,
            ])->save();

            $this->recordLog($connection, $eventType, 'sent', $preview ?? $message, null);

            return ['ok' => true, 'error' => null];
        }

        $safeError = $this->sanitizeError((string) ($result['error'] ?? 'Delivery failed.'), $botToken);
        $connection->forceFill([
            'last_error' => Str::limit($safeError, 490),
        ])->save();

        $this->recordLog($connection, $eventType, 'failed', $preview ?? $message, $safeError);

        return ['ok' => false, 'error' => $safeError];
    }

    /**
     * Record a sanitized notification delivery entry without exposing tokens or secrets.
     */
    public function recordLog(
        TelegramConnection $connection,
        string $eventType,
        string $status,
        string $preview,
        ?string $errorMessage = null
    ): TelegramNotificationLog {
        $plainPreview = trim(strip_tags($preview));

        return TelegramNotificationLog::withoutGlobalScopes()->create([
            'business_id' => $connection->business_id,
            'telegram_connection_id' => $connection->id,
            'event_type' => $eventType,
            'status' => $status,
            'recipient_masked' => $connection->masked_chat_id,
            'message_preview' => Str::limit($plainPreview, 240),
            'error_message' => $errorMessage ? Str::limit($this->sanitizeError($errorMessage), 500) : null,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }

    /**
     * Strip any raw or URL-embedded Telegram bot tokens from error text before logging or display.
     */
    public function sanitizeError(string $message, ?string $token = null): string
    {
        $sanitized = $message;

        if ($token !== null && $token !== '') {
            $sanitized = str_replace($token, '[REDACTED_TOKEN]', $sanitized);
        }

        return (string) preg_replace('/bot\d+:[A-Za-z0-9_-]+/', 'bot[REDACTED_TOKEN]', $sanitized);
    }

    /**
     * Build an HTTP client configured for Telegram Bot API calls.
     */
    private function httpClient(): PendingRequest
    {
        $client = Http::timeout(8)->acceptJson();

        $verifySsl = config('services.telegram.verify_ssl', true);
        $missingLocalWindowsCa = app()->environment('local')
            && empty(ini_get('curl.cainfo'))
            && empty(ini_get('openssl.cafile'));

        if (
            $verifySsl === false
            || $verifySsl === 'false'
            || $verifySsl === 0
            || $verifySsl === '0'
            || $missingLocalWindowsCa
        ) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * Build a Telegram Bot API method URL.
     */
    private function endpoint(string $botToken, string $method): string
    {
        $base = rtrim((string) config('services.telegram.api_base_url', 'https://api.telegram.org'), '/');

        return "{$base}/bot{$botToken}/{$method}";
    }
}
