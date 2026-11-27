<?php

namespace Tests\Feature;

use App\Models\AiCreditLedger;
use App\Models\AiForecast;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SabayraAiForecastingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlansSeeder::class);
        TenantContext::clear();
    }

    public function test_inventory_forecast_honestly_reports_insufficient_data_with_zero_credit_charge(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'New Empty Mart',
            'slug' => 'new-empty-mart',
            'business_type' => 'retail',
            'status' => 'active',
        ]);

        InventoryItem::create([
            'business_id' => $business->id,
            'name_en' => 'Sugar Syrup',
            'unit' => 'bottle',
            'current_stock' => 10,
            'low_stock_threshold' => 2,
            'is_active' => true,
        ]);

        $page = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.ai.inventory-forecast'));

        $page->assertOk()
            ->assertSee('Insufficient')
            ->assertSee('Sugar Syrup');

        $forecast = AiForecast::withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
        $this->assertSame('insufficient_data', $forecast->status);
        $this->assertSame('insufficient', $forecast->confidence_level);

        $ledger = AiCreditLedger::withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
        $this->assertSame(0, $ledger->used_credits);
    }

    public function test_inventory_forecast_calculates_moving_average_runway_and_optional_ai_advice(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Siem Reap Coffee',
            'slug' => 'sr-coffee',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        $milk = InventoryItem::create([
            'business_id' => $business->id,
            'name_en' => 'Whole Milk',
            'name_km' => 'ទឹកដោះគោស្រស់',
            'unit' => 'liter',
            'current_stock' => 5.0,
            'low_stock_threshold' => 4.0,
            'unit_cost' => 2.00,
            'is_active' => true,
        ]);

        // Record 10 days of usage (2 liters per day = 20 liters total -> ~2.5 days left -> critical!)
        for ($d = 0; $d < 10; $d++) {
            InventoryMovement::create([
                'business_id' => $business->id,
                'inventory_item_id' => $milk->id,
                'user_id' => $owner->id,
                'type' => 'recipe_deduction',
                'quantity' => -2.0,
                'balance_after' => 5.0,
                'reason' => 'POS order deduction',
                'created_at' => now()->subDays($d)->setTime(14, 0),
            ]);
        }

        PlatformSetting::setValue('ai.gemini_api_key', 'AIzaSyInventoryFcKey123456789012', true);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Reorder Whole Milk urgently: estimated 2.5 days of stock remaining.']]],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => ['promptTokenCount' => 130, 'candidatesTokenCount' => 40, 'totalTokenCount' => 170],
            ], 200),
        ]);

        $run = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->postJson(route('business.ai.inventory-forecast.run'), [
                'with_ai' => true,
                'request_uuid' => 'inv-fc-test-1',
            ]);

        $run->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('forecast.status', 'ready')
            ->assertJsonPath('forecast.confidence_level', 'medium')
            ->assertJsonPath('forecast.statistical_payload.critical_count', 1)
            ->assertJsonPath('monthly_summary.used_credits', 2);

        $row = $run->json('forecast.statistical_payload.items.0');
        $this->assertSame('Whole Milk', $row['name_en']);
        $this->assertSame('critical', $row['risk_level']);
        $this->assertGreaterThan(0, $row['recommended_reorder_qty']);

        $page = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.ai.inventory-forecast'));
        $page->assertOk()->assertSee('Reorder Whole Milk urgently');
    }

    public function test_sales_prediction_blocks_insufficient_history_and_forecasts_accurate_multi_horizon_series(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Kep Grill',
            'slug' => 'kep-grill',
            'business_type' => 'restaurant',
            'exchange_rate_khr' => 4100,
            'status' => 'active',
        ]);

        $category = Category::create([
            'business_id' => $business->id,
            'name_en' => 'Seafood',
            'slug' => 'seafood',
            'is_active' => true,
        ]);

        $dish = Product::create([
            'business_id' => $business->id,
            'category_id' => $category->id,
            'name_en' => 'Pepper Crab',
            'slug' => 'pepper-crab',
            'base_price' => 15.00,
            'is_available' => true,
        ]);

        // Only 2 orders initially -> insufficient_data
        for ($i = 1; $i <= 2; $i++) {
            Order::create([
                'business_id' => $business->id,
                'order_number' => "KG-EARLY-{$i}",
                'order_type' => 'dine_in',
                'subtotal' => 15.00,
                'total_amount' => 15.00,
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => 'cash',
            ]);
        }

        $earlyPage = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.ai.sales-prediction'));

        $earlyPage->assertOk()->assertSee('Insufficient');

        // Now populate 16 continuous days of real orders ($30/day)
        for ($d = 1; $d <= 15; $d++) {
            $ord = Order::create([
                'business_id' => $business->id,
                'order_number' => "KG-DAY-{$d}",
                'order_type' => 'dine_in',
                'subtotal' => 30.00,
                'total_amount' => 30.00,
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => 'bakong_khqr',
            ]);
            $ord->forceFill(['created_at' => now()->subDays($d)->setTime(18, 0)])->save();

            OrderItem::create([
                'order_id' => $ord->id,
                'product_id' => $dish->id,
                'product_name' => 'Pepper Crab',
                'unit_price' => 15.00,
                'quantity' => 2,
                'subtotal' => 30.00,
            ]);
        }

        PlatformSetting::setValue('ai.gemini_api_key', 'AIzaSySalesPredictionKey1234567890', true);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Steady $30 daily baseline driven cleanly by Seafood category.']]],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => ['promptTokenCount' => 140, 'candidatesTokenCount' => 35, 'totalTokenCount' => 175],
            ], 200),
        ]);

        $run = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->postJson(route('business.ai.sales-prediction.run'), [
                'horizon' => '7d',
                'with_ai' => true,
                'request_uuid' => 'sales-fc-test-1',
            ]);

        $run->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('forecast.status', 'ready')
            ->assertJsonPath('forecast.confidence_level', 'medium')
            ->assertJsonPath('monthly_summary.used_credits', 2);

        $sevenDay = $run->json('forecast.statistical_payload.horizons.7d');
        $this->assertGreaterThan(100, $sevenDay['expected_revenue_usd']);
        $this->assertLessThan($sevenDay['high_estimate_usd'], $sevenDay['expected_revenue_usd']);

        // Inventory staff blocked from financial sales prediction
        $invStaff = User::factory()->create();
        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $invStaff->id,
            'role' => 'inventory',
            'is_active' => true,
        ]);

        $this->actingAs($invStaff)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.ai.sales-prediction'))
            ->assertForbidden();
    }
}
