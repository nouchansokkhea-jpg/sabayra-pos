# Tax Calculations Specification

## Supported Modes

Every business configures:

- **Tax Name**: Displayed on checkout, orders, POS, and receipts (default `VAT`, supports `GST`, `Sales Tax`, etc.).
- **Tax Rate (%)**: Decimal percentage between `0.00` and `100.00`. Setting `0` disables tax calculation.
- **Tax Inclusive**: Toggle controlling whether menu prices already include tax.

## Authoritative Backend Formulas (`OrderPricingCalculator`)

Implemented identically in `App\Services\Pricing\OrderPricingCalculator::calculate(Business $business, float $grossSubtotal, float $discountAmount = 0.00)` and mirrored in live Alpine UI previews:

Let `net_subtotal = max(0, gross_subtotal - min(discount_amount, gross_subtotal))`.

### 1. Tax Exclusive (`tax_inclusive = false`)

```text
Tax   = round(net_subtotal * (tax_rate / 100), 2)
Total = net_subtotal + service_charge_amount + Tax
```

Example (`$10.00` item, `10%` VAT, no service charge):
- Subtotal: `$10.00`
- Tax: `$1.00`
- Total: `$11.00`

### 2. Tax Inclusive (`tax_inclusive = true`)

```text
Tax component = round(net_subtotal * (tax_rate / (100 + tax_rate)), 2)
Total         = net_subtotal + service_charge_amount
```

Example (`$10.00` item, `10%` VAT inclusive, no service charge):
- Subtotal including tax: `$10.00`
- Tax component: `$0.91`
- Total: `$10.00`

## Historical Order Immutability

At order creation (`OrderCheckoutController` and `PosController`), `orders` persists:
- `tax_name`
- `tax_rate`
- `tax_inclusive`
- `tax_amount`

Subsequent edits to business tax settings only affect new orders; historical orders, order views, thermal receipts, and financial CSV exports always render the saved order snapshot.
