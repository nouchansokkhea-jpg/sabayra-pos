<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\AiCreditLedger;
use App\Models\AiMessage;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\User;
use App\Services\AI\BusinessMetricsQueryService;
use App\Services\Tenant\TenantContext;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SabayraAiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlansSeeder::class);
        TenantContext::clear();
    }

    public function test_all_seven_read_only_tools_compute_accurate_tenant_scoped_metrics(): void
    {
        $ownerA = User::factory()->create();
        $businessA = Business::create([
            'owner_id' => $ownerA->id,
            'name' => 'Rumduol Cafe',
            'slug' => 'rumduol-cafe',
            'business_type' => 'cafe',
            'currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'status' => 'active',
        ]);

        $ownerB = User::factory()->create();
        $businessB = Business::create([
            'owner_id' => $ownerB->id,
            'name' => 'Other Secret Cafe',
            'slug' => 'other-secret-cafe',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        $catA = Category::create([
            'business_id' => $businessA->id,
            'name_en' => 'Drinks',
            'slug' => 'drinks',
            'is_active' => true,
        ]);

        $icedLatte = Product::create([
            'business_id' => $businessA->id,
            'category_id' => $catA->id,
            'name_en' => 'Iced Latte',
            'name_km' => 'ឡាតេទឹកកក',
            'slug' => 'iced-latte',
            'base_price' => 3.50,
            'cost_price' => 1.00,
            'track_inventory' => true,
            'stock_quantity' => 3,
            'low_stock_threshold' => 10,
            'is_available' => true,
        ]);

        $matchaCake = Product::create([
            'business_id' => $businessA->id,
            'category_id' => $catA->id,
            'name_en' => 'Matcha Cake',
            'slug' => 'matcha-cake',
            'base_price' => 4.50,
            'is_available' => true,
        ]);

        InventoryItem::create([
            'business_id' => $businessA->id,
            'name_en' => 'Fresh Milk',
            'unit' => 'liter',
            'current_stock' => 2.5,
            'low_stock_threshold' => 5.0,
            'unit_cost' => 1.80,
            'is_active' => true,
        ]);

        $orderA = Order::create([
            'business_id' => $businessA->id,
            'order_number' => 'ORD-A-001',
            'order_type' => 'dine_in',
            'subtotal' => 7.00,
            'total_amount' => 7.70,
            'tax_amount' => 0.70,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'bakong_khqr',
        ]);

        OrderItem::create([
            'order_id' => $orderA->id,
            'product_id' => $icedLatte->id,
            'product_name' => 'Iced Latte',
            'unit_price' => 3.50,
            'quantity' => 2,
            'subtotal' => 7.00,
        ]);

        $expCatA = ExpenseCategory::create([
            'business_id' => $businessA->id,
            'name' => 'Ice & Milk',
        ]);
        Expense::create([
            'business_id' => $businessA->id,
            'category_id' => $expCatA->id,
            'title' => 'Weekly Milk Delivery',
            'amount' => 2.00,
            'currency' => 'USD',
            'date' => now()->toDateString(),
        ]);

        // Noise order in Tenant B that must NEVER leak into Business A
        Order::create([
            'business_id' => $businessB->id,
            'order_number' => 'ORD-B-999',
            'order_type' => 'dine_in',
            'subtotal' => 5000.00,
            'total_amount' => 5000.00,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);

        /** @var BusinessMetricsQueryService $metrics */
        $metrics = app(BusinessMetricsQueryService::class);

        $sales = $metrics->executeTool($businessA, 'get_sales_summary', ['days' => 7]);
        $this->assertSame(1, $sales['paid_orders_count']);
        $this->assertEquals(7.70, $sales['paid_revenue_usd']);

        $top = $metrics->executeTool($businessA, 'get_top_selling_products', ['days' => 30, 'limit' => 5]);
        $this->assertSame('Iced Latte', $top['items'][0]['name_en']);
        $this->assertEquals(2.0, $top['items'][0]['quantity_sold']);

        $slow = $metrics->executeTool($businessA, 'get_low_selling_products', ['days' => 30, 'limit' => 5]);
        $this->assertSame('Matcha Cake', $slow['items'][0]['name_en']);
        $this->assertEquals(0.0, $slow['items'][0]['quantity_sold']);

        $lowStock = $metrics->executeTool($businessA, 'get_low_stock_products', ['limit' => 10]);
        $this->assertSame(2, $lowStock['low_stock_count']);

        $profit = $metrics->executeTool($businessA, 'get_expense_and_profit_summary', ['days' => 30]);
        $this->assertEquals(7.70, $profit['paid_revenue_usd']);
        $this->assertEquals(2.00, $profit['recorded_expenses_usd']);
        $this->assertEquals(2.00, $profit['estimated_product_cogs_usd']);
        $this->assertEquals(3.70, $profit['net_operating_estimate_usd']);

        $peaks = $metrics->executeTool($businessA, 'get_peak_hours', ['days' => 30]);
        $this->assertTrue($peaks['has_data']);
        $this->assertSame(1, $peaks['total_orders_analyzed']);

        $payments = $metrics->executeTool($businessA, 'get_payment_method_breakdown', ['days' => 30]);
        $this->assertSame('bakong_khqr', $payments['methods'][0]['payment_method']);
        $this->assertEquals(100.0, $payments['methods'][0]['share_pct']);

        $unknown = $metrics->executeTool($businessA, 'drop_database', []);
        $this->assertArrayHasKey('error', $unknown);
    }

    public function test_assistant_handles_khmer_prompt_executes_tools_persists_messages_and_charges_one_credit(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Phnom Penh Tea',
            'slug' => 'pp-tea',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        Order::create([
            'business_id' => $business->id,
            'order_number' => 'ORD-PPT-01',
            'order_type' => 'takeaway',
            'subtotal' => 12.00,
            'total_amount' => 12.00,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);

        PlatformSetting::setValue('ai.default_provider', 'gemini', false);
        PlatformSetting::setValue('ai.gemini_api_key', 'AIzaSyAssistantTestKey1234567890', true);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'functionCall' => [
                                'name' => 'get_sales_summary',
                                'args' => ['days' => 7],
                            ],
                        ]]],
                        'finishReason' => 'STOP',
                    ]],
                    'usageMetadata' => ['promptTokenCount' => 60, 'candidatesTokenCount' => 20, 'totalTokenCount' => 80],
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [['text' => 'សប្តាហ៍នេះហាងលក់បានសរុប $12.00 (49,200 ៛) ពីការបញ្ជាទិញ 1 ដង។']]],
                        'finishReason' => 'STOP',
                    ]],
                    'usageMetadata' => ['promptTokenCount' => 110, 'candidatesTokenCount' => 40, 'totalTokenCount' => 150],
                ], 200),
        ]);

        $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.ai.assistant'))
            ->assertOk()
            ->assertSee('New Business Assistant Session');

        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->postJson(route('business.ai.assistant.ask'), [
                'message' => 'តើសប្តាហ៍នេះលក់បានប៉ុន្មាន?',
                'request_uuid' => 'assistant-khmer-uuid-1',
            ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('user_message.language_detected', 'km')
            ->assertJsonPath('assistant_message.data_sources.0.tool', 'get_sales_summary')
            ->assertJsonPath('monthly_summary.used_credits', 1);

        $conversation = AiConversation::withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
        $this->assertSame(2, $conversation->message_count);

        $assistantMsg = AiMessage::withoutGlobalScopes()
            ->where('conversation_id', $conversation->id)
            ->where('role', 'assistant')
            ->firstOrFail();

        $this->assertSame('get_sales_summary', $assistantMsg->data_sources_json[0]['tool']);

        $ledger = AiCreditLedger::withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
        $this->assertSame(1, $ledger->used_credits);

        // Unauthorized staff (cashier without view_business_reports) cannot query Business Assistant
        $cashier = User::factory()->create();
        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $cashier->id,
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->actingAs($cashier)
            ->withSession(['active_business_id' => $business->id])
            ->postJson(route('business.ai.assistant.ask'), ['message' => 'Show profit'])
            ->assertForbidden();
    }
}
