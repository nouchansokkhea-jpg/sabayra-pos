# SABAYRA AI Center — Phase 0 Project Audit

**Branch:** `feature/sabayra-ai-center` (branched from `main` at `e0800f6`)  
**Audit Date:** 2026-11-27  
**Framework & Runtime:** Laravel `13.43.0` (`laravel/framework`), PHP `^8.3` (`composer.json`), Blade + Tailwind CSS v3 + Alpine.js + Chart.js + Vite `8`, PostgreSQL (`pgsql` in production) / SQLite `:memory:` (automated tests).

---

## 1. What Already Exists in the Repository

### 1.1 Existing AI Modules & Placeholders
The repository already contains an early rule-based AI module and UI routes under Business Intelligence:

| Component | Path | Current Behavior |
| --- | --- | --- |
| `AiInsightController` | `app/Http/Controllers/Business/AiInsightController.php` | Exposes 4 GET routes (`weeklySummary`, `menuAudit`, `sentimentRadar`, `upsellPairings`) |
| `AiInsightsService` | `app/Services/AI/AiInsightsService.php` | Computes this-week vs. last-week revenue/order growth, top product, customer counts, and low-stock count with hardcoded recommendation templates |
| `MenuAuditService` | `app/Services/AI/MenuAuditService.php` | Scores menu quality (missing images, short descriptions `< 20` chars, `$0.00` prices, pricing outliers) |
| `SentimentRadarService` | `app/Services/AI/SentimentRadarService.php` | Keyword-matches `customer_reviews.comment` using fixed English word lists (`delicious`, `slow`, etc.) |
| `UpsellPairingService` | `app/Services/AI/UpsellPairingService.php` | Runs SQL co-purchase aggregation across `order_items` within the tenant |
| `AiMenuBuilderController` | `app/Http/Controllers/Business/AiMenuBuilderController.php` | Shows a static Alpine sample dish array (`menu-builder.blade.php`) and saves submitted dishes/categories into `products` and `categories` |
| `BusinessAiInsight` | `app/Models/BusinessAiInsight.php` | Maps to `business_ai_insights` (`2026_11_23_100003_create_ai_cache_and_ar_assets_tables.php`); currently unused by controllers and lacks `BelongsToTenant` |

**Audit Defect Found in Existing AI Service:** `AiInsightsService` queries `Order`, `Customer`, `InventoryItem`, and `Product` relying implicitly on `TenantScope` without explicit `where('business_id', $business->id)` filters, which would return cross-tenant data or fail when invoked inside queued jobs or CLI commands where `TenantContext` is unset.

### 1.2 Core Multi-Tenant & Authorization Foundation
- **Tenancy:** `App\Http\Middleware\ResolveTenant` resolves the active workspace per session/route (`{business_slug}` / `session('active_business_id')`), enforces `$user->belongsToBusiness($business)`, and sets `App\Services\Tenant\TenantContext`. Tenant models use `App\Traits\BelongsToTenant` and `App\Scopes\TenantScope`.
- **Roles & Permissions:** `spatie/laravel-permission` `8.5.0` seeded by `Database\Seeders\RolesAndPermissionsSeeder` (`super_admin`, `owner`, `manager`, `cashier`, `waiter`, `kitchen`, `inventory`) with 14 granular permissions including `manage_settings`, `view_business_reports`, `manage_products`, `manage_inventory`, `manage_orders`, `use_pos`.
- **Sector Feature Flags:** `App\Services\Business\BusinessFeatureService` and `config/business_features.php` govern sector-specific visibility (`restaurant`, `cafe`, `milk_tea`, `bakery`, retail/service types) and localized terminology.
- **Navigation Shell:** `resources/views/layouts/business-nav-links.blade.php` renders grouped sidebar navigation (`Overview`, `Catalog & Menu`, `Operations`, `Management`, `Business Intelligence`, `Account & Settings`).

### 1.3 Operational & Financial Data Sources Available to AI
- **Business Config:** `businesses` (`business_type`, `default_currency` USD/KHR, `exchange_rate_khr`, `tax_name`, `tax_percent`, `tax_inclusive`, `service_charge_enabled`, `service_charge_rate`), plus `config/app.php` (`'timezone' => env('APP_TIMEZONE', 'Asia/Phnom_Penh')`).
- **Catalog:** `categories`, `products` (`name_en`, `name_km`, `description_en`, `description_km`, `product_type`, `base_price`, `cost_price`, `discount_price`, `track_inventory`, `stock_quantity`, `low_stock_threshold`), `product_variants`, `product_addons`.
- **Sales & Orders:** `orders` (authoritative financial snapshots: `subtotal`, `discount_amount`, `service_charge_amount`, `tax_amount`, `total_amount`, `total_khr`, `currency`, `status`, `payment_status`, `payment_method`, `paid_at`), `order_items`, `order_item_addons`, `pos_sessions`, `pos_transactions`.
- **Inventory & Supply Chain:** `inventory_items` (`current_stock`, `low_stock_threshold`, `unit_cost`, `unit`), `recipes`, `recipe_items`, `inventory_movements` (`type`: `in`, `out`, `adjustment`, `recipe_deduction`, `waste`, `quantity`, `balance_after`, `cost_per_unit`), `suppliers`, `purchase_orders`, `purchase_order_items`.
- **Expenses & Marketing:** `expenses`, `expense_categories`, `customers` (`total_spent`, `total_orders`), `customer_reviews`, `coupons`, `happy_hours`.

### 1.4 Platform Settings, Encryption, Queues & Plans
- **Platform Secret Storage:** `App\Models\PlatformSetting` already supports `Crypt::encryptString()` (`is_encrypted = true`), hidden `value`, and Super Admin controls under `Admin\SettingController`.
- **Audit Logging:** `App\Models\SecurityLog` captures security/audit metadata with IP and user-agent context.
- **Background Queues:** `config/queue.php` uses `database` queue connection (`jobs` table); `docker/supervisord.conf` runs `laravel-worker` (`php artisan queue:work --sleep=3 --tries=3 --max-time=3600`).
- **Subscriptions & Plans:** `plans`, `subscriptions`, and `subscription_payments` exist (`PlansSeeder` defines `free`, `basic`, `pro`, `business`). `config/subscription.php` sets `'plans_enabled' => env('SUBSCRIPTION_PLANS_ENABLED', false)` so subscription paywalls are toggled centrally without removing plan definitions.

---

## 2. What Is Missing

1. **AI Provider Abstraction & Configuration:** No LLM provider interface (`AIManager`, `AIProviderInterface`), no Google Gemini / OpenAI HTTP integration, no configurable timeouts/retries/temperature/token caps, and no encrypted Super Admin AI provider configuration UI.
2. **AI Center Navigation & Overview Hub:** No `✨ AI Center` unified sidebar section or Overview page showing monthly AI requests, remaining credits, active features, latest insights, inventory risks, and quick actions.
3. **AI Business Assistant:** No multi-turn conversational chat (`ai_conversations`, `ai_messages`), no safe read-only tool-calling orchestrator, no Khmer/English/mixed-language routing, and no natural-language sales/product/inventory/order Q&A.
4. **Live AI Menu Creator & Translator:** `AiMenuBuilderController` only displays a static hardcoded Alpine array—there is no real AI description generation, improvement, Khmer↔English translation, promotional copy, or product-level save/apply flow.
5. **AI Marketing Studio:** Zero implementation for Facebook, TikTok, Telegram, and campaign copy generation or saved content history.
6. **AI Inventory Forecast & Sales Prediction:** Zero statistical forecasting (`1d`, `7d`, `30d`), zero ingredient burn-rate / days-of-stock calculation, no reorder quantity model, no insufficient-data guard, and no forecast explanation persistence.
7. **Persistent Manageable AI Insights:** `business_ai_insights` lacks workflow status (`new`/`reviewed`/`dismissed`), action metadata, and automated/on-demand refresh.
8. **AI Subscription & Credit Metering:** `plans` lacks AI entitlements/credits; no atomic idempotent ledger or usage log prevents unlimited usage, double billing, or feature bypass.

---

## 3. Existing Database Tables Reusable for AI

| Table | How Reused |
| --- | --- |
| `businesses` | Tenant owner scope, `business_type`, `default_currency`, `exchange_rate_khr`, locale context |
| `users` | Actor attribution (`user_id`, `reviewed_by`) and Spatie role/permission checks |
| `plans` & `subscriptions` | Extended with AI feature flags and `ai_monthly_credits` quotas |
| `platform_settings` | Stores platform-level active AI provider, default model, encrypted API keys (`openai_api_key`, `gemini_api_key`), and global AI safety kill switch |
| `business_ai_insights` | Enhanced in-place (`ALTER TABLE`) to store structured, reviewable/dismissible AI business insights |
| `security_logs` | Records sensitive AI configuration updates, quota exhaustions, and cross-tenant rejections |
| `orders`, `order_items`, `products`, `categories`, `inventory_items`, `recipes`, `recipe_items`, `inventory_movements`, `purchase_orders`, `expenses` | Read-only analytical inputs for backend tool execution, statistical forecasting, and AI context builders |

---

## 4. Files Requiring Modification

- `routes/business.php` & `routes/admin.php`: Register `business.ai.*` AI Center routes (throttled appropriately) and `admin.settings.*` / `admin.plans.*` AI configuration controls, preserving existing `/ai/*` route aliases cleanly.
- `resources/views/layouts/business-nav-links.blade.php`: Add the unified **AI Center** sidebar section (`AI Overview`, `AI Business Assistant`, `AI Menu Creator`, `AI Marketing Studio`, `AI Inventory Forecast`, `AI Sales Prediction`, `AI Usage & History`, `AI Settings`).
- `resources/views/business/dashboard.blade.php` & `app/Http/Controllers/Business/DashboardController.php`: Surface verified `BusinessAiInsight` alerts cleanly on the business overview dashboard.
- `app/Services/AI/AiInsightsService.php` & `app/Models/BusinessAiInsight.php`: Add `BelongsToTenant`, explicit `business_id` scoping, persistence, and status workflow (`new`, `reviewed`, `dismissed`).
- `app/Models/Plan.php`, `app/Models/Business.php`, `app/Http/Controllers/Admin/PlanController.php`, `app/Http/Controllers/Admin/SettingController.php`, `resources/views/admin/plans/*`, `resources/views/admin/settings/index.blade.php`: Add AI plan entitlements and platform AI provider settings.
- `config/services.php`, `.env.example`, `render.yaml`, `database/seeders/PlansSeeder.php`: Register AI provider configuration keys and seed default AI plan limits.

---

## 5. Required AI Infrastructure

- **HTTP Client Architecture:** Use Laravel's built-in `Illuminate\Support\Facades\Http` (Guzzle) with configurable timeout, exponential retry on transient `429`/`5xx`, and zero external SDK package dependency bloat (keeping `composer.json` stable).
- **Provider Strategy:**
  - **Google Gemini REST API (`gemini-2.5-flash` primary):** Verified REST support for `systemInstruction`, OpenAPI `tools` (`functionDeclarations`), JSON Structured Outputs (`responseMimeType: "application/json"` + `responseSchema`), strong Khmer script handling, and exact `usageMetadata` token telemetry (`promptTokenCount`, `candidatesTokenCount`, `totalTokenCount`).
  - **OpenAI Chat Completions REST API (`gpt-4o-mini` / `gpt-4o` configurable):** Verified support for `tools` function calling, strict JSON Schema (`response_format`), and `usage` counters.
- **Queue Readiness:** Background insight/forecast generation jobs can dispatch onto existing `database` queue handled by `laravel-worker` (`docker/supervisord.conf`), while synchronous fallback supports interactive UX when queues are `sync` or empty.

---

## 6. Security Requirements Identified

1. **Zero Arbitrary AI SQL:** Models only invoke a strict allowlist of read-only backend PHP tools (`get_sales_summary`, `get_top_products`, `get_inventory_status`, `get_order_summary`, `compare_sales_periods`, `get_category_performance`, `get_peak_business_hours`), each typed, range-checked, and forced to the server-resolved `Business`.
2. **Strict Tenant Isolation:** Never accept `business_id` from request payloads or model tool arguments; resolve tenant solely via `TenantContext` + `auth` user authorization and scope every table (`ai_conversations`, `ai_messages`, `ai_usage_logs`, `ai_generated_contents`, `ai_forecasts`, `business_ai_insights`, `ai_credit_ledgers`) by `business_id`.
3. **Credential Encryption & Redaction:** Platform OpenAI/Gemini API keys stored in `PlatformSetting` using `Crypt::encryptString()`, excluded from serialization/views/JS/logs, and sanitized in provider error messages.
4. **PII Minimization:** Aggregated sales, product, category, and stock metrics are sent to external LLMs; customer names, phone numbers, emails, and delivery addresses are stripped from AI prompts and tool responses.
5. **Atomic Credit & Rate Protection:** DB-transactional idempotent `request_uuid` credit reservation prevents duplicate credit deduction or race-condition overdrafts; failed provider calls refund reserved credits automatically; rate limiting throttles chat/generation endpoints.
