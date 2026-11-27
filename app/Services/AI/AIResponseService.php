<?php

namespace App\Services\AI;

use Illuminate\Support\Str;

class AIResponseService
{
    /**
     * Extract and validate a JSON object from model text output.
     *
     * @return array<string, mixed>
     *
     * @throws AIProviderException
     */
    public function extractJsonObject(string $rawText): array
    {
        $clean = trim($rawText);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $clean, $matches) === 1) {
            $clean = trim($matches[1]);
        }

        if ($clean === '') {
            throw new AIProviderException(
                'invalid_response',
                502,
                'The AI provider returned an empty structured response. Please try again.'
            );
        }

        $decoded = json_decode($clean, true);

        if (! is_array($decoded)) {
            $firstBrace = strpos($clean, '{');
            $lastBrace = strrpos($clean, '}');
            if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                $slice = substr($clean, $firstBrace, ($lastBrace - $firstBrace) + 1);
                $decoded = json_decode($slice, true);
            }
        }

        if (! is_array($decoded)) {
            throw new AIProviderException(
                'invalid_response',
                502,
                'The AI provider returned malformed JSON output. Please try again.'
            );
        }

        return $decoded;
    }

    /**
     * Assert that every expected key exists in a decoded structured payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $requiredKeys
     * @return array<string, mixed>
     *
     * @throws AIProviderException
     */
    public function validateRequiredKeys(array $payload, array $requiredKeys): array
    {
        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $payload)) {
                throw new AIProviderException(
                    'invalid_response',
                    502,
                    "The AI structured response is missing required field '{$key}'."
                );
            }
        }

        return $payload;
    }

    /**
     * Redact API keys, Bearer tokens, and HTML noise before logging or returning error details.
     *
     * @param  array<int, string>  $secrets
     */
    public function sanitizeErrorMessage(string $message, array $secrets = []): string
    {
        $clean = strip_tags($message);

        foreach ($secrets as $secret) {
            if ($secret !== '') {
                $clean = str_replace($secret, '[REDACTED]', $clean);
            }
        }

        $clean = preg_replace('/AIza[0-9A-Za-z\-_]{20,}/', '[REDACTED_KEY]', $clean) ?? $clean;
        $clean = preg_replace('/sk-[0-9A-Za-z\-_]{16,}/', '[REDACTED_KEY]', $clean) ?? $clean;
        $clean = preg_replace('/Bearer\s+[0-9A-Za-z\-_\.]+/i', 'Bearer [REDACTED]', $clean) ?? $clean;
        $clean = preg_replace('/\s+/', ' ', trim($clean)) ?? trim($clean);

        if ($clean === '') {
            return 'Unexpected AI service error occurred.';
        }

        return Str::limit($clean, 300);
    }
}
