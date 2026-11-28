<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Business;
use App\Models\User;
use App\Services\AI\Data\AIRequest;
use App\Services\AI\Data\AIResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusinessAssistantService
{
    public function __construct(
        private readonly AIManager $aiManager,
        private readonly AIContextService $contextService,
        private readonly AIUsageService $usageService,
        private readonly BusinessMetricsQueryService $metricsQueryService,
    ) {}

    /**
     * Answer a staff/owner question grounded strictly in tenant-scoped read-only tools.
     *
     * @return array{
     *     conversation: AiConversation,
     *     user_message: AiMessage,
     *     assistant_message: AiMessage,
     *     data_sources: array<int, array<string, mixed>>
     * }
     *
     * @throws AIProviderException
     */
    public function reply(
        Business $business,
        User $user,
        string $promptText,
        ?string $conversationId = null,
        ?string $requestUuid = null
    ): array {
        $promptText = trim($promptText);
        if ($promptText === '') {
            throw new AIProviderException('invalid_request', 422, 'Please type a question for SABAYRA AI Business Assistant.');
        }

        $conversation = null;
        if (filled($conversationId)) {
            $conversation = AiConversation::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('user_id', $user->id)
                ->whereKey($conversationId)
                ->first();
        }

        $featureSettings = $this->usageService->getOrCreateFeatureSetting($business);
        $detectedLanguage = $this->contextService->detectLanguage($promptText);
        $targetLanguage = $this->contextService->resolveResponseLanguage(
            (string) ($featureSettings->preferred_language ?? 'auto'),
            $promptText
        );

        $historyMessages = [];
        if ($conversation) {
            $historyMessages = AiMessage::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('conversation_id', $conversation->id)
                ->whereIn('role', ['user', 'assistant'])
                ->latest('created_at')
                ->take(8)
                ->get()
                ->reverse()
                ->map(static fn (AiMessage $msg): array => [
                    'role' => $msg->role === 'assistant' ? 'assistant' : 'user',
                    'content' => (string) $msg->content,
                ])
                ->values()
                ->all();
        }

        $historyMessages[] = [
            'role' => 'user',
            'content' => $promptText,
        ];

        $compactContext = $this->contextService->buildCompactBusinessContext($business);
        $systemPrompt = $this->buildSystemPrompt($compactContext, $targetLanguage, (string) ($featureSettings->preferred_tone ?? 'friendly'));

        $aiRequest = new AIRequest(
            feature: 'assistant',
            systemPrompt: $systemPrompt,
            messages: $historyMessages,
            tools: $this->metricsQueryService->toolDeclarations(),
            requestUuid: $requestUuid,
        );

        $result = $this->aiManager->completeWithTools(
            $business,
            $aiRequest,
            fn (string $toolName, array $args): array => $this->metricsQueryService->executeTool($business, $toolName, $args),
            $user->id
        );

        /** @var AIResponse $response */
        $response = $result['response'];
        $executedTools = $result['executed_tools'];

        $dataSources = array_values(array_map(static fn (array $entry): array => [
            'tool' => $entry['name'],
            'arguments' => $entry['arguments'],
        ], $executedTools));

        return DB::transaction(function () use (
            $business,
            $user,
            $conversation,
            $promptText,
            $detectedLanguage,
            $targetLanguage,
            $response,
            $executedTools,
            $dataSources
        ): array {
            if (! $conversation) {
                $conversation = AiConversation::withoutGlobalScopes()->create([
                    'business_id' => $business->id,
                    'user_id' => $user->id,
                    'title' => Str::limit(preg_replace('/\s+/', ' ', $promptText) ?? $promptText, 70),
                    'preferred_language' => $targetLanguage,
                    'message_count' => 0,
                    'last_message_at' => now(),
                ]);
            }

            $userMessage = AiMessage::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'role' => 'user',
                'content' => $promptText,
                'language_detected' => $detectedLanguage,
            ]);

            $assistantMessage = AiMessage::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'role' => 'assistant',
                'content' => $response->text,
                'language_detected' => $targetLanguage,
                'tool_calls_json' => $executedTools !== [] ? array_map(static fn ($t): array => ['name' => $t['name'], 'arguments' => $t['arguments']], $executedTools) : null,
                'tool_results_json' => $executedTools !== [] ? array_map(static fn ($t): array => ['name' => $t['name'], 'result' => $t['result']], $executedTools) : null,
                'data_sources_json' => $dataSources !== [] ? $dataSources : null,
                'provider' => $response->provider,
                'model' => $response->model,
                'usage_log_id' => $response->usageLogId,
            ]);

            $conversation->update([
                'message_count' => (int) $conversation->message_count + 2,
                'last_message_at' => now(),
            ]);

            return [
                'conversation' => $conversation->fresh(),
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
                'data_sources' => $dataSources,
            ];
        });
    }

    /**
     * Construct a grounded system prompt preventing fabricated business numbers.
     *
     * @param  array<string, mixed>  $compactContext
     */
    protected function buildSystemPrompt(array $compactContext, string $language, string $tone): string
    {
        $languageInstruction = $this->contextService->formatSystemLanguageInstruction($language);
        $contextJson = json_encode($compactContext, JSON_UNESCAPED_UNICODE);

        $categoryLabel = (string) ($compactContext['business_category_label'] ?? $compactContext['business_type'] ?? 'business');
        $reportFocus = (string) ($compactContext['report_primary_focus'] ?? 'sales_overview');

        return implode("\n\n", [
            "You are SABAYRA AI Business Assistant advising a Cambodian {$categoryLabel} merchant (analytics focus: {$reportFocus}).",
            $languageInstruction,
            "Tone: {$tone}. Tailor recommendations directly to {$categoryLabel} workflows (never suggest irrelevant hospitality tables to retail/service shops or irrelevant retail shelves to restaurants), and use concise actionable bullet points.",
            'CRITICAL DATA INTEGRITY RULES:',
            '1. NEVER invent, guess, or estimate sales revenue, order counts, stock balances, expenses, or product rankings. Call the provided read-only business tools whenever the user asks about business metrics.',
            '2. If a tool returns zero records or has_data = false, state clearly and honestly that there is not enough recorded transaction/stock data yet.',
            '3. Present monetary values cleanly in USD ($) and KHR (៛) using the store exchange rate when helpful.',
            "Compact verified store baseline: {$contextJson}",
        ]);
    }
}
