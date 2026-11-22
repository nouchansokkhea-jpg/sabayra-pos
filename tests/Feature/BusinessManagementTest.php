<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_onboard_and_create_a_business(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('business.store'), [
            'name' => 'Brown Coffee BKK',
            'slug' => 'brown-coffee-bkk',
            'business_type' => 'cafe',
            'phone' => '+855 23 215 215',
            'address' => 'St. 51 corner 302, BKK1, Phnom Penh',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100.00,
            'tax_percent' => 10.00,
        ]);

        $response->assertRedirect(route('business.dashboard'));
        $this->assertDatabaseHas('businesses', ['slug' => 'brown-coffee-bkk']);
        $this->assertDatabaseHas('business_branches', ['name' => 'Main Branch']);

        $business = Business::where('slug', 'brown-coffee-bkk')->first();
        $this->assertTrue($user->isOwnerOf($business));
    }

    public function test_user_can_create_business_with_uppercase_or_spaced_slug_and_it_auto_slugifies(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('business.store'), [
            'name' => 'Bar Premium',
            'slug' => 'Bar-Premium',
            'business_type' => 'restaurant',
            'phone' => '0715734762',
            'telegram_handle' => 'doungchan',
            'address' => 'Phnom Penh',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100.00,
        ]);

        $response->assertRedirect(route('business.dashboard'));
        $this->assertDatabaseHas('businesses', [
            'name' => 'Bar Premium',
            'slug' => 'bar-premium',
        ]);
    }

    public function test_owner_can_access_dashboard_and_see_catalog_stats(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Amazon Café Cambodia',
            'slug' => 'amazon-cafe-cambodia',
            'status' => 'active',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Amazon Café Cambodia');
        $response->assertSee('Business Overview');
    }

    public function test_owner_can_create_and_manage_categories(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Mekong Bakery',
            'slug' => 'mekong-bakery',
            'status' => 'active',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        TenantContext::set($business);

        $response = $this->actingAs($user)
            ->withSession(['active_business_id' => $business->id])
            ->post(route('business.categories.store'), [
                'name_en' => 'Fresh Breads',
                'name_km' => 'នំបុ័ងស្រស់ៗ',
                'slug' => 'fresh-breads',
                'sort_order' => 1,
            ]);

        $response->assertRedirect(route('business.categories.index'));
        $this->assertDatabaseHas('categories', [
            'business_id' => $business->id,
            'name_en' => 'Fresh Breads',
        ]);
    }

    public function test_owner_can_create_product_with_variants(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Boba Tea Land',
            'slug' => 'boba-tea-land',
            'status' => 'active',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        TenantContext::set($business);

        $category = Category::create([
            'name_en' => 'Milk Teas',
            'slug' => 'milk-teas',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_business_id' => $business->id])
            ->post(route('business.products.store'), [
                'name_en' => 'Brown Sugar Milk Tea',
                'name_km' => 'តែទឹកដោះគោស្ករត្នោត',
                'slug' => 'brown-sugar-milk-tea',
                'category_id' => $category->id,
                'product_type' => 'beverage',
                'base_price' => 2.50,
                'cost_price' => 0.80,
                'is_available' => 1,
                'variants' => [
                    ['name_en' => 'Regular', 'price_adjustment' => 0.00],
                    ['name_en' => 'Large', 'price_adjustment' => 0.50],
                ],
            ]);

        $response->assertRedirect(route('business.products.index'));

        $this->assertDatabaseHas('products', [
            'business_id' => $business->id,
            'name_en' => 'Brown Sugar Milk Tea',
        ]);

        $product = Product::where('slug', 'brown-sugar-milk-tea')->first();
        $this->assertCount(2, $product->variants);
    }

    public function test_owner_can_add_staff_member(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Phnom Penh Mart',
            'slug' => 'pp-mart',
            'status' => 'active',
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->post(route('business.staff.store'), [
                'name' => 'Sophea Cashier',
                'email' => 'sophea@mart.com',
                'phone' => '+855 12 999 888',
                'role' => 'cashier',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'sophea@mart.com']);
        $this->assertDatabaseHas('business_members', [
            'business_id' => $business->id,
            'role' => 'cashier',
        ]);
    }

    public function test_user_can_switch_active_business_workspace(): void
    {
        $user = User::factory()->create();

        $biz1 = Business::create([
            'owner_id' => $user->id,
            'name' => 'Business One',
            'slug' => 'business-one',
            'status' => 'active',
        ]);
        BusinessMember::create([
            'business_id' => $biz1->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $biz2 = Business::create([
            'owner_id' => $user->id,
            'name' => 'KTV Lounge',
            'slug' => 'ktv-lounge',
            'status' => 'active',
        ]);
        BusinessMember::create([
            'business_id' => $biz2->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Initially on biz1, post switch to biz2
        $response = $this->actingAs($user)
            ->withSession(['active_business_id' => $biz1->id])
            ->post(route('business.switch', $biz2));

        $response->assertRedirect(route('business.dashboard'));
        $this->assertEquals($biz2->id, session('active_business_id'));
    }
}
