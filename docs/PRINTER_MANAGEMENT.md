# SABAYRA — Printer Management & Hardware Setup

## 1. Overview

SABAYRA features an enterprise-grade printer management system accessible at `/app/printers`. It allows multi-tenant business owners to configure, monitor, and print to thermal receipt and kitchen docket printers over local network (LAN / Wi-Fi), USB, or Bluetooth connections.

---

## 2. Printer Types & Purposes

### Printer Types
1. **Receipt Printer**: Front-of-house thermal printers positioned at the cashier desk. Designed for generating customer tax invoices with full financial breakdowns, Bakong KHQR hashes, and shop contact information.
2. **Kitchen Printer**: Back-of-house thermal printers mounted in kitchen or barista stations. Formatted specifically for chefs with large readable table numbers and product names, omitting prices and totals.
3. **Label Printer**: Barcode / price tag printers for retail and inventory labeling.
4. **Barcode Printer**: High-density SKU and shelf-talker printing.

### Printing Purposes
- `receipt`: Dedicated exclusively for customer receipts and bills.
- `kitchen`: Dedicated exclusively for preparation orders and line dockets.
- `both`: Dual-purpose printer capable of handling both customer receipts and kitchen tickets.

---

## 3. Supported Connection Interfaces

| Interface | Protocol | Port / Address | Best Use Case |
| :--- | :--- | :--- | :--- |
| **Network (LAN / Wi-Fi)** | Raw TCP/IP (ESC/POS) | Default: `9100` | Multi-device environments (POS terminals, tablets, KDS) |
| **USB Direct** | Windows / Linux Driver | Virtual COM / Direct Spool | Single counter POS workstation |
| **Bluetooth** | SPP (Serial Port Profile) | Pair by Device Name | Mobile waiter tablets & food truck setups |

---

## 4. Printer Management Interface Features

1. **Hardware Matrix**:
   - Live status indicator (Active / Offline / Testing).
   - Display of connection type, IP address & port, paper size (80mm vs 58mm), assigned outlet branch, and last activity timestamp.
   - Default printer designation per tenant.
2. **Printer Configuration Modal**:
   - Friendly Printer Name (e.g., "Cashier Station 1", "Main Kitchen Line").
   - Type selection: Receipt, Kitchen, Label, Barcode.
   - Purpose selection: Customer Receipt, Kitchen Orders, or Both.
   - Connection Type: Network (IP/LAN), USB, Bluetooth.
   - IP Address & TCP Port with input validation.
   - Branch Outlet assignment.
   - Default printer toggle.
3. **Action Triggers**:
   - **Test Print**: Generates an 80mm ESC/POS formatted test slip verifying character widths, Khmer Unicode support, and alignment. Updates `last_used_at` timestamp.
   - **Edit Printer**: Modifies configuration parameters with strict tenant isolation.
   - **Delete Printer**: Safely unlinks printer device from business records.
4. **Quick Print Console**:
   - Instant access to the last 10 real orders.
   - One-click trigger for **Customer Receipt** (`/app/orders/{order}/receipt`).
   - One-click trigger for **Kitchen Docket** (`/app/orders/{order}/kitchen-ticket`).
   - One-click **Reprint** action.
