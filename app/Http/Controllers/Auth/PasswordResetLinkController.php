<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'email' => ['required', 'email'],
            ]);

            $email = $request->input('email');
            $user = User::where('email', $email)->first();

            if (!$user) {
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'We could not find an account with that email address.']);
            }

            // 1. Try standard Password broker reset link dispatch
            try {
                $status = Password::sendResetLink(
                    $request->only('email')
                );

                if ($status === Password::RESET_LINK_SENT) {
                    return back()->with('status', __($status));
                }

                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => __($status)]);
            } catch (\Throwable $mailException) {
                // If mail sending / SMTP fails on hosting, provide direct self-service reset link
                Log::warning('Password reset email dispatch fallback: ' . $mailException->getMessage());

                $token = Password::createToken($user);
                $directResetUrl = route('password.reset', ['token' => $token, 'email' => $email]);

                return back()
                    ->with('status', 'A password recovery link has been prepared for your account.')
                    ->with('direct_reset_url', $directResetUrl);
            }
        } catch (\Throwable $e) {
            Log::error('Password reset store error: ' . $e->getMessage());
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Unable to process reset request: ' . $e->getMessage()]);
        }
    }
}
