<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display deep commercial analytics, peak hours, and category breakdowns.
     */
    public function index(): View
    {
        $business = TenantContext::get();

        // 1. Hourly Peak Distribution (from actual orders)
        $isPgsql = DB::connection()->getDriverName() === 'pgsql';
        $hourExpr = $isPgsql ? 'EXTRACT(HOUR FROM created_at)::INTEGER as hour' : "CAST(strftime('%H', created_at) AS INTEGER) as hour";

        $hourlyDistribution = Order::select(DB::raw($hourExpr), DB::raw('COUNT(*) as total_orders'))
            ->where('business_id', $business->id)
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total_orders', 'hour')
            ->toArray();

        $peakHours = [];
        for ($h = 6; $h <= 23; $h++) {
            $label = ($h < 12 ? $h . ' AM' : ($h == 12 ? '12 PM' : ($h - 12) . ' PM'));
            $peakHours[] = [
                'hour' => $label,
                'count' => $hourlyDistribution[$h] ?? 0,
            ];
        }

        // 2. Category Revenue Share
        $categoryBreakdown = DB::table('order_items as oi')
            ->join('orders as o', 'oi.order_id', '=', 'o.id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('o.business_id', $business->id)
            ->where('o.payment_status', 'paid')
            ->select(
                DB::raw("COALESCE(c.name_en, 'Uncategorized') as category_name"),
                DB::raw('SUM(oi.subtotal) as total_revenue')
            )
            ->groupBy('category_name')
            ->orderByDesc('total_revenue')
            ->take(6)
            ->get();

        // 3. Order Types (Dine-in, Takeaway, Delivery, POS)
        $orderTypes = Order::select('order_type', DB::raw('COUNT(*) as total_count'))
            ->where('business_id', $business->id)
            ->groupBy('order_type')
            ->pluck('total_count', 'order_type')
            ->toArray();

        // 4. Payment Methods Share (Cash, KHQR, Card)
        $paymentMethods = Order::select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as amount'))
            ->where('business_id', $business->id)
            ->where('payment_status', 'paid')
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->get();

        return view('business.analytics.index', compact(
            'business',
            'peakHours',
            'categoryBreakdown',
            'orderTypes',
            'paymentMethods'
        ));
    }
}
