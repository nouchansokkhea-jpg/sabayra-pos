<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LandingPageAndKdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kitchen', 'guard_name' => 'web']);
    }

    public function test_landing_page_renders_successfully_with_plans_and_branding(): void
    {
        Plan::create([
            'name' => 'PRO PLAN',
            'slug' => 'pro',
            'description' => 'For fast growing businesses',
            'monthly_price' => 35.00,
            'yearly_price' => 350.00,
            'product_limit' => 500,
            'branch_limit' => 3,
            'staff_limit' => 10,
            'has_pos' => true,
            'has_inventory' => true,
            'has_online_ordering' => true,
            'has_analytics' => true,
            'is_active' => true,
            'is_popular' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('SABAYRA');
        $response->assertSee('PRO PLAN');
        $response->assertSee('One Platform. Every Business.');
        $response->assertSee('Active Outlets');
        $response->assertSee('Total Menu Views');
        $response->assertSee('Products & Dishes', false);
        $response->assertSee('Orders Processed');
        $response->assertSee('sabayra-icon.png');
        $response->assertDontSee('MenuHub Cambodia');
    }

    public function test_locale_switcher_route_sets_locale_in_session(): void
    {
        $response = $this->get('/locale/km');

        $response->assertRedirect();
        $this->assertEquals('km', session('locale'));

        $responseEn = $this->get('/locale/en');
        $responseEn->assertRedirect();
        $this->assertEquals('en', session('locale'));
    }

    public function test_kitchen_display_screen_loads_for_tenant_staff(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'KDS Bistro',
            'slug' => 'kds-bistro',
            'business_type' => 'restaurant',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        TenantContext::set($business);

        $table = RestaurantTable::create([
            'business_id' => $business->id,
            'table_number' => '05',
            'name' => 'Table 05',
            'capacity' => 4,
            'status' => 'occupied',
        ]);

        $order = Order::create([
            'business_id' => $business->id,
            'table_id' => $table->id,
            'order_number' => 'ORD-20261122-0005',
            'order_type' => 'dine_in',
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'subtotal' => 12.00,
            'total_usd' => 12.00,
            'total_khr' => 49200,
            'customer_name' => 'Sophea',
            'notes' => 'No onions please',
        ]);

        $category = Category::create([
            'business_id' => $business->id,
            'name_en' => 'Main Dishes',
            'name_km' => 'ម្ហូបចម្បង',
            'slug' => 'main-dishes',
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'category_id' => $category->id,
            'name_en' => 'Khmer Fried Rice',
            'name_km' => 'បាយឆាខ្មែរ',
            'slug' => 'khmer-fried-rice',
            'base_price' => 6.00,
            'is_available' => true,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Khmer Fried Rice',
            'unit_price' => 6.00,
            'quantity' => 2,
            'subtotal' => 12.00,
        ]);

        $response = $this->actingAs($owner)->get('/app/kitchen');

        $response->assertStatus(200);
        $response->assertSee('KDS Bistro');
        $response->assertSee('ORD-20261122-0005');
        $response->assertSee('Khmer Fried Rice');
        $response->assertSee('No onions please');
    }

    public function test_kds_api_returns_json_orders(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'API KDS Cafe',
            'slug' => 'api-kds-cafe',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        TenantContext::set($business);

        Order::create([
            'business_id' => $business->id,
            'order_number' => 'ORD-API-001',
            'order_type' => 'takeaway',
            'status' => 'preparing',
            'payment_status' => 'paid',
            'currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'subtotal' => 5.00,
            'total_usd' => 5.00,
            'total_khr' => 20500,
        ]);

        $response = $this->actingAs($owner)->getJson('/app/kitchen/api/orders');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'orders',
            'counts' => ['total', 'confirmed', 'preparing', 'ready'],
            'server_time',
        ]);
        $response->assertJsonFragment([
            'order_number' => 'ORD-API-001',
            'status' => 'preparing',
        ]);
    }

    public function test_kds_bump_workflow_and_recipe_deduction(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Bump Test Resto',
            'slug' => 'bump-test-resto',
            'business_type' => 'restaurant',
            'status' => 'active',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        TenantContext::set($business);

        $category = Category::create([
            'business_id' => $business->id,
            'name_en' => 'Coffee',
            'name_km' => 'កាហ្វេ',
            'slug' => 'coffee',
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'category_id' => $category->id,
            'name_en' => 'Iced Americano',
            'name_km' => 'អាមេរិកាណូទឹកកក',
            'slug' => 'iced-americano',
            'base_price' => 2.50,
            'is_available' => true,
        ]);

        // Raw inventory item: Roasted Coffee Beans
        $beanStock = InventoryItem::create([
            'business_id' => $business->id,
            'name_en' => 'Arabica Coffee Beans',
            'name_km' => 'គ្រាប់កាហ្វេអារ៉ាប៊ីកា',
            'sku' => 'RAW-BEAN-01',
            'unit' => 'g',
            'current_stock' => 1000.00, // 1000 grams
            'unit_cost' => 0.02,
        ]);

        // Recipe: 20g beans per 1 Americano
        $recipe = Recipe::create([
            'product_id' => $product->id,
        ]);

        RecipeItem::create([
            'recipe_id' => $recipe->id,
            'inventory_item_id' => $beanStock->id,
            'quantity' => 20.00,
            'unit' => 'g',
        ]);

        $order = Order::create([
            'business_id' => $business->id,
            'order_number' => 'ORD-BUMP-001',
            'order_type' => 'dine_in',
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'subtotal' => 5.00,
            'total_usd' => 5.00,
            'total_khr' => 20500,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Iced Americano',
            'unit_price' => 2.50,
            'quantity' => 2, // 2 x 20g = 40g deduction expected
            'subtotal' => 5.00,
        ]);

        // 1. Bump confirmed -> preparing
        $bump1 = $this->actingAs($owner)->postJson("/app/kitchen/orders/{$order->id}/bump");
        $bump1->assertStatus(200);
        $this->assertEquals('preparing', $order->fresh()->status);

        // 2. Bump preparing -> ready
        $bump2 = $this->actingAs($owner)->postJson("/app/kitchen/orders/{$order->id}/bump");
        $bump2->assertStatus(200);
        $this->assertEquals('ready', $order->fresh()->status);

        // 3. Bump ready -> completed (triggers recipe deduction)
        $bump3 = $this->actingAs($owner)->postJson("/app/kitchen/orders/{$order->id}/bump");
        $bump3->assertStatus(200);
        $this->assertEquals('completed', $order->fresh()->status);

        // Verify inventory deducted: 1000 - 40 = 960g
        $this->assertEquals(960.00, (float) $beanStock->fresh()->current_stock);
    }
}
