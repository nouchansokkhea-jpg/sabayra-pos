<?php

namespace App\Services\AI;

use App\Models\AiForecast;
use App\Models\Business;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\AI\Data\AIRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesPredictionService
{
    public function __construct(
        private readonly AIManager $aiManager,
        private readonly AIContextService $contextService,
        private readonly AIUsageService $usageService,
    ) {}

    /**
     * Compute deterministic sales projections (1d, 7d, 30d) strictly from verified historical orders.
     *
     * Marks insufficient_data with zero credit cost when order history is too small.
     *
     * @throws AIProviderException
     */
    public function predict(
        Business $business,
        User $user,
        string $horizon = '7d',
        bool $withAiCommentary = false,
        ?string $requestUuid = null
    ): AiForecast {
        if (! in_array($horizon, ['1d', '7d', '30d'], true)) {
            $horizon = '7d';
        }

        $tz = config('app.timezone', 'Asia/Phnom_Penh');
        $today = Carbon::now($tz)->startOfDay();
        $windowStart = (clone $today)->subDays(59)->startOfDay();

        $orders = Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('created_at', '>=', $windowStart)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->orderBy('created_at')
            ->get(['total_amount', 'payment_status', 'created_at']);

        $totalOrders = $orders->count();
        $earliestOrder = $orders->first()?->created_at?->copy()->timezone($tz)->startOfDay();
        $daysAvailable = $earliestOrder
            ? max(1, min(60, (int) $earliestOrder->diffInDays($today) + 1))
            : 0;

        $dailyMap = [];
        if ($earliestOrder) {
            $cursor = clone $earliestOrder;
            while ($cursor->lte($today)) {
                $dailyMap[$cursor->toDateString()] = [
                    'date' => $cursor->toDateString(),
                    'weekday' => $cursor->format('D'),
                    'orders' => 0,
                    'revenue_usd' => 0.0,
                ];
                $cursor->addDay();
            }

            foreach ($orders as $order) {
                $dayKey = $order->created_at->copy()->timezone($tz)->toDateString();
                if (isset($dailyMap[$dayKey])) {
                    $dailyMap[$dayKey]['orders']++;
                    $dailyMap[$dayKey]['revenue_usd'] = round(
                        $dailyMap[$dayKey]['revenue_usd'] + (float) $order->total_amount,
                        2
                    );
                }
            }
        }

        $dailySeries = array_values($dailyMap);
        $minOrdersRequired = (int) config('ai.forecasting.min_orders_required', 5);
        $minShortDays = (int) config('ai.forecasting.min_days_for_short_horizon', 7);
        $minMonthlyDays = (int) config('ai.forecasting.min_days_for_monthly_horizon', 14);

        $exchangeRate = (float) ($business->exchange_rate_khr ?: config('menuhub.default_exchange_rate_khr', 4100));
        $observedRevenue = round((float) $orders->sum('total_amount'), 2);

        if ($totalOrders < $minOrdersRequired || $daysAvailable < 3) {
            return AiForecast::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'forecast_type' => 'sales',
                'horizon' => $horizon,
                'status' => 'insufficient_data',
                'historical_days_available' => $daysAvailable,
                'data_points_count' => $totalOrders,
                'confidence_level' => 'insufficient',
                'statistical_payload' => [
                    'observed_revenue_usd' => $observedRevenue,
                    'observed_orders' => $totalOrders,
                    'daily_series' => $dailySeries,
                    'horizons' => [],
                    'top_categories' => [],
                ],
                'ai_explanation' => null,
                'limitations' => "Recorded {$totalOrders} order(s) across {$daysAvailable} day(s). At least {$minOrdersRequired} orders across 3+ days are required before generating reliable projections.",
                'assumptions_json' => [
                    'Forecasting requires real non-cancelled order history rather than synthetic numbers.',
                ],
                'generated_at' => now(),
                'expires_at' => now()->addHours(12),
            ]);
        }

        $seriesCount = count($dailySeries);
        $last7 = array_slice($dailySeries, max(0, $seriesCount - 7));
        $prior7 = $seriesCount > 7 ? array_slice($dailySeries, max(0, $seriesCount - 14), min(7, $seriesCount - 7)) : $last7;

        $avgDailyRevenue7 = count($last7) > 0 ? (array_sum(array_column($last7, 'revenue_usd')) / count($last7)) : 0.0;
        $avgDailyOrders7 = count($last7) > 0 ? (array_sum(array_column($last7, 'orders')) / count($last7)) : 0.0;
        $avgDailyOverall = $seriesCount > 0 ? ($observedRevenue / $seriesCount) : 0.0;
        $avgOrdersOverall = $seriesCount > 0 ? ($totalOrders / $seriesCount) : 0.0;

        $priorRevenueAvg = count($prior7) > 0 ? (array_sum(array_column($prior7, 'revenue_usd')) / count($prior7)) : $avgDailyRevenue7;
        $rawTrendFactor = $priorRevenueAvg > 0 ? ($avgDailyRevenue7 / $priorRevenueAvg) : 1.0;
        $trendFactor = max(0.75, min(1.25, $rawTrendFactor));

        $baselineDailyRev = (0.65 * $avgDailyRevenue7) + (0.35 * $avgDailyOverall);
        $baselineDailyOrders = (0.65 * $avgDailyOrders7) + (0.35 * $avgOrdersOverall);

        $weekdayTotals = [];
        foreach ($dailySeries as $entry) {
            $wd = $entry['weekday'];
            $weekdayTotals[$wd] ??= ['sum' => 0.0, 'days' => 0];
            $weekdayTotals[$wd]['sum'] += $entry['revenue_usd'];
            $weekdayTotals[$wd]['days']++;
        }
        $weekdaySeasonality = [];
        foreach ($weekdayTotals as $wd => $stats) {
            $wdAvg = $stats['days'] > 0 ? ($stats['sum'] / $stats['days']) : $avgDailyOverall;
            $weekdaySeasonality[$wd] = $avgDailyOverall > 0 ? round($wdAvg / $avgDailyOverall, 2) : 1.0;
        }

        $horizons = [];
        foreach (['1d' => 1, '7d' => 7, '30d' => 30] as $hKey => $hDays) {
            $expectedRev = round($baselineDailyRev * $hDays * ($hDays === 1 ? 1.0 : ((1.0 + $trendFactor) / 2.0)), 2);
            $expectedOrders = (int) round($baselineDailyOrders * $hDays * ($hDays === 1 ? 1.0 : ((1.0 + $trendFactor) / 2.0)));

            $horizons[$hKey] = [
                'horizon' => $hKey,
                'days' => $hDays,
                'expected_revenue_usd' => $expectedRev,
                'low_estimate_usd' => round($expectedRev * 0.85, 2),
                'high_estimate_usd' => round($expectedRev * 1.15, 2),
                'expected_revenue_khr' => round($expectedRev * $exchangeRate, 0),
                'expected_orders' => max(0, $expectedOrders),
            ];
        }

        $topCategories = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.business_id', $business->id)
            ->where('orders.created_at', '>=', $windowStart)
            ->whereNotIn('orders.status', ['cancelled', 'rejected'])
            ->groupBy('categories.name_en')
            ->select([
                DB::raw("COALESCE(categories.name_en, 'Uncategorized') as category_name"),
                DB::raw('SUM(order_items.subtotal) as revenue_usd'),
            ])
            ->orderByDesc('revenue_usd')
            ->limit(5)
            ->get()
            ->map(static fn ($row): array => [
                'category' => $row->category_name,
                'revenue_usd' => round((float) $row->revenue_usd, 2),
            ])
            ->all();

        $confidence = 'medium';
        $limitations = null;

        if ($daysAvailable < $minShortDays || ($horizon === '30d' && $daysAvailable < $minMonthlyDays)) {
            $confidence = 'low';
            $limitations = "Observed {$daysAvailable} day(s) of order history. Extending a {$horizon} forecast carries wider uncertainty until 14+ days of continuous orders accrue.";
        } elseif ($daysAvailable >= 30 && $totalOrders >= 40) {
            $confidence = 'high';
        }

        $statisticalPayload = [
            'observed_revenue_usd' => $observedRevenue,
            'observed_orders' => $totalOrders,
            'avg_daily_revenue_7d' => round($avgDailyRevenue7, 2),
            'avg_daily_revenue_overall' => round($avgDailyOverall, 2),
            'trend_multiplier' => round($trendFactor, 2),
            'weekday_seasonality' => $weekdaySeasonality,
            'daily_series' => array_slice($dailySeries, -30),
            'horizons' => $horizons,
            'top_categories' => $topCategories,
        ];

        $assumptions = [
            'Combines 65% recent 7-day rolling revenue average and 35% active-history daily average.',
            'Momentum multiplier is conservatively damped between 0.75x and 1.25x to avoid single-day outlier distortion.',
            'Excludes cancelled and rejected orders; uncertainty bands reflect ±15% variance.',
        ];

        $aiExplanation = null;
        $usageLogId = null;

        if ($withAiCommentary) {
            $aiResult = $this->generateAiExplanation($business, $user, $horizon, $statisticalPayload, $confidence, $limitations, $requestUuid);
            $aiExplanation = $aiResult['text'];
            $usageLogId = $aiResult['usage_log_id'];
        }

        return AiForecast::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'forecast_type' => 'sales',
            'horizon' => $horizon,
            'status' => 'ready',
            'historical_days_available' => $daysAvailable,
            'data_points_count' => $totalOrders,
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
     * Generate plain-language commentary on precomputed statistical revenue projections.
     *
     * @param  array<string, mixed>  $statisticalPayload
     * @return array{text: string, usage_log_id: ?string}
     */
    private function generateAiExplanation(
        Business $business,
        User $user,
        string $horizon,
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

        $promptInput = [
            'selected_horizon' => $horizon,
            'confidence_level' => $confidence,
            'limitations' => $limitations,
            'projection' => $statisticalPayload['horizons'][$horizon] ?? null,
            'all_horizons' => $statisticalPayload['horizons'],
            'avg_daily_revenue_7d' => $statisticalPayload['avg_daily_revenue_7d'],
            'trend_multiplier' => $statisticalPayload['trend_multiplier'],
            'weekday_seasonality' => $statisticalPayload['weekday_seasonality'],
            'top_categories' => $statisticalPayload['top_categories'],
        ];

        $response = $this->aiManager->complete($business, new AIRequest(
            feature: 'sales_prediction',
            systemPrompt: implode("\n\n", [
                'You are SABAYRA AI Financial & Demand Advisor for Cambodian cafés, restaurants, bakeries, and shops.',
                $this->contextService->formatSystemLanguageInstruction($lang),
                'Explain what is driving the statistical sales forecast, highlight strongest weekdays and categories, and suggest 2 practical prep or staffing tips.',
                'STRICT RULE: Never change, round differently, or fabricate any sales figures outside the provided statistical JSON.',
            ]),
            messages: [
                ['role' => 'user', 'content' => json_encode($promptInput, JSON_UNESCAPED_UNICODE)],
            ],
            requestUuid: $requestUuid,
        ), $user->id);

        return [
            'text' => $response->text,
            'usage_log_id' => $response->usageLogId,
        ];
    }
}
