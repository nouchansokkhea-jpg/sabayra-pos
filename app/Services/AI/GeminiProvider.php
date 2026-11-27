<?php

namespace App\Services\AI;

use App\Services\AI\Data\AIRequest;
use App\Services\AI\Data\AIResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use stdClass;

class GeminiProvider implements AIProviderInterface
{
    public function __construct(
        private readonly AIResponseService $responseService
    ) {}

    /**
     * {@inheritDoc}
     */
    public function name(): string
    {
        return 'gemini';
    }

    /**
     * {@inheritDoc}
     */
    public function complete(AIRequest $request, array $runtimeConfig): AIResponse
    {
        $apiKey = (string) ($runtimeConfig['api_key'] ?? '');
        $model = (string) ($runtimeConfig['model'] ?? config('ai.providers.gemini.default_model', 'gemini-2.5-flash'));
        $baseUrl = rtrim((string) ($runtimeConfig['base_url'] ?? config('ai.providers.gemini.base_url')), '/');
        $timeoutSeconds = max(5, (int) ($runtimeConfig['timeout_seconds'] ?? 30));
        $maxAttempts = max(1, min(4, ((int) ($runtimeConfig['retry_attempts'] ?? 1)) + 1));
        $verifySsl = (bool) ($runtimeConfig['verify_ssl'] ?? true);

        if ($apiKey === '') {
            throw new AIProviderException('missing_api_key', 400, 'Google Gemini API key is not configured.');
        }

        $endpoint = "{$baseUrl}/models/{$model}:generateContent";
        $payload = $this->buildPayload($request, $runtimeConfig);

        $startedAt = hrtime(true);
        $httpResponse = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $pending = Http::timeout($timeoutSeconds)
                    ->acceptJson()
                    ->asJson()
                    ->withHeaders([
                        'x-goog-api-key' => $apiKey,
                    ]);

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
                        ? 'Google Gemini request timed out. Please try again.'
                        : 'Unable to reach Google Gemini service right now. Please try again shortly.',
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
            throw new AIProviderException('invalid_response', 502, 'Google Gemini returned an unreadable response payload.');
        }

        $candidate = $data['candidates'][0] ?? null;
        if (! is_array($candidate)) {
            throw new AIProviderException('invalid_response', 502, 'Google Gemini returned no response candidate.');
        }

        $parts = $candidate['content']['parts'] ?? [];
        if (! is_array($parts)) {
            $parts = [];
        }

        $textChunks = [];
        $toolCalls = [];

        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }
            if (isset($part['text']) && is_string($part['text'])) {
                $textChunks[] = $part['text'];
            }
            if (isset($part['functionCall']) && is_array($part['functionCall'])) {
                $name = (string) ($part['functionCall']['name'] ?? '');
                if ($name !== '') {
                    $args = $part['functionCall']['args'] ?? [];
                    $toolCalls[] = [
                        'id' => 'gemini_call_' . Str::random(10),
                        'name' => $name,
                        'arguments' => is_array($args) ? $args : [],
                    ];
                }
            }
        }

        $text = trim(implode("\n", $textChunks));
        $structuredData = [];

        if ($request->responseSchema !== null && $toolCalls === []) {
            $structuredData = $this->responseService->extractJsonObject($text);
        } elseif ($text === '' && $toolCalls === []) {
            throw new AIProviderException('invalid_response', 502, 'Google Gemini returned empty content.');
        }

        $usage = is_array($data['usageMetadata'] ?? null) ? $data['usageMetadata'] : [];
        $promptTokens = (int) ($usage['promptTokenCount'] ?? 0);
        $completionTokens = (int) ($usage['candidatesTokenCount'] ?? 0);
        $totalTokens = (int) ($usage['totalTokenCount'] ?? ($promptTokens + $completionTokens));

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
            finishReason: isset($candidate['finishReason']) ? (string) $candidate['finishReason'] : null,
        );
    }

    /**
     * Build a Gemini v1beta generateContent request body.
     *
     * @param  array<string, mixed>  $runtimeConfig
     * @return array<string, mixed>
     */
    protected function buildPayload(AIRequest $request, array $runtimeConfig): array
    {
        $temperature = (float) ($runtimeConfig['temperature'] ?? 0.4);
        $maxOutputTokens = max(64, (int) ($runtimeConfig['max_output_tokens'] ?? 2048));

        $payload = [];

        if (trim($request->systemPrompt) !== '') {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $request->systemPrompt],
                ],
            ];
        }

        $contents = [];
        $pendingToolParts = [];

        $flushToolParts = static function () use (&$contents, &$pendingToolParts): void {
            if ($pendingToolParts !== []) {
                $contents[] = [
                    'role' => 'user',
                    'parts' => $pendingToolParts,
                ];
                $pendingToolParts = [];
            }
        };

        foreach ($request->messages as $message) {
            $role = (string) ($message['role'] ?? 'user');

            if ($role === 'tool') {
                $toolName = (string) ($message['tool_name'] ?? 'tool');
                $rawContent = $message['content'] ?? '';
                $decoded = is_string($rawContent) ? json_decode($rawContent, true) : $rawContent;
                $responseValue = is_array($decoded) ? $decoded : ['result' => (string) $rawContent];

                $pendingToolParts[] = [
                    'functionResponse' => [
                        'name' => $toolName,
                        'response' => $responseValue,
                    ],
                ];
                continue;
            }

            $flushToolParts();

            $parts = [];
            $text = (string) ($message['content'] ?? '');
            if ($text !== '') {
                $parts[] = ['text' => $text];
            }

            if ($role === 'assistant' && ! empty($message['tool_calls']) && is_array($message['tool_calls'])) {
                foreach ($message['tool_calls'] as $call) {
                    $callName = (string) ($call['name'] ?? '');
                    if ($callName === '') {
                        continue;
                    }
                    $args = $call['arguments'] ?? [];
                    $parts[] = [
                        'functionCall' => [
                            'name' => $callName,
                            'args' => empty($args) ? new stdClass() : $args,
                        ],
                    ];
                }
            }

            if ($parts !== []) {
                $contents[] = [
                    'role' => $role === 'assistant' ? 'model' : 'user',
                    'parts' => $parts,
                ];
            }
        }

        $flushToolParts();

        $payload['contents'] = $contents;

        if ($request->tools !== []) {
            $declarations = [];
            foreach ($request->tools as $tool) {
                $declarations[] = [
                    'name' => (string) $tool['name'],
                    'description' => (string) ($tool['description'] ?? ''),
                    'parameters' => $this->toGeminiSchema((array) ($tool['parameters'] ?? ['type' => 'object', 'properties' => []])),
                ];
            }
            $payload['tools'] = [
                ['functionDeclarations' => $declarations],
            ];
        }

        $generationConfig = [
            'temperature' => $temperature,
            'maxOutputTokens' => $maxOutputTokens,
        ];

        if ($request->responseSchema !== null && $request->tools === []) {
            $generationConfig['responseMimeType'] = 'application/json';
            $generationConfig['responseSchema'] = $this->toGeminiSchema($request->responseSchema);
        }

        $payload['generationConfig'] = $generationConfig;

        return $payload;
    }

    /**
     * Convert standard JSON Schema to Gemini uppercase OpenAPI schema subset.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    protected function toGeminiSchema(array $schema): array
    {
        $converted = [];

        if (isset($schema['type'])) {
            $type = $schema['type'];
            if (is_array($type)) {
                $nonNull = array_values(array_filter($type, static fn ($item): bool => $item !== 'null'));
                $type = $nonNull[0] ?? 'string';
            }
            $converted['type'] = strtoupper((string) $type);
        }

        if (isset($schema['description'])) {
            $converted['description'] = (string) $schema['description'];
        }

        if (isset($schema['enum']) && is_array($schema['enum'])) {
            $converted['enum'] = array_values($schema['enum']);
        }

        if (isset($schema['nullable'])) {
            $converted['nullable'] = (bool) $schema['nullable'];
        }

        if (array_key_exists('properties', $schema) && is_array($schema['properties'])) {
            if ($schema['properties'] === []) {
                $converted['properties'] = new stdClass();
            } else {
                $props = [];
                foreach ($schema['properties'] as $propName => $propSchema) {
                    if (is_array($propSchema)) {
                        $props[$propName] = $this->toGeminiSchema($propSchema);
                    }
                }
                $converted['properties'] = $props;
            }
        }

        if (isset($schema['required']) && is_array($schema['required'])) {
            $converted['required'] = array_values($schema['required']);
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            $converted['items'] = $this->toGeminiSchema($schema['items']);
        }

        return $converted;
    }

    /**
     * Map Gemini HTTP failures into clean AIProviderException instances with secret redaction.
     *
     * @throws AIProviderException
     */
    protected function throwForHttpFailure(?Response $response, string $apiKey): never
    {
        if ($response === null) {
            throw new AIProviderException('provider_outage', 503, 'Google Gemini did not respond.');
        }

        $status = $response->status();
        $rawError = (string) ($response->json('error.message') ?? $response->body());
        $cleanError = $this->responseService->sanitizeErrorMessage($rawError, [$apiKey]);
        $upper = strtoupper($cleanError . ' ' . $this->responseService->sanitizeErrorMessage($response->body(), [$apiKey]));

        if ($status === 401 || $status === 403 || str_contains($upper, 'API_KEY_INVALID') || str_contains($upper, 'API KEY NOT VALID')) {
            throw new AIProviderException(
                'invalid_api_key',
                401,
                'Google Gemini API key was rejected. Please verify the configured key in Platform Settings.'
            );
        }

        if ($status === 429) {
            throw new AIProviderException(
                'rate_limit',
                429,
                'Google Gemini rate limit reached. Please wait a moment and try again.'
            );
        }

        if ($status >= 500) {
            throw new AIProviderException(
                'provider_outage',
                503,
                'Google Gemini service is temporarily unavailable. Please try again later.'
            );
        }

        throw new AIProviderException(
            'invalid_request',
            422,
            "Google Gemini rejected the request: {$cleanError}"
        );
    }
}
