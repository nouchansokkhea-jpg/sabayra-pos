<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\TwoFactorAuthenticationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityCenterController extends Controller
{
    /**
     * Display Security Center overview inside the unified Settings & Security workspace.
     */
    public function index(Request $request): View
    {
        $viewData = SettingController::buildViewData($request, 'security');

        return view('business.security.index', $viewData);
    }

    /**
     * Legacy 2FA toggle endpoint upgraded to initiate verified setup (never bypassing TOTP confirmation).
     */
    public function toggleTwoFactor(Request $request, TwoFactorAuthenticationService $twoFactorService): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            $twoFactorService->beginSetup($user);

            return redirect()
                ->route('business.settings.index', ['section' => 'security'])
                ->with('status', 'Scan the QR code with your authenticator app and confirm your 6-digit code to activate 2FA.');
        }

        $request->validateWithBag('twoFactorDisable', [
            'current_password' => ['required', 'current_password:web'],
        ]);

        $twoFactorService->disable($user);

        return redirect()
            ->route('business.settings.index', ['section' => 'security'])
            ->with('status', 'Two-Factor Authentication disabled successfully.');
    }
}
