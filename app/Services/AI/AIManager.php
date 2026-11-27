<?php

namespace App\Services\AI;

use App\Models\Business;
use App\Models\PlatformSetting;
use App\Services\AI\Data\AIRequest;
use App\Services\AI\Data\AIResponse;
use Throwable;

class AIManager
{
    public function __construct(
        private readonly GeminiProvider $geminiProvider,
        private readonly OpenAIProvider $openAIProvider,
        private readonly AIUsageService $usageService,
        private readonly AIResponseService $responseService,
    ) {}

    /**
     * Determine whether AI features are globally enabled across the platform.
     */
    public function isGloballyEnabled(): bool
    {
        $configEnabled = (bool) config('ai.enabled', true);
        $platformSettingEnabled = (bool) PlatformSetting::getValue('ai.enabled', true);

        return $configEnabled && $platformSettingEnabled;
    }

    /**
     * Return non-secret platform AI configuration for Super Admin UI and status badges.
     *
     * @return array<string, mixed>
     */
    public function getPlatformConfig(): array
    {
        $defaultProvider = (string) PlatformSetting::getValue(
            'ai.default_provider',
            config('ai.default_provider', 'gemini')
        );
        $defaultModel = (string) PlatformSetting::getValue(
            'ai.default_model',
            config('ai.default_model', 'gemini-2.5-flash')
        );

        return [
            'enabled' => $this->isGloballyEnabled(),
            'default_provider' => in_array($defaultProvider, ['gemini', 'openai'], true) ? $defaultProvider : 'gemini',
            'default_model' => $defaultModel,
            'temperature' => (float) PlatformSetting::getValue('ai.temperature', config('ai.temperature', 0.4)),
            'max_output_tokens' => (int) PlatformSetting::getValue('ai.max_output_tokens', config('ai.max_output_tokens', 2048)),
            'timeout_seconds' => (int) PlatformSetting::getValue('ai.timeout_seconds', config('ai.timeout_seconds', 30)),
            'retry_attempts' => (int) PlatformSetting::getValue('ai.retry_attempts', config('ai.retry_attempts', 2)),
            'default_monthly_credits' => (int) PlatformSetting::getValue('ai.default_monthly_credits', config('ai.default_monthly_credits', 100)),
            'allow_business_override' => (bool) PlatformSetting::getValue('ai.allow_business_override', config('ai.allow_business_override', false)),
            'gemini_key_configured' => filled($this->resolveApiKey('gemini')),
            'openai_key_configured' => filled($this->resolveApiKey('openai')),
            'providers' => config('ai.providers', []),
        ];
    }

    /**
     * Resolve effective provider, model, and decrypted server-side API key for a business call.
     *
     * @return array{
     *     provider: string,
     *     api_key: string,
     *     model: string,
     *     base_url: string,
     *     temperature: float,
     *     max_output_tokens: int,
     *     timeout_seconds: int,
     *     retry_attempts: int,
     *     verify_ssl: bool
     * }
     *
     * @throws AIProviderException
     */
    public function resolveRuntimeForBusiness(Business $business, AIRequest $request): array
    {
        $platform = $this->getPlatformConfig();
        $entitlements = $this->usageService->resolveEntitlements($business);
        $featureSettings = $this->usageService->getOrCreateFeatureSetting($business);

        $provider = $request->provider ?: (string) $platform['default_provider'];
        $model = $request->model ?: (string) $platform['default_model'];
        $temperature = $request->temperature ?? (float) $platform['temperature'];

        if ($platform['allow_business_override'] && $request->provider === null && filled($featureSettings->provider_override)) {
            $provider = (string) $featureSettings->provider_override;
        }
        if ($platform['allow_business_override'] && $request->model === null && filled($featureSettings->model_override)) {
            $model = (string) $featureSettings->model_override;
        }
        if ($request->temperature === null && $featureSettings->temperature !== null) {
            $temperature = (float) $featureSettings->temperature;
        }

        if (! in_array($provider, ['gemini', 'openai'], true)) {
            throw new AIProviderException('unsupported_provider', 400, "Unsupported AI provider '{$provider}'.");
        }

        $providerConfig = config("ai.providers.{$provider}", []);
        $allowedModels = array_keys((array) ($providerConfig['models'] ?? []));
        if (! in_array($model, $allowedModels, true)) {
            $model = (string) ($providerConfig['default_model'] ?? ($provider === 'openai' ? 'gpt-4o-mini' : 'gemini-2.5-flash'));
        }

        $apiKey = $this->resolveApiKey($provider);
        $planCap = max(256, (int) $entitlements['max_tokens_per_request']);
        $platformTokens = max(256, (int) $platform['max_output_tokens']);
        $requestedTokens = $request->maxTokens !== null ? max(64, $request->maxTokens) : $platformTokens;
        $effectiveMaxTokens = min($planCap, $requestedTokens);

        return [
            'provider' => $provider,
            'api_key' => $apiKey,
            'model' => $model,
            'base_url' => (string) ($providerConfig['base_url'] ?? ''),
            'temperature' => max(0.0, min(1.5, $temperature)),
            'max_output_tokens' => $effectiveMaxTokens,
            'timeout_seconds' => max(5, (int) $platform['timeout_seconds']),
            'retry_attempts' => max(0, (int) $platform['retry_attempts']),
            'verify_ssl' => (bool) ($providerConfig['verify_ssl'] ?? true),
        ];
    }

    /**
     * Execute a single metered completion request for a business tenant.
     *
     * @throws AIProviderException
     */
    public function complete(Business $business, AIRequest $request, ?int $userId = null): AIResponse
    {
        if (! $this->isGloballyEnabled()) {
            throw new AIProviderException('ai_disabled', 503, 'SABAYRA AI Center is currently disabled by platform administration.');
        }

        $this->usageService->assertFeatureAllowed($business, $request->feature);
        $runtime = $this->resolveRuntimeForBusiness($business, $request);

        if ($runtime['api_key'] === '') {
            $label = ucfirst($runtime['provider']);
            throw new AIProviderException('missing_api_key', 400, "{$label} API key is not configured yet.");
        }

        $credits = $request->creditsCost ?? $this->usageService->featureCreditCost($request->feature);
        $usageLog = $this->usageService->reserveCredit(
            $business,
            $userId,
            $request->feature,
            $runtime['provider'],
            $runtime['model'],
            $credits,
            $request->requestUuid
        );

        try {
            $response = $this->driver($runtime['provider'])->complete($request, $runtime);
            $costUsd = $this->usageService->calculateEstimatedCostUsd(
                $response->provider,
                $response->model,
                $response->promptTokens,
                $response->completionTokens
            );

            $finalResponse = $response->withMetering(
                $response->promptTokens,
                $response->completionTokens,
                $response->totalTokens,
                $response->latencyMs,
                $costUsd,
                $usageLog->id
            );

            $this->usageService->finalizeUsage($usageLog, $finalResponse);

            return $finalResponse;
        } catch (AIProviderException $exception) {
            $safeMessage = $this->responseService->sanitizeErrorMessage($exception->getMessage(), [$runtime['api_key']]);
            $this->usageService->refundReservation($usageLog, $exception->errorCode, $safeMessage);
            throw new AIProviderException($exception->errorCode, $exception->httpStatus, $safeMessage, $exception);
        } catch (Throwable $exception) {
            $safeMessage = $this->responseService->sanitizeErrorMessage($exception->getMessage(), [$runtime['api_key']]);
            $this->usageService->refundReservation($usageLog, 'unexpected_error', $safeMessage);
            throw new AIProviderException('unexpected_error', 500, 'An unexpected error occurred while processing the AI request.', $exception);
        }
    }

    /**
     * Execute a multi-turn tool-calling loop under a single credit reservation.
     *
     * @param  callable(string, array<string, mixed>): array<string, mixed>  $toolExecutor
     * @return array{
     *     response: AIResponse,
     *     executed_tools: array<int, array{name: string, arguments: array<string, mixed>, result: array<string, mixed>}>
     * }
     *
     * @throws AIProviderException
     */
    public function completeWithTools(
        Business $business,
        AIRequest $request,
        callable $toolExecutor,
        ?int $userId = null,
        int $maxRounds = 4
    ): array {
        if (! $this->isGloballyEnabled()) {
            throw new AIProviderException('ai_disabled', 503, 'SABAYRA AI Center is currently disabled by platform administration.');
        }

        $this->usageService->assertFeatureAllowed($business, $request->feature);
        $runtime = $this->resolveRuntimeForBusiness($business, $request);

        if ($runtime['api_key'] === '') {
            $label = ucfirst($runtime['provider']);
            throw new AIProviderException('missing_api_key', 400, "{$label} API key is not configured yet.");
        }

        $credits = $request->creditsCost ?? $this->usageService->featureCreditCost($request->feature);
        $usageLog = $this->usageService->reserveCredit(
            $business,
            $userId,
            $request->feature,
            $runtime['provider'],
            $runtime['model'],
            $credits,
            $request->requestUuid
        );

        $driver = $this->driver($runtime['provider']);
        $messages = $request->messages;
        $executedTools = [];
        $totalPromptTokens = 0;
        $totalCompletionTokens = 0;
        $totalTokens = 0;
        $totalLatencyMs = 0;
        $finalResponse = null;

        try {
            $loopRequest = $request;

            for ($round = 1; $round <= max(1, $maxRounds); $round++) {
                $turnResponse = $driver->complete($loopRequest->withMessages($messages), $runtime);
                $totalPromptTokens += $turnResponse->promptTokens;
                $totalCompletionTokens += $turnResponse->completionTokens;
                $totalTokens += $turnResponse->totalTokens;
                $totalLatencyMs += $turnResponse->latencyMs;

                if (! $turnResponse->hasToolCalls()) {
                    $finalResponse = $turnResponse;
                    break;
                }

                $messages[] = [
                    'role' => 'assistant',
                    'content' => $turnResponse->text,
                    'tool_calls' => $turnResponse->toolCalls,
                ];

                foreach ($turnResponse->toolCalls as $call) {
                    $toolResult = $toolExecutor($call['name'], $call['arguments']);
                    $executedTools[] = [
                        'name' => $call['name'],
                        'arguments' => $call['arguments'],
                        'result' => $toolResult,
                    ];
                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $call['id'],
                        'tool_name' => $call['name'],
                        'content' => json_encode($toolResult, JSON_UNESCAPED_UNICODE),
                    ];
                }
            }

            if ($finalResponse === null) {
                $finalResponse = $driver->complete($request->withMessages($messages, []), $runtime);
                $totalPromptTokens += $finalResponse->promptTokens;
                $totalCompletionTokens += $finalResponse->completionTokens;
                $totalTokens += $finalResponse->totalTokens;
                $totalLatencyMs += $finalResponse->latencyMs;
            }

            $costUsd = $this->usageService->calculateEstimatedCostUsd(
                $finalResponse->provider,
                $finalResponse->model,
                $totalPromptTokens,
                $totalCompletionTokens
            );

            $meteredResponse = $finalResponse->withMetering(
                $totalPromptTokens,
                $totalCompletionTokens,
                $totalTokens,
                $totalLatencyMs,
                $costUsd,
                $usageLog->id
            );

            $this->usageService->finalizeUsage($usageLog, $meteredResponse);

            return [
                'response' => $meteredResponse,
                'executed_tools' => $executedTools,
            ];
        } catch (AIProviderException $exception) {
            $safeMessage = $this->responseService->sanitizeErrorMessage($exception->getMessage(), [$runtime['api_key']]);
            $this->usageService->refundReservation($usageLog, $exception->errorCode, $safeMessage);
            throw new AIProviderException($exception->errorCode, $exception->httpStatus, $safeMessage, $exception);
        } catch (Throwable $exception) {
            $safeMessage = $this->responseService->sanitizeErrorMessage($exception->getMessage(), [$runtime['api_key']]);
            $this->usageService->refundReservation($usageLog, 'unexpected_error', $safeMessage);
            throw new AIProviderException('unexpected_error', 500, 'An unexpected error occurred during tool-assisted AI execution.', $exception);
        }
    }

    /**
     * Test provider connectivity for Super Admin without charging tenant credits.
     *
     * @return array{ok: bool, provider: string, model: string, latency_ms: int, message: string}
     */
    public function testProviderConnection(string $provider, ?string $model = null, ?string $overrideApiKey = null): array
    {
        if (! in_array($provider, ['gemini', 'openai'], true)) {
            return [
                'ok' => false,
                'provider' => $provider,
                'model' => (string) $model,
                'latency_ms' => 0,
                'message' => 'Unsupported AI provider selected.',
            ];
        }

        $providerConfig = config("ai.providers.{$provider}", []);
        $resolvedModel = filled($model)
            ? (string) $model
            : (string) ($providerConfig['default_model'] ?? ($provider === 'openai' ? 'gpt-4o-mini' : 'gemini-2.5-flash'));
        $apiKey = filled($overrideApiKey) ? trim((string) $overrideApiKey) : $this->resolveApiKey($provider);

        if ($apiKey === '') {
            return [
                'ok' => false,
                'provider' => $provider,
                'model' => $resolvedModel,
                'latency_ms' => 0,
                'message' => ucfirst($provider) . ' API key is not configured yet.',
            ];
        }

        $pingRequest = new AIRequest(
            feature: 'connection_test',
            systemPrompt: 'You are a diagnostics verifier. Reply concisely.',
            messages: [
                ['role' => 'user', 'content' => 'Reply strictly with JSON: {"status":"ok"}'],
            ],
            responseSchema: [
                'type' => 'object',
                'properties' => [
                    'status' => ['type' => 'string'],
                ],
                'required' => ['status'],
            ],
            responseSchemaName: 'sabayra_ping',
            temperature: 0.0,
            maxTokens: 64,
        );

        $runtime = [
            'provider' => $provider,
            'api_key' => $apiKey,
            'model' => $resolvedModel,
            'base_url' => (string) ($providerConfig['base_url'] ?? ''),
            'temperature' => 0.0,
            'max_output_tokens' => 64,
            'timeout_seconds' => 15,
            'retry_attempts' => 0,
            'verify_ssl' => (bool) ($providerConfig['verify_ssl'] ?? true),
        ];

        try {
            $response = $this->driver($provider)->complete($pingRequest, $runtime);

            return [
                'ok' => true,
                'provider' => $response->provider,
                'model' => $response->model,
                'latency_ms' => $response->latencyMs,
                'message' => "Connected to {$response->provider} ({$response->model}) in {$response->latencyMs}ms.",
            ];
        } catch (AIProviderException $exception) {
            return [
                'ok' => false,
                'provider' => $provider,
                'model' => $resolvedModel,
                'latency_ms' => 0,
                'message' => $this->responseService->sanitizeErrorMessage($exception->getMessage(), [$apiKey]),
            ];
        }
    }

    /**
     * Resolve provider driver instance.
     */
    protected function driver(string $provider): AIProviderInterface
    {
        return match ($provider) {
            'openai' => $this->openAIProvider,
            default => $this->geminiProvider,
        };
    }

    /**
     * Resolve server-side API key from encrypted platform settings or config/services fallback.
     */
    protected function resolveApiKey(string $provider): string
    {
        $settingKey = $provider === 'openai' ? 'ai.openai_api_key' : 'ai.gemini_api_key';
        $fromDb = PlatformSetting::getValue($settingKey);
        if (filled($fromDb)) {
            return trim((string) $fromDb);
        }

        $fromConfig = config("services.{$provider}.api_key");

        return filled($fromConfig) ? trim((string) $fromConfig) : '';
    }
}
