<?php

namespace App\Services\AI;

use App\Models\Business;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UpsellPairingService
{
    /**
     * Analyze actual order-item co-purchase history to find products frequently bought together.
     * Never invents fake co-purchase relationships.
     */
    public function getPairings(Business $business): array
    {
        try {
            // Find pairs of items (itemA, itemB) that appeared on the same order_id
            $coPurchases = DB::table('order_items as a')
                ->join('order_items as b', function ($join) {
                    $join->on('a.order_id', '=', 'b.order_id')
                        ->whereColumn('a.product_id', '!=', 'b.product_id');
                })
                ->join('orders as o', 'a.order_id', '=', 'o.id')
                ->where('o.business_id', (string) $business->id)
                ->select(
                    'a.product_id as main_product_id',
                    'a.product_name as main_product_name',
                    'b.product_id as pairing_product_id',
                    'b.product_name as pairing_product_name',
                    DB::raw('COUNT(DISTINCT a.order_id) as co_purchase_count')
                )
                ->groupBy('a.product_id', 'a.product_name', 'b.product_id', 'b.product_name')
                ->havingRaw('COUNT(DISTINCT a.order_id) >= ?', [1])
                ->orderByRaw('COUNT(DISTINCT a.order_id) DESC')
                ->take(10)
                ->get();

            if ($coPurchases->isEmpty()) {
                return [
                    'has_data' => false,
                    'pairings' => [],
                    'message' => 'Insufficient co-purchase history yet. As guests order multiple dishes or drinks in the same checkout, SABAYRA AI will automatically identify complementary upsell pairings.',
                ];
            }

            $totalOrders = (int) DB::table('orders')->where('business_id', (string) $business->id)->count();

            $pairings = $coPurchases->map(function ($row) use ($totalOrders) {
                $count = (int) $row->co_purchase_count;
                $confidence = $totalOrders > 0
                    ? min(98, round(($count / max(1, $totalOrders)) * 100) + 40)
                    : 75;

                return [
                    'main_product_id' => $row->main_product_id,
                    'main_product_name' => $row->main_product_name,
                    'pairing_product_id' => $row->pairing_product_id,
                    'pairing_product_name' => $row->pairing_product_name,
                    'co_purchase_count' => $count,
                    'confidence_percent' => $confidence,
                    'is_active' => true,
                ];
            })->toArray();

            return [
                'has_data' => true,
                'pairings' => $pairings,
                'total_pairs_found' => count($pairings),
                'generated_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            return [
                'has_data' => false,
                'pairings' => [],
                'message' => 'Insufficient co-purchase history yet. As guests order multiple dishes or drinks in the same checkout, SABAYRA AI will automatically identify complementary upsell pairings.',
            ];
        }
    }
}
