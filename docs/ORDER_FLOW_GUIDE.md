# SABAYRA — Order Flow Guide Architecture

## 1. Executive Summary

The **Order Flow Guide** in SABAYRA (`/app/workflow-guide` and `/app/guides/order-flow`) provides business owners, managers, cashiers, and kitchen teams with a structured operating guide for end-to-end order processing. It bridges customer self-service ordering via QR standees with back-of-house kitchen fulfillment and front-of-house cashier payments.

---

## 2. Six-Stage Operational Lifecycle

```
[ Step 1: Customer Order ]
        │ (Status: pending / unpaid)
        ▼
[ Step 2: Staff Confirmation ]
        │ (Status: confirmed)
        ▼
[ Step 3: Kitchen Preparation ]
        │ (Status: preparing, Kitchen ticket prints / KDS displays)
        ▼
[ Step 4: Ready to Serve ]
        │ (Status: ready, Floor runner alerted)
        ▼
[ Step 5: Invoice & Payment ]
        │ (Status: paid, Bakong KHQR / Cash, Receipt prints)
        ▼
[ Step 6: Complete & Deplete Stock ]
          (Status: completed, Table released, Ingredients deducted)
```

### Step 1: Customer Order
- **Channel**: Customer scans table QR standee or counter QR using mobile device camera.
- **Experience**: Progressive Web App loads instantly—no app download required.
- **Payload**: Items, quantities, item modifiers (sugar level, toppings), notes, and table identifier.
- **Initial Status**: `pending`

### Step 2: Staff Confirmation
- **Channel**: Cashier POS and Live Orders Kanban.
- **Notification**: Loud chime sound and live banner badge.
- **Action**: Cashier checks product availability, clarifies notes, and approves the ticket.
- **Status Change**: `pending` &rarr; `confirmed`

### Step 3: Kitchen Preparation
- **Channel**: Kitchen Display System (KDS) screen and/or 80mm thermal kitchen printer.
- **Action**: Kitchen chef clicks "Start Cooking" or tears off printed docket.
- **Timer**: Live timer starts measuring preparation speed; card turns yellow/red when exceeding standard threshold.
- **Status Change**: `confirmed` &rarr; `preparing`

### Step 4: Ready to Serve
- **Channel**: KDS completion bump button.
- **Notification**: Floor runner or waiter receives table alert to dispatch dishes.
- **Action**: Hot dishes delivered directly to dining table or counter pickup.
- **Status Change**: `preparing` &rarr; `ready`

### Step 5: Invoice & Payment
- **Channel**: Touch POS checkout or table bill request.
- **Currencies**: Dual USD ($) and Khmer Riel (៛) calculation via dynamic store exchange rate.
- **Payment Methods**: Bakong KHQR dynamic QR scanning or physical cash with change calculator.
- **Documentation**: 58mm / 80mm customer tax invoice printed.
- **Status Change**: `ready` &rarr; `paid`

### Step 6: Complete & Inventory Depletion
- **Action**: Cashier marks ticket finalized after customer departure.
- **Automations**:
  - Table status automatically reset to `available` for incoming guests.
  - Linked recipe ingredients auto-deducted from inventory stock levels.
  - Revenue, profit, and velocity metrics synced to owner's dashboard in real-time.
- **Status Change**: `paid` &rarr; `completed`

---

## 3. Quick Links Integration

The guide provides instant operational links directly scoped to the current tenant:
- **View My Menu**: Live storefront URL generated via `QrCodeService::getMenuUrl($business)`.
- **Copy Menu URL**: One-click clipboard copy with Alpine.js visual confirmation.
- **Live Orders**: Real-time order stream (`/app/orders/live`).
- **Kitchen Display**: Fullscreen KDS interface (`/app/kitchen`).
- **QR Studio**: Standee and menu designer (`/app/qr-studio`).
- **Printers**: Hardware management and Quick Print console (`/app/printers`).
- **Reports & Analytics**: Daily sales, item ranking, and revenue breakdown (`/app/reports`).

---

## 4. Hardware Recommendations

| Device | Role | Form Factor | Recommended Connectivity |
| :--- | :--- | :--- | :--- |
| **Owner / Manager Terminal** | Catalog, Staff, Inventory, Financials | PC, Mac, or Tablet | Wi-Fi / LAN |
| **Kitchen Display (KDS)** | Chef order cards & bump controls | 10"+ Android/iPad Tablet | 5GHz Wi-Fi / Kiosk Browser |
| **Cashier Terminal** | Touch POS & Checkout | Touch POS / PC | Ethernet LAN / USB |
| **Receipt / Docket Printer** | Customer invoices & kitchen dockets | 80mm ESC/POS Thermal | Ethernet LAN / USB / Bluetooth |
