<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryStorefrontAndBookingTest extends TestCase
{
    use RefreshDatabase;

    private function createPublicBiz(string $type, string $slug, string $name): array
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'slug' => $slug,
            'business_type' => $type,
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
        ]);

        $member = BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        return [$owner, $business, $member];
    }

    public function test_retail_storefront_renders_catalog_mode_and_blocks_dine_in_checkout(): void
    {
        [, $business] = $this->createPublicBiz('supermarket', 'mega-mart-kh', 'Mega Mart KH');

        $cat = Category::create([
            'business_id' => $business->id,
            'name_en' => 'Drinks',
            'slug' => 'drinks',
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'category_id' => $cat->id,
            'name_en' => 'Vital Water 1.5L',
            'slug' => 'vital-15l',
            'brand' => 'Vital',
            'sku' => 'SKU-VTL',
            'base_price' => 1.25,
            'is_available' => true,
        ]);

        $page = $this->get(route('public.menu', ['business_slug' => $business->slug]));
        $page->assertOk();
        $page->assertSee('Online Grocery Catalog');
        $page->assertSee('All Brands');
        $page->assertSee('In Stock Only');
        $page->assertDontSee('Table Number *');

        // Backend rejects dine_in for a supermarket store
        $this->postJson(route('public.checkout', ['business_slug' => $business->slug]), [
            'customer_name' => 'Dara',
            'customer_phone' => '012111222',
            'order_type' => 'dine_in',
            'table_number' => '01',
            'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);

        // Takeaway checkout succeeds
        $this->postJson(route('public.checkout', ['business_slug' => $business->slug]), [
            'customer_name' => 'Dara',
            'customer_phone' => '012111222',
            'order_type' => 'takeaway',
            'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertOk()->assertJson(['success' => true]);
    }

    public function test_milk_tea_storefront_displays_drink_customizer_and_saves_sugar_ice_options(): void
    {
        [, $business] = $this->createPublicBiz('milk_tea', 'boba-time', 'Boba Time');

        $cat = Category::create([
            'business_id' => $business->id,
            'name_en' => 'Milk Teas',
            'slug' => 'milk-teas',
            'is_active' => true,
        ]);

        $drink = Product::create([
            'business_id' => $business->id,
            'category_id' => $cat->id,
            'name_en' => 'Brown Sugar Pearl Latte',
            'slug' => 'brown-sugar-latte',
            'product_type' => 'beverage',
            'base_price' => 3.50,
            'is_available' => true,
        ]);

        $page = $this->get(route('public.menu', ['business_slug' => $business->slug]));
        $page->assertOk();
        $page->assertSee('Sugar Level');
        $page->assertSee('Ice Level');

        $this->postJson(route('public.checkout', ['business_slug' => $business->slug]), [
            'customer_name' => 'Kanya',
            'customer_phone' => '099888777',
            'order_type' => 'takeaway',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $drink->id,
                    'quantity' => 1,
                    'options' => ['temp' => 'Iced', 'sugar' => '50%', 'ice' => 'Less Ice'],
                ],
            ],
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $drink->id,
            'notes' => 'Iced · 50% · Less Ice',
        ]);
    }

    public function test_services_storefront_booking_prevents_double_booking_and_renders_status_pass(): void
    {
        [$owner, $salon, $staff] = $this->createPublicBiz('services', 'khmer-spa-clinic', 'Khmer Spa Clinic');

        $cat = Category::create([
            'business_id' => $salon->id,
            'name_en' => 'Treatments',
            'slug' => 'treatments',
            'is_active' => true,
        ]);

        $service = Product::create([
            'business_id' => $salon->id,
            'category_id' => $cat->id,
            'name_en' => 'Signature Aroma Massage',
            'slug' => 'aroma-massage',
            'product_type' => 'service',
            'duration_minutes' => 60,
            'base_price' => 25.00,
            'is_available' => true,
        ]);

        $page = $this->get(route('public.menu', ['business_slug' => $salon->slug]));
        $page->assertOk();
        $page->assertSee('Online Appointment Scheduler');
        $page->assertSee('Signature Aroma Massage');
        $page->assertSee('Book Slot');

        $date = now()->addDays(2)->toDateString();

        // Check available slots endpoint
        $this->getJson(route('public.booking-slots', [
            'business_slug' => $salon->slug,
            'date' => $date,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
        ]))->assertOk()->assertJsonFragment(['time' => '10:00', 'available' => true]);

        // First booking succeeds
        $bookResponse = $this->postJson(route('public.book', ['business_slug' => $salon->slug]), [
            'customer_name' => 'Channy M',
            'customer_phone' => '012777666',
            'service_product_id' => $service->id,
            'staff_member_id' => $staff->id,
            'reservation_date' => $date,
            'reservation_time' => '10:00',
        ]);

        $bookResponse->assertOk()->assertJson(['success' => true]);
        $bookingCode = $bookResponse->json('booking_code');
        $this->assertNotEmpty($bookingCode);

        // Overlapping booking for same staff at 10:00 fails with 422
        $this->postJson(route('public.book', ['business_slug' => $salon->slug]), [
            'customer_name' => 'Other Client',
            'customer_phone' => '012333444',
            'service_product_id' => $service->id,
            'staff_member_id' => $staff->id,
            'reservation_date' => $date,
            'reservation_time' => '10:00',
        ])->assertStatus(422);

        // Status pass renders cleanly
        $this->get(route('public.booking-status', [
            'business_slug' => $salon->slug,
            'booking_code' => $bookingCode,
        ]))->assertOk()->assertSee($bookingCode)->assertSee('Signature Aroma Massage');

        // Merchant QR studio hides dining tables for Services business
        $this->actingAs($owner)
            ->withSession(['active_business_id' => $salon->id])
            ->get(route('business.qr.index'))
            ->assertOk()
            ->assertDontSee('Dine-In Table QR Codes');
    }
}
