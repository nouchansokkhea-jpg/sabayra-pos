# SABAYRA AI Center — System Architecture

## 1. Architectural Principles

1. **Provider-Independent AI Abstraction**: Business controllers and domain services depend only on `App\Services\AI\AIManager` and the `App\Services\AI\Data\AIRequest` / `AIResponse` DTOs rather than vendor SDKs.
2. **Laravel Stack Consistency**: Built natively on Laravel 13, PHP 8.3+, Blade, Tailwind CSS, Alpine.js, Chart.js, and PostgreSQL without introducing Node SSR frameworks or external paid AI packages.
3. **Strict Tenant Isolation**: Every AI Center table uses `business_id` foreign keys and `App\Traits\BelongsToTenant` global scopes, paired with explicit `where('business_id', $business->id)` filtering and role checks via `App\Services\AI\AiAccessService`.
4. **Grounded Truthfulness**: Numeric analytics and forecasts are computed deterministically in PHP against verified database records. When historical data is insufficient, statistical forecasts mark `status = 'insufficient_data'` without charging AI credits or synthesizing estimates.
5. **Server-Side Secret Custody**: Provider credentials live in `config/services.php` (from `.env`) or AES-encrypted `platform_settings` rows and are never passed to frontend Blade markup or API error payloads.

## 2. Core Components (`app/Services/AI/`)

- `AIManager.php`: Resolves platform settings, provider selection (`gemini` or `openai`), model bounds, token caps, credit reservation/finalization/refunds, and connectivity tests.
- `AIProviderInterface.php`: Contract implemented by `GeminiProvider` and `OpenAIProvider`.
- `GeminiProvider.php`: Calls Google Gemini `v1beta/models/{model}:generateContent` with `x-goog-api-key`, `systemInstruction`, uppercase OpenAPI tool schemas (`functionDeclarations`), and `responseSchema` JSON mode.
- `OpenAIProvider.php`: Calls OpenAI `v1/chat/completions` with `tool_calls` and strict `json_schema` structured outputs.
- `AIUsageService.php`: Handles subscription plan entitlement checks (`subscription.plans_enabled`), atomic `DB::transaction` + `lockForUpdate` credit reservations with `request_uuid` idempotency, token cost estimation, and automatic refunds on failures.
- `AIContextService.php`: Detects Khmer (`km`), English (`en`), or `mixed` scripts and builds compact PII-free store context.
- `AIResponseService.php`: Validates/extracts JSON payloads and redacts API keys/Bearer tokens from upstream errors.
- `AiAccessService.php`: Enforces role and Spatie permission access across AI Center areas (`overview`, `assistant`, `menu_creator`, `marketing_studio`, `inventory_forecast`, `sales_prediction`, `usage`, `settings`, `insights`).
- Domain Modules: `BusinessMetricsQueryService`, `BusinessAssistantService`, `MenuCreatorService`, `MarketingStudioService`, `InventoryForecastService`, `SalesPredictionService`, `AiInsightsService`.
