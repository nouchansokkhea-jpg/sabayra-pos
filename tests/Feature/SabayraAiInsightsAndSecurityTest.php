<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessAiInsight;
use App\Models\BusinessMember;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AI\AiInsightsService;
use App\Services\Tenant\TenantContext;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SabayraAiInsightsAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlansSeeder::class);
        TenantContext::clear();
    }

    public function test_weekly_summary_strictly_isolates_tenant_orders_and_persists_manageable_ai_insights(): void
    {
        $ownerA = User::factory()->create();
        $businessA = Business::create([
            'owner_id' => $ownerA->id,
            'name' => 'Lotus Noodles',
            'slug' => 'lotus-noodles',
            'business_type' => 'restaurant',
            'status' => 'active',
        ]);

        $ownerB = User::factory()->create();
        $businessB = Business::create([
            'owner_id' => $ownerB->id,
            'name' => 'Competitor Restaurant',
            'slug' => 'competitor-rest',
            'business_type' => 'restaurant',
            'status' => 'active',
        ]);

        Order::create([
            'business_id' => $businessA->id,
            'order_number' => 'LN-001',
            'order_type' => 'dine_in',
            'subtotal' => 25.00,
            'total_amount' => 25.00,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);

        // Huge order in Tenant B MUST NOT appear in Business A weekly summary
        Order::create([
            'business_id' => $businessB->id,
            'order_number' => 'COMP-999',
            'order_type' => 'dine_in',
            'subtotal' => 8888.00,
            'total_amount' => 8888.00,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);

        /** @var AiInsightsService $service */
        $service = app(AiInsightsService::class);
        $summaryA = $service->generateWeeklySummary($businessA);
        $this->assertEquals(25.00, $summaryA['this_week_revenue']);
        $this->assertSame(1, $summaryA['this_week_orders']);

        PlatformSetting::setValue('ai.gemini_api_key', 'AIzaSyInsightBriefTestKey1234567890', true);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'title' => 'Early Week Revenue Baseline ($25.00)',
                            'summary' => 'Recorded 1 paid order totaling $25.00 so far this week.',
                            'recommended_action' => 'Promote signature dishes via QR standees to build order volume.',
                            'priority' => 'medium',
                        ]),
                    ]]],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => ['promptTokenCount' => 80, 'candidatesTokenCount' => 45, 'totalTokenCount' => 125],
            ], 200),
        ]);

        $gen = $this->actingAs($ownerA)
            ->withSession(['active_business_id' => $businessA->id])
            ->postJson(route('business.ai.insights.generate'), [
                'with_ai' => true,
                'request_uuid' => 'insight-brief-1',
            ]);

        $gen->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('monthly_summary.used_credits', 2);

        $insight = BusinessAiInsight::withoutGlobalScopes()
            ->where('business_id', $businessA->id)
            ->where('insight_type', 'executive_brief')
            ->firstOrFail();

        // Tenant B cannot change status of Tenant A insight
        TenantContext::clear();
        $this->actingAs($ownerB)
            ->withSession(['active_business_id' => $businessB->id])
            ->patch(route('business.ai.insights.status', $insight), ['status' => 'applied'])
            ->assertForbidden();

        // Owner A marks insight applied
        TenantContext::clear();
        $this->actingAs($ownerA)
            ->withSession(['active_business_id' => $businessA->id])
            ->patch(route('business.ai.insights.status', $insight), ['status' => 'applied'])
            ->assertRedirect();

        $insight->refresh();
        $this->assertSame('applied', $insight->status);
        $this->assertSame($ownerA->id, $insight->reviewed_by);

        // Dashboard renders real AI Center widget with remaining credits and persisted insight
        $dashboard = $this->actingAs($ownerA)
            ->withSession(['active_business_id' => $businessA->id])
            ->get(route('business.dashboard'));

        $dashboard->assertOk()
            ->assertSee('SABAYRA AI Center')
            ->assertSee('Early Week Revenue Baseline ($25.00)');
    }

    public function test_subscription_plan_enforcement_and_credit_limits_work_across_http_routes(): void
    {
        config()->set('subscription.plans_enabled', true);

        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Plan Enforced Shop',
            'slug' => 'plan-enforced-shop',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        $freePlan = Plan::where('slug', 'free')->firstOrFail();
        $proPlan = Plan::where('slug', 'pro')->firstOrFail();

        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $freePlan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $secretKey = 'AIzaSyPlanCheckSecretKey1234567890';
        PlatformSetting::setValue('ai.gemini_api_key', $secretKey, true);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '{"headline_km":"ក","headline_en":"E","post_body_km":"ក","post_body_en":"E","video_script":"","cta_text":"Order","hashtags":["#A"],"suggested_promo_schedule":"Fri"}']]],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => ['promptTokenCount' => 40, 'candidatesTokenCount' => 30, 'totalTokenCount' => 70],
            ], 200),
        ]);

        // Free plan blocks marketing_studio POST (403 plan_upgrade_required)
        $blocked = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->postJson(route('business.ai.marketing.generate'), [
                'channel' => 'facebook',
                'campaign_type' => 'product_spotlight',
                'target_language' => 'bilingual',
                'tone' => 'friendly',
            ]);

        $blocked->assertForbidden()
            ->assertJsonPath('error_code', 'plan_upgrade_required');
        $this->assertStringNotContainsString($secretKey, $blocked->content());

        // Upgrade to Pro unlocks Marketing Studio immediately
        $subscription->update(['plan_id' => $proPlan->id]);

        $allowed = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->postJson(route('business.ai.marketing.generate'), [
                'channel' => 'facebook',
                'campaign_type' => 'product_spotlight',
                'target_language' => 'bilingual',
                'tone' => 'friendly',
            ]);

        $allowed->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('monthly_summary.allocated_credits', 500)
            ->assertJsonPath('monthly_summary.used_credits', 1);

        // Role matrix check: waiter cannot open any AI Center page or trigger endpoints
        $waiter = User::factory()->create();
        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $waiter->id,
            'role' => 'waiter',
            'is_active' => true,
        ]);

        $this->actingAs($waiter)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.ai.overview'))
            ->assertForbidden();
    }
}
