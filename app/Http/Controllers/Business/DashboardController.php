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
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the merchant business overview dashboard.
     */
    public function index(): View
    {
        $business = TenantContext::get();

        // 1. Core Counts
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $totalOrders = Order::count();
        $totalCustomers = Customer::count();
        $totalMenuViews = (int) $business->qrCodes()->sum('scan_count');

        // 2. Revenue (USD & KHR)
        $totalRevenueUsd = (float) Order::where('payment_status', 'paid')->sum('total_amount');
        $totalRevenueKhr = (float) Order::where('payment_status', 'paid')->sum('total_khr');

        $todayRevenueUsd = (float) Order::where('payment_status', 'paid')
            ->whereDate('created_at', Carbon::today())
            ->sum('total_amount');

        $todayRevenueKhr = (float) Order::where('payment_status', 'paid')
            ->whereDate('created_at', Carbon::today())
            ->sum('total_khr');

        // 3. Order Status Breakdown
        $pendingOrders = Order::where('status', 'pending')->count();
        $preparingOrders = Order::whereIn('status', ['confirmed', 'preparing'])->count();

        // 4. Low Stock Alerts
        $lowStockProducts = Product::where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->take(5)
            ->get();

        $lowStockIngredients = InventoryItem::whereColumn('current_stock', '<=', 'low_stock_threshold')
            ->take(5)
            ->get();

        // 5. Best Selling Products
        $bestSellers = OrderItem::select('product_name', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(subtotal) as total_earned'))
            ->groupBy('product_name')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        // 6. Recent Orders
        $recentOrders = Order::with('table')->latest()->take(6)->get();

        // 7. Last 7 Days Sales Trend (for Chart.js)
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

        return view('business.dashboard', compact(
            'business',
            'totalProducts',
            'totalCategories',
            'totalMenuViews',
            'totalOrders',
            'totalCustomers',
            'totalRevenueUsd',
            'totalRevenueKhr',
            'todayRevenueUsd',
            'todayRevenueKhr',
            'pendingOrders',
            'preparingOrders',
            'lowStockProducts',
            'lowStockIngredients',
            'bestSellers',
            'recentOrders',
            'chartDates',
            'chartSales'
        ));
    }
}
