<?php

namespace App\Services\AI;

use App\Models\AiCreditLedger;
use App\Models\AiFeatureSetting;
use App\Models\AiUsageLog;
use App\Models\Business;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Services\AI\Data\AIResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AIUsageService
{
    /**
     * Resolve effective plan entitlements and token caps for a business.
     *
     * Respects `subscription.plans_enabled`: in free launch mode (`false`),
     * all AI Center features remain available unless disabled in business settings.
     *
     * @return array{
     *     plans_enforced: bool,
     *     plan_name: string,
     *     plan_slug: string,
     *     monthly_credits: int,
     *     max_tokens_per_request: int,
     *     features: array<string, bool>
     * }
     */
    public function resolveEntitlements(Business $business): array
    {
        $plansEnforced = (bool) config('subscription.plans_enabled', false);
        $defaultCredits = (int) PlatformSetting::getValue(
            'ai.default_monthly_credits',
            config('ai.default_monthly_credits', 100)
        );
        $defaultMaxTokens = (int) PlatformSetting::getValue(
            'ai.max_output_tokens',
            config('ai.max_output_tokens', 2048)
        );

        $subscription = $business->activeSubscription()->with('plan')->first();
        $plan = $subscription?->plan;

        if (! $plansEnforced) {
            return [
                'plans_enforced' => false,
                'plan_name' => $plan?->name ?? 'SABAYRA Launch Access',
                'plan_slug' => $plan?->slug ?? 'launch',
                'monthly_credits' => max(0, $plan ? max($defaultCredits, (int) $plan->ai_monthly_credits) : $defaultCredits),
                'max_tokens_per_request' => max(256, $plan ? max($defaultMaxTokens, (int) $plan->ai_max_tokens_per_request) : $defaultMaxTokens),
                'features' => [
                    'assistant' => true,
                    'menu_creator' => true,
                    'marketing_studio' => true,
                    'inventory_forecast' => true,
                    'sales_prediction' => true,
                    'insights' => true,
                ],
            ];
        }

        if (! $plan) {
            $plan = Plan::where('slug', 'free')->first();
        }

        if (! $plan) {
            return [
                'plans_enforced' => true,
                'plan_name' => 'Free Tier',
                'plan_slug' => 'free',
                'monthly_credits' => 30,
                'max_tokens_per_request' => 1536,
                'features' => [
                    'assistant' => true,
                    'menu_creator' => true,
                    'marketing_studio' => false,
                    'inventory_forecast' => false,
                    'sales_prediction' => false,
                    'insights' => true,
                ],
            ];
        }

        return [
            'plans_enforced' => true,
            'plan_name' => $plan->name,
            'plan_slug' => $plan->slug,
            'monthly_credits' => max(0, (int) $plan->ai_monthly_credits),
            'max_tokens_per_request' => max(256, (int) $plan->ai_max_tokens_per_request),
            'features' => [
                'assistant' => (bool) $plan->has_ai_assistant,
                'menu_creator' => (bool) $plan->has_ai_menu_creator,
                'marketing_studio' => (bool) $plan->has_ai_marketing,
                'inventory_forecast' => (bool) $plan->has_ai_forecasting,
                'sales_prediction' => (bool) $plan->has_ai_forecasting,
                'insights' => (bool) $plan->has_ai_assistant,
            ],
        ];
    }

    /**
     * Get or initialize business AI feature preferences without failing on concurrency.
     */
    public function getOrCreateFeatureSetting(Business $business): AiFeatureSetting
    {
        $setting = AiFeatureSetting::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->first();

        if ($setting) {
            return $setting;
        }

        try {
            return AiFeatureSetting::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'preferred_language' => 'auto',
                'preferred_tone' => 'friendly',
                'temperature' => (float) config('ai.temperature', 0.4),
                'assistant_enabled' => true,
                'menu_creator_enabled' => true,
                'marketing_studio_enabled' => true,
                'forecasting_enabled' => true,
                'insights_enabled' => true,
            ]);
        } catch (QueryException) {
            return AiFeatureSetting::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->firstOrFail();
        }
    }

    /**
     * Assert that the business is permitted by both plan entitlement and AI Settings to run a feature.
     *
     * @throws AIProviderException
     */
    public function assertFeatureAllowed(Business $business, string $feature): void
    {
        $entitlements = $this->resolveEntitlements($business);
        if (! ($entitlements['features'][$feature] ?? false)) {
            throw new AIProviderException(
                'plan_upgrade_required',
                403,
                "Your current subscription plan ({$entitlements['plan_name']}) does not include this AI feature. Upgrade your plan to unlock it."
            );
        }

        $settings = $this->getOrCreateFeatureSetting($business);
        if (! $settings->isFeatureEnabled($feature)) {
            throw new AIProviderException(
                'feature_disabled',
                403,
                'This AI feature is currently turned off in your Business AI Settings.'
            );
        }
    }

    /**
     * Resolve or initialize the monthly credit ledger for a business, syncing plan credits.
     */
    public function getOrCreateLedger(Business $business, ?string $periodYm = null): AiCreditLedger
    {
        $period = $periodYm ?? $this->currentPeriodYm();
        $entitlements = $this->resolveEntitlements($business);
        $allocated = $entitlements['monthly_credits'];

        return DB::transaction(function () use ($business, $period, $allocated): AiCreditLedger {
            $ledger = AiCreditLedger::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('period_ym', $period)
                ->lockForUpdate()
                ->first();

            if ($ledger) {
                if ($period === $this->currentPeriodYm() && (int) $ledger->allocated_credits !== $allocated) {
                    $ledger->allocated_credits = $allocated;
                    $ledger->save();
                }

                return $ledger;
            }

            try {
                return AiCreditLedger::withoutGlobalScopes()->create([
                    'business_id' => $business->id,
                    'period_ym' => $period,
                    'allocated_credits' => $allocated,
                    'bonus_credits' => 0,
                    'used_credits' => 0,
                    'reserved_credits' => 0,
                ]);
            } catch (QueryException) {
                return AiCreditLedger::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->where('period_ym', $period)
                    ->lockForUpdate()
                    ->firstOrFail();
            }
        });
    }

    /**
     * Atomically reserve monthly credits and create a pending AiUsageLog guarded by request_uuid.
     *
     * @throws AIProviderException
     */
    public function reserveCredit(
        Business $business,
        ?int $userId,
        string $feature,
        string $provider,
        string $model,
        int $credits = 1,
        ?string $requestUuid = null
    ): AiUsageLog {
        $credits = max(1, $credits);
        $uuid = filled($requestUuid) ? trim((string) $requestUuid) : (string) Str::uuid();
        $period = $this->currentPeriodYm();
        $entitlements = $this->resolveEntitlements($business);
        $allocated = $entitlements['monthly_credits'];

        return DB::transaction(function () use ($business, $userId, $feature, $provider, $model, $credits, $uuid, $period, $allocated): AiUsageLog {
            if (AiUsageLog::withoutGlobalScopes()->where('request_uuid', $uuid)->exists()) {
                throw new AIProviderException(
                    'duplicate_request',
                    409,
                    'This AI request was already submitted and is not charged twice.'
                );
            }

            $ledger = AiCreditLedger::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('period_ym', $period)
                ->lockForUpdate()
                ->first();

            if (! $ledger) {
                try {
                    $ledger = AiCreditLedger::withoutGlobalScopes()->create([
                        'business_id' => $business->id,
                        'period_ym' => $period,
                        'allocated_credits' => $allocated,
                        'bonus_credits' => 0,
                        'used_credits' => 0,
                        'reserved_credits' => 0,
                    ]);
                } catch (QueryException) {
                    $ledger = AiCreditLedger::withoutGlobalScopes()
                        ->where('business_id', $business->id)
                        ->where('period_ym', $period)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            } elseif ((int) $ledger->allocated_credits !== $allocated) {
                $ledger->allocated_credits = $allocated;
            }

            $remaining = $ledger->remainingCredits();
            if ($remaining < $credits) {
                throw new AIProviderException(
                    'insufficient_credits',
                    402,
                    "Insufficient monthly AI credits remaining ({$remaining} left, {$credits} required)."
                );
            }

            $ledger->reserved_credits = (int) $ledger->reserved_credits + $credits;
            $ledger->save();

            return AiUsageLog::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'user_id' => $userId,
                'request_uuid' => $uuid,
                'feature' => $feature,
                'provider' => $provider,
                'model' => $model,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
                'credits_charged' => $credits,
                'estimated_cost_usd' => 0.0,
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Commit reserved credits into used credits and record final provider token metrics.
     */
    public function finalizeUsage(AiUsageLog $usageLog, AIResponse $response): AiUsageLog
    {
        return DB::transaction(function () use ($usageLog, $response): AiUsageLog {
            /** @var AiUsageLog $lockedLog */
            $lockedLog = AiUsageLog::withoutGlobalScopes()
                ->whereKey($usageLog->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLog->status !== 'pending') {
                return $lockedLog;
            }

            $period = ($lockedLog->created_at ?? now())->format('Y-m');
            $ledger = AiCreditLedger::withoutGlobalScopes()
                ->where('business_id', $lockedLog->business_id)
                ->where('period_ym', $period)
                ->lockForUpdate()
                ->first();

            if ($ledger) {
                $credits = (int) $lockedLog->credits_charged;
                $ledger->reserved_credits = max(0, (int) $ledger->reserved_credits - $credits);
                $ledger->used_credits = (int) $ledger->used_credits + $credits;
                $ledger->save();
            }

            $estimatedCost = $this->calculateEstimatedCostUsd(
                $response->provider,
                $response->model,
                $response->promptTokens,
                $response->completionTokens
            );

            $lockedLog->update([
                'provider' => $response->provider,
                'model' => $response->model,
                'prompt_tokens' => $response->promptTokens,
                'completion_tokens' => $response->completionTokens,
                'total_tokens' => $response->totalTokens,
                'estimated_cost_usd' => $estimatedCost,
                'latency_ms' => $response->latencyMs,
                'status' => 'completed',
                'error_code' => null,
                'error_message' => null,
            ]);

            return $lockedLog->fresh();
        });
    }

    /**
     * Release reserved credits back to the monthly ledger when an AI call fails.
     */
    public function refundReservation(AiUsageLog $usageLog, string $errorCode, string $errorMessage): AiUsageLog
    {
        return DB::transaction(function () use ($usageLog, $errorCode, $errorMessage): AiUsageLog {
            /** @var AiUsageLog $lockedLog */
            $lockedLog = AiUsageLog::withoutGlobalScopes()
                ->whereKey($usageLog->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLog->status !== 'pending') {
                return $lockedLog;
            }

            $period = ($lockedLog->created_at ?? now())->format('Y-m');
            $ledger = AiCreditLedger::withoutGlobalScopes()
                ->where('business_id', $lockedLog->business_id)
                ->where('period_ym', $period)
                ->lockForUpdate()
                ->first();

            if ($ledger) {
                $credits = (int) $lockedLog->credits_charged;
                $ledger->reserved_credits = max(0, (int) $ledger->reserved_credits - $credits);
                $ledger->save();
            }

            $lockedLog->update([
                'status' => 'refunded',
                'error_code' => Str::limit($errorCode, 60),
                'error_message' => Str::limit($errorMessage, 500),
            ]);

            return $lockedLog->fresh();
        });
    }

    /**
     * Estimate USD API cost from configured token pricing per 1 million tokens.
     */
    public function calculateEstimatedCostUsd(string $provider, string $model, int $promptTokens, int $completionTokens): float
    {
        $models = config("ai.providers.{$provider}.models", []);
        $pricing = $models[$model] ?? (is_array($models) && $models !== [] ? reset($models) : null);

        if (! is_array($pricing)) {
            return 0.0;
        }

        $inputRate = (float) ($pricing['input_per_1m'] ?? 0.0);
        $outputRate = (float) ($pricing['output_per_1m'] ?? 0.0);

        $cost = (($promptTokens / 1_000_000) * $inputRate) + (($completionTokens / 1_000_000) * $outputRate);

        return round($cost, 6);
    }

    /**
     * Build a real monthly AI credit and usage summary for a business.
     *
     * @return array{
     *     period_ym: string,
     *     allocated_credits: int,
     *     bonus_credits: int,
     *     total_credits: int,
     *     used_credits: int,
     *     reserved_credits: int,
     *     remaining_credits: int,
     *     requests_count: int,
     *     completed_requests: int,
     *     refunded_requests: int,
     *     total_tokens: int,
     *     estimated_cost_usd: float
     * }
     */
    public function getMonthlySummary(Business $business, ?string $periodYm = null): array
    {
        $period = $periodYm ?? $this->currentPeriodYm();
        $ledger = $this->getOrCreateLedger($business, $period);

        $monthStart = now()->startOfMonth();
        if (preg_match('/^\d{4}-\d{2}$/', $period) === 1) {
            $monthStart = now()->setDate((int) substr($period, 0, 4), (int) substr($period, 5, 2), 1)->startOfMonth();
        }
        $monthEnd = (clone $monthStart)->endOfMonth();

        $logsQuery = AiUsageLog::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        $requestsCount = (clone $logsQuery)->count();
        $completedRequests = (clone $logsQuery)->where('status', 'completed')->count();
        $refundedRequests = (clone $logsQuery)->whereIn('status', ['refunded', 'failed'])->count();
        $totalTokens = (int) (clone $logsQuery)->where('status', 'completed')->sum('total_tokens');
        $estimatedCostUsd = (float) (clone $logsQuery)->where('status', 'completed')->sum('estimated_cost_usd');

        return [
            'period_ym' => $period,
            'allocated_credits' => (int) $ledger->allocated_credits,
            'bonus_credits' => (int) $ledger->bonus_credits,
            'total_credits' => $ledger->totalCredits(),
            'used_credits' => (int) $ledger->used_credits,
            'reserved_credits' => (int) $ledger->reserved_credits,
            'remaining_credits' => $ledger->remainingCredits(),
            'requests_count' => $requestsCount,
            'completed_requests' => $completedRequests,
            'refunded_requests' => $refundedRequests,
            'total_tokens' => $totalTokens,
            'estimated_cost_usd' => round($estimatedCostUsd, 4),
        ];
    }

    /**
     * Resolve default credit cost configured for a feature key.
     */
    public function featureCreditCost(string $feature): int
    {
        return max(1, (int) config("ai.feature_credits.{$feature}", 1));
    }

    /**
     * Return active billing period key (YYYY-MM).
     */
    public function currentPeriodYm(): string
    {
        return now()->format('Y-m');
    }
}
