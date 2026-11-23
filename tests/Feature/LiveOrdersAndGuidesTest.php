<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LiveOrdersAndGuidesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kitchen', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->business = Business::create([
            'owner_id' => $this->user->id,
            'name' => 'SABAYRA Coffee',
            'slug' => 'sabayra-coffee',
            'category' => 'cafe',
            'status' => 'active',
        ]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);
    }

    public function test_merchant_can_access_live_orders_feed(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.orders.live'));

        $response->assertStatus(200);
        $response->assertSee('Live Orders');
        $response->assertSee('Real-Time Kitchen');
    }

    public function test_live_orders_poll_api_returns_active_orders_json(): void
    {
        $order = Order::create([
            'business_id' => $this->business->id,
            'order_number' => 'ORD-LIVE-001',
            'order_type' => 'dine_in',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'subtotal' => 15.00,
            'total_amount' => 15.00,
            'total_usd' => 15.00,
            'total_khr' => 61500,
            'customer_name' => 'Chanthy',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.orders.live.poll'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'orders',
            'has_pending',
            'total_active',
            'timestamp',
        ]);
        $response->assertJson([
            'has_pending' => true,
            'total_active' => 1,
        ]);
    }

    public function test_merchant_can_access_order_flow_guide(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.guides.order-flow'));

        $response->assertStatus(200);
        $response->assertSee('Interactive Operational Flow Architecture');
        $response->assertSee('Order Placement');
    }

    public function test_merchant_can_access_tutorials_guide(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.guides.tutorials'));

        $response->assertStatus(200);
        $response->assertSee('Masterclasses');
        $response->assertSee('Setting Up Products');
    }
}
