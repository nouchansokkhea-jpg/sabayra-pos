# SABAYRA AI Center — Implementation Roadmap (Phases 0–11)

## Phase Breakdown & Deliverables

### Phase 0 — Repository Audit & AI Architecture (Current Phase)
- Inspect existing codebase, schema, permissions, plans, queues, and AI placeholders.
- Create feature branch `feature/sabayra-ai-center` cleanly from `main`.
- Deliver `docs/AI_PROJECT_AUDIT.md`, `docs/AI_FEATURE_GAP_ANALYSIS.md`, `docs/AI_IMPLEMENTATION_ROADMAP.md`, and `docs/AI_DATABASE_DESIGN.md`.

### Phase 1 — AI Provider Integration & Configuration
- Add `config/ai.php`, `config/services.php` defaults, and `.env.example` AI keys.
- Run additive schema migration (`ai_feature_settings`, `ai_usage_logs`, `ai_credit_ledgers`, `ai_conversations`, `ai_messages`, `ai_generated_contents`, `ai_forecasts`, plus `plans` & `business_ai_insights` upgrades).
- Implement `AIProviderInterface`, `AIManager`, `GeminiProvider`, `OpenAIProvider`, `AIResponseService`, and Super Admin global AI settings (`/admin/settings`) with encrypted API key storage in `PlatformSetting`.

### Phase 2 — AI Center Dashboard & Usage Tracking
- Implement `AIUsageService` (atomic credit reservation, duplicate `request_uuid` guard, automatic refund on failure, token/cost logging).
- Build `AiCenterController@overview`, `usageHistory()`, and `settings()`.
- Add `✨ AI Center` navigation section in `resources/views/layouts/business-nav-links.blade.php` matching SABAYRA navy/orange branding.

### Phase 3 — AI Business Assistant (Read-Only Tool-Calling Chat)
- Build `AIToolRegistry` + `AIContextService` with 7 authorized read-only tools:
  - `get_sales_summary`
  - `get_top_products`
  - `get_inventory_status`
  - `get_order_summary`
  - `compare_sales_periods`
  - `get_category_performance`
  - `get_peak_business_hours`
- Enforce strict tenant `business_id` scoping, argument validation, timezone (`Asia/Phnom_Penh` / business context), USD & KHR formatting, and PII exclusion.
- Build `AiAssistantController` and responsive chat view (`Khmer`, `English`, `Mixed` quick prompts, conversation sidebar, copy/clear/new conversation, structured metric cards).

### Phase 4 — AI Menu Creator & Khmer Translation
- Upgrade `AiMenuBuilderController` + add `AiMenuCreatorService` & `AiMenuCreatorController`:
  - Generate Description, Improve Description, Translate to Khmer, Translate to English, Generate Product Name, Short Description, Promotional Description.
  - Bilingual editor supporting saving to `ai_generated_contents` and optionally updating an existing `Product` (`description_en`, `description_km`, `name_en`, `name_km`) after explicit user approval.
  - Preserve existing guided batch menu importer cleanly.

### Phase 5 — AI Marketing Studio
- Build `AiMarketingService` & `AiMarketingController`:
  - Platforms: `facebook`, `tiktok`, `telegram`, `general`.
  - Campaign types: product launch, discount campaign, seasonal campaign, caption, short ad copy, hashtag generator.
  - Editable output, copy to clipboard, regenerate, save to history (`ai_generated_contents`).

### Phase 6 — AI Inventory Forecast
- Build statistical engine `InventoryForecastService`:
  - Calculates average daily unit sales (`order_items`) + ingredient BOM consumption (`recipes` / `recipe_items`) + recent `inventory_movements` (`waste`, `recipe_deduction`, `out`).
  - Computes estimated days of stock remaining, low-stock / out-of-stock risk level, slow-moving / overstock flags, and recommended reorder quantity (factoring open `purchase_orders` where present).
  - Generates AI operational explanation grounded exclusively in calculated numbers (`ai_forecasts`), showing insufficient-data message when order/movement history is missing.

### Phase 7 — AI Sales Prediction
- Build statistical predictor `SalesPredictionService`:
  - Horizons: `1d` (tomorrow), `7d`, `30d`.
  - Statistical methods: weighted moving average + exponential smoothing + day-of-week index when sufficient history exists (`>= 7` active days for `1d`/`7d`, `>= 14` days for `30d`).
  - Explicit insufficient-data guard (`"Not enough historical data to generate a reliable forecast yet."`)—never fabricates confidence scores or projections.
  - Chart.js visualization + confidence bounds + AI summary & limitations.

### Phase 8 — AI Business Insights
- Upgrade `AiInsightsService` (explicit `business_id` scoping + PostgreSQL/SQLite safe queries) and `BusinessAiInsight`:
  - Generate insights from actual sales deltas, slow movers, category share, low-stock triggers, co-purchase pairings (`UpsellPairingService`), and peak hours.
  - Persist insights with `new` / `reviewed` / `dismissed` statuses; expose action buttons on AI Overview and a verified Business Insights panel on `business/dashboard.blade.php`.

### Phase 9 — Subscription Limits & Credit Management
- Add AI entitlement fields (`has_ai_assistant`, `has_ai_menu_creator`, `has_ai_marketing`, `has_ai_forecasting`, `ai_monthly_credits`) to `plans` and `Admin\PlanController` + `PlansSeeder`.
- Build `AIEntitlementService` supporting both paid plan tiers (when `SUBSCRIPTION_PLANS_ENABLED=true`) and configurable default trial/free allowances (when `false`), enforced via middleware/service checks with clear UI warnings.

### Phase 10 — Security Hardening & Automated Testing
- Add strict Form Requests, policies/permission checks (`manage_settings` / `view_business_reports` / `manage_products` / `manage_inventory`), route rate-limiting, prompt injection guardrails, and comprehensive `tests/Feature/SabayraAiCenterTest.php` using `Http::fake()` deterministic provider mocks.
- Run `npm run build`, `route:cache`, `view:cache`, migrations against PostgreSQL, and full `php artisan test`.

### Phase 11 — Documentation & Production Readiness
- Complete all remaining docs in Section 18 (`docs/AI_ARCHITECTURE.md`, `AI_PROVIDER_SETUP.md`, `AI_BUSINESS_ASSISTANT.md`, `AI_MENU_CREATOR.md`, `AI_MARKETING_STUDIO.md`, `AI_INVENTORY_FORECAST.md`, `AI_SALES_PREDICTION.md`, `AI_SUBSCRIPTION_LIMITS.md`, `AI_SECURITY.md`, `AI_TESTING_REPORT.md`) with verified commands and test metrics.
