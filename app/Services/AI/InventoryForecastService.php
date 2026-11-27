<?php

namespace App\Services\AI;

use App\Models\AiForecast;
use App\Models\Business;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\AI\Data\AIRequest;
use Illuminate\Support\Carbon;

class InventoryForecastService
{
    public function __construct(
        private readonly AIManager $aiManager,
        private readonly AIContextService $contextService,
        private readonly AIUsageService $usageService,
    ) {}

    /**
     * Compute weighted moving-average stock depletion forecasts and optional AI procurement advice.
     *
     * Never calls the LLM when consumption history is insufficient.
     *
     * @throws AIProviderException
     */
    public function analyze(
        Business $business,
        User $user,
        bool $withAiCommentary = false,
        ?string $requestUuid = null
    ): AiForecast {
        $lookbackDays = 30;
        $windowStart = Carbon::now()->subDays($lookbackDays - 1)->startOfDay();
        $recent7dStart = Carbon::now()->subDays(6)->startOfDay();

        $ingredients = InventoryItem::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_km', 'unit', 'current_stock', 'low_stock_threshold', 'unit_cost']);

        $trackedProducts = Product::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('track_inventory', true)
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_km', 'unit', 'stock_quantity', 'low_stock_threshold', 'cost_price']);

        $movements = InventoryMovement::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereIn('type', ['out', 'recipe_deduction', 'waste'])
            ->where('created_at', '>=', $windowStart)
            ->get(['inventory_item_id', 'quantity', 'created_at']);

        $trackedProductIds = $trackedProducts->pluck('id')->all();
        $productSales = $trackedProductIds !== []
            ? OrderItem::query()
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.business_id', $business->id)
                ->whereIn('order_items.product_id', $trackedProductIds)
                ->whereNotIn('orders.status', ['cancelled', 'rejected'])
                ->where('orders.created_at', '>=', $windowStart)
                ->get(['order_items.product_id', 'order_items.quantity', 'orders.created_at'])
            : collect();

        $allDates = collect();
        foreach ($movements as $m) {
            if ($m->created_at) {
                $allDates->push($m->created_at);
            }
        }
        foreach ($productSales as $ps) {
            if ($ps->created_at) {
                $allDates->push(Carbon::parse((string) $ps->created_at));
            }
        }

        $earliestDate = $allDates->min();
        $daysAvailable = $earliestDate
            ? max(1, min($lookbackDays, (int) Carbon::parse((string) $earliestDate)->startOfDay()->diffInDays(Carbon::now()->startOfDay()) + 1))
            : 0;

        $dataPointsCount = $movements->count() + $productSales->count();
        $totalTracked = $ingredients->count() + $trackedProducts->count();

        $itemsForecast = [];

        foreach ($ingredients as $item) {
            $itemMovements = $movements->where('inventory_item_id', $item->id);
            $qty30 = (float) $itemMovements->sum(static fn (InventoryMovement $m): float => abs((float) $m->quantity));
            $qty7 = (float) $itemMovements
                ->filter(static fn (InventoryMovement $m): bool => $m->created_at && $m->created_at->gte($recent7dStart))
                ->sum(static fn (InventoryMovement $m): float => abs((float) $m->quantity));

            $itemsForecast[] = $this->buildItemForecastRow(
                source: 'ingredient',
                id: $item->id,
                nameEn: $item->name_en,
                nameKm: $item->name_km,
                unit: $item->unit ?? 'unit',
                currentStock: (float) $item->current_stock,
                threshold: (float) $item->low_stock_threshold,
                unitCost: $item->unit_cost !== null ? (float) $item->unit_cost : null,
                qty30: $qty30,
                qty7: $qty7,
                daysAvailable: $daysAvailable
            );
        }

        foreach ($trackedProducts as $product) {
            $itemSales = $productSales->where('product_id', $product->id);
            $qty30 = (float) $itemSales->sum(static fn ($row): float => abs((float) $row->quantity));
            $qty7 = (float) $itemSales
                ->filter(static fn ($row): bool => $row->created_at && Carbon::parse((string) $row->created_at)->gte($recent7dStart))
                ->sum(static fn ($row): float => abs((float) $row->quantity));

            $itemsForecast[] = $this->buildItemForecastRow(
                source: 'product',
                id: $product->id,
                nameEn: $product->name_en,
                nameKm: $product->name_km,
                unit: $product->unit ?? 'item',
                currentStock: (float) $product->stock_quantity,
                threshold: (float) $product->low_stock_threshold,
                unitCost: $product->cost_price !== null ? (float) $product->cost_price : null,
                qty30: $qty30,
                qty7: $qty7,
                daysAvailable: $daysAvailable
            );
        }

        usort($itemsForecast, static function (array $a, array $b): int {
            $rank = ['critical' => 0, 'warning' => 1, 'healthy' => 2];
            $cmp = ($rank[$a['risk_level']] ?? 3) <=> ($rank[$b['risk_level']] ?? 3);
            if ($cmp !== 0) {
                return $cmp;
            }
            $daysA = $a['estimated_days_remaining'] ?? 99999;
            $daysB = $b['estimated_days_remaining'] ?? 99999;

            return $daysA <=> $daysB;
        });

        $criticalCount = count(array_filter($itemsForecast, static fn (array $r): bool => $r['risk_level'] === 'critical'));
        $warningCount = count(array_filter($itemsForecast, static fn (array $r): bool => $r['risk_level'] === 'warning'));
        $minDaysRecommended = (int) config('ai.forecasting.min_days_for_short_horizon', 7);

        $status = 'ready';
        $confidence = 'medium';
        $limitations = null;

        if ($totalTracked === 0 || $dataPointsCount === 0) {
            $status = 'insufficient_data';
            $confidence = 'insufficient';
            $limitations = $totalTracked === 0
                ? 'No active inventory ingredients or inventory-tracked products exist yet for this business.'
                : 'No historical stock deductions, waste logs, or tracked product orders were recorded in the past 30 days. Stock runway cannot be computed until consumption occurs.';
        } elseif ($daysAvailable < $minDaysRecommended || $dataPointsCount < 5) {
            $confidence = 'low';
            $limitations = "Limited data span: {$dataPointsCount} consumption event(s) over {$daysAvailable} day(s). Minimum {$minDaysRecommended} days recommended for high confidence.";
        } elseif ($daysAvailable >= 21 && $dataPointsCount >= 12) {
            $confidence = 'high';
        }

        $statisticalPayload = [
            'lookback_days' => $lookbackDays,
            'total_tracked_items' => $totalTracked,
            'critical_count' => $criticalCount,
            'warning_count' => $warningCount,
            'items' => $itemsForecast,
        ];

        $assumptions = [
            'Weighted moving average combines 60% last-7-day velocity and 40% lookback-window velocity.',
            'Recommended reorder quantities target 14 days of projected stock cover.',
            'Excludes cancelled and rejected orders from demand counts.',
        ];

        $aiExplanation = null;
        $usageLogId = null;

        if ($withAiCommentary && $status === 'ready') {
            $aiResult = $this->generateAiExplanation($business, $user, $statisticalPayload, $confidence, $limitations, $requestUuid);
            $aiExplanation = $aiResult['text'];
            $usageLogId = $aiResult['usage_log_id'];
        }

        return AiForecast::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'forecast_type' => 'inventory',
            'horizon' => 'stock_runway',
            'status' => $status,
            'historical_days_available' => $daysAvailable,
            'data_points_count' => $dataPointsCount,
            'confidence_level' => $confidence,
            'statistical_payload' => $statisticalPayload,
            'ai_explanation' => $aiExplanation,
            'limitations' => $limitations,
            'assumptions_json' => $assumptions,
            'usage_log_id' => $usageLogId,
            'generated_at' => now(),
            'expires_at' => now()->addHours(12),
        ]);
    }

    /**
     * Build one deterministic inventory item/product runway record.
     *
     * @return array<string, mixed>
     */
    private function buildItemForecastRow(
        string $source,
        string $id,
        string $nameEn,
        ?string $nameKm,
        string $unit,
        float $currentStock,
        float $threshold,
        ?float $unitCost,
        float $qty30,
        float $qty7,
        int $daysAvailable
    ): array {
        $span30 = max(1, $daysAvailable);
        $span7 = max(1, min(7, $daysAvailable));

        $avg30 = $daysAvailable > 0 ? ($qty30 / $span30) : 0.0;
        $avg7 = $daysAvailable > 0 ? ($qty7 / $span7) : 0.0;
        $weightedDaily = round((0.6 * $avg7) + (0.4 * $avg30), 3);

        $daysRemaining = $weightedDaily > 0
            ? round(max(0.0, $currentStock) / $weightedDaily, 1)
            : null;

        $riskLevel = 'healthy';
        if ($currentStock <= 0 || ($daysRemaining !== null && $daysRemaining <= 3.0) || $currentStock <= $threshold) {
            $riskLevel = 'critical';
        } elseif ($daysRemaining !== null && $daysRemaining <= 7.0) {
            $riskLevel = 'warning';
        }

        $targetStock = $weightedDaily > 0
            ? ($weightedDaily * 14.0)
            : max(0.0, $threshold * 2.0);
        $recommendedReorder = round(max(0.0, $targetStock - max(0.0, $currentStock)), 2);
        $estimatedReorderCost = $unitCost !== null && $unitCost > 0
            ? round($recommendedReorder * $unitCost, 2)
            : null;

        return [
            'source' => $source,
            'id' => $id,
            'name_en' => $nameEn,
            'name_km' => $nameKm,
            'unit' => $unit,
            'current_stock' => round($currentStock, 2),
            'low_stock_threshold' => round($threshold, 2),
            'total_consumed_30d' => round($qty30, 2),
            'weighted_daily_usage' => $weightedDaily,
            'estimated_days_remaining' => $daysRemaining,
            'risk_level' => $riskLevel,
            'recommended_reorder_qty' => $recommendedReorder,
            'estimated_reorder_cost_usd' => $estimatedReorderCost,
        ];
    }

    /**
     * Request plain-language procurement advice strictly anchored to precomputed numbers.
     *
     * @param  array<string, mixed>  $statisticalPayload
     * @return array{text: string, usage_log_id: ?string}
     */
    private function generateAiExplanation(
        Business $business,
        User $user,
        array $statisticalPayload,
        string $confidence,
        ?string $limitations,
        ?string $requestUuid
    ): array {
        $featureSettings = $this->usageService->getOrCreateFeatureSetting($business);
        $lang = (string) ($featureSettings->preferred_language ?? 'auto');
        if ($lang === 'auto') {
            $lang = 'mixed';
        }
        $langRule = $this->contextService->formatSystemLanguageInstruction($lang);

        $topItems = array_slice($statisticalPayload['items'], 0, 12);
        $promptPayload = [
            'confidence_level' => $confidence,
            'limitations' => $limitations,
            'critical_count' => $statisticalPayload['critical_count'],
            'warning_count' => $statisticalPayload['warning_count'],
            'top_risk_items' => $topItems,
        ];

        $response = $this->aiManager->complete($business, new AIRequest(
            feature: 'inventory_forecast',
            systemPrompt: implode("\n\n", [
                'You are SABAYRA AI Inventory & Procurement Advisor for Cambodian businesses.',
                $langRule,
                'Explain the computed inventory runway and reorder priorities concisely in clear prose.',
                'STRICT RULE: Quote ONLY the quantities, days remaining, and reorder quantities provided in the statistical JSON. Never alter or invent any number.',
            ]),
            messages: [
                ['role' => 'user', 'content' => json_encode($promptPayload, JSON_UNESCAPED_UNICODE)],
            ],
            requestUuid: $requestUuid,
        ), $user->id);

        return [
            'text' => $response->text,
            'usage_log_id' => $response->usageLogId,
        ];
    }
}
