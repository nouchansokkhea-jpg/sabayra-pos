<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Services\Payment\KHQRService;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $staff;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();

        $this->owner = User::factory()->create([
            'password' => Hash::make('OwnerPass@123'),
        ]);

        $this->business = Business::create([
            'owner_id' => $this->owner->id,
            'name' => 'Sabayra Bistro',
            'slug' => 'sabayra-bistro',
            'business_type' => 'restaurant',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100.00,
            'tax_percent' => 10.00,
        ]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'password' => Hash::make('StaffPass@123'),
        ]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->staff->id,
            'role' => 'cashier',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_owner_can_view_settings_hub_and_legacy_business_redirect_preserves_section(): void
    {
        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.settings.index'));

        $response->assertOk()
            ->assertSee('General Settings')
            ->assertSee('Business Information')
            ->assertSee('Payment &amp; Tax', false)
            ->assertSee('Notifications')
            ->assertSee('Security')
            ->assertSee('Account Settings');

        $redirectResponse = $this->actingAs($this->owner)
            ->get('/business/settings?section=notifications');

        $redirectResponse->assertRedirect('/app/settings?section=notifications');
    }

    public function test_owner_can_update_general_settings_including_khmer_slug_and_preferences(): void
    {
        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.general.update'), [
                'status' => 'active',
                'slug' => 'ហាងសប្បាយរ៉ា',
                'public_url' => 'https://menu.sabayra.kh/',
                'theme_preset' => 'emerald',
                'primary_color' => '#059669',
                'layout' => 'grid',
                'font' => "Inter, 'Kantumruy Pro', sans-serif",
                'auto_accept_orders' => '1',
                'show_khr_prices' => '0',
            ]);

        $response->assertRedirect(route('business.settings.index', ['section' => 'general']));

        $this->business->refresh();
        $this->assertSame('ហាងសប្បាយរ៉ា', $this->business->slug);
        $this->assertSame('https://menu.sabayra.kh', $this->business->theme_settings['public_url']);
        $this->assertSame('#059669', $this->business->theme_settings['primary_color']);
        $this->assertTrue(BusinessSetting::getValue($this->business->id, 'general.auto_accept_orders'));
        $this->assertFalse(BusinessSetting::getValue($this->business->id, 'general.show_khr_prices'));
    }

    public function test_owner_can_update_business_information(): void
    {
        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.business.update'), [
                'name' => 'Sabayra Flagship BKK1',
                'business_type' => 'cafe',
                'tagline' => 'Specialty Khmer Coffee',
                'description' => 'Freshly roasted local beans daily.',
                'phone' => '+855 12 888 777',
                'email' => 'hello@sabayra.kh',
                'city' => 'Phnom Penh',
                'address' => 'Street 51, BKK1',
                'social_links' => [
                    'telegram' => 'sabayra_official',
                ],
            ]);

        $response->assertRedirect(route('business.settings.index', ['section' => 'business']));

        $this->assertDatabaseHas('businesses', [
            'id' => $this->business->id,
            'name' => 'Sabayra Flagship BKK1',
            'business_type' => 'cafe',
            'telegram_handle' => 'sabayra_official',
        ]);
    }

    public function test_owner_can_update_payment_and_tax_settings_and_khqr_uses_saved_account(): void
    {
        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.payment.update'), [
                'default_currency' => 'KHR',
                'exchange_rate_khr' => 4050,
                'tax_percent' => 10.00,
                'bakong_account_id' => 'sabayra_shop@aclb',
                'bakong_merchant_name' => 'Sabayra BKK1',
                'khqr_enabled' => '1',
                'cash_enabled' => '1',
                'receipt_footer' => 'Thank you! See you soon.',
            ]);

        $response->assertRedirect(route('business.settings.index', ['section' => 'payment']));

        $this->business->refresh();
        $this->assertSame('KHR', $this->business->default_currency);
        $this->assertEquals(4050.00, (float) $this->business->exchange_rate_khr);
        $this->assertSame('sabayra_shop@aclb', BusinessSetting::getValue($this->business->id, 'payment.bakong_account_id'));

        $khqrPayload = KHQRService::generate($this->business, 40500, 'KHR', 'ORD-001');
        $this->assertStringContainsString('sabayra_shop@aclb', $khqrPayload);
    }

    public function test_staff_cannot_access_or_modify_owner_business_settings(): void
    {
        // Staff accessing /app/settings without section defaults safely to personal security tab
        $defaultResponse = $this->actingAs($this->staff)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.settings.index'));
        $defaultResponse->assertOk()
            ->assertSee('Two-Factor Authentication')
            ->assertDontSee('Telegram &amp; Alerts', false);

        // Direct access to owner-only business tabs is forbidden
        foreach (['general', 'business', 'payment', 'notifications'] as $restrictedSection) {
            $this->actingAs($this->staff)
                ->withSession(['active_business_id' => $this->business->id])
                ->get(route('business.settings.index', ['section' => $restrictedSection]))
                ->assertForbidden();
        }

        // Write attempts are rejected with 403
        $this->actingAs($this->staff)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.general.update'), [
                'status' => 'inactive',
                'slug' => 'hijacked-slug',
            ])
            ->assertForbidden();

        $this->actingAs($this->staff)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.payment.update'), [
                'default_currency' => 'KHR',
                'exchange_rate_khr' => 4200,
                'bakong_account_id' => 'attacker@bank',
            ])
            ->assertForbidden();

        $this->assertSame('sabayra-bistro', $this->business->fresh()->slug);
    }

    public function test_forged_business_id_cannot_modify_another_tenant_settings(): void
    {
        $otherOwner = User::factory()->create();
        $otherBusiness = Business::create([
            'owner_id' => $otherOwner->id,
            'name' => 'Victim Restaurant',
            'slug' => 'victim-restaurant',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.general.update'), [
                'business_id' => $otherBusiness->id,
                'status' => 'inactive',
                'slug' => 'sabayra-updated',
            ]);

        $response->assertRedirect();
        $this->assertSame('victim-restaurant', $otherBusiness->fresh()->slug);
        $this->assertSame('sabayra-updated', $this->business->fresh()->slug);
    }

    public function test_user_can_update_personal_account_and_password_with_security_log(): void
    {
        $accountResponse = $this->actingAs($this->staff)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.account.update'), [
                'name' => 'Dara Cashier',
                'email' => $this->staff->email,
                'phone' => '+855 99 111 222',
                'preferred_locale' => 'km',
            ]);

        $accountResponse->assertRedirect(route('business.settings.index', ['section' => 'account']));
        $this->staff->refresh();
        $this->assertSame('Dara Cashier', $this->staff->name);
        $this->assertSame('km', $this->staff->preferred_locale);

        // Wrong current password fails
        $badPasswordResponse = $this->actingAs($this->staff)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.account.password'), [
                'current_password' => 'WrongPass123',
                'password' => 'NewStrongPass@123',
                'password_confirmation' => 'NewStrongPass@123',
            ]);

        $badPasswordResponse->assertSessionHasErrorsIn('updatePassword', 'current_password');

        // Correct current password succeeds and writes SecurityLog
        $goodPasswordResponse = $this->actingAs($this->staff)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.account.password'), [
                'current_password' => 'StaffPass@123',
                'password' => 'NewStrongPass@123',
                'password_confirmation' => 'NewStrongPass@123',
            ]);

        $goodPasswordResponse->assertRedirect(route('business.settings.index', ['section' => 'account']));
        $this->assertTrue(Hash::check('NewStrongPass@123', $this->staff->fresh()->password));
        $this->assertDatabaseHas('security_logs', [
            'user_id' => $this->staff->id,
            'business_id' => $this->business->id,
            'event_type' => 'password_changed',
        ]);
    }
}
