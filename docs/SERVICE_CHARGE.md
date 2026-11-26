# Service Charge Configuration & Accounting Rule

## Business Settings

Configured under **Business Settings → Payment & Tax → Service Charge**:

- `service_charge_enabled` (`boolean`): Enables or disables service fees for all new customer and POS orders.
- `service_charge_rate` (`decimal 0.00–100.00`): Percentage applied when enabled.

## Unified Calculation Order

To guarantee consistency across Customer Checkout (`OrderCheckoutController`), Touch POS (`PosController`), Order Details (`orders/show.blade.php`), 80mm Receipts (`orders/receipt.blade.php`), and Financial Reports (`ReportController`), SABAYRA enforces one explicit pricing order via `App\Services\Pricing\OrderPricingCalculator`:

1. `gross_subtotal` = sum of item unit prices (including variants and add-ons) multiplied by quantity.
2. `discount_amount` = `min(discount, gross_subtotal)`.
3. `net_subtotal` = `gross_subtotal - discount_amount`.
4. `service_charge_amount`:
   - If `!service_charge_enabled` or `service_charge_rate <= 0`: `0.00`.
   - If enabled: `round(net_subtotal * (service_charge_rate / 100), 2)`.
5. `tax_amount` is calculated on `net_subtotal` (pre-service-charge base) using either the Exclusive or Inclusive formula.
6. `total_amount` = `net_subtotal + service_charge_amount + (tax_inclusive ? 0 : tax_amount)`.

Each order saves `service_charge_enabled`, `service_charge_rate`, and `service_charge_amount` on `orders`, and `ReportController` aggregates total service charges separately alongside Tax Collected and Net Revenue (Ex-Tax).
