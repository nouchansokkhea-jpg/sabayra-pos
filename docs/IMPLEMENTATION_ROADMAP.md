# SABAYRA SaaS Platform — Implementation Roadmap
**Version:** 1.0.0  
**Date:** November 23, 2026  

---

## Phased Execution Strategy

### Phase 0: Repository Audit & Architectural Baselining (Completed)
- Full inventory of all existing controllers, models, routes, and views.
- Gap analysis comparing existing features against Modules A through S.
- Verification of database connectivity, multi-tenant isolation, and test suite.

---

### Phase 1: Core Dashboard & Navigation Reorganization
- **Scope:**
  - Standardize 9-group navigation hierarchy:
    1. Overview
    2. Catalog & Menu
    3. Operations
    4. Management
    5. Customer & Marketing
    6. Business Intelligence
    7. QR & Advanced Features
    8. Learning & Support
    9. Account
  - Implement `BusinessFeatureService` and `config/business_features.php` to govern dynamic feature visibility by sector.
  - Upgrade `DashboardController` and `business/dashboard.blade.php`:
    - Public Storefront URL card with copy link, QR shortcut, and storefront button.
    - Today vs. Yesterday comparisons (Revenue, Orders) with % change.
    - Quick actions grid (Add product, Open POS, KDS, Live orders).
    - Top sellers list.
    - Real-data security health widget.

---

### Phase 2: Operations & Kitchen Workflow
- **Scope:**
  - Create dedicated Live Orders Kanban workspace (`/app/orders/live`) with sound alerts and drawer details.
  - Upgrade Kitchen Display System (`/app/kitchen`) with elapsed time counters, item modifiers breakdown, and station filtering.
  - Create interactive Order Flow Guide (`/app/guides/order-flow`).
  - Standardize 80mm & 58mm thermal receipt printing views.

---

### Phase 3: Reservations, Reviews & Marketing Engine
- **Scope:**
  - Reservations management (`/app/reservations`) with conflict prevention.
  - Customer Waitlist management (`/app/waitlist`).
  - Customer Reviews & Ratings moderation (`/app/reviews`).
  - Coupons & Promo Discounts (`/app/coupons`) with cart checkout validation.
  - Happy Hours automated scheduling (`/app/happy-hours`) with business-local timezone support.
  - Delivery Zones (`/app/delivery-zones`) with minimum spend and fee calculation.

---

### Phase 4: Business Intelligence & AI Services Layer
- **Scope:**
  - Advanced Analytics workspace (`/app/analytics`):
    - Orders by hour of day & revenue by day of week.
    - Revenue by category chart.
    - Peak business hours heatmap.
  - Four dedicated AI services:
    1. `AiInsightsService` (Weekly sales, velocity, actionable recommendations)
    2. `MenuAuditService` (Audits missing photos, weak descriptions, $0 prices, pricing outliers)
    3. `SentimentRadarService` (Analyzes authentic customer comments for compliments and complaints)
    4. `UpsellPairingService` (Order basket co-purchase matrix)

---

### Phase 5: Hardware & Product Bulk Import
- **Scope:**
  - Printer configuration (`/app/printers`) for network IP, USB, and Bluetooth dockets.
  - 5-step bulk product CSV / Excel import wizard (`/app/products/import`).

---

### Phase 6: Account Security & Settings Organization
- **Scope:**
  - Two-Factor Authentication (2FA) toggle with TOTP / secret management.
  - Security logs and login audit tracking (`/app/security`).
  - Structured tabbed business settings.

---

### Phase 7: Automated Verification & Documentation
- **Scope:**
  - Comprehensive feature tests in `tests/Feature/MerchantModulesAndNavigationTest.php`.
  - Validate 100% green pass on `php artisan test`.
  - Update all project documentation in `docs/`.
