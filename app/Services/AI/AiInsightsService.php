<?php

namespace App\Services\AI;

use App\Models\Business;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AiInsightsService
{
    /**
     * Generate dynamic weekly business summaries and actionable recommendations
     * based on actual order, revenue, customer, and inventory data.
     */
    public function generateWeeklySummary(Business $business): array
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $lastWeekStart = $now->copy()->subWeek()->startOfWeek();
        $lastWeekEnd = $now->copy()->subWeek()->endOfWeek();

        // 1. Revenue & Orders this week vs last week
        $thisWeekOrders = Order::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
        $thisWeekRevenue = (float) Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->sum('total_amount');

        $lastWeekOrders = Order::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();
        $lastWeekRevenue = (float) Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
            ->sum('total_amount');

        $revenueGrowth = $lastWeekRevenue > 0
            ? round((($thisWeekRevenue - $lastWeekRevenue) / $lastWeekRevenue) * 100, 1)
            : ($thisWeekRevenue > 0 ? 100 : 0);

        // 2. Product Velocity
        $topProduct = OrderItem::select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
            ->whereHas('order', fn($q) => $q->where('business_id', $business->id)->whereBetween('created_at', [$startOfWeek, $endOfWeek]))
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->first();

        // 3. Customer Trends
        $newCustomersThisWeek = Customer::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
        $totalCustomers = Customer::count();

        // 4. Low stock inventory risks
        $lowStockCount = InventoryItem::whereColumn('current_stock', '<=', 'low_stock_threshold')->count();
        $lowStockProductCount = Product::where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        // 5. Generate Actionable AI Recommendations
        $recommendations = [];

        if ($revenueGrowth >= 10) {
            $recommendations[] = [
                'type' => 'growth',
                'title' => 'Momentum High',
                'description' => "Revenue is up {$revenueGrowth}% compared to last week. Consider featuring complementary addons to boost basket size even further.",
                'priority' => 'medium',
            ];
        } elseif ($revenueGrowth < -5) {
            $recommendations[] = [
                'type' => 'retention',
                'title' => 'Revenue Dip Detected',
                'description' => "Revenue decreased by " . abs($revenueGrowth) . "% this week. Activate a Happy Hour or send a promotional coupon to re-engage past customers.",
                'priority' => 'high',
            ];
        } else {
            $recommendations[] = [
                'type' => 'steady',
                'title' => 'Steady Performance',
                'description' => 'Sales performance is consistent with previous patterns. Test a seasonal special to spur higher order volume.',
                'priority' => 'low',
            ];
        }

        if ($topProduct) {
            $recommendations[] = [
                'type' => 'product',
                'title' => "Star Performer: {$topProduct->product_name}",
                'description' => "{$topProduct->product_name} accounted for the highest sales volume ({$topProduct->total_qty} units sold). Ensure ingredient stocks are well-replenished.",
                'priority' => 'medium',
            ];
        }

        if ($lowStockCount > 0 || $lowStockProductCount > 0) {
            $totalLow = $lowStockCount + $lowStockProductCount;
            $recommendations[] = [
                'type' => 'inventory',
                'title' => 'Replenishment Alert',
                'description' => "{$totalLow} items or ingredients are at or below their low-stock thresholds. Check inventory before the weekend rush.",
                'priority' => 'high',
            ];
        }

        return [
            'period_label' => $startOfWeek->format('M d') . ' - ' . $endOfWeek->format('M d, Y'),
            'this_week_revenue' => $thisWeekRevenue,
            'last_week_revenue' => $lastWeekRevenue,
            'revenue_growth_percent' => $revenueGrowth,
            'this_week_orders' => $thisWeekOrders,
            'last_week_orders' => $lastWeekOrders,
            'top_product' => $topProduct ? [
                'name' => $topProduct->product_name,
                'quantity' => (float) $topProduct->total_qty,
                'revenue' => (float) $topProduct->total_revenue,
            ] : null,
            'new_customers' => $newCustomersThisWeek,
            'total_customers' => $totalCustomers,
            'recommendations' => $recommendations,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
