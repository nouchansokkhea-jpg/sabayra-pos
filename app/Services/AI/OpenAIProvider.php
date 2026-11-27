<?php

namespace App\Services\AI;

use App\Services\AI\Data\AIRequest;
use App\Services\AI\Data\AIResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use stdClass;

class OpenAIProvider implements AIProviderInterface
{
    public function __construct(
        private readonly AIResponseService $responseService
    ) {}

    /**
     * {@inheritDoc}
     */
    public function name(): string
    {
        return 'openai';
    }

    /**
     * {@inheritDoc}
     */
    public function complete(AIRequest $request, array $runtimeConfig): AIResponse
    {
        $apiKey = (string) ($runtimeConfig['api_key'] ?? '');
        $model = (string) ($runtimeConfig['model'] ?? config('ai.providers.openai.default_model', 'gpt-4o-mini'));
        $baseUrl = rtrim((string) ($runtimeConfig['base_url'] ?? config('ai.providers.openai.base_url')), '/');
        $timeoutSeconds = max(5, (int) ($runtimeConfig['timeout_seconds'] ?? 30));
        $maxAttempts = max(1, min(4, ((int) ($runtimeConfig['retry_attempts'] ?? 1)) + 1));
        $verifySsl = (bool) ($runtimeConfig['verify_ssl'] ?? true);

        if ($apiKey === '') {
            throw new AIProviderException('missing_api_key', 400, 'OpenAI API key is not configured.');
        }

        $endpoint = "{$baseUrl}/chat/completions";
        $payload = $this->buildPayload($request, $model, $runtimeConfig);

        $startedAt = hrtime(true);
        $httpResponse = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $pending = Http::timeout($timeoutSeconds)
                    ->acceptJson()
                    ->asJson()
                    ->withToken($apiKey);

                if (! $verifySsl) {
                    $pending = $pending->withoutVerifying();
                }

                /** @var Response $httpResponse */
                $httpResponse = $pending->post($endpoint, $payload);

                if ($httpResponse->successful()) {
                    break;
                }

                if (in_array($httpResponse->status(), [429, 500, 502, 503, 504], true) && $attempt < $maxAttempts) {
                    if (! app()->runningUnitTests()) {
                        usleep(200000 * $attempt);
                    }
                    continue;
                }

                $this->throwForHttpFailure($httpResponse, $apiKey);
            } catch (ConnectionException $exception) {
                if ($attempt < $maxAttempts) {
                    if (! app()->runningUnitTests()) {
                        usleep(200000 * $attempt);
                    }
                    continue;
                }

                $lower = strtolower($exception->getMessage());
                $isTimeout = str_contains($lower, 'timed out') || str_contains($lower, 'timeout');
                throw new AIProviderException(
                    $isTimeout ? 'timeout' : 'provider_outage',
                    $isTimeout ? 504 : 503,
                    $isTimeout
                        ? 'OpenAI request timed out. Please try again.'
                        : 'Unable to reach OpenAI service right now. Please try again shortly.',
                    $exception
                );
            }
        }

        if ($httpResponse === null || ! $httpResponse->successful()) {
            $this->throwForHttpFailure($httpResponse, $apiKey);
        }

        $latencyMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);
        $data = $httpResponse->json();

        if (! is_array($data)) {
            throw new AIProviderException('invalid_response', 502, 'OpenAI returned an unreadable response payload.');
        }

        $choice = $data['choices'][0] ?? null;
        if (! is_array($choice) || ! is_array($choice['message'] ?? null)) {
            throw new AIProviderException('invalid_response', 502, 'OpenAI returned no message choice.');
        }

        $message = $choice['message'];
        $text = trim((string) ($message['content'] ?? ''));

        $toolCalls = [];
        if (! empty($message['tool_calls']) && is_array($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $call) {
                $function = $call['function'] ?? [];
                $name = (string) ($function['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                $rawArgs = $function['arguments'] ?? '{}';
                $decodedArgs = is_string($rawArgs) ? json_decode($rawArgs, true) : $rawArgs;

                $toolCalls[] = [
                    'id' => (string) ($call['id'] ?? ('call_' . uniqid())),
                    'name' => $name,
                    'arguments' => is_array($decodedArgs) ? $decodedArgs : [],
                ];
            }
        }

        $structuredData = [];
        if ($request->responseSchema !== null && $toolCalls === []) {
            $structuredData = $this->responseService->extractJsonObject($text);
        } elseif ($text === '' && $toolCalls === []) {
            throw new AIProviderException('invalid_response', 502, 'OpenAI returned empty content.');
        }

        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : [];
        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);
        $totalTokens = (int) ($usage['total_tokens'] ?? ($promptTokens + $completionTokens));

        return new AIResponse(
            provider: $this->name(),
            model: $model,
            text: $text,
            structuredData: $structuredData,
            toolCalls: $toolCalls,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            totalTokens: $totalTokens,
            latencyMs: $latencyMs,
            finishReason: isset($choice['finish_reason']) ? (string) $choice['finish_reason'] : null,
        );
    }

    /**
     * Build an OpenAI Chat Completions payload with tools or strict json_schema.
     *
     * @param  array<string, mixed>  $runtimeConfig
     * @return array<string, mixed>
     */
    protected function buildPayload(AIRequest $request, string $model, array $runtimeConfig): array
    {
        $messages = [];

        if (trim($request->systemPrompt) !== '') {
            $messages[] = [
                'role' => 'system',
                'content' => $request->systemPrompt,
            ];
        }

        foreach ($request->messages as $msg) {
            $role = (string) ($msg['role'] ?? 'user');

            if ($role === 'tool') {
                $rawContent = $msg['content'] ?? '';
                $content = is_string($rawContent) ? $rawContent : json_encode($rawContent, JSON_UNESCAPED_UNICODE);
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => (string) ($msg['tool_call_id'] ?? 'call_default'),
                    'content' => (string) $content,
                ];
                continue;
            }

            $entry = [
                'role' => $role === 'assistant' ? 'assistant' : 'user',
                'content' => ($msg['content'] ?? '') === '' && ! empty($msg['tool_calls']) ? null : (string) ($msg['content'] ?? ''),
            ];

            if ($role === 'assistant' && ! empty($msg['tool_calls']) && is_array($msg['tool_calls'])) {
                $entry['tool_calls'] = array_map(static function (array $call): array {
                    $args = $call['arguments'] ?? [];

                    return [
                        'id' => (string) ($call['id'] ?? 'call_default'),
                        'type' => 'function',
                        'function' => [
                            'name' => (string) ($call['name'] ?? ''),
                            'arguments' => empty($args) ? '{}' : json_encode($args, JSON_UNESCAPED_UNICODE),
                        ],
                    ];
                }, $msg['tool_calls']);
            }

            $messages[] = $entry;
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => (float) ($runtimeConfig['temperature'] ?? 0.4),
            'max_completion_tokens' => max(64, (int) ($runtimeConfig['max_output_tokens'] ?? 2048)),
        ];

        if ($request->tools !== []) {
            $payload['tools'] = array_map(static fn (array $tool): array => [
                'type' => 'function',
                'function' => [
                    'name' => (string) $tool['name'],
                    'description' => (string) ($tool['description'] ?? ''),
                    'parameters' => empty($tool['parameters']) ? ['type' => 'object', 'properties' => new stdClass()] : $tool['parameters'],
                ],
            ], $request->tools);
        }

        if ($request->responseSchema !== null && $request->tools === []) {
            $payload['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => $request->responseSchemaName ?: 'sabayra_structured_response',
                    'strict' => true,
                    'schema' => $this->toOpenAIStrictSchema($request->responseSchema),
                ],
            ];
        }

        return $payload;
    }

    /**
     * Normalize JSON Schema for OpenAI strict structured outputs.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    protected function toOpenAIStrictSchema(array $schema): array
    {
        $result = $schema;
        $type = $result['type'] ?? null;

        if ($type === 'object') {
            $result['additionalProperties'] = false;
            $props = is_array($result['properties'] ?? null) ? $result['properties'] : [];
            $normalizedProps = [];
            foreach ($props as $key => $propSchema) {
                $normalizedProps[$key] = is_array($propSchema)
                    ? $this->toOpenAIStrictSchema($propSchema)
                    : $propSchema;
            }
            $result['properties'] = $normalizedProps === [] ? new stdClass() : $normalizedProps;
            $result['required'] = array_keys($props);
        } elseif ($type === 'array' && is_array($result['items'] ?? null)) {
            $result['items'] = $this->toOpenAIStrictSchema($result['items']);
        }

        return $result;
    }

    /**
     * Map OpenAI HTTP failures into clean AIProviderException instances with secret redaction.
     *
     * @throws AIProviderException
     */
    protected function throwForHttpFailure(?Response $response, string $apiKey): never
    {
        if ($response === null) {
            throw new AIProviderException('provider_outage', 503, 'OpenAI did not respond.');
        }

        $status = $response->status();
        $rawError = (string) ($response->json('error.message') ?? $response->body());
        $cleanError = $this->responseService->sanitizeErrorMessage($rawError, [$apiKey]);

        if ($status === 401 || $status === 403) {
            throw new AIProviderException(
                'invalid_api_key',
                401,
                'OpenAI API key was rejected. Please verify the configured key in Platform Settings.'
            );
        }

        if ($status === 429) {
            throw new AIProviderException(
                'rate_limit',
                429,
                'OpenAI rate limit reached. Please wait a moment and try again.'
            );
        }

        if ($status >= 500) {
            throw new AIProviderException(
                'provider_outage',
                503,
                'OpenAI service is temporarily unavailable. Please try again later.'
            );
        }

        throw new AIProviderException(
            'invalid_request',
            422,
            "OpenAI rejected the request: {$cleanError}"
        );
    }
}
