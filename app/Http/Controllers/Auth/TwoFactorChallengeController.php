<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecurityLog;
use App\Models\User;
use App\Services\Security\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(private readonly TwoFactorAuthenticationService $twoFactorService)
    {
    }

    /**
     * Display the two-factor authentication verification challenge page.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $pending = $this->resolvePendingChallenge($request);

        if (! $pending) {
            return redirect()->route('login')
                ->with('status', 'Please enter your email and password to sign in.');
        }

        return view('auth.two-factor-challenge', [
            'userEmailMasked' => $this->maskEmail((string) $pending['user']->email),
        ]);
    }

    /**
     * Verify a submitted TOTP or backup recovery code and complete sign-in.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $pending = $this->resolvePendingChallenge($request);

        if (! $pending) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Your two-factor login session expired. Please sign in again.']);
        }

        /** @var User $user */
        $user = $pending['user'];
        $throttleKey = '2fa_challenge:' . $user->id . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'code' => "Too many verification attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:32'],
            'recovery_code' => ['nullable', 'string', 'max:32'],
        ]);

        $submittedCode = trim((string) (($validated['recovery_code'] ?? null) ?: ($validated['code'] ?? '')));

        if ($submittedCode === '') {
            throw ValidationException::withMessages([
                'code' => 'Please enter your 6-digit authenticator code or a recovery code.',
            ]);
        }

        $verification = $this->twoFactorService->verifyChallenge($user, $submittedCode);

        if (! $verification['ok']) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'code' => 'The provided two-factor authentication or recovery code is invalid.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->forget('auth.two_factor_pending');

        Auth::login($user, (bool) $pending['remember']);
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
        ])->saveQuietly();

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => $request->session()->get('active_business_id'),
            'event_type' => 'login_success',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['two_factor_method' => $verification['method'] ?? 'totp'],
        ]);

        if (($verification['method'] ?? null) === 'recovery_code') {
            $remaining = (int) ($verification['remaining_recovery_codes'] ?? 0);
            session()->flash('status', "Signed in using a backup recovery code. {$remaining} unused recovery code(s) remaining.");
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Resolve and validate the active pending 2FA login challenge from session.
     *
     * @return array{user: User, remember: bool}|null
     */
    private function resolvePendingChallenge(Request $request): ?array
    {
        $payload = $request->session()->get('auth.two_factor_pending');

        if (! is_array($payload) || empty($payload['user_id']) || empty($payload['expires_at'])) {
            return null;
        }

        if (now()->timestamp > (int) $payload['expires_at']) {
            $request->session()->forget('auth.two_factor_pending');

            return null;
        }

        $user = User::find($payload['user_id']);

        if (! $user || ! $user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->forget('auth.two_factor_pending');

            return null;
        }

        return [
            'user' => $user,
            'remember' => (bool) ($payload['remember'] ?? false),
        ];
    }

    private function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $prefix = substr($local, 0, min(2, strlen($local)));

        return $prefix . str_repeat('*', max(2, strlen($local) - strlen($prefix))) . '@' . $domain;
    }
}
