<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessBranch;
use App\Models\DeliveryZone;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryZoneController extends Controller
{
    /**
     * Display delivery zones and rates.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $zones = DeliveryZone::with('branch')->where('business_id', $business->id)->get();
        $branches = BusinessBranch::where('business_id', $business->id)->get();

        return view('business.delivery_zones.index', compact('business', 'zones', 'branches'));
    }

    /**
     * Store a delivery zone.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'zone_name' => 'required|string|max:100',
            'delivery_fee' => 'required|numeric|min:0',
            'min_order_amount' => 'required|numeric|min:0',
            'free_delivery_threshold' => 'nullable|numeric|min:0',
            'estimated_delivery_minutes' => 'required|integer|min:5|max:180',
            'branch_id' => 'nullable|uuid|exists:business_branches,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['business_id'] = $business->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        DeliveryZone::create($validated);

        return back()->with('status', 'Delivery zone created successfully!');
    }

    /**
     * Delete a delivery zone.
     */
    public function destroy(DeliveryZone $zone): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($zone->business_id !== $business->id, 403);

        $zone->delete();

        return back()->with('status', 'Delivery zone removed.');
    }
}
