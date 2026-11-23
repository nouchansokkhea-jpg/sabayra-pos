<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\User;
use App\Services\Payment\KHQRService;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosAndInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;
    protected Category $category;
    protected Product $product;
    protected InventoryItem $riceItem;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();

        $this->owner = User::factory()->create();
        $this->business = Business::create([
            'owner_id' => $this->owner->id,
            'name' => 'Phnom Penh Bistro',
            'slug' => 'phnom-penh-bistro',
            'business_type' => 'restaurant',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'tax_percent' => 10.00,
        ]);

        TenantContext::set($this->business);

        $this->category = Category::create([
            'business_id' => $this->business->id,
            'name_en' => 'Rice Dishes',
            'name_km' => 'បាយ',
            'slug' => 'rice-dishes',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'category_id' => $this->category->id,
            'name_en' => 'Lok Lak Beef Rice',
            'name_km' => 'បាយឡុកឡាក់សាច់គោ',
            'slug' => 'lok-lak-beef-rice',
            'base_price' => 5.00,
            'is_available' => true,
            'track_inventory' => true,
            'stock_quantity' => 50,
        ]);

        // Raw material
        $this->riceItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'name_en' => 'Jasmine Rice',
            'unit' => 'kg',
            'current_stock' => 100.000,
            'low_stock_threshold' => 10.000,
            'unit_cost' => 0.80,
            'is_active' => true,
        ]);

        // Create recipe for Lok Lak: uses 0.25 kg rice per dish
        $recipe = Recipe::create([
            'product_id' => $this->product->id,
            'yield_quantity' => 1,
            'notes' => 'Standard portion',
        ]);

        RecipeItem::create([
            'recipe_id' => $recipe->id,
            'inventory_item_id' => $this->riceItem->id,
            'quantity' => 0.250,
            'unit' => 'kg',
        ]);

        TenantContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_cashier_can_open_pos_shift_session(): void
    {
        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post('/app/pos/open-session', [
                'opening_cash' => 50.00,
            ]);

        $response->assertRedirect('/app/pos');

        $this->assertDatabaseHas('pos_sessions', [
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'opening_cash' => 50.00,
            'status' => 'open',
        ]);
    }

    public function test_cashier_can_perform_pos_checkout_with_recipe_deduction(): void
    {
        TenantContext::set($this->business);
        $session = PosSession::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'opening_cash' => 50.00,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        TenantContext::clear();

        // 2x Lok Lak Beef Rice: subtotal = 2 x $5.00 = $10.00, tax 10% = $1.00, total = $11.00
        // Expected rice deduction: 2 x 0.25 kg = 0.50 kg (balance: 100 - 0.50 = 99.50 kg)
        // Expected product stock deduction: 50 - 2 = 48
        $payload = [
            'order_type' => 'dine_in',
            'table_number' => '01',
            'customer_name' => 'Kosal Vuth',
            'customer_phone' => '017223344',
            'payment_method' => 'cash',
            'amount_tendered' => 20.00,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->postJson('/app/pos/checkout', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total_usd' => 11.00,
                'change_usd' => 9.00, // $20 - $11 = $9
            ]);

        // Assert Order created & paid
        $this->assertDatabaseHas('orders', [
            'business_id' => $this->business->id,
            'customer_name' => 'Kosal Vuth',
            'total_amount' => 11.00,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);

        // Assert POS transaction created
        $this->assertDatabaseHas('pos_transactions', [
            'pos_session_id' => $session->id,
            'business_id' => $this->business->id,
            'amount' => 11.00,
            'amount_tendered' => 20.00,
            'change_given' => 9.00,
            'payment_method' => 'cash',
        ]);

        // Assert Product stock decremented: 50 - 2 = 48
        $this->assertEquals(48, $this->product->fresh()->stock_quantity);

        // Assert Raw Material automatically deducted by RecipeDeductionService: 100 - 0.5 = 99.5 kg
        $this->assertEquals(99.500, (float) $this->riceItem->fresh()->current_stock);

        // Assert Inventory Movement logged
        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $this->business->id,
            'inventory_item_id' => $this->riceItem->id,
            'type' => 'recipe_deduction',
            'balance_after' => 99.500,
        ]);
    }

    public function test_cashier_can_close_pos_shift_session(): void
    {
        TenantContext::set($this->business);
        $session = PosSession::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'opening_cash' => 50.00,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        TenantContext::clear();

        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post("/app/pos/close-session/{$session->id}", [
                'closing_cash' => 50.00,
            ]);

        $response->assertRedirect('/app/pos');

        $this->assertEquals('closed', $session->fresh()->status);
        $this->assertEquals(50.00, (float)$session->fresh()->closing_cash);
        $this->assertNotNull($session->fresh()->closed_at);
    }

    public function test_merchant_can_manage_inventory_and_adjust_stock(): void
    {
        // 1. View Inventory Index
        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get('/app/inventory');

        $response->assertStatus(200);
        $response->assertSee('Jasmine Rice');

        // 2. Add New Raw Material
        $storeResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post('/app/inventory', [
                'name_en' => 'Black Kampot Pepper',
                'name_km' => 'ម្រេចកំពតខ្មៅ',
                'sku' => 'RAW-PEP-01',
                'unit' => 'kg',
                'current_stock' => 5.000,
                'low_stock_threshold' => 1.000,
                'unit_cost' => 12.00,
            ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('inventory_items', [
            'business_id' => $this->business->id,
            'name_en' => 'Black Kampot Pepper',
            'current_stock' => 5.000,
        ]);

        // 3. Adjust Stock (Restock in 5kg)
        $pepper = InventoryItem::where('sku', 'RAW-PEP-01')->first();
        $adjustResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post("/app/inventory/{$pepper->id}/adjust", [
                'type' => 'in',
                'quantity' => 5.000,
                'reason' => 'Direct farm purchase',
            ]);

        $adjustResponse->assertRedirect();
        $this->assertEquals(10.000, (float)$pepper->fresh()->current_stock);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $pepper->id,
            'type' => 'in',
            'quantity' => 5.000,
            'balance_after' => 10.000,
        ]);
    }

    public function test_merchant_can_view_reports_and_export_csv(): void
    {
        TenantContext::set($this->business);
        Order::create([
            'business_id' => $this->business->id,
            'order_number' => 'ORD-REP-01',
            'order_type' => 'dine_in',
            'customer_name' => 'Chea',
            'customer_phone' => '011223344',
            'subtotal' => 25.00,
            'tax_amount' => 2.50,
            'total_amount' => 27.50,
            'total_khr' => 112750,
            'currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'khqr',
        ]);

        $qrCode = \App\Models\QrCode::create([
            'business_id' => $this->business->id,
            'type' => 'menu',
            'code' => 'TEST-QR-SCAN',
            'target_url' => 'http://localhost/test',
            'scan_count' => 12,
        ]);

        \App\Models\QrScanEvent::create([
            'qr_code_id' => $qrCode->id,
            'business_id' => $this->business->id,
            'scanned_at' => now(),
        ]);
        TenantContext::clear();

        // 1. View Reports Page
        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get('/app/reports');

        $response->assertStatus(200);
        $response->assertSee('27.50');
        $response->assertSee('Financial Analytics');

        // 2. Export CSV
        $exportResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get('/app/reports/export');

        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_khqr_service_generates_valid_emvco_string(): void
    {
        $khqr = KHQRService::generate($this->business, 15.50, 'USD', 'BILL-1001');

        $this->assertIsString($khqr);
        $this->assertStringStartsWith('000201010212', $khqr); // Tag 00='01', Tag 01='12'
        $this->assertStringContainsString('5303840', $khqr); // USD code 840
        $this->assertStringContainsString('540515.50', $khqr); // Amount $15.50
        $this->assertStringContainsString('5802KH', $khqr); // KH Country
        $this->assertStringContainsString('6304', $khqr); // Tag 63 Checksum length 4
    }
}
