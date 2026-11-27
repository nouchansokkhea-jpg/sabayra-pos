# Feature Four: SABAYRA AI Inventory Forecast

## 1. Statistical Engine (`InventoryForecastService`)

Accessible at **AI Center -> AI Inventory Forecast** (`GET /app/ai/inventory-forecast`, route `business.ai.inventory-forecast`), inventory runway projections are computed deterministically in PHP before any AI commentary runs:

- **Tracked Sources**:
  - Active `InventoryItem` records via `InventoryMovement` consumption (`type` in `out`, `recipe_deduction`, `waste`) over a 30-day lookback window.
  - Inventory-tracked `Product` items (`track_inventory = true`) via non-cancelled `OrderItem` sales over the same lookback window.
- **Weighted Velocity Formula**:
  - Combines recent 7-day daily usage (weight `0.6`) and active lookback window daily usage (weight `0.4`) divided by actual observed active days so brand-new stores are not under-divided.
- **Risk Classification & Reorder Target**:
  - `critical`: stock <= `0`, estimated days remaining <= `3.0`, or stock <= `low_stock_threshold`.
  - `warning`: estimated days remaining <= `7.0`.
  - `healthy`: > 7 days estimated cover.
  - Suggested reorder targets 14 days of projected consumption above current stock and estimates cost where `unit_cost` / `cost_price` is set.

## 2. Honesty & AI Explanation Layer

- When zero consumption movements/orders exist, status is saved as `insufficient_data` with confidence `insufficient` and **zero AI credits deducted**.
- Clicking **Explain & Prioritize (2 Credits)** sends only precomputed statistical JSON to `AIManager::complete()`, instructing the LLM to explain reorder priorities without altering or inventing quantities.
