# SABAYRA SaaS Platform — Changelog
**Format:** Keep a Changelog  
**Versioning:** Semantic Versioning (`MAJOR.MINOR.PATCH`)  

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
