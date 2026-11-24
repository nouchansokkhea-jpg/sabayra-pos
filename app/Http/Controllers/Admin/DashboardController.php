<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Display the Super Admin platform overview.
     */
    public function index(): View
    {
        // 1. Core Counts from Database
        $totalUsers = User::count();
        $totalBusinesses = Business::count();
        $activeBusinesses = Business::where('status', 'active')->count();
        $suspendedBusinesses = Business::where('status', 'suspended')->count();
        $totalProducts = Product::withoutGlobalScopes()->count();
        $totalOrders = Order::withoutGlobalScopes()->count();

        // 2. Financial Metrics
        $totalSalesUsd = (float) Order::withoutGlobalScopes()
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $totalSalesKhr = (float) Order::withoutGlobalScopes()
            ->where('payment_status', 'paid')
            ->sum('total_khr');

        // Monthly Recurring Revenue (MRR) from active subscriptions
        $mrr = (float) Subscription::where('status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum(DB::raw("CASE WHEN subscriptions.billing_cycle = 'yearly' THEN plans.yearly_price / 12 ELSE plans.monthly_price END"));

        // 3. Businesses by Type
        $businessTypes = Business::select('business_type', DB::raw('count(*) as count'))
            ->groupBy('business_type')
            ->pluck('count', 'business_type')
            ->toArray();

        // 4. Recent Businesses
        $recentBusinesses = Business::with('owner')
            ->latest()
            ->take(6)
            ->get();

        // 5. Recent Registered Platform Users
        $recentUsers = User::latest()
            ->take(6)
            ->get();

        // 6. Recent Activity Logs
        $recentActivities = Activity::with('causer')
            ->latest()
            ->take(8)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalBusinesses',
            'activeBusinesses',
            'suspendedBusinesses',
            'totalProducts',
            'totalOrders',
            'totalSalesUsd',
            'totalSalesKhr',
            'mrr',
            'businessTypes',
            'recentBusinesses',
            'recentUsers',
            'recentActivities'
        ));
    }
}
