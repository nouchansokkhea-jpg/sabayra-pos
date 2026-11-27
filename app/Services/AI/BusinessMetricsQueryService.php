<?php

namespace App\Services\AI;

use App\Models\Business;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BusinessMetricsQueryService
{
    /**
     * Allowlisted read-only function tool schemas exposed to the AI Business Assistant.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toolDeclarations(): array
    {
        return [
            [
                'name' => 'get_sales_summary',
                'description' => 'Get verified revenue, order count, average order value, tax, service charge, and comparison vs prior equal period.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'days' => [
                            'type' => 'integer',
                            'description' => 'Number of days to summarize (1 to 90, default 7).',
                        ],
                    ],
                    'required' => ['days'],
                ],
            ],
            [
                'name' => 'get_top_selling_products',
                'description' => 'Get top-selling menu items or products by units sold and revenue over recent days.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'days' => [
                            'type' => 'integer',
                            'description' => 'Lookback window in days (1 to 90, default 30).',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Max products to return (1 to 20, default 10).',
                        ],
                    ],
                    'required' => ['days'],
                ],
            ],
            [
                'name' => 'get_low_selling_products',
                'description' => 'Find active products with the lowest or zero sales quantities in recent days to help review slow movers.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'days' => [
                            'type' => 'integer',
                            'description' => 'Lookback window in days (1 to 90, default 30).',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Max slow-moving products to return (1 to 20, default 10).',
                        ],
                    ],
                    'required' => ['days'],
                ],
            ],
            [
                'name' => 'get_low_stock_products',
                'description' => 'Check both tracked products and raw ingredients currently at or below their low-stock threshold.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Max low-stock items to return (1 to 30, default 15).',
                        ],
                    ],
                    'required' => ['limit'],
                ],
            ],
            [
                'name' => 'get_expense_and_profit_summary',
                'description' => 'Summarize paid revenue, recorded operating expenses by category, estimated product cost (where entered), and net cash flow.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'days' => [
                            'type' => 'integer',
                            'description' => 'Lookback window in days (1 to 90, default 30).',
                        ],
                    ],
                    'required' => ['days'],
                ],
            ],
            [
                'name' => 'get_peak_hours',
                'description' => 'Analyze busiest order hours (Asia/Phnom_Penh local time) and busiest days of week.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'days' => [
                            'type' => 'integer',
                            'description' => 'Lookback window in days (1 to 90, default 30).',
                        ],
                    ],
                    'required' => ['days'],
                ],
            ],
            [
                'name' => 'get_payment_method_breakdown',
                'description' => 'Compare orders and totals across payment methods (Cash, Bakong KHQR, ABA PayWay, Bank Transfer, QR, COD) and payment status.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'days' => [
                            'type' => 'integer',
                            'description' => 'Lookback window in days (1 to 90, default 30).',
                        ],
                    ],
                    'required' => ['days'],
                ],
            ],
        ];
    }

    /**
     * Dispatch an allowlisted read-only query strictly scoped to the provided Business tenant.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function executeTool(Business $business, string $toolName, array $arguments = []): array
    {
        return match ($toolName) {
            'get_sales_summary' => $this->getSalesSummary($business, (int) ($arguments['days'] ?? 7)),
            'get_top_selling_products' => $this->getTopSellingProducts($business, (int) ($arguments['days'] ?? 30), (int) ($arguments['limit'] ?? 10)),
            'get_low_selling_products' => $this->getLowSellingProducts($business, (int) ($arguments['days'] ?? 30), (int) ($arguments['limit'] ?? 10)),
            'get_low_stock_products' => $this->getLowStockProducts($business, (int) ($arguments['limit'] ?? 15)),
            'get_expense_and_profit_summary' => $this->getExpenseAndProfitSummary($business, (int) ($arguments['days'] ?? 30)),
            'get_peak_hours' => $this->getPeakHours($business, (int) ($arguments['days'] ?? 30)),
            'get_payment_method_breakdown' => $this->getPaymentMethodBreakdown($business, (int) ($arguments['days'] ?? 30)),
            default => ['error' => "Unsupported read-only tool '{$toolName}'."],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function getSalesSummary(Business $business, int $days = 7): array
    {
        $days = max(1, min(90, $days));
        $tz = config('app.timezone', 'Asia/Phnom_Penh');
        $end = Carbon::now($tz)->endOfDay();
        $start = Carbon::now($tz)->subDays($days - 1)->startOfDay();

        $prevEnd = (clone $start)->subSecond();
        $prevStart = (clone $start)->subDays($days)->startOfDay();

        $base = Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereNotIn('status', ['cancelled', 'rejected']);

        $currentOrders = (clone $base)->whereBetween('created_at', [$start, $end]);
        $paidCurrent = (clone $currentOrders)->where('payment_status', 'paid');

        $totalOrders = (clone $currentOrders)->count();
        $paidOrdersCount = (clone $paidCurrent)->count();
        $paidRevenue = round((float) (clone $paidCurrent)->sum('total_amount'), 2);
        $grossOrderValue = round((float) (clone $currentOrders)->sum('total_amount'), 2);
        $discounts = round((float) (clone $paidCurrent)->sum('discount_amount'), 2);
        $tax = round((float) (clone $paidCurrent)->sum('tax_amount'), 2);
        $serviceCharge = round((float) (clone $paidCurrent)->sum('service_charge_amount'), 2);
        $avgOrderValue = $paidOrdersCount > 0 ? round($paidRevenue / $paidOrdersCount, 2) : 0.0;

        $prevRevenue = round((float) (clone $base)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->sum('total_amount'), 2);

        $deltaPct = $prevRevenue > 0
            ? round((($paidRevenue - $prevRevenue) / $prevRevenue) * 100, 1)
            : null;

        $rate = (float) ($business->exchange_rate_khr ?: config('menuhub.default_exchange_rate_khr', 4100));

        return [
            'period_days' => $days,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'currency' => $business->currency ?? 'USD',
            'exchange_rate_khr' => $rate,
            'total_orders' => $totalOrders,
            'paid_orders_count' => $paidOrdersCount,
            'paid_revenue_usd' => $paidRevenue,
            'paid_revenue_khr' => round($paidRevenue * $rate, 0),
            'gross_orders_including_unpaid_usd' => $grossOrderValue,
            'discounts_usd' => $discounts,
            'tax_collected_usd' => $tax,
            'service_charge_usd' => $serviceCharge,
            'average_paid_order_usd' => $avgOrderValue,
            'previous_period_paid_revenue_usd' => $prevRevenue,
            'revenue_change_pct' => $deltaPct,
            'has_data' => $totalOrders > 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getTopSellingProducts(Business $business, int $days = 30, int $limit = 10): array
    {
        $days = max(1, min(90, $days));
        $limit = max(1, min(20, $limit));
        $since = Carbon::now()->subDays($days - 1)->startOfDay();

        $rows = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.business_id', $business->id)
            ->where('orders.created_at', '>=', $since)
            ->whereNotIn('orders.status', ['cancelled', 'rejected'])
            ->groupBy('order_items.product_id', 'order_items.product_name', 'products.name_km', 'products.cost_price')
            ->select([
                'order_items.product_id',
                'order_items.product_name',
                'products.name_km',
                'products.cost_price',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as order_count'),
            ])
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();

        $items = $rows->map(static function ($row): array {
            $qty = round((float) $row->total_qty, 2);
            $rev = round((float) $row->total_revenue, 2);
            $costPerUnit = $row->cost_price !== null ? (float) $row->cost_price : null;
            $estimatedMargin = ($costPerUnit !== null && $costPerUnit > 0)
                ? round($rev - ($costPerUnit * $qty), 2)
                : null;

            return [
                'product_id' => $row->product_id,
                'name_en' => $row->product_name,
                'name_km' => $row->name_km,
                'quantity_sold' => $qty,
                'revenue_usd' => $rev,
                'orders_count' => (int) $row->order_count,
                'estimated_gross_profit_usd' => $estimatedMargin,
            ];
        })->all();

        return [
            'period_days' => $days,
            'items' => $items,
            'has_data' => $items !== [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getLowSellingProducts(Business $business, int $days = 30, int $limit = 10): array
    {
        $days = max(1, min(90, $days));
        $limit = max(1, min(20, $limit));
        $since = Carbon::now()->subDays($days - 1)->startOfDay();

        $salesByProduct = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.business_id', $business->id)
            ->where('orders.created_at', '>=', $since)
            ->whereNotIn('orders.status', ['cancelled', 'rejected'])
            ->whereNotNull('order_items.product_id')
            ->groupBy('order_items.product_id')
            ->select([
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.subtotal) as total_revenue'),
            ])
            ->get()
            ->keyBy('product_id');

        $products = Product::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_available', true)
            ->get(['id', 'name_en', 'name_km', 'base_price']);

        $ranked = $products->map(static function (Product $product) use ($salesByProduct): array {
            $sold = $salesByProduct->get($product->id);

            return [
                'product_id' => $product->id,
                'name_en' => $product->name_en,
                'name_km' => $product->name_km,
                'price_usd' => (float) $product->base_price,
                'quantity_sold' => $sold ? round((float) $sold->total_qty, 2) : 0.0,
                'revenue_usd' => $sold ? round((float) $sold->total_revenue, 2) : 0.0,
            ];
        })
            ->sortBy('quantity_sold')
            ->take($limit)
            ->values()
            ->all();

        return [
            'period_days' => $days,
            'items' => $ranked,
            'has_data' => $ranked !== [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getLowStockProducts(Business $business, int $limit = 15): array
    {
        $limit = max(1, min(30, $limit));

        $lowProducts = Product::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->take($limit)
            ->get(['id', 'name_en', 'name_km', 'stock_quantity', 'low_stock_threshold', 'unit'])
            ->map(static fn (Product $p): array => [
                'source' => 'product',
                'id' => $p->id,
                'name_en' => $p->name_en,
                'name_km' => $p->name_km,
                'current_stock' => round((float) $p->stock_quantity, 2),
                'low_stock_threshold' => round((float) $p->low_stock_threshold, 2),
                'unit' => $p->unit ?? 'item',
                'is_out_of_stock' => (float) $p->stock_quantity <= 0,
            ]);

        $lowIngredients = InventoryItem::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'low_stock_threshold')
            ->orderBy('current_stock')
            ->take($limit)
            ->get(['id', 'name_en', 'name_km', 'current_stock', 'low_stock_threshold', 'unit'])
            ->map(static fn (InventoryItem $item): array => [
                'source' => 'ingredient',
                'id' => $item->id,
                'name_en' => $item->name_en,
                'name_km' => $item->name_km,
                'current_stock' => round((float) $item->current_stock, 3),
                'low_stock_threshold' => round((float) $item->low_stock_threshold, 3),
                'unit' => $item->unit ?? 'unit',
                'is_out_of_stock' => (float) $item->current_stock <= 0,
            ]);

        $combined = $lowProducts->concat($lowIngredients)
            ->sortBy('current_stock')
            ->take($limit)
            ->values()
            ->all();

        return [
            'low_stock_count' => count($combined),
            'items' => $combined,
            'has_alerts' => $combined !== [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getExpenseAndProfitSummary(Business $business, int $days = 30): array
    {
        $days = max(1, min(90, $days));
        $since = Carbon::now()->subDays($days - 1)->startOfDay();

        $paidRevenue = round((float) Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('created_at', '>=', $since)
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->sum('total_amount'), 2);

        $expenses = Expense::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('date', '>=', $since->toDateString())
            ->with('category:id,name')
            ->get();

        $totalExpenses = round((float) $expenses->sum('amount'), 2);
        $byCategory = $expenses->groupBy(static fn (Expense $e): string => $e->category?->name ?? 'Uncategorized')
            ->map(static fn ($group, string $cat): array => [
                'category' => $cat,
                'amount_usd' => round((float) $group->sum('amount'), 2),
                'entries' => $group->count(),
            ])
            ->sortByDesc('amount_usd')
            ->values()
            ->all();

        $cogsItems = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.business_id', $business->id)
            ->where('orders.created_at', '>=', $since)
            ->where('orders.payment_status', 'paid')
            ->whereNotIn('orders.status', ['cancelled', 'rejected'])
            ->whereNotNull('products.cost_price')
            ->where('products.cost_price', '>', 0)
            ->select([
                DB::raw('SUM(order_items.quantity * products.cost_price) as estimated_cogs'),
            ])
            ->value('estimated_cogs');

        $estimatedCogs = round((float) ($cogsItems ?? 0.0), 2);
        $netCashAfterExpenses = round($paidRevenue - $totalExpenses - $estimatedCogs, 2);

        return [
            'period_days' => $days,
            'paid_revenue_usd' => $paidRevenue,
            'recorded_expenses_usd' => $totalExpenses,
            'estimated_product_cogs_usd' => $estimatedCogs,
            'net_operating_estimate_usd' => $netCashAfterExpenses,
            'expense_categories' => $byCategory,
            'note' => 'COGS includes only products with an entered cost_price; operating expenses come from recorded Expense entries.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPeakHours(Business $business, int $days = 30): array
    {
        $days = max(1, min(90, $days));
        $tz = config('app.timezone', 'Asia/Phnom_Penh');
        $since = Carbon::now($tz)->subDays($days - 1)->startOfDay();

        $orders = Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('created_at', '>=', $since)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->get(['created_at', 'total_amount']);

        if ($orders->isEmpty()) {
            return [
                'period_days' => $days,
                'has_data' => false,
                'peak_hours' => [],
                'by_weekday' => [],
            ];
        }

        $hourly = [];
        $weekdays = [];

        foreach ($orders as $order) {
            $local = $order->created_at->copy()->timezone($tz);
            $hour = (int) $local->format('G');
            $dayName = $local->format('l');

            $hourly[$hour] ??= ['hour_24' => $hour, 'label' => sprintf('%02d:00–%02d:59', $hour, $hour), 'orders' => 0, 'revenue_usd' => 0.0];
            $hourly[$hour]['orders']++;
            $hourly[$hour]['revenue_usd'] = round($hourly[$hour]['revenue_usd'] + (float) $order->total_amount, 2);

            $weekdays[$dayName] ??= ['day' => $dayName, 'orders' => 0, 'revenue_usd' => 0.0];
            $weekdays[$dayName]['orders']++;
            $weekdays[$dayName]['revenue_usd'] = round($weekdays[$dayName]['revenue_usd'] + (float) $order->total_amount, 2);
        }

        usort($hourly, static fn (array $a, array $b): int => $b['orders'] <=> $a['orders']);
        usort($weekdays, static fn (array $a, array $b): int => $b['orders'] <=> $a['orders']);

        return [
            'period_days' => $days,
            'timezone' => $tz,
            'has_data' => true,
            'total_orders_analyzed' => $orders->count(),
            'peak_hours' => array_slice($hourly, 0, 5),
            'by_weekday' => $weekdays,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaymentMethodBreakdown(Business $business, int $days = 30): array
    {
        $days = max(1, min(90, $days));
        $since = Carbon::now()->subDays($days - 1)->startOfDay();

        $orders = Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('created_at', '>=', $since)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->get(['payment_method', 'payment_status', 'total_amount']);

        $totalOrders = $orders->count();
        if ($totalOrders === 0) {
            return [
                'period_days' => $days,
                'has_data' => false,
                'methods' => [],
                'statuses' => [],
            ];
        }

        $methods = $orders->groupBy(static fn (Order $o): string => $o->payment_method ?: 'unspecified')
            ->map(static function ($group, string $method) use ($totalOrders): array {
                $count = $group->count();

                return [
                    'payment_method' => $method,
                    'orders_count' => $count,
                    'share_pct' => round(($count / $totalOrders) * 100, 1),
                    'total_usd' => round((float) $group->sum('total_amount'), 2),
                ];
            })
            ->sortByDesc('orders_count')
            ->values()
            ->all();

        $statuses = $orders->groupBy(static fn (Order $o): string => $o->payment_status ?: 'unknown')
            ->map(static fn ($group, string $status): array => [
                'payment_status' => $status,
                'orders_count' => $group->count(),
                'total_usd' => round((float) $group->sum('total_amount'), 2),
            ])
            ->values()
            ->all();

        return [
            'period_days' => $days,
            'has_data' => true,
            'total_orders' => $totalOrders,
            'methods' => $methods,
            'statuses' => $statuses,
        ];
    }
}
