<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorAuthenticationService $twoFactorService)
    {
    }

    /**
     * Initiate TOTP setup and generate an unconfirmed QR code and secret key for the current user.
     */
    public function setup(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return redirect()
                ->route('business.settings.index', ['section' => 'security'])
                ->with('status', 'Two-Factor Authentication is already active on your account.');
        }

        $this->twoFactorService->beginSetup($user);

        return redirect()
            ->route('business.settings.index', ['section' => 'security'])
            ->with('status', 'Scan the QR code with your authenticator app and enter a 6-digit code to activate 2FA.');
    }

    /**
     * Confirm initial TOTP verification code, activate 2FA, and flash recovery codes once.
     */
    public function confirm(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validateWithBag('twoFactorConfirm', [
            'code' => ['required', 'string', 'max:16'],
        ]);

        $result = $this->twoFactorService->confirmSetup($user, $validated['code']);

        if (! $result['ok']) {
            return redirect()
                ->route('business.settings.index', ['section' => 'security'])
                ->withErrors(['code' => $result['error'] ?? 'Verification code is invalid.'], 'twoFactorConfirm');
        }

        return redirect()
            ->route('business.settings.index', ['section' => 'security'])
            ->with('two_factor_recovery_codes_plain', $result['recovery_codes'] ?? [])
            ->with('status', 'Two-Factor Authentication is now enabled. Store your backup recovery codes in a safe place.');
    }

    /**
     * Cancel an unconfirmed pending TOTP setup.
     */
    public function cancel(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $this->twoFactorService->cancelSetup($user);

        return redirect()
            ->route('business.settings.index', ['section' => 'security'])
            ->with('status', 'Pending Two-Factor Authentication setup cancelled.');
    }

    /**
     * Regenerate single-use recovery codes after verifying current account password.
     */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return redirect()
                ->route('business.settings.index', ['section' => 'security'])
                ->with('error', 'Enable Two-Factor Authentication before regenerating recovery codes.');
        }

        $request->validateWithBag('twoFactorRecovery', [
            'current_password' => ['required', 'current_password:web'],
        ]);

        $codes = $this->twoFactorService->regenerateRecoveryCodes($user);

        return redirect()
            ->route('business.settings.index', ['section' => 'security'])
            ->with('two_factor_recovery_codes_plain', $codes)
            ->with('status', 'New backup recovery codes generated. Previously unused codes are now invalidated.');
    }

    /**
     * Disable Two-Factor Authentication after verifying current account password.
     */
    public function disable(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validateWithBag('twoFactorDisable', [
            'current_password' => ['required', 'current_password:web'],
        ]);

        $this->twoFactorService->disable($user);
        session()->forget('two_factor_recovery_codes_plain');

        return redirect()
            ->route('business.settings.index', ['section' => 'security'])
            ->with('status', 'Two-Factor Authentication has been disabled for your account.');
    }
}
