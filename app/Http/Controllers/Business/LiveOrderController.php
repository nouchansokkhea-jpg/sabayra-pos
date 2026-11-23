<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveOrderController extends Controller
{
    /**
     * Display the real-time Live Orders Kanban board.
     */
    public function index(Request $request): View
    {
        $business = TenantContext::get();

        $activeOrders = Order::with(['items.addons', 'items.variant', 'table', 'customer'])
            ->where('business_id', $business->id)
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready'])
            ->latest()
            ->get();

        $pending = $activeOrders->where('status', 'pending');
        $confirmed = $activeOrders->where('status', 'confirmed');
        $preparing = $activeOrders->where('status', 'preparing');
        $ready = $activeOrders->where('status', 'ready');

        return view('business.orders.live', compact('business', 'pending', 'confirmed', 'preparing', 'ready'));
    }

    /**
     * API endpoint to poll active orders for live audio alerts and kanban sync.
     */
    public function pollApi(): JsonResponse
    {
        $business = TenantContext::get();

        $orders = Order::with(['items', 'table'])
            ->where('business_id', $business->id)
            ->whereDate('created_at', Carbon::today())
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready'])
            ->latest()
            ->take(30)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_type' => $order->order_type,
                    'table_number' => $order->table ? ($order->table->name ?? $order->table->table_number) : null,
                    'customer_name' => $order->customer_name ?: 'Guest',
                    'total_amount' => (float) $order->total_amount,
                    'status' => $order->status,
                    'created_at_human' => $order->created_at->diffForHumans(),
                    'items_count' => $order->items->count(),
                ];
            });

        return response()->json([
            'orders' => $orders,
            'has_pending' => $orders->where('status', 'pending')->count() > 0,
            'total_active' => $orders->count(),
            'timestamp' => now()->timestamp,
        ]);
    }
}
