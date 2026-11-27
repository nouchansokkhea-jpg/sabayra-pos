# SABAYRA AI Center — Database Design

All new tables use UUID primary keys (`HasUuids`), explicit `business_id` foreign keys with `cascadeOnDelete()` for multi-tenant isolation (`BelongsToTenant`), composite query indexes compatible with PostgreSQL and SQLite, and exact decimal precision for tokens, credits, costs, and statistical metrics.

---

## 1. Alterations to Existing Tables

### `plans` (additive columns)
| Column | Type | Default | Purpose |
| --- | --- | --- | --- |
| `has_ai_assistant` | `boolean` | `true` | Enables AI Business Assistant |
| `has_ai_menu_creator` | `boolean` | `true` | Enables AI Menu Creator & Translator |
| `has_ai_marketing` | `boolean` | `false` | Enables AI Marketing Studio |
| `has_ai_forecasting` | `boolean` | `false` | Enables AI Inventory Forecast & Sales Prediction |
| `ai_monthly_credits` | `integer` | `100` | Monthly AI credit pool for businesses on this plan |
| `ai_max_tokens_per_request` | `integer` | `2048` | Max output token budget per request |

### `business_ai_insights` (additive columns preserving existing schema)
Preserves existing `id`, `business_id`, `insight_type`, `payload`, `generated_at`, `created_at`, `updated_at`:

| Column | Type | Nullable / Default | Purpose |
| --- | --- | --- | --- |
| `title` | `string(160)` | `nullable` | Display heading for the insight card |
| `summary` | `text` | `nullable` | Concise natural-language explanation |
| `recommended_action` | `string(255)` | `nullable` | Suggested business next action |
| `metrics_json` | `json` | `nullable` | Structured supporting numbers |
| `priority` | `string(20)` | `'medium'` | `high`, `medium`, `low` |
| `period_start` | `date` | `nullable` | Start of analyzed date window |
| `period_end` | `date` | `nullable` | End of analyzed date window |
| `status` | `string(20)` | `'new'` | `new`, `reviewed`, `dismissed` |
| `reviewed_by` | `foreignId -> users` | `nullable` | User who reviewed or dismissed |
| `reviewed_at` | `timestamp` | `nullable` | Timestamp of status change |

Indexes: `(business_id, status, generated_at)` for fast dashboard/overview queries.

---

## 2. New AI Tables

### `ai_feature_settings`
Per-business AI preferences (never stores unencrypted platform keys; model/provider override governed by Super Admin policy):
- `id` (`uuid` PK)
- `business_id` (`foreignUuid -> businesses`, unique)
- `preferred_language` (`string(20)`, default `'auto'`: `auto`, `km`, `en`, `mixed`)
- `preferred_tone` (`string(30)`, default `'friendly'`)
- `provider_override` (`string(40)`, nullable)
- `model_override` (`string(80)`, nullable)
- `temperature` (`decimal(3,2)`, default `0.40`)
- `assistant_enabled` (`boolean`, default `true`)
- `menu_creator_enabled` (`boolean`, default `true`)
- `marketing_studio_enabled` (`boolean`, default `true`)
- `forecasting_enabled` (`boolean`, default `true`)
- `insights_enabled` (`boolean`, default `true`)
- `created_at`, `updated_at`

### `ai_credit_ledgers`
Monthly atomic credit quota tracker per business preventing overdrafts and race conditions:
- `id` (`uuid` PK)
- `business_id` (`foreignUuid -> businesses`)
- `period_ym` (`string(7)`, e.g. `'2026-11'`)
- `allocated_credits` (`integer`, default `100`)
- `bonus_credits` (`integer`, default `0`)
- `used_credits` (`integer`, default `0`)
- `reserved_credits` (`integer`, default `0`)
- `created_at`, `updated_at`
- Unique index: `(business_id, period_ym)`

### `ai_usage_logs`
Audit ledger and duplicate-charge protection for every AI invocation:
- `id` (`uuid` PK)
- `business_id` (`foreignUuid -> businesses`)
- `user_id` (`foreignId -> users`, nullable `nullOnDelete`)
- `request_uuid` (`string(64)`, unique) — ensures credits are never charged twice for the same request
- `feature` (`string(50)`): `assistant`, `menu_creator`, `marketing_studio`, `inventory_forecast`, `sales_prediction`, `insights`
- `provider` (`string(40)`): `gemini`, `openai`
- `model` (`string(80)`)
- `prompt_tokens` (`integer`, default `0`)
- `completion_tokens` (`integer`, default `0`)
- `total_tokens` (`integer`, default `0`)
- `credits_charged` (`integer`, default `1`)
- `estimated_cost_usd` (`decimal(10,6)`, default `0.000000`)
- `latency_ms` (`integer`, nullable)
- `status` (`string(25)`): `pending`, `completed`, `failed`, `refunded`, `rejected`
- `error_code` (`string(60)`, nullable)
- `error_message` (`string(500)`, nullable)
- `created_at`, `updated_at`
- Indexes: `(business_id, created_at)`, `(business_id, feature, status)`

### `ai_conversations`
Multi-tenant conversational threads for AI Business Assistant:
- `id` (`uuid` PK)
- `business_id` (`foreignUuid -> businesses`)
- `user_id` (`foreignId -> users`, `cascadeOnDelete`)
- `title` (`string(160)`)
- `preferred_language` (`string(20)`, default `'auto'`)
- `message_count` (`integer`, default `0`)
- `last_message_at` (`timestamp`, nullable)
- `created_at`, `updated_at`
- Index: `(business_id, user_id, last_message_at)`

### `ai_messages`
Ordered messages and tool-execution telemetry within a conversation:
- `id` (`uuid` PK)
- `business_id` (`foreignUuid -> businesses`)
- `conversation_id` (`foreignUuid -> ai_conversations`, `cascadeOnDelete`)
- `user_id` (`foreignId -> users`, nullable `nullOnDelete`)
- `role` (`string(20)`): `user`, `assistant`, `tool`
- `content` (`text`)
- `language_detected` (`string(20)`, nullable)
- `tool_calls_json` (`json`, nullable)
- `tool_results_json` (`json`, nullable)
- `data_sources_json` (`json`, nullable)
- `provider` (`string(40)`, nullable)
- `model` (`string(80)`, nullable)
- `usage_log_id` (`foreignUuid -> ai_usage_logs`, nullable `nullOnDelete`)
- `created_at`, `updated_at`
- Index: `(conversation_id, created_at)`

### `ai_generated_contents`
Versioned archive of AI Menu Creator outputs and AI Marketing Studio campaigns:
- `id` (`uuid` PK)
- `business_id` (`foreignUuid -> businesses`)
- `user_id` (`foreignId -> users`, nullable `nullOnDelete`)
- `feature` (`string(40)`): `menu_creator`, `marketing_studio`
- `content_type` (`string(50)`): `description`, `improve`, `translate_km`, `translate_en`, `product_name`, `short_description`, `promo_description`, `facebook_caption`, `tiktok_script`, `telegram_promo`, `promo_campaign`, `hashtags`
- `platform` (`string(30)`, default `'menu'`): `menu`, `facebook`, `tiktok`, `telegram`, `general`
- `product_id` (`foreignUuid -> products`, nullable `nullOnDelete`)
- `language` (`string(20)`, default `'km'`)
- `tone` (`string(30)`, nullable)
- `title` (`string(160)`, nullable)
- `input_payload` (`json`, nullable)
- `output_text` (`text`)
- `structured_output` (`json`, nullable)
- `status` (`string(20)`, default `'draft'`): `draft`, `applied`, `archived`
- `usage_log_id` (`foreignUuid -> ai_usage_logs`, nullable `nullOnDelete`)
- `created_at`, `updated_at`
- Index: `(business_id, feature, created_at)`

### `ai_forecasts`
Cached statistical + AI-explained inventory and sales predictions:
- `id` (`uuid` PK)
- `business_id` (`foreignUuid -> businesses`)
- `user_id` (`foreignId -> users`, nullable `nullOnDelete`)
- `forecast_type` (`string(30)`): `inventory`, `sales`
- `horizon` (`string(20)`): `1d`, `7d`, `30d`, `stock_runway`
- `status` (`string(30)`): `ready`, `insufficient_data`, `failed`
- `historical_days_available` (`integer`, default `0`)
- `data_points_count` (`integer`, default `0`)
- `confidence_level` (`string(20)`, default `'insufficient'`): `high`, `medium`, `low`, `insufficient`
- `statistical_payload` (`json`) — authoritative numeric forecasts computed in PHP
- `ai_explanation` (`text`, nullable) — grounded natural-language summary
- `limitations` (`text`, nullable)
- `assumptions_json` (`json`, nullable)
- `usage_log_id` (`foreignUuid -> ai_usage_logs`, nullable `nullOnDelete`)
- `generated_at` (`timestamp`)
- `expires_at` (`timestamp`, nullable)
- `created_at`, `updated_at`
- Index: `(business_id, forecast_type, horizon, generated_at)`

---

## 3. Platform-Level Configuration Keys (`platform_settings`)
- `ai.enabled` (boolean)
- `ai.default_provider` (`gemini` or `openai`)
- `ai.default_model` (e.g. `gemini-2.5-flash` or `gpt-4o-mini`)
- `ai.gemini_api_key` (**encrypted** via `Crypt::encryptString()`)
- `ai.openai_api_key` (**encrypted** via `Crypt::encryptString()`)
- `ai.default_monthly_credits` (integer, fallback when subscription plans are disabled)
- `ai.max_output_tokens` (integer)
- `ai.timeout_seconds` (integer)
- `ai.retry_attempts` (integer)
- `ai.allow_business_override` (boolean)
