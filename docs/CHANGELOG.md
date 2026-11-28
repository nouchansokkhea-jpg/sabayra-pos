# SABAYRA SaaS Platform — Changelog
**Format:** Keep a Changelog  
**Versioning:** Semantic Versioning (`MAJOR.MINOR.PATCH`)  

---

## [2.1.0] — 2026-11-28 (Category-Based Business Experience & Smart Industry Dashboards)

### Added
- Centralized 12-sector registry in `config/business_features.php` + `App\Services\Business\CategoryExperienceService` (`Feature/CategoryExperienceArchitectureTest`).
- Additive idempotent migration `2026_11_28_000001_add_category_experience_columns.php` extending `products`, `product_variants`, `orders`, `order_items`, and `reservations` (for service appointments & booking codes).
- Interactive 12-category workspace preview on onboarding (`/app/create-business`) and guided zero-data-loss Category & Custom Module settings (`PUT /app/settings/experience`).
- Category-driven sidebar navigation (`layouts/business-nav-links.blade.php`) and `DashboardExperienceService` with personalized panel visibility (`POST /app/dashboard/widgets`).
- Category-aware product/service forms & Touch POS upgrades: barcode/SKU instant lookup (`GET /app/pos/lookup-barcode`), café/milk-tea sugar & ice presets, fashion size/color variant stock tracking, bakery pre-orders, and service staff/booking checkout.
- Category-adapted public storefronts (`menu` vs `catalog` vs `booking` mode), online service slot scheduler & booking status pass (`/{slug}/booking-slots`, `/{slug}/book`, `/{slug}/booking/{booking_code}`), and category-filtered QR Studio.
- Industry-specific reports, commercial analytics, and category-grounded `AIContextService` prompts.
- Verified with 192 PHPUnit tests (1,508 assertions, 0 failures) and production `vite build`.

---

## [Unreleased] — 2026-11-23

### Added
- Created dedicated feature branch `feature/sabayra-complete-saas-upgrade`.
- Comprehensive technical audit of existing repository architecture, database schemas, tenant isolation, and security.
- Created `docs/PROJECT_AUDIT.md` documenting current technical stack and architecture.
- Created `docs/FEATURE_GAP_ANALYSIS.md` mapping Modules A through S to the codebase.
- Created `docs/IMPLEMENTATION_ROADMAP.md` outlining phased upgrades from Phase 1 through Phase 8.
- Created `docs/DATABASE_DESIGN.md` establishing schema models for reservations, reviews, marketing, printers, and delivery zones.
- Created `docs/FEATURE_MATRIX.md` defining 12 Cambodian business sectors and feature entitlements.
- Created `docs/TESTING_REPORT.md` baselining 79 passing tests and establishing QA coverage targets.

---

## [1.0.0] — 2026-11-22

### Added
- Core Multi-Tenant Architecture with `ResolveTenant` middleware and `TenantContext`.
- Super Admin platform portal at `/admin` with merchant management, pricing plans, and system activity logs.
- Merchant workspace at `/app` with catalog, products, branches, tables, staff roles, and settings.
- Kitchen Display System (KDS) at `/app/kitchen`.
- Touch POS Terminal at `/app/pos` with cash drawer sessions and Bakong KHQR dynamic payment generation.
- Inventory tracking and recipe ingredient automatic deduction upon order completion.
- Public customer mobile storefront at `/{business_slug}` with English/Khmer switching and dual USD/KHR display.
- PostgreSQL and Supabase Cloud integration.
