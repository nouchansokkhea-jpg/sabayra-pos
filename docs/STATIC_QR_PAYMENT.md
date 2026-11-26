# Static QR Payment Specification

## Overview

The **QR Payment** option allows merchants to display a static payment QR image (e.g., bank counter QR, Wing QR, or custom printed KHQR image) for manual payment confirmation. It is intentionally separated from verified dynamic payment gateways and never auto-settles orders.

## Settings Fields

Stored in `business_payment_configs`:

- `static_qr_enabled`: Enable/Disable toggle (requires both label and uploaded QR image).
- `static_qr_label`: Customer-facing checkout label (default `QR Payment`, e.g., `KHQR Payment`, `Bank QR Payment`).
- `static_qr_image_path`: Persistent uploaded QR asset path (stored via `SupabaseStorageService` when reachable or `public` disk fallback).
- `static_qr_instructions`: Optional scan instructions shown to customers.
- `static_qr_require_proof`: Controls proof upload prompt after ordering.
- Supports previewing current QR image, replacing with a new image (cleaning up prior local files), and removing via `remove_static_qr_image`.

## Upload Security

Uploaded QR files in `PaymentSettingsController::storeStaticQrImage()` are validated against real file bytes using `\finfo(FILEINFO_MIME_TYPE)` and `getimagesizefromstring()` (`image/png`, `image/jpeg`, `image/webp`, max `5MB`) before persistence.

## Customer Checkout & Settlement

1. Enabled Static QR appears in `public/menu.blade.php` using its configured `static_qr_label`.
2. Placing a `static_qr` order creates an `unpaid` order and renders the uploaded static QR on `public/order-status.blade.php`.
3. Customers upload payment proof via the HMAC-protected tracking URL (`pending_verification`), and staff verify settlement in `OrderManagementController`.
