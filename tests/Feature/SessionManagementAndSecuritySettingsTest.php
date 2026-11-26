<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Notifications\BusinessEventNotification;
use App\Services\Security\SessionManagementService;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionManagementAndSecuritySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $cashier;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();

        $this->owner = User::factory()->create([
            'email' => 'owner-sessions@sabayra.test',
            'password' => Hash::make('OwnerPass@123'),
        ]);

        $this->business = Business::create([
            'owner_id' => $this->owner->id,
            'name' => 'Tonle Bistro',
            'slug' => 'tonle-bistro',
            'business_type' => 'restaurant',
            'status' => 'active',
        ]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'email' => 'cashier-sessions@sabayra.test',
            'password' => Hash::make('CashierPass@123'),
        ]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->cashier->id,
            'role' => 'cashier',
            'is_active' => true,
        ]);
    }

    public function test_active_devices_list_masks_raw_session_ids_and_isolates_per_user(): void
    {
        Config::set('session.driver', 'database');

        $remoteSessionId = 'raw-secret-session-' . Str::random(24);
        $otherUserSessionId = 'other-user-session-' . Str::random(24);

        DB::table('sessions')->insert([
            [
                'id' => $remoteSessionId,
                'user_id' => $this->owner->id,
                'ip_address' => '203.144.77.10',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
                'payload' => base64_encode('test'),
                'last_activity' => now()->subMinutes(5)->timestamp,
            ],
            [
                'id' => $otherUserSessionId,
                'user_id' => $this->cashier->id,
                'ip_address' => '198.51.100.88',
                'user_agent' => 'Mozilla/5.0 (Linux; Android 14)',
                'payload' => base64_encode('test'),
                'last_activity' => now()->subMinutes(2)->timestamp,
            ],
        ]);

        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.settings.index', ['section' => 'security']));

        $response->assertOk()
            ->assertSee('Active Devices &amp; Sessions', false)
            ->assertSee('Safari on iOS (Mobile)')
            ->assertSee('203.144.77.10')
            ->assertDontSee($remoteSessionId)
            ->assertDontSee('198.51.100.88');
    }

    public function test_user_can_revoke_specific_remote_session_without_cross_user_access(): void
    {
        Config::set('session.driver', 'database');
        $service = app(SessionManagementService::class);

        $remoteSessionId = 'owner-remote-' . Str::random(20);
        $cashierSessionId = 'cashier-remote-' . Str::random(20);

        DB::table('sessions')->insert([
            [
                'id' => $remoteSessionId,
                'user_id' => $this->owner->id,
                'ip_address' => '10.10.20.30',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X) Chrome/129.0.0.0 Safari/537.36',
                'payload' => base64_encode('remote'),
                'last_activity' => now()->subMinutes(3)->timestamp,
            ],
            [
                'id' => $cashierSessionId,
                'user_id' => $this->cashier->id,
                'ip_address' => '10.10.20.99',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Firefox/131.0',
                'payload' => base64_encode('cashier'),
                'last_activity' => now()->subMinutes(3)->timestamp,
            ],
        ]);

        // Cashier cannot revoke Owner's session even if forging HMAC ref
        $ownerRef = $service->signSessionRef($remoteSessionId);
        $this->actingAs($this->cashier)
            ->withSession(['active_business_id' => $this->business->id])
            ->delete(route('business.security.sessions.destroy', $ownerRef))
            ->assertRedirect();

        $this->assertDatabaseHas('sessions', ['id' => $remoteSessionId]);

        // Owner revokes their own remote session cleanly
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->delete(route('business.security.sessions.destroy', $ownerRef))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('sessions', ['id' => $remoteSessionId]);
        $this->assertDatabaseHas('security_logs', [
            'user_id' => $this->owner->id,
            'event_type' => 'session_revoked',
        ]);
    }

    public function test_revoke_other_sessions_requires_password_and_rotates_remember_token(): void
    {
        Config::set('session.driver', 'database');

        $this->owner->setRememberToken('initial-remember-token-xyz');
        $this->owner->save();

        $other1 = 'other-device-1-' . Str::random(16);
        $other2 = 'other-device-2-' . Str::random(16);

        DB::table('sessions')->insert([
            [
                'id' => $other1,
                'user_id' => $this->owner->id,
                'ip_address' => '172.20.1.1',
                'user_agent' => 'Chrome/129.0',
                'payload' => base64_encode('1'),
                'last_activity' => now()->subMinute()->timestamp,
            ],
            [
                'id' => $other2,
                'user_id' => $this->owner->id,
                'ip_address' => '172.20.1.2',
                'user_agent' => 'Firefox/130.0',
                'payload' => base64_encode('2'),
                'last_activity' => now()->subMinutes(10)->timestamp,
            ],
        ]);

        // Wrong password rejected
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.security.sessions.revoke-others'), [
                'current_password' => 'WrongPass!',
            ])
            ->assertSessionHasErrorsIn('revokeSessions', 'current_password');

        $this->assertDatabaseHas('sessions', ['id' => $other1]);
        $this->assertDatabaseHas('sessions', ['id' => $other2]);

        // Correct password revokes other sessions & rotates remember_token
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.security.sessions.revoke-others'), [
                'current_password' => 'OwnerPass@123',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('sessions', ['id' => $other1]);
        $this->assertDatabaseMissing('sessions', ['id' => $other2]);
        $this->assertNotSame('initial-remember-token-xyz', $this->owner->fresh()->getRememberToken());

        $this->assertDatabaseHas('security_logs', [
            'user_id' => $this->owner->id,
            'event_type' => 'sessions_revoked',
        ]);
    }

    public function test_workspace_security_settings_enforce_idle_timeout_and_new_ip_alerts(): void
    {
        Notification::fake();

        // Staff cannot update workspace security policy
        $this->actingAs($this->cashier)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.security.update'), [
                'session_timeout_minutes' => 15,
            ])
            ->assertForbidden();

        // Owner enables 15-minute idle timeout and alerts
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.security.update'), [
                'session_timeout_minutes' => 15,
                'alert_unfamiliar_login' => '1',
                'alert_failed_logins' => '1',
            ])
            ->assertRedirect(route('business.settings.index', ['section' => 'security']));

        $this->assertSame(15, BusinessSetting::getValue($this->business->id, 'security.session_timeout_minutes'));
        $this->assertTrue((bool) BusinessSetting::getValue($this->business->id, 'security.alert_unfamiliar_login'));
        $this->assertDatabaseHas('security_logs', [
            'user_id' => $this->owner->id,
            'business_id' => $this->business->id,
            'event_type' => 'security_settings_updated',
        ]);

        // Expired sabayra_last_seen_at triggers idle timeout logout on /app/settings
        $this->actingAs($this->owner)
            ->withSession([
                'active_business_id' => $this->business->id,
                'sabayra_last_seen_at' => now()->subMinutes(20)->timestamp,
            ])
            ->get(route('business.settings.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();

        // Unfamiliar IP sign-in sends New IP Sign-In Detected alert
        $this->withServerVariables(['REMOTE_ADDR' => '10.1.1.1'])
            ->post('/login', [
                'email' => $this->owner->email,
                'password' => 'OwnerPass@123',
            ]);
        $this->post('/logout');

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.55'])
            ->post('/login', [
                'email' => $this->owner->email,
                'password' => 'OwnerPass@123',
            ]);

        Notification::assertSentTo(
            $this->owner,
            BusinessEventNotification::class,
            fn (BusinessEventNotification $n): bool => $n->title === 'New IP Sign-In Detected'
        );
    }
}
