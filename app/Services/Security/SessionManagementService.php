<?php

namespace App\Services\Security;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SessionManagementService
{
    public function __construct(private readonly SecurityActivityService $activityService)
    {
    }

    /**
     * Determine whether Laravel's database session table is active and inspectable.
     */
    public function isDatabaseDriverSupported(): bool
    {
        return config('session.driver') === 'database'
            && Schema::hasTable((string) config('session.table', 'sessions'));
    }

    /**
     * List non-expired active sessions for a user without exposing raw session IDs.
     *
     * @return array{
     *     supported: bool,
     *     sessions: Collection<int, array{
     *         session_ref: string,
     *         is_current: bool,
     *         device_label: string,
     *         device_type: string,
     *         browser: string,
     *         os: string,
     *         ip_address: ?string,
     *         user_agent: ?string,
     *         last_active_at: Carbon,
     *         last_active_human: string
     *     }>
     * }
     */
    public function getActiveSessions(User $user, Request $request): array
    {
        $currentSessionId = $request->hasSession() ? (string) $request->session()->getId() : '';

        if (! $this->isDatabaseDriverSupported()) {
            $parsed = $this->activityService->parseUserAgent($request->userAgent());

            return [
                'supported' => false,
                'sessions' => collect([
                    [
                        'session_ref' => $this->signSessionRef($currentSessionId !== '' ? $currentSessionId : 'current'),
                        'is_current' => true,
                        'device_label' => $parsed['label'],
                        'device_type' => $parsed['device_type'],
                        'browser' => $parsed['browser'],
                        'os' => $parsed['os'],
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'last_active_at' => now(),
                        'last_active_human' => 'Active now',
                    ],
                ]),
            ];
        }

        $table = (string) config('session.table', 'sessions');
        $cutoffTimestamp = now()->subMinutes(max(1, (int) config('session.lifetime', 120)))->timestamp;

        $rows = DB::table($table)
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', $cutoffTimestamp)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity']);

        $sessions = $rows->map(function (object $row) use ($currentSessionId): array {
            $parsed = $this->activityService->parseUserAgent($row->user_agent ?? null);
            $lastActive = Carbon::createFromTimestamp((int) $row->last_activity);
            $isCurrent = $currentSessionId !== '' && hash_equals($currentSessionId, (string) $row->id);

            return [
                'session_ref' => $this->signSessionRef((string) $row->id),
                'is_current' => $isCurrent,
                'device_label' => $parsed['label'],
                'device_type' => $parsed['device_type'],
                'browser' => $parsed['browser'],
                'os' => $parsed['os'],
                'ip_address' => $row->ip_address,
                'user_agent' => $row->user_agent,
                'last_active_at' => $lastActive,
                'last_active_human' => $isCurrent ? 'Active now' : $lastActive->diffForHumans(),
            ];
        });

        // Ensure current session is represented even before session middleware flushes on initial login request
        if ($currentSessionId !== '' && $sessions->where('is_current', true)->isEmpty()) {
            $parsed = $this->activityService->parseUserAgent($request->userAgent());
            $sessions->prepend([
                'session_ref' => $this->signSessionRef($currentSessionId),
                'is_current' => true,
                'device_label' => $parsed['label'],
                'device_type' => $parsed['device_type'],
                'browser' => $parsed['browser'],
                'os' => $parsed['os'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_active_at' => now(),
                'last_active_human' => 'Active now',
            ]);
        }

        return [
            'supported' => true,
            'sessions' => $sessions->sortByDesc('is_current')->values(),
        ];
    }

    /**
     * Revoke a single non-current user session by its HMAC-signed reference.
     *
     * @return array{ok: bool, error?: string}
     */
    public function revokeSessionByRef(User $user, string $sessionRef, Request $request): array
    {
        if (! $this->isDatabaseDriverSupported()) {
            return [
                'ok' => false,
                'error' => 'Individual remote session revocation requires database session storage.',
            ];
        }

        $table = (string) config('session.table', 'sessions');
        $currentSessionId = $request->hasSession() ? (string) $request->session()->getId() : '';

        $rows = DB::table($table)
            ->where('user_id', $user->id)
            ->get(['id', 'ip_address', 'user_agent']);

        $matched = null;
        foreach ($rows as $row) {
            if (hash_equals($this->signSessionRef((string) $row->id), $sessionRef)) {
                $matched = $row;
                break;
            }
        }

        if ($matched === null) {
            return [
                'ok' => false,
                'error' => 'Selected session could not be found or has already expired.',
            ];
        }

        if ($currentSessionId !== '' && hash_equals($currentSessionId, (string) $matched->id)) {
            return [
                'ok' => false,
                'error' => 'You cannot revoke your active current device here. Use Sign Out instead.',
            ];
        }

        DB::table($table)
            ->where('id', $matched->id)
            ->where('user_id', $user->id)
            ->delete();

        $parsed = $this->activityService->parseUserAgent($matched->user_agent ?? null);

        $this->activityService->record($user, 'session_revoked', $request, [
            'revoked_ip' => $matched->ip_address,
            'revoked_device' => $parsed['label'],
        ]);

        return ['ok' => true];
    }

    /**
     * Revoke all active sessions for the user except the current session, rotating remember token.
     */
    public function revokeOtherSessions(User $user, Request $request): int
    {
        $revokedCount = 0;

        if ($this->isDatabaseDriverSupported()) {
            $table = (string) config('session.table', 'sessions');
            $currentSessionId = $request->hasSession() ? (string) $request->session()->getId() : '';

            $query = DB::table($table)->where('user_id', $user->id);

            if ($currentSessionId !== '') {
                $query->where('id', '!=', $currentSessionId);
            }

            $revokedCount = (int) $query->delete();
        }

        $user->setRememberToken(Str::random(60));
        $user->save();

        $this->activityService->record($user, 'sessions_revoked', $request, [
            'revoked_count' => $revokedCount,
        ]);

        return $revokedCount;
    }

    /**
     * Compute a non-reversible HMAC reference token so raw session IDs never leave the server.
     */
    public function signSessionRef(string $sessionId): string
    {
        return hash_hmac('sha256', $sessionId, (string) config('app.key'));
    }
}
