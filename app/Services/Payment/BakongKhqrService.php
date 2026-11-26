<?php

namespace App\Services\Payment;

use App\Models\Business;
use App\Models\BusinessPaymentConfig;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Services\Notifications\BusinessNotificationService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BakongKhqrService
{
    /**
     * Validate a Bakong Account ID using the official NBC Bakong Open API /v1/check_bakong_account endpoint.
     * Never marks an account verified merely by regex or format matching.
     *
     * @return array{ok: bool, verified: bool, status: string, message: string}
     */
    public function verifyAccount(BusinessPaymentConfig $config, string $accountId): array
    {
        $cleanAccountId = strtolower(trim($accountId));

        if (! preg_match('/^[a-z0-9._-]{2,64}@[a-z0-9.-]{2,40}$/', $cleanAccountId)) {
            $message = 'Invalid Bakong Account ID format. Expected format: name@bank (e.g. sokpheng@aclb).';
            $config->forceFill([
                'bakong_account_id' => $cleanAccountId !== '' ? $cleanAccountId : null,
                'bakong_verified' => false,
                'bakong_verified_at' => null,
                'bakong_verification_status' => 'invalid_format',
                'bakong_verification_message' => $message,
            ])->save();

            return ['ok' => false, 'verified' => false, 'status' => 'invalid_format', 'message' => $message];
        }

        if (! PlatformSetting::isBakongEnabled()) {
            $message = 'Bakong checkout is currently disabled platform-wide by Super Admin. Your Account ID was saved as Not Verified until Bakong is re-enabled and verified.';
            $config->forceFill([
                'bakong_account_id' => $cleanAccountId,
                'bakong_verified' => false,
                'bakong_verified_at' => null,
                'bakong_verification_status' => 'platform_disabled',
                'bakong_verification_message' => Str::limit($message, 490),
            ])->save();

            return ['ok' => false, 'verified' => false, 'status' => 'platform_disabled', 'message' => $message];
        }

        $apiToken = PlatformSetting::resolveBakongApiToken();
        if ($apiToken === null) {
            $message = 'Not Verified: NBC Bakong Open API access token (BAKONG_OPEN_API_TOKEN) is missing in Platform Settings. Configure an official NBC Bakong Open API token before verifying merchant accounts.';
            $config->forceFill([
                'bakong_account_id' => $cleanAccountId,
                'bakong_verified' => false,
                'bakong_verified_at' => null,
                'bakong_verification_status' => 'credentials_missing',
                'bakong_verification_message' => Str::limit($message, 490),
            ])->save();

            return ['ok' => false, 'verified' => false, 'status' => 'credentials_missing', 'message' => $message];
        }

        $baseUrl = PlatformSetting::resolveBakongApiBaseUrl();

        try {
            $response = $this->httpClient($apiToken)->post("{$baseUrl}/v1/check_bakong_account", [
                'accountId' => $cleanAccountId,
            ]);

            $json = $response->json();

            if ($response->successful() && is_array($json) && (int) ($json['responseCode'] ?? -1) === 0) {
                $message = "Verified with NBC Bakong Open API ({$cleanAccountId}).";
                $config->forceFill([
                    'bakong_account_id' => $cleanAccountId,
                    'bakong_verified' => true,
                    'bakong_verified_at' => now(),
                    'bakong_verification_status' => 'verified',
                    'bakong_verification_message' => $message,
                ])->save();

                return ['ok' => true, 'verified' => true, 'status' => 'verified', 'message' => $message];
            }

            $apiMsg = is_array($json) ? (string) ($json['responseMessage'] ?? 'Account could not be verified.') : ('NBC Bakong API returned HTTP ' . $response->status());
            $safeMsg = "Not Verified: {$apiMsg}";

            $config->forceFill([
                'bakong_account_id' => $cleanAccountId,
                'bakong_verified' => false,
                'bakong_verified_at' => null,
                'bakong_verification_status' => 'failed',
                'bakong_verification_message' => Str::limit($safeMsg, 490),
            ])->save();

            return ['ok' => false, 'verified' => false, 'status' => 'failed', 'message' => $safeMsg];
        } catch (\Throwable $e) {
            $safeMsg = 'Not Verified: Could not reach NBC Bakong Open API (' . Str::limit($e->getMessage(), 200) . ').';
            $config->forceFill([
                'bakong_account_id' => $cleanAccountId,
                'bakong_verified' => false,
                'bakong_verified_at' => null,
                'bakong_verification_status' => 'error',
                'bakong_verification_message' => Str::limit($safeMsg, 490),
            ])->save();

            return ['ok' => false, 'verified' => false, 'status' => 'error', 'message' => $safeMsg];
        }
    }

    /**
     * Create a Bakong EMVCo KHQR transaction with 32-char MD5 tracking hash and local SVG QR image.
     *
     * @return array{ok: bool, transaction?: PaymentTransaction, qr_svg?: string, error?: string}
     */
    public function createOrderTransaction(Business $business, Order $order, BusinessPaymentConfig $config): array
    {
        if (! PlatformSetting::isBakongEnabled()) {
            return ['ok' => false, 'error' => 'Bakong KHQR checkout is currently turned off platform-wide.'];
        }

        if (! $config->bakong_enabled || empty($config->bakong_account_id) || ! $config->bakong_verified) {
            return ['ok' => false, 'error' => 'Bakong KHQR is not verified or enabled for this business.'];
        }

        $currency = strtoupper((string) ($order->currency ?: 'USD')) === 'KHR' ? 'KHR' : 'USD';
        $amount = $currency === 'KHR' ? (float) $order->total_khr : (float) $order->total_amount;
        $reference = 'BK' . gmdate('YmdHis') . strtoupper(Str::random(6));

        $payload = KHQRService::generate($business, $amount, $currency, $order->order_number);
        $md5 = md5($payload);
        $qrSvg = (string) QrCode::format('svg')->size(220)->margin(1)->generate($payload);

        $tx = PaymentTransaction::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'order_id' => $order->id,
            'gateway' => 'bakong_khqr',
            'reference_number' => $reference,
            'khqr_md5' => $md5,
            'khqr_payload' => $payload,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'idempotency_key' => "bakong:{$order->id}:{$md5}",
            'expires_at' => now()->addMinutes(15),
        ]);

        $order->forceFill(['payment_reference' => $reference])->save();

        return [
            'ok' => true,
            'transaction' => $tx,
            'qr_svg' => $qrSvg,
        ];
    }

    /**
     * Check live Bakong KHQR settlement via NBC Open API POST /v1/check_transaction_by_md5.
     *
     * @return array{ok: bool, paid: bool, status: string, message: string}
     */
    public function verifyTransactionStatus(PaymentTransaction $transaction): array
    {
        if ($transaction->status === 'paid') {
            return ['ok' => true, 'paid' => true, 'status' => 'paid', 'message' => 'Bakong KHQR payment already confirmed.'];
        }

        if ($transaction->isExpired() && $transaction->status === 'pending') {
            $transaction->update(['status' => 'expired']);

            return ['ok' => true, 'paid' => false, 'status' => 'expired', 'message' => 'This Bakong KHQR session has expired.'];
        }

        if (! PlatformSetting::isBakongEnabled()) {
            return ['ok' => false, 'paid' => false, 'status' => $transaction->status, 'message' => 'Bakong KHQR is currently disabled platform-wide.'];
        }

        if (empty($transaction->khqr_md5)) {
            return ['ok' => false, 'paid' => false, 'status' => 'failed', 'message' => 'Missing KHQR MD5 fingerprint.'];
        }

        $apiToken = PlatformSetting::resolveBakongApiToken();
        if ($apiToken === null) {
            return [
                'ok' => false,
                'paid' => false,
                'status' => $transaction->status,
                'message' => 'NBC Bakong Open API access token is missing; automatic verification cannot proceed.',
            ];
        }

        $baseUrl = PlatformSetting::resolveBakongApiBaseUrl();

        try {
            $response = $this->httpClient($apiToken)->post("{$baseUrl}/v1/check_transaction_by_md5", [
                'md5' => $transaction->khqr_md5,
            ]);

            $json = $response->json();
            $responseCode = (int) ($json['responseCode'] ?? -1);
            $errorCode = $json['errorCode'] ?? null;

            if ($response->successful() && $responseCode === 0 && is_array($json['data'] ?? null)) {
                $txData = $json['data'];
                $txHash = (string) ($txData['hash'] ?? $transaction->khqr_md5);
                $txCurrency = strtoupper((string) ($txData['currency'] ?? $transaction->currency));
                $txAmount = (float) ($txData['amount'] ?? 0);

                if ($txCurrency !== strtoupper((string) $transaction->currency) || abs($txAmount - (float) $transaction->amount) > 0.01) {
                    return [
                        'ok' => false,
                        'paid' => false,
                        'status' => 'failed',
                        'message' => 'Bakong transaction amount or currency mismatch.',
                    ];
                }

                // Prevent replay / duplicate hash confirmation across different orders
                $duplicateExists = PaymentTransaction::withoutGlobalScopes()
                    ->where('gateway', 'bakong_khqr')
                    ->where('external_transaction_id', $txHash)
                    ->where('id', '!=', $transaction->id)
                    ->where('status', 'paid')
                    ->exists();

                if ($duplicateExists) {
                    return [
                        'ok' => false,
                        'paid' => false,
                        'status' => 'failed',
                        'message' => 'Duplicate Bakong transaction hash rejected.',
                    ];
                }

                $this->markTransactionPaidAtomically($transaction, $txHash, $json);

                return ['ok' => true, 'paid' => true, 'status' => 'paid', 'message' => 'Bakong KHQR payment verified and confirmed.'];
            }

            if ((int) $errorCode === 3) {
                $transaction->update(['status' => 'failed', 'gateway_payload' => is_array($json) ? $json : null]);
                $transaction->order()->where('payment_status', '!=', 'paid')->update(['payment_status' => 'failed']);

                return ['ok' => true, 'paid' => false, 'status' => 'failed', 'message' => 'Bakong reported transaction failure.'];
            }

            return [
                'ok' => true,
                'paid' => false,
                'status' => 'pending',
                'message' => (string) ($json['responseMessage'] ?? 'Awaiting Bakong transfer confirmation.'),
            ];
        } catch (\Throwable $e) {
            Log::warning('Bakong check_transaction_by_md5 failed', ['tx_id' => $transaction->id, 'error' => $e->getMessage()]);

            return [
                'ok' => false,
                'paid' => false,
                'status' => $transaction->status,
                'message' => 'Could not reach NBC Bakong Open API right now.',
            ];
        }
    }

    /**
     * Render a local SVG QR code for any EMVCo KHQR payload string.
     */
    public function renderQrSvg(string $khqrPayload): string
    {
        return (string) QrCode::format('svg')->size(220)->margin(1)->generate($khqrPayload);
    }

    private function markTransactionPaidAtomically(PaymentTransaction $transaction, string $externalHash, array $gatewayPayload): bool
    {
        $transitioned = DB::transaction(function () use ($transaction, $externalHash, $gatewayPayload): bool {
            $locked = PaymentTransaction::withoutGlobalScopes()->whereKey($transaction->id)->lockForUpdate()->first();
            if (! $locked || $locked->status === 'paid') {
                return false;
            }

            $now = now();
            $locked->update([
                'status' => 'paid',
                'external_transaction_id' => $externalHash,
                'verified_at' => $now,
                'gateway_payload' => $gatewayPayload,
            ]);

            $order = Order::withoutGlobalScopes()->whereKey($locked->order_id)->lockForUpdate()->first();
            if ($order && $order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'khqr',
                    'payment_reference' => $locked->reference_number,
                    'gateway_transaction_id' => $externalHash,
                    'payment_verified_at' => $now,
                    'paid_at' => $now,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'from_status' => $order->status,
                    'to_status' => $order->status,
                    'note' => "Bakong KHQR verified via NBC Open API (Hash: {$externalHash}).",
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

    private function httpClient(string $apiToken): PendingRequest
    {
        $client = Http::withToken($apiToken)
            ->timeout(12)
            ->acceptJson();

        if (! (bool) config('services.bakong.verify_ssl', true)) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }
}
