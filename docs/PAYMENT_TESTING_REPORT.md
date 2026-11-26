# SABAYRA Payment & Tax Upgrade — Testing & Verification Report

## Automated Test Suite Results

Executed `php artisan test` across the complete PHPUnit test suite after all implementation fixes and migrations:

- **Command**: `php artisan test`
- **Tests**: `150`
- **Passed**: `150`
- **Failed**: `0`
- **Assertions**: `965`
- **Duration**: `70,051 ms`

## PostgreSQL Schema Migration Verification

Executed `php artisan migrate --force` against the configured local PostgreSQL database (`menuhub_db`):

- `2026_11_26_000001_upgrade_payment_tax_and_orders_tables` — `DONE` (`368.91ms`)
- `2026_11_26_000002_create_payment_transactions_and_proofs_tables` — `DONE` (`112.37ms`)

## Verified Coverage (`tests/Feature/PaymentAndTaxUpgradeTest.php`)

| Area | Verified Scenarios |
| --- | --- |
| **Tax Exclusive & Inclusive** | `$10.00` exclusive `@ 10% VAT` (`$1.00` tax, `$11.00` total); `$10.00` inclusive `@ 10% GST` (`$0.91` tax component, `$10.00` order base); thermal receipt display |
| **Historical Order Immutability** | Updating business tax mode (`VAT -> GST`, `exclusive -> inclusive`) leaves previously placed orders unchanged (`tax_name`, `tax_rate`, `tax_inclusive`, `total_amount`) |
| **Service Charge & POS Parity** | Enabled service charge (`5% -> $0.50`) applied on pre-tax subtotal across checkout, orders, and receipts; disabled toggle with non-zero rate (`12%`) charges `$0.00` on POS |
| **Bank Transfer Workflow** | Mandatory banking detail validation; disguised `.png` containing PHP rejected (`422`); invalid HMAC token blocked (`403`); valid proof saved to private `local` disk (`pending_verification`, never auto-`paid`); `orders.show` renders uploaded proof, reviewer (`reviewedBy`), and missing-file fallback cleanly; staff approve/reject audit trail |
| **Static QR & Cash on Delivery** | Static QR requires image upload before enabling; storefront displays custom QR label & COD for delivery only; `cod` rejected on `dine_in` (`422`); delivery `cod` starts `pending_collection` and settles via staff collection (`collect-cod`) |
| **ABA PayWay Integration** | Untrusted non-`payway.com.kh` URL rejected; `aba_api_key` stored encrypted in DB and excluded from `toArray()` / public HTML; `check-transaction-2` connection test returns `valid`; `/generate-qr` creates `PaymentTransaction`; tampered webhook signature rejected (`403`); valid `X-PayWay-HMAC-SHA512` webhook marks order `paid` with idempotent replay |
| **Bakong KHQR & Platform Toggle** | Format-valid `bakong_account_id` without NBC Open API token stays unverified (`credentials_missing`) and blocked at checkout (`422`); verified account passes `/v1/check_bakong_account`; Super Admin platform off switch shows required notice, preserves merchant setting, and blocks checkout (`422`); re-enabling restores checkout; tracking page renders local `<svg>` QR without `api.qrserver.com`; `/v1/check_transaction_by_md5` settles order atomically |
| **Multi-Tenant Security** | Cross-tenant `showProof` and `approvePayment` requests rejected (`403`) |
| **Financial Reports & CSV** | Period summary displays `Tax Collected`, `Net Revenue (Ex-Tax)`, and exports `Service Charge (USD)` & `Tax Name` columns in UTF-8 CSV |

All 144 existing feature and unit tests across authentication, multi-tenant isolation, settings, POS inventory, recipes, ordering, printers, Telegram notifications, and Super Admin controls also continued to pass cleanly (`150/150`).
