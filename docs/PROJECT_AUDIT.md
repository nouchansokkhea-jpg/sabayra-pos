# SABAYRA SaaS Platform — Comprehensive Project Audit
**Version:** 1.0.0  
**Date:** November 23, 2026  
**Auditor:** Senior Software Architect & Laravel Platform Engineer  
**Branch:** `feature/sabayra-complete-saas-upgrade`  

---

## 1. Executive Summary
This document provides a comprehensive technical audit of the **SABAYRA** multi-tenant SaaS POS and Business Management platform. SABAYRA is built to empower Cambodian and international businesses—including restaurants, cafes, supermarkets, retail, fashion, and service shops—with a centralized, scalable cloud platform.

The audit evaluates the existing codebase, architecture, database schemas, security, multi-tenancy isolation, test suite, and operational workflows.

---

## 2. Technology Stack Verification

### Backend
- **PHP Version:** PHP 8.3+ (Runtime tested on PHP 8.5)
- **Framework:** Laravel 13.x (latest 13.43.0)
- **Database Engine:** PostgreSQL (PDO pgsql) with Supabase Cloud connection & local PostgreSQL (`menuhub_db`)
- **Key Composer Dependencies:**
  - `spatie/laravel-permission`: Role-based access control (Super Admin, Business Owner, Manager, Cashier, Kitchen, Waiter)
  - `spatie/laravel-activitylog`: Audit logging for sensitive administrative and tenant operations
  - `simplesoftwareio/simple-qrcode`: Server-side QR code generator (PNG / SVG)
  - `laravel/boost`: Developer and environment assistance toolkit

### Frontend
- **Templating:** Laravel Blade with modern reusable components
- **Styling:** Tailwind CSS 3.4 / 4.x with `@tailwindcss/forms`
- **Interactivity:** Alpine.js 3.x for reactive client-side states, modals, and tab switching
- **Visualizations:** Chart.js 4.x for revenue trends, order heatmaps, and analytics charts
- **Bundler:** Vite 6 / 8 with `laravel-vite-plugin`

---

## 3. Existing Architecture Overview

SABAYRA is divided into three isolated application layers:

### A. Platform Super Admin Layer (`/admin`)
- **Route Prefix:** `/admin`
- **Route Name Prefix:** `admin.*`
- **Middleware:** `['web', 'auth', 'super_admin']`
- **Controller Namespace:** `App\Http\Controllers\Admin`
- **Capabilities:**
  - Platform Dashboard & Global Metrics (MRR, Total Merchants, Active Outlets)
  - Business Management (Status toggling, workspace inspection, deletion)
  - User Directory & RBAC management
  - Subscription Plans Management (`plans` resource)
  - Subscription Payments & Transaction Audits
  - System-wide Activity Logs (`spatie/laravel-activitylog`)
  - Platform Settings & Sector Presets

### B. Merchant Tenant Workspace (`/app`)
- **Route Prefix:** `/app`
- **Route Name Prefix:** `business.*`
- **Middleware:** `['web', 'auth', 'tenant']`
- **Controller Namespace:** `App\Http\Controllers\Business`
- **Tenant Context Resolution:** Managed by `App\Http\Middleware\ResolveTenant` and stored in static container `App\Services\Tenant\TenantContext`.
- **Capabilities:**
  - Business Dashboard & Key Metrics
  - Category & Product CRUD with variant and add-on support
  - Branch Management (multi-outlet isolation)
  - Staff & Roles Management
  - Table & QR Standee Generation
  - Order Management & Status Transition Tracking
  - Kitchen Display System (KDS) with sound alerts and Kanban
  - Touch POS Terminal with cash sessions and Bakong KHQR integration
  - Inventory & Recipe ingredient deduction
  - Financial reporting & CSV exports

### C. Public Customer Storefront (`/{business_slug}`)
- **Route Prefix:** `/{business_slug}`
- **Route Name Prefix:** `public.*`
- **Middleware:** `['web', 'tenant']`
- **Controller Namespace:** `App\Http\Controllers\Public`
- **Capabilities:**
  - Frictionless mobile-first web menu (No registration or app download needed)
  - Bilingual English and Khmer interface with real-time translation switch
  - Dual currency display (USD `$` and Khmer Riel `៛` with configurable exchange rate)
  - Contactless Dine-In ordering (via table QR parameter `?table=01`)
  - Customer checkout supporting Cash and Bakong KHQR dynamic codes
  - Live order tracking page (`/{business_slug}/order/{order_number}`)

---

## 4. Existing Database Structure & Relationships

### Core Entities:
1. **`users`**: Platform accounts with `is_super_admin`, authentication credentials, preferred locale.
2. **`businesses`**: Core tenant entity. Has `owner_id`, `category`, `currency`, `exchange_rate_khr`, `theme_settings`.
3. **`business_members`**: Membership pivot mapping `user_id` to `business_id` with `role` (`owner`, `manager`, `cashier`, `kitchen`, `waiter`, `staff`) and `branch_id`.
4. **`business_branches`**: Multi-outlet support under a parent business.
5. **`plans` & `subscriptions`**: SaaS subscription management with quotas and billing cycles.
6. **`categories` & `products`**: Hierarchical catalog with `product_variants`, `product_addons`, and `product_images`.
7. **`restaurant_tables` & `qr_codes`**: Table mapping, scan tracking, and entry points.
8. **`customers`**: Tenant-scoped customer directory.
9. **`orders` & `order_items`**: Complete order transaction lifecycle with `order_item_addons` and `order_status_histories`.
10. **`pos_sessions` & `pos_transactions`**: Cashier shift reconciliation and cash drawer audit.
11. **`inventory_items` & `recipes`**: Stock tracking with automatic ingredient deduction upon order completion.

---

## 5. Multi-Tenant Isolation Analysis

- **Isolation Strategy:** Single database with shared tables and mandatory `business_id` foreign keys (Logical Multi-Tenancy).
- **Enforcement Mechanisms:**
  1. `ResolveTenant` middleware ensures an authenticated user belongs to the requested business (`$user->belongsToBusiness($business)`).
  2. `TenantContext::set($business)` provides static thread-safe access to the active tenant.
  3. Controllers scope all database queries explicitly by `where('business_id', $business->id)`.
- **Assessment:** Solid baseline, but requires Global Scopes and server-side authorization policies across newly introduced models to prevent accidental cross-tenant data leakage.

---

## 6. Subscription Architecture & Feature Flags

- **Plans:** Free Trial, Starter, Professional, Business, Enterprise.
- **Config Flag:** `config/subscription.php` manages `plans_enabled`.
  - When `false`: 100% complimentary access for all Cambodian merchants.
  - Secret PIN Trigger: `RESTORE-SUBSCRIPTION-PLANS` preserves the capability to reactivate multi-tier billing anytime upon request.
- **Quota Enforcement:** Currently partial; needs a centralized `SubscriptionEntitlementService` to enforce branch limits, staff quotas, and feature flags consistently without scattering hardcoded conditionals.

---

## 7. Current Test Suite Baseline

- **Framework:** PHPUnit 12.5+ with Laravel Testing utilities
- **Test Baseline:** 79 automated tests passing 100% (321 assertions).
  - `tests/Feature/Auth/*`: 6 tests (Registration, Login, Password Reset, etc.)
  - `tests/Feature/BusinessManagementTest.php`: 7 tests
  - `tests/Feature/BusinessOwnerRoleTest.php`: 8 tests
  - `tests/Feature/LandingPageAndKdsTest.php`: 6 tests
  - `tests/Feature/MultiTenantIsolationTest.php`: 6 tests
  - `tests/Feature/PosAndInventoryTest.php`: 8 tests
  - `tests/Feature/ProfileTest.php`: 6 tests
  - `tests/Feature/PublicMenuAndOrderingTest.php`: 8 tests
  - `tests/Feature/QrCodeServiceTest.php`: 4 tests
  - `tests/Feature/SuperAdminTest.php`: 10 tests
  - `tests/Unit/ExampleTest.php`: 1 test

---

## 8. Identified Gaps & Technical Risks

1. **Missing Operational Modules (From Master Specification):**
   - Live Orders dedicated Kanban board (`/app/orders/live`) with sound alerts.
   - Table Reservations & Customer Waitlist systems.
   - Customer Reviews & Feedback moderation.
   - Coupons & Automated Happy Hours scheduling.
   - Delivery Zones with fee structures.
   - Hardware printer management (ESC/POS 58mm/80mm thermal dockets).
   - CSV / Excel bulk product import wizard.
2. **Category Engine:**
   - Need category-specific feature visibility so non-food retail stores do not see Kitchen Display or Restaurant Tables.
3. **Database Portability:**
   - Database queries must remain 100% standard SQL compatible across both PostgreSQL (production) and SQLite (in-memory testing). Avoid non-standard SQL aliases in `HAVING` clauses.
