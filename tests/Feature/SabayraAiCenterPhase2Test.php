<?php

namespace Tests\Feature;

use App\Models\AiFeatureSetting;
use App\Models\AiUsageLog;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SabayraAiCenterPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlansSeeder::class);
        TenantContext::clear();
    }

    public function test_owner_sees_real_zeroed_telemetry_and_real_inventory_warnings_on_ai_overview(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Bayon Roast Cafe',
            'slug' => 'bayon-roast-cafe',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        $category = Category::create([
            'business_id' => $business->id,
            'name_en' => 'Coffee',
            'name_km' => 'កាហ្វេ',
            'slug' => 'coffee',
            'is_active' => true,
        ]);

        Product::create([
            'business_id' => $business->id,
            'category_id' => $category->id,
            'name_en' => 'Arabica Beans 1kg',
            'name_km' => 'គ្រាប់កាហ្វេ អារ៉ាប៊ីកា',
            'slug' => 'arabica-beans-1kg',
            'base_price' => 18.00,
            'track_inventory' => true,
            'stock_quantity' => 2,
            'low_stock_threshold' => 5,
            'is_available' => true,
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.ai.overview'));

        $response->assertOk();
        $response->assertSee('SABAYRA AI Center');
        $response->assertSee('Arabica Beans 1kg');
        $response->assertSee('2 left');
        $response->assertSee('AI Business Assistant');
        $response->assertSee('AI Settings');
    }

    public function test_owner_can_view_usage_history_and_update_business_ai_settings(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Mekong Noodle House',
            'slug' => 'mekong-noodle-house',
            'business_type' => 'restaurant',
            'status' => 'active',
        ]);

        AiUsageLog::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'request_uuid' => (string) Str::uuid(),
            'feature' => 'assistant',
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
            'prompt_tokens' => 100,
            'completion_tokens' => 50,
            'total_tokens' => 150,
            'credits_charged' => 1,
            'estimated_cost_usd' => 0.000155,
            'latency_ms' => 420,
            'status' => 'completed',
        ]);

        $usagePage = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.ai.usage'));

        $usagePage->assertOk();
        $usagePage->assertSee('gemini-2.5-flash');
        $usagePage->assertSee('150');

        $updateResponse = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->put(route('business.ai.settings.update'), [
                'preferred_language' => 'km',
                'preferred_tone' => 'professional',
                'temperature' => '0.55',
                'assistant_enabled' => '1',
                'menu_creator_enabled' => '1',
                'marketing_studio_enabled' => '0',
                'forecasting_enabled' => '1',
                'insights_enabled' => '1',
            ]);

        $updateResponse->assertRedirect();

        $settings = AiFeatureSetting::withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
        $this->assertSame('km', $settings->preferred_language);
        $this->assertSame('professional', $settings->preferred_tone);
        $this->assertEquals(0.55, $settings->temperature);
        $this->assertFalse($settings->marketing_studio_enabled);
    }

    public function test_role_permissions_and_tenant_isolation_protect_ai_center_pages(): void
    {
        $ownerA = User::factory()->create();
        $businessA = Business::create([
            'owner_id' => $ownerA->id,
            'name' => 'Store A',
            'slug' => 'store-a',
            'business_type' => 'restaurant',
            'status' => 'active',
        ]);

        $inventoryStaff = User::factory()->create();
        BusinessMember::create([
            'business_id' => $businessA->id,
            'user_id' => $inventoryStaff->id,
            'role' => 'inventory',
            'is_active' => true,
        ]);

        // Inventory staff can open Overview & Inventory Forecast, blocked from AI Settings & Usage
        $this->actingAs($inventoryStaff)
            ->withSession(['active_business_id' => $businessA->id])
            ->get(route('business.ai.overview'))
            ->assertOk();

        $this->actingAs($inventoryStaff)
            ->withSession(['active_business_id' => $businessA->id])
            ->get(route('business.ai.inventory-forecast'))
            ->assertOk();

        $this->actingAs($inventoryStaff)
            ->withSession(['active_business_id' => $businessA->id])
            ->get(route('business.ai.settings'))
            ->assertForbidden();

        $this->actingAs($inventoryStaff)
            ->withSession(['active_business_id' => $businessA->id])
            ->get(route('business.ai.usage'))
            ->assertForbidden();

        // Tenant B cannot see Tenant A's usage log
        $ownerB = User::factory()->create();
        $businessB = Business::create([
            'owner_id' => $ownerB->id,
            'name' => 'Store B Private',
            'slug' => 'store-b-private',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        AiUsageLog::withoutGlobalScopes()->create([
            'business_id' => $businessA->id,
            'user_id' => $ownerA->id,
            'request_uuid' => (string) Str::uuid(),
            'feature' => 'assistant',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'total_tokens' => 987,
            'credits_charged' => 1,
            'status' => 'completed',
        ]);

        $usageB = $this->actingAs($ownerB)
            ->withSession(['active_business_id' => $businessB->id])
            ->get(route('business.ai.usage'));

        $usageB->assertOk();
        $usageB->assertDontSee('gpt-4o-mini');
        $usageB->assertDontSee('987');
    }
}
