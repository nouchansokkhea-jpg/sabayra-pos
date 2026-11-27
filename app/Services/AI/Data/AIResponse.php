<?php

namespace App\Services\AI\Data;

class AIResponse
{
    /**
     * Construct a normalized AI provider response payload.
     *
     * @param  array<string, mixed>  $structuredData
     * @param  array<int, array{id: string, name: string, arguments: array<string, mixed>}>  $toolCalls
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly string $text,
        public readonly array $structuredData = [],
        public readonly array $toolCalls = [],
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly int $totalTokens = 0,
        public readonly int $latencyMs = 0,
        public readonly float $estimatedCostUsd = 0.0,
        public readonly ?string $usageLogId = null,
        public readonly ?string $finishReason = null,
    ) {}

    /**
     * Determine whether the model requested one or more tool executions.
     */
    public function hasToolCalls(): bool
    {
        return $this->toolCalls !== [];
    }

    /**
     * Return a copy with aggregated metering metadata attached.
     */
    public function withMetering(
        int $promptTokens,
        int $completionTokens,
        int $totalTokens,
        int $latencyMs,
        float $estimatedCostUsd,
        ?string $usageLogId = null
    ): self {
        return new self(
            provider: $this->provider,
            model: $this->model,
            text: $this->text,
            structuredData: $this->structuredData,
            toolCalls: $this->toolCalls,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            totalTokens: $totalTokens,
            latencyMs: $latencyMs,
            estimatedCostUsd: $estimatedCostUsd,
            usageLogId: $usageLogId ?? $this->usageLogId,
            finishReason: $this->finishReason,
        );
    }
}
