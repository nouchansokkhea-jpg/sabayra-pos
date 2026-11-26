# Payment & Checkout Security Controls

## 1. Credential Protection & Redaction

- `business_payment_configs.aba_api_key` uses Laravel's `encrypted` cast and is listed in `$hidden` so secrets are encrypted in PostgreSQL and excluded from serialization.
- `platform_settings` encrypts `payments.bakong_api_token` using `Crypt::encryptString()`.
- Merchant secrets are never rendered in Blade views, JavaScript payloads, public menu responses, or exception traces (`AbaPayWayService::sanitizeError()` & `redactSecretsFromArray()`).

## 2. Server-Authoritative Pricing & Payment State

- All prices, variants, add-ons, discounts, service charges, and taxes are computed server-side by `OrderPricingCalculator` on both customer checkout and POS checkout.
- Customer checkout rejects disabled, unverified, or channel-ineligible payment methods (`PaymentGatewayVisibilityService::isMethodAllowedForCheckout()`).
- Bank transfers start `unpaid`, move to `pending_verification` upon proof upload, and become `paid` only via authenticated merchant staff review.
- Cash on Delivery orders start `pending_collection` and become `paid` only when staff records collection (`collectCod`).
- ABA PayWay & Bakong KHQR orders become `paid` only after verified server-side gateway responses (`X-PayWay-HMAC-SHA512` webhook, `check-transaction-2`, or `check_transaction_by_md5`) with amount/currency matching and duplicate-replay protection.

## 3. File Upload Hardening & Private Storage

- Payment proofs (`PaymentProofService::storeCustomerProof()`) and static QR uploads (`PaymentSettingsController::storeStaticQrImage()`) restrict file size to 5MB and validate true image headers via `\finfo(FILEINFO_MIME_TYPE)` and `getimagesizefromstring()` (`image/png`, `image/jpeg`, `image/webp`), blocking disguised PHP/text/executable uploads.
- Customer payment proofs are written to the private `local` disk (`payment-proofs/{business_id}/{order_id}/...`) using UUID filenames AND backed up in `payment_proofs.encoded_content` (`$hidden` from serialization) so proofs survive ephemeral container restarts on cloud PaaS without ever using a public bucket. Proofs stream exclusively via tenant-authorized `OrderManagementController@showProof` with `X-Content-Type-Options: nosniff` and `Cache-Control: private, no-store`.
- Public customer proof uploads and payment verification endpoints require a constant-time HMAC order token (`hash_hmac('sha256', ...)` + `hash_equals()`) and route throttling.

## 4. Multi-Tenant Isolation & Audit Trail

- All business payment configs, orders, transactions, and proofs are scoped via `business_id` and `BelongsToTenant`; cross-tenant staff requests to order actions or private proofs are blocked (`403`/`404`).
- Critical settings updates, verification attempts, ABA connection tests, proof approvals/rejections, COD collections, and platform switches emit structured `SecurityLog` and `OrderStatusHistory` records.
