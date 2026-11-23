<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Display promotional coupons and discount vouchers.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $coupons = Coupon::where('business_id', $business->id)->latest()->get();

        return view('business.coupons.index', compact('business', 'coupons'));
    }

    /**
     * Create a promotional coupon.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'code' => 'required|string|max:50|alpha_dash',
            'description' => 'nullable|string|max:255',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0.1',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $validated['business_id'] = $business->id;
        $validated['code'] = strtoupper($validated['code']);
        $validated['min_order_amount'] = $validated['min_order_amount'] ?? 0.00;
        $validated['is_active'] = true;

        Coupon::create($validated);

        return back()->with('status', "Coupon {$validated['code']} created successfully!");
    }

    /**
     * Toggle coupon active status.
     */
    public function toggleStatus(Coupon $coupon): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($coupon->business_id !== $business->id, 403);

        $coupon->update(['is_active' => !$coupon->is_active]);

        return back()->with('status', "Coupon {$coupon->code} is now " . ($coupon->is_active ? 'active' : 'inactive') . '.');
    }

    /**
     * Delete a coupon.
     */
    public function destroy(Coupon $coupon): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($coupon->business_id !== $business->id, 403);

        $coupon->delete();

        return back()->with('status', 'Coupon deleted.');
    }
}
