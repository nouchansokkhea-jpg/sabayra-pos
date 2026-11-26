<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessPaymentConfig;
use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentProof;
use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\Payment\PaymentGatewayVisibilityService;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentAndTaxUpgradeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $otherOwner;
    private User $superAdmin;
    private Business $business;
    private Business $otherBusiness;
    private Product $product;
    private RestaurantTable $table;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();
        Storage::fake('local');
        Storage::fake('public');

        $this->owner = User::factory()->create();
        $this->otherOwner = User::factory()->create();
        $this->superAdmin = User::factory()->create(['is_super_admin' => true]);

        $this->business = Business::create([
            'owner_id' => $this->owner->id,
            'name' => 'Sabayra Bistro',
            'slug' => 'sabayra-bistro',
            'business_type' => 'restaurant',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
            'tax_name' => 'VAT',
            'tax_percent' => 10.00,
            'tax_inclusive' => false,
            'service_charge_enabled' => false,
            'service_charge_rate' => 0.00,
        ]);

        $this->otherBusiness = Business::create([
            'owner_id' => $this->otherOwner->id,
            'name' => 'Other Store',
            'slug' => 'other-store',
            'business_type' => 'cafe',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
        ]);

        TenantContext::set($this->business);

        $category = Category::create([
            'business_id' => $this->business->id,
            'name_en' => 'Mains',
            'name_km' => 'ម្ហូប',
            'slug' => 'mains',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'category_id' => $category->id,
            'name_en' => 'Lok Lak',
            'name_km' => 'ឡុកឡាក់',
            'slug' => 'lok-lak',
            'base_price' => 10.00,
            'is_available' => true,
        ]);

        $this->table = RestaurantTable::create([
            'business_id' => $this->business->id,
            'table_number' => 'T1',
            'name' => 'Table 1',
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

    public function test_tax_exclusive_and_inclusive_calculations_and_historical_order_snapshots(): void
    {
        // 1. Tax Exclusive: $10.00 @ 10% VAT => Subtotal $10.00, Tax $1.00, Total $11.00
        $resExclusive = $this->postJson("/{$this->business->slug}/checkout", [
            'customer_name' => 'Exclusive Guest',
            'customer_phone' => '012111222',
            'order_type' => 'takeaway',
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ]);

        $resExclusive->assertOk()->assertJsonPath('success', true);

        $exclusiveOrder = Order::withoutGlobalScopes()
            ->where('order_number', $resExclusive->json('order_number'))
            ->firstOrFail();

        $this->assertEquals(10.00, (float) $exclusiveOrder->subtotal);
        $this->assertEquals(1.00, (float) $exclusiveOrder->tax_amount);
        $this->assertEquals(11.00, (float) $exclusiveOrder->total_amount);
        $this->assertSame('VAT', $exclusiveOrder->tax_name);
        $this->assertEquals(10.00, (float) $exclusiveOrder->tax_rate);
        $this->assertFalse((bool) $exclusiveOrder->tax_inclusive);

        // 2. Switch business to Tax Inclusive (GST 10%) + Service Charge (5%)
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.payment.update'), [
                'default_currency' => 'USD',
                'exchange_rate_khr' => 4100,
                'tax_name' => 'GST',
                'tax_percent' => 10.00,
                'tax_inclusive' => '1',
                'service_charge_enabled' => '1',
                'service_charge_rate' => 5.00,
                'cash_enabled' => '1',
            ])
            ->assertRedirect(route('business.settings.index', ['section' => 'payment']));

        // Historical order MUST remain unchanged
        $exclusiveOrder->refresh();
        $this->assertSame('VAT', $exclusiveOrder->tax_name);
        $this->assertFalse((bool) $exclusiveOrder->tax_inclusive);
        $this->assertEquals(11.00, (float) $exclusiveOrder->total_amount);

        // 3. Tax Inclusive ($10.00) + Service Charge 5% ($0.50)
        // Tax component = 10 * 10 / 110 = 0.91, Total = 10.00 + 0.50 = 10.50
        $resInclusive = $this->postJson("/{$this->business->slug}/checkout", [
            'customer_name' => 'Inclusive Guest',
            'customer_phone' => '012333444',
            'order_type' => 'takeaway',
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ]);

        $resInclusive->assertOk();
        $inclusiveOrder = Order::withoutGlobalScopes()
            ->where('order_number', $resInclusive->json('order_number'))
            ->firstOrFail();

        $this->assertSame('GST', $inclusiveOrder->tax_name);
        $this->assertTrue((bool) $inclusiveOrder->tax_inclusive);
        $this->assertEquals(0.91, (float) $inclusiveOrder->tax_amount);
        $this->assertTrue((bool) $inclusiveOrder->service_charge_enabled);
        $this->assertEquals(5.00, (float) $inclusiveOrder->service_charge_rate);
        $this->assertEquals(0.50, (float) $inclusiveOrder->service_charge_amount);
        $this->assertEquals(10.50, (float) $inclusiveOrder->total_amount);

        // Verify thermal receipt renders service charge & tax snapshot cleanly
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.orders.receipt', $inclusiveOrder))
            ->assertOk()
            ->assertSee('Service Charge (5.00%)')
            ->assertSee('GST (10.00% incl.)')
            ->assertSee('$10.50');
    }

    public function test_service_charge_disabled_and_pos_consistency(): void
    {
        $this->business->update([
            'tax_name' => 'VAT',
            'tax_percent' => 10.00,
            'tax_inclusive' => false,
            'service_charge_enabled' => false,
            'service_charge_rate' => 12.00,
        ]);

        PosSession::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'opening_cash' => 50.00,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $posRes = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->postJson(route('business.pos.checkout'), [
                'order_type' => 'takeaway',
                'payment_method' => 'cash',
                'amount_tendered' => 20.00,
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1],
                ],
            ]);

        $posRes->assertOk()->assertJsonPath('total_usd', 11);

        $posOrder = Order::withoutGlobalScopes()
            ->where('order_number', $posRes->json('order_number'))
            ->firstOrFail();

        $this->assertFalse((bool) $posOrder->service_charge_enabled);
        $this->assertEquals(0.00, (float) $posOrder->service_charge_amount);
        $this->assertEquals(1.00, (float) $posOrder->tax_amount);
        $this->assertEquals(11.00, (float) $posOrder->total_amount);
    }

    public function test_bank_transfer_proof_upload_private_storage_and_staff_approval_rejection(): void
    {
        // Validation rejects incomplete bank details
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.payment.update'), [
                'default_currency' => 'USD',
                'exchange_rate_khr' => 4100,
                'bank_transfer_enabled' => '1',
                'bank_name' => '',
            ])
            ->assertSessionHasErrors('bank_name');

        // Configure complete Bank Transfer
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.payment.update'), [
                'default_currency' => 'USD',
                'exchange_rate_khr' => 4100,
                'bank_transfer_enabled' => '1',
                'bank_name' => 'ABA Bank',
                'bank_account_holder' => 'Sabayra Co Ltd',
                'bank_account_number' => '001234567',
                'bank_instructions' => 'Send exact USD amount.',
                'bank_reference_instructions' => 'Put order number in note.',
            ])
            ->assertRedirect();

        $checkout = $this->postJson("/{$this->business->slug}/checkout", [
            'customer_name' => 'Dara Transfer',
            'customer_phone' => '012444555',
            'order_type' => 'takeaway',
            'payment_method' => 'bank_transfer',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ])->assertOk();

        $order = Order::withoutGlobalScopes()->where('order_number', $checkout->json('order_number'))->firstOrFail();
        $proofToken = $checkout->json('proof_token');
        $this->assertSame('unpaid', $order->payment_status);

        // Disguised PHP/text upload claiming .png extension must fail
        $spoofFile = UploadedFile::fake()->createWithContent('evil.png', '<?php echo "hack";');
        $this->postJson("/{$this->business->slug}/order/{$order->order_number}/proof", [
            'proof_token' => $proofToken,
            'proof_file' => $spoofFile,
        ])->assertStatus(422);

        // Unauthorized / missing token upload must return 403
        $validPng = UploadedFile::fake()->image('transfer.png', 200, 200);
        $this->post("/{$this->business->slug}/order/{$order->order_number}/proof", [
            'proof_token' => 'wrong-token',
            'proof_file' => $validPng,
        ])->assertStatus(403);

        // Valid upload stores file privately and moves order to pending_verification (NOT paid)
        $uploadRes = $this->postJson("/{$this->business->slug}/order/{$order->order_number}/proof", [
            'proof_token' => $proofToken,
            'proof_file' => $validPng,
            'customer_reference' => 'ABA-REF-991',
            'customer_note' => 'Transferred from my ABA app',
        ])->assertStatus(201);

        $order->refresh();
        $this->assertSame('pending_verification', $order->payment_status);
        $this->assertNull($order->paid_at);

        $proof = PaymentProof::withoutGlobalScopes()->where('order_id', $order->id)->firstOrFail();
        Storage::disk('local')->assertExists($proof->file_path);
        $this->assertNotEmpty($proof->encoded_content);
        $this->assertArrayNotHasKey('encoded_content', $proof->toArray());

        // Cross-tenant staff cannot view proof or approve payment in another workspace
        TenantContext::clear();

        $this->actingAs($this->otherOwner)
            ->withSession(['active_business_id' => $this->otherBusiness->id])
            ->get(route('business.orders.payment-proof.show', [$order, $proof]))
            ->assertStatus(403);

        TenantContext::clear();

        $this->actingAs($this->otherOwner)
            ->withSession(['active_business_id' => $this->otherBusiness->id])
            ->post(route('business.orders.payment.approve', $order))
            ->assertStatus(403);

        TenantContext::clear();

        // Authorized staff can view order details page & private proof image without 500 errors
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.orders.show', $order))
            ->assertOk()
            ->assertSee('Customer Payment Proofs')
            ->assertSee('ABA-REF-991')
            ->assertSee('Approve Payment Proof');

        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.orders.payment-proof.show', [$order, $proof]))
            ->assertOk();

        // Simulate ephemeral container restart wiping local disk; PostgreSQL backup must still stream 200 PNG and re-hydrate local disk
        Storage::disk('local')->delete($proof->file_path);
        Storage::disk('local')->assertMissing($proof->file_path);

        $rehydratedResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.orders.payment-proof.show', [$order, $proof]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->assertStringStartsWith("\x89PNG", (string) $rehydratedResponse->getContent());
        Storage::disk('local')->assertExists($proof->file_path);

        // Staff rejects first, then customer/admin approves after review
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.orders.payment.reject', $order), [
                'review_note' => 'Blurry slip, please resend.',
            ])
            ->assertRedirect();

        $this->assertSame('rejected', $order->fresh()->payment_status);

        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.orders.payment.approve', $order), [
                'review_note' => 'Verified in ABA merchant portal.',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame($this->owner->id, $order->payment_verified_by);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('approved', $proof->fresh()->status);

        // Order details view also renders reviewedBy and verification timestamp cleanly after approval
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.orders.show', $order))
            ->assertOk()
            ->assertSee('Verified in ABA merchant portal.');

        // Legacy pre-upgrade proofs missing both disk file and encoded_content render clean fallback HTML/SVG instead of 404
        Storage::disk('local')->delete($proof->file_path);
        PaymentProof::withoutGlobalScopes()->whereKey($proof->id)->update(['encoded_content' => null]);

        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->withHeaders(['Accept' => 'text/html,application/xhtml+xml'])
            ->get(route('business.orders.payment-proof.show', [$order, $proof]))
            ->assertOk()
            ->assertSee('Original Screenshot Expired on Temporary Container Storage')
            ->assertSee('ABA-REF-991');

        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->withHeaders(['Accept' => 'image/avif,image/webp,image/png,*/*;q=0.8'])
            ->get(route('business.orders.payment-proof.show', [$order, $proof]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8');
    }

    public function test_static_qr_image_lifecycle_and_cod_collection_flow(): void
    {
        // Enabling static QR without uploading an image fails
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.payment.update'), [
                'default_currency' => 'USD',
                'exchange_rate_khr' => 4100,
                'static_qr_enabled' => '1',
                'static_qr_label' => 'Wing Scan QR',
            ])
            ->assertSessionHasErrors('static_qr_image');

        $qrImg = UploadedFile::fake()->image('merchant-qr.png', 300, 300);

        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.payment.update'), [
                'default_currency' => 'USD',
                'exchange_rate_khr' => 4100,
                'static_qr_enabled' => '1',
                'static_qr_label' => 'Wing Scan QR',
                'static_qr_image' => $qrImg,
                'cod_enabled' => '1',
                'cod_instructions' => 'Prepare exact change.',
            ])
            ->assertRedirect();

        $cfg = BusinessPaymentConfig::resolveForBusiness($this->business);
        $this->assertTrue($cfg->static_qr_enabled);
        $this->assertNotNull($cfg->static_qr_image_path);

        // Public storefront displays the static QR custom label and COD
        $this->get("/{$this->business->slug}")
            ->assertOk()
            ->assertSee('Wing Scan QR')
            ->assertSee('Cash on Delivery');

        // COD checkout on dine_in is rejected
        $this->postJson("/{$this->business->slug}/checkout", [
            'customer_name' => 'COD Bad Channel',
            'customer_phone' => '012000111',
            'order_type' => 'dine_in',
            'table_number' => 'T1',
            'payment_method' => 'cod',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ])->assertStatus(422);

        // COD checkout on delivery starts pending_collection (NOT paid)
        $codCheckout = $this->postJson("/{$this->business->slug}/checkout", [
            'customer_name' => 'COD Rider Guest',
            'customer_phone' => '012000222',
            'order_type' => 'delivery',
            'delivery_address' => 'Street 271',
            'payment_method' => 'cod',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ])->assertOk();

        $codOrder = Order::withoutGlobalScopes()->where('order_number', $codCheckout->json('order_number'))->firstOrFail();
        $this->assertSame('pending_collection', $codOrder->payment_status);
        $this->assertNull($codOrder->paid_at);

        // Staff collects COD cash
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.orders.payment.collect-cod', $codOrder), [
                'collection_note' => 'Collected by driver Sok.',
            ])
            ->assertRedirect();

        $codOrder->refresh();
        $this->assertSame('paid', $codOrder->payment_status);
        $this->assertSame($this->owner->id, $codOrder->payment_collected_by);
        $this->assertNotNull($codOrder->payment_collected_at);
    }

    public function test_aba_payway_encryption_test_connection_dynamic_qr_and_signed_webhook(): void
    {
        // Reject untrusted PayWay URL
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.payment.update'), [
                'default_currency' => 'USD',
                'exchange_rate_khr' => 4100,
                'aba_payway_enabled' => '1',
                'aba_payway_link' => 'https://evil-phishing.example.com/pay',
            ])
            ->assertSessionHasErrors('aba_payway_link');

        $secretKey = 'aba-super-secret-hmac-key-2026';

        // Save valid ABA PayWay API credentials
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.payment.update'), [
                'default_currency' => 'USD',
                'exchange_rate_khr' => 4100,
                'aba_payway_enabled' => '1',
                'aba_merchant_id' => 'ec000123',
                'aba_api_key' => $secretKey,
                'aba_environment' => 'sandbox',
            ])
            ->assertRedirect();

        // Key is encrypted at rest and never exposed in JSON/toArray or public HTML
        $rawEncrypted = DB::table('business_payment_configs')
            ->where('business_id', $this->business->id)
            ->value('aba_api_key');
        $this->assertNotSame($secretKey, $rawEncrypted);

        $cfg = BusinessPaymentConfig::resolveForBusiness($this->business);
        $this->assertArrayNotHasKey('aba_api_key', $cfg->toArray());

        $publicHtml = $this->get("/{$this->business->slug}")->assertOk()->getContent();
        $this->assertStringNotContainsString($secretKey, (string) $publicHtml);
        $this->assertStringContainsString('Pay with ABA', (string) $publicHtml);

        // Mock official ABA API for test connection, QR generation, and transaction verification
        Http::fake([
            '*/api/payment-gateway/v1/payments/check-transaction-2' => Http::response([
                'status' => ['code' => '11', 'message' => 'Transaction not found'],
            ], 200),
            '*/api/payment-gateway/v1/payments/generate-qr' => Http::response([
                'status' => ['code' => '0', 'message' => 'Success'],
                'qrString' => '00020101021229300010aba@aba0112ec0001235802KH5914Sabayra Bistro6304ABCD',
                'abapay_deeplink' => 'https://link.payway.com.kh/checkout/test-123',
            ], 200),
        ]);

        // Test connection endpoint marks API credentials valid
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->postJson(route('business.settings.payment.test-aba'))
            ->assertOk()
            ->assertJsonPath('status', 'valid');

        // Customer checkout initializes live ABA PayWay transaction (unpaid until webhook)
        $abaCheckout = $this->postJson("/{$this->business->slug}/checkout", [
            'customer_name' => 'ABA Customer',
            'customer_phone' => '012777888',
            'order_type' => 'takeaway',
            'payment_method' => 'aba_payway',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ])->assertOk();

        $order = Order::withoutGlobalScopes()->where('order_number', $abaCheckout->json('order_number'))->firstOrFail();
        $tx = PaymentTransaction::withoutGlobalScopes()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame('unpaid', $order->payment_status);

        // Tampered webhook signature fails with 403
        $webhookBody = json_encode(['tran_id' => $tx->reference_number, 'status' => '0', 'apv' => 'APV-778899']);
        $this->call(
            'POST',
            "/webhooks/aba-payway/{$tx->reference_number}",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYWAY_HMAC_SHA512' => 'forged-sig'],
            $webhookBody
        )->assertStatus(403);
        $this->assertSame('unpaid', $order->fresh()->payment_status);

        // Valid signed webhook settles order atomically and rejects duplicates idempotently
        $validSig = base64_encode(hash_hmac('sha512', $webhookBody, $secretKey, true));
        $this->call(
            'POST',
            "/webhooks/aba-payway/{$tx->reference_number}",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYWAY_HMAC_SHA512' => $validSig],
            $webhookBody
        )->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('paid', $tx->fresh()->status);

        // Replay returns 200 idempotent
        $this->call(
            'POST',
            "/webhooks/aba-payway/{$tx->reference_number}",
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYWAY_HMAC_SHA512' => $validSig],
            $webhookBody
        )->assertOk()->assertJsonPath('ok', true);
    }

    public function test_bakong_verification_platform_toggle_and_md5_settlement(): void
    {
        // Format-valid account without NBC Open API token stays Not Verified and cannot be used at checkout
        config(['services.bakong.api_token' => '']);

        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.settings.payment.verify-bakong'), [
                'bakong_account_id' => 'sabayra@aclb',
                'bakong_merchant_name' => 'Sabayra Bistro',
            ])
            ->assertRedirect();

        $cfg = BusinessPaymentConfig::resolveForBusiness($this->business);
        $this->assertFalse($cfg->bakong_verified);
        $this->assertSame('credentials_missing', $cfg->bakong_verification_status);

        // Save enabled preference anyway -> checkout rejects khqr because account is not verified
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.payment.update'), [
                'default_currency' => 'USD',
                'exchange_rate_khr' => 4100,
                'tax_percent' => 10.00,
                'bakong_enabled' => '1',
                'bakong_account_id' => 'sabayra@aclb',
            ])->assertRedirect();

        $this->postJson("/{$this->business->slug}/checkout", [
            'customer_name' => 'Unverified Bakong',
            'customer_phone' => '012999000',
            'order_type' => 'takeaway',
            'payment_method' => 'khqr',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ])->assertStatus(422);

        // Super Admin adds Bakong Open API token & verification succeeds against NBC API
        $this->actingAs($this->superAdmin)
            ->post(route('admin.settings.update'), [
                'bakong_enabled' => '1',
                'aba_payway_enabled' => '1',
                'bakong_api_base_url' => 'https://api-bakong.nbc.gov.kh',
                'bakong_api_token' => 'live-nbc-jwt-token-xyz',
            ])
            ->assertRedirect();

        Http::fake([
            'https://api-bakong.nbc.gov.kh/v1/check_bakong_account' => Http::response([
                'responseCode' => 0,
                'responseMessage' => 'Success',
            ], 200),
            'https://api-bakong.nbc.gov.kh/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 0,
                'data' => [
                    'hash' => 'nbc-tx-hash-9001',
                    'amount' => 11.00,
                    'currency' => 'USD',
                ],
            ], 200),
        ]);

        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->postJson(route('business.settings.payment.verify-bakong'), [
                'bakong_account_id' => 'sabayra@aclb',
            ])
            ->assertOk()
            ->assertJsonPath('verified', true);

        // Super Admin turns Bakong OFF platform-wide -> business preference is preserved, warning banner shown, checkout hides & rejects KHQR
        $this->actingAs($this->superAdmin)
            ->post(route('admin.settings.update'), [
                'bakong_enabled' => '0',
                'aba_payway_enabled' => '1',
            ])->assertRedirect();

        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.settings.index', ['section' => 'payment']))
            ->assertOk()
            ->assertSee('Bakong checkout is currently turned off platform-wide. Your setting here is saved and will apply again if Bakong is re-enabled.');

        $this->assertTrue(BusinessPaymentConfig::resolveForBusiness($this->business)->bakong_enabled);

        $this->postJson("/{$this->business->slug}/checkout", [
            'customer_name' => 'Paused Bakong',
            'customer_phone' => '012999000',
            'order_type' => 'takeaway',
            'payment_method' => 'khqr',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ])->assertStatus(422);

        // Re-enable platform switch -> checkout automatically restores Bakong KHQR
        $this->actingAs($this->superAdmin)
            ->post(route('admin.settings.update'), [
                'bakong_enabled' => '1',
                'aba_payway_enabled' => '1',
            ])->assertRedirect();

        $khqrCheckout = $this->postJson("/{$this->business->slug}/checkout", [
            'customer_name' => 'Bakong Customer',
            'customer_phone' => '012999001',
            'order_type' => 'takeaway',
            'payment_method' => 'khqr',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ])->assertOk();

        $order = Order::withoutGlobalScopes()->where('order_number', $khqrCheckout->json('order_number'))->firstOrFail();
        $proofToken = $khqrCheckout->json('proof_token');

        // Order tracking page renders local SVG KHQR without external api.qrserver.com leak
        $statusHtml = $this->get($khqrCheckout->json('redirect_url'))->assertOk()->getContent();
        $this->assertStringContainsString('<svg', (string) $statusHtml);
        $this->assertStringNotContainsString('api.qrserver.com', (string) $statusHtml);

        // Customer payment verification checks Bakong MD5 and marks Paid
        $verifyRes = $this->postJson("/{$this->business->slug}/order/{$order->order_number}/verify-payment", [
            'token' => $proofToken,
        ]);
        $this->assertSame(200, $verifyRes->getStatusCode(), 'Verify response: ' . json_encode($verifyRes->json()));
        $verifyRes->assertJsonPath('paid', true);

        $this->assertSame('paid', $order->fresh()->payment_status);

        // Financial report includes tax collected, net revenue, and CSV columns
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.reports.index'))
            ->assertOk()
            ->assertSee('Tax Collected')
            ->assertSee('Net Revenue (Ex-Tax)')
            ->assertSee('$1.00');

        $csvRes = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.reports.export'));
        $csvRes->assertOk();
        $csv = $csvRes->streamedContent();
        $this->assertStringContainsString('Service Charge (USD)', $csv);
        $this->assertStringContainsString('Tax Name', $csv);
    }
}
