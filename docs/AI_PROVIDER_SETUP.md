# SABAYRA AI Provider Configuration & Setup Guide

## 1. Environment Variables (`.env`)

Configure default behavior and optional fallback API credentials in `.env` (reference `.env.example`):

- `AI_ENABLED=true`
- `AI_DEFAULT_PROVIDER=gemini` (`gemini` or `openai`)
- `AI_DEFAULT_MODEL=gemini-2.5-flash`
- `AI_TEMPERATURE=0.4`
- `AI_MAX_OUTPUT_TOKENS=2048`
- `AI_TIMEOUT_SECONDS=30`
- `AI_RETRY_ATTEMPTS=2`
- `AI_DEFAULT_MONTHLY_CREDITS=100`
- `AI_ALLOW_BUSINESS_OVERRIDE=false`
- `GEMINI_API_KEY=...` / `GEMINI_MODEL=gemini-2.5-flash`
- `OPENAI_API_KEY=...` / `OPENAI_MODEL=gpt-4o-mini`

## 2. Super Admin Runtime Configuration

Platform administrators manage live AI settings from **Super Admin -> Platform Settings** (`/admin/settings`, route `admin.settings.ai.update`):

- Toggle AI Center platform-wide (`ai.enabled`).
- Select default provider (`gemini` or `openai`) and model (`gemini-2.5-flash`, `gemini-2.5-pro`, `gemini-2.0-flash`, `gpt-4o-mini`, `gpt-4o`).
- Tune temperature (`0.0` to `1.5`), max output tokens (`128` to `8192`), request timeout (`5s` to `120s`), transient retry attempts (`0` to `3`), and fallback monthly credits when `SUBSCRIPTION_PLANS_ENABLED=false`.
- Store Google Gemini and OpenAI API keys encrypted inside `platform_settings` (`is_encrypted = true`).
- Click **Test AI Connection** (`admin.settings.ai.test`) to verify key validity and latency without consuming tenant AI credits.

## 3. Graceful Error & Refund Behavior

- Invalid/rejected keys (`401`/`403`/Gemini `API_KEY_INVALID`) map to `invalid_api_key` with redacted error text.
- Rate limits (`429`) and transient `5xx` outages retry automatically up to configured retry attempts before returning `rate_limit` or `provider_outage`.
- Timeouts (`ConnectionException`) map to `timeout` (`504`).
- Reserved AI credits in `ai_credit_ledgers` are automatically released (`status = 'refunded'`) whenever a provider call throws an exception or returns invalid structured JSON.
