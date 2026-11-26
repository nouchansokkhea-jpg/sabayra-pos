<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PaymentProof;
use App\Models\SecurityLog;
use App\Models\User;
use App\Services\Inventory\RecipeDeductionService;
use App\Services\Notifications\BusinessNotificationService;
use App\Services\Payment\PaymentProofService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrderManagementController extends Controller
{
    public function __construct(
        private PaymentProofService $proofService,
    ) {
    }

    /**
     * Display listing of business orders with live filters.
     */
    public function index(Request $request): View
    {
        $query = Order::with(['table', 'customer', 'items.addons', 'proofs']);

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

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($type = $request->input('order_type')) {
            $query->where('order_type', $type);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

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
     * Show order details including tax/service breakdown, proofs, and transactions.
     */
    public function show(Order $order): View
    {
        $this->authorizeOrderAccess($order);

        $order->load([
            'table',
            'branch',
            'customer',
            'items.addons',
            'statusHistories.user',
            'proofs.reviewedBy',
            'paymentTransactions',
            'paymentVerifiedBy',
            'paymentCollectedBy',
        ]);

        return view('business.orders.show', compact('order'));
    }

    /**
     * Update order status and append to status history.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

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
            app(BusinessNotificationService::class)
                ->notifyOrderEvent($order, "order.{$newStatus}", $validated['note'] ?? null);
        }

        return back()->with('success', "Order {$order->order_number} marked as {$newStatus}.");
    }

    /**
     * Update order payment status and/or method with server audit trail.
     */
    public function updatePayment(Request $request, Order $order): RedirectResponse
    {
        $business = $this->authorizeOrderAccess($order);
        /** @var User $staff */
        $staff = Auth::user();

        $validated = $request->validate([
            'payment_status' => ['required', 'string', 'in:unpaid,pending_verification,pending_collection,paid,rejected,failed,refunded'],
            'payment_method' => ['nullable', 'string', 'in:cash,cod,khqr,aba_payway,bank_transfer,static_qr,card'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $previousPaymentStatus = $order->payment_status;
        $targetStatus = $validated['payment_status'];

        $updatePayload = [
            'payment_status' => $targetStatus,
        ];

        if (! empty($validated['payment_method'])) {
            $updatePayload['payment_method'] = $validated['payment_method'];
        }

        if ($targetStatus === 'paid' && $previousPaymentStatus !== 'paid') {
            $now = now();
            $updatePayload['paid_at'] = $order->paid_at ?? $now;
            $updatePayload['payment_verified_at'] = $order->payment_verified_at ?? $now;
            $updatePayload['payment_verified_by'] = $order->payment_verified_by ?? $staff->id;
        }

        $order->update($updatePayload);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'user_id' => $staff->id,
            'from_status' => $order->status,
            'to_status' => $order->status,
            'note' => $validated['note'] ?? "Payment status changed from {$previousPaymentStatus} to {$targetStatus}.",
            'created_at' => now(),
        ]);

        SecurityLog::create([
            'user_id' => $staff->id,
            'business_id' => $business->id,
            'event_type' => 'order_payment_status_updated',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'from' => $previousPaymentStatus,
                'to' => $targetStatus,
                'method' => $order->payment_method,
            ],
        ]);

        if ($targetStatus === 'paid' && $previousPaymentStatus !== 'paid') {
            app(BusinessNotificationService::class)
                ->notifyOrderEvent($order, 'order.payment_received');
        }

        return back()->with('success', "Payment status updated for order {$order->order_number}.");
    }

    /**
     * Staff approves uploaded customer payment proof (Bank Transfer / Static QR / PayWay Link).
     */
    public function approvePayment(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);
        /** @var User $staff */
        $staff = Auth::user();

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->proofService->approveOrderPayment(
            $order,
            $staff,
            $validated['review_note'] ?? null,
            $request->ip(),
            $request->userAgent()
        );

        return back()->with('success', "Payment approved for order {$order->order_number}.");
    }

    /**
     * Staff rejects uploaded customer payment proof with a required reason.
     */
    public function rejectPayment(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);
        /** @var User $staff */
        $staff = Auth::user();

        $validated = $request->validate([
            'review_note' => ['required', 'string', 'max:500'],
        ]);

        $this->proofService->rejectOrderPayment(
            $order,
            $staff,
            $validated['review_note'],
            $request->ip(),
            $request->userAgent()
        );

        return back()->with('success', "Payment proof rejected for order {$order->order_number}.");
    }

    /**
     * Staff confirms cash collection for a Cash on Delivery order.
     */
    public function collectCod(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);
        /** @var User $staff */
        $staff = Auth::user();

        $validated = $request->validate([
            'collection_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->proofService->collectCodPayment(
            $order,
            $staff,
            $validated['collection_note'] ?? null,
            $request->ip(),
            $request->userAgent()
        );

        return back()->with('success', "Cash on Delivery collected for order {$order->order_number}.");
    }

    /**
     * Stream a privately stored customer payment proof to an authorized staff member of the same tenant.
     */
    public function showProof(Order $order, PaymentProof $proof): Response
    {
        $business = $this->authorizeOrderAccess($order);

        abort_if($proof->order_id !== $order->id || $proof->business_id !== $business->id, 403);
        abort_unless(Storage::disk('local')->exists($proof->file_path), 404);

        $contents = Storage::disk('local')->get($proof->file_path);

        return response($contents, 200, [
            'Content-Type' => $proof->mime_type ?: 'image/png',
            'Content-Disposition' => 'inline; filename="' . basename($proof->file_path) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /**
     * Render 80mm thermal receipt layout for printing.
     */
    public function receipt(Order $order): View
    {
        $business = $this->authorizeOrderAccess($order);

        $order->load(['table', 'items.addons', 'branch', 'paymentVerifiedBy', 'paymentCollectedBy']);

        return view('business.orders.receipt', compact('order', 'business'));
    }

    /**
     * Render 80mm thermal kitchen order docket layout for printing without financial data.
     */
    public function kitchenTicket(Order $order): View
    {
        $business = $this->authorizeOrderAccess($order);

        $order->load(['table', 'items.addons', 'items.variant', 'branch']);

        return view('business.orders.kitchen_ticket', compact('order', 'business'));
    }

    /**
     * Ensure current authenticated staff belongs to the tenant owning the requested order.
     */
    private function authorizeOrderAccess(Order $order): Business
    {
        $business = TenantContext::get();
        /** @var User|null $user */
        $user = Auth::user();

        if (! $business || ! $user || $order->business_id !== $business->id || ! $user->belongsToBusiness($business)) {
            abort(403, 'Unauthorized order access.');
        }

        return $business;
    }
}
