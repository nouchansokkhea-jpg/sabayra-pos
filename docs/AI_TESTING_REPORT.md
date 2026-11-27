# SABAYRA AI Center — Testing & Verification Report

## 1. Automated Verification Summary

- **Full PHPUnit Suite (`php artisan test`)**:
  - Result: **Passed** (`170` tests passed, `1,142` assertions, `0` failures, `0` skipped, duration `31.74s` on SQLite `:memory:` with `Http::fake()` provider mocks).
- **Local PostgreSQL Migration (`php artisan migrate --force`)**:
  - Result: `2026_11_27_000001_create_sabayra_ai_center_tables` completed cleanly (`480.02ms DONE`).
- **Frontend Asset Build (`npm run build`)**:
  - Result: Vite production bundle built cleanly in `6.83s` (`public/build/manifest.json`, `app-OyzuFtH-.css`, `app-L7ma5G9v.js` including bundled Chart.js).
- **Blade & Route Verification (`php artisan route:list --path=ai`, `php artisan view:cache`)**:
  - Result: `35` matching routes registered and all Blade templates compiled cleanly with zero syntax errors.

## 2. AI Center Feature Test Suites

1. [SabayraAiProviderTest.php](file:///e:/menuhub/tests/Feature/SabayraAiProviderTest.php)
   - Super Admin encrypted Gemini & OpenAI API key persistence (plaintext never rendered in DOM).
   - Super Admin live provider connection tests (`x-goog-api-key` header and Bearer token verification).
   - Structured JSON completion, multi-turn tool calling (`Gemini` and `OpenAI`) billed at 1 credit, automatic credit refund + key redaction on HTTP `401` / timeout / malformed JSON, duplicate `request_uuid` guard (`409`), insufficient credits (`402`), plan entitlement checks, and Plan AI entitlement CRUD updates.
2. [SabayraAiCenterPhase2Test.php](file:///e:/menuhub/tests/Feature/SabayraAiCenterPhase2Test.php)
   - AI Overview renders genuine zeroed telemetry and live low-stock inventory warnings.
   - Usage & History ledger view and Business AI Settings persistence (`preferred_language`, `preferred_tone`, `temperature`, feature switches).
   - Role permissions (`inventory` allowed on Inventory Forecast, `403` on Settings & Usage) and cross-tenant isolation.
3. [SabayraAiAssistantTest.php](file:///e:/menuhub/tests/Feature/SabayraAiAssistantTest.php)
   - All 7 read-only tools (`get_sales_summary`, `get_top_selling_products`, `get_low_selling_products`, `get_low_stock_products`, `get_expense_and_profit_summary`, `get_peak_hours`, `get_payment_method_breakdown`) compute accurate tenant-scoped numbers and reject unknown tool names.
   - Khmer prompt language detection (`km`), tool loop persistence into `ai_conversations` / `ai_messages` (`data_sources_json`), single-credit charge, and unauthorized staff `403` protection.
4. [SabayraAiMenuCreatorTest.php](file:///e:/menuhub/tests/Feature/SabayraAiMenuCreatorTest.php)
   - Generates bilingual Khmer & English drafts without mutating `products` automatically; explicit confirmation applies chosen copy and transitions draft status to `applied`.
5. [SabayraAiMarketingStudioTest.php](file:///e:/menuhub/tests/Feature/SabayraAiMarketingStudioTest.php)
   - Generates localized Cambodian Facebook/TikTok/Telegram campaign copy saved in `ai_generated_contents` and blocks cross-tenant draft deletion (`403`).
6. [SabayraAiForecastingTest.php](file:///e:/menuhub/tests/Feature/SabayraAiForecastingTest.php)
   - Inventory Forecast marks `insufficient_data` with zero credit deduction when no stock consumption history exists; computes weighted moving-average runway, risk levels, and reorder quantities cleanly.
   - Sales Prediction blocks insufficient order history honestly (`< 5` orders) and computes `1d`, `7d`, and `30d` projections + category leaders when continuous order series exist, while restricting `inventory` staff (`403`).
7. [SabayraAiInsightsAndSecurityTest.php](file:///e:/menuhub/tests/Feature/SabayraAiInsightsAndSecurityTest.php)
   - Verifies tenant isolation fix in `AiInsightsService::generateWeeklySummary()`, persistent `BusinessAiInsight` generation & review/apply workflow, real Dashboard AI Center widget rendering, subscription plan gating across HTTP endpoints (`Free` vs `Pro`), and waiter role restrictions (`403`).

## 3. Notes on Live Provider Testing

All automated tests used Laravel `Http::fake()` mocks against Google Gemini (`v1beta`) and OpenAI (`v1/chat/completions`) response payloads so test runs remain deterministic and cost-free. Live API verification can be performed safely from **Super Admin -> Platform Settings -> Test AI Connection** after saving a production/sandbox API key.
