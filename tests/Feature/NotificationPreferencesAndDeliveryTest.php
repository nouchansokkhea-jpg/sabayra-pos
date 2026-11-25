<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramBusinessNotificationJob;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\CustomerReview;
use App\Models\InventoryItem;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\TelegramConnection;
use App\Models\TelegramNotificationLog;
use App\Models\User;
use App\Notifications\BusinessEventNotification;
use App\Services\Notifications\BusinessNotificationService;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationPreferencesAndDeliveryTest extends TestCase
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
            'email' => 'owner@sabayra.test',
        ]);

        $this->business = Business::create([
            'owner_id' => $this->owner->id,
            'name' => 'Lotus Pavilion',
            'slug' => 'lotus-pavilion',
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

        $this->cashier = User::factory()->create([
            'email' => 'cashier@sabayra.test',
        ]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->cashier->id,
            'role' => 'cashier',
            'is_active' => true,
        ]);
    }

    public function test_owner_can_view_and_save_notification_preferences_with_branch_filter(): void
    {
        $branch = BusinessBranch::create([
            'business_id' => $this->business->id,
            'name' => 'Toul Kork Outlet',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.settings.index', ['section' => 'notifications']));

        $response->assertStatus(200)
            ->assertSee('Notification Preferences')
            ->assertSee('Order Notifications')
            ->assertSee('Reservation Notifications')
            ->assertSee('Review Notifications')
            ->assertSee('Inventory Notifications')
            ->assertSee('Business Notifications')
            ->assertSee('Telegram: Not Connected')
            ->assertSee('Toul Kork Outlet');

        $saveResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.notifications.update'), [
                'branch_id' => $branch->id,
                'preferences' => [
                    'order.created' => [
                        'telegram_enabled' => '1',
                        'email_enabled' => '1',
                        'in_app_enabled' => '1',
                        'browser_enabled' => '1',
                    ],
                    'order.cancelled' => [
                        'telegram_enabled' => '0',
                        'email_enabled' => '0',
                        'in_app_enabled' => '0',
                        'browser_enabled' => '0',
                    ],
                ],
            ]);

        $saveResponse->assertRedirect(route('business.settings.index', ['section' => 'notifications']));

        $this->assertDatabaseHas('notification_preferences', [
            'business_id' => $this->business->id,
            'branch_id' => $branch->id,
            'event_key' => 'order.created',
            'telegram_enabled' => true,
            'email_enabled' => true,
            'in_app_enabled' => true,
            'browser_enabled' => true,
        ]);

        $this->assertDatabaseHas('notification_preferences', [
            'business_id' => $this->business->id,
            'branch_id' => $branch->id,
            'event_key' => 'order.cancelled',
            'telegram_enabled' => false,
            'email_enabled' => false,
            'in_app_enabled' => false,
            'browser_enabled' => false,
        ]);
    }

    public function test_staff_without_manage_settings_cannot_update_notification_preferences(): void
    {
        $response = $this->actingAs($this->cashier)
            ->withSession(['active_business_id' => $this->business->id])
            ->put(route('business.settings.notifications.update'), [
                'preferences' => [
                    'order.created' => ['telegram_enabled' => '1'],
                ],
            ]);

        $response->assertStatus(403);
    }

    public function test_notifications_are_queued_and_respect_disabled_preferences(): void
    {
        TelegramConnection::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'connection_mode' => 'custom_bot',
            'bot_token' => '123456:custom-secret-token',
            'chat_id' => '987654321',
            'is_active' => true,
            'notifications_enabled' => true,
        ]);

        NotificationPreference::create([
            'business_id' => $this->business->id,
            'user_id' => null,
            'event_key' => 'order.confirmed',
            'telegram_enabled' => true,
            'email_enabled' => false,
            'in_app_enabled' => true,
            'browser_enabled' => false,
        ]);

        NotificationPreference::create([
            'business_id' => $this->business->id,
            'user_id' => null,
            'event_key' => 'order.cancelled',
            'telegram_enabled' => false,
            'email_enabled' => false,
            'in_app_enabled' => false,
            'browser_enabled' => false,
        ]);

        Queue::fake();
        Notification::fake();

        $service = app(BusinessNotificationService::class);

        $order = Order::create([
            'business_id' => $this->business->id,
            'order_number' => 'ORD-TEST-001',
            'order_type' => 'dine_in',
            'subtotal' => 10.00,
            'total_amount' => 11.00,
            'total_khr' => 45100,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        $service->notifyOrderEvent($order, 'order.confirmed');

        $order->update(['status' => 'cancelled']);
        $service->notifyOrderEvent($order, 'order.cancelled');

        Queue::assertPushed(SendTelegramBusinessNotificationJob::class, 1);
        Queue::assertPushed(SendTelegramBusinessNotificationJob::class, function (SendTelegramBusinessNotificationJob $job): bool {
            return $job->businessId === $this->business->id && $job->eventType === 'order.confirmed';
        });

        Notification::assertSentTo(
            $this->owner,
            BusinessEventNotification::class,
            fn (BusinessEventNotification $n): bool => $n->eventKey === 'order.confirmed'
        );

        Notification::assertNotSentTo(
            $this->owner,
            BusinessEventNotification::class,
            fn (BusinessEventNotification $n): bool => $n->eventKey === 'order.cancelled'
        );
    }

    public function test_duplicate_notifications_are_prevented_within_dedupe_window(): void
    {
        TelegramConnection::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'connection_mode' => 'custom_bot',
            'bot_token' => '123456:custom-secret-token',
            'chat_id' => '987654321',
            'is_active' => true,
            'notifications_enabled' => true,
        ]);

        Queue::fake();
        Notification::fake();

        $order = Order::create([
            'business_id' => $this->business->id,
            'order_number' => 'ORD-DUP-001',
            'order_type' => 'takeaway',
            'subtotal' => 5.00,
            'total_amount' => 5.50,
            'total_khr' => 22550,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $service = app(BusinessNotificationService::class);
        $service->notifyOrderEvent($order, 'order.created');
        $service->notifyOrderEvent($order, 'order.created');

        Queue::assertPushed(SendTelegramBusinessNotificationJob::class, 1);
        Notification::assertSentToTimes($this->owner, BusinessEventNotification::class, 1);
    }

    public function test_branch_scoping_prevents_dispatch_to_other_branch_telegram_or_staff(): void
    {
        $branchA = BusinessBranch::create([
            'business_id' => $this->business->id,
            'name' => 'Branch A',
            'is_active' => true,
        ]);

        $branchB = BusinessBranch::create([
            'business_id' => $this->business->id,
            'name' => 'Branch B',
            'is_active' => true,
        ]);

        $branchACashier = User::factory()->create();
        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $branchACashier->id,
            'role' => 'cashier',
            'branch_id' => $branchA->id,
            'is_active' => true,
        ]);

        TelegramConnection::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'connection_mode' => 'custom_bot',
            'bot_token' => '123456:branch-secret',
            'chat_id' => '987654321',
            'is_active' => true,
            'notifications_enabled' => true,
        ]);

        NotificationPreference::create([
            'business_id' => $this->business->id,
            'user_id' => null,
            'branch_id' => $branchA->id,
            'event_key' => 'order.created',
            'telegram_enabled' => true,
            'email_enabled' => false,
            'in_app_enabled' => true,
            'browser_enabled' => false,
        ]);

        Queue::fake();
        Notification::fake();

        $orderBranchB = Order::create([
            'business_id' => $this->business->id,
            'branch_id' => $branchB->id,
            'order_number' => 'ORD-BR-B',
            'order_type' => 'takeaway',
            'subtotal' => 8.00,
            'total_amount' => 8.80,
            'total_khr' => 36080,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        app(BusinessNotificationService::class)->notifyOrderEvent($orderBranchB, 'order.created');

        // Preference is scoped to Branch A, so Branch B event must not notify Telegram or Branch A staff
        Queue::assertNotPushed(SendTelegramBusinessNotificationJob::class);
        Notification::assertNotSentTo($branchACashier, BusinessEventNotification::class);
        Notification::assertNotSentTo($this->owner, BusinessEventNotification::class);

        $orderBranchA = Order::create([
            'business_id' => $this->business->id,
            'branch_id' => $branchA->id,
            'order_number' => 'ORD-BR-A',
            'order_type' => 'takeaway',
            'subtotal' => 8.00,
            'total_amount' => 8.80,
            'total_khr' => 36080,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        app(BusinessNotificationService::class)->notifyOrderEvent($orderBranchA, 'order.created');

        Queue::assertPushed(SendTelegramBusinessNotificationJob::class, 1);
        Notification::assertSentTo($branchACashier, BusinessEventNotification::class);
        Notification::assertSentTo($this->owner, BusinessEventNotification::class);
    }

    public function test_end_to_end_public_checkout_delivers_telegram_and_in_app_without_customer_pii_or_secrets(): void
    {
        $secretToken = '99887766:AAFakeSensitiveBotTokenSecret';

        TelegramConnection::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'connection_mode' => 'custom_bot',
            'bot_token' => $secretToken,
            'chat_id' => '777888999',
            'is_active' => true,
            'notifications_enabled' => true,
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 4242],
            ], 200),
        ]);

        TenantContext::set($this->business);
        $category = Category::create([
            'business_id' => $this->business->id,
            'name_en' => 'Mains',
            'slug' => 'mains',
            'is_active' => true,
        ]);
        $product = Product::create([
            'business_id' => $this->business->id,
            'category_id' => $category->id,
            'name_en' => 'Beef Lok Lak',
            'slug' => 'beef-lok-lak',
            'base_price' => 7.50,
            'is_available' => true,
        ]);
        TenantContext::clear();

        $sensitivePhone = '012999888';
        $sensitiveAddress = 'Secret Villa #42, Chamkarmon';

        $response = $this->postJson("/{$this->business->slug}/checkout", [
            'customer_name' => 'Dara Sokha',
            'customer_phone' => $sensitivePhone,
            'order_type' => 'delivery',
            'delivery_address' => $sensitiveAddress,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $orderNumber = $response->json('order_number');

        Http::assertSent(function ($request) use ($orderNumber, $sensitivePhone, $sensitiveAddress, $secretToken): bool {
            $body = (string) ($request['text'] ?? '');

            return str_contains($request->url(), '/sendMessage')
                && str_contains($body, $orderNumber)
                && ! str_contains($body, $sensitivePhone)
                && ! str_contains($body, $sensitiveAddress)
                && ! str_contains($body, $secretToken);
        });

        $this->assertDatabaseHas('telegram_notification_logs', [
            'business_id' => $this->business->id,
            'event_type' => 'order.created',
            'status' => 'sent',
        ]);

        $log = TelegramNotificationLog::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->firstOrFail();
        $this->assertStringNotContainsString($sensitivePhone, (string) $log->message_preview);
        $this->assertStringNotContainsString($sensitiveAddress, (string) $log->message_preview);
        $this->assertStringNotContainsString($secretToken, (string) $log->message_preview);

        $inApp = $this->owner->notifications()->first();
        $this->assertNotNull($inApp);
        $this->assertSame('order.created', $inApp->data['event_key']);
        $this->assertSame($this->business->id, $inApp->data['business_id']);
        $this->assertStringNotContainsString($sensitivePhone, (string) $inApp->data['message']);
        $this->assertStringNotContainsString($sensitiveAddress, (string) $inApp->data['message']);
    }

    public function test_inventory_low_stock_and_out_of_stock_trigger_only_on_threshold_crossings(): void
    {
        TenantContext::set($this->business);
        $item = InventoryItem::create([
            'business_id' => $this->business->id,
            'name_en' => 'Espresso Beans',
            'unit' => 'kg',
            'current_stock' => 12.000,
            'low_stock_threshold' => 5.000,
            'is_active' => true,
        ]);
        TenantContext::clear();

        // Crossing 12 -> 4 (<= 5 threshold) triggers low_stock
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.inventory.adjust-stock', $item), [
                'type' => 'out',
                'quantity' => 8,
                'reason' => 'Morning brew usage',
            ])
            ->assertRedirect();

        $this->assertCount(
            1,
            $this->owner->notifications()->get()->filter(fn ($n): bool => ($n->data['event_key'] ?? '') === 'inventory.low_stock')
        );

        // Deducting while already below threshold (4 -> 3) should NOT spam another low_stock alert
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.inventory.adjust-stock', $item), [
                'type' => 'out',
                'quantity' => 1,
                'reason' => 'Afternoon brew usage',
            ])
            ->assertRedirect();

        $this->assertCount(
            1,
            $this->owner->notifications()->get()->filter(fn ($n): bool => ($n->data['event_key'] ?? '') === 'inventory.low_stock')
        );

        // Hitting zero stock (3 -> 0) triggers out_of_stock
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.inventory.adjust-stock', $item), [
                'type' => 'out',
                'quantity' => 3,
                'reason' => 'Finished batch',
            ])
            ->assertRedirect();

        $this->assertCount(
            1,
            $this->owner->notifications()->get()->filter(fn ($n): bool => ($n->data['event_key'] ?? '') === 'inventory.out_of_stock')
        );
    }

    public function test_customer_review_triggers_created_and_low_rating_alert_for_three_stars_or_less(): void
    {
        CustomerReview::create([
            'business_id' => $this->business->id,
            'customer_name' => 'Happy Diner',
            'rating' => 5,
            'comment' => 'Great food and fast service!',
            'sentiment' => 'positive',
            'status' => 'published',
        ]);

        $notificationsAfterFiveStar = $this->owner->notifications()->get();
        $this->assertCount(1, $notificationsAfterFiveStar->filter(fn ($n): bool => ($n->data['event_key'] ?? '') === 'review.created'));
        $this->assertCount(0, $notificationsAfterFiveStar->filter(fn ($n): bool => ($n->data['event_key'] ?? '') === 'review.low_rating'));

        CustomerReview::create([
            'business_id' => $this->business->id,
            'customer_name' => 'Unhappy Diner',
            'rating' => 2,
            'comment' => 'Soup arrived lukewarm.',
            'sentiment' => 'negative',
            'status' => 'published',
        ]);

        $allNotifications = $this->owner->notifications()->get();
        $this->assertCount(2, $allNotifications->filter(fn ($n): bool => ($n->data['event_key'] ?? '') === 'review.created'));
        $this->assertCount(1, $allNotifications->filter(fn ($n): bool => ($n->data['event_key'] ?? '') === 'review.low_rating'));
    }

    public function test_reservation_lifecycle_and_in_app_mark_read_are_tenant_and_user_isolated(): void
    {
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.reservations.store'), [
                'customer_name' => 'Private Guest',
                'customer_phone' => '099888777',
                'party_size' => 4,
                'reservation_date' => now()->addDay()->toDateString(),
                'reservation_time' => '19:00',
            ])
            ->assertRedirect();

        $ownerNotification = $this->owner->notifications()->firstOrFail();
        $this->assertSame('reservation.created', $ownerNotification->data['event_key']);
        $this->assertStringNotContainsString('099888777', (string) $ownerNotification->data['message']);

        // Other tenant owner cannot read or mark Owner A's notification
        $otherOwner = User::factory()->create();
        $otherBusiness = Business::create([
            'owner_id' => $otherOwner->id,
            'name' => 'Other Kitchen',
            'slug' => 'other-kitchen',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);
        BusinessMember::create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherOwner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($otherOwner)
            ->withSession(['active_business_id' => $otherBusiness->id])
            ->post(route('business.notifications.read', $ownerNotification->id))
            ->assertStatus(404);

        // Owner marks their own notification read
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.notifications.read', $ownerNotification->id))
            ->assertRedirect();

        $this->assertNotNull($ownerNotification->fresh()->read_at);

        // Mark all read clears remaining unread notifications in active business
        $reservation = Reservation::where('business_id', $this->business->id)->firstOrFail();
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.reservations.update-status', $reservation), ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertGreaterThanOrEqual(1, $this->owner->unreadNotifications()->count());

        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $this->owner->unreadNotifications()->count());
    }

    public function test_daily_sales_summary_command_dispatches_only_for_active_businesses(): void
    {
        $suspendedOwner = User::factory()->create();
        $suspendedBusiness = Business::create([
            'owner_id' => $suspendedOwner->id,
            'name' => 'Paused Cafe',
            'slug' => 'paused-cafe',
            'business_type' => 'cafe',
            'status' => 'suspended',
        ]);

        Order::create([
            'business_id' => $this->business->id,
            'order_number' => 'ORD-SUM-001',
            'order_type' => 'dine_in',
            'subtotal' => 25.00,
            'total_amount' => 27.50,
            'total_khr' => 112750,
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        Order::create([
            'business_id' => $suspendedBusiness->id,
            'order_number' => 'ORD-SUSP-001',
            'order_type' => 'takeaway',
            'subtotal' => 10.00,
            'total_amount' => 10.00,
            'total_khr' => 41000,
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);

        $this->artisan('sabayra:send-daily-sales-summaries', [
            '--date' => now()->toDateString(),
        ])->assertSuccessful();

        $summaryNotification = $this->owner->notifications()
            ->get()
            ->firstWhere('data.event_key', 'business.daily_summary');

        $this->assertNotNull($summaryNotification);
        $this->assertStringContainsString('$27.50', (string) $summaryNotification->data['message']);
        $this->assertCount(0, $suspendedOwner->notifications()->get());
    }
}
