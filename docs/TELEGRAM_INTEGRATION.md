# SABAYRA Telegram Bot Integration Guide

## Overview

SABAYRA enables merchants to receive business updates over Telegram using two mutually exclusive connection modes per tenant workspace:

1. **One-Click Official Bot Linking** (`official_bot`): The merchant clicks **Connect Telegram** inside Settings → Notifications to generate a 15-minute single-use verification code (`SBYR...`), opens the official SABAYRA bot via deep link (`https://t.me/{bot}?start={code}`), and verifies via Telegram webhook.
2. **Custom Business Bot** (`custom_bot`): The merchant supplies their own BotFather token and target group/user Chat ID. SABAYRA validates both `getMe` and test message delivery against the Telegram Bot API *before* persisting credentials as active.

## Database Tables

- `telegram_connections`: Stores one connection record per `business_id`, encrypting `bot_token` and `chat_id` at rest via Laravel's `'encrypted'` cast and hiding both fields from serialization.
- `telegram_verification_codes`: Stores SHA-256 hashes (`code_hash`) of single-use verification codes, expiration timestamps (`expires_at`), and single-use redemption timestamps (`used_at`).
- `telegram_notification_logs`: Records delivery status (`sent`, `failed`, `skipped`), masked recipients, sanitized previews, and token-redacted error text.

## Routes & Controllers

| Method | URI | Name | Controller Action |
|---|---|---|---|
| `POST` | `/webhooks/telegram` | `webhooks.telegram` | `Public\TelegramWebhookController@handle` (CSRF exempt, `throttle:60,1`, verifies `X-Telegram-Bot-Api-Secret-Token`) |
| `POST` | `/app/settings/telegram/connect` | `business.settings.telegram.connect` | `Business\TelegramSettingsController@connect` |
| `POST` | `/app/settings/telegram/custom` | `business.settings.telegram.custom` | `Business\TelegramSettingsController@saveCustomBot` |
| `PATCH` | `/app/settings/telegram/toggle` | `business.settings.telegram.toggle` | `Business\TelegramSettingsController@toggle` |
| `POST` | `/app/settings/telegram/test` | `business.settings.telegram.test` | `Business\TelegramSettingsController@test` |
| `POST` | `/app/settings/telegram/disconnect` | `business.settings.telegram.disconnect` | `Business\TelegramSettingsController@disconnect` |
| `GET` | `/app/settings/telegram/status` | `business.settings.telegram.status` | `Business\TelegramSettingsController@status` (masked JSON status polling) |

## Services

- `App\Services\Telegram\TelegramBotService`: Wraps Laravel HTTP client calls (`getMe`, `sendMessage`), scrubs tokens (`[REDACTED_TOKEN]`) from errors/logs, and records `TelegramNotificationLog`.
- `App\Services\Telegram\TelegramConnectionService`: Handles verification code generation, atomic webhook verification & linking, custom bot validation, connection toggling, test delivery, and credential purging on disconnect.

## Environment Variables & Bot Setup

Set the following variables in `.env` / Render Environment for official bot support:

```env
TELEGRAM_BOT_TOKEN=123456:your_botfather_token
TELEGRAM_BOT_USERNAME=YourSabayraBot
TELEGRAM_WEBHOOK_SECRET=long-random-secret-token
TELEGRAM_VERIFICATION_TTL_MINUTES=15
TELEGRAM_VERIFY_SSL=true
TELEGRAM_POLLING_FALLBACK=false
```

Register your webhook URL automatically via Artisan (or during Render container startup):

```bash
php artisan sabayra:telegram-webhook --set --url=https://your-domain.com
php artisan sabayra:telegram-webhook --info
```

Or register manually with Telegram Bot API (`setWebhook`):

```bash
curl -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/setWebhook" \
  -d "url=https://your-domain.com/webhooks/telegram" \
  -d "secret_token=${TELEGRAM_WEBHOOK_SECRET}"
```

## Security Controls

- All `telegram_connections` queries & mutations strictly authorize `User::canManageBusinessSettings($business)` via `TenantContext::get()`.
- `bot_token` and `chat_id` are encrypted at rest and hidden from `toArray()` / JSON / HTML inputs; only `masked_chat_id` is exposed to the UI.
- Error messages are sanitized before logging or displaying to strip `bot<id>:<secret>` patterns.
- Verification codes expire after 15 minutes, are stored hashed (`sha256`), and cannot be reused after redemption.
- Webhook endpoint verifies `X-Telegram-Bot-Api-Secret-Token` with `hash_equals` and enforces rate limiting (`60/min`).

## Phase 3 Event Preferences & Queued Dispatch

- `App\Services\Notifications\BusinessNotificationService` evaluates `NotificationPreference`, branch scope, and cache-based deduplication before dispatching `App\Jobs\SendTelegramBusinessNotificationJob` and `App\Notifications\BusinessEventNotification` (both implementing `ShouldQueue`).
- Notifications omit sensitive customer phone numbers, delivery addresses, email addresses, and payment credentials.
- `docker/supervisord.conf` configures `[program:laravel-worker]` (`php artisan queue:work`) alongside PHP-FPM and Nginx, and `sabayra:send-daily-sales-summaries` is scheduled daily at `23:55` in `routes/console.php`.
