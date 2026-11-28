# SABAYRA SaaS Platform — Business Category Feature Matrix
**Version:** 2.0.0  
**Date:** November 28, 2026  

---

## 1. Supported Business Categories
SABAYRA natively supports 12 business categories tailored for Cambodia (`config/business_features.php` + `App\Services\Business\CategoryExperienceService`):

| Category Key | English Display Name | Khmer Display Name | POS Mode | Storefront Mode |
| :--- | :--- | :--- | :--- | :--- |
| `restaurant` | Restaurant | ភោជនីយដ្ឋាន | `hospitality_table` | `menu` (Tables & QR) |
| `cafe` | Café | ហាងកាហ្វេ | `drink_bar` | `menu` (Drink Customizer) |
| `milk_tea` | Milk Tea Shop | ហាងតែគុជ | `drink_bar` | `menu` (Sugar/Ice/Toppings) |
| `bakery` | Bakery | ហាងនំបុ័ង / នំខេក | `bakery_preorder` | `menu` (Pickup Pre-Orders) |
| `fast_food` | Fast Food | អាហាររហ័ស | `express_counter` | `menu` (Combos & KDS) |
| `supermarket` | Supermarket | ផ្សារទំនើប | `barcode_checkout` | `catalog` (Brands/Stock/SKU) |
| `mini_mart` | Mini Mart | មីនីម៉ាត | `barcode_checkout` | `catalog` (Brands/Stock/SKU) |
| `retail_shop` | Retail Shop | ហាងលក់រាយទូទៅ | `barcode_checkout` | `catalog` (Brands/Stock/SKU) |
| `clothing` | Clothing & Fashion Store | ហាងសម្លៀកបំពាក់ | `fashion_variant` | `catalog` (Size/Color/Brands) |
| `beauty` | Beauty & Cosmetics | ហាងសម្អាង / គ្រឿងសម្អាង | `cosmetic_retail` | `catalog` (Brands/Shades) |
| `services` | Services Business | អាជីវកម្មសេវាកម្ម | `service_booking` | `booking` (Slots & Staff) |
| `other` | Other Business | អាជីវកម្មផ្សេងៗ | `flexible` | `catalog` / Configurable |

---

## 2. Extended Feature Visibility & Experience Matrix (v2.0)

| Feature / Module | Restaurant | Café | Milk Tea | Bakery | Fast Food | Supermarket | Mini Mart | Retail | Clothing | Beauty | Services | Other |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **Touch POS** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Catalog / Menu / Services** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Tables & Dine-In QR** | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ⚙️ |
| **Kitchen Display (KDS)** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ⚙️ |
| **Live Orders** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Reservations (Tables)** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ⚙️ |
| **Service Bookings (Calendar)** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ⚙️ |
| **Customer Waitlist** | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Inventory & Stock** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ⚙️ |
| **Recipes & Ingredients** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ⚙️ |
| **Suppliers & PO** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ⚙️ |
| **Barcode / SKU Scan** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ⚙️ |
| **Bulk CSV Import** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Expiry / Batch Control** | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| **Drink Presets (Sugar/Ice)** | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Variant Size/Color Matrix** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| **Pre-Order Pickup Schedule** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Coupons & Discounts** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Happy Hours** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ⚙️ |
| **Delivery Zones** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ⚙️ |
| **Receipt Printers** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

Legend: `✅` Enabled by default · `❌` Hidden/Disabled · `⚙️` Configurable per business via `Category & Modules` Settings (`category.custom_modules`).

---

## 3. Centralized Category Experience Engine
Governed centrally by `App\Services\Business\CategoryExperienceService` (with backward-compatible static wrapper `App\Services\Business\BusinessFeatureService`):
- `isModuleEnabled($business, $feature)` checks base category registry + `BusinessSetting` (`category.custom_modules`) + active subscription plan limits when `subscription.plans_enabled` is enabled.
- Safe switching preserves all tenant products, categories, orders, customers, and history (`initializeDefaultsForBusiness()`).
- Dashboard KPI/panel resolution runs through `App\Services\Business\DashboardExperienceService` with per-workspace hidden widget preferences (`dashboard.hidden_widgets`).
