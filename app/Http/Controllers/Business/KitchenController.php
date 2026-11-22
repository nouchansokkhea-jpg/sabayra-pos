<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\Inventory\RecipeDeductionService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KitchenController extends Controller
{
    /**
     * Dedicated Kitchen Display System (KDS) full-screen screen.
     */
    public function index(Request $request): View
    {
        $business = TenantContext::get();

        $orders = Order::with(['table', 'customer', 'items.addons', 'items.variant'])
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->orderBy('created_at', 'asc')
            ->get();

        $activeCounts = [
            'total' => $orders->count(),
            'confirmed' => $orders->where('status', 'confirmed')->count(),
            'preparing' => $orders->where('status', 'preparing')->count(),
            'ready' => $orders->where('status', 'ready')->count(),
        ];

        $formattedOrders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_type' => $order->order_type,
                'status' => $order->status,
                'table_name' => $order->table ? ($order->table->name ?? 'Table ' . $order->table->table_number) : null,
                'customer_name' => $order->customer_name ?? $order->customer?->name,
                'notes' => $order->notes,
                'created_at' => $order->created_at->toISOString(),
                'elapsed_minutes' => (int) $order->created_at->diffInMinutes(now()),
                'items' => $order->items->map(fn($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => (int) $item->quantity,
                    'variant_name' => $item->variant?->name,
                    'notes' => $item->notes,
                    'addons' => $item->addons->map(fn($a) => [
                        'addon_name' => $a->addon_name,
                        'quantity' => (int) $a->quantity,
                    ]),
                ]),
            ];
        });

        return view('business.kitchen.index', compact('orders', 'formattedOrders', 'activeCounts', 'business'));
    }

    /**
     * API endpoint for Alpine.js real-time polling without full-page reloads.
     */
    public function ordersApi(Request $request): JsonResponse
    {
        $orders = Order::with(['table:id,table_number,name', 'customer:id,name,phone', 'items.addons', 'items.variant'])
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_type' => $order->order_type,
                    'status' => $order->status,
                    'table_name' => $order->table ? ($order->table->name ?? 'Table ' . $order->table->table_number) : null,
                    'customer_name' => $order->customer_name ?? $order->customer?->name,
                    'notes' => $order->notes,
                    'created_at' => $order->created_at->toISOString(),
                    'elapsed_minutes' => (int) $order->created_at->diffInMinutes(now()),
                    'items' => $order->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_name' => $item->product_name,
                            'quantity' => (int) $item->quantity,
                            'variant_name' => $item->variant?->name,
                            'notes' => $item->notes,
                            'addons' => $item->addons->map(fn($addon) => [
                                'addon_name' => $addon->addon_name,
                                'quantity' => (int) $addon->quantity,
                            ]),
                        ];
                    }),
                ];
            });

        return response()->json([
            'orders' => $orders,
            'counts' => [
                'total' => $orders->count(),
                'confirmed' => $orders->where('status', 'confirmed')->count(),
                'preparing' => $orders->where('status', 'preparing')->count(),
                'ready' => $orders->where('status', 'ready')->count(),
            ],
            'server_time' => now()->format('H:i:s'),
        ]);
    }

    /**
     * Bump order to next operational kitchen state.
     */
    public function bump(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $oldStatus = $order->status;

        // Transition mapping: confirmed -> preparing -> ready -> completed
        $nextStatus = match ($oldStatus) {
            'pending', 'confirmed' => 'preparing',
            'preparing' => 'ready',
            'ready' => 'completed',
            default => 'completed',
        };

        if ($targetStatus = $request->input('target_status')) {
            if (in_array($targetStatus, ['confirmed', 'preparing', 'ready', 'completed', 'cancelled'], true)) {
                $nextStatus = $targetStatus;
            }
        }

        $order->update(['status' => $nextStatus]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'from_status' => $oldStatus,
            'to_status' => $nextStatus,
            'note' => "Kitchen bump: {$oldStatus} -> {$nextStatus}",
            'created_at' => now(),
        ]);

        // Auto deduct ingredients when marked completed if recipes exist
        if ($nextStatus === 'completed' && $oldStatus !== 'completed') {
            app(RecipeDeductionService::class)->deductOrderIngredients($order);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Order {$order->order_number} bumped to {$nextStatus}.",
                'from_status' => $oldStatus,
                'to_status' => $nextStatus,
            ]);
        }

        return back()->with('success', "Order {$order->order_number} updated to {$nextStatus}.");
    }
}
