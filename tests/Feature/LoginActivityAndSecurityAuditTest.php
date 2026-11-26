<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\SecurityLog;
use App\Models\User;
use App\Notifications\BusinessEventNotification;
use App\Services\Security\SecurityActivityService;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginActivityAndSecurityAuditTest extends TestCase
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
            'email' => 'owner-audit@sabayra.test',
            'password' => Hash::make('OwnerPass@123'),
        ]);

        $this->business = Business::create([
            'owner_id' => $this->owner->id,
            'name' => 'Bayon Bistro',
            'slug' => 'bayon-bistro',
            'business_type' => 'restaurant',
            'status' => 'active',
        ]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'email' => 'cashier-audit@sabayra.test',
            'password' => Hash::make('CashierPass@123'),
        ]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->staff->id,
            'role' => 'cashier',
            'is_active' => true,
        ]);
    }

    public function test_successful_login_logout_and_new_device_ip_are_logged_with_parsed_agent(): void
    {
        $chromeUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0.0.0 Safari/537.36';
        $safariUa = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';

        // 1. First login from IP 10.0.0.1 (Windows Desktop)
        $this->withServerVariables([
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_USER_AGENT' => $chromeUa,
        ])->post('/login', [
            'email' => $this->owner->email,
            'password' => 'OwnerPass@123',
        ])->assertRedirect();

        $firstLog = SecurityLog::where('user_id', $this->owner->id)
            ->where('event_type', 'login_success')
            ->firstOrFail();

        $this->assertSame('10.0.0.1', $firstLog->ip_address);
        $this->assertSame('Chrome on Windows (Desktop)', $firstLog->metadata['device_label']);
        $this->assertArrayNotHasKey('new_device_ip', $firstLog->metadata);

        // Logout writes logout event
        $this->post('/logout')->assertRedirect('/');
        $this->assertDatabaseHas('security_logs', [
            'user_id' => $this->owner->id,
            'event_type' => 'logout',
        ]);

        // 2. Second login from new IP 203.144.88.5 (iOS Mobile) flags new_device_ip
        $this->withServerVariables([
            'REMOTE_ADDR' => '203.144.88.5',
            'HTTP_USER_AGENT' => $safariUa,
        ])->post('/login', [
            'email' => $this->owner->email,
            'password' => 'OwnerPass@123',
        ])->assertRedirect();

        $secondLog = SecurityLog::where('user_id', $this->owner->id)
            ->where('event_type', 'login_success')
            ->where('ip_address', '203.144.88.5')
            ->firstOrFail();

        $this->assertTrue((bool) ($secondLog->metadata['new_device_ip'] ?? false));
        $this->assertSame('Safari on iOS (Mobile)', $secondLog->metadata['device_label']);

        // View renders parsed devices, IPs, and New IP badge
        $view = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.settings.index', ['section' => 'security']));

        $view->assertOk()
            ->assertSee('Recent Login Activity')
            ->assertSee('Safari on iOS (Mobile)')
            ->assertSee('203.144.88.5')
            ->assertSee('New IP');
    }

    public function test_failed_login_attempts_lockout_and_suspicious_security_alerts(): void
    {
        Notification::fake();

        // Wrong password x3 increments failed_login_attempts & triggers Business account alert on 3rd failure
        for ($i = 1; $i <= 3; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.22'])
                ->post('/login', [
                    'email' => $this->owner->email,
                    'password' => 'WrongPass!' . $i,
                ])
                ->assertSessionHasErrors('email');
        }

        $this->assertSame(3, $this->owner->fresh()->failed_login_attempts);
        $this->assertSame(
            3,
            SecurityLog::where('user_id', $this->owner->id)
                ->where('event_type', 'login_failed')
                ->count()
        );

        Notification::assertSentTo(
            $this->owner,
            BusinessEventNotification::class,
            fn (BusinessEventNotification $n): bool => $n->eventKey === 'business.account_alert'
        );

        // Two more failures trigger rate-limit lockout on 6th attempt
        for ($i = 4; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.22'])
                ->post('/login', [
                    'email' => $this->owner->email,
                    'password' => 'WrongPass!' . $i,
                ]);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.22'])
            ->post('/login', [
                'email' => $this->owner->email,
                'password' => 'OwnerPass@123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseHas('security_logs', [
            'user_id' => $this->owner->id,
            'event_type' => 'login_locked',
            'ip_address' => '198.51.100.22',
        ]);
    }

    public function test_password_change_and_staff_role_change_are_recorded_and_activity_is_user_isolated(): void
    {
        // Staff updates password via profile endpoint
        $this->actingAs($this->staff)
            ->put('/password', [
                'current_password' => 'CashierPass@123',
                'password' => 'NewCashier@999',
                'password_confirmation' => 'NewCashier@999',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('security_logs', [
            'user_id' => $this->staff->id,
            'event_type' => 'password_changed',
        ]);

        // Owner promotes staff from cashier to manager -> logs role_changed on staff user
        $staffMembership = BusinessMember::where('business_id', $this->business->id)
            ->where('user_id', $this->staff->id)
            ->firstOrFail();

        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.staff.update', $staffMembership), [
                'role' => 'manager',
            ])
            ->assertRedirect();

        $roleLog = SecurityLog::where('user_id', $this->staff->id)
            ->where('event_type', 'role_changed')
            ->firstOrFail();

        $this->assertSame('cashier', $roleLog->metadata['from_role']);
        $this->assertSame('manager', $roleLog->metadata['to_role']);

        // Owner's login activity log must NOT display staff user's personal events or IPs
        SecurityLog::create([
            'user_id' => $this->staff->id,
            'event_type' => 'login_success',
            'ip_address' => '172.16.99.88',
        ]);

        $ownerView = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.settings.index', ['section' => 'security']));

        $ownerView->assertOk()->assertDontSee('172.16.99.88');

        // Activity filter tabs filter cleanly (table hides login_success while showing account events)
        $this->actingAs($this->staff)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.settings.index', ['section' => 'security', 'activity_filter' => 'account']))
            ->assertOk()
            ->assertSee('Password Updated')
            ->assertSee('Workspace Role Changed')
            ->assertDontSee('Signed In');
    }

    public function test_user_agent_parser_identifies_browsers_and_devices_accurately(): void
    {
        $service = app(SecurityActivityService::class);

        $edgeWin = $service->parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0');
        $this->assertSame('Microsoft Edge', $edgeWin['browser']);
        $this->assertSame('Windows', $edgeWin['os']);
        $this->assertSame('Desktop', $edgeWin['device_type']);

        $androidChrome = $service->parseUserAgent('Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Chrome/129.0.0.0 Mobile Safari/537.36');
        $this->assertSame('Chrome', $androidChrome['browser']);
        $this->assertSame('Android', $androidChrome['os']);
        $this->assertSame('Mobile', $androidChrome['device_type']);
    }
}
