<?php

namespace Tests\Feature;

use App\Models\AiGeneratedContent;
use App\Models\Business;
use App\Models\Category;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SabayraAiMarketingStudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlansSeeder::class);
        TenantContext::clear();
    }

    public function test_marketing_studio_generates_cambodian_facebook_and_tiktok_campaigns_and_blocks_cross_tenant_delete(): void
    {
        $ownerA = User::factory()->create();
        $businessA = Business::create([
            'owner_id' => $ownerA->id,
            'name' => 'Bokor Bakery',
            'slug' => 'bokor-bakery',
            'business_type' => 'bakery',
            'status' => 'active',
        ]);

        $cat = Category::create([
            'business_id' => $businessA->id,
            'name_en' => 'Pastries',
            'slug' => 'pastries',
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $businessA->id,
            'category_id' => $cat->id,
            'name_en' => 'Pandan Croissant',
            'name_km' => 'នំប៉័ងក្រូសង់តើយ',
            'slug' => 'pandan-croissant',
            'base_price' => 2.50,
            'is_available' => true,
        ]);

        PlatformSetting::setValue('ai.gemini_api_key', 'AIzaSyMarketingTestKey123456789012', true);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'headline_km' => 'ប្រម៉ូសិនពិសេសនំប៉័ងក្រូសង់តើយស្រួយឆ្ងាញ់!',
                            'headline_en' => 'Fresh Pandan Croissant baked every morning!',
                            'post_body_km' => 'រីករាយជាមួយនំប៉័ងក្រូសង់តើយតម្លៃត្រឹមតែ $2.50 (10,250 ៛) បង់តាម Bakong KHQR បានភ្លាមៗ។',
                            'post_body_en' => 'Warm flaky Pandan Croissant for $2.50! Order via SABAYRA QR.',
                            'video_script' => '0-3s: Tear crispy croissant open in slow motion. VO: ក្តៅៗស្រួយៗ!',
                            'cta_text' => 'Order now via QR Menu or Inbox!',
                            'hashtags' => ['#SABAYRA', '#PhnomPenhBakery', '#PandanCroissant'],
                            'suggested_promo_schedule' => 'Friday 14:00–17:00 ICT',
                        ], JSON_UNESCAPED_UNICODE),
                    ]]],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => ['promptTokenCount' => 110, 'candidatesTokenCount' => 90, 'totalTokenCount' => 200],
            ], 200),
        ]);

        $this->actingAs($ownerA)
            ->withSession(['active_business_id' => $businessA->id])
            ->get(route('business.ai.marketing'))
            ->assertOk()
            ->assertSee('Cambodian Campaign Builder');

        $gen = $this->actingAs($ownerA)
            ->withSession(['active_business_id' => $businessA->id])
            ->postJson(route('business.ai.marketing.generate'), [
                'channel' => 'facebook',
                'campaign_type' => 'product_spotlight',
                'product_id' => $product->id,
                'target_language' => 'bilingual',
                'tone' => 'friendly',
            ]);

        $gen->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('campaign.platform', 'facebook')
            ->assertJsonPath('campaign.structured.hashtags.0', '#SABAYRA');

        $draft = AiGeneratedContent::withoutGlobalScopes()->findOrFail($gen->json('campaign.id'));
        $this->assertSame('marketing_studio', $draft->feature);

        // Tenant B cannot delete Tenant A's marketing draft
        $ownerB = User::factory()->create();
        $businessB = Business::create([
            'owner_id' => $ownerB->id,
            'name' => 'Business B',
            'slug' => 'business-b-mkt',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        $this->actingAs($ownerB)
            ->withSession(['active_business_id' => $businessB->id])
            ->delete(route('business.ai.marketing.destroy', $draft))
            ->assertForbidden();
    }
}
