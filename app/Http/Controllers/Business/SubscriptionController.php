<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;

class SubscriptionController extends Controller
{
    /**
     * Display Subscription & Billing overview and entitlements.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $subscription = $business->subscriptions()->latest()->first();
        $plansEnabled = (bool) config('subscription.plans_enabled', false);
        $availablePlans = $plansEnabled ? Plan::where('is_active', true)->orderBy('sort_order')->get() : collect();

        return view('business.subscription.index', compact('business', 'subscription', 'plansEnabled', 'availablePlans'));
    }
}
