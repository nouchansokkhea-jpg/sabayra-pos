<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QrScanEvent;
use App\Models\Reservation;
use App\Services\Business\CategoryExperienceService;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly CategoryExperienceService $categoryExperience
    ) {
    }
    /**
     * Display comprehensive business financial analytics, tax & service charge summaries, and sales reports.
     */
    public function index(Request $request): View
    {
        $business = TenantContext::get();

        $period = $request->input('period', 'month');
        $startDate = match ($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };
        $endDate = Carbon::now()->endOfDay();

        $ordersQuery = Order::where('business_id', $business->id)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $paidOrdersQuery = (clone $ordersQuery)->where('payment_status', 'paid');

        // Core financial metrics
        $totalOrders = (clone $ordersQuery)->count();
        $completedOrders = (clone $ordersQuery)->where('status', 'completed')->count();
        $paidOrdersCount = (clone $paidOrdersQuery)->count();

        $totalRevenue = round((float) (clone $paidOrdersQuery)->sum('total_amount'), 2);
        $totalRevenueKhr = round((float) (clone $paidOrdersQuery)->sum('total_khr'), 0);
        $totalGrossSales = round((float) (clone $paidOrdersQuery)->sum('subtotal'), 2);
        $totalDiscounts = round((float) (clone $paidOrdersQuery)->sum('discount_amount'), 2);
        $totalTaxCollected = round((float) (clone $paidOrdersQuery)->sum('tax_amount'), 2);
        $totalServiceCharges = round((float) (clone $paidOrdersQuery)->sum('service_charge_amount'), 2);
        $netRevenue = round(max(0.0, $totalRevenue - $totalTaxCollected - $totalServiceCharges), 2);
        $avgOrderValue = $paidOrdersCount > 0 ? round($totalRevenue / $paidOrdersCount, 2) : 0.00;

        // Payment Status Breakdown (Paid vs Pending Verification vs Pending Collection vs Unpaid/Rejected)
        $paymentStatusBreakdown = (clone $ordersQuery)
            ->select('payment_status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_status')
            ->get()
            ->keyBy('payment_status');

        $pendingVerificationCount = (int) ($paymentStatusBreakdown['pending_verification']->count ?? 0);
        $pendingVerificationAmount = round((float) ($paymentStatusBreakdown['pending_verification']->total ?? 0), 2);
        $pendingCollectionCount = (int) ($paymentStatusBreakdown['pending_collection']->count ?? 0);
        $pendingCollectionAmount = round((float) ($paymentStatusBreakdown['pending_collection']->total ?? 0), 2);
        $unpaidAmount = round((float) ($paymentStatusBreakdown['unpaid']->total ?? 0), 2);
        $unpaidCount = (int) ($paymentStatusBreakdown['unpaid']->count ?? 0);

        // Payment Method breakdown (paid orders)
        $paymentBreakdown = (clone $paidOrdersQuery)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // Channel breakdown (Dine-in, Takeaway, Delivery)
        $channelBreakdown = (clone $ordersQuery)
            ->select('order_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('order_type')
            ->get();

        // Top Selling Products
        $topProducts = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.business_id', $business->id)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.payment_status', 'paid')
            ->select(
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.subtotal) as total_sales')
            )
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        // QR Scans & Menu Views Metrics
        $totalQrScans = (int) $business->qrCodes()->sum('scan_count');
        $periodQrScans = (int) QrScanEvent::where('business_id', $business->id)
            ->whereBetween('scanned_at', [$startDate, $endDate])
            ->count();
        if ($periodQrScans === 0 && $totalQrScans > 0) {
            $periodQrScans = $totalQrScans;
        }

        // Daily Sales & Views Chart Data for the last 14 days
        $chartLabels = [];
        $chartData = [];
        $viewsChartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('M d');
            $daySales = Order::where('business_id', $business->id)
                ->where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('total_amount');
            $chartData[] = (float) $daySales;

            $dayViews = QrScanEvent::where('business_id', $business->id)
                ->whereDate('scanned_at', $date)
                ->count();
            $viewsChartData[] = $dayViews;
        }

        $categoryMeta = $this->categoryExperience->meta($business);
        $reportConfig = $this->categoryExperience->reportConfig($business);
        $terminology = $this->categoryExperience->terminology($business);
        $sectorReport = $this->buildSectorReport(
            $business,
            (string) ($reportConfig['primary_focus'] ?? 'sales_overview'),
            $startDate,
            $endDate
        );

        return view('business.reports.index', compact(
            'business',
            'period',
            'totalOrders',
            'completedOrders',
            'paidOrdersCount',
            'totalRevenue',
            'totalRevenueKhr',
            'totalGrossSales',
            'totalDiscounts',
            'totalTaxCollected',
            'totalServiceCharges',
            'netRevenue',
            'avgOrderValue',
            'paymentStatusBreakdown',
            'pendingVerificationCount',
            'pendingVerificationAmount',
            'pendingCollectionCount',
            'pendingCollectionAmount',
            'unpaidCount',
            'unpaidAmount',
            'totalQrScans',
            'periodQrScans',
            'paymentBreakdown',
            'channelBreakdown',
            'topProducts',
            'chartLabels',
            'chartData',
            'viewsChartData',
            'categoryMeta',
            'reportConfig',
            'terminology',
            'sectorReport'
        ));
    }

    /**
     * Compute category-specific financial & operational metrics for the active period.
     *
     * @return array<string, mixed>
     */
    private function buildSectorReport(Business $business, string $focus, Carbon $startDate, Carbon $endDate): array
    {
        if ($focus === 'services') {
            $bookings = Reservation::with(['serviceProduct', 'staffMember.user'])
                ->where('business_id', $business->id)
                ->where('booking_type', 'service')
                ->whereDate('reservation_date', '>=', $startDate->toDateString())
                ->whereDate('reservation_date', '<=', $endDate->toDateString())
                ->get();

            $completed = $bookings->where('status', 'completed')->count();
            $cancelled = $bookings->where('status', 'cancelled')->count();
            $total = $bookings->count();

            $staffRows = $bookings
                ->groupBy(fn (Reservation $r): string => $r->staffMember?->user?->name ?? 'Unassigned')
                ->map(fn ($grp, string $staffName): array => [
                    'name' => $staffName,
                    'count' => $grp->count(),
                    'completed' => $grp->where('status', 'completed')->count(),
                    'value' => '$' . number_format((float) $grp->where('status', 'completed')->sum('price_usd'), 2),
                ])
                ->values()
                ->all();

            return [
                'title' => 'Service Bookings & Staff Performance',
                'kpis' => [
                    ['label' => 'Period Bookings', 'value' => (string) $total],
                    ['label' => 'Completed Services', 'value' => (string) $completed],
                    ['label' => 'Cancelled Bookings', 'value' => (string) $cancelled],
                    ['label' => 'Completion Rate', 'value' => ($total > 0 ? round(($completed / $total) * 100) : 100) . '%'],
                ],
                'rows' => $staffRows,
            ];
        }

        if ($focus === 'fashion') {
            $variantBreakdown = DB::table('order_items as oi')
                ->join('orders as o', 'oi.order_id', '=', 'o.id')
                ->join('product_variants as pv', 'oi.variant_id', '=', 'pv.id')
                ->where('o.business_id', $business->id)
                ->whereBetween('o.created_at', [$startDate, $endDate])
                ->where('o.payment_status', 'paid')
                ->select(
                    DB::raw("COALESCE(pv.size, 'Standard') as size"),
                    DB::raw("COALESCE(pv.color, pv.shade, 'Standard') as color"),
                    DB::raw('SUM(oi.quantity) as qty'),
                    DB::raw('SUM(oi.subtotal) as sales')
                )
                ->groupBy('pv.size', 'pv.color', 'pv.shade')
                ->orderByDesc('qty')
                ->take(8)
                ->get();

            return [
                'title' => 'Fashion Sales by Size & Color',
                'kpis' => [
                    ['label' => 'Variant Lines Sold', 'value' => (string) $variantBreakdown->count()],
                    ['label' => 'Remaining Variant Stock', 'value' => (string) ((int) ProductVariant::whereHas('product', fn ($q) => $q->where('business_id', $business->id))->sum('stock_quantity'))],
                ],
                'rows' => $variantBreakdown->map(fn ($r): array => [
                    'name' => trim($r->size . ' / ' . $r->color),
                    'count' => (int) $r->qty,
                    'value' => '$' . number_format((float) $r->sales, 2),
                ])->all(),
            ];
        }

        if (in_array($focus, ['retail', 'beauty', 'bakery'], true)) {
            $tracked = Product::where('business_id', $business->id)->where('track_inventory', true)->get();
            $costValue = round((float) $tracked->sum(fn (Product $p): float => max(0, (float) $p->stock_quantity) * (float) ($p->cost_price ?? 0)), 2);
            $retailValue = round((float) $tracked->sum(fn (Product $p): float => max(0, (float) $p->stock_quantity) * (float) ($p->base_price ?? 0)), 2);
            $expiringSoon = Product::where('business_id', $business->id)
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', now()->addDays(30))
                ->orderBy('expiry_date')
                ->take(6)
                ->get();

            return [
                'title' => 'Stock Valuation & Expiry Report',
                'kpis' => [
                    ['label' => 'Stock Cost Value', 'value' => '$' . number_format($costValue, 2)],
                    ['label' => 'Stock Retail Value', 'value' => '$' . number_format($retailValue, 2)],
                    ['label' => 'Items Expiring (30d)', 'value' => (string) $expiringSoon->count()],
                ],
                'rows' => $expiringSoon->map(fn (Product $p): array => [
                    'name' => $p->name_en . ($p->batch_number ? " ({$p->batch_number})" : ''),
                    'count' => (int) $p->stock_quantity,
                    'value' => $p->expiry_date?->format('Y-m-d') ?? 'N/A',
                ])->all(),
            ];
        }

        return [
            'title' => 'Hospitality & Channel Summary',
            'kpis' => [],
            'rows' => [],
        ];
    }

    /**
     * Export sales orders report to CSV format with tax, service charge, and payment breakdown.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $business = TenantContext::get();
        $fileName = "sales_report_{$business->slug}_" . now()->format('Ymd_His') . ".csv";

        $orders = Order::where('business_id', $business->id)
            ->with(['customer', 'table'])
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($orders): void {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel Khmer/English support
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Order Number',
                'Date',
                'Type',
                'Customer Name',
                'Customer Phone',
                'Status',
                'Payment Status',
                'Payment Method',
                'Payment Reference',
                'Subtotal (USD)',
                'Discount (USD)',
                'Service Charge (USD)',
                'Tax Name',
                'Tax Rate (%)',
                'Tax Mode',
                'Tax (USD)',
                'Total (USD)',
                'Total (KHR)',
            ]);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_number,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->order_type,
                    $order->customer_name,
                    $order->customer_phone,
                    $order->status,
                    $order->payment_status,
                    $order->payment_method,
                    $order->payment_reference,
                    number_format((float) $order->subtotal, 2, '.', ''),
                    number_format((float) $order->discount_amount, 2, '.', ''),
                    number_format((float) ($order->service_charge_amount ?? 0), 2, '.', ''),
                    $order->tax_name ?? 'VAT',
                    number_format((float) ($order->tax_rate ?? 0), 2, '.', ''),
                    ($order->tax_inclusive ?? false) ? 'Inclusive' : 'Exclusive',
                    number_format((float) $order->tax_amount, 2, '.', ''),
                    number_format((float) $order->total_amount, 2, '.', ''),
                    number_format((float) $order->total_khr, 0, '.', ''),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
