<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reservation;
use App\Services\Business\CategoryExperienceService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly CategoryExperienceService $categoryExperience
    ) {
    }

    /**
     * Display deep commercial analytics, peak hours, and category-specific intelligence.
     */
    public function index(): View
    {
        $business = TenantContext::get();

        // 1. Hourly Peak Distribution (computed safely across SQLite & PostgreSQL)
        $hourlyDistribution = Order::where('business_id', $business->id)
            ->get(['created_at'])
            ->groupBy(fn (Order $o): int => (int) $o->created_at?->format('G'))
            ->map(fn ($grp): int => $grp->count())
            ->toArray();

        $peakHours = [];
        for ($h = 6; $h <= 23; $h++) {
            $label = ($h < 12 ? $h . ' AM' : ($h === 12 ? '12 PM' : ($h - 12) . ' PM'));
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

        // 3. Order Types (Dine-in, Takeaway, Delivery, Service)
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

        // 5. Category-Specific Commercial Intelligence
        $categoryMeta = $this->categoryExperience->meta($business);
        $reportConfig = $this->categoryExperience->reportConfig($business);
        $sectorInsights = $this->buildSectorInsights(
            $business,
            (string) ($reportConfig['primary_focus'] ?? 'general'),
            $orderTypes
        );

        return view('business.analytics.index', compact(
            'business',
            'peakHours',
            'categoryBreakdown',
            'orderTypes',
            'paymentMethods',
            'categoryMeta',
            'reportConfig',
            'sectorInsights'
        ));
    }

    /**
     * Compute real database sector insights matching the business category focus.
     *
     * @param  array<string, int>  $orderTypes
     * @return array<string, mixed>
     */
    private function buildSectorInsights(Business $business, string $focus, array $orderTypes = []): array
    {
        if (in_array($focus, ['retail', 'beauty', 'bakery'], true)) {
            $tracked = Product::where('business_id', $business->id)->where('track_inventory', true)->get();
            $costValuation = round((float) $tracked->sum(fn (Product $p): float => max(0, (float) $p->stock_quantity) * (float) ($p->cost_price ?? 0)), 2);
            $retailValuation = round((float) $tracked->sum(fn (Product $p): float => max(0, (float) $p->stock_quantity) * (float) ($p->base_price ?? 0)), 2);
            $expiring30 = Product::where('business_id', $business->id)
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', now()->addDays(30))
                ->count();

            $topBrands = DB::table('order_items as oi')
                ->join('orders as o', 'oi.order_id', '=', 'o.id')
                ->join('products as p', 'oi.product_id', '=', 'p.id')
                ->where('o.business_id', $business->id)
                ->where('o.payment_status', 'paid')
                ->whereNotNull('p.brand')
                ->select('p.brand', DB::raw('SUM(oi.subtotal) as revenue'))
                ->groupBy('p.brand')
                ->orderByDesc('revenue')
                ->take(5)
                ->get();

            return [
                'focus' => $focus,
                'cards' => [
                    ['label' => 'Stock Cost Valuation', 'value' => '$' . number_format($costValuation, 2)],
                    ['label' => 'Stock Retail Potential', 'value' => '$' . number_format($retailValuation, 2)],
                    ['label' => 'Expiring Within 30d', 'value' => (string) $expiring30],
                ],
                'rows' => $topBrands->map(fn ($b): array => [
                    'label' => $b->brand,
                    'value' => '$' . number_format((float) $b->revenue, 2),
                ])->all(),
                'rows_title' => 'Top Selling Brands',
            ];
        }

        if ($focus === 'fashion') {
            $totalVariantStock = (int) round((float) ProductVariant::whereHas('product', fn ($q) => $q->where('business_id', $business->id))->sum('stock_quantity'));
            $sizeCounts = OrderItem::query()
                ->join('orders as o', 'order_items.order_id', '=', 'o.id')
                ->join('product_variants as pv', 'order_items.variant_id', '=', 'pv.id')
                ->where('o.business_id', $business->id)
                ->where('o.payment_status', 'paid')
                ->whereNotNull('pv.size')
                ->select('pv.size', DB::raw('SUM(order_items.quantity) as qty'))
                ->groupBy('pv.size')
                ->orderByDesc('qty')
                ->take(5)
                ->get();

            return [
                'focus' => $focus,
                'cards' => [
                    ['label' => 'Total Variant Stock Units', 'value' => number_format($totalVariantStock)],
                    ['label' => 'Distinct Sold Sizes', 'value' => (string) $sizeCounts->count()],
                ],
                'rows' => $sizeCounts->map(fn ($s): array => [
                    'label' => 'Size ' . $s->size,
                    'value' => ((int) $s->qty) . ' sold',
                ])->all(),
                'rows_title' => 'Top Purchased Sizes',
            ];
        }

        if ($focus === 'services') {
            $totalBookings = Reservation::where('business_id', $business->id)->where('booking_type', 'service')->count();
            $completedBookings = Reservation::where('business_id', $business->id)->where('booking_type', 'service')->where('status', 'completed')->count();
            $completionRate = $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100, 1) : 100.0;

            $topServices = Reservation::query()
                ->join('products as p', 'reservations.service_product_id', '=', 'p.id')
                ->where('reservations.business_id', $business->id)
                ->where('reservations.booking_type', 'service')
                ->select('p.name_en', DB::raw('COUNT(*) as bookings_count'))
                ->groupBy('p.name_en')
                ->orderByDesc('bookings_count')
                ->take(5)
                ->get();

            return [
                'focus' => $focus,
                'cards' => [
                    ['label' => 'Total Appointments', 'value' => (string) $totalBookings],
                    ['label' => 'Completed Visits', 'value' => (string) $completedBookings],
                    ['label' => 'Booking Completion Rate', 'value' => $completionRate . '%'],
                ],
                'rows' => $topServices->map(fn ($s): array => [
                    'label' => $s->name_en,
                    'value' => $s->bookings_count . ' bookings',
                ])->all(),
                'rows_title' => 'Most Requested Services',
            ];
        }

        return [
            'focus' => $focus,
            'cards' => [
                ['label' => 'Dine-In Orders', 'value' => (string) ($orderTypes['dine_in'] ?? 0)],
                ['label' => 'Takeaway Orders', 'value' => (string) ($orderTypes['takeaway'] ?? 0)],
                ['label' => 'Delivery Orders', 'value' => (string) ($orderTypes['delivery'] ?? 0)],
            ],
            'rows' => [],
            'rows_title' => '',
        ];
    }
}
