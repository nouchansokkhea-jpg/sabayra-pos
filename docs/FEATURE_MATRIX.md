# SABAYRA SaaS Platform — Business Category Feature Matrix
**Version:** 1.0.0  
**Date:** November 23, 2026  

---

## 1. Supported Business Categories
SABAYRA natively supports 12 business categories tailored for Cambodia:

| Category Key | English Display Name | Khmer Display Name |
| :--- | :--- | :--- |
| `restaurant` | Restaurant | ភោជនីយដ្ឋាន |
| `cafe` | Café | ហាងកាហ្វេ |
| `milk_tea` | Milk Tea Shop | ហាងតែគុជ |
| `bakery` | Bakery | ហាងនំបុ័ង / នំខេក |
| `fast_food` | Fast Food | អាហាររហ័ស |
| `supermarket` | Supermarket | ផ្សារទំនើប |
| `mini_mart` | Mini Mart | មីនីម៉ាត |
| `retail_shop` | Retail Shop | ហាងលក់រាយទូទៅ |
| `clothing` | Clothing & Fashion Store | ហាងសម្លៀកបំពាក់ |
| `beauty` | Beauty & Cosmetics | ហាងសម្អាង / គ្រឿងសម្អាង |
| `services` | Services Business | អាជីវកម្មសេវាកម្ម |
| `other` | Other Business | អាជីវកម្មផ្សេងៗ |

---

## 2. Feature Visibility & Entitlement Matrix

| Feature / Module | Restaurant | Café | Milk Tea | Bakery | Fast Food | Supermarket | Mini Mart | Retail | Clothing | Beauty | Services |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **Touch POS** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Catalog & Products** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Tables & Dine-In QR** | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Kitchen Display (KDS)** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Live Orders** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Reservations** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Customer Waitlist** | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Inventory & Stock** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Recipes & Ingredients** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Coupons & Discounts** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Happy Hours** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Delivery Zones** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| **Receipt Printers** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Barcode / SKU** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |

---

## 3. Centralized Feature Engine
Governed centrally by `App\Services\Business\BusinessFeatureService`:
```php
if (BusinessFeatureService::canAccess($business, 'tables')) {
    // Render Table Navigation & QR features
}
```
Ensures zero hardcoded sector checks throughout controllers and Blade views.
