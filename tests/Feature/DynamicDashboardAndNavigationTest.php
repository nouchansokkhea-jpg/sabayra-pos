<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicDashboardAndNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWorkspace(string $businessType, string $name, string $slug): array
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'slug' => $slug,
            'business_type' => $businessType,
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

        return [$owner, $business];
    }

    public function test_restaurant_dashboard_and_sidebar_show_hospitality_modules(): void
    {
        [$owner, $business] = $this->createTenantWorkspace('restaurant', 'Romdoul Kitchen', 'romdoul-kitchen');

        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.dashboard'));

        $response->assertOk();
        $response->assertSee('Restaurant Operations Command');
        $response->assertSee('Active Tables');
        $response->assertSee('Kitchen (KDS)');
        $response->assertSee('Tables & QR Standees', false);
        $response->assertSee('Table Reservations');
        $response->assertSee('Recipes & BOM');
    }

    public function test_supermarket_dashboard_and_sidebar_hide_tables_and_show_retail_inventory(): void
    {
        [$owner, $business] = $this->createTenantWorkspace('supermarket', 'Bayon Market', 'bayon-market');

        $category = Category::create([
            'business_id' => $business->id,
            'name_en' => 'Groceries',
            'slug' => 'groceries',
            'is_active' => true,
        ]);

        Product::create([
            'business_id' => $business->id,
            'category_id' => $category->id,
            'name_en' => 'Fresh Milk 1L',
            'slug' => 'fresh-milk-1l',
            'batch_number' => 'LOT-88',
            'expiry_date' => now()->addDays(5),
            'base_price' => 3.50,
            'cost_price' => 2.00,
            'track_inventory' => true,
            'stock_quantity' => 10,
            'low_stock_threshold' => 15,
            'is_available' => true,
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.dashboard'));

        $response->assertOk();
        $response->assertSee('Supermarket Retail Control');
        $response->assertSee('Inventory Value');
        $response->assertSee('$20.00');
        $response->assertSee('Expiring in 14 Days');
        $response->assertSee('LOT-88');
        $response->assertSee('Bulk CSV / Barcode Import');
        $response->assertSee('Suppliers & PO');
        $response->assertDontSee('Tables & QR Standees', false);
        $response->assertDontSee('Kitchen (KDS)');
        $response->assertDontSee('Recipes & BOM');
    }

    public function test_clothing_store_dashboard_renders_variant_size_and_color_breakdown(): void
    {
        [$owner, $business] = $this->createTenantWorkspace('clothing', 'Sen Sok Boutique', 'sen-sok-boutique');

        $category = Category::create([
            'business_id' => $business->id,
            'name_en' => 'Dresses',
            'slug' => 'dresses',
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'category_id' => $category->id,
            'name_en' => 'Silk Linen Shirt',
            'slug' => 'silk-linen-shirt',
            'base_price' => 28.00,
            'is_available' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'name_en' => 'M / Ivory',
            'size' => 'M',
            'color' => 'Ivory',
            'stock_quantity' => 12,
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.dashboard'));

        $response->assertOk();
        $response->assertSee('Fashion Boutique & Variant Stock');
        $response->assertSee('Top Selling Size');
        $response->assertSee('Top Selling Color');
        $response->assertSee('Variant Units in Stock');
        $response->assertSee('M / Ivory');
    }

    public function test_services_dashboard_renders_upcoming_appointments_and_tenant_isolation(): void
    {
        [$ownerA, $salonA] = $this->createTenantWorkspace('services', 'BKK Glow Spa', 'bkk-glow-spa');
        [$ownerB, $salonB] = $this->createTenantWorkspace('services', 'Other Studio', 'other-studio');

        $category = Category::create([
            'business_id' => $salonA->id,
            'name_en' => 'Spa Treatments',
            'slug' => 'spa-treatments',
            'is_active' => true,
        ]);

        $serviceProduct = Product::create([
            'business_id' => $salonA->id,
            'category_id' => $category->id,
            'name_en' => 'Aroma Facial 60m',
            'slug' => 'aroma-facial-60m',
            'product_type' => 'service',
            'base_price' => 35.00,
            'duration_minutes' => 60,
            'is_available' => true,
        ]);

        Reservation::create([
            'business_id' => $salonA->id,
            'booking_type' => 'service',
            'booking_code' => 'BK-SALON-1',
            'customer_name' => 'Sreyleak N',
            'customer_phone' => '012999888',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '14:00',
            'duration_minutes' => 60,
            'service_product_id' => $serviceProduct->id,
            'status' => 'confirmed',
        ]);

        Reservation::create([
            'business_id' => $salonB->id,
            'booking_type' => 'service',
            'booking_code' => 'BK-OTHER-9',
            'customer_name' => 'Secret Other Client',
            'customer_phone' => '011000111',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '15:00',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($ownerA)
            ->withSession(['active_business_id' => $salonA->id])
            ->get(route('business.dashboard'));

        $response->assertOk();
        $response->assertSee('Appointments & Service Performance');
        $response->assertSee('Service Appointments');
        $response->assertSee('Sreyleak N');
        $response->assertSee('Aroma Facial 60m');
        $response->assertDontSee('Secret Other Client');
    }

    public function test_dashboard_widget_personalization_persists_hidden_panels(): void
    {
        [$owner, $business] = $this->createTenantWorkspace('retail_shop', 'Toul Kork Tech', 'tk-tech');

        $post = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->post(route('business.dashboard.widgets.update'), [
                'available_panels' => ['revenue_chart', 'best_sellers', 'low_stock_products', 'recent_products'],
                'visible_panels' => ['revenue_chart', 'best_sellers'],
            ]);

        $post->assertRedirect(route('business.dashboard'));

        $hidden = BusinessSetting::getValue($business->id, 'dashboard.hidden_widgets', []);
        $this->assertContains('low_stock_products', $hidden);
        $this->assertContains('recent_products', $hidden);
        $this->assertNotContains('revenue_chart', $hidden);
    }
}
