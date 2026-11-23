# SABAYRA SaaS Platform — Feature Gap Analysis
**Version:** 1.0.0  
**Date:** November 23, 2026  

---

## Matrix: Modules A through S Specification vs. Existing Repository

| Module ID | Module Name | Existing State | Gap / Planned Upgrade | Priority |
| :--- | :--- | :--- | :--- | :--- |
| **Module A** | Business Dashboard | Partial | Existing dashboard has core stats. Missing Today vs Yesterday % comparison, Public Storefront card, Top Sellers list, Customer insights, and Security overview. | High |
| **Module B** | Order Management | Partial | Standard order list, detail, and status bump exist. Missing multi-status filters, advanced date picker, receipt generator for 80mm/58mm. | High |
| **Module C** | Live Orders | Missing | Missing dedicated full-screen live order feed with audio alerts, auto-polling fallback, and drawer detail. | High |
| **Module D** | Kitchen Display (KDS) | Partial | Basic KDS Kanban exists. Needs item modifiers breakdown, elapsed time visual timer, audio alerts, and role restriction for cook stations. | Medium |
| **Module E** | Order Flow Guide | Missing | Missing step-by-step interactive lifecycle visual guide tailored to specific business types. | Medium |
| **Module F** | Tables & QR Ordering | Implemented | Master QR, Table QR, PNG download, and standee print exist. Need AR card integration. | Low |
| **Module G** | Reservations & Waitlist | Missing | No reservations or customer queue waitlist models, migrations, or views exist. | High |
| **Module H** | Customer Reviews | Missing | No customer review collection, rating calculation, or moderation views exist. | Medium |
| **Module I** | Coupons & Discounts | Missing | No promotional coupon code system with usage limits and checkout validation exists. | High |
| **Module J** | Happy Hours | Missing | No automated time-based pricing promotions with business timezone scheduling exist. | High |
| **Module K** | Delivery Zones | Missing | No delivery zone radius, district fees, or minimum checkout rules exist. | Medium |
| **Module L** | Advanced Analytics | Partial | Basic Chart.js sales trend exists. Missing peak hours heatmap, revenue by category, and unique visitor metrics. | High |
| **Module M** | Financial Reports | Partial | Basic report and CSV export exist. Missing PDF export, tax collected vs service charge breakdowns. | Medium |
| **Module N** | Printer Management | Missing | No ESC/POS printer registry (network/USB/Bluetooth) or 58mm/80mm docket routing exists. | High |
| **Module O** | Product Bulk Import | Missing | No CSV / Excel column mapping wizard with batch validation and duplicate detection exists. | High |
| **Module P** | Payment & Tax Settings | Partial | KHQR service and cash checkout exist. Missing payment proof uploads, tax inclusive/exclusive toggle. | Medium |
| **Module Q** | Telegram Notifications | Missing | No Telegram bot account linking or event webhooks for new orders/reservations exist. | Medium |
| **Module R** | Account Security | Partial | Standard passwords exist. Missing TOTP Two-Factor Authentication, login attempt tracking, and session audit logs. | High |
| **Module S** | Business Settings | Implemented | Operating hours, logo, currency, exchange rate, and tax settings exist. Needs clean tabs layout. | Low |

---

## Business Category Matrix

Existing categories defined in `config/menuhub.php`:
1. `restaurant` (Dining, tables, kitchen, recipes)
2. `cafe` (Counter, drinks, pastries, barista)
3. `milk_tea` (Add-ons, toppings, sugar/ice levels)
4. `bakery` (Baking, batch inventory, cakes)
5. `fast_food` (Combos, quick fulfillment, KDS)
6. `supermarket` (Barcode, high SKU inventory, checkout)
7. `mini_mart` (Convenience retail, cashier sessions)
8. `retail_shop` (Retail goods, variants, inventory)
9. `clothing` (Sizes, colors, fashion categories)
10. `beauty` (Cosmetics, services, personal care)
11. `services` (Appointments, customer records)
12. `other` (General trade and small businesses)

**Engine Requirement:** Create `BusinessFeatureService` to dynamically show/hide navigation items and dashboard widgets based on category.
