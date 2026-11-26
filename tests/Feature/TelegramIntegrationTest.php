<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\TelegramConnection;
use App\Models\TelegramVerificationCode;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $staff;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();

        Config::set('services.telegram.bot_token', '999999999:OFFICIAL_TEST_BOT_TOKEN');
        Config::set('services.telegram.bot_username', 'SabayraOfficialBot');
        Config::set('services.telegram.webhook_secret', 'sabayra-webhook-secret-xyz');

        $this->owner = User::factory()->create();
        $this->business = Business::create([
            'owner_id' => $this->owner->id,
            'name' => 'Kampuchea Grill',
            'slug' => 'kampuchea-grill',
            'status' => 'active',
        ]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create();
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

    public function test_owner_can_generate_one_time_verification_code_and_link_correct_telegram_chat(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 42]], 200),
        ]);

        // 1. Generate verification code in Settings
        $connectResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.settings.telegram.connect'));

        $connectResponse->assertRedirect(route('business.settings.index', ['section' => 'notifications']));

        $sessionLink = session("telegram_link_{$this->business->id}");
        $this->assertIsArray($sessionLink);
        $plainCode = $sessionLink['code'];
        $this->assertStringStartsWith('SBYR', $plainCode);

        // Stored hashed in DB (plain code is NOT stored verbatim)
        $this->assertDatabaseHas('telegram_verification_codes', [
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'code_hash' => hash('sha256', $plainCode),
            'used_at' => null,
        ]);

        // 2. Reject invalid webhook secret
        $badWebhook = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
        ])->postJson(route('webhooks.telegram'), [
            'message' => [
                'text' => "/start {$plainCode}",
                'chat' => ['id' => 123456789],
            ],
        ]);
        $badWebhook->assertForbidden();

        // 3. Valid webhook links the chat cleanly
        $goodWebhook = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'sabayra-webhook-secret-xyz',
        ])->postJson(route('webhooks.telegram'), [
            'message' => [
                'text' => "/start {$plainCode}",
                'chat' => ['id' => 987654321, 'type' => 'private'],
                'from' => ['id' => 987654321, 'username' => 'sokha_owner', 'first_name' => 'Sokha'],
            ],
        ]);

        $goodWebhook->assertOk()->assertJson([
            'ok' => true,
            'status' => 'connected',
        ]);

        // Verification code marked as used
        $codeRow = TelegramVerificationCode::where('code_hash', hash('sha256', $plainCode))->first();
        $this->assertNotNull($codeRow->used_at);

        // Connection created and encrypted at rest
        $connection = TelegramConnection::where('business_id', $this->business->id)->first();
        $this->assertNotNull($connection);
        $this->assertTrue($connection->is_active);
        $this->assertSame('official_bot', $connection->connection_mode);
        $this->assertSame('987654321', $connection->chat_id);
        $this->assertSame('sokha_owner', $connection->telegram_username);

        // Raw DB chat_id must be encrypted, NOT plaintext
        $rawChatId = DB::table('telegram_connections')->where('id', $connection->id)->value('chat_id');
        $this->assertNotSame('987654321', $rawChatId);

        // UI & status endpoint show masked Chat ID only, never full Chat ID
        $statusResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->getJson(route('business.settings.telegram.status'));

        $statusResponse->assertOk()
            ->assertJson([
                'connected' => true,
                'masked_chat_id' => $connection->masked_chat_id,
            ]);
        $this->assertStringNotContainsString('987654321', $statusResponse->getContent());
    }

    public function test_expired_verification_code_cannot_link_telegram_chat(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.settings.telegram.connect'));

        $plainCode = session("telegram_link_{$this->business->id}")['code'];

        // Travel past 15-minute expiration
        $this->travel(16)->minutes();

        $webhookResponse = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'sabayra-webhook-secret-xyz',
        ])->postJson(route('webhooks.telegram'), [
            'message' => [
                'text' => "/start {$plainCode}",
                'chat' => ['id' => 555666777],
            ],
        ]);

        $webhookResponse->assertOk()->assertJson([
            'ok' => true,
            'status' => 'code_expired',
        ]);

        $this->assertDatabaseMissing('telegram_connections', [
            'business_id' => $this->business->id,
            'is_active' => true,
        ]);
    }

    public function test_verification_code_cannot_be_reused(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
        ]);

        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.settings.telegram.connect'));

        $plainCode = session("telegram_link_{$this->business->id}")['code'];

        // First link succeeds
        $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'sabayra-webhook-secret-xyz'])
            ->postJson(route('webhooks.telegram'), [
                'message' => [
                    'text' => "/start {$plainCode}",
                    'chat' => ['id' => 111222333],
                ],
            ])->assertJson(['status' => 'connected']);

        // Attacker tries reusing same code with different chat ID
        $reuseResponse = $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'sabayra-webhook-secret-xyz'])
            ->postJson(route('webhooks.telegram'), [
                'message' => [
                    'text' => "/start {$plainCode}",
                    'chat' => ['id' => 999888777],
                ],
            ]);

        $reuseResponse->assertOk()->assertJson(['status' => 'code_already_used']);

        // Original linked chat remains intact
        $connection = TelegramConnection::where('business_id', $this->business->id)->first();
        $this->assertSame('111222333', $connection->chat_id);
    }

    public function test_manual_custom_bot_validates_api_before_saving_and_encrypts_token(): void
    {
        $badToken = '111222:BAD_BOT_TOKEN_SECRET';
        $validToken = '777888:REAL_CUSTOM_BOT_SECRET_ABC';

        Http::fake([
            "https://api.telegram.org/bot{$badToken}/getMe" => Http::response([
                'ok' => false,
                'description' => 'Unauthorized',
            ], 401),
            "https://api.telegram.org/bot{$validToken}/getMe" => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 777888,
                    'username' => 'KampucheaAlertsBot',
                    'first_name' => 'Kampuchea Alerts',
                ],
            ], 200),
            "https://api.telegram.org/bot{$validToken}/sendMessage" => Http::sequence()
                ->push(['ok' => false, 'description' => 'Bad Request: chat not found'], 400)
                ->push(['ok' => true, 'result' => ['message_id' => 88]], 200)
                ->push(['ok' => true, 'result' => ['message_id' => 89]], 200),
        ]);

        // 1. Invalid bot token fails and is NOT saved
        $failTokenResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.settings.telegram.custom'), [
                'bot_token' => $badToken,
                'chat_id' => '-100555444333',
                'notifications_enabled' => '1',
            ]);

        $failTokenResponse->assertRedirect(route('business.settings.index', ['section' => 'notifications']));
        $failTokenResponse->assertSessionHas('error');
        $this->assertDatabaseCount('telegram_connections', 0);

        // 2. Valid token + unreachable chat fails and is NOT saved as active
        $failChatResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.settings.telegram.custom'), [
                'bot_token' => $validToken,
                'chat_id' => '-100000000000',
                'notifications_enabled' => '1',
            ]);

        $failChatResponse->assertSessionHas('error');
        $this->assertDatabaseCount('telegram_connections', 0);

        // 3. Valid token + valid chat succeeds and encrypts credentials at rest
        $okResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.settings.telegram.custom'), [
                'bot_token' => $validToken,
                'chat_id' => '-100999888777',
                'notifications_enabled' => '1',
            ]);

        $okResponse->assertSessionHas('success');

        $connection = TelegramConnection::where('business_id', $this->business->id)->first();
        $this->assertNotNull($connection);
        $this->assertTrue($connection->is_active);
        $this->assertSame('custom_bot', $connection->connection_mode);
        $this->assertSame($validToken, $connection->bot_token);

        // Verify database ciphertext does NOT contain plaintext bot token
        $rawToken = DB::table('telegram_connections')->where('id', $connection->id)->value('bot_token');
        $this->assertNotSame($validToken, $rawToken);
        $this->assertStringNotContainsString($validToken, (string) $rawToken);

        // Verify Settings page HTML never leaks full token or raw chat ID
        $pageResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.settings.index', ['section' => 'notifications']));

        $pageResponse->assertOk()
            ->assertSee('KampucheaAlertsBot')
            ->assertSee($connection->masked_chat_id)
            ->assertDontSee($validToken)
            ->assertDontSee('-100999888777');

        // 4. Test connection sends message & logs delivery
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.settings.telegram.test'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('telegram_notification_logs', [
            'business_id' => $this->business->id,
            'event_type' => 'connection_test',
            'status' => 'sent',
        ]);

        // 5. Disconnect purges token & chat ID
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.settings.telegram.disconnect'))
            ->assertSessionHas('success');

        $connection->refresh();
        $this->assertFalse($connection->is_active);
        $this->assertNull($connection->bot_token);
        $this->assertNull($connection->chat_id);
    }

    public function test_cross_business_and_unauthorized_staff_cannot_access_telegram_controls(): void
    {
        $otherOwner = User::factory()->create();
        $otherBusiness = Business::create([
            'owner_id' => $otherOwner->id,
            'name' => 'Other Store',
            'slug' => 'other-store',
            'status' => 'active',
        ]);

        BusinessMember::create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherOwner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $otherConn = TelegramConnection::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherOwner->id,
            'connection_mode' => 'custom_bot',
            'bot_token' => '555:SECRET_OTHER_TOKEN',
            'chat_id' => '444555666',
            'is_active' => true,
            'notifications_enabled' => true,
        ]);

        // Staff without manage_settings cannot trigger any Telegram control
        $this->actingAs($this->staff)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.settings.telegram.connect'))
            ->assertForbidden();

        $this->actingAs($this->staff)
            ->withSession(['active_business_id' => $this->business->id])
            ->getJson(route('business.settings.telegram.status'))
            ->assertForbidden();

        // Owner A cannot affect Business B even by injecting forged business_id
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.settings.telegram.disconnect'), [
                'business_id' => $otherBusiness->id,
            ])
            ->assertRedirect();

        $otherConn->refresh();
        $this->assertTrue($otherConn->is_active);
        $this->assertSame('555:SECRET_OTHER_TOKEN', $otherConn->bot_token);
    }

    public function test_notifications_view_renders_bot_usernames_without_literal_blade_braces_and_respects_verify_ssl(): void
    {
        config(['services.telegram.bot_username' => 'Sabayrabot']);
        $this->business->update(['name' => "Sokha's Kampuchea Grill"]);

        TelegramConnection::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'connection_mode' => 'official_bot',
            'chat_id' => '99887766',
            'is_active' => true,
            'notifications_enabled' => true,
        ]);

        $connectedView = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.settings.index', ['section' => 'notifications']));

        $connectedView->assertStatus(200)
            ->assertDontSee("for Sokha's Kampuchea Grill", false);

        TelegramConnection::withoutGlobalScopes()->where('business_id', $this->business->id)->delete();

        $disconnectedView = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->get(route('business.settings.index', ['section' => 'notifications']));

        $disconnectedView->assertStatus(200)
            ->assertSee('@Sabayrabot')
            ->assertDontSee('@{{ $officialBotUsername }}', false);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.telegram.org/*' => \Illuminate\Support\Facades\Http::response([
                'ok' => true,
                'result' => ['username' => 'Sabayrabot'],
            ], 200),
        ]);

        config(['services.telegram.verify_ssl' => false]);
        $result = app(\App\Services\Telegram\TelegramBotService::class)->getMe('123:test-token');
        $this->assertTrue($result['ok']);
        $this->assertSame('Sabayrabot', $result['bot']['username']);
    }

    public function test_polling_fallback_links_pending_verification_code_for_localhost_testing(): void
    {
        config([
            'services.telegram.bot_token' => '999:official-poll-token',
            'services.telegram.bot_username' => 'Sabayrabot',
            'services.telegram.polling_fallback' => true,
        ]);

        // Generate a real 15-minute verification code for this business
        $connectResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.settings.telegram.connect'));
        $connectResponse->assertRedirect();

        $pending = session("telegram_link_{$this->business->id}");
        $code = $pending['code'];

        \Illuminate\Support\Facades\Http::fake([
            'https://api.telegram.org/bot999:official-poll-token/getUpdates*' => \Illuminate\Support\Facades\Http::response([
                'ok' => true,
                'result' => [
                    [
                        'update_id' => 1001,
                        'message' => [
                            'message_id' => 55,
                            'from' => [
                                'id' => 777123,
                                'first_name' => 'Sophea',
                                'username' => 'sophea_owner',
                            ],
                            'chat' => [
                                'id' => 777123,
                                'type' => 'private',
                            ],
                            'text' => "/start {$code}",
                        ],
                    ],
                ],
            ], 200),
            'https://api.telegram.org/bot999:official-poll-token/sendMessage' => \Illuminate\Support\Facades\Http::response([
                'ok' => true,
                'result' => ['message_id' => 56],
            ], 200),
        ]);

        $statusResponse = $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->getJson(route('business.settings.telegram.status'));

        $statusResponse->assertStatus(200)
            ->assertJson([
                'connected' => true,
                'connection_mode' => 'official_bot',
                'telegram_username' => 'sophea_owner',
            ]);

        $this->assertDatabaseHas('telegram_connections', [
            'business_id' => $this->business->id,
            'is_active' => true,
            'connection_mode' => 'official_bot',
        ]);
    }

    public function test_multiple_businesses_link_independently_via_shared_official_bot_and_webhook_setup_command(): void
    {
        config([
            'services.telegram.bot_token' => '999:official-shared-token',
            'services.telegram.bot_username' => 'Sabayrabot',
            'services.telegram.webhook_secret' => 'shared-secret-token',
        ]);

        Http::fake([
            'https://api.telegram.org/bot999:official-shared-token/setWebhook' => Http::response(['ok' => true, 'result' => true], 200),
            'https://api.telegram.org/bot999:official-shared-token/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'Sabayrabot']], 200),
            'https://api.telegram.org/bot999:official-shared-token/getWebhookInfo' => Http::response([
                'ok' => true,
                'result' => ['url' => 'https://sabayra.example.com/webhooks/telegram', 'pending_update_count' => 0],
            ], 200),
            'https://api.telegram.org/bot999:official-shared-token/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 77]], 200),
        ]);

        $this->artisan('sabayra:telegram-webhook', ['--set' => true, '--url' => 'https://sabayra.example.com'])
            ->assertSuccessful();

        $this->artisan('sabayra:telegram-webhook', ['--info' => true])
            ->assertSuccessful();

        $otherOwner = User::factory()->create();
        $otherBusiness = Business::create([
            'owner_id' => $otherOwner->id,
            'name' => 'Bokor Cafe',
            'slug' => 'bokor-cafe',
            'status' => 'active',
        ]);
        BusinessMember::create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherOwner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Both businesses generate their own one-time codes
        $this->actingAs($this->owner)
            ->withSession(['active_business_id' => $this->business->id])
            ->post(route('business.settings.telegram.connect'))
            ->assertRedirect();
        $codeA = session("telegram_link_{$this->business->id}")['code'];

        $this->actingAs($otherOwner)
            ->withSession(['active_business_id' => $otherBusiness->id])
            ->post(route('business.settings.telegram.connect'))
            ->assertRedirect();
        $codeB = session("telegram_link_{$otherBusiness->id}")['code'];

        // Both verify through the SAME shared webhook -> strictly routed to their own chats
        $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'shared-secret-token'])
            ->postJson(route('webhooks.telegram'), [
                'message' => [
                    'text' => "/start {$codeA}",
                    'chat' => ['id' => 111111, 'type' => 'private'],
                    'from' => ['id' => 111111, 'username' => 'owner_a', 'first_name' => 'OwnerA'],
                ],
            ])->assertOk();

        $this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'shared-secret-token'])
            ->postJson(route('webhooks.telegram'), [
                'message' => [
                    'text' => "/start {$codeB}",
                    'chat' => ['id' => 222222, 'type' => 'private'],
                    'from' => ['id' => 222222, 'username' => 'owner_b', 'first_name' => 'OwnerB'],
                ],
            ])->assertOk();

        // Dispatch alert for Business B -> goes ONLY to chat_id 222222, never 111111
        app(\App\Services\Notifications\BusinessNotificationService::class)->notifyAccountAlert(
            $otherBusiness,
            'Plan Activated',
            'Bokor Cafe plan is active.'
        );

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/sendMessage')
                && ($request['chat_id'] ?? null) === '222222'
                && str_contains((string) ($request['text'] ?? ''), 'Bokor Cafe');
        });

        Http::assertNotSent(function ($request): bool {
            return str_contains($request->url(), '/sendMessage')
                && ($request['chat_id'] ?? null) === '111111'
                && str_contains((string) ($request['text'] ?? ''), 'Bokor Cafe');
        });
    }
}
