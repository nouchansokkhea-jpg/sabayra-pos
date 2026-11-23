<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the comprehensive merchant business overview dashboard.
     */
    public function index(?Request $request = null): View
    {
        $business = TenantContext::get();
        $user = Auth::user();

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // 1. Core Counts & Catalog Stats
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_available', true)->count();
        $featuredProducts = Product::where('is_featured', true)->count();
        $totalCategories = Category::count();
        $totalCustomers = Customer::count();

        // Menu views from QR scans
        $totalMenuViews = (int) $business->qrCodes()->sum('scan_count');
        $todayMenuViews = (int) DB::table('qr_scan_events')
            ->join('qr_codes', 'qr_scan_events.qr_code_id', '=', 'qr_codes.id')
            ->where('qr_codes.business_id', $business->id)
            ->whereDate('qr_scan_events.scanned_at', $today)
            ->count();

        // 2. Revenue (Today vs Yesterday)
        $todayRevenueUsd = (float) Order::where('payment_status', 'paid')
            ->whereDate('created_at', $today)
            ->sum('total_amount');

        $yesterdayRevenueUsd = (float) Order::where('payment_status', 'paid')
            ->whereDate('created_at', $yesterday)
            ->sum('total_amount');

        $revenueChangePercent = $yesterdayRevenueUsd > 0
            ? round((($todayRevenueUsd - $yesterdayRevenueUsd) / $yesterdayRevenueUsd) * 100, 1)
            : ($todayRevenueUsd > 0 ? 100.0 : 0.0);

        // 3. Orders (Today vs Yesterday)
        $todayOrders = Order::whereDate('created_at', $today)->count();
        $yesterdayOrders = Order::whereDate('created_at', $yesterday)->count();

        $ordersChangePercent = $yesterdayOrders > 0
            ? round((($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100, 1)
            : ($todayOrders > 0 ? 100.0 : 0.0);

        $pendingOrders = Order::where('status', 'pending')->count();
        $preparingOrders = Order::whereIn('status', ['confirmed', 'preparing'])->count();

        // 4. Weekly Metrics
        $weeklyRevenue = (float) Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->sum('total_amount');

        $weeklyOrdersCount = Order::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();

        // 5. Customer Insights
        $newCustomersThisWeek = Customer::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
        $paidOrdersCount = Order::where('payment_status', 'paid')->count();
        $totalRevenueAllTime = (float) Order::where('payment_status', 'paid')->sum('total_amount');
        $averageOrderValue = $paidOrdersCount > 0 ? round($totalRevenueAllTime / $paidOrdersCount, 2) : 0.00;

        // 6. Recent Products
        $recentProducts = Product::with('category')->latest()->take(5)->get();

        // 7. Top Selling Products
        $bestSellers = OrderItem::select('product_name', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(subtotal) as total_earned'))
            ->groupBy('product_name')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        // 8. Low Stock Alerts
        $lowStockProducts = Product::where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->take(5)
            ->get();

        $lowStockIngredients = InventoryItem::whereColumn('current_stock', '<=', 'low_stock_threshold')
            ->take(5)
            ->get();

        // 9. Last 7 Days Sales Trend (for Chart.js)
        $dates = collect();
        $salesData = collect();

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dates->push($day->format('M d'));

            $daySum = (float) Order::where('payment_status', 'paid')
                ->whereDate('created_at', $day)
                ->sum('total_amount');

            $salesData->push($daySum);
        }

        $chartDates = $dates->toArray();
        $chartSales = $salesData->toArray();

        // 10. Security Overview
        $securityOverview = [
            'two_factor_enabled' => (bool) ($user->two_factor_enabled ?? false),
            'last_login' => $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Active now',
            'failed_attempts' => (int) ($user->failed_login_attempts ?? 0),
        ];

        // Public Storefront URL
        $publicMenuUrl = url('/' . $business->slug);

        return view('business.dashboard', compact(
            'business',
            'publicMenuUrl',
            'totalProducts',
            'activeProducts',
            'featuredProducts',
            'totalCategories',
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
            'chartDates',
            'chartSales',
            'securityOverview'
        ));
    }
}
