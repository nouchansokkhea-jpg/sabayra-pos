<?php

namespace App\Services\Security;

use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\SecurityLog;
use App\Models\User;
use App\Services\Notifications\BusinessNotificationService;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\Request;

class SecurityActivityService
{
    /**
     * Record a security or authentication event for a user and optionally alert on suspicious activity.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        ?User $user,
        string $eventType,
        Request $request,
        array $metadata = [],
        ?string $businessId = null
    ): SecurityLog {
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        $parsedAgent = $this->parseUserAgent($userAgent);

        $resolvedBusinessId = $businessId
            ?? ($request->hasSession() ? $request->session()->get('active_business_id') : null)
            ?? TenantContext::get()?->id;

        if ($user instanceof User && $eventType === 'login_success' && ! empty($ipAddress)) {
            $hasPreviousLogins = SecurityLog::where('user_id', $user->id)
                ->where('event_type', 'login_success')
                ->exists();

            if ($hasPreviousLogins) {
                $knownIp = SecurityLog::where('user_id', $user->id)
                    ->where('event_type', 'login_success')
                    ->where('ip_address', $ipAddress)
                    ->exists();

                if (! $knownIp) {
                    $metadata['new_device_ip'] = true;
                }
            }
        }

        $metadata['device_label'] = $metadata['device_label'] ?? $parsedAgent['label'];
        $metadata['device_type'] = $metadata['device_type'] ?? $parsedAgent['device_type'];

        $log = SecurityLog::create([
            'user_id' => $user?->id,
            'business_id' => is_string($resolvedBusinessId) && $resolvedBusinessId !== '' ? $resolvedBusinessId : null,
            'event_type' => $eventType,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        if ($user instanceof User) {
            if (in_array($eventType, ['login_locked', 'login_failed'], true)) {
                $this->dispatchSuspiciousLoginAlertIfNeeded($user, $eventType, $ipAddress, $parsedAgent['label']);
            } elseif ($eventType === 'login_success' && ! empty($metadata['new_device_ip'])) {
                $this->dispatchUnfamiliarIpAlertIfNeeded($user, $ipAddress, $parsedAgent['label']);
            }
        }

        return $log;
    }

    /**
     * Parse a raw HTTP User-Agent string into browser, OS, device type, and display label.
     *
     * @return array{device_type: string, browser: string, os: string, label: string}
     */
    public function parseUserAgent(?string $userAgent): array
    {
        $ua = trim((string) $userAgent);

        if ($ua === '') {
            return [
                'device_type' => 'Unknown',
                'browser' => 'Unknown Client',
                'os' => 'Unknown OS',
                'label' => 'Unknown Device',
            ];
        }

        $deviceType = 'Desktop';
        if (preg_match('/iPad|Tablet|PlayBook|Silk/i', $ua) === 1) {
            $deviceType = 'Tablet';
        } elseif (preg_match('/Mobile|iPhone|iPod|Android.*Mobile|BlackBerry|Opera Mini/i', $ua) === 1) {
            $deviceType = 'Mobile';
        } elseif (preg_match('/Symfony|curl|Guzzle|Postman|PHP/i', $ua) === 1) {
            $deviceType = 'API / Client';
        }

        $os = 'Unknown OS';
        if (preg_match('/iPhone|iPad|iPod/i', $ua) === 1) {
            $os = 'iOS';
        } elseif (preg_match('/Android/i', $ua) === 1) {
            $os = 'Android';
        } elseif (preg_match('/Windows NT/i', $ua) === 1) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X|Macintosh/i', $ua) === 1) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $ua) === 1) {
            $os = 'Linux';
        }

        $browser = 'Unknown Browser';
        if (preg_match('/Edg\//i', $ua) === 1) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/OPR\/|Opera/i', $ua) === 1) {
            $browser = 'Opera';
        } elseif (preg_match('/Chrome\/|CriOS/i', $ua) === 1) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/|FxiOS/i', $ua) === 1) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\//i', $ua) === 1 && preg_match('/Chrome|Chromium/i', $ua) !== 1) {
            $browser = 'Safari';
        } elseif (preg_match('/Postman/i', $ua) === 1) {
            $browser = 'Postman';
        } elseif (preg_match('/Symfony/i', $ua) === 1) {
            $browser = 'Test Client';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
            'label' => "{$browser} on {$os} ({$deviceType})",
        ];
    }

    /**
     * Return human-readable presentation metadata for any SecurityLog event type.
     *
     * @return array{label: string, status_text: string, badge_class: string, category: string}
     */
    public function describeEvent(string $eventType): array
    {
        return match ($eventType) {
            'login_success' => [
                'label' => 'Signed In',
                'status_text' => 'Success',
                'badge_class' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30',
                'category' => 'logins',
            ],
            'login_failed' => [
                'label' => 'Failed Sign-In',
                'status_text' => 'Failed',
                'badge_class' => 'bg-rose-500/15 text-rose-600 dark:text-rose-300 border border-rose-500/30',
                'category' => 'failed',
            ],
            'login_locked' => [
                'label' => 'Rate Limit Lockout',
                'status_text' => 'Blocked',
                'badge_class' => 'bg-rose-500/15 text-rose-600 dark:text-rose-300 border border-rose-500/30',
                'category' => 'failed',
            ],
            'logout' => [
                'label' => 'Signed Out',
                'status_text' => 'Session Ended',
                'badge_class' => 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600',
                'category' => 'logins',
            ],
            'session_revoked' => [
                'label' => 'Device Session Revoked',
                'status_text' => 'Revoked',
                'badge_class' => 'bg-amber-500/15 text-amber-600 dark:text-amber-300 border border-amber-500/30',
                'category' => 'logins',
            ],
            'sessions_revoked' => [
                'label' => 'All Other Sessions Revoked',
                'status_text' => 'Signed Out',
                'badge_class' => 'bg-amber-500/15 text-amber-600 dark:text-amber-300 border border-amber-500/30',
                'category' => 'logins',
            ],
            'security_settings_updated' => [
                'label' => 'Security Policy Updated',
                'status_text' => 'Policy',
                'badge_class' => 'bg-sky-500/15 text-sky-600 dark:text-sky-300 border border-sky-500/30',
                'category' => 'account',
            ],
            '2fa_setup_initiated' => [
                'label' => '2FA Setup Started',
                'status_text' => 'Pending',
                'badge_class' => 'bg-sky-500/15 text-sky-600 dark:text-sky-300 border border-sky-500/30',
                'category' => '2fa',
            ],
            '2fa_enabled' => [
                'label' => '2FA Enabled',
                'status_text' => 'Active',
                'badge_class' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30',
                'category' => '2fa',
            ],
            '2fa_disabled' => [
                'label' => '2FA Disabled',
                'status_text' => 'Changed',
                'badge_class' => 'bg-rose-500/15 text-rose-600 dark:text-rose-300 border border-rose-500/30',
                'category' => '2fa',
            ],
            '2fa_recovery_used' => [
                'label' => 'Recovery Code Sign-In',
                'status_text' => 'Verified',
                'badge_class' => 'bg-amber-500/15 text-amber-600 dark:text-amber-300 border border-amber-500/30',
                'category' => '2fa',
            ],
            '2fa_recovery_codes_regenerated' => [
                'label' => 'Recovery Codes Rotated',
                'status_text' => 'Updated',
                'badge_class' => 'bg-sky-500/15 text-sky-600 dark:text-sky-300 border border-sky-500/30',
                'category' => '2fa',
            ],
            '2fa_failed' => [
                'label' => 'Failed 2FA Challenge',
                'status_text' => 'Failed',
                'badge_class' => 'bg-rose-500/15 text-rose-600 dark:text-rose-300 border border-rose-500/30',
                'category' => 'failed',
            ],
            'password_changed' => [
                'label' => 'Password Updated',
                'status_text' => 'Changed',
                'badge_class' => 'bg-amber-500/15 text-amber-600 dark:text-amber-300 border border-amber-500/30',
                'category' => 'account',
            ],
            'password_reset' => [
                'label' => 'Password Reset',
                'status_text' => 'Changed',
                'badge_class' => 'bg-amber-500/15 text-amber-600 dark:text-amber-300 border border-amber-500/30',
                'category' => 'account',
            ],
            'role_changed' => [
                'label' => 'Workspace Role Changed',
                'status_text' => 'Access',
                'badge_class' => 'bg-amber-500/15 text-amber-600 dark:text-amber-300 border border-amber-500/30',
                'category' => 'account',
            ],
            'telegram_link_initiated', 'telegram_connected', 'telegram_custom_bot_saved', 'telegram_toggled', 'telegram_disconnected' => [
                'label' => ucwords(str_replace('_', ' ', $eventType)),
                'status_text' => 'Integration',
                'badge_class' => 'bg-sky-500/15 text-sky-600 dark:text-sky-300 border border-sky-500/30',
                'category' => 'account',
            ],
            default => [
                'label' => ucwords(str_replace('_', ' ', $eventType)),
                'status_text' => 'Recorded',
                'badge_class' => 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600',
                'category' => 'all',
            ],
        };
    }

    /**
     * Resolve event type list matching a UI filter tab.
     *
     * @return array<int, string>|null
     */
    public static function eventTypesForFilter(?string $filter): ?array
    {
        return match ($filter) {
            'logins' => ['login_success', 'logout', 'session_revoked', 'sessions_revoked'],
            'failed' => ['login_failed', 'login_locked', '2fa_failed'],
            '2fa' => ['2fa_setup_initiated', '2fa_enabled', '2fa_disabled', '2fa_recovery_used', '2fa_recovery_codes_regenerated', '2fa_failed'],
            'account' => ['password_changed', 'password_reset', 'role_changed', 'security_settings_updated', 'telegram_connected', 'telegram_disconnected'],
            default => null,
        };
    }

    private function dispatchSuspiciousLoginAlertIfNeeded(User $user, string $eventType, ?string $ipAddress, string $deviceLabel): void
    {
        if ($eventType === 'login_failed' && (int) $user->failed_login_attempts < 3) {
            return;
        }

        $ownedBusiness = Business::where('owner_id', $user->id)->first();
        if (! $ownedBusiness) {
            return;
        }

        if (! (bool) BusinessSetting::getValue($ownedBusiness->id, 'security.alert_failed_logins', true)) {
            return;
        }

        $safeIp = $ipAddress ?? 'unknown IP';
        $message = $eventType === 'login_locked'
            ? "Sign-in attempts for your account were temporarily rate-limited from {$safeIp} ({$deviceLabel})."
            : "{$user->failed_login_attempts} failed sign-in attempt(s) detected for your account from {$safeIp} ({$deviceLabel}).";

        app(BusinessNotificationService::class)->notifyAccountAlert(
            $ownedBusiness,
            'Security Alert: Unusual Sign-In Activity',
            $message,
            "sec_alert:{$user->id}:{$eventType}:{$safeIp}",
        );
    }

    private function dispatchUnfamiliarIpAlertIfNeeded(User $user, ?string $ipAddress, string $deviceLabel): void
    {
        $ownedBusiness = Business::where('owner_id', $user->id)->first();
        if (! $ownedBusiness) {
            return;
        }

        if (! (bool) BusinessSetting::getValue($ownedBusiness->id, 'security.alert_unfamiliar_login', true)) {
            return;
        }

        $safeIp = $ipAddress ?? 'unknown IP';

        app(BusinessNotificationService::class)->notifyAccountAlert(
            $ownedBusiness,
            'New IP Sign-In Detected',
            "Your SABAYRA account signed in from a previously unseen IP ({$safeIp}, {$deviceLabel}).",
            "new_ip:{$user->id}:{$safeIp}",
        );
    }
}
