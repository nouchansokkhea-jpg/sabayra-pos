# ABA PayWay Integration (Official Merchant API & Link Modes)

## Supported Configuration Modes

Managed in `business_payment_configs` and `App\Services\Payment\AbaPayWayService`:

1. **Official Merchant API Mode (Dynamic KHQR / Check Transaction / Webhook)**:
   - Uses `aba_merchant_id`, encrypted `aba_api_key` (`casts: ['aba_api_key' => 'encrypted']`, `$hidden = ['aba_api_key']`), and `aba_environment` (`sandbox` or `production`).
   - Sandbox base URL: `config('services.aba_payway.sandbox_url')` (`https://checkout-sandbox.payway.com.kh`)
   - Production base URL: `config('services.aba_payway.production_url')` (`https://checkout.payway.com.kh`)
2. **Official ABA PayWay Link Mode**:
   - `aba_payway_link` must be a valid `https://` URL hosted on `payway.com.kh` or a `*.payway.com.kh` subdomain; arbitrary external URLs are rejected.
   - Link-only setups are clearly identified as `link_only` during Test Connection and require manual proof verification rather than pretending to support dynamic KHQR API settlement.

## Payment & Server Verification Workflow

1. **Test Connection (`POST /app/settings/payment/test-aba`)**:
   - Signs an API request against `/api/payment-gateway/v1/payments/check-transaction-2` using canonical ABA PayWay Base64 `HMAC-SHA512`.
   - Verifies gateway reachability and merchant credential authenticity before marking `aba_test_status = 'connected'`.
2. **Checkout Transaction Creation**:
   - `AbaPayWayService::createOrderTransaction()` calls `/api/payment-gateway/v1/payments/generate-qr` with canonical 19-field HMAC-SHA512 signature (`req_time + merchant_id + tran_id + ...`), unique `reference_number`, Base64 callback URL (`POST /webhooks/aba-payway/{reference}`), order amount, and lifetime.
   - Creates a `pending` `PaymentTransaction` record; order stays `unpaid`.
3. **Server Verification & Webhook Settlement**:
   - **Webhook**: `POST /webhooks/aba-payway/{reference}` (CSRF-exempt, throttled at 120/min) validates `X-PayWay-HMAC-SHA512` using constant-time `hash_equals()`, verifies transaction reference & amount, handles duplicate deliveries idempotently, and atomically marks transaction & order `paid` inside a row lock.
   - **Active Poll Check**: `POST /{business_slug}/order/{order_number}/verify-payment` calls `/api/payment-gateway/v1/payments/check-transaction-2` using server-side credentials.
   - Customer browser redirects are never accepted as proof of payment, and `aba_api_key` is stripped from responses, views, and sanitized error logs.
