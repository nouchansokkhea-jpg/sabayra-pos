<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    /**
     * Display customer referral program and campaigns.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $referrals = Referral::where('business_id', $business->id)->latest()->get();

        $stats = [
            'total_advocates' => $referrals->count(),
            'total_referrals_completed' => (int) $referrals->sum('times_used'),
        ];

        return view('business.referrals.index', compact('business', 'referrals', 'stats'));
    }

    /**
     * Create a referral code for a customer or promoter.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'referrer_name' => 'required|string|max:255',
            'referrer_phone' => 'nullable|string|max:50',
            'reward_description' => 'required|string|max:255',
            'referral_code' => 'nullable|string|max:50',
        ]);

        $code = !empty($validated['referral_code'])
            ? strtoupper(Str::slug($validated['referral_code']))
            : strtoupper(substr(Str::slug($validated['referrer_name']), 0, 4) . rand(100, 999));

        Referral::create([
            'business_id' => $business->id,
            'referral_code' => $code,
            'referrer_name' => $validated['referrer_name'],
            'referrer_phone' => $validated['referrer_phone'] ?? null,
            'reward_description' => $validated['reward_description'],
            'is_active' => true,
        ]);

        return back()->with('status', "Referral code {$code} generated successfully!");
    }

    /**
     * Delete a referral code.
     */
    public function destroy(Referral $referral): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($referral->business_id !== $business->id, 403);

        $referral->delete();

        return back()->with('status', 'Referral code removed.');
    }
}
