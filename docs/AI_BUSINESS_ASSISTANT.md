# Feature One: SABAYRA AI Business Assistant

## 1. Purpose & Capabilities

Accessible via **AI Center -> AI Business Assistant** (`GET /app/ai/assistant`, route `business.ai.assistant`), staff and owners can ask operational questions in **Khmer (`ភាសាខ្មែរ`)**, **English**, or **mixed Khmer-English**.

Every answer is grounded strictly in real business records using allowlisted read-only query tools in `App\Services\AI\BusinessMetricsQueryService`.

## 2. Allowlisted Read-Only Tools (No Arbitrary AI SQL)

All 7 tools scope explicitly by `$business->id` and never query customer emails, phone numbers, addresses, passwords, payment proof files, or API keys:

1. `get_sales_summary(days)`: Paid revenue (USD & KHR), order counts, average order value, discounts, tax, service charges, and prior-period comparison.
2. `get_top_selling_products(days, limit)`: Best sellers ranked by sold quantity and revenue, with estimated gross margin where `cost_price` is entered.
3. `get_low_selling_products(days, limit)`: Active menu items with the lowest or zero order quantities.
4. `get_low_stock_products(limit)`: Tracked products (`track_inventory = true`) and active raw ingredients (`InventoryItem`) at or below reorder threshold.
5. `get_expense_and_profit_summary(days)`: Paid revenue minus recorded expenses (`Expense`) and product COGS estimate.
6. `get_peak_hours(days)`: Busiest hours of the day in `Asia/Phnom_Penh` timezone and weekday distribution.
7. `get_payment_method_breakdown(days)`: Order counts, USD totals, and percentage shares across Cash, Bakong KHQR, ABA PayWay, Bank Transfer, QR, and COD.

## 3. Persistence & Credit Metering

- Conversations and chronological turns persist in `ai_conversations` and `ai_messages` scoped to `(business_id, user_id)`.
- Assistant replies record `data_sources_json`, `tool_calls_json`, `tool_results_json`, `provider`, `model`, and `usage_log_id`.
- A multi-round tool-calling turn via `AIManager::completeWithTools()` consumes **1 credit** per user question.
