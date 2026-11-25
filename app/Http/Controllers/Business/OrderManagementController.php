<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\Inventory\RecipeDeductionService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderManagementController extends Controller
{
    /**
     * Display listing of business orders with live filters.
     */
    public function index(Request $request): View
    {
        $query = Order::with(['table', 'customer', 'items.addons']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('order_type')) {
            $query->where('order_type', $type);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        // Status counts for badge tabs
        $statusCounts = [
            'all' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'confirmed' => Order::where('status', 'confirmed')->count(),
            'preparing' => Order::where('status', 'preparing')->count(),
            'ready' => Order::where('status', 'ready')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];

        return view('business.orders.index', compact('orders', 'statusCounts'));
    }

    /**
     * Show order details.
     */
    public function show(Order $order): View
    {
        $order->load(['table', 'customer', 'items.addons', 'statusHistories.user']);
        return view('business.orders.show', compact('order'));
    }

    /**
     * Update order status and append to status history.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,confirmed,preparing,ready,out_for_delivery,completed,cancelled'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        $order->update(['status' => $newStatus]);

        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            app(RecipeDeductionService::class)->deductOrderIngredients($order);
        }

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'note' => $validated['note'] ?? "Status updated from {$oldStatus} to {$newStatus}.",
            'created_at' => now(),
        ]);

        if ($oldStatus !== $newStatus && in_array($newStatus, ['confirmed', 'completed', 'cancelled'], true)) {
            app(\App\Services\Notifications\BusinessNotificationService::class)
                ->notifyOrderEvent($order, "order.{$newStatus}", $validated['note'] ?? null);
        }

        return back()->with('success', "Order {$order->order_number} marked as {$newStatus}.");
    }

    /**
     * Mark order payment status.
     */
    public function updatePayment(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'string', 'in:unpaid,paid,refunded'],
            'payment_method' => ['nullable', 'string', 'in:cash,khqr,card'],
        ]);

        $previousPaymentStatus = $order->payment_status;
        $order->update($validated);

        if ($validated['payment_status'] === 'paid' && $previousPaymentStatus !== 'paid') {
            app(\App\Services\Notifications\BusinessNotificationService::class)
                ->notifyOrderEvent($order, 'order.payment_received');
        }

        return back()->with('success', "Payment status updated for order {$order->order_number}.");
    }

    /**
     * Render thermal receipt layout for printing.
     */
    public function receipt(Order $order): View
    {
        $business = TenantContext::get();
        abort_if($order->business_id !== $business->id, 403);

        $order->load(['table', 'items.addons', 'branch']);

        return view('business.orders.receipt', compact('order', 'business'));
    }

    /**
     * Render 80mm thermal kitchen order docket layout for printing without financial data.
     */
    public function kitchenTicket(Order $order): View
    {
        $business = TenantContext::get();
        abort_if($order->business_id !== $business->id, 403);

        $order->load(['table', 'items.addons', 'items.variant', 'branch']);

        return view('business.orders.kitchen_ticket', compact('order', 'business'));
    }
}
