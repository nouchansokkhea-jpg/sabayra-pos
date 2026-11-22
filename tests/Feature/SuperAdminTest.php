<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_super_admin(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_regular_merchants_forbidden_from_super_admin(): void
    {
        $regularUser = User::factory()->create(['is_super_admin' => false]);

        $response = $this->actingAs($regularUser)->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_dashboard_and_see_real_metrics(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Platform Overview');
        $response->assertSee('Total Businesses');
    }

    public function test_super_admin_can_toggle_business_status(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $owner = User::factory()->create();

        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Kravanh Cafe',
            'slug' => 'kravanh-cafe',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.businesses.toggle-status', $business));
        $response->assertRedirect();

        $business->refresh();
        $this->assertSame('suspended', $business->status);
    }

    public function test_super_admin_can_create_subscription_plan(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.plans.store'), [
            'name' => 'ULTRA VIP',
            'slug' => 'ultra-vip',
            'description' => 'Unlimited everything for mega chains',
            'monthly_price' => 99.00,
            'yearly_price' => 990.00,
            'currency' => 'USD',
            'product_limit' => 5000,
            'branch_limit' => 20,
            'staff_limit' => 100,
            'has_pos' => 1,
            'has_inventory' => 1,
            'has_online_ordering' => 1,
            'has_analytics' => 1,
            'has_custom_branding' => 1,
            'is_active' => 1,
            'sort_order' => 5,
        ]);

        $response->assertRedirect(route('admin.plans.index'));
        $this->assertDatabaseHas('plans', ['slug' => 'ultra-vip']);
    }

    public function test_super_admin_can_view_all_subscriptions_and_payments(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $owner = User::factory()->create();

        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Sabay Cafe',
            'slug' => 'sabay-cafe',
            'status' => 'active',
        ]);

        $plan = Plan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'monthly_price' => 29.00,
            'yearly_price' => 290.00,
        ]);

        $sub = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 29.00,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $payment = SubscriptionPayment::create([
            'subscription_id' => $sub->id,
            'business_id' => $business->id,
            'amount' => 29.00,
            'currency' => 'USD',
            'payment_method' => 'bakong_khqr',
            'transaction_reference' => 'TXN-TEST-123',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $subResponse = $this->actingAs($admin)->get(route('admin.subscriptions.index'));
        $subResponse->assertStatus(200);
        $subResponse->assertSee('Sabay Cafe');

        $payResponse = $this->actingAs($admin)->get(route('admin.payments.index'));
        $payResponse->assertStatus(200);
        $payResponse->assertSee('TXN-TEST-123');
    }

    public function test_super_admin_can_view_analytics_and_categories(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        $analyticsRes = $this->actingAs($admin)->get(route('admin.analytics.index'));
        $analyticsRes->assertStatus(200);
        $analyticsRes->assertSee('Revenue & Platform Analytics');

        $catRes = $this->actingAs($admin)->get(route('admin.categories.index'));
        $catRes->assertStatus(200);
        $catRes->assertSee('Business Sectors & Presets');
    }

    public function test_super_admin_can_manage_support_tickets(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $owner = User::factory()->create();

        $ticket = SupportTicket::create([
            'user_id' => $owner->id,
            'subject' => 'Need help with KHQR',
            'message' => 'Please activate our terminal',
            'priority' => 'high',
            'status' => 'open',
            'channel' => 'web',
            'sender_name' => $owner->name,
            'sender_email' => $owner->email,
        ]);

        $res = $this->actingAs($admin)->get(route('admin.support.index'));
        $res->assertStatus(200);
        $res->assertSee('Need help with KHQR');

        $updateRes = $this->actingAs($admin)->post(route('admin.support.update-status', $ticket), [
            'status' => 'resolved',
            'admin_notes' => 'Activated and verified successfully.',
        ]);
        $updateRes->assertRedirect();

        $ticket->refresh();
        $this->assertSame('resolved', $ticket->status);
        $this->assertNotNull($ticket->resolved_at);
    }

    public function test_super_admin_can_view_business_details_with_active_subscription(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $owner = User::factory()->create();

        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Angkor Brewery',
            'slug' => 'angkor-brewery',
            'business_type' => 'bar',
            'status' => 'active',
        ]);

        $oldPlan = Plan::create([
            'name' => 'Starter Plan',
            'slug' => 'starter-plan',
            'monthly_price' => 19.00,
            'yearly_price' => 190.00,
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $oldPlan->id,
            'billing_cycle' => 'monthly',
            'price' => 19.00,
            'status' => 'cancelled',
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->subMonth(),
            'created_at' => now()->subMonths(2),
        ]);

        $plan = Plan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'monthly_price' => 99.00,
            'yearly_price' => 990.00,
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'price' => 99.00,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.businesses.show', $business));
        $response->assertStatus(200);
        $response->assertSee('Angkor Brewery');
        $response->assertSee('Enterprise Plan');
        $this->assertEquals('Enterprise Plan', $business->fresh()->activeSubscription->plan->name);
    }
}
