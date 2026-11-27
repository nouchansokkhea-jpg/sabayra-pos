<?php

namespace Tests\Feature;

use App\Models\AiCreditLedger;
use App\Models\AiUsageLog;
use App\Models\Business;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AI\AIManager;
use App\Services\AI\AIProviderException;
use App\Services\AI\AIUsageService;
use App\Services\AI\Data\AIRequest;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SabayraAiProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlansSeeder::class);
    }

    public function test_super_admin_can_save_encrypted_ai_credentials_without_exposing_keys_in_dom(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $admin->assignRole('super_admin');

        $secretGemini = 'AIzaSyFakeTestSecretKeyForSabayraCheck9999';
        $secretOpenAi = 'sk-proj-fakeSecretKeyForSabayraTesting8888';

        $response = $this->actingAs($admin)->post(route('admin.settings.ai.update'), [
            'ai_enabled' => '1',
            'ai_default_provider' => 'gemini',
            'ai_default_model' => 'gemini-2.5-flash',
            'ai_temperature' => '0.35',
            'ai_max_output_tokens' => '2048',
            'ai_timeout_seconds' => '25',
            'ai_retry_attempts' => '1',
            'ai_default_monthly_credits' => '120',
            'ai_allow_business_override' => '0',
            'gemini_api_key' => $secretGemini,
            'openai_api_key' => $secretOpenAi,
        ]);

        $response->assertRedirect();

        $rawGeminiRow = PlatformSetting::where('key', 'ai.gemini_api_key')->firstOrFail();
        $this->assertTrue($rawGeminiRow->is_encrypted);
        $this->assertNotSame($secretGemini, $rawGeminiRow->value);
        $this->assertSame($secretGemini, PlatformSetting::getValue('ai.gemini_api_key'));
        $this->assertSame($secretOpenAi, PlatformSetting::getValue('ai.openai_api_key'));

        $page = $this->actingAs($admin)->get(route('admin.settings.index'));
        $page->assertOk();
        $page->assertSee('SABAYRA AI Center Configuration');
        $page->assertDontSee($secretGemini);
        $page->assertDontSee($secretOpenAi);
    }

    public function test_super_admin_can_test_gemini_and_openai_provider_connections(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $admin->assignRole('super_admin');

        PlatformSetting::setValue('ai.gemini_api_key', 'AIzaSyTestHeaderOnlyKey1234567890', true, 'test', $admin->id);
        PlatformSetting::setValue('ai.openai_api_key', 'sk-testOpenAiKey1234567890', true, 'test', $admin->id);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '{"status":"ok"}']]],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => [
                    'promptTokenCount' => 10,
                    'candidatesTokenCount' => 5,
                    'totalTokenCount' => 15,
                ],
            ], 200),
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => '{"status":"ok"}'],
                    'finish_reason' => 'stop',
                ]],
                'usage' => [
                    'prompt_tokens' => 12,
                    'completion_tokens' => 6,
                    'total_tokens' => 18,
                ],
            ], 200),
        ]);

        $geminiTest = $this->actingAs($admin)->postJson(route('admin.settings.ai.test'), [
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
        ]);
        $geminiTest->assertOk()->assertJsonPath('ok', true)->assertJsonPath('provider', 'gemini');

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'generativelanguage.googleapis.com')
                && ! str_contains($request->url(), 'AIzaSyTestHeaderOnlyKey1234567890')
                && $request->hasHeader('x-goog-api-key', 'AIzaSyTestHeaderOnlyKey1234567890');
        });

        $openAiTest = $this->actingAs($admin)->postJson(route('admin.settings.ai.test'), [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
        ]);
        $openAiTest->assertOk()->assertJsonPath('ok', true)->assertJsonPath('provider', 'openai');
    }

    public function test_ai_manager_executes_structured_completion_and_records_metering(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Kandal Cafe',
            'slug' => 'kandal-cafe',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        PlatformSetting::setValue('ai.enabled', true, false);
        PlatformSetting::setValue('ai.default_provider', 'gemini', false);
        PlatformSetting::setValue('ai.default_model', 'gemini-2.5-flash', false);
        PlatformSetting::setValue('ai.gemini_api_key', 'AIzaSyMockGeminiKey12345678901234', true);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '{"description_km":"កាហ្វេទឹកកករសជាតិឈ្ងុយឆ្ងាញ់","description_en":"Rich Cambodian iced coffee"}']]],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => [
                    'promptTokenCount' => 120,
                    'candidatesTokenCount' => 80,
                    'totalTokenCount' => 200,
                ],
            ], 200),
        ]);

        /** @var AIManager $manager */
        $manager = app(AIManager::class);
        $response = $manager->complete($business, new AIRequest(
            feature: 'menu_creator',
            systemPrompt: 'Generate a Cambodian bilingual menu item description.',
            messages: [
                ['role' => 'user', 'content' => 'Iced Coffee $2.50'],
            ],
            responseSchema: [
                'type' => 'object',
                'properties' => [
                    'description_km' => ['type' => 'string'],
                    'description_en' => ['type' => 'string'],
                ],
                'required' => ['description_km', 'description_en'],
            ],
        ), $owner->id);

        $this->assertSame('gemini', $response->provider);
        $this->assertSame(200, $response->totalTokens);
        $this->assertSame('Rich Cambodian iced coffee', $response->structuredData['description_en']);

        $log = AiUsageLog::withoutGlobalScopes()->findOrFail($response->usageLogId);
        $this->assertSame('completed', $log->status);
        $this->assertSame(1, $log->credits_charged);

        $ledger = AiCreditLedger::withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
        $this->assertSame(1, $ledger->used_credits);
        $this->assertSame(0, $ledger->reserved_credits);
    }

    public function test_ai_manager_runs_tool_calling_loop_for_gemini_and_openai_under_single_credit(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Phnom Penh Bistro',
            'slug' => 'pp-bistro',
            'business_type' => 'restaurant',
            'status' => 'active',
        ]);

        PlatformSetting::setValue('ai.gemini_api_key', 'AIzaSyToolTestKey123456789012345', true);
        PlatformSetting::setValue('ai.openai_api_key', 'sk-openaiToolTestKey1234567890', true);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'functionCall' => [
                                'name' => 'get_sales_summary',
                                'args' => ['days' => 7],
                            ],
                        ]]],
                        'finishReason' => 'STOP',
                    ]],
                    'usageMetadata' => ['promptTokenCount' => 50, 'candidatesTokenCount' => 20, 'totalTokenCount' => 70],
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [['text' => 'Sales for the last 7 days reached $540.00 across 32 orders.']]],
                        'finishReason' => 'STOP',
                    ]],
                    'usageMetadata' => ['promptTokenCount' => 90, 'candidatesTokenCount' => 25, 'totalTokenCount' => 115],
                ], 200),
            'https://api.openai.com/v1/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => [
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_abc123',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'get_sales_summary',
                                    'arguments' => '{"days":7}',
                                ],
                            ]],
                        ],
                        'finish_reason' => 'tool_calls',
                    ]],
                    'usage' => ['prompt_tokens' => 45, 'completion_tokens' => 15, 'total_tokens' => 60],
                ], 200)
                ->push([
                    'choices' => [[
                        'message' => [
                            'content' => 'OpenAI summary: $540.00 in 7 days.',
                        ],
                        'finish_reason' => 'stop',
                    ]],
                    'usage' => ['prompt_tokens' => 80, 'completion_tokens' => 20, 'total_tokens' => 100],
                ], 200),
        ]);

        /** @var AIManager $manager */
        $manager = app(AIManager::class);
        $tools = [[
            'name' => 'get_sales_summary',
            'description' => 'Read business sales totals.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'days' => ['type' => 'integer'],
                ],
                'required' => ['days'],
            ],
        ]];

        $geminiResult = $manager->completeWithTools(
            $business,
            new AIRequest(
                feature: 'assistant',
                systemPrompt: 'Use tools to answer.',
                messages: [['role' => 'user', 'content' => 'What were 7 day sales?']],
                tools: $tools,
                provider: 'gemini',
            ),
            static fn (string $name, array $args): array => ['days' => $args['days'] ?? 7, 'revenue_usd' => 540.00],
            $owner->id
        );

        $this->assertSame(185, $geminiResult['response']->totalTokens);
        $this->assertCount(1, $geminiResult['executed_tools']);
        $this->assertStringContainsString('$540.00', $geminiResult['response']->text);

        $openAiResult = $manager->completeWithTools(
            $business,
            new AIRequest(
                feature: 'assistant',
                systemPrompt: 'Use tools to answer.',
                messages: [['role' => 'user', 'content' => 'What were 7 day sales?']],
                tools: $tools,
                provider: 'openai',
                model: 'gpt-4o-mini',
            ),
            static fn (string $name, array $args): array => ['days' => $args['days'] ?? 7, 'revenue_usd' => 540.00],
            $owner->id
        );

        $this->assertSame(160, $openAiResult['response']->totalTokens);
        $this->assertCount(1, $openAiResult['executed_tools']);

        $ledger = AiCreditLedger::withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
        $this->assertSame(2, $ledger->used_credits);
        $this->assertSame(0, $ledger->reserved_credits);
    }

    public function test_provider_errors_timeouts_and_malformed_json_refund_credits_and_redact_secrets(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Tea Garden',
            'slug' => 'tea-garden',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        $secret = 'AIzaSySecretMustNeverLeak1234567890';
        PlatformSetting::setValue('ai.gemini_api_key', $secret, true);
        PlatformSetting::setValue('ai.retry_attempts', 0, false);

        /** @var AIManager $manager */
        $manager = app(AIManager::class);

        // 1. Invalid key with upstream echoed secret & malformed JSON
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['error' => ['message' => "API key not valid: {$secret}"]], 401)
                ->push(['candidates' => [['content' => ['parts' => [['text' => 'NOT VALID JSON {{']]]]]], 200),
        ]);

        try {
            $manager->complete($business, new AIRequest('assistant', 'sys', [['role' => 'user', 'content' => 'hi']]), $owner->id);
            $this->fail('Expected AIProviderException for 401.');
        } catch (AIProviderException $e) {
            $this->assertSame('invalid_api_key', $e->errorCode);
            $this->assertStringNotContainsString($secret, $e->getMessage());
        }

        // 2. Malformed JSON when schema required
        try {
            $manager->complete($business, new AIRequest(
                feature: 'menu_creator',
                systemPrompt: 'sys',
                messages: [['role' => 'user', 'content' => 'hi']],
                responseSchema: ['type' => 'object', 'properties' => ['a' => ['type' => 'string']]],
            ), $owner->id);
            $this->fail('Expected AIProviderException for malformed JSON.');
        } catch (AIProviderException $e) {
            $this->assertSame('invalid_response', $e->errorCode);
        }

        $ledger = AiCreditLedger::withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
        $this->assertSame(0, $ledger->used_credits);
        $this->assertSame(0, $ledger->reserved_credits);
        $this->assertSame(2, AiUsageLog::withoutGlobalScopes()->where('business_id', $business->id)->where('status', 'refunded')->count());
    }

    public function test_provider_timeout_refunds_reserved_credits(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Timeout Bistro',
            'slug' => 'timeout-bistro',
            'business_type' => 'restaurant',
            'status' => 'active',
        ]);

        PlatformSetting::setValue('ai.gemini_api_key', 'AIzaSyTimeoutTestKey12345678901234', true);
        PlatformSetting::setValue('ai.retry_attempts', 0, false);

        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 30000 milliseconds'));

        /** @var AIManager $manager */
        $manager = app(AIManager::class);

        try {
            $manager->complete($business, new AIRequest('assistant', 'sys', [['role' => 'user', 'content' => 'hi']]), $owner->id);
            $this->fail('Expected AIProviderException for timeout.');
        } catch (AIProviderException $e) {
            $this->assertSame('timeout', $e->errorCode);
        }

        $ledger = AiCreditLedger::withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
        $this->assertSame(0, $ledger->used_credits);
        $this->assertSame(0, $ledger->reserved_credits);
        $this->assertSame(1, AiUsageLog::withoutGlobalScopes()->where('business_id', $business->id)->where('status', 'refunded')->count());
    }

    public function test_duplicate_request_uuid_insufficient_credits_and_plan_entitlement_guards(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Kampot Bakery',
            'slug' => 'kampot-bakery',
            'business_type' => 'bakery',
            'status' => 'active',
        ]);

        PlatformSetting::setValue('ai.gemini_api_key', 'AIzaSyEntitlementGuardKey1234567890', true);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'OK']]]]],
                'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 5, 'totalTokenCount' => 15],
            ], 200),
        ]);

        /** @var AIManager $manager */
        $manager = app(AIManager::class);
        $manager->complete($business, new AIRequest('assistant', 'sys', [['role' => 'user', 'content' => 'hello']], requestUuid: 'fixed-req-uuid-1'), $owner->id);

        try {
            $manager->complete($business, new AIRequest('assistant', 'sys', [['role' => 'user', 'content' => 'hello']], requestUuid: 'fixed-req-uuid-1'), $owner->id);
            $this->fail('Duplicate request UUID should fail.');
        } catch (AIProviderException $e) {
            $this->assertSame('duplicate_request', $e->errorCode);
        }

        // Enforce subscription plans and attach Free plan (marketing blocked, 30 credits)
        config()->set('subscription.plans_enabled', true);
        $freePlan = Plan::where('slug', 'free')->firstOrFail();
        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $freePlan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        try {
            $manager->complete($business, new AIRequest('marketing_studio', 'sys', [['role' => 'user', 'content' => 'promo']]), $owner->id);
            $this->fail('Free plan should block marketing_studio when plans_enabled is true.');
        } catch (AIProviderException $e) {
            $this->assertSame('plan_upgrade_required', $e->errorCode);
        }

        // Exhaust Free plan credits
        $usageService = app(AIUsageService::class);
        $ledger = $usageService->getOrCreateLedger($business);
        $ledger->update(['used_credits' => $ledger->allocated_credits]);

        try {
            $manager->complete($business, new AIRequest('assistant', 'sys', [['role' => 'user', 'content' => 'hi']]), $owner->id);
            $this->fail('Exhausted credits should block request.');
        } catch (AIProviderException $e) {
            $this->assertSame('insufficient_credits', $e->errorCode);
        }
    }

    public function test_super_admin_can_update_plan_ai_entitlements(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $admin->assignRole('super_admin');
        $basic = Plan::where('slug', 'basic')->firstOrFail();

        $response = $this->actingAs($admin)->put(route('admin.plans.update', $basic), [
            'name' => $basic->name,
            'slug' => $basic->slug,
            'description' => $basic->description,
            'monthly_price' => $basic->monthly_price,
            'yearly_price' => $basic->yearly_price,
            'currency' => $basic->currency ?? 'USD',
            'product_limit' => $basic->product_limit,
            'branch_limit' => $basic->branch_limit,
            'staff_limit' => $basic->staff_limit,
            'ai_monthly_credits' => 300,
            'ai_max_tokens_per_request' => 3072,
            'has_ai_assistant' => '1',
            'has_ai_menu_creator' => '1',
            'has_ai_marketing' => '1',
            'has_ai_forecasting' => '1',
            'is_active' => '1',
            'sort_order' => $basic->sort_order,
        ]);

        $response->assertRedirect(route('admin.plans.index'));
        $basic->refresh();
        $this->assertSame(300, $basic->ai_monthly_credits);
        $this->assertSame(3072, $basic->ai_max_tokens_per_request);
        $this->assertTrue($basic->has_ai_forecasting);
    }
}
