# SABAYRA — Category-Based Business Experience Architecture
**Version:** 2.0.0  
**Date:** November 28, 2026  

---

## 1. Core Design Philosophy
**ONE PLATFORM — MULTIPLE BUSINESS EXPERIENCES.**

Instead of duplicating controllers, views, or tenant isolation logic across 12 business types, SABAYRA separates **core multi-tenant commerce primitives** (Products, Variants, Orders, Tax/Service Charge pricing, Payments, Staff, Customers, Reservations, Inventory) from **category presentation & workflow policies** defined centrally in [`config/business_features.php`](../config/business_features.php) and resolved by [`CategoryExperienceService`](../app/Services/Business/CategoryExperienceService.php).

---

## 2. Architecture Components

### A. Central Registry & Service Layer
- [`config/business_features.php`](../config/business_features.php): Declares all 12 Cambodian sectors (`restaurant`, `cafe`, `milk_tea`, `bakery`, `fast_food`, `supermarket`, `mini_mart`, `retail_shop`, `clothing`, `beauty`, `services`, `other`), bilingual names (`name_en`, `name_km`) & descriptions, 26 feature flags, localized terminology, dashboard KPI/panel configuration, POS UI mode, public storefront mode, and analytics/report focus.
- [`CategoryExperienceService`](../app/Services/Business/CategoryExperienceService.php): Resolves base category configuration merged cleanly with optional per-tenant overrides stored in `business_settings` (`category.custom_modules`) and optional subscription plan checks (`subscription.plans_enabled`).
- [`BusinessFeatureService`](../app/Services/Business/BusinessFeatureService.php): Preserved static helper facade delegating cleanly to `CategoryExperienceService`.

### B. Onboarding & Safe Category Switching
- [`BusinessWorkspaceController`](../app/Http/Controllers/Business/BusinessWorkspaceController.php) + [`onboarding/create.blade.php`](../resources/views/business/onboarding/create.blade.php): Interactive 12-category onboarding picker showing live preview of active modules, KPIs, POS style, and storefront mode, plus optional custom module selection for `other` businesses.
- [`SettingController::updateCategoryExperience()`](../app/Http/Controllers/Business/SettingController.php) (`PUT /app/settings/experience`): Safe guided category switcher that shows newly activated and hidden modules beforehand without deleting any products, categories, customers, or historical orders/invoices.

### C. Smart Dashboards & Dynamic Navigation
- [`DashboardExperienceService`](../app/Services/Business/DashboardExperienceService.php): Computes genuine, strictly tenant-scoped KPIs (e.g., Active Tables, Avg Prep Time, Scheduled Pre-Orders, Expiring Batches in 14d, Inventory Cost Valuation, Top Selling Size/Color, Variant Units, Today's Bookings, Completed Services) and specialized panels (`expiring_soon`, `fashion_variant_breakdown`, `upcoming_appointments_panel`, `staff_performance_panel`, `peak_hours_compact`, `top_toppings`) alongside widget visibility preferences (`POST /app/dashboard/widgets`).
- [`layouts/business-nav-links.blade.php`](../resources/views/layouts/business-nav-links.blade.php): Dynamically adapts section titles, item labels, Bulk CSV Import, Service Appointments vs Table Reservations, Recipes BOM, Suppliers & POs, Kitchen KDS, Tables & QR Standees, Delivery Zones, and Analytics links.

### D. Catalog, POS, Public Storefronts & Service Bookings
- [`ProductController`](../app/Http/Controllers/Business/ProductController.php): Exposes retail `barcode`, `brand`, `batch_number`, `expiry_date`, service `duration_minutes`, and variant matrix attributes (`size`, `color`, `shade`, `barcode`, `stock_quantity`) tailored to the workspace category.
- [`PosController`](../app/Http/Controllers/Business/PosController.php):
  - `GET /app/pos/lookup-barcode`: Tenant-isolated instant product/variant lookup by barcode or SKU.
  - Category-aware Touch POS supports Drink Sugar/Ice/Temperature presets (`cafe`, `milk_tea`), Barcode scanner bar (`supermarket`, `mini_mart`, `retail_shop`, `clothing`, `beauty`), Pre-order pickup timestamp (`bakery`), Fashion size/color stock decrements (`clothing`), and Appointment/Staff assignment (`services`).
- [`PublicMenuController`](../app/Http/Controllers/Public/PublicMenuController.php), [`OrderCheckoutController`](../app/Http/Controllers/Public/OrderCheckoutController.php), & [`PublicBookingController`](../app/Http/Controllers/Public/PublicBookingController.php):
  - `menu` mode: Digital QR Menus with optional drink customizer or pre-order pickup.
  - `catalog` mode: Searchable retail storefront with brand/in-stock filters and pickup/delivery enforcement.
  - `booking` mode: Service Catalog + real-time staff/time-slot scheduler (`GET /{slug}/booking-slots`, `POST /{slug}/book`, `GET /{slug}/booking/{booking_code}`) with transactional double-booking prevention.

### E. Sector Analytics, Reports & AI Grounding
- [`AnalyticsController`](../app/Http/Controllers/Business/AnalyticsController.php) & [`ReportController`](../app/Http/Controllers/Business/ReportController.php): Stock cost/retail valuation & 30-day expiry alerts (`retail`/`beauty`/`bakery`), Fashion Sales by Size & Color (`fashion`), Booking Completion & Staff Performance (`services`), and Hospitality/Channel revenue.
- [`AIContextService`](../app/Services/AI/AIContextService.php) & [`BusinessAssistantService`](../app/Services/AI/BusinessAssistantService.php): Injects verified category metadata, storefront/POS mode, and sector metrics into SABAYRA AI Assistant prompts.

---

## 3. Setup & Deployment Notes
1. Run database migrations (`php artisan migrate --force`) to apply additive category columns safely.
2. Build production frontend assets (`npm run build`).
3. Ensure Render / server deployment executes migrations idempotently (`docker/entrypoint.sh` already runs `php artisan migrate --force`).
