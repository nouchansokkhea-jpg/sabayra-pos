<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessPaymentConfig;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payment\AbaPayWayService;
use App\Services\Payment\BakongKhqrService;
use App\Services\Payment\PaymentGatewayVisibilityService;
use App\Services\Payment\PaymentProofService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CustomerOrderPaymentController extends Controller
{
    public function __construct(
        private PaymentGatewayVisibilityService $visibilityService,
        private PaymentProofService $proofService,
        private AbaPayWayService $abaPayWayService,
        private BakongKhqrService $bakongKhqrService,
    ) {
    }

    /**
     * Accept a customer's manual payment proof upload on the order status page.
     * Never automatically marks an order as paid upon upload.
     */
    public function uploadProof(Request $request, string $business_slug, string $order_number): RedirectResponse|JsonResponse
    {
        $decodedSlug = urldecode($business_slug);
        $business = Business::where(function ($query) use ($business_slug, $decodedSlug): void {
            $query->where('slug', $business_slug)
                ->orWhere('slug', $decodedSlug);
        })
            ->where('status', 'active')
            ->firstOrFail();

        $order = Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('order_number', $order_number)
            ->firstOrFail();

        $proofToken = (string) ($request->input('proof_token') ?? $request->query('token') ?? '');
        if (! $this->visibilityService->verifyOrderProofToken($order, $proofToken)) {
            abort(403, 'Invalid or missing order payment authorization token.');
        }

        if ($order->payment_status === 'paid' || in_array($order->status, ['cancelled', 'completed'], true)) {
            $message = 'Payment proof uploads are closed for this order.';

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        if (! in_array($order->payment_method, ['bank_transfer', 'static_qr', 'aba_payway'], true)) {
            $message = 'Payment proof upload is not applicable to the selected payment method.';

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        $validated = $request->validate([
            'proof_file' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'customer_reference' => ['nullable', 'string', 'max:100'],
            'customer_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $proof = $this->proofService->storeCustomerProof(
                $order,
                $request->file('proof_file'),
                $validated['customer_reference'] ?? null,
                $validated['customer_note'] ?? null,
                $request->ip()
            );
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['proof_file' => $e->getMessage()])->withInput();
        }

        $statusUrl = route('public.order-status', [
            'business_slug' => $business->slug,
            'order_number' => $order->order_number,
            'token' => $this->visibilityService->generateOrderProofToken($order),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Payment proof uploaded and is now pending verification by the merchant.',
                'payment_status' => 'pending_verification',
                'proof_id' => $proof->id,
                'status_url' => $statusUrl,
            ], 201);
        }

        return redirect($statusUrl)->with(
            'success',
            'Payment proof received. Your order payment status is now Pending Verification until staff reviews it.'
        );
    }

    /**
     * Perform server-side payment verification for Bakong KHQR or ABA PayWay transactions.
     */
    public function verifyStatus(Request $request, string $business_slug, string $order_number): JsonResponse|RedirectResponse
    {
        $decodedSlug = urldecode($business_slug);
        $business = Business::where(function ($query) use ($business_slug, $decodedSlug): void {
            $query->where('slug', $business_slug)
                ->orWhere('slug', $decodedSlug);
        })
            ->where('status', 'active')
            ->firstOrFail();

        $order = Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('order_number', $order_number)
            ->firstOrFail();

        $token = (string) ($request->input('token') ?? $request->query('token') ?? '');
        if (! $this->visibilityService->verifyOrderProofToken($order, $token)) {
            abort(403, 'Invalid or missing order verification token.');
        }

        $order->refresh();
        if ($order->payment_status === 'paid') {
            $result = ['ok' => true, 'paid' => true, 'status' => 'paid', 'message' => 'Payment verified and confirmed.'];
        } else {
            $tx = PaymentTransaction::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('order_id', $order->id)
                ->latest('created_at')
                ->first();

            if (! $tx) {
                $result = [
                    'ok' => true,
                    'paid' => false,
                    'status' => $order->payment_status,
                    'message' => 'No live gateway transaction found for this order.',
                ];
            } elseif ($tx->gateway === 'aba_payway') {
                $config = BusinessPaymentConfig::resolveForBusiness($business);
                $result = $this->abaPayWayService->verifyTransactionStatus($tx, $config);
            } elseif ($tx->gateway === 'bakong_khqr') {
                $result = $this->bakongKhqrService->verifyTransactionStatus($tx);
            } else {
                $result = [
                    'ok' => true,
                    'paid' => false,
                    'status' => $order->payment_status,
                    'message' => 'Manual payment methods require staff verification.',
                ];
            }
        }

        if ($request->expectsJson()) {
            return response()->json($result, $result['ok'] ? 200 : 422);
        }

        $statusUrl = route('public.order-status', [
            'business_slug' => $business->slug,
            'order_number' => $order->order_number,
            'token' => $this->visibilityService->generateOrderProofToken($order),
        ]);

        return redirect($statusUrl)->with(
            $result['paid'] ? 'success' : 'info',
            $result['message']
        );
    }
}
