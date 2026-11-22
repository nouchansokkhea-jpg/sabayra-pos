<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    /**
     * Display a listing of all registered businesses.
     */
    public function index(Request $request): View
    {
        $query = Business::with(['owner', 'activeSubscription.plan'])
            ->withCount(['products', 'orders', 'branches', 'members']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by Business Type
        if ($type = $request->input('business_type')) {
            $query->where('business_type', $type);
        }

        // Filter by Status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $businesses = $query->latest()->paginate(15)->withQueryString();
        $businessTypes = config('menuhub.business_types');

        return view('admin.businesses.index', compact('businesses', 'businessTypes'));
    }

    /**
     * Show details of a specific business.
     */
    public function show(Business $business): View
    {
        $business->load([
            'owner',
            'branches',
            'members.user',
            'activeSubscription.plan',
            'subscriptions.plan',
        ]);

        $totalRevenue = (float) $business->orders()
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $totalRevenueKhr = (float) $business->orders()
            ->where('payment_status', 'paid')
            ->sum('total_khr');

        $recentOrders = $business->orders()->latest()->take(5)->get();

        return view('admin.businesses.show', compact('business', 'totalRevenue', 'totalRevenueKhr', 'recentOrders'));
    }

    /**
     * Toggle the status of a business (active <-> suspended).
     */
    public function toggleStatus(Business $business): RedirectResponse
    {
        $newStatus = $business->status === 'active' ? 'suspended' : 'active';
        $business->update(['status' => $newStatus]);

        $msg = $newStatus === 'active'
            ? "Business '{$business->name}' has been activated."
            : "Business '{$business->name}' has been suspended.";

        return back()->with('success', $msg);
    }

    /**
     * Delete a business with safety confirmation.
     */
    public function destroy(Business $business): RedirectResponse
    {
        $name = $business->name;
        $business->delete();

        return redirect()->route('admin.businesses.index')
            ->with('success', "Business '{$name}' has been deleted.");
    }
}
