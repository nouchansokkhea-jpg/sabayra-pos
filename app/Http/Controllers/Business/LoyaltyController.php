<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    /**
     * Display customer loyalty & reward points program.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $topMembers = Customer::where('business_id', $business->id)
            ->where('loyalty_points', '>', 0)
            ->orderByDesc('loyalty_points')
            ->take(15)
            ->get();

        $settings = $business->theme_settings['loyalty'] ?? [
            'points_per_dollar' => 10,
            'redemption_rate' => 100, // 100 pts = $1
            'is_enabled' => true,
        ];

        return view('business.loyalty.index', compact('business', 'topMembers', 'settings'));
    }

    /**
     * Update loyalty program configuration.
     */
    public function update(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'points_per_dollar' => 'required|integer|min:1|max:1000',
            'redemption_rate' => 'required|integer|min:10|max:10000',
            'is_enabled' => 'nullable|boolean',
        ]);

        $settings = $business->theme_settings ?? [];
        $settings['loyalty'] = [
            'points_per_dollar' => (int) $validated['points_per_dollar'],
            'redemption_rate' => (int) $validated['redemption_rate'],
            'is_enabled' => $request->boolean('is_enabled'),
        ];

        $business->update(['theme_settings' => $settings]);

        return back()->with('status', 'Loyalty program settings updated successfully!');
    }
}
