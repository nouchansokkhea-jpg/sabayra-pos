<?php

namespace App\Services\AI;

use App\Models\Business;
use App\Models\BusinessAiInsight;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\AI\Data\AIRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AiInsightsService
{
    public function __construct(
        private readonly AIManager $aiManager,
        private readonly AIContextService $contextService,
        private readonly AIUsageService $usageService,
    ) {}

    /**
     * Generate dynamic weekly business summaries and actionable recommendations
     * scoped strictly to the active Business tenant.
     *
     * @return array<string, mixed>
     */
    public function generateWeeklySummary(Business $business): array
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfDay();

        $lastWeekStart = $startOfWeek->copy()->subWeek();
        $lastWeekEnd = $endOfWeek->copy()->subWeek();

        // 1. Tenant-scoped revenue & orders this week vs last week (excluding cancelled/rejected)
        $thisWeekOrders = Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->count();

        $thisWeekRevenue = (float) Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->sum('total_amount');

        $lastWeekOrders = Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
            ->count();

        $lastWeekRevenue = (float) Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
            ->sum('total_amount');

        $revenueGrowth = $lastWeekRevenue > 0
            ? round((($thisWeekRevenue - $lastWeekRevenue) / $lastWeekRevenue) * 100, 1)
            : ($thisWeekRevenue > 0 ? 100.0 : 0.0);

        // 2. Top selling item this week scoped by business_id
        $topProduct = OrderItem::select(
            'order_items.product_name',
            DB::raw('SUM(order_items.quantity) as total_qty'),
            DB::raw('SUM(order_items.subtotal) as total_revenue')
        )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.business_id', $business->id)
            ->whereNotIn('orders.status', ['cancelled', 'rejected'])
            ->whereBetween('orders.created_at', [$startOfWeek, $endOfWeek])
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_qty')
            ->first();

        // 3. Tenant-scoped Customer acquisition
        $newCustomersThisWeek = Customer::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->count();

        $totalCustomers = Customer::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->count();

        // 4. Tenant-scoped Low Stock Checks (ingredients + tracked products)
        $lowStockCount = InventoryItem::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'low_stock_threshold')
            ->count();

        $lowStockProductCount = Product::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        // 5. Truthful actionable recommendations
        $recommendations = [];

        if ($thisWeekOrders === 0 && $lastWeekOrders === 0) {
            $recommendations[] = [
                'type' => 'steady',
                'title' => 'Awaiting Order History',
                'description' => 'No orders have been recorded over the past 14 days yet. Start recording POS or QR orders to unlock deeper performance insights.',
                'priority' => 'low',
            ];
        } elseif ($revenueGrowth > 10) {
            $recommendations[] = [
                'type' => 'growth',
                'title' => 'Strong Revenue Momentum',
                'description' => "Revenue rose by {$revenueGrowth}% compared to last week. Ensure peak-hour staffing and best-seller stock are prepared.",
                'priority' => 'medium',
            ];
        } elseif ($revenueGrowth < -10) {
            $recommendations[] = [
                'type' => 'decline',
                'title' => 'Revenue Dip Detected',
                'description' => 'Revenue dipped by ' . abs($revenueGrowth) . '% vs last week. Review slow-moving items in AI Marketing Studio to run a weekday combo.',
                'priority' => 'high',
            ];
        } else {
            $recommendations[] = [
                'type' => 'steady',
                'title' => 'Steady Performance',
                'description' => 'Sales performance is tracking in line with last week. Consider testing a seasonal special to lift average order value.',
                'priority' => 'low',
            ];
        }

        if ($topProduct) {
            $recommendations[] = [
                'type' => 'product',
                'title' => "Star Performer: {$topProduct->product_name}",
                'description' => "{$topProduct->product_name} led sales with {$topProduct->total_qty} units sold this week.",
                'priority' => 'medium',
            ];
        }

        if ($lowStockCount > 0 || $lowStockProductCount > 0) {
            $totalLow = $lowStockCount + $lowStockProductCount;
            $recommendations[] = [
                'type' => 'inventory',
                'title' => 'Replenishment Alert',
                'description' => "{$totalLow} tracked product(s) or ingredient(s) are at or below low-stock threshold. Review Inventory Forecast before busy shifts.",
                'priority' => 'high',
            ];
        }

        return [
            'period_label' => $startOfWeek->format('M d') . ' - ' . $endOfWeek->format('M d, Y'),
            'period_start' => $startOfWeek->toDateString(),
            'period_end' => $endOfWeek->toDateString(),
            'this_week_revenue' => round($thisWeekRevenue, 2),
            'last_week_revenue' => round($lastWeekRevenue, 2),
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
            'low_stock_alerts' => $lowStockCount + $lowStockProductCount,
            'recommendations' => $recommendations,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Persist real actionable BusinessAiInsight records and optionally enrich with AI brief.
     *
     * @return Collection<int, BusinessAiInsight>
     *
     * @throws AIProviderException
     */
    public function generateAndPersistInsights(
        Business $business,
        User $user,
        bool $withAiExecutiveBrief = true,
        ?string $requestUuid = null
    ): Collection {
        $summary = $this->generateWeeklySummary($business);
        $created = collect();

        if ($withAiExecutiveBrief) {
            $aiManager = $this->aiManager;
            $contextService = $this->contextService;
            $usageService = $this->usageService;
            $featureSettings = $usageService->getOrCreateFeatureSetting($business);

            $lang = (string) ($featureSettings->preferred_language ?? 'auto');
            if ($lang === 'auto') {
                $lang = 'mixed';
            }

            $schema = [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'summary' => ['type' => 'string'],
                    'recommended_action' => ['type' => 'string'],
                    'priority' => ['type' => 'string'],
                ],
                'required' => ['title', 'summary', 'recommended_action', 'priority'],
            ];

            $response = $aiManager->complete($business, new AIRequest(
                feature: 'insights',
                systemPrompt: implode("\n\n", [
                    'You are SABAYRA AI Business Strategist reviewing weekly SME telemetry.',
                    $contextService->formatSystemLanguageInstruction($lang),
                    'Generate ONE crisp executive insight and ONE concrete next action based ONLY on the provided verified weekly metrics. Never invent numbers.',
                    'Priority must be one of: low, medium, high.',
                ]),
                messages: [
                    ['role' => 'user', 'content' => json_encode($summary, JSON_UNESCAPED_UNICODE)],
                ],
                responseSchema: $schema,
                responseSchemaName: 'sabayra_executive_insight',
                requestUuid: $requestUuid,
            ), $user->id);

            $structured = $response->structuredData;
            $priority = in_array(strtolower((string) ($structured['priority'] ?? 'medium')), ['low', 'medium', 'high'], true)
                ? strtolower((string) $structured['priority'])
                : 'medium';

            $created->push(BusinessAiInsight::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'insight_type' => 'executive_brief',
                'title' => (string) ($structured['title'] ?? 'Weekly AI Executive Brief'),
                'summary' => (string) ($structured['summary'] ?? ''),
                'recommended_action' => (string) ($structured['recommended_action'] ?? ''),
                'payload' => $summary,
                'metrics_json' => [
                    'this_week_revenue' => $summary['this_week_revenue'],
                    'this_week_orders' => $summary['this_week_orders'],
                    'revenue_growth_percent' => $summary['revenue_growth_percent'],
                    'usage_log_id' => $response->usageLogId,
                ],
                'priority' => $priority,
                'period_start' => $summary['period_start'],
                'period_end' => $summary['period_end'],
                'status' => 'new',
                'generated_at' => now(),
            ]));
        }

        foreach ($summary['recommendations'] as $rec) {
            if (($rec['priority'] ?? 'low') === 'low' && $withAiExecutiveBrief) {
                continue;
            }

            $created->push(BusinessAiInsight::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'insight_type' => (string) ($rec['type'] ?? 'weekly_rule'),
                'title' => (string) ($rec['title'] ?? 'Business Alert'),
                'summary' => (string) ($rec['description'] ?? ''),
                'recommended_action' => ($rec['type'] ?? '') === 'inventory'
                    ? 'Open Inventory Forecast & Reorder Alerts'
                    : 'Review store report and menu pricing',
                'payload' => $summary,
                'metrics_json' => [
                    'this_week_revenue' => $summary['this_week_revenue'],
                    'this_week_orders' => $summary['this_week_orders'],
                    'low_stock_alerts' => $summary['low_stock_alerts'],
                ],
                'priority' => (string) ($rec['priority'] ?? 'medium'),
                'period_start' => $summary['period_start'],
                'period_end' => $summary['period_end'],
                'status' => 'new',
                'generated_at' => now(),
            ]));
        }

        return $created;
    }
}
