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
use App\Services\Payment\PaymentGatewayVisibilityService;
use App\Services\Payment\PaymentProofService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class OrderManagementController extends Controller
{
    public function __construct(
        private PaymentProofService $proofService,
        private PaymentGatewayVisibilityService $visibilityService,
    ) {
    }

    /**
     * Display listing of business orders with live filters.
     */
    public function index(Request $request): View
    {
        $query = Order::with([
            'table',
            'customer',
            'items.addons',
            'proofs:id,business_id,order_id,payment_transaction_id,payment_method,file_path,original_filename,mime_type,file_size,customer_reference,status,reviewed_by,reviewed_at,created_at',
        ]);

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
        $business = $this->authorizeOrderAccess($order);

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

        $customerTrackingUrl = route('public.order-status', [
            'business_slug' => $business->slug,
            'order_number' => $order->order_number,
            'token' => $this->visibilityService->generateOrderProofToken($order),
        ]);

        return view('business.orders.show', compact('order', 'customerTrackingUrl'));
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
     * Falls back to persistent database binary storage when ephemeral container disk was cleared,
     * and renders a structured audit summary when inspecting pre-upgrade proofs without stored bytes.
     */
    public function showProof(Request $request, Order $order, PaymentProof $proof): Response
    {
        $business = $this->authorizeOrderAccess($order);

        abort_if($proof->order_id !== $order->id || $proof->business_id !== $business->id, 403);

        $contents = $proof->resolveBinaryContents();
        if ($contents !== null) {
            $safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string) ($proof->original_filename ?: $proof->file_path))) ?: 'payment-proof.png';

            return response($contents, 200, [
                'Content-Type' => $proof->mime_type ?: 'image/png',
                'Content-Disposition' => 'inline; filename="' . $safeFilename . '"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
            ]);
        }

        $proof->loadMissing('reviewedBy');

        $customerTrackingUrl = route('public.order-status', [
            'business_slug' => $business->slug,
            'order_number' => $order->order_number,
            'token' => $this->visibilityService->generateOrderProofToken($order),
        ]);

        $acceptHeader = (string) $request->header('Accept', '');
        if (str_contains($acceptHeader, 'text/html')) {
            return response()->view(
                'business.orders.payment_proof_unavailable',
                compact('order', 'proof', 'business', 'customerTrackingUrl'),
                200,
                [
                    'X-Content-Type-Options' => 'nosniff',
                    'Cache-Control' => 'private, no-store, max-age=0',
                ]
            );
        }

        $refLabel = htmlspecialchars((string) ($proof->customer_reference ?: $proof->original_filename ?: 'Payment Proof'), ENT_QUOTES, 'UTF-8');
        $svgPlaceholder = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="320" viewBox="0 0 640 320">
  <rect width="640" height="320" rx="20" fill="#0f172a"/>
  <rect x="20" y="20" width="600" height="280" rx="14" fill="#1e293b" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="6 4"/>
  <text x="320" y="125" text-anchor="middle" fill="#fbbf24" font-family="system-ui, sans-serif" font-size="16" font-weight="700">Screenshot Expired on Temporary Container Storage</text>
  <text x="320" y="158" text-anchor="middle" fill="#e2e8f0" font-family="monospace" font-size="13">{$refLabel}</text>
  <text x="320" y="192" text-anchor="middle" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="12">Uploaded prior to persistent database proof storage. Metadata &amp; reference remain intact.</text>
</svg>
SVG;

        return response($svgPlaceholder, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
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
