<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessMember;
use App\Models\User;
use App\Services\Business\BusinessFeatureService;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantModulesAndNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Business $business;
    protected BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::create([
            'owner_id' => $this->user->id,
            'name' => 'SABAYRA Grand Phnom Penh',
            'slug' => 'sabayra-grand-phnom-penh',
            'category' => 'restaurant',
            'business_type' => 'restaurant',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100.00,
        ]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->branch = BusinessBranch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Outlet',
            'is_main' => true,
            'is_active' => true,
        ]);

        TenantContext::set($this->business);
    }

    public function test_all_9_navigation_groups_are_accessible_for_merchant(): void
    {
        $routes = [
            // Group 1: Overview
            'business.dashboard',

            // Group 2: Catalog & Menu
            'business.products.index',
            'business.categories.index',
            'business.storefront.templates',
            'business.translations.index',
            'business.ai.menu-builder',
            'business.products.import',

            // Group 3: Operations
            'business.orders.index',
            'business.orders.live',
            'business.reservations.index',
            'business.waitlist.index',
            'business.tables.index',
            'business.kitchen.index',
            'business.pos.index',
            'business.printers.index',

            // Group 4: Management
            'business.staff.index',
            'business.inventory.index',
            'business.recipes.index',
            'business.suppliers.index',
            'business.branches.index',
            'business.delivery-zones.index',

            // Group 5: Customer & Marketing
            'business.customers.index',
            'business.reviews.index',
            'business.coupons.index',
            'business.happy-hours.index',
            'business.loyalty.index',
            'business.referrals.index',

            // Group 6: Business Intelligence
            'business.analytics.index',
            'business.reports.index',
            'business.ai.insights',
            'business.ai.menu-audit',
            'business.ai.sentiment',
            'business.ai.upsell-pairings',

            // Group 7: QR & Advanced Features
            'business.qr.index',
            'business.ar.cards',
            'business.ar.scan-assistant',

            // Group 8: Learning & Support
            'business.guides.order-flow',
            'business.tutorials.index',
            'business.help.index',

            // Group 9: Account
            'business.subscription.index',
            'business.settings.index',
            'business.security.index',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->user)
                ->withSession(['active_business_id' => $this->business->id])
                ->get(route($route));

            $response->assertStatus(200);
        }
    }

    public function test_merchant_can_create_reservation(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.reservations.store'), [
                'customer_name' => 'Sokha Chan',
                'customer_phone' => '012345678',
                'reservation_date' => now()->addDay()->toDateString(),
                'reservation_time' => '19:00',
                'party_size' => 4,
                'special_requests' => 'VIP window booth preferred',
            ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('reservations', [
            'business_id' => $this->business->id,
            'customer_name' => 'Sokha Chan',
            'party_size' => 4,
        ]);
    }

    public function test_merchant_can_add_customer_to_waitlist(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.waitlist.store'), [
                'customer_name' => 'Bopha Pich',
                'customer_phone' => '098765432',
                'party_size' => 2,
                'estimated_wait_minutes' => 15,
                'notes' => 'Outdoor seating preferred',
            ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('customer_waitlists', [
            'business_id' => $this->business->id,
            'customer_name' => 'Bopha Pich',
            'party_size' => 2,
            'status' => 'waiting',
        ]);
    }

    public function test_merchant_can_create_delivery_zone(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.delivery-zones.store'), [
                'zone_name' => 'BKK1 & Tonle Bassac',
                'delivery_fee' => 1.50,
                'min_order_amount' => 5.00,
                'free_delivery_threshold' => 25.00,
                'estimated_delivery_minutes' => 30,
            ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('delivery_zones', [
            'business_id' => $this->business->id,
            'zone_name' => 'BKK1 & Tonle Bassac',
            'delivery_fee' => 1.50,
        ]);
    }

    public function test_merchant_can_register_receipt_printer(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.printers.store'), [
                'name' => 'Kitchen Hot Line Printer',
                'connection_type' => 'network',
                'paper_size' => '80mm',
                'ip_address' => '192.168.1.200',
                'port' => 9100,
            ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('printers', [
            'business_id' => $this->business->id,
            'name' => 'Kitchen Hot Line Printer',
            'paper_size' => '80mm',
        ]);
    }

    public function test_merchant_can_create_coupon(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.coupons.store'), [
                'code' => 'SABAYRA2026',
                'discount_type' => 'percentage',
                'discount_value' => 20.00,
                'minimum_spend' => 10.00,
                'usage_limit' => 500,
            ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('coupons', [
            'business_id' => $this->business->id,
            'code' => 'SABAYRA2026',
            'discount_value' => 20.00,
        ]);
    }

    public function test_merchant_can_toggle_two_factor_auth(): void
    {
        $this->assertFalse($this->user->hasEnabledTwoFactorAuthentication());

        $setupResponse = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.security.2fa.setup'));

        $setupResponse->assertRedirect(route('business.settings.index', ['section' => 'security']));
        $this->user->refresh();
        $this->assertTrue($this->user->hasPendingTwoFactorSetup());
        $this->assertFalse($this->user->hasEnabledTwoFactorAuthentication());

        $code = app(\App\Services\Security\TwoFactorAuthenticationService::class)
            ->getCurrentCode((string) $this->user->two_factor_secret);

        $confirmResponse = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.security.2fa.confirm'), ['code' => $code]);

        $confirmResponse->assertRedirect(route('business.settings.index', ['section' => 'security']));
        $this->user->refresh();
        $this->assertTrue($this->user->hasEnabledTwoFactorAuthentication());
        $this->assertNotNull($this->user->two_factor_confirmed_at);

        $this->assertDatabaseHas('security_logs', [
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
            'event_type' => '2fa_enabled',
        ]);
    }

    public function test_category_engine_enforces_tailored_visibility(): void
    {
        // Restaurant has tables, kitchen, and recipes
        $this->assertTrue(BusinessFeatureService::isEnabled($this->business, 'tables'));
        $this->assertTrue(BusinessFeatureService::isEnabled($this->business, 'kitchen'));
        $this->assertTrue(BusinessFeatureService::isEnabled($this->business, 'recipes'));

        // Supermarket / Mini-mart disables dine-in tables & kitchen
        $supermarket = Business::create([
            'owner_id' => $this->user->id,
            'name' => 'Phnom Penh Supermarket',
            'slug' => 'phnom-penh-supermarket',
            'category' => 'supermarket',
            'business_type' => 'supermarket',
            'status' => 'active',
        ]);

        $this->assertFalse(BusinessFeatureService::isEnabled($supermarket, 'tables'));
        $this->assertFalse(BusinessFeatureService::isEnabled($supermarket, 'kitchen'));
        $this->assertFalse(BusinessFeatureService::isEnabled($supermarket, 'recipes'));
        $this->assertTrue(BusinessFeatureService::isEnabled($supermarket, 'pos'));
    }
}
