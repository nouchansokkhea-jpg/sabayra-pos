<?php

namespace App\Services\AI\Data;

class AIRequest
{
    /**
     * Construct a provider-agnostic AI request payload.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     * @param  array<string, mixed>|null  $responseSchema
     */
    public function __construct(
        public readonly string $feature,
        public readonly string $systemPrompt,
        public readonly array $messages,
        public readonly array $tools = [],
        public readonly ?array $responseSchema = null,
        public readonly ?string $responseSchemaName = null,
        public readonly ?float $temperature = null,
        public readonly ?int $maxTokens = null,
        public readonly ?string $provider = null,
        public readonly ?string $model = null,
        public readonly ?string $requestUuid = null,
        public readonly ?int $creditsCost = null,
    ) {}

    /**
     * Clone the request with updated conversation messages while preserving configuration.
     *
     * @param  array<int, array<string, mixed>>  $messages
     */
    public function withMessages(array $messages, ?array $tools = null, ?array $responseSchema = null): self
    {
        return new self(
            feature: $this->feature,
            systemPrompt: $this->systemPrompt,
            messages: $messages,
            tools: $tools ?? $this->tools,
            responseSchema: $responseSchema ?? $this->responseSchema,
            responseSchemaName: $this->responseSchemaName,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            provider: $this->provider,
            model: $this->model,
            requestUuid: $this->requestUuid,
            creditsCost: $this->creditsCost,
        );
    }
}
