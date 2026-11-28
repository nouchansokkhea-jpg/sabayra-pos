<?php

namespace App\Services\Business;

use App\Models\Business;
use App\Models\BusinessAiInsight;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\AI\AiAccessService;
use App\Services\AI\AIUsageService;
use App\Services\Qr\QrCodeService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardExperienceService
{
    public function __construct(
        private readonly CategoryExperienceService $categoryExperience
    ) {
    }

    /**
     * Build category-aware, tenant-isolated KPIs, charts, quick actions, and dashboard panels.
     *
     * @return array<string, mixed>
     */
    public function buildForBusiness(Business $business, User $user): array
    {
        $categoryConfig = $this->categoryExperience->configFor($business);
        $categoryMeta = $this->categoryExperience->meta($business);
        $dashboardCfg = $this->categoryExperience->dashboardConfig($business);
        $storefrontCfg = $this->categoryExperience->storefrontConfig($business);
        $terminology = $this->categoryExperience->terminology($business);
        $hiddenWidgets = $this->categoryExperience->hiddenWidgets($business);

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // Core Catalog Counts
        $totalProducts = Product::where('business_id', $business->id)->count();
        $activeProducts = Product::where('business_id', $business->id)->where('is_available', true)->count();
        $featuredProducts = Product::where('business_id', $business->id)->where('is_featured', true)->count();
        $totalCategories = Category::where('business_id', $business->id)->count();
        $totalCustomers = Customer::where('business_id', $business->id)->count();

        // QR Scan Metrics
        $totalMenuViews = (int) $business->qrCodes()->sum('scan_count');
        $todayMenuViews = (int) DB::table('qr_scan_events')
            ->join('qr_codes', 'qr_scan_events.qr_code_id', '=', 'qr_codes.id')
            ->where('qr_codes.business_id', $business->id)
            ->whereDate('qr_scan_events.scanned_at', $today)
            ->count();

        // Revenue Metrics (USD + Comparison)
        $todayRevenueUsd = (float) Order::where('business_id', $business->id)
            ->where('payment_status', 'paid')
            ->whereDate('created_at', $today)
            ->sum('total_amount');

        $yesterdayRevenueUsd = (float) Order::where('business_id', $business->id)
            ->where('payment_status', 'paid')
            ->whereDate('created_at', $yesterday)
            ->sum('total_amount');

        $revenueChangePercent = $yesterdayRevenueUsd > 0
            ? round((($todayRevenueUsd - $yesterdayRevenueUsd) / $yesterdayRevenueUsd) * 100, 1)
            : ($todayRevenueUsd > 0 ? 100.0 : 0.0);

        // Order Flow Metrics
        $todayOrders = Order::where('business_id', $business->id)->whereDate('created_at', $today)->count();
        $yesterdayOrders = Order::where('business_id', $business->id)->whereDate('created_at', $yesterday)->count();
        $ordersChangePercent = $yesterdayOrders > 0
            ? round((($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100, 1)
            : ($todayOrders > 0 ? 100.0 : 0.0);

        $pendingOrders = Order::where('business_id', $business->id)->where('status', 'pending')->count();
        $preparingOrders = Order::where('business_id', $business->id)->whereIn('status', ['confirmed', 'preparing'])->count();

        $weeklyRevenue = (float) Order::where('business_id', $business->id)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->sum('total_amount');

        $weeklyOrdersCount = Order::where('business_id', $business->id)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->count();

        $newCustomersThisWeek = Customer::where('business_id', $business->id)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->count();

        $paidOrdersCount = Order::where('business_id', $business->id)->where('payment_status', 'paid')->count();
        $totalRevenueAllTime = (float) Order::where('business_id', $business->id)->where('payment_status', 'paid')->sum('total_amount');
        $averageOrderValue = $paidOrdersCount > 0 ? round($totalRevenueAllTime / $paidOrdersCount, 2) : 0.00;

        // Recent & Best Sellers
        $recentProducts = Product::where('business_id', $business->id)
            ->with('category')
            ->latest()
            ->take(5)
            ->get();

        $bestSellers = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.business_id', $business->id)
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.subtotal) as total_earned')
            )
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        // Low Stock Lists
        $lowStockProducts = Product::where('business_id', $business->id)
            ->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->take(6)
            ->get();

        $lowStockProductsCount = Product::where('business_id', $business->id)
            ->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        $lowStockIngredients = InventoryItem::where('business_id', $business->id)
            ->whereColumn('current_stock', '<=', 'low_stock_threshold')
            ->orderBy('current_stock')
            ->take(6)
            ->get();

        // Industry-Specific Metrics
        $activeTables = RestaurantTable::where('business_id', $business->id)->where('is_active', true)->count();
        $totalTables = RestaurantTable::where('business_id', $business->id)->count();

        $readyTodayOrders = Order::where('business_id', $business->id)
            ->whereIn('status', ['ready', 'completed'])
            ->whereDate('created_at', $today)
            ->get(['created_at', 'updated_at']);
        $avgPrepMinutes = $readyTodayOrders->isNotEmpty()
            ? (int) round($readyTodayOrders->avg(fn (Order $o): int => max(1, (int) $o->created_at->diffInMinutes($o->updated_at))))
            : 0;

        $productsSoldToday = (float) OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.business_id', $business->id)
            ->where('orders.status', '!=', 'cancelled')
            ->whereDate('orders.created_at', $today)
            ->sum('order_items.quantity');

        $preOrdersCount = Order::where('business_id', $business->id)
            ->whereNotNull('scheduled_for')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        $expiringSoonProducts = Product::where('business_id', $business->id)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $today->copy()->addDays(30))
            ->orderBy('expiry_date')
            ->take(6)
            ->get();

        $expiringProductsCount = Product::where('business_id', $business->id)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $today->copy()->addDays(14))
            ->count();

        $productStockValuation = (float) Product::where('business_id', $business->id)
            ->where('track_inventory', true)
            ->where('stock_quantity', '>', 0)
            ->get(['stock_quantity', 'cost_price', 'base_price'])
            ->sum(fn (Product $p): float => (float) $p->stock_quantity * (float) ($p->cost_price ?? $p->base_price ?? 0));

        $ingredientValuation = (float) InventoryItem::where('business_id', $business->id)
            ->where('current_stock', '>', 0)
            ->get(['current_stock', 'unit_cost'])
            ->sum(fn (InventoryItem $item): float => (float) $item->current_stock * (float) ($item->unit_cost ?? 0));

        $inventoryValueUsd = round($productStockValuation + $ingredientValuation, 2);

        // Fashion & Variant Breakdown
        $variantRows = ProductVariant::query()
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('products.business_id', $business->id)
            ->select(
                'product_variants.size',
                'product_variants.color',
                'product_variants.shade',
                'product_variants.stock_quantity',
                'product_variants.name_en'
            )
            ->get();

        $variantStockTotal = (int) round((float) $variantRows->sum('stock_quantity'));

        $variantSales = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('product_variants', 'order_items.variant_id', '=', 'product_variants.id')
            ->where('orders.business_id', $business->id)
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'product_variants.size',
                'product_variants.color',
                'order_items.quantity',
                'order_items.options_json'
            )
            ->get();

        $sizeSales = [];
        $colorSales = [];
        $toppingCounts = [];

        foreach ($variantSales as $row) {
            $qty = (float) ($row->quantity ?? 0);
            $opts = is_array($row->options_json) ? $row->options_json : [];
            $size = trim((string) ($row->size ?? $opts['size'] ?? ''));
            $color = trim((string) ($row->color ?? $opts['color'] ?? ''));

            if ($size !== '') {
                $sizeSales[$size] = ($sizeSales[$size] ?? 0) + $qty;
            }
            if ($color !== '') {
                $colorSales[$color] = ($colorSales[$color] ?? 0) + $qty;
            }
            if (! empty($opts['toppings']) && is_array($opts['toppings'])) {
                foreach ($opts['toppings'] as $topping) {
                    $label = is_string($topping) ? trim($topping) : trim((string) ($topping['name'] ?? ''));
                    if ($label !== '') {
                        $toppingCounts[$label] = ($toppingCounts[$label] ?? 0) + max(1, (int) $qty);
                    }
                }
            }
        }

        arsort($sizeSales);
        arsort($colorSales);
        arsort($toppingCounts);

        $topSellingSize = ! empty($sizeSales) ? (string) array_key_first($sizeSales) : '—';
        $topSellingColor = ! empty($colorSales) ? (string) array_key_first($colorSales) : '—';

        $fashionVariantBreakdown = $variantRows
            ->groupBy(fn ($v): string => trim((string) ($v->size ?: 'One Size')) . ' / ' . trim((string) ($v->color ?: $v->shade ?: 'Standard')))
            ->map(function (Collection $group, string $label): array {
                return [
                    'label' => $label,
                    'variants_count' => $group->count(),
                    'stock_total' => (int) round((float) $group->sum('stock_quantity')),
                ];
            })
            ->sortByDesc('stock_total')
            ->take(6)
            ->values();

        // Top Drink Toppings / Modifiers
        $addonRows = DB::table('order_item_addons')
            ->join('order_items', 'order_item_addons.order_item_id', '=', 'order_items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.business_id', $business->id)
            ->select('order_item_addons.name', DB::raw('COUNT(*) as qty'))
            ->groupBy('order_item_addons.name')
            ->orderByDesc('qty')
            ->take(5)
            ->get();

        foreach ($addonRows as $addonRow) {
            $toppingCounts[$addonRow->name] = ($toppingCounts[$addonRow->name] ?? 0) + (int) $addonRow->qty;
        }
        arsort($toppingCounts);
        $topToppings = collect($toppingCounts)->take(6)->map(fn (int $count, string $name): array => [
            'name' => $name,
            'count' => $count,
        ])->values();

        // Service Bookings & Appointments Metrics
        $serviceBookingScope = $categoryMeta['key'] === 'services' || $this->categoryExperience->isModuleEnabled($business, 'service_bookings');
        $todayBookings = Reservation::where('business_id', $business->id)
            ->when($serviceBookingScope, fn ($q) => $q->where('booking_type', 'service'))
            ->whereDate('reservation_date', $today)
            ->count();

        $upcomingAppointmentsCount = Reservation::where('business_id', $business->id)
            ->when($serviceBookingScope, fn ($q) => $q->where('booking_type', 'service'))
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('reservation_date', '>=', $today)
            ->count();

        $completedServicesCount = Reservation::where('business_id', $business->id)
            ->when($serviceBookingScope, fn ($q) => $q->where('booking_type', 'service'))
            ->where('status', 'completed')
            ->count();

        $upcomingAppointments = Reservation::with(['serviceProduct', 'staffMember.user'])
            ->where('business_id', $business->id)
            ->when($serviceBookingScope, fn ($q) => $q->where('booking_type', 'service'))
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('reservation_date', '>=', $today)
            ->orderBy('reservation_date')
            ->orderBy('reservation_time')
            ->take(6)
            ->get();

        $staffMembers = BusinessMember::with('user')
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->get();

        $staffPerformance = $staffMembers->map(function (BusinessMember $member) use ($business): array {
            $bookingsCount = Reservation::where('business_id', $business->id)
                ->where('staff_member_id', $member->id)
                ->count();
            $completedBookings = Reservation::where('business_id', $business->id)
                ->where('staff_member_id', $member->id)
                ->where('status', 'completed')
                ->count();
            $assignedOrdersRevenue = (float) Order::where('business_id', $business->id)
                ->where('assigned_staff_id', $member->id)
                ->where('payment_status', 'paid')
                ->sum('total_amount');

            return [
                'name' => $member->user?->name ?? 'Staff',
                'role' => ucfirst($member->role),
                'bookings' => $bookingsCount,
                'completed' => $completedBookings,
                'revenue_usd' => round($assignedOrdersRevenue, 2),
            ];
        })->sortByDesc(fn (array $s): float => $s['revenue_usd'] + ($s['completed'] * 10))->take(5)->values();

        // 7-Day Sales Trend + Peak Hours (computed cleanly in PHP for cross-DB portability)
        $chartDates = [];
        $chartSales = [];
        $sevenDayPaidOrders = Order::where('business_id', $business->id)
            ->where('payment_status', 'paid')
            ->whereDate('created_at', '>=', $today->copy()->subDays(6))
            ->get(['created_at', 'total_amount']);

        for ($i = 6; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $dayKey = $day->format('Y-m-d');
            $chartDates[] = $day->format('M d');
            $chartSales[] = round((float) $sevenDayPaidOrders
                ->filter(fn (Order $o): bool => $o->created_at?->format('Y-m-d') === $dayKey)
                ->sum('total_amount'), 2);
        }

        $hourlyBuckets = [];
        foreach ($sevenDayPaidOrders as $order) {
            if ($order->created_at) {
                $hour = (int) $order->created_at->format('G');
                $hourlyBuckets[$hour] = ($hourlyBuckets[$hour] ?? 0) + 1;
            }
        }
        arsort($hourlyBuckets);
        $peakHoursCompact = collect($hourlyBuckets)->take(4)->map(fn (int $count, int $hour): array => [
            'label' => sprintf('%02d:00 - %02d:59', $hour, $hour),
            'orders' => $count,
        ])->values();

        // Security Overview
        $securityOverview = [
            'two_factor_enabled' => (bool) ($user->two_factor_enabled ?? false),
            'last_login' => $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Active now',
            'failed_attempts' => (int) ($user->failed_login_attempts ?? 0),
        ];

        $publicMenuUrl = QrCodeService::getMenuUrl($business);

        // AI Center Widget
        $canAccessAi = app(AiAccessService::class)->canAccess($user, $business, 'overview');
        $aiOverviewWidget = [
            'enabled' => $canAccessAi,
            'remaining_credits' => 0,
            'total_credits' => 0,
            'used_credits' => 0,
            'requests_count' => 0,
            'low_stock_alerts_count' => $lowStockProducts->count() + $lowStockIngredients->count(),
            'latest_insight' => null,
        ];

        if ($canAccessAi) {
            $aiSummary = app(AIUsageService::class)->getMonthlySummary($business);
            $aiOverviewWidget['remaining_credits'] = $aiSummary['remaining_credits'];
            $aiOverviewWidget['total_credits'] = $aiSummary['total_credits'];
            $aiOverviewWidget['used_credits'] = $aiSummary['used_credits'];
            $aiOverviewWidget['requests_count'] = $aiSummary['completed_requests'];
            $aiOverviewWidget['latest_insight'] = BusinessAiInsight::where('business_id', $business->id)
                ->where(function ($query): void {
                    $query->whereNull('status')->orWhere('status', '!=', 'dismissed');
                })
                ->latest('generated_at')
                ->first();
        }

        // Dynamic KPI Card Definitions
        $kpiCards = $this->resolveKpiCards($business, (array) ($dashboardCfg['kpis'] ?? []), [
            'todayRevenueUsd' => $todayRevenueUsd,
            'yesterdayRevenueUsd' => $yesterdayRevenueUsd,
            'revenueChangePercent' => $revenueChangePercent,
            'todayOrders' => $todayOrders,
            'yesterdayOrders' => $yesterdayOrders,
            'ordersChangePercent' => $ordersChangePercent,
            'averageOrderValue' => $averageOrderValue,
            'activeTables' => $activeTables,
            'totalTables' => $totalTables,
            'pendingOrders' => $pendingOrders,
            'preparingOrders' => $preparingOrders,
            'avgPrepMinutes' => $avgPrepMinutes,
            'productsSoldToday' => $productsSoldToday,
            'preOrdersCount' => $preOrdersCount,
            'expiringProductsCount' => $expiringProductsCount,
            'inventoryValueUsd' => $inventoryValueUsd,
            'lowStockProductsCount' => $lowStockProductsCount,
            'topSellingSize' => $topSellingSize,
            'topSellingColor' => $topSellingColor,
            'variantStockTotal' => $variantStockTotal,
            'todayBookings' => $todayBookings,
            'upcomingAppointmentsCount' => $upcomingAppointmentsCount,
            'completedServicesCount' => $completedServicesCount,
            'totalCustomers' => $totalCustomers,
        ]);

        // Dynamic Quick Action Buttons
        $quickActionButtons = $this->resolveQuickActions(
            $business,
            (array) ($dashboardCfg['quick_actions'] ?? []),
            $terminology
        );

        // Panel Catalog & Visibility
        $panelCatalog = [
            'revenue_chart' => 'Revenue Trend (7 Days)',
            'best_sellers' => 'Top Selling ' . ($terminology['catalog'] ?? 'Items'),
            'low_stock_ingredients' => 'Low Stock Ingredients & Supplies',
            'low_stock_products' => 'Low Stock Products & Variants',
            'recent_products' => 'Recent Catalog Items',
            'expiring_soon' => 'Expiring Batches & Items',
            'fashion_variant_breakdown' => 'Size & Color Stock Breakdown',
            'upcoming_appointments_panel' => 'Upcoming Service Appointments',
            'staff_performance_panel' => 'Team / Staff Performance',
            'peak_hours_compact' => 'Peak Rush Hours',
            'top_toppings' => 'Top Drink Toppings & Add-ons',
        ];

        $configuredPanels = (array) ($dashboardCfg['panels'] ?? ['revenue_chart', 'best_sellers', 'low_stock_products', 'recent_products']);
        $availablePanels = collect($configuredPanels)->mapWithKeys(fn (string $key): array => [
            $key => $panelCatalog[$key] ?? ucfirst(str_replace('_', ' ', $key)),
        ])->all();
        $activePanels = array_values(array_diff($configuredPanels, $hiddenWidgets));

        return compact(
            'business',
            'categoryConfig',
            'categoryMeta',
            'dashboardCfg',
            'storefrontCfg',
            'terminology',
            'kpiCards',
            'quickActionButtons',
            'activePanels',
            'availablePanels',
            'hiddenWidgets',
            'publicMenuUrl',
            'totalProducts',
            'activeProducts',
            'featuredProducts',
            'totalCategories',
            'totalCustomers',
            'totalMenuViews',
            'todayMenuViews',
            'todayRevenueUsd',
            'yesterdayRevenueUsd',
            'revenueChangePercent',
            'todayOrders',
            'yesterdayOrders',
            'ordersChangePercent',
            'pendingOrders',
            'preparingOrders',
            'weeklyRevenue',
            'weeklyOrdersCount',
            'newCustomersThisWeek',
            'averageOrderValue',
            'recentProducts',
            'bestSellers',
            'lowStockProducts',
            'lowStockIngredients',
            'expiringSoonProducts',
            'fashionVariantBreakdown',
            'upcomingAppointments',
            'staffPerformance',
            'peakHoursCompact',
            'topToppings',
            'inventoryValueUsd',
            'chartDates',
            'chartSales',
            'securityOverview',
            'aiOverviewWidget'
        );
    }

    /**
     * Resolve configured dashboard KPI cards from real database values.
     *
     * @param  array<int, string>  $kpiKeys
     * @param  array<string, mixed>  $m
     * @return array<int, array<string, mixed>>
     */
    private function resolveKpiCards(Business $business, array $kpiKeys, array $m): array
    {
        $catalog = [
            'today_revenue' => [
                'label' => "Today's Revenue",
                'value' => '$' . number_format((float) $m['todayRevenueUsd'], 2),
                'subtext' => 'Yesterday: $' . number_format((float) $m['yesterdayRevenueUsd'], 2),
                'badge' => ($m['revenueChangePercent'] >= 0 ? '↑ +' : '↓ ') . $m['revenueChangePercent'] . '%',
                'badge_tone' => $m['revenueChangePercent'] >= 0 ? 'emerald' : 'rose',
                'accent_right' => number_format((float) $m['todayRevenueUsd'] * (float) $business->exchange_rate_khr, 0) . ' ៛',
            ],
            'today_orders' => [
                'label' => "Today's Orders",
                'value' => (string) $m['todayOrders'],
                'subtext' => 'Yesterday: ' . $m['yesterdayOrders'],
                'badge' => ($m['ordersChangePercent'] >= 0 ? '↑ +' : '↓ ') . $m['ordersChangePercent'] . '%',
                'badge_tone' => $m['ordersChangePercent'] >= 0 ? 'emerald' : 'rose',
            ],
            'average_order_value' => [
                'label' => 'Avg Ticket Value',
                'value' => '$' . number_format((float) $m['averageOrderValue'], 2),
                'subtext' => 'Across paid checkouts',
                'tone' => 'emerald',
            ],
            'active_tables' => [
                'label' => 'Active Tables',
                'value' => (string) $m['activeTables'],
                'subtext' => $m['totalTables'] . ' total configured tables',
                'tone' => 'orange',
            ],
            'pending_orders' => [
                'label' => 'Pending Orders',
                'value' => (string) $m['pendingOrders'],
                'subtext' => 'Awaiting confirmation',
                'tone' => 'amber',
            ],
            'preparing_orders' => [
                'label' => 'Preparing Queue',
                'value' => (string) $m['preparingOrders'],
                'subtext' => 'Confirmed & in prep',
                'tone' => 'sky',
            ],
            'avg_prep_minutes' => [
                'label' => 'Avg Prep Time',
                'value' => $m['avgPrepMinutes'] > 0 ? $m['avgPrepMinutes'] . 'm' : '—',
                'subtext' => 'Today completed orders',
                'tone' => 'sky',
            ],
            'products_sold_today' => [
                'label' => 'Units Sold Today',
                'value' => (string) ((int) round((float) $m['productsSoldToday'])),
                'subtext' => 'Non-cancelled line items',
                'tone' => 'emerald',
            ],
            'pre_orders_count' => [
                'label' => 'Scheduled Pre-Orders',
                'value' => (string) $m['preOrdersCount'],
                'subtext' => 'Upcoming pickup dates',
                'tone' => 'amber',
            ],
            'expiring_products' => [
                'label' => 'Expiring in 14 Days',
                'value' => (string) $m['expiringProductsCount'],
                'subtext' => 'Items nearing expiry date',
                'tone' => $m['expiringProductsCount'] > 0 ? 'rose' : 'emerald',
            ],
            'inventory_value' => [
                'label' => 'Inventory Value',
                'value' => '$' . number_format((float) $m['inventoryValueUsd'], 2),
                'subtext' => 'Tracked stock valuation',
                'tone' => 'emerald',
            ],
            'low_stock_products' => [
                'label' => 'Low Stock Alerts',
                'value' => (string) $m['lowStockProductsCount'],
                'subtext' => 'At or below threshold',
                'tone' => $m['lowStockProductsCount'] > 0 ? 'rose' : 'emerald',
            ],
            'top_selling_size' => [
                'label' => 'Top Selling Size',
                'value' => (string) $m['topSellingSize'],
                'subtext' => 'Most purchased variant size',
                'tone' => 'orange',
            ],
            'top_selling_color' => [
                'label' => 'Top Selling Color',
                'value' => (string) $m['topSellingColor'],
                'subtext' => 'Most purchased color',
                'tone' => 'purple',
            ],
            'variant_stock_total' => [
                'label' => 'Variant Units in Stock',
                'value' => number_format((int) $m['variantStockTotal']),
                'subtext' => 'Total across sizes & colors',
                'tone' => 'sky',
            ],
            'today_bookings' => [
                'label' => "Today's Bookings",
                'value' => (string) $m['todayBookings'],
                'subtext' => 'Scheduled for today',
                'tone' => 'orange',
            ],
            'upcoming_appointments' => [
                'label' => 'Upcoming Appointments',
                'value' => (string) $m['upcomingAppointmentsCount'],
                'subtext' => 'Pending & confirmed',
                'tone' => 'sky',
            ],
            'completed_services' => [
                'label' => 'Completed Services',
                'value' => (string) $m['completedServicesCount'],
                'subtext' => 'Total completed bookings',
                'tone' => 'emerald',
            ],
            'total_customers' => [
                'label' => 'Total Customers',
                'value' => number_format((int) $m['totalCustomers']),
                'subtext' => 'Registered profiles',
                'tone' => 'purple',
            ],
        ];

        $cards = [];
        foreach ($kpiKeys as $key) {
            if (isset($catalog[$key])) {
                $cards[] = array_merge(['key' => $key], $catalog[$key]);
            }
        }

        return $cards;
    }

    /**
     * Resolve category-specific quick actions gated cleanly by module activation.
     *
     * @param  array<int, string>  $actionKeys
     * @param  array<string, string>  $terminology
     * @return array<int, array<string, string>>
     */
    private function resolveQuickActions(Business $business, array $actionKeys, array $terminology): array
    {
        $itemLabel = $terminology['item_singular'] ?? 'Product';
        $allActions = [
            'pos' => ['label' => 'Touch POS', 'route' => route('business.pos.index'), 'color' => 'emerald', 'module' => 'pos'],
            'kitchen' => ['label' => 'Kitchen (KDS)', 'route' => route('business.kitchen.index'), 'color' => 'purple', 'module' => 'kitchen'],
            'tables' => ['label' => 'Tables & QR', 'route' => route('business.tables.index'), 'color' => 'amber', 'module' => 'tables'],
            'reservations' => ['label' => 'Reservations', 'route' => route('business.reservations.index'), 'color' => 'sky', 'module' => 'reservations'],
            'bookings' => ['label' => 'Appointments', 'route' => route('business.reservations.index'), 'color' => 'sky', 'module' => 'service_bookings'],
            'live_orders' => ['label' => 'Live Orders', 'route' => route('business.orders.live'), 'color' => 'orange', 'module' => null],
            'add_product' => ['label' => 'Add ' . $itemLabel, 'route' => route('business.products.create'), 'color' => 'orange', 'module' => null],
            'bulk_import' => ['label' => 'Bulk Import', 'route' => route('business.products.import'), 'color' => 'blue', 'module' => 'bulk_import'],
            'inventory' => ['label' => 'Inventory', 'route' => route('business.inventory.index'), 'color' => 'blue', 'module' => 'inventory'],
            'recipes' => ['label' => 'Recipes BOM', 'route' => route('business.recipes.index'), 'color' => 'purple', 'module' => 'recipes'],
            'suppliers' => ['label' => 'Suppliers & PO', 'route' => route('business.suppliers.index'), 'color' => 'zinc', 'module' => 'suppliers'],
            'coupons' => ['label' => 'Promotions', 'route' => route('business.coupons.index'), 'color' => 'amber', 'module' => null],
            'happy_hours' => ['label' => 'Happy Hours', 'route' => route('business.happy-hours.index'), 'color' => 'amber', 'module' => 'happy_hours'],
            'loyalty' => ['label' => 'Loyalty', 'route' => route('business.loyalty.index'), 'color' => 'purple', 'module' => null],
            'qr_studio' => ['label' => 'QR Studio', 'route' => route('business.qr.index'), 'color' => 'amber', 'module' => null],
            'staff' => ['label' => 'Team & Staff', 'route' => route('business.staff.index'), 'color' => 'blue', 'module' => null],
            'customers' => ['label' => $terminology['customer'] ?? 'Customers', 'route' => route('business.customers.index'), 'color' => 'purple', 'module' => null],
            'settings' => ['label' => 'Settings', 'route' => route('business.settings.index'), 'color' => 'zinc', 'module' => null],
            'reports' => ['label' => 'Reports', 'route' => route('business.reports.index'), 'color' => 'zinc', 'module' => null],
        ];

        $resolved = [];
        foreach ($actionKeys as $key) {
            if (! isset($allActions[$key])) {
                continue;
            }
            $action = $allActions[$key];
            if ($action['module'] !== null && ! $this->categoryExperience->isModuleEnabled($business, $action['module'])) {
                continue;
            }
            $resolved[] = [
                'key' => $key,
                'label' => $action['label'],
                'route' => $action['route'],
                'color' => $action['color'],
            ];
        }

        return $resolved;
    }
}
