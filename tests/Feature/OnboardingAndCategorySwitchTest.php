<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Business\CategoryExperienceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingAndCategorySwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_page_renders_all_12_categories_and_tailored_defaults_persist(): void
    {
        $user = User::factory()->create();

        $getResponse = $this->actingAs($user)->get(route('business.create'));
        $getResponse->assertOk();
        $getResponse->assertSee('Services Business');
        $getResponse->assertSee('Clothing & Fashion Store');
        $getResponse->assertSee('Beauty & Cosmetics');

        $response = $this->actingAs($user)->post(route('business.store'), [
            'name' => 'Phnom Penh Supermarket',
            'slug' => 'pp-supermarket',
            'business_type' => 'supermarket',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
        ]);

        $response->assertRedirect(route('business.dashboard'));

        $business = Business::where('slug', 'pp-supermarket')->firstOrFail();
        $this->assertSame('#10b981', $business->theme_settings['primary_color']);
        $this->assertSame('supermarket', BusinessSetting::getValue($business->id, 'category.active_type'));
        $this->assertSame('retail', BusinessSetting::getValue($business->id, 'general.default_product_type'));
    }

    public function test_onboarding_custom_other_business_persists_selected_modules(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('business.store'), [
            'name' => 'Custom Pet Studio',
            'slug' => 'custom-pet-studio',
            'business_type' => 'other',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'custom_modules' => [
                'pos' => '1',
                'inventory' => '1',
                'service_bookings' => '1',
                'tables' => '0',
            ],
        ])->assertRedirect(route('business.dashboard'));

        $business = Business::where('slug', 'custom-pet-studio')->firstOrFail();
        $service = app(CategoryExperienceService::class);

        $this->assertTrue($service->isModuleEnabled($business, 'service_bookings'));
        $this->assertTrue($service->isModuleEnabled($business, 'pos'));
        $this->assertFalse($service->isModuleEnabled($business, 'tables'));
    }

    public function test_safe_category_switch_preserves_existing_products_categories_and_orders(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Hybrid Bistro & Shop',
            'slug' => 'hybrid-bistro',
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

        $category = Category::create([
            'business_id' => $business->id,
            'name_en' => 'Signatures',
            'slug' => 'signatures',
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'category_id' => $category->id,
            'name_en' => 'Signature Noodles',
            'slug' => 'signature-noodles',
            'price_usd' => 6.50,
            'is_available' => true,
        ]);

        $order = Order::create([
            'business_id' => $business->id,
            'order_number' => 'ORD-SWITCH-1',
            'order_type' => 'dine_in',
            'subtotal_usd' => 6.50,
            'total_usd' => 6.50,
            'total_khr' => 26650,
            'exchange_rate_used' => 4100,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->put(route('business.settings.experience.update'), [
                'business_type' => 'mini_mart',
                'apply_category_theme' => '1',
                'custom_modules' => [
                    'pos' => '1',
                    'inventory' => '1',
                    'barcode' => '1',
                    'delivery_zones' => '1',
                ],
            ]);

        $response->assertRedirect(route('business.settings.index', ['section' => 'experience']));

        $business->refresh();
        $this->assertSame('mini_mart', $business->business_type);
        $this->assertSame('#06b6d4', $business->theme_settings['primary_color']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name_en' => 'Signature Noodles']);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name_en' => 'Signatures']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'order_number' => 'ORD-SWITCH-1']);

        $service = app(CategoryExperienceService::class);
        $this->assertTrue($service->isModuleEnabled($business, 'barcode'));
        $this->assertFalse($service->isModuleEnabled($business, 'tables'));
    }

    public function test_non_manager_staff_cannot_update_category_experience(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Salon Test',
            'slug' => 'salon-test',
            'business_type' => 'services',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $staff->id,
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->withSession(['active_business_id' => $business->id])
            ->put(route('business.settings.experience.update'), [
                'business_type' => 'restaurant',
            ])
            ->assertForbidden();
    }
}
