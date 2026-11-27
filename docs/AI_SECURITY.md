# SABAYRA AI Center — Security & Privacy Architecture

## 1. Multi-Tenant & Role-Based Isolation

- **Global Scopes & Explicit Filtering**: All AI models (`AiFeatureSetting`, `AiCreditLedger`, `AiUsageLog`, `AiConversation`, `AiMessage`, `AiGeneratedContent`, `AiForecast`, `BusinessAiInsight`) apply `App\Traits\BelongsToTenant` and explicit `where('business_id', $business->id)` clauses across controllers and services.
- **Cross-Tenant Ownership Checks**: Mutating or deleting conversations, generated menu/marketing content, or business insights explicitly verifies `business_id === $business->id` (and `user_id` for private assistant threads) and returns `403` on mismatch.
- **Role & Permission Matrix (`AiAccessService`)**:
  - `owner` / `manager` / `super_admin`: Full AI Center access (settings mutation additionally guarded by `canManageBusinessSettings`).
  - `inventory`: Permitted on Overview & Inventory Forecast; blocked (`403`) from Financial Sales Prediction, Usage & History, and AI Settings.
  - `cashier` / `waiter` / `kitchen`: Restricted (`403`) unless granted explicit business permissions (`view_business_reports`, `manage_products`, `manage_inventory`, `manage_settings`).

## 2. Credential Protection & Data Privacy

- **Server-Side Encryption**: API keys (`ai.gemini_api_key`, `ai.openai_api_key`) are stored encrypted (`is_encrypted = true`) in `platform_settings`. Super Admin settings DOM only renders boolean `Configured` / `Missing` indicators — plaintext keys are never rendered in Blade views, JSON responses, or `SecurityLog` metadata.
- **Header-Based Auth & Error Redaction**: Gemini calls pass `x-goog-api-key` in HTTP headers (never in query URLs). `AIResponseService::sanitizeErrorMessage()` strips matching secrets, `AIza...`, `sk-...`, and `Bearer ...` patterns before persisting `ai_usage_logs.error_message` or returning errors.
- **No Arbitrary SQL & Zero Customer PII**: The Business Assistant only dispatches 7 allowlisted parameterized PHP query methods and never exposes customer emails, phone numbers, addresses, passwords, or payment proofs to prompts.
