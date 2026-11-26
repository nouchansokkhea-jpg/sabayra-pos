<?php

namespace App\Services\Payment;

use App\Models\Business;
use App\Models\BusinessPaymentConfig;
use App\Models\Order;
use App\Models\PlatformSetting;

class PaymentGatewayVisibilityService
{
    /**
     * Resolve all payment methods with availability, eligibility rules, and safe public display info.
     *
     * @return array<string, array<string, mixed>>
     */
    public function resolveCheckoutMethods(Business $business, ?string $orderType = null): array
    {
        $config = BusinessPaymentConfig::resolveForBusiness($business);
        $platformBakongEnabled = PlatformSetting::isBakongEnabled();
        $platformAbaEnabled = PlatformSetting::isAbaPayWayEnabled();

        $bankAvailable = $config->bank_transfer_enabled && $config->hasBankTransferDetails();
        $staticQrAvailable = $config->static_qr_enabled && $config->hasStaticQrConfigured();
        $codAvailable = $config->cod_enabled && ($orderType === null || $orderType === 'delivery');
        $counterCashAvailable = $config->cash_enabled && ($orderType === null || $orderType !== 'delivery');

        $abaApiReady = $config->hasAbaMerchantApiCredentials() && $config->aba_test_status !== 'failed';
        $abaLinkReady = $config->hasValidAbaPaywayLink();
        $abaAvailable = $platformAbaEnabled && $config->aba_payway_enabled && ($abaApiReady || $abaLinkReady);

        $bakongAvailable = $platformBakongEnabled
            && $config->bakong_enabled
            && filled($config->bakong_account_id)
            && $config->bakong_verified;

        return [
            'bank_transfer' => [
                'code' => 'bank_transfer',
                'label' => 'Bank Transfer',
                'description' => 'Pay directly to our bank account and upload your transfer receipt.',
                'enabled' => $bankAvailable,
                'order_types' => ['dine_in', 'takeaway', 'delivery'],
                'bank_name' => $config->bank_name,
                'bank_account_holder' => $config->bank_account_holder,
                'bank_account_number' => $config->bank_account_number,
                'bank_instructions' => $config->bank_instructions,
                'bank_reference_instructions' => $config->bank_reference_instructions,
            ],
            'static_qr' => [
                'code' => 'static_qr',
                'label' => $config->static_qr_label ?: 'QR Payment',
                'description' => 'Scan our static QR code with your banking app and upload proof if required.',
                'enabled' => $staticQrAvailable,
                'order_types' => ['dine_in', 'takeaway', 'delivery'],
                'qr_image_url' => $config->static_qr_image_path,
                'instructions' => $config->static_qr_instructions,
                'require_proof' => (bool) $config->static_qr_require_proof,
            ],
            'cod' => [
                'code' => 'cod',
                'label' => 'Cash on Delivery',
                'description' => 'Pay cash in USD or KHR when your delivery arrives.',
                'enabled' => $codAvailable,
                'order_types' => ['delivery'],
                'instructions' => $config->cod_instructions,
            ],
            'cash' => [
                'code' => 'cash',
                'label' => 'Cash at Counter',
                'description' => 'Pay cash at the counter when picking up or dining in.',
                'enabled' => $counterCashAvailable,
                'order_types' => ['dine_in', 'takeaway'],
            ],
            'aba_payway' => [
                'code' => 'aba_payway',
                'label' => 'Pay with ABA',
                'description' => $abaApiReady
                    ? 'Generate a dynamic ABA PayWay KHQR or checkout payment verified by ABA PayWay.'
                    : 'Open the merchant ABA PayWay link and submit transfer confirmation.',
                'enabled' => $abaAvailable,
                'order_types' => ['dine_in', 'takeaway', 'delivery'],
                'mode' => $abaApiReady ? 'api' : ($abaLinkReady ? 'link' : 'unconfigured'),
                'payway_link' => $abaLinkReady ? $config->aba_payway_link : null,
                'logo' => asset('images/payments/aba.png'),
            ],
            'khqr' => [
                'code' => 'khqr',
                'label' => 'Bakong KHQR',
                'description' => 'Instant National Bank of Cambodia KHQR verified through Bakong Open API.',
                'enabled' => $bakongAvailable,
                'platform_enabled' => $platformBakongEnabled,
                'order_types' => ['dine_in', 'takeaway', 'delivery'],
                'merchant_name' => $config->bakong_merchant_name ?: $business->name,
                'logo' => asset('images/payments/bakong.png'),
            ],
        ];
    }

    /**
     * Determine whether the submitted customer checkout payment method is permitted for this business and order type.
     */
    public function isMethodAllowedForCheckout(Business $business, string $paymentMethod, string $orderType): bool
    {
        $methods = $this->resolveCheckoutMethods($business, $orderType);
        $normalized = $this->normalizeCheckoutMethod($business, $paymentMethod, $orderType);

        if ($normalized === null || ! isset($methods[$normalized])) {
            return false;
        }

        return (bool) $methods[$normalized]['enabled'];
    }

    /**
     * Normalize legacy payment codes (e.g. 'bakong_khqr' -> 'khqr', delivery 'cash' -> 'cod').
     */
    public function normalizeCheckoutMethod(Business $business, string $paymentMethod, string $orderType): ?string
    {
        $config = BusinessPaymentConfig::resolveForBusiness($business);

        return match ($paymentMethod) {
            'bank_transfer', 'static_qr', 'cod', 'aba_payway' => $paymentMethod,
            'khqr', 'bakong_khqr' => 'khqr',
            'cash' => $orderType === 'delivery'
                ? ($config->cod_enabled ? 'cod' : null)
                : ($config->cash_enabled ? 'cash' : null),
            default => null,
        };
    }

    /**
     * Generate an order-scoped HMAC token authorizing customer proof upload and status checks on tracking pages.
     */
    public function generateOrderProofToken(Order $order): string
    {
        return hash_hmac('sha256', "{$order->id}|{$order->order_number}|{$order->business_id}", (string) config('app.key'));
    }

    /**
     * Verify the order-scoped customer proof/payment action token in constant time.
     */
    public function verifyOrderProofToken(Order $order, ?string $token): bool
    {
        if (! is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($this->generateOrderProofToken($order), $token);
    }
}
