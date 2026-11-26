<?php

namespace App\Services\Pricing;

use App\Models\Business;

class OrderPricingCalculator
{
    /**
     * Calculate authoritative order totals using consistent SABAYRA pricing order:
     * 1. Gross Subtotal (sum of line item subtotals)
     * 2. Net Subtotal = max(0, Gross Subtotal - min(Discount, Gross Subtotal))
     * 3. Service Charge (if enabled & rate > 0) = round(Net Subtotal * Service Rate / 100, 2)
     * 4. Tax:
     *    - Exclusive: round(Net Subtotal * Tax Rate / 100, 2); Total = Net Subtotal + Service Charge + Tax
     *    - Inclusive: round(Net Subtotal * Tax Rate / (100 + Tax Rate), 2); Total = Net Subtotal + Service Charge
     *
     * @return array{
     *     gross_subtotal: float,
     *     discount_amount: float,
     *     net_subtotal: float,
     *     service_charge_enabled: bool,
     *     service_charge_rate: float,
     *     service_charge_amount: float,
     *     tax_name: string,
     *     tax_rate: float,
     *     tax_inclusive: bool,
     *     tax_amount: float,
     *     total_amount: float,
     *     total_khr: float,
     *     exchange_rate_khr: float,
     *     currency: string
     * }
     */
    public function calculate(Business $business, float $grossSubtotal, float $discountAmount = 0.00): array
    {
        $grossSubtotal = round(max(0.00, $grossSubtotal), 2);
        $discount = round(min(max(0.00, $discountAmount), $grossSubtotal), 2);
        $netSubtotal = round(max(0.00, $grossSubtotal - $discount), 2);

        $serviceChargeEnabled = (bool) ($business->service_charge_enabled ?? false);
        $rawServiceRate = round(max(0.00, min(100.00, (float) ($business->service_charge_rate ?? 0.00))), 2);
        $appliedServiceEnabled = $serviceChargeEnabled && $rawServiceRate > 0.0;
        $appliedServiceRate = $appliedServiceEnabled ? $rawServiceRate : 0.00;
        $serviceChargeAmount = $appliedServiceEnabled
            ? round($netSubtotal * ($appliedServiceRate / 100), 2)
            : 0.00;

        $taxName = trim((string) ($business->tax_name ?? 'VAT')) ?: 'VAT';
        $taxRate = round(max(0.00, min(100.00, (float) ($business->tax_percent ?? 0.00))), 2);
        $taxInclusive = (bool) ($business->tax_inclusive ?? false);

        if ($taxRate <= 0.0) {
            $taxAmount = 0.00;
            $totalAmount = round($netSubtotal + $serviceChargeAmount, 2);
        } elseif ($taxInclusive) {
            $taxAmount = round($netSubtotal * ($taxRate / (100.0 + $taxRate)), 2);
            $totalAmount = round($netSubtotal + $serviceChargeAmount, 2);
        } else {
            $taxAmount = round($netSubtotal * ($taxRate / 100.0), 2);
            $totalAmount = round($netSubtotal + $serviceChargeAmount + $taxAmount, 2);
        }

        $exchangeRate = (float) ($business->exchange_rate_khr > 0 ? $business->exchange_rate_khr : 4100.00);
        $totalKhr = $business->khrEquivalent($totalAmount);

        return [
            'gross_subtotal' => $grossSubtotal,
            'discount_amount' => $discount,
            'net_subtotal' => $netSubtotal,
            'service_charge_enabled' => $appliedServiceEnabled,
            'service_charge_rate' => $appliedServiceRate,
            'service_charge_amount' => $serviceChargeAmount,
            'tax_name' => $taxName,
            'tax_rate' => $taxRate,
            'tax_inclusive' => $taxInclusive,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'total_khr' => $totalKhr,
            'exchange_rate_khr' => $exchangeRate,
            'currency' => (string) ($business->default_currency ?: 'USD'),
        ];
    }
}
