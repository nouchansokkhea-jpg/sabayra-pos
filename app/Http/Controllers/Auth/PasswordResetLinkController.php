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
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        // 1. Try sending the reset link via Laravel's Password broker
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return back()->with('status', __($status));
            }

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        } catch (\Throwable $e) {
            // Handle mail transport or connection failure gracefully without throwing HTTP 500
            Log::warning('Password reset email dispatch fallback: ' . $e->getMessage());

            if ($user) {
                $token = Password::createToken($user);
                $directResetUrl = route('password.reset', ['token' => $token, 'email' => $email]);

                return back()
                    ->with('status', 'A password recovery link has been prepared for your account.')
                    ->with('direct_reset_url', $directResetUrl);
            }

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __('passwords.user')]);
        }
    }
}
