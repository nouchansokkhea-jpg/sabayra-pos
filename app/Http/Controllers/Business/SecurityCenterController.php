<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\SecurityLog;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityCenterController extends Controller
{
    /**
     * Display Security Center overview, 2FA status, and login audit logs.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $user = Auth::user();

        $logs = SecurityLog::where('user_id', $user->id)
            ->latest('created_at')
            ->take(15)
            ->get();

        return view('business.security.index', compact('business', 'user', 'logs'));
    }

    /**
     * Toggle two-factor authentication flag for current user account.
     */
    public function toggleTwoFactor(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $enabled = !$user->two_factor_enabled;

        $user->update([
            'two_factor_enabled' => $enabled,
            'two_factor_secret' => $enabled ? bin2hex(random_bytes(16)) : null,
        ]);

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => TenantContext::get()?->id,
            'event_type' => $enabled ? '2fa_enabled' : '2fa_disabled',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'Two-Factor Authentication (2FA) status updated successfully.');
    }
}
