# Bank Transfer Payment Workflow

## Merchant Configuration

Businesses enabling Bank Transfer (`bank_transfer_enabled = true`) must provide:

- `bank_name`
- `bank_account_holder`
- `bank_account_number`
- Optional `bank_instructions`
- Optional `bank_reference_instructions`

Validation in `PaymentSettingsController::update()` blocks enabling Bank Transfer without complete banking details.

## Customer Checkout & Proof Verification Workflow

1. Customer selects **Bank Transfer** on storefront checkout (`POST /{business_slug}/checkout`).
2. Order is created with `payment_method = 'bank_transfer'` and `payment_status = 'unpaid'` (plus an HMAC-signed `proof_token` for the order tracking URL).
3. `resources/views/public/order-status.blade.php` displays the merchant's bank account details, instructions, and proof upload form.
4. Customer uploads a receipt screenshot (`POST /{business_slug}/order/{order_number}/proof`) handled by `CustomerOrderPaymentController@uploadProof` and `PaymentProofService@storeCustomerProof`:
   - File must pass extension, MIME (`finfo`), and `getimagesizefromstring()` validation (`PNG`, `JPG`/`JPEG`, `WebP`, max `5MB`).
   - Binary content is saved to the private `local` disk (`payment-proofs/{business_id}/{order_id}/{uuid}.{ext}`) and persisted in `payment_proofs.encoded_content` (`$hidden`) so proof images remain viewable across ephemeral Docker container redeployments without ever exposing a public URL.
   - `PaymentProof` (`status = 'pending'`) and `PaymentTransaction` (`status = 'pending_verification'`) records are created.
   - Order transitions to `payment_status = 'pending_verification'` and **is never automatically marked `paid`**.
5. Staff reviews proofs in **Orders → Show** (`business.orders.show`) where images stream strictly via authenticated tenant-checked `GET /app/orders/{order}/payment-proof/{proof}` (`OrderManagementController@showProof`).
6. Authorized staff either approves (`POST /app/orders/{order}/payment/approve` → `paid`, sets `payment_verified_at` & `payment_verified_by`) or rejects (`POST /app/orders/{order}/payment/reject` with required reason → `rejected`). Both actions record `OrderStatusHistory` and `SecurityLog` entries.
