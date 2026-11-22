<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display Platform-wide Financial and Growth Analytics.
     */
    public function index(Request $request): View
    {
        // 1. Subscription Revenue
        $totalSaaSRevenue = (float) SubscriptionPayment::where('status', 'completed')->sum('amount');

        $mrr = (float) Subscription::where('status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum(DB::raw("CASE WHEN subscriptions.billing_cycle = 'yearly' THEN plans.yearly_price / 12 ELSE plans.monthly_price END"));

        $arr = $mrr * 12;

        // 2. Merchant GMV
        $totalMerchantGMV = (float) Order::withoutGlobalScopes()->where('payment_status', 'paid')->sum('total_amount');
        $totalOrders = Order::withoutGlobalScopes()->count();
        $completedOrders = Order::withoutGlobalScopes()->where('status', 'completed')->count();

        // 3. Plan Distribution
        $planBreakdown = Plan::withCount(['subscriptions as active_subs' => function ($q) {
            $q->where('status', 'active');
        }])->get();

        // 4. Payment Methods Breakdown
        $saasPaymentMethods = SubscriptionPayment::where('status', 'completed')
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // 5. Top Businesses by Sales Volume
        $topBusinesses = Business::withCount('orders')
            ->get()
            ->map(function ($b) {
                $b->total_sales = (float) Order::withoutGlobalScopes()
                    ->where('business_id', $b->id)
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');
                return $b;
            })
            ->sortByDesc('total_sales')
            ->take(5);

        return view('admin.analytics.index', compact(
            'totalSaaSRevenue',
            'mrr',
            'arr',
            'totalMerchantGMV',
            'totalOrders',
            'completedOrders',
            'planBreakdown',
            'saasPaymentMethods',
            'topBusinesses'
        ));
    }
}
