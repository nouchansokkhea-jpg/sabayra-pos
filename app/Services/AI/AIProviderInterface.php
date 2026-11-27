<?php

namespace App\Services\AI;

use App\Services\AI\Data\AIRequest;
use App\Services\AI\Data\AIResponse;

interface AIProviderInterface
{
    /**
     * Return the canonical lowercase provider key (e.g. 'gemini', 'openai').
     */
    public function name(): string;

    /**
     * Execute a single completion or tool-calling turn against the AI provider API.
     *
     * @param  array{
     *     api_key: string,
     *     model: string,
     *     base_url: string,
     *     temperature: float,
     *     max_output_tokens: int,
     *     timeout_seconds: int,
     *     retry_attempts: int,
     *     verify_ssl: bool
     * }  $runtimeConfig
     *
     * @throws AIProviderException
     */
    public function complete(AIRequest $request, array $runtimeConfig): AIResponse;
}
