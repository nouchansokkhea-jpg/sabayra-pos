<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessPaymentConfig;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMenuAndOrderingTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;
    protected Category $category;
    protected Product $product;
    protected RestaurantTable $table;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();

        $this->owner = User::factory()->create();
        $this->business = Business::create([
            'owner_id' => $this->owner->id,
            'name' => 'Angkor Bistro',
            'slug' => 'angkor-bistro',
            'business_type' => 'restaurant',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'tax_percent' => 10.00,
        ]);

        TenantContext::set($this->business);

        $this->category = Category::create([
            'business_id' => $this->business->id,
            'name_en' => 'Main Courses',
            'name_km' => 'ម្ហូបចម្បង',
            'slug' => 'main-courses',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'category_id' => $this->category->id,
            'name_en' => 'Fish Amok',
            'name_km' => 'អាម៉ុកត្រី',
            'slug' => 'fish-amok',
            'base_price' => 6.50,
            'is_available' => true,
            'track_inventory' => true,
            'stock_quantity' => 20,
        ]);

        $this->table = RestaurantTable::create([
            'business_id' => $this->business->id,
            'table_number' => '05',
            'name' => 'Table 5',
            'capacity' => 4,
            'status' => 'available',
            'is_active' => true,
        ]);

        TenantContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_guest_can_view_public_digital_menu(): void
    {
        $response = $this->get("/{$this->business->slug}");

        $response->assertStatus(200);
        $response->assertSee('Angkor Bistro');
        $response->assertSee('Fish Amok');
        $response->assertSee('6.50');
    }

    public function test_guest_can_scan_table_qr_code_and_view_menu(): void
    {
        // Table QR scan route
        $response = $this->get("/{$this->business->slug}/table/05");

        $response->assertStatus(200);
        $response->assertSee('Angkor Bistro');
        $response->assertSee('05');
        $this->assertEquals('05', session("table_{$this->business->id}"));
    }

    public function test_customer_can_place_dine_in_order_via_checkout_api(): void
    {
        BusinessPaymentConfig::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'bakong_enabled' => true,
            'bakong_account_id' => 'angkor_bistro@aclb',
            'bakong_merchant_name' => 'Angkor Bistro',
            'bakong_verified' => true,
            'bakong_verified_at' => now(),
            'bakong_verification_status' => 'verified',
        ]);

        $payload = [
            'customer_name' => 'Sokha Meng',
            'customer_phone' => '012999888',
            'order_type' => 'dine_in',
            'table_number' => '05',
            'payment_method' => 'khqr',
            'notes' => 'Please make it extra spicy',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'notes' => 'No coriander',
                ],
            ],
        ];

        $response = $this->postJson("/{$this->business->slug}/checkout", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('orders', [
            'business_id' => $this->business->id,
            'customer_name' => 'Sokha Meng',
            'order_type' => 'dine_in',
            'subtotal' => 13.00, // 2 x 6.50
            'tax_amount' => 1.30, // 10% of 13.00
            'total_amount' => 14.30,
            'payment_method' => 'khqr',
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 6.50,
            'subtotal' => 13.00,
            'notes' => 'No coriander',
        ]);

        // Assert tracked inventory decremented: 20 - 2 = 18
        $this->assertEquals(18, $this->product->fresh()->stock_quantity);

        // Assert customer registered in database
        $this->assertDatabaseHas('customers', [
            'business_id' => $this->business->id,
            'phone' => '012999888',
            'total_orders' => 1,
        ]);
    }

    public function test_customer_can_order_with_variants_and_addons(): void
    {
        TenantContext::set($this->business);
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name_en' => 'Large',
            'price_adjustment' => 2.00,
        ]);

        $addon = ProductAddon::create([
            'product_id' => $this->product->id,
            'name_en' => 'Extra Coconut Milk',
            'price' => 1.00,
        ]);
        TenantContext::clear();

        $payload = [
            'customer_name' => 'Vannak Chem',
            'customer_phone' => '098112233',
            'order_type' => 'takeaway',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'variant_id' => $variant->id,
                    'addons' => [$addon->id],
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->postJson("/{$this->business->slug}/checkout", $payload);

        $response->assertStatus(200);

        // Subtotal: 6.50 (base) + 2.00 (variant) + 1.00 (addon) = 9.50
        // Tax 10% = 0.95, Total = 10.45, KHR = 10.45 * 4100 = 42845
        $this->assertDatabaseHas('orders', [
            'business_id' => $this->business->id,
            'order_type' => 'takeaway',
            'subtotal' => 9.50,
            'tax_amount' => 0.95,
            'total_amount' => 10.45,
            'total_khr' => 42800,
        ]);
    }

    public function test_customer_can_view_order_status_page(): void
    {
        TenantContext::set($this->business);
        $order = Order::create([
            'business_id' => $this->business->id,
            'order_number' => 'ORD-TEST-001',
            'order_type' => 'dine_in',
            'customer_name' => 'Sophea',
            'customer_phone' => '077889900',
            'subtotal' => 10.00,
            'tax_amount' => 1.00,
            'total_amount' => 11.00,
            'total_khr' => 45100,
            'currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'status' => 'preparing',
            'payment_status' => 'unpaid',
            'payment_method' => 'khqr',
        ]);
        TenantContext::clear();

        $response = $this->get("/{$this->business->slug}/order/{$order->order_number}");

        $response->assertStatus(200);
        $response->assertSee('ORD-TEST-001');
        $response->assertSee('Sophea');
        $response->assertSee('Preparing');
    }

    public function test_customer_can_place_takeaway_order_without_table_number(): void
    {
        $payload = [
            'customer_name' => 'Kong Takeaway',
            'customer_phone' => '098765432',
            'order_type' => 'takeaway',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->postJson("/{$this->business->slug}/checkout", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('orders', [
            'business_id' => $this->business->id,
            'customer_name' => 'Kong Takeaway',
            'order_type' => 'takeaway',
            'table_id' => null,
            'payment_method' => 'cash',
        ]);
    }

    public function test_customer_can_place_delivery_order_with_address(): void
    {
        $payload = [
            'customer_name' => 'Dara Delivery',
            'customer_phone' => '012888777',
            'order_type' => 'delivery',
            'delivery_address' => '#123 Street 2004, Phnom Penh',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->postJson("/{$this->business->slug}/checkout", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('orders', [
            'business_id' => $this->business->id,
            'customer_name' => 'Dara Delivery',
            'order_type' => 'delivery',
            'delivery_address' => '#123 Street 2004, Phnom Penh',
        ]);
    }

    public function test_customer_can_order_from_khmer_slug_business(): void
    {
        TenantContext::clear();
        $khmerBusiness = \App\Models\Business::create([
            'owner_id' => $this->owner->id,
            'name' => 'ហាងបាយឆ្ងាញ់',
            'slug' => 'បាយឆ្ងាញ់',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'tax_percent' => 0,
        ]);

        TenantContext::set($khmerBusiness);
        $product = \App\Models\Product::create([
            'business_id' => $khmerBusiness->id,
            'name_en' => 'Khmer Fried Rice',
            'slug' => 'khmer-fried-rice',
            'base_price' => 3.00,
            'is_available' => true,
        ]);
        TenantContext::clear();

        $payload = [
            'customer_name' => 'Bopha',
            'customer_phone' => '098111222',
            'order_type' => 'takeaway',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->postJson('/' . urlencode('បាយឆ្ងាញ់') . '/checkout', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('orders', [
            'business_id' => $khmerBusiness->id,
            'customer_name' => 'Bopha',
            'order_type' => 'takeaway',
        ]);
    }

    public function test_business_owner_can_manage_orders_and_status(): void
    {
        TenantContext::set($this->business);
        $order = Order::create([
            'business_id' => $this->business->id,
            'order_number' => 'ORD-STATUS-001',
            'order_type' => 'dine_in',
            'customer_name' => 'Dara Rath',
            'customer_phone' => '012334455',
            'subtotal' => 20.00,
            'total_amount' => 20.00,
            'total_khr' => 82000,
            'currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
        ]);
        TenantContext::clear();

        // 1. View Orders list
        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get('/app/orders');

        $response->assertStatus(200);
        $response->assertSee('ORD-STATUS-001');

        // 2. Transition status: Pending -> Confirmed
        $statusResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post("/app/orders/{$order->id}/status", [
                'status' => 'confirmed',
                'note' => 'Order verified by manager',
            ]);

        $statusResponse->assertRedirect();
        $this->assertEquals('confirmed', $order->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'confirmed',
            'note' => 'Order verified by manager',
        ]);

        // 3. Mark payment as Paid
        $paymentResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post("/app/orders/{$order->id}/payment", [
                'payment_status' => 'paid',
                'payment_method' => 'cash',
            ]);

        $paymentResponse->assertRedirect();
        $this->assertEquals('paid', $order->fresh()->payment_status);

        // 4. View 80mm Receipt
        $receiptResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get("/app/orders/{$order->id}/receipt");

        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee('ORD-STATUS-001');
        $receiptResponse->assertSee('Angkor Bistro');
    }

    public function test_business_owner_can_manage_tables(): void
    {
        // 1. View tables index
        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get('/app/tables');

        $response->assertStatus(200);
        $response->assertSee('#05');

        // 2. Add new table
        $storeResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post('/app/tables', [
                'table_number' => 'VIP-1',
                'name' => 'VIP Room 1',
                'capacity' => 8,
            ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('restaurant_tables', [
            'business_id' => $this->business->id,
            'table_number' => 'VIP-1',
            'capacity' => 8,
        ]);

        // Verify QR Code auto-generated
        $this->assertDatabaseHas('qr_codes', [
            'business_id' => $this->business->id,
            'type' => 'table',
            'code' => "QR-TBL-{$this->business->slug}-VIP-1",
        ]);
    }

    public function test_multiple_businesses_can_place_orders_on_same_day_without_order_number_collision(): void
    {
        $today = now()->format('Ymd');
        $expectedOrderNumber = "ORD-{$today}-0001";

        // First business places first takeaway order today
        $firstResponse = $this->postJson("/{$this->business->slug}/checkout", [
            'customer_name' => 'First Merchant Customer',
            'customer_phone' => '012111111',
            'order_type' => 'takeaway',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $firstResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'order_number' => $expectedOrderNumber,
            ]);

        // Second independent business places its first takeaway order today
        $otherOwner = User::factory()->create();
        $otherBusiness = Business::create([
            'owner_id' => $otherOwner->id,
            'name' => 'Second Cafe',
            'slug' => 'second-cafe',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'tax_percent' => 0,
        ]);

        TenantContext::set($otherBusiness);
        $otherProduct = Product::create([
            'business_id' => $otherBusiness->id,
            'name_en' => 'Iced Latte',
            'slug' => 'iced-latte',
            'base_price' => 2.50,
            'is_available' => true,
        ]);
        TenantContext::clear();

        $secondResponse = $this->postJson("/{$otherBusiness->slug}/checkout", [
            'customer_name' => 'Second Merchant Customer',
            'customer_phone' => '0986544',
            'order_type' => 'takeaway',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $otherProduct->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $secondResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'order_number' => $expectedOrderNumber,
            ]);

        $this->assertEquals(
            2,
            Order::withoutGlobalScopes()->where('order_number', $expectedOrderNumber)->count()
        );
    }

    public function test_sequential_orders_in_same_business_avoid_collisions_when_gaps_exist(): void
    {
        $today = now()->format('Ymd');

        TenantContext::set($this->business);
        Order::create([
            'business_id' => $this->business->id,
            'order_number' => "ORD-{$today}-0001",
            'order_type' => 'takeaway',
            'customer_name' => 'Early Guest',
            'customer_phone' => '011000111',
            'subtotal' => 5.00,
            'total_amount' => 5.00,
            'total_khr' => 20500,
            'currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
        ]);
        Order::create([
            'business_id' => $this->business->id,
            'order_number' => "ORD-{$today}-0003",
            'order_type' => 'takeaway',
            'customer_name' => 'Third Guest',
            'customer_phone' => '011000333',
            'subtotal' => 5.00,
            'total_amount' => 5.00,
            'total_khr' => 20500,
            'currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
        ]);
        TenantContext::clear();

        // Notice count() + 1 would wrongly produce 0003 and collide!
        $response = $this->postJson("/{$this->business->slug}/checkout", [
            'customer_name' => 'Next Guest',
            'customer_phone' => '099887766',
            'order_type' => 'delivery',
            'delivery_address' => 'Phnom Penh',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'order_number' => "ORD-{$today}-0004",
            ]);
    }
}
