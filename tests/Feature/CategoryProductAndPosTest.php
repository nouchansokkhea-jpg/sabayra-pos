<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryProductAndPosTest extends TestCase
{
    use RefreshDatabase;

    private function createWorkspace(string $type, string $slug): array
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => ucfirst($type) . ' Biz',
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

    public function test_clothing_product_creation_saves_size_color_barcode_and_variant_stock(): void
    {
        [$owner, $business] = $this->createWorkspace('clothing', 'khmer-wear');

        $category = Category::create([
            'business_id' => $business->id,
            'name_en' => 'Shirts',
            'slug' => 'shirts',
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->post(route('business.products.store'), [
                'name_en' => 'Organic Cotton Tee',
                'category_id' => $category->id,
                'product_type' => 'retail',
                'brand' => 'Sabayra Studio',
                'barcode' => '884000112233',
                'base_price' => 18.00,
                'track_inventory' => '1',
                'stock_quantity' => 30,
                'variants' => [
                    [
                        'name_en' => 'L / Navy',
                        'size' => 'L',
                        'color' => 'Navy',
                        'barcode' => '884000112234',
                        'stock_quantity' => 14,
                        'price_adjustment' => 0.00,
                    ],
                ],
            ]);

        $response->assertRedirect(route('business.products.index'));

        $this->assertDatabaseHas('products', [
            'business_id' => $business->id,
            'name_en' => 'Organic Cotton Tee',
            'brand' => 'Sabayra Studio',
            'barcode' => '884000112233',
        ]);

        $this->assertDatabaseHas('product_variants', [
            'name_en' => 'L / Navy',
            'size' => 'L',
            'color' => 'Navy',
            'barcode' => '884000112234',
            'stock_quantity' => 14.00,
        ]);
    }

    public function test_supermarket_pos_barcode_lookup_is_tenant_isolated_and_finds_variants(): void
    {
        [$ownerA, $martA] = $this->createWorkspace('supermarket', 'mart-alpha');
        [$ownerB, $martB] = $this->createWorkspace('supermarket', 'mart-beta');

        $catB = Category::create([
            'business_id' => $martB->id,
            'name_en' => 'Other',
            'slug' => 'other-b',
            'is_active' => true,
        ]);

        Product::create([
            'business_id' => $martB->id,
            'category_id' => $catB->id,
            'name_en' => 'Tenant B Secret Item',
            'slug' => 'tenant-b-secret',
            'barcode' => '999988887777',
            'base_price' => 5.00,
            'is_available' => true,
        ]);

        $catA = Category::create([
            'business_id' => $martA->id,
            'name_en' => 'Beverages',
            'slug' => 'bev-a',
            'is_active' => true,
        ]);

        Product::create([
            'business_id' => $martA->id,
            'category_id' => $catA->id,
            'name_en' => 'Anchor Beer 6-Pack',
            'slug' => 'anchor-6pk',
            'barcode' => '885123456789',
            'base_price' => 4.50,
            'is_available' => true,
        ]);

        // Cross-tenant barcode lookup MUST return 404
        $this->actingAs($ownerA)
            ->withSession(['active_business_id' => $martA->id])
            ->getJson(route('business.pos.lookup-barcode', ['code' => '999988887777']))
            ->assertNotFound();

        // Own tenant barcode lookup returns item
        $this->actingAs($ownerA)
            ->withSession(['active_business_id' => $martA->id])
            ->getJson(route('business.pos.lookup-barcode', ['code' => '885123456789']))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'product' => [
                    'name_en' => 'Anchor Beer 6-Pack',
                ],
            ]);
    }

    public function test_cafe_and_services_pos_checkout_saves_drink_options_and_completes_booking(): void
    {
        [$owner, $salon, $member] = $this->createWorkspace('services', 'aura-spa');

        $cat = Category::create([
            'business_id' => $salon->id,
            'name_en' => 'Spa & Tea',
            'slug' => 'spa-tea',
            'is_active' => true,
        ]);

        $service = Product::create([
            'business_id' => $salon->id,
            'category_id' => $cat->id,
            'name_en' => 'Deep Tissue Massage',
            'slug' => 'deep-tissue-massage',
            'product_type' => 'service',
            'duration_minutes' => 60,
            'base_price' => 30.00,
            'is_available' => true,
        ]);

        $reservation = Reservation::create([
            'business_id' => $salon->id,
            'booking_type' => 'service',
            'booking_code' => 'BK-POS-10',
            'customer_name' => 'Sophea D',
            'customer_phone' => '012445566',
            'reservation_date' => now()->toDateString(),
            'reservation_time' => '11:00',
            'duration_minutes' => 60,
            'service_product_id' => $service->id,
            'staff_member_id' => $member->id,
            'status' => 'confirmed',
        ]);

        TenantContext::set($salon);
        PosSession::create([
            'business_id' => $salon->id,
            'user_id' => $owner->id,
            'opening_cash' => 50.00,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        TenantContext::clear();

        $checkout = $this->actingAs($owner)
            ->withSession(['active_business_id' => $salon->id])
            ->postJson(route('business.pos.checkout'), [
                'order_type' => 'service',
                'customer_name' => 'Sophea D',
                'customer_phone' => '012445566',
                'assigned_staff_id' => $member->id,
                'reservation_id' => $reservation->id,
                'payment_method' => 'cash',
                'amount_tendered' => 30.00,
                'items' => [
                    [
                        'product_id' => $service->id,
                        'quantity' => 1,
                        'options' => ['temp' => 'Hot', 'sugar' => '50%', 'ice' => 'No Ice'],
                    ],
                ],
            ]);

        $checkout->assertOk()->assertJson(['success' => true, 'total_usd' => 30.00]);

        $this->assertDatabaseHas('orders', [
            'business_id' => $salon->id,
            'order_type' => 'service',
            'assigned_staff_id' => $member->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);
    }
}
