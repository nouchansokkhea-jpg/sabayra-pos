<?php

namespace App\Services\Payment;

use App\Models\Business;
use App\Models\BusinessPaymentConfig;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Services\Notifications\BusinessNotificationService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AbaPayWayService
{
    /**
     * Validate ABA PayWay Link and optional Merchant API credentials before persisting.
     *
     * @return array{ok: bool, error?: string, mode: string}
     */
    public function validateConfigurationInput(?string $paywayLink, ?string $merchantId, ?string $apiKey, bool $enabled): array
    {
        $cleanLink = trim((string) $paywayLink);
        $cleanMerchantId = trim((string) $merchantId);
        $cleanApiKey = trim((string) $apiKey);

        if ($cleanLink !== '') {
            $parsed = parse_url($cleanLink);
            $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
            $host = strtolower((string) ($parsed['host'] ?? ''));

            if ($scheme !== 'https' || ($host !== 'payway.com.kh' && ! str_ends_with($host, '.payway.com.kh'))) {
                return [
                    'ok' => false,
                    'error' => 'ABA PayWay Link must be a valid HTTPS URL hosted on payway.com.kh (for example https://link.payway.com.kh/...).',
                    'mode' => 'invalid',
                ];
            }
        }

        if ($enabled && $cleanLink === '' && ($cleanMerchantId === '' || $cleanApiKey === '')) {
            return [
                'ok' => false,
                'error' => 'Provide either valid ABA PayWay Merchant API credentials (Merchant ID + API Key) or an official ABA PayWay HTTPS link before enabling Pay with ABA.',
                'mode' => 'unconfigured',
            ];
        }

        if ($cleanMerchantId !== '' && $cleanApiKey !== '') {
            return ['ok' => true, 'mode' => 'merchant_api'];
        }

        if ($cleanLink !== '') {
            return ['ok' => true, 'mode' => 'payway_link'];
        }

        return ['ok' => true, 'mode' => 'disabled'];
    }

    /**
     * Test ABA PayWay connection & configuration against official Merchant API (when API credentials exist).
     *
     * @return array{ok: bool, status: string, message: string}
     */
    public function testConnection(BusinessPaymentConfig $config): array
    {
        if (! $config->hasAbaMerchantApiCredentials()) {
            if ($config->hasValidAbaPaywayLink()) {
                $message = 'Valid ABA PayWay Payment Link configured. Note: Static PayWay Links redirect customers manually and do NOT support server-side dynamic KHQR creation or automatic payment callbacks without Merchant API credentials (Merchant ID + API Key).';
                $config->forceFill([
                    'aba_last_tested_at' => now(),
                    'aba_test_status' => 'link_only',
                    'aba_test_message' => Str::limit($message, 490),
                ])->save();

                return ['ok' => true, 'status' => 'link_only', 'message' => $message];
            }

            $message = 'ABA PayWay Merchant API credentials (Merchant ID and API Key) are missing.';
            $config->forceFill([
                'aba_last_tested_at' => now(),
                'aba_test_status' => 'failed',
                'aba_test_message' => $message,
            ])->save();

            return ['ok' => false, 'status' => 'failed', 'message' => $message];
        }

        $merchantId = (string) $config->aba_merchant_id;
        $apiKey = (string) $config->aba_api_key;
        $reqTime = gmdate('YmdHis');
        $probeTranId = 'TEST' . strtoupper(Str::random(10));
        $hash = $this->computeCheckTransactionHash($reqTime, $merchantId, $probeTranId, $apiKey);

        $endpoint = $this->endpoint($config->aba_environment, '/api/payment-gateway/v1/payments/check-transaction-2');

        try {
            $response = $this->httpClient()->post($endpoint, [
                'req_time' => $reqTime,
                'merchant_id' => $merchantId,
                'tran_id' => $probeTranId,
                'hash' => $hash,
            ]);

            $json = $response->json();
            $statusCode = (string) data_get($json, 'status.code', '');
            $statusMsg = (string) data_get($json, 'status.message', '');

            // ABA returns 401/403 or invalid hash/merchant errors when credentials are rejected;
            // non-auth structured PayWay codes (such as Transaction Not Found) prove credentials & signature passed.
            $authRejected = $response->status() === 401
                || $response->status() === 403
                || in_array($statusCode, ['1', '2', '3', '4', '5'], true) && str_contains(strtolower($statusMsg), 'hash')
                || str_contains(strtolower($statusMsg), 'unauthorized')
                || str_contains(strtolower($statusMsg), 'invalid merchant');

            if ($response->successful() && is_array($json) && ! $authRejected) {
                $message = "ABA PayWay Merchant API authenticated successfully ({$config->aba_environment}).";
                $config->forceFill([
                    'aba_last_tested_at' => now(),
                    'aba_test_status' => 'valid',
                    'aba_test_message' => $message,
                ])->save();

                return ['ok' => true, 'status' => 'valid', 'message' => $message];
            }

            $errorDetail = $statusMsg !== '' ? $statusMsg : 'HTTP ' . $response->status();
            $safeMessage = $this->sanitizeError("ABA PayWay API rejected credentials: {$errorDetail}", $apiKey);

            $config->forceFill([
                'aba_last_tested_at' => now(),
                'aba_test_status' => 'failed',
                'aba_test_message' => Str::limit($safeMessage, 490),
            ])->save();

            return ['ok' => false, 'status' => 'failed', 'message' => $safeMessage];
        } catch (\Throwable $e) {
            $safeMessage = $this->sanitizeError('Could not reach ABA PayWay API: ' . $e->getMessage(), $apiKey);
            $config->forceFill([
                'aba_last_tested_at' => now(),
                'aba_test_status' => 'failed',
                'aba_test_message' => Str::limit($safeMessage, 490),
            ])->save();

            return ['ok' => false, 'status' => 'failed', 'message' => $safeMessage];
        }
    }

    /**
     * Create an ABA PayWay dynamic KHQR / payment transaction record on checkout.
     *
     * @return array{ok: bool, transaction?: PaymentTransaction, error?: string}
     */
    public function createOrderTransaction(Order $order, BusinessPaymentConfig $config): array
    {
        if (! $config->hasAbaMerchantApiCredentials()) {
            if ($config->hasValidAbaPaywayLink()) {
                $reference = $this->generateUniqueReference($order);
                $tx = PaymentTransaction::withoutGlobalScopes()->create([
                    'business_id' => $order->business_id,
                    'order_id' => $order->id,
                    'gateway' => 'aba_payway',
                    'reference_number' => $reference,
                    'checkout_url' => $config->aba_payway_link,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency ?: 'USD',
                    'status' => 'pending',
                    'idempotency_key' => "aba_link:{$order->id}:{$reference}",
                    'expires_at' => now()->addMinutes(30),
                    'notes' => 'ABA PayWay Link mode — manual merchant link without API callback verification.',
                ]);

                $order->forceFill(['payment_reference' => $reference])->save();

                return ['ok' => true, 'transaction' => $tx];
            }

            return [
                'ok' => false,
                'error' => 'ABA PayWay merchant credentials are not configured for this business.',
            ];
        }

        $merchantId = (string) $config->aba_merchant_id;
        $apiKey = (string) $config->aba_api_key;
        $reqTime = gmdate('YmdHis');
        $tranId = $this->generateUniqueReference($order);
        $currency = strtoupper((string) ($order->currency ?: 'USD')) === 'KHR' ? 'KHR' : 'USD';
        $amount = $currency === 'KHR'
            ? (string) round((float) $order->total_khr)
            : number_format((float) $order->total_amount, 2, '.', '');

        $itemsPayload = base64_encode((string) json_encode(
            $order->items()->get(['product_name', 'quantity', 'unit_price'])->map(fn ($i): array => [
                'name' => $i->product_name,
                'quantity' => (float) $i->quantity,
                'price' => (float) $i->unit_price,
            ])->all()
        ));

        $callbackUrl = base64_encode(route('webhooks.aba-payway', ['reference' => $tranId]));
        $firstName = trim(Str::before((string) ($order->customer_name ?: 'Guest'), ' ')) ?: 'Guest';
        $lastName = trim(Str::after((string) ($order->customer_name ?: ''), ' ')) ?: 'Customer';
        $email = '';
        $phone = trim((string) ($order->customer_phone ?? ''));
        $purchaseType = 'purchase';
        $paymentOption = 'abapay_khqr';
        $returnDeeplink = '';
        $customFields = '';
        $returnParams = '';
        $payout = '';
        $lifetime = '15';
        $qrTemplate = 'template3_color';

        $hash = $this->computeGenerateQrHash([
            'req_time' => $reqTime,
            'merchant_id' => $merchantId,
            'tran_id' => $tranId,
            'amount' => $amount,
            'items' => $itemsPayload,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'purchase_type' => $purchaseType,
            'payment_option' => $paymentOption,
            'callback_url' => $callbackUrl,
            'return_deeplink' => $returnDeeplink,
            'currency' => $currency,
            'custom_fields' => $customFields,
            'return_params' => $returnParams,
            'payout' => $payout,
            'lifetime' => $lifetime,
            'qr_image_template' => $qrTemplate,
        ], $apiKey);

        $endpoint = $this->endpoint($config->aba_environment, '/api/payment-gateway/v1/payments/generate-qr');

        try {
            $response = $this->httpClient()->post($endpoint, [
                'req_time' => $reqTime,
                'merchant_id' => $merchantId,
                'tran_id' => $tranId,
                'amount' => $amount,
                'items' => $itemsPayload,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'purchase_type' => $purchaseType,
                'payment_option' => $paymentOption,
                'callback_url' => $callbackUrl,
                'return_deeplink' => $returnDeeplink,
                'currency' => $currency,
                'custom_fields' => $customFields,
                'return_params' => $returnParams,
                'payout' => $payout,
                'lifetime' => (int) $lifetime,
                'qr_image_template' => $qrTemplate,
                'hash' => $hash,
            ]);

            $json = $response->json();
            $statusCode = (string) data_get($json, 'status.code', '');

            if ($response->successful() && is_array($json) && $statusCode === '0') {
                $qrString = (string) (data_get($json, 'qrString') ?? '');
                $qrImage = (string) (data_get($json, 'qrImage') ?? '');
                $deeplink = (string) (data_get($json, 'abapay_deeplink') ?? '');

                $tx = PaymentTransaction::withoutGlobalScopes()->create([
                    'business_id' => $order->business_id,
                    'order_id' => $order->id,
                    'gateway' => 'aba_payway',
                    'reference_number' => $tranId,
                    'external_transaction_id' => $tranId,
                    'khqr_payload' => $qrString !== '' ? $qrString : null,
                    'khqr_md5' => $qrString !== '' ? md5($qrString) : null,
                    'qr_image_url' => $qrImage !== '' ? $qrImage : null,
                    'checkout_url' => $deeplink !== '' ? $deeplink : null,
                    'amount' => $currency === 'KHR' ? (float) $order->total_khr : (float) $order->total_amount,
                    'currency' => $currency,
                    'status' => 'pending',
                    'idempotency_key' => "aba_api:{$order->id}:{$tranId}",
                    'expires_at' => now()->addMinutes((int) $lifetime),
                    'gateway_payload' => $this->redactSecretsFromArray($json, $apiKey),
                ]);

                $order->forceFill(['payment_reference' => $tranId])->save();

                return ['ok' => true, 'transaction' => $tx];
            }

            $err = (string) (data_get($json, 'status.message') ?? 'ABA PayWay rejected payment creation.');
            $safeErr = $this->sanitizeError($err, $apiKey);

            $tx = PaymentTransaction::withoutGlobalScopes()->create([
                'business_id' => $order->business_id,
                'order_id' => $order->id,
                'gateway' => 'aba_payway',
                'reference_number' => $tranId,
                'amount' => $currency === 'KHR' ? (float) $order->total_khr : (float) $order->total_amount,
                'currency' => $currency,
                'status' => 'failed',
                'idempotency_key' => "aba_api_fail:{$order->id}:{$tranId}",
                'notes' => $safeErr,
            ]);

            return ['ok' => false, 'transaction' => $tx, 'error' => $safeErr];
        } catch (\Throwable $e) {
            $safeErr = $this->sanitizeError('Could not reach ABA PayWay gateway: ' . $e->getMessage(), $apiKey);
            Log::warning('ABA PayWay generate-qr failed', ['order_id' => $order->id, 'error' => $safeErr]);

            return ['ok' => false, 'error' => $safeErr];
        }
    }

    /**
     * Check official ABA PayWay transaction status via check-transaction-2 API.
     *
     * @return array{ok: bool, paid: bool, status: string, message: string}
     */
    public function verifyTransactionStatus(PaymentTransaction $transaction, BusinessPaymentConfig $config): array
    {
        if ($transaction->status === 'paid') {
            return ['ok' => true, 'paid' => true, 'status' => 'paid', 'message' => 'Payment already verified.'];
        }

        if ($transaction->isExpired() && $transaction->status === 'pending') {
            $transaction->update(['status' => 'expired']);

            return ['ok' => true, 'paid' => false, 'status' => 'expired', 'message' => 'ABA PayWay QR expired.'];
        }

        if (! $config->hasAbaMerchantApiCredentials()) {
            return [
                'ok' => false,
                'paid' => false,
                'status' => $transaction->status,
                'message' => 'Server-side verification requires ABA PayWay Merchant ID and API Key.',
            ];
        }

        $merchantId = (string) $config->aba_merchant_id;
        $apiKey = (string) $config->aba_api_key;
        $reqTime = gmdate('YmdHis');
        $hash = $this->computeCheckTransactionHash($reqTime, $merchantId, $transaction->reference_number, $apiKey);
        $endpoint = $this->endpoint($config->aba_environment, '/api/payment-gateway/v1/payments/check-transaction-2');

        try {
            $response = $this->httpClient()->post($endpoint, [
                'req_time' => $reqTime,
                'merchant_id' => $merchantId,
                'tran_id' => $transaction->reference_number,
                'hash' => $hash,
            ]);

            $json = $response->json();
            $paymentStatus = strtoupper((string) data_get($json, 'data.payment_status', ''));
            $paymentCode = data_get($json, 'data.payment_status_code');
            $paidAmount = (float) data_get($json, 'data.amount', 0);

            if ($response->successful() && ($paymentStatus === 'APPROVED' || $paymentCode === 0 || $paymentCode === '0')) {
                if (abs($paidAmount - (float) $transaction->amount) > 0.01) {
                    return [
                        'ok' => false,
                        'paid' => false,
                        'status' => 'failed',
                        'message' => 'ABA PayWay confirmed an amount mismatch.',
                    ];
                }

                $this->markTransactionPaidAtomically($transaction, $json, $transaction->reference_number, $apiKey);

                return ['ok' => true, 'paid' => true, 'status' => 'paid', 'message' => 'ABA PayWay payment approved and confirmed.'];
            }

            if (in_array($paymentStatus, ['DECLINED', 'CANCELLED', 'FAILED', 'REFUNDED'], true)) {
                $targetStatus = $paymentStatus === 'REFUNDED' ? 'refunded' : 'failed';
                $transaction->update([
                    'status' => $targetStatus,
                    'gateway_payload' => $this->redactSecretsFromArray((array) $json, $apiKey),
                ]);

                if ($targetStatus === 'failed') {
                    $transaction->order()->where('payment_status', '!=', 'paid')->update(['payment_status' => 'failed']);
                }

                return ['ok' => true, 'paid' => false, 'status' => $targetStatus, 'message' => "Payment status: {$paymentStatus}."];
            }

            return [
                'ok' => true,
                'paid' => false,
                'status' => 'pending',
                'message' => 'Payment is still awaiting confirmation from ABA PayWay.',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'paid' => false,
                'status' => $transaction->status,
                'message' => $this->sanitizeError('ABA PayWay check failed: ' . $e->getMessage(), $apiKey),
            ];
        }
    }

    /**
     * Handle asynchronous ABA PayWay webhook callback with HMAC-SHA512 signature check & idempotency.
     *
     * @return array{ok: bool, status_code: int, message: string}
     */
    public function handleWebhookCallback(Request $request, string $reference): array
    {
        $transaction = PaymentTransaction::withoutGlobalScopes()
            ->where('gateway', 'aba_payway')
            ->where('reference_number', $reference)
            ->first();

        if (! $transaction) {
            return ['ok' => false, 'status_code' => 404, 'message' => 'Transaction not found.'];
        }

        $business = Business::find($transaction->business_id);
        if (! $business) {
            return ['ok' => false, 'status_code' => 404, 'message' => 'Business not found.'];
        }

        $config = BusinessPaymentConfig::resolveForBusiness($business);
        if (! $config->hasAbaMerchantApiCredentials()) {
            return ['ok' => false, 'status_code' => 403, 'message' => 'ABA PayWay merchant credentials not configured.'];
        }

        $apiKey = (string) $config->aba_api_key;
        $incomingHmac = trim((string) ($request->header('X-PayWay-HMAC-SHA512') ?? $request->input('hash', '')));
        $rawBody = (string) $request->getContent();

        if ($incomingHmac === '') {
            return ['ok' => false, 'status_code' => 403, 'message' => 'Missing PayWay signature.'];
        }

        $expectedRawSig = base64_encode(hash_hmac('sha512', $rawBody, $apiKey, true));
        $expectedTranSig = base64_encode(hash_hmac('sha512', $reference . ((string) $request->input('status', '')), $apiKey, true));

        $sigValid = hash_equals($expectedRawSig, $incomingHmac) || hash_equals($expectedTranSig, $incomingHmac);
        if (! $sigValid) {
            Log::warning('Invalid ABA PayWay callback signature', ['reference' => $reference]);

            return ['ok' => false, 'status_code' => 403, 'message' => 'Invalid signature.'];
        }

        $payload = $request->all();
        $tranId = (string) ($payload['tran_id'] ?? $reference);

        if ($tranId !== $transaction->reference_number) {
            return ['ok' => false, 'status_code' => 422, 'message' => 'Transaction reference mismatch.'];
        }

        if ($transaction->status === 'paid') {
            return ['ok' => true, 'status_code' => 200, 'message' => 'Already processed (idempotent).'];
        }

        $statusVal = $payload['status'] ?? null;
        $paymentStatus = strtoupper((string) ($payload['payment_status'] ?? ''));

        if ($statusVal === 0 || $statusVal === '0' || $paymentStatus === 'APPROVED') {
            $paid = $this->markTransactionPaidAtomically($transaction, $payload, $tranId, $apiKey);

            return [
                'ok' => true,
                'status_code' => 200,
                'message' => $paid ? 'Payment confirmed.' : 'Already confirmed.',
            ];
        }

        $transaction->update([
            'status' => 'failed',
            'gateway_payload' => $this->redactSecretsFromArray($payload, $apiKey),
        ]);
        $transaction->order()->where('payment_status', '!=', 'paid')->update(['payment_status' => 'failed']);

        return ['ok' => true, 'status_code' => 200, 'message' => 'Failed status recorded.'];
    }

    /**
     * Compute ABA PayWay official HMAC-SHA512 Base64 hash for /generate-qr.
     *
     * @param  array<string, scalar>  $fields
     */
    public function computeGenerateQrHash(array $fields, string $apiKey): string
    {
        $sequence = [
            'req_time',
            'merchant_id',
            'tran_id',
            'amount',
            'items',
            'first_name',
            'last_name',
            'email',
            'phone',
            'purchase_type',
            'payment_option',
            'callback_url',
            'return_deeplink',
            'currency',
            'custom_fields',
            'return_params',
            'payout',
            'lifetime',
            'qr_image_template',
        ];

        $b4hash = '';
        foreach ($sequence as $key) {
            $b4hash .= (string) ($fields[$key] ?? '');
        }

        return base64_encode(hash_hmac('sha512', $b4hash, $apiKey, true));
    }

    /**
     * Compute ABA PayWay official HMAC-SHA512 Base64 hash for /check-transaction-2.
     */
    public function computeCheckTransactionHash(string $reqTime, string $merchantId, string $tranId, string $apiKey): string
    {
        return base64_encode(hash_hmac('sha512', $reqTime . $merchantId . $tranId, $apiKey, true));
    }

    private function markTransactionPaidAtomically(
        PaymentTransaction $transaction,
        array $gatewayPayload,
        string $externalId,
        string $apiKey
    ): bool {
        $transitioned = DB::transaction(function () use ($transaction, $gatewayPayload, $externalId, $apiKey): bool {
            $locked = PaymentTransaction::withoutGlobalScopes()->whereKey($transaction->id)->lockForUpdate()->first();
            if (! $locked || $locked->status === 'paid') {
                return false;
            }

            $now = now();
            $locked->update([
                'status' => 'paid',
                'external_transaction_id' => $externalId,
                'verified_at' => $now,
                'gateway_payload' => $this->redactSecretsFromArray($gatewayPayload, $apiKey),
            ]);

            $order = Order::withoutGlobalScopes()->whereKey($locked->order_id)->lockForUpdate()->first();
            if ($order && $order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'aba_payway',
                    'payment_reference' => $locked->reference_number,
                    'gateway_transaction_id' => $externalId,
                    'payment_verified_at' => $now,
                    'paid_at' => $now,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'from_status' => $order->status,
                    'to_status' => $order->status,
                    'note' => "ABA PayWay payment confirmed server-side (Ref: {$externalId}).",
                    'created_at' => $now,
                ]);
            }

            return true;
        });

        if ($transitioned) {
            $order = Order::withoutGlobalScopes()->find($transaction->order_id);
            if ($order) {
                app(BusinessNotificationService::class)->notifyOrderEvent($order, 'order.payment_received');
            }
        }

        return $transitioned;
    }

    private function generateUniqueReference(Order $order): string
    {
        return 'ABA' . gmdate('YmdHis') . strtoupper(Str::random(6));
    }

    private function endpoint(string $environment, string $path): string
    {
        $base = $environment === 'production'
            ? (string) config('services.aba_payway.production_url', 'https://checkout.payway.com.kh')
            : (string) config('services.aba_payway.sandbox_url', 'https://checkout-sandbox.payway.com.kh');

        return rtrim($base, '/') . $path;
    }

    private function httpClient(): PendingRequest
    {
        $client = Http::timeout(12)->acceptJson();

        if (! (bool) config('services.aba_payway.verify_ssl', true)) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    private function sanitizeError(string $message, ?string $apiKey = null): string
    {
        if ($apiKey !== null && $apiKey !== '') {
            return str_replace($apiKey, '[REDACTED_KEY]', $message);
        }

        return $message;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redactSecretsFromArray(array $payload, string $apiKey): array
    {
        unset($payload['hash'], $payload['api_key']);
        array_walk_recursive($payload, function (&$val) use ($apiKey): void {
            if (is_string($val) && $apiKey !== '') {
                $val = str_replace($apiKey, '[REDACTED_KEY]', $val);
            }
        });

        return $payload;
    }
}
