<?php

namespace App\Services\AI;

use App\Models\Business;
use App\Models\Product;

class MenuAuditService
{
    /**
     * Audit business products for data quality, completeness, and pricing outliers.
     */
    public function auditMenu(Business $business): array
    {
        $products = Product::where('business_id', $business->id)->get();
        $totalProducts = $products->count();

        if ($totalProducts === 0) {
            return [
                'health_score' => 100,
                'total_products' => 0,
                'missing_images_count' => 0,
                'weak_descriptions_count' => 0,
                'missing_prices_count' => 0,
                'pricing_outliers_count' => 0,
                'inactive_products_count' => 0,
                'issues' => [],
                'recommendations' => [
                    [
                        'severity' => 'info',
                        'title' => 'No Products in Catalog Yet',
                        'description' => 'Add your first menu products or use the AI Menu Builder to quickly import dishes.',
                    ]
                ],
                'audited_at' => now()->toIso8601String(),
            ];
        }

        $missingImages = [];
        $weakDescriptions = [];
        $missingPrices = [];
        $pricingOutliers = [];
        $inactiveProducts = [];

        $avgPrice = (float) $products->avg('base_price');
        $outlierThreshold = max($avgPrice * 3, 50.0);

        foreach ($products as $p) {
            // 1. Missing Image
            if (empty($p->primary_image_url)) {
                $missingImages[] = [
                    'id' => $p->id,
                    'name' => $p->name_en,
                ];
            }

            // 2. Weak Description (< 20 characters)
            if (empty($p->description_en) || mb_strlen(trim($p->description_en)) < 20) {
                $weakDescriptions[] = [
                    'id' => $p->id,
                    'name' => $p->name_en,
                    'length' => mb_strlen(trim($p->description_en ?? '')),
                ];
            }

            // 3. Missing Price
            if ((float) $p->base_price <= 0.0) {
                $missingPrices[] = [
                    'id' => $p->id,
                    'name' => $p->name_en,
                ];
            }

            // 4. Pricing Outlier
            if ((float) $p->base_price > $outlierThreshold && $totalProducts > 3) {
                $pricingOutliers[] = [
                    'id' => $p->id,
                    'name' => $p->name_en,
                    'price' => (float) $p->base_price,
                    'store_avg' => round($avgPrice, 2),
                ];
            }

            // 5. Inactive
            if (!$p->is_available) {
                $inactiveProducts[] = [
                    'id' => $p->id,
                    'name' => $p->name_en,
                ];
            }
        }

        // Calculate Quality Penalty Score
        $deductions = 0;
        $deductions += (count($missingImages) / $totalProducts) * 35;
        $deductions += (count($weakDescriptions) / $totalProducts) * 25;
        $deductions += (count($missingPrices) / $totalProducts) * 25;
        $deductions += min(count($pricingOutliers) * 5, 15);

        $healthScore = max(10, round(100 - $deductions));

        // Prioritized Actionable Recommendations
        $recommendations = [];

        if (count($missingImages) > 0) {
            $recommendations[] = [
                'severity' => 'high',
                'title' => count($missingImages) . ' products missing food photos',
                'description' => 'Visual items receive 42% higher online orders in Cambodia. Upload appetizing photos for items like ' . collect($missingImages)->take(2)->pluck('name')->implode(', ') . '.',
            ];
        }

        if (count($weakDescriptions) > 0) {
            $recommendations[] = [
                'severity' => 'medium',
                'title' => count($weakDescriptions) . ' products have short or empty descriptions',
                'description' => 'Add ingredients, portion sizes, or flavor notes so guests understand taste before ordering.',
            ];
        }

        if (count($missingPrices) > 0) {
            $recommendations[] = [
                'severity' => 'high',
                'title' => count($missingPrices) . ' products have $0.00 pricing',
                'description' => 'Check pricing on ' . collect($missingPrices)->take(2)->pluck('name')->implode(', ') . ' to prevent free checkouts.',
            ];
        }

        if (count($pricingOutliers) > 0) {
            $recommendations[] = [
                'severity' => 'low',
                'title' => count($pricingOutliers) . ' pricing outliers detected',
                'description' => 'Items are priced significantly above the average store price ($' . round($avgPrice, 2) . '). Confirm these are premium or combo platters.',
            ];
        }

        return [
            'health_score' => $healthScore,
            'total_products' => $totalProducts,
            'missing_images_count' => count($missingImages),
            'weak_descriptions_count' => count($weakDescriptions),
            'missing_prices_count' => count($missingPrices),
            'pricing_outliers_count' => count($pricingOutliers),
            'inactive_products_count' => count($inactiveProducts),
            'recommendations' => $recommendations,
            'audited_at' => now()->toIso8601String(),
        ];
    }
}
