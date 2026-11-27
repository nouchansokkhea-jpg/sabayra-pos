<?php

namespace App\Services\AI;

use App\Models\AiGeneratedContent;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Services\AI\Data\AIRequest;

class MarketingStudioService
{
    public function __construct(
        private readonly AIManager $aiManager,
        private readonly AIContextService $contextService,
        private readonly AIUsageService $usageService,
    ) {}

    /**
     * Generate localized Cambodian marketing content (Facebook, TikTok, Telegram, Holiday campaigns).
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
        $channel = (string) ($input['channel'] ?? 'facebook');
        $campaignType = (string) ($input['campaign_type'] ?? 'product_spotlight');
        $language = (string) ($input['target_language'] ?? 'bilingual');
        $tone = (string) ($input['tone'] ?? 'friendly');

        $product = null;
        if (filled($input['product_id'] ?? null)) {
            $product = Product::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->whereKey($input['product_id'])
                ->first();
        }

        $compactContext = $this->contextService->buildCompactBusinessContext($business);
        $rate = (float) $compactContext['exchange_rate_khr'];

        $productSummary = $product ? [
            'name_en' => $product->name_en,
            'name_km' => $product->name_km,
            'description_en' => $product->description_en,
            'description_km' => $product->description_km,
            'price_usd' => round((float) $product->effective_price, 2),
            'price_khr' => round((float) $product->effective_price * $rate, 0),
        ] : null;

        $promptInput = [
            'channel' => $channel,
            'campaign_type' => $campaignType,
            'target_language' => $language,
            'tone' => $tone,
            'occasion' => trim((string) ($input['occasion'] ?? '')),
            'offer_details' => trim((string) ($input['offer_details'] ?? '')),
            'product' => $productSummary,
            'business' => $compactContext,
        ];

        $systemPrompt = implode("\n\n", [
            'You are SABAYRA AI Marketing Strategist for Cambodian restaurants, cafés, bakeries, milk-tea shops, and retail businesses.',
            'Create ready-to-publish social media content crafted specifically for Cambodian digital habits (Facebook Pages, TikTok/Reels short video scripts, Telegram customer broadcasts).',
            'When product pricing is supplied, display accurate USD ($) and KHR (៛) values. Never fabricate fake discounts or promotions not provided in offer_details.',
            'Return strict JSON adhering to the required schema.',
        ]);

        $schema = [
            'type' => 'object',
            'properties' => [
                'headline_km' => ['type' => 'string'],
                'headline_en' => ['type' => 'string'],
                'post_body_km' => ['type' => 'string'],
                'post_body_en' => ['type' => 'string'],
                'video_script' => ['type' => 'string'],
                'cta_text' => ['type' => 'string'],
                'hashtags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'suggested_promo_schedule' => ['type' => 'string'],
            ],
            'required' => [
                'headline_km',
                'headline_en',
                'post_body_km',
                'post_body_en',
                'video_script',
                'cta_text',
                'hashtags',
                'suggested_promo_schedule',
            ],
        ];

        $response = $this->aiManager->complete($business, new AIRequest(
            feature: 'marketing_studio',
            systemPrompt: $systemPrompt,
            messages: [
                ['role' => 'user', 'content' => json_encode($promptInput, JSON_UNESCAPED_UNICODE)],
            ],
            responseSchema: $schema,
            responseSchemaName: 'sabayra_marketing_studio_output',
            requestUuid: $requestUuid,
        ), $user->id);

        $structured = $response->structuredData;
        $titleSource = $product?->name_en
            ?: (filled($input['occasion'] ?? null) ? (string) $input['occasion'] : ucwords(str_replace('_', ' ', $campaignType)));

        $combinedText = trim(implode("\n\n", array_filter([
            $structured['headline_km'] ?? null,
            $structured['post_body_km'] ?? null,
            $structured['post_body_en'] ?? null,
        ])));

        return AiGeneratedContent::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'feature' => 'marketing_studio',
            'content_type' => $campaignType,
            'platform' => $channel,
            'product_id' => $product?->id,
            'language' => $language,
            'tone' => $tone,
            'title' => $titleSource,
            'input_payload' => $promptInput,
            'output_text' => $combinedText,
            'structured_output' => $structured,
            'status' => 'draft',
            'usage_log_id' => $response->usageLogId,
        ]);
    }
}
