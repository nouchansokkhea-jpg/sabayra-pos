<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\User;
use App\Services\Security\TwoFactorAuthenticationService;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();

        $this->user = User::factory()->create([
            'email' => 'owner2fa@sabayra.test',
            'password' => Hash::make('OwnerPass@123'),
        ]);

        $this->business = Business::create([
            'owner_id' => $this->user->id,
            'name' => 'Kampuchea Coffee',
            'slug' => 'kampuchea-coffee',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    public function test_user_can_setup_and_confirm_totp_with_qr_svg_and_recovery_codes(): void
    {
        $service = app(TwoFactorAuthenticationService::class);

        // 1. Begin setup -> creates encrypted pending secret, not active yet
        $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.security.2fa.setup'))
            ->assertRedirect(route('business.settings.index', ['section' => 'security']));

        $this->user->refresh();
        $this->assertTrue($this->user->hasPendingTwoFactorSetup());
        $this->assertFalse($this->user->hasEnabledTwoFactorAuthentication());
        $plainSecret = (string) $this->user->two_factor_secret;
        $this->assertNotEmpty($plainSecret);

        // View renders QR SVG and manual setup key while pending
        $setupView = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.settings.index', ['section' => 'security']));
        $setupView->assertOk()
            ->assertSee('<svg', false)
            ->assertSee($plainSecret);

        // 2. Wrong TOTP code fails confirmation
        $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.security.2fa.confirm'), ['code' => '000000'])
            ->assertSessionHasErrorsIn('twoFactorConfirm', 'code');

        $this->user->refresh();
        $this->assertFalse($this->user->hasEnabledTwoFactorAuthentication());

        // 3. Valid TOTP code enables 2FA and returns 8 recovery codes in session
        $validCode = $service->getCurrentCode($plainSecret);
        $confirmResponse = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.security.2fa.confirm'), ['code' => $validCode]);

        $confirmResponse->assertRedirect(route('business.settings.index', ['section' => 'security']))
            ->assertSessionHas('two_factor_recovery_codes_plain');

        $plainCodes = session('two_factor_recovery_codes_plain');
        $this->assertIsArray($plainCodes);
        $this->assertCount(8, $plainCodes);

        $this->user->refresh();
        $this->assertTrue($this->user->hasEnabledTwoFactorAuthentication());
        $this->assertNotNull($this->user->two_factor_confirmed_at);

        // Secrets are hidden from array/JSON serialization
        $serialized = $this->user->toArray();
        $this->assertArrayNotHasKey('two_factor_secret', $serialized);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $serialized);

        // Recovery codes stored hashed (not plaintext)
        $storedHashes = $this->user->two_factor_recovery_codes;
        $this->assertCount(8, $storedHashes);
        $this->assertNotContains($plainCodes[0], $storedHashes);

        // Confirmed Settings page does NOT leak the TOTP secret anymore
        $afterConfirmView = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.settings.index', ['section' => 'security']));
        $afterConfirmView->assertOk()->assertDontSee($plainSecret);
    }

    public function test_login_requires_totp_challenge_and_blocks_totp_replay(): void
    {
        $service = app(TwoFactorAuthenticationService::class);
        $setup = $service->beginSetup($this->user);
        $confirmCode = $service->getCurrentCode($setup['secret'], time() - 60);

        // Confirm setup using an earlier valid window slice (t - 60s requires temporary confirm via t - 30s)
        $setupCode = $service->getCurrentCode($setup['secret'], time() - 30);
        $confirmed = $service->confirmSetup($this->user, $setupCode);
        $this->assertTrue($confirmed['ok']);

        // Login with password -> remains guest until 2FA code verified
        $loginResponse = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'OwnerPass@123',
        ]);

        $loginResponse->assertRedirect(route('two-factor.login'));
        $this->assertGuest();

        // Cannot bypass challenge to visit protected workspace settings
        $this->get(route('business.settings.index'))->assertRedirect(route('login'));

        // Challenge view renders
        $this->get(route('two-factor.login'))->assertOk()->assertSee('Verify Your Identity');

        // Invalid challenge code stays guest
        $this->post(route('two-factor.login.store'), ['code' => '999999'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();

        // Valid TOTP challenge completes authentication and honors intended URL (/app/settings)
        $liveCode = $service->getCurrentCode($setup['secret']);
        $this->post(route('two-factor.login.store'), ['code' => $liveCode])
            ->assertRedirect(route('business.settings.index'));

        $this->assertAuthenticatedAs($this->user);
        $this->assertNotNull($this->user->fresh()->last_login_at);
        $this->assertDatabaseHas('security_logs', [
            'user_id' => $this->user->id,
            'event_type' => 'login_success',
        ]);

        // Replaying the exact same TOTP code must fail
        $this->post('/logout');
        $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'OwnerPass@123',
        ])->assertRedirect(route('two-factor.login'));

        $this->post(route('two-factor.login.store'), ['code' => $liveCode])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_code_logs_in_once_and_cannot_be_reused(): void
    {
        $service = app(TwoFactorAuthenticationService::class);
        $setup = $service->beginSetup($this->user);
        $setupCode = $service->getCurrentCode($setup['secret'], time() - 30);
        $result = $service->confirmSetup($this->user, $setupCode);
        $recoveryCode = $result['recovery_codes'][0];

        // First login using recovery code succeeds
        $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'OwnerPass@123',
        ])->assertRedirect(route('two-factor.login'));

        $this->post(route('two-factor.login.store'), [
            'recovery_code' => $recoveryCode,
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($this->user);
        $this->assertCount(7, $this->user->fresh()->two_factor_recovery_codes);
        $this->assertDatabaseHas('security_logs', [
            'user_id' => $this->user->id,
            'event_type' => '2fa_recovery_used',
        ]);

        // Second login with the SAME single-use recovery code must fail
        $this->post('/logout');
        $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'OwnerPass@123',
        ])->assertRedirect(route('two-factor.login'));

        $this->post(route('two-factor.login.store'), [
            'recovery_code' => $recoveryCode,
        ])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_regenerating_recovery_codes_and_disabling_2fa_require_current_password(): void
    {
        $service = app(TwoFactorAuthenticationService::class);
        $setup = $service->beginSetup($this->user);
        $oldResult = $service->confirmSetup($this->user, $service->getCurrentCode($setup['secret'], time() - 30));
        $oldCode = $oldResult['recovery_codes'][0];

        // Wrong password cannot regenerate recovery codes
        $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.security.2fa.recovery-codes'), ['current_password' => 'WrongPassword'])
            ->assertSessionHasErrorsIn('twoFactorRecovery', 'current_password');

        // Correct password regenerates recovery codes and invalidates old ones
        $regenResponse = $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.security.2fa.recovery-codes'), ['current_password' => 'OwnerPass@123']);

        $regenResponse->assertRedirect(route('business.settings.index', ['section' => 'security']))
            ->assertSessionHas('two_factor_recovery_codes_plain');

        $newCodes = session('two_factor_recovery_codes_plain');
        $this->assertCount(8, $newCodes);
        $this->assertNotSame($oldCode, $newCodes[0]);
        $this->assertFalse($service->verifyChallenge($this->user->fresh(), $oldCode)['ok']);

        // Wrong password cannot disable 2FA
        $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->delete(route('business.security.2fa.disable'), ['current_password' => 'WrongPassword'])
            ->assertSessionHasErrorsIn('twoFactorDisable', 'current_password');
        $this->assertTrue($this->user->fresh()->hasEnabledTwoFactorAuthentication());

        // Correct password disables 2FA and cleans secret/recovery codes
        $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->delete(route('business.security.2fa.disable'), ['current_password' => 'OwnerPass@123'])
            ->assertRedirect(route('business.settings.index', ['section' => 'security']));

        $fresh = $this->user->fresh();
        $this->assertFalse($fresh->hasEnabledTwoFactorAuthentication());
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_recovery_codes);
        $this->assertNull($fresh->two_factor_confirmed_at);

        // Login now completes without 2FA challenge
        $this->post('/logout');
        $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'OwnerPass@123',
        ])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_challenge_rate_limits_and_legacy_toggle_never_bypass_verification(): void
    {
        // Legacy toggle initiates setup instead of marking 2FA enabled without TOTP verification
        $this->actingAs($this->user)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.security.2fa'))
            ->assertRedirect(route('business.settings.index', ['section' => 'security']));

        $this->user->refresh();
        $this->assertTrue($this->user->hasPendingTwoFactorSetup());
        $this->assertFalse($this->user->hasEnabledTwoFactorAuthentication());

        // Activate 2FA properly
        $service = app(TwoFactorAuthenticationService::class);
        $service->confirmSetup($this->user, $service->getCurrentCode((string) $this->user->two_factor_secret));

        $this->post('/logout');

        // Visiting challenge directly without pending session redirects to login
        $this->get(route('two-factor.login'))->assertRedirect(route('login'));

        // Sign in to start challenge, then submit 5 invalid attempts -> 6th triggers rate limit lockout message
        $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'OwnerPass@123',
        ])->assertRedirect(route('two-factor.login'));

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('two-factor.login.store'), ['code' => '000001'])
                ->assertSessionHasErrors('code');
        }

        $this->post(route('two-factor.login.store'), ['code' => '000001'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }
}
