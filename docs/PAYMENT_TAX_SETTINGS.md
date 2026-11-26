# SABAYRA Payment & Tax Settings Overview

## Architecture & Persistence

The SABAYRA Payment & Tax module is managed per tenant business under **Business Settings → Payment & Tax** (`GET /app/settings?section=payment`), persisted across PostgreSQL/SQLite tables:

- `businesses`: stores core pricing & tax defaults (`default_currency`, `exchange_rate_khr`, `tax_name`, `tax_percent`, `tax_inclusive`, `service_charge_enabled`, `service_charge_rate`).
- `business_payment_configs`: dedicated per-business payment gateway and manual payment configuration table (`bank_transfer_*`, `static_qr_*`, `cash_enabled`, `cod_*`, `aba_*`, `bakong_*`).
- `platform_settings`: platform-wide Super Admin configuration (`payments.bakong_enabled`, `payments.aba_payway_enabled`, `payments.bakong_api_base_url`, encrypted `payments.bakong_api_token`).
- `payment_transactions`: live gateway and payment attempt records linked to `orders`.
- `payment_proofs`: private customer proof uploads and staff review decisions (`pending`, `approved`, `rejected`).
- `orders`: stores an immutable snapshot of applied `tax_name`, `tax_rate`, `tax_inclusive`, `service_charge_enabled`, `service_charge_rate`, `service_charge_amount`, `payment_reference`, `gateway_transaction_id`, `payment_verified_at`, `payment_verified_by`, `payment_collected_at`, `payment_collected_by`, and `paid_at`.

## UI Organization

The settings view (`resources/views/business/settings/partials/payment-tax.blade.php`) is structured into:

1. **Currency & Receipt Preferences** (`default_currency`, `exchange_rate_khr`, `receipt_footer`)
2. **Tax Settings** (`tax_name`, `tax_percent`, `tax_inclusive` + interactive preview)
3. **Service Charge** (`service_charge_enabled`, `service_charge_rate`)
4. **Payment Methods** (Bank Transfer, QR Payment, Cash on Delivery & In-Store Cash, ABA PayWay, and Bakong KHQR)

## Primary Routes & Controllers

- `App\Http\Controllers\Business\PaymentSettingsController@update` (`PUT /app/settings/payment`, named `business.settings.payment.update`; backward-compatible delegate from `SettingController@updatePaymentTax`)
- `App\Http\Controllers\Business\PaymentSettingsController@verifyBakong` (`POST /app/settings/payment/verify-bakong`)
- `App\Http\Controllers\Business\PaymentSettingsController@testAbaConnection` (`POST /app/settings/payment/test-aba`)
- `App\Http\Controllers\Admin\SettingController@update` (`POST /admin/settings`)
- `App\Services\Payment\PaymentGatewayVisibilityService`: central source of truth deciding which payment methods appear on public storefront menus and are accepted by `OrderCheckoutController@checkout`.
