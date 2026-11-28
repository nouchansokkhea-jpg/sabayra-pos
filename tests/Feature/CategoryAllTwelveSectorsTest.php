<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Business\CategoryExperienceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class CategoryAllTwelveSectorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_12_business_categories_render_dashboard_pos_products_reports_analytics_and_storefront(): void
    {
        $registry = app(CategoryExperienceService::class)->categories();
        $this->assertCount(12, $registry);

        foreach ($registry as $categoryKey => $config) {
            $owner = User::factory()->create();
            $business = Business::create([
                'owner_id' => $owner->id,
                'name' => 'SABAYRA ' . $config['name_en'],
                'slug' => 'sabayra-' . str_replace('_', '-', $categoryKey),
                'business_type' => $categoryKey,
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

            $category = Category::create([
                'business_id' => $business->id,
                'name_en' => 'Signature',
                'slug' => 'signature-' . $categoryKey,
                'is_active' => true,
            ]);

            Product::create([
                'business_id' => $business->id,
                'category_id' => $category->id,
                'name_en' => 'Sample Item ' . $categoryKey,
                'slug' => 'sample-' . $categoryKey,
                'product_type' => $config['pos']['default_product_type'] ?? 'standard',
                'base_price' => 5.00,
                'is_available' => true,
            ]);

            // Dashboard renders tailored headline and bilingual name
            $dash = $this->actingAs($owner)
                ->withSession(['active_business_id' => $business->id])
                ->get(route('business.dashboard'));
            $dash->assertOk();
            $dash->assertSee($config['dashboard']['headline_en']);
            $dash->assertSee($config['name_km']);

            // Products create renders
            $this->actingAs($owner)
                ->withSession(['active_business_id' => $business->id])
                ->get(route('business.products.create'))
                ->assertOk();

            // POS renders
            $this->actingAs($owner)
                ->withSession(['active_business_id' => $business->id])
                ->get(route('business.pos.index'))
                ->assertOk();

            // Reports & Analytics render
            $this->actingAs($owner)
                ->withSession(['active_business_id' => $business->id])
                ->get(route('business.reports.index'))
                ->assertOk();
            $this->actingAs($owner)
                ->withSession(['active_business_id' => $business->id])
                ->get(route('business.analytics.index'))
                ->assertOk();

            // Public Storefront renders category storefront mode
            $sf = $this->get(route('public.menu', ['business_slug' => $business->slug]));
            $sf->assertOk();
            $sf->assertSee($config['storefront']['title_en']);
        }
    }

    public function test_khmer_localization_resolves_khmer_category_display_names_and_descriptions(): void
    {
        $owner = User::factory()->create(['preferred_locale' => 'km']);
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'រំដួល កាហ្វេ',
            'slug' => 'romdoul-cafe',
            'business_type' => 'cafe',
            'status' => 'active',
            'default_currency' => 'KHR',
            'exchange_rate_khr' => 4100,
        ]);

        App::setLocale('km');
        $meta = app(CategoryExperienceService::class)->meta($business);
        App::setLocale('en');

        $this->assertSame('ហាងកាហ្វេ', $meta['display_name']);
        $this->assertNotEmpty($meta['description']);
    }

    public function test_cross_tenant_isolation_prevents_booking_status_snooping(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $bizA = Business::create([
            'owner_id' => $ownerA->id,
            'name' => 'Salon A',
            'slug' => 'salon-alpha',
            'business_type' => 'services',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
        ]);

        $bizB = Business::create([
            'owner_id' => $ownerB->id,
            'name' => 'Salon B',
            'slug' => 'salon-beta',
            'business_type' => 'services',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
        ]);

        Reservation::create([
            'business_id' => $bizA->id,
            'booking_type' => 'service',
            'booking_code' => 'BK-TENANT-A',
            'customer_name' => 'Private Client A',
            'customer_phone' => '012999000',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '11:00',
            'status' => 'confirmed',
        ]);

        // Booking code belonging to Salon A must NOT resolve under Salon B URL
        $this->get(route('public.booking-status', [
            'business_slug' => $bizB->slug,
            'booking_code' => 'BK-TENANT-A',
        ]))->assertNotFound();
    }
}
