<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Business\BusinessFeatureService;
use App\Services\Business\CategoryExperienceService;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryExperienceArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();
    }

    public function test_all_twelve_business_categories_expose_complete_centralized_configurations(): void
    {
        /** @var CategoryExperienceService $service */
        $service = app(CategoryExperienceService::class);
        $categories = $service->categories();

        $expectedKeys = [
            'restaurant',
            'cafe',
            'milk_tea',
            'bakery',
            'fast_food',
            'supermarket',
            'mini_mart',
            'retail_shop',
            'clothing',
            'beauty',
            'services',
            'other',
        ];

        $this->assertSame($expectedKeys, array_keys($categories));

        foreach ($expectedKeys as $key) {
            $cfg = $categories[$key];
            $this->assertArrayHasKey('name_en', $cfg);
            $this->assertArrayHasKey('name_km', $cfg);
            $this->assertArrayHasKey('description_en', $cfg);
            $this->assertArrayHasKey('description_km', $cfg);
            $this->assertArrayHasKey('features', $cfg);
            $this->assertArrayHasKey('terminology', $cfg);
            $this->assertArrayHasKey('dashboard', $cfg);
            $this->assertArrayHasKey('pos', $cfg);
            $this->assertArrayHasKey('storefront', $cfg);
            $this->assertArrayHasKey('reports', $cfg);
        }

        $owner = User::factory()->create();
        $clothing = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Phnom Penh Apparel',
            'slug' => 'pp-apparel',
            'business_type' => 'clothing',
            'status' => 'active',
        ]);

        $this->assertTrue(BusinessFeatureService::isEnabled($clothing, 'variant_matrix'));
        $this->assertTrue(BusinessFeatureService::isEnabled($clothing, 'barcode'));
        $this->assertFalse(BusinessFeatureService::isEnabled($clothing, 'kitchen'));
        $this->assertSame('fashion_variant', $service->posConfig($clothing)['mode']);
        $this->assertSame('catalog', $service->storefrontConfig($clothing)['mode']);

        $servicesBiz = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Bayon Spa & Clinic',
            'slug' => 'bayon-spa',
            'business_type' => 'services',
            'status' => 'active',
        ]);

        $this->assertTrue(BusinessFeatureService::isEnabled($servicesBiz, 'service_bookings'));
        $this->assertFalse(BusinessFeatureService::isEnabled($servicesBiz, 'tables'));
        $this->assertSame('booking', $service->storefrontConfig($servicesBiz)['mode']);
        $this->assertSame('service_booking', $service->posConfig($servicesBiz)['mode']);
    }

    public function test_other_business_custom_module_overrides_and_additive_schema_columns_persist_cleanly(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Flexible Studio',
            'slug' => 'flexible-studio',
            'business_type' => 'other',
            'status' => 'active',
        ]);

        /** @var CategoryExperienceService $service */
        $service = app(CategoryExperienceService::class);
        $this->assertFalse($service->isModuleEnabled($business, 'service_bookings'));

        BusinessSetting::setValue($business->id, 'category.custom_modules', [
            'service_bookings' => true,
            'barcode' => false,
        ]);

        $this->assertTrue($service->isModuleEnabled($business, 'service_bookings'));
        $this->assertFalse($service->isModuleEnabled($business, 'barcode'));
        $this->assertSame('booking', $service->storefrontConfig($business)['mode']);

        $cat = Category::create([
            'business_id' => $business->id,
            'name_en' => 'Cosmetics',
            'slug' => 'cosmetics',
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'category_id' => $cat->id,
            'name_en' => 'Matte Lipstick',
            'brand' => 'Khmer Glow',
            'slug' => 'matte-lipstick',
            'batch_number' => 'LOT-2026-A',
            'expiry_date' => '2027-06-30',
            'duration_minutes' => 45,
            'attributes_json' => ['skin_type' => 'all', 'customizable' => true],
            'base_price' => 12.00,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name_en' => 'Rosewood 02 / M',
            'size' => 'M',
            'color' => 'Rose',
            'shade' => '02 Rosewood',
            'barcode' => '8850001234567',
            'sku' => 'GLOW-02',
            'stock_quantity' => 25,
        ]);

        $reservation = Reservation::create([
            'business_id' => $business->id,
            'booking_type' => 'service',
            'booking_code' => 'BK-20261128-0001',
            'customer_name' => 'Dara',
            'customer_phone' => '012345678',
            'party_size' => 1,
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '14:00',
            'duration_minutes' => 45,
            'end_time' => '14:45',
            'price_usd' => 12.00,
            'service_product_id' => $product->id,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        $this->assertSame('Khmer Glow', $product->fresh()->brand);
        $this->assertSame('02 Rosewood', $variant->fresh()->shade);
        $this->assertSame($product->id, $reservation->fresh()->serviceProduct->id);

        $diff = $service->diffCategoryChange($business, 'restaurant');
        $this->assertTrue($diff['preserves_data']);
        $this->assertContains('kitchen', $diff['enabled_modules']);
    }
}
