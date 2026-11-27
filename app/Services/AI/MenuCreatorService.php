<?php

namespace App\Services\AI;

use App\Models\AiGeneratedContent;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Services\AI\Data\AIRequest;
use Illuminate\Support\Facades\DB;

class MenuCreatorService
{
    public function __construct(
        private readonly AIManager $aiManager,
        private readonly AIContextService $contextService,
        private readonly AIUsageService $usageService,
        private readonly BusinessMetricsQueryService $metricsQueryService,
    ) {}

    /**
     * Generate bilingual menu item copy, translations, or combo/pricing suggestions.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws AIProviderException
     */
    public function generate(
        Business $business,
        User $user,
        array $input,
        ?string $requestUuid = null
    ): AiGeneratedContent {
        $action = (string) ($input['action'] ?? 'description');
        $product = null;
        if (filled($input['product_id'] ?? null)) {
            $product = Product::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->whereKey($input['product_id'])
                ->first();
        }

        $itemName = trim((string) ($input['item_name'] ?? ($product?->name_en ?? '')));
        if ($itemName === '' && $action !== 'combos_and_pricing') {
            throw new AIProviderException('invalid_request', 422, 'Select an existing product or enter a product/dish name.');
        }

        $featureSettings = $this->usageService->getOrCreateFeatureSetting($business);
        $language = (string) ($input['target_language'] ?? $featureSettings->preferred_language ?? 'auto');
        if (! in_array($language, ['auto', 'km', 'en', 'bilingual'], true)) {
            $language = 'bilingual';
        }
        $tone = (string) ($input['tone'] ?? $featureSettings->preferred_tone ?? 'friendly');
        $compactContext = $this->contextService->buildCompactBusinessContext($business);

        $storeAnchors = [];
        if ($action === 'combos_and_pricing') {
            $storeAnchors = [
                'top_sellers' => $this->metricsQueryService->getTopSellingProducts($business, 30, 6),
                'catalog_price_band' => $this->catalogPriceBand($business),
            ];
        }

        $userPromptPayload = [
            'action' => $action,
            'language_preference' => $language,
            'tone' => $tone,
            'item_name_en' => $product?->name_en ?: $itemName,
            'item_name_km' => $product?->name_km ?: ($input['item_name_km'] ?? ''),
            'current_description_en' => $product?->description_en ?: ($input['notes'] ?? ''),
            'current_description_km' => $product?->description_km ?? '',
            'category' => $product?->category?->name_en ?: ($input['category_name'] ?? ''),
            'price_usd' => $product !== null ? (float) $product->base_price : (float) ($input['price_usd'] ?? 0),
            'notes_or_ingredients' => (string) ($input['notes'] ?? ''),
            'store_context' => $compactContext,
            'store_anchors' => $storeAnchors,
        ];

        $systemPrompt = implode("\n\n", [
            'You are SABAYRA AI Menu & Catalog Expert specializing in Cambodian restaurants, cafés, bakeries, milk-tea shops, and retail stores.',
            'Write appetizing, authentic, clear copy tailored to Cambodian customers in both natural Khmer (ភាសាខ្មែរ) and English.',
            'Respect the configured tone, never fabricate dietary claims unless mentioned in notes, and keep Khmer phrasing natural rather than awkward literal word-for-word translation.',
            'Return strict JSON matching the requested schema.',
        ]);

        $schema = [
            'type' => 'object',
            'properties' => [
                'name_en' => ['type' => 'string'],
                'name_km' => ['type' => 'string'],
                'description_en' => ['type' => 'string'],
                'description_km' => ['type' => 'string'],
                'marketing_hook' => ['type' => 'string'],
                'suggestions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'title_km' => ['type' => 'string'],
                            'suggested_price_usd' => ['type' => 'number'],
                            'rationale' => ['type' => 'string'],
                        ],
                        'required' => ['title', 'title_km', 'suggested_price_usd', 'rationale'],
                    ],
                ],
            ],
            'required' => ['name_en', 'name_km', 'description_en', 'description_km', 'marketing_hook', 'suggestions'],
        ];

        $response = $this->aiManager->complete($business, new AIRequest(
            feature: 'menu_creator',
            systemPrompt: $systemPrompt,
            messages: [
                ['role' => 'user', 'content' => json_encode($userPromptPayload, JSON_UNESCAPED_UNICODE)],
            ],
            responseSchema: $schema,
            responseSchemaName: 'sabayra_menu_creator_output',
            requestUuid: $requestUuid,
        ), $user->id);

        $structured = $response->structuredData;
        $summaryLines = array_filter([
            filled($structured['name_km'] ?? null) ? "ខ្មែរ: {$structured['name_km']}" : null,
            filled($structured['name_en'] ?? null) ? "EN: {$structured['name_en']}" : null,
            filled($structured['description_km'] ?? null) ? (string) $structured['description_km'] : null,
            filled($structured['description_en'] ?? null) ? (string) $structured['description_en'] : null,
        ]);

        return AiGeneratedContent::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'feature' => 'menu_creator',
            'content_type' => $action,
            'platform' => 'menu',
            'product_id' => $product?->id,
            'language' => $language,
            'tone' => $tone,
            'title' => $product?->name_en ?: ($itemName !== '' ? $itemName : 'Combo & Menu Ideas'),
            'input_payload' => $userPromptPayload,
            'output_text' => implode("\n", $summaryLines),
            'structured_output' => $structured,
            'status' => 'draft',
            'usage_log_id' => $response->usageLogId,
        ]);
    }

    /**
     * Apply explicitly selected generated copy fields onto an existing business product.
     *
     * @param  array<string, bool>  $fields
     */
    public function applyToProduct(
        Business $business,
        AiGeneratedContent $content,
        Product $product,
        array $fields
    ): Product {
        if ($content->business_id !== $business->id || $product->business_id !== $business->id) {
            abort(403, 'Cross-tenant menu modification blocked.');
        }

        $structured = is_array($content->structured_output) ? $content->structured_output : [];
        $updates = [];

        if (($fields['name_en'] ?? false) && filled($structured['name_en'] ?? null)) {
            $updates['name_en'] = trim((string) $structured['name_en']);
        }
        if (($fields['name_km'] ?? false) && filled($structured['name_km'] ?? null)) {
            $updates['name_km'] = trim((string) $structured['name_km']);
        }
        if (($fields['description_en'] ?? false) && filled($structured['description_en'] ?? null)) {
            $updates['description_en'] = trim((string) $structured['description_en']);
        }
        if (($fields['description_km'] ?? false) && filled($structured['description_km'] ?? null)) {
            $updates['description_km'] = trim((string) $structured['description_km']);
        }

        DB::transaction(function () use ($product, $content, $updates): void {
            if ($updates !== []) {
                $product->update($updates);
            }
            $content->update([
                'product_id' => $product->id,
                'status' => 'applied',
            ]);
        });

        return $product->fresh();
    }

    /**
     * Compute real store catalog price bounds to ground combo/pricing recommendations.
     *
     * @return array<string, mixed>
     */
    private function catalogPriceBand(Business $business): array
    {
        $products = Product::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_available', true)
            ->get(['base_price']);

        if ($products->isEmpty()) {
            return ['count' => 0, 'min_usd' => 0, 'avg_usd' => 0, 'max_usd' => 0];
        }

        return [
            'count' => $products->count(),
            'min_usd' => round((float) $products->min('base_price'), 2),
            'avg_usd' => round((float) $products->avg('base_price'), 2),
            'max_usd' => round((float) $products->max('base_price'), 2),
        ];
    }
}
