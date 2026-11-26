# Bakong KHQR Integration (NBC Open API)

## Platform-Wide Control & Business Settings

Bakong KHQR availability is controlled at two levels:

1. **Platform Level (`platform_settings`)**:
   - Super Admin manages `payments.bakong_enabled`, `payments.bakong_api_base_url` (`https://api-bakong.nbc.gov.kh`), and encrypted `payments.bakong_api_token` (`BAKONG_OPEN_API_TOKEN`) under **Admin → Settings**.
   - Disabling Bakong platform-wide immediately pauses Bakong at storefront checkout and displays a notice in Business Settings while **preserving** every business's saved `bakong_enabled`, `bakong_account_id`, and verification state. Re-enabling automatically restores checkout eligibility for verified businesses.
2. **Business Level (`business_payment_configs`)**:
   - `bakong_enabled`, `bakong_account_id` (`name@bank`), `bakong_merchant_name`, `bakong_verified`, `bakong_verified_at`, `bakong_verification_status`, and `bakong_verification_message`.

## Genuine NBC Account Verification

Implemented in `App\Services\Payment\BakongKhqrService::verifyAccount()`:

- Regex validation (`name@bank`) alone **never** verifies an account.
- Editing `bakong_account_id` immediately resets `bakong_verified = false`.
- Clicking **Verify** (`POST /app/settings/payment/verify-bakong`) requires Bakong to be enabled platform-wide AND a valid NBC Bakong Open API bearer token (`PlatformSetting::resolveBakongApiToken()`).
- Calls `POST {baseUrl}/v1/check_bakong_account` with `{"accountId": "..."}`; marks `bakong_verified = true` only when `responseCode === 0`.

## Dynamic KHQR Generation & MD5 Verification

1. At checkout (`khqr`), `BakongKhqrService::createOrderTransaction()` checks platform switch + business toggle + verified status, builds a transaction-specific EMVCo TLV string with unique bill reference and CRC16 via `KHQRService::generate()`, computes its `md5` hash, stores `PaymentTransaction`, and renders local crisp SVG QR code on `public/order-status.blade.php` (`QrCode::format('svg')`).
2. `verifyTransactionStatus()` (`POST /{slug}/order/{number}/verify-payment`) calls NBC `POST {baseUrl}/v1/check_transaction_by_md5`, verifies `responseCode === 0`, validates currency & amount (`abs(txAmount - expected) <= 0.01`), blocks duplicate NBC transaction hash replays across orders, and atomically marks the order `paid`.
