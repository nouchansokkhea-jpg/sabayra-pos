<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Printer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderFlowAndPrinterTest extends TestCase
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
            'name' => 'SABAYRA Test Kitchen',
            'slug' => 'sabayra-test-kitchen',
            'category' => 'restaurant',
            'status' => 'active',
        ]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);
    }

    public function test_user_can_access_workflow_guide(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.workflow-guide'));

        $response->assertStatus(200);
        $response->assertSee('Order Flow Guide');
        $response->assertSee('Customer Order');
        $response->assertSee('Staff Confirmation');
        $response->assertSee('Kitchen Preparation');
        $response->assertSee('Ready to Serve');
        $response->assertSee('Invoice');
        $response->assertSee('Payment');
        $response->assertSee('Complete');
        $response->assertSee(route('business.orders.live'));
        $response->assertSee(route('business.kitchen.index'));
        $response->assertSee(route('business.printers.index'));
    }

    public function test_legacy_business_workflow_guide_redirects(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/business/workflow-guide');

        $response->assertRedirect('/app/workflow-guide');
    }

    public function test_user_can_view_printers_page(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.printers.index'));

        $response->assertStatus(200);
        $response->assertSee('Printers');
        $response->assertSee('Add New Printer');
        $response->assertSee('Quick Print');
    }

    public function test_user_can_create_update_and_delete_printer(): void
    {
        // 1. Create
        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.printers.store'), [
                'name' => 'Counter Thermal 1',
                'printer_type' => 'receipt',
                'connection_type' => 'network',
                'printing_purpose' => 'both',
                'paper_size' => '80mm',
                'ip_address' => '192.168.1.100',
                'port' => 9100,
                'is_default' => '1',
            ]);

        $response->assertRedirect(route('business.printers.index'));
        $this->assertDatabaseHas('printers', [
            'business_id' => $this->business->id,
            'name' => 'Counter Thermal 1',
            'ip_address' => '192.168.1.100',
            'is_default' => true,
        ]);

        $printer = Printer::where('name', 'Counter Thermal 1')->firstOrFail();

        // 2. Update
        $updateResponse = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.printers.update', $printer), [
                'name' => 'Counter Thermal Updated',
                'printer_type' => 'kitchen',
                'connection_type' => 'network',
                'printing_purpose' => 'kitchen',
                'paper_size' => '80mm',
                'ip_address' => '192.168.1.105',
                'port' => 9100,
            ]);

        $updateResponse->assertRedirect(route('business.printers.index'));
        $this->assertDatabaseHas('printers', [
            'id' => $printer->id,
            'name' => 'Counter Thermal Updated',
            'ip_address' => '192.168.1.105',
        ]);

        // 3. Test print slip
        $testResponse = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.printers.test-print', $printer));

        $testResponse->assertStatus(200);
        $testResponse->assertSee('PRINTER SELF-TEST SLIP');
        $this->assertNotNull($printer->fresh()->last_used_at);

        // 4. Delete
        $deleteResponse = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->delete(route('business.printers.destroy', $printer));

        $deleteResponse->assertRedirect(route('business.printers.index'));
        $this->assertDatabaseMissing('printers', ['id' => $printer->id]);
    }

    public function test_kitchen_ticket_omits_pricing_and_displays_order_details(): void
    {
        \App\Services\Tenant\TenantContext::set($this->business);

        $order = Order::create([
            'business_id' => $this->business->id,
            'order_number' => 'ORD-9988',
            'order_type' => 'dine_in',
            'status' => 'preparing',
            'payment_status' => 'paid',
            'currency' => 'USD',
            'subtotal' => 18.50,
            'total_amount' => 18.50,
        ]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'name_en' => 'Lok Lak Beef Rice',
            'name_km' => 'បាយសាច់គោឡុកឡាក់',
            'slug' => 'lok-lak-beef-rice',
            'price_usd' => 5.50,
            'is_available' => true,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Lok Lak Beef Rice',
            'quantity' => 2,
            'unit_price' => 5.50,
            'subtotal' => 11.00,
            'notes' => 'No onions please',
        ]);

        \App\Services\Tenant\TenantContext::clear();

        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.orders.kitchen-ticket', $order));

        $response->assertStatus(200);
        $response->assertSee('KITCHEN ORDER DOCKET');
        $response->assertSee('ORD-9988');
        $response->assertSee('Lok Lak Beef Rice');
        $response->assertSee('No onions please');
        // Must NOT see financial symbols or price
        $response->assertDontSee('$18.50');
        $response->assertDontSee('$11.00');
        $response->assertDontSee('$5.50');
    }

    public function test_tenant_cannot_access_or_modify_other_tenants_printers(): void
    {
        $otherUser = User::factory()->create();
        $otherBusiness = Business::create([
            'owner_id' => $otherUser->id,
            'name' => 'Other Merchant',
            'slug' => 'other-merchant',
            'status' => 'active',
        ]);
        BusinessMember::create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherUser->id,
            'role' => 'owner',
        ]);

        $otherPrinter = Printer::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Secret Printer',
            'connection_type' => 'network',
            'ip_address' => '10.0.0.1',
        ]);

        // Attempt to update other tenant's printer
        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.printers.update', $otherPrinter), [
                'name' => 'Hacked Printer',
            ]);

        $response->assertStatus(403);
        $this->assertEquals('Secret Printer', $otherPrinter->fresh()->name);
    }
}
