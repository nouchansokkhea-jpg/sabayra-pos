<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display comprehensive business financial analytics & sales reports.
     */
    public function index(Request $request): View
    {
        $business = TenantContext::get();

        $period = $request->input('period', 'month'); // today, week, month, year
        $startDate = match ($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };
        $endDate = Carbon::now()->endOfDay();

        $ordersQuery = Order::where('business_id', $business->id)
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Key KPI metrics
        $totalOrders = (clone $ordersQuery)->count();
        $completedOrders = (clone $ordersQuery)->where('status', 'completed')->count();
        $totalRevenue = (float) (clone $ordersQuery)->where('payment_status', 'paid')->sum('total_amount');
        $totalRevenueKhr = (float) (clone $ordersQuery)->where('payment_status', 'paid')->sum('total_khr');
        $avgOrderValue = $completedOrders > 0 ? $totalRevenue / $completedOrders : 0.00;

        // Payment Method breakdown
        $paymentBreakdown = (clone $ordersQuery)
            ->where('payment_status', 'paid')
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
        $periodQrScans = (int) \App\Models\QrScanEvent::where('business_id', $business->id)
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

            $dayViews = \App\Models\QrScanEvent::where('business_id', $business->id)
                ->whereDate('scanned_at', $date)
                ->count();
            $viewsChartData[] = $dayViews;
        }

        return view('business.reports.index', compact(
            'business',
            'period',
            'totalOrders',
            'completedOrders',
            'totalRevenue',
            'totalRevenueKhr',
            'avgOrderValue',
            'totalQrScans',
            'periodQrScans',
            'paymentBreakdown',
            'channelBreakdown',
            'topProducts',
            'chartLabels',
            'chartData',
            'viewsChartData'
        ));
    }

    /**
     * Export sales orders report to CSV format.
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

        return response()->stream(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel Khmer/English support
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Order Number',
                'Date',
                'Type',
                'Customer Name',
                'Customer Phone',
                'Status',
                'Payment Status',
                'Payment Method',
                'Subtotal (USD)',
                'Discount (USD)',
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
                    number_format($order->subtotal, 2, '.', ''),
                    number_format($order->discount_amount, 2, '.', ''),
                    number_format($order->tax_amount, 2, '.', ''),
                    number_format($order->total_amount, 2, '.', ''),
                    number_format($order->total_khr, 0, '.', ''),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
