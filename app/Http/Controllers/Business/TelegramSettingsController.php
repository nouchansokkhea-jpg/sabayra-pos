<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\TelegramConnection;
use App\Models\User;
use App\Services\Telegram\TelegramConnectionService;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TelegramSettingsController extends Controller
{
    public function __construct(private readonly TelegramConnectionService $connectionService)
    {
    }

    /**
     * Generate a 15-minute one-time verification code for One-Click Telegram Connection.
     */
    public function connect(Request $request): RedirectResponse
    {
        $business = $this->authorizedBusiness();
        /** @var User $user */
        $user = Auth::user();

        $linkPayload = $this->connectionService->generateVerificationCode($business, $user);

        session([
            "telegram_link_{$business->id}" => [
                'code' => $linkPayload['code'],
                'expires_at' => $linkPayload['expires_at']->toIso8601String(),
                'bot_username' => $linkPayload['bot_username'],
                'deep_link' => $linkPayload['deep_link'],
            ],
        ]);

        return redirect()
            ->route('business.settings.index', ['section' => 'notifications'])
            ->with('success', 'One-time Telegram verification code generated (valid for 15 minutes).');
    }

    /**
     * Validate and save a custom business Telegram Bot Token & Chat ID.
     */
    public function saveCustomBot(Request $request): RedirectResponse
    {
        $business = $this->authorizedBusiness();
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'bot_token' => ['nullable', 'string', 'max:255'],
            'chat_id' => ['required', 'string', 'max:64'],
            'notifications_enabled' => ['nullable', 'boolean'],
        ]);

        $result = $this->connectionService->saveCustomBotConfiguration(
            $business,
            $user,
            $validated['bot_token'] ?? null,
            trim((string) $validated['chat_id']),
            $request->boolean('notifications_enabled', true)
        );

        if (!$result['ok']) {
            return redirect()
                ->route('business.settings.index', ['section' => 'notifications'])
                ->withInput($request->except('bot_token'))
                ->with('error', $result['error'] ?? 'Could not validate Telegram bot credentials.');
        }

        session()->forget("telegram_link_{$business->id}");

        return redirect()
            ->route('business.settings.index', ['section' => 'notifications'])
            ->with('success', 'Custom Telegram Bot validated and connected successfully!');
    }

    /**
     * Toggle Telegram notifications on or off for the active business connection.
     */
    public function toggle(Request $request): RedirectResponse
    {
        $business = $this->authorizedBusiness();
        /** @var User $user */
        $user = Auth::user();

        $enabled = $request->boolean('notifications_enabled');
        $connection = $this->connectionService->toggleNotifications($business, $user, $enabled);

        if (!$connection) {
            return redirect()
                ->route('business.settings.index', ['section' => 'notifications'])
                ->with('error', 'No active Telegram connection found for this business.');
        }

        return redirect()
            ->route('business.settings.index', ['section' => 'notifications'])
            ->with('success', $enabled ? 'Telegram notifications enabled.' : 'Telegram notifications paused.');
    }

    /**
     * Send a live test notification through the business's connected Telegram bot.
     */
    public function test(Request $request): RedirectResponse
    {
        $business = $this->authorizedBusiness();
        /** @var User $user */
        $user = Auth::user();

        $result = $this->connectionService->sendTestNotification($business, $user);

        if (!$result['ok']) {
            return redirect()
                ->route('business.settings.index', ['section' => 'notifications'])
                ->with('error', $result['error'] ?? 'Failed to deliver Telegram test message.');
        }

        return redirect()
            ->route('business.settings.index', ['section' => 'notifications'])
            ->with('success', 'Test notification delivered to your connected Telegram chat!');
    }

    /**
     * Disconnect Telegram and purge stored credentials for the active business.
     */
    public function disconnect(Request $request): RedirectResponse
    {
        $business = $this->authorizedBusiness();
        /** @var User $user */
        $user = Auth::user();

        $this->connectionService->disconnect($business, $user);
        session()->forget("telegram_link_{$business->id}");

        return redirect()
            ->route('business.settings.index', ['section' => 'notifications'])
            ->with('success', 'Telegram connection disconnected and credentials cleared.');
    }

    /**
     * Return safe, masked connection status JSON for live UI polling during verification.
     */
    public function status(): JsonResponse
    {
        $business = $this->authorizedBusiness();

        $this->connectionService->syncPendingVerificationFromUpdates($business);

        $connection = TelegramConnection::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->first();

        if ($connection?->is_active) {
            session()->forget("telegram_link_{$business->id}");
        }

        return response()->json([
            'connected' => (bool) ($connection?->is_active),
            'notifications_enabled' => (bool) ($connection?->notifications_enabled),
            'connection_mode' => $connection?->connection_mode,
            'masked_chat_id' => $connection?->masked_chat_id,
            'telegram_username' => $connection?->telegram_username,
            'telegram_display_name' => $connection?->telegram_display_name,
            'last_notification_at' => $connection?->last_notification_at?->diffForHumans(),
        ]);
    }

    /**
     * Resolve active tenant workspace and enforce business settings authorization.
     */
    private function authorizedBusiness(): Business
    {
        $business = TenantContext::get();
        /** @var User|null $user */
        $user = Auth::user();

        if (!$business || !$user || !$user->canManageBusinessSettings($business)) {
            abort(403, 'You do not have permission to manage Telegram notification settings.');
        }

        return $business;
    }
}
