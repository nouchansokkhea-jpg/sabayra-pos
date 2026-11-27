# Feature Five: SABAYRA AI Sales Prediction

## 1. Statistical Engine (`SalesPredictionService`)

Accessible at **AI Center -> AI Sales Prediction** (`GET /app/ai/sales-prediction`, route `business.ai.sales-prediction`), projections for `1d`, `7d`, and `30d` horizons are computed deterministically in PHP from non-cancelled `Order` records in the store's `Asia/Phnom_Penh` timezone:

- **Continuous Daily Time Series**: Zero-fills only days after the business's first recorded order inside a 60-day lookback window.
- **Data Sufficiency Guards (`config/ai.php`)**:
  - Requires at least `min_orders_required = 5` and `3+` active historical days. Below threshold, stores `status = 'insufficient_data'`, `confidence_level = 'insufficient'`, an explicit explanation citing exact observed counts, and charges **zero AI credits**.
  - Extending a `30d` horizon with `< 14` days of order history explicitly marks confidence `low` with a visible limitation banner.
- **Rolling Baseline & Trend Clamping**:
  - Combines 65% recent 7-day daily average and 35% active-history average, with a damped momentum multiplier (`0.75x` to `1.25x`), plus ±15% conservative interval bounds, USD & KHR totals, weekday seasonality factors, and top category leaders.
- **Visualization & AI Explanation**:
  - Renders observed daily revenue history with Chart.js.
  - Optional **Explain Drivers (2 Credits)** summarizes drivers, strongest weekdays, and staffing preparation strictly from precomputed stats.
