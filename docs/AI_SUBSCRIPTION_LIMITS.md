# SABAYRA AI Center — Subscription Plans & Monthly Credit Enforcement

## 1. Dual Launch & Enforced Modes (`config/subscription.php`)

- **Launch Mode (`SUBSCRIPTION_PLANS_ENABLED=false`)**:
  - No paywall blocks store AI modules; all enabled business AI features remain accessible with monthly credits defaulting to `PlatformSetting('ai.default_monthly_credits', config('ai.default_monthly_credits', 100))`.
- **Plan-Enforced Mode (`SUBSCRIPTION_PLANS_ENABLED=true`)**:
  - `AIUsageService::resolveEntitlements(Business)` resolves the active `Subscription -> Plan` (falling back to `free` if none attached).
  - Features map to `plans` entitlement columns:
    - `has_ai_assistant` -> Business Assistant & Insights
    - `has_ai_menu_creator` -> AI Menu Creator
    - `has_ai_marketing` -> AI Marketing Studio
    - `has_ai_forecasting` -> AI Inventory Forecast & AI Sales Prediction
  - Token output per request is capped cleanly by `plans.ai_max_tokens_per_request`.
  - Default plans seeded in `PlansSeeder`:
    - `free`: 30 credits/mo (Assistant + Menu Creator only)
    - `basic`: 150 credits/mo (+ Marketing Studio)
    - `pro`: 500 credits/mo (All AI features)
    - `business`: 2,000 credits/mo (All AI features)

## 2. Atomic Idempotent Credit Ledger

- Monthly balances live in `ai_credit_ledgers` (`allocated_credits`, `bonus_credits`, `used_credits`, `reserved_credits` per `business_id` and `YYYY-MM`).
- `AIUsageService::reserveCredit()` runs inside `DB::transaction()` with `lockForUpdate()`, checks duplicate `request_uuid` (`409 duplicate_request`), verifies sufficient remaining credits (`402 insufficient_credits`), reserves credits before the HTTP call, commits tokens/USD cost in `finalizeUsage()`, or returns reserved credits on failure via `refundReservation()`.
- Free statistical refreshes and `insufficient_data` forecast states never deduct credits.
