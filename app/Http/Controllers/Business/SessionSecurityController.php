<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Services\Security\SecurityActivityService;
use App\Services\Security\SessionManagementService;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionSecurityController extends Controller
{
    public function __construct(
        private readonly SessionManagementService $sessionService,
        private readonly SecurityActivityService $activityService
    ) {
    }

    /**
     * Revoke a single remote device session belonging to the authenticated user.
     */
    public function destroy(Request $request, string $sessionRef): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $result = $this->sessionService->revokeSessionByRef($user, $sessionRef, $request);

        if (! $result['ok']) {
            return redirect()
                ->route('business.settings.index', ['section' => 'security'])
                ->with('error', $result['error'] ?? 'Unable to revoke session.');
        }

        return redirect()
            ->route('business.settings.index', ['section' => 'security'])
            ->with('status', 'Selected device session has been signed out.');
    }

    /**
     * Revoke all active sessions except the current device after confirming current password.
     */
    public function destroyOthers(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validateWithBag('revokeSessions', [
            'current_password' => ['required', 'current_password:web'],
        ]);

        $revokedCount = $this->sessionService->revokeOtherSessions($user, $request);

        return redirect()
            ->route('business.settings.index', ['section' => 'security'])
            ->with('status', "Signed out {$revokedCount} other active device session(s) and rotated remember tokens.");
    }

    /**
     * Update workspace-level security policy settings for the active business.
     */
    public function updateSecuritySettings(Request $request): RedirectResponse
    {
        $business = TenantContext::get();
        /** @var User|null $user */
        $user = Auth::user();

        if (! $business instanceof Business || ! $user || ! $user->canManageBusinessSettings($business)) {
            abort(403, 'You do not have permission to manage workspace security settings.');
        }

        $validated = $request->validate([
            'session_timeout_minutes' => ['required', 'integer', 'in:0,15,30,60,120,480'],
            'alert_unfamiliar_login' => ['nullable', 'boolean'],
            'alert_failed_logins' => ['nullable', 'boolean'],
        ]);

        $timeoutMinutes = (int) $validated['session_timeout_minutes'];
        $alertUnfamiliar = $request->boolean('alert_unfamiliar_login');
        $alertFailed = $request->boolean('alert_failed_logins');

        BusinessSetting::setValue($business->id, 'security.session_timeout_minutes', $timeoutMinutes);
        BusinessSetting::setValue($business->id, 'security.alert_unfamiliar_login', $alertUnfamiliar);
        BusinessSetting::setValue($business->id, 'security.alert_failed_logins', $alertFailed);

        if ($timeoutMinutes > 0 && $request->hasSession()) {
            $request->session()->put('sabayra_last_seen_at', now()->timestamp);
        }

        $this->activityService->record($user, 'security_settings_updated', $request, [
            'session_timeout_minutes' => $timeoutMinutes,
            'alert_unfamiliar_login' => $alertUnfamiliar,
            'alert_failed_logins' => $alertFailed,
        ], $business->id);

        return redirect()
            ->route('business.settings.index', ['section' => 'security'])
            ->with('status', 'Workspace security settings updated.');
    }
}
