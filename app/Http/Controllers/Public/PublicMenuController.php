<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\QrCode;
use App\Models\QrScanEvent;
use App\Models\RestaurantTable;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicMenuController extends Controller
{
    /**
     * Display the public digital menu / product catalog for a business.
     */
    public function show(Request $request, string $business_slug): View
    {
        $business = Business::where('slug', $business_slug)
            ->where('status', 'active')
            ->firstOrFail();

        TenantContext::set($business);

        // Track menu view count
        try {
            $menuQr = QrCode::firstOrCreate(
                ['business_id' => $business->id, 'type' => 'menu'],
                [
                    'code' => "QR-MAIN-{$business->slug}",
                    'target_url' => url("/{$business->slug}"),
                    'scan_count' => 0,
                ]
            );
            $menuQr->increment('scan_count');
            $menuQr->update(['last_scanned_at' => now()]);
        } catch (\Throwable $e) {
            // Non-blocking
        }

        $categories = Category::where('is_active', true)
            ->with(['products' => function ($q) {
                $q->where('is_available', true)->with(['variants', 'addons']);
            }])
            ->orderBy('sort_order')
            ->get();

        $featuredProducts = Product::where('is_available', true)
            ->where('is_featured', true)
            ->with(['variants', 'addons', 'category'])
            ->take(8)
            ->get();

        $tables = RestaurantTable::where('is_active', true)->orderBy('table_number')->get();
        $tableNumber = $request->query('table') ?? session("table_{$business->id}");

        return view('public.menu', compact(
            'business',
            'categories',
            'featuredProducts',
            'tables',
            'tableNumber'
        ));
    }

    /**
     * Handle table QR code scan and redirect to menu with table context.
     */
    public function table(Request $request, string $business_slug, string $table_number): View
    {
        $business = Business::where('slug', $business_slug)
            ->where('status', 'active')
            ->firstOrFail();

        TenantContext::set($business);

        // Find table
        $table = RestaurantTable::where('business_id', $business->id)
            ->where('table_number', $table_number)
            ->first();

        // Track QR scan event
        if ($table && $table->qrCode) {
            $table->qrCode->increment('scan_count');
            $table->qrCode->update(['last_scanned_at' => now()]);

            QrScanEvent::create([
                'qr_code_id' => $table->qrCode->id,
                'business_id' => $business->id,
                'table_id' => $table->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'scanned_at' => now(),
            ]);
        }

        session(["table_{$business->id}" => $table_number]);

        $categories = Category::where('is_active', true)
            ->with(['products' => function ($q) {
                $q->where('is_available', true)->with(['variants', 'addons']);
            }])
            ->orderBy('sort_order')
            ->get();

        $featuredProducts = Product::where('is_available', true)
            ->where('is_featured', true)
            ->with(['variants', 'addons', 'category'])
            ->take(8)
            ->get();

        $tables = RestaurantTable::where('is_active', true)->orderBy('table_number')->get();
        $tableNumber = $table_number;

        return view('public.menu', compact(
            'business',
            'categories',
            'featuredProducts',
            'tables',
            'tableNumber'
        ));
    }

    /**
     * Display live order tracking status for customer.
     */
    public function orderStatus(Request $request, string $business_slug, string $order_number): View
    {
        $business = Business::where('slug', $business_slug)->firstOrFail();
        TenantContext::set($business);

        $order = Order::where('business_id', $business->id)
            ->where('order_number', $order_number)
            ->with(['items.addons', 'table', 'statusHistories'])
            ->firstOrFail();

        return view('public.order-status', compact('business', 'order'));
    }
}
