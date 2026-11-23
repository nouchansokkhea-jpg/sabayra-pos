# SABAYRA — Thermal Printing Architecture & Ticket Specifications

## 1. Overview

SABAYRA features a hybrid thermal printing architecture designed for the Cambodian hospitality and retail landscape. It supports both raw ESC/POS network printing (port 9100) and universal browser print spooling formatted for 58mm and 80mm continuous roll paper.

---

## 2. Receipt vs. Kitchen Ticket Layout Comparison

| Field / Attribute | Customer Receipt (`/orders/{order}/receipt`) | Kitchen Ticket (`/orders/{order}/kitchen-ticket`) |
| :--- | :--- | :--- |
| **Primary Audience** | Customer, Cashier, Tax Record | Kitchen Chef, Line Cook, Barista |
| **Paper Standard** | 58mm or 80mm roll | 80mm high-visibility roll |
| **Order Number Header** | Standard font size | Extra-large bold display (3x font size) |
| **Table / Fulfillment** | Included in metadata | Prominently highlighted at top |
| **Product Items** | Product name, unit price, line total | Product name, large quantity badge, prep notes |
| **Item Checkboxes** | Not included | [ ] Physical preparation checkboxes |
| **Financial / Pricing Data** | Subtotal, discounts, tax, total USD, total KHR | **Omitted completely** (no prices or totals) |
| **Payment Details** | Cash tendered, change, Bakong KHQR trace ID | **Omitted completely** |
| **Footer** | Thank you message, Wi-Fi info, tax TIN | Order timestamp, server name, station routing |

---

## 3. Dedicated Kitchen Ticket Specification

The dedicated kitchen docket (`resources/views/business/orders/kitchen_ticket.blade.php`) follows strict back-of-house design principles:
- **Clean Focus**: Removes distraction of currency values, taxes, and payment methods.
- **Large Typography**: Ensures chefs can read orders from 1-2 meters away across the preparation counter.
- **Modifiers & Add-ons**: Clearly indented underneath parent item with bold bullets (e.g. `• Less Sweet (50%)`, `• Extra Pearls`).
- **Special Cooking Notes**: Displayed in prominent callout boxes (e.g. "NO ONIONS", "ALLERGIC TO PEANUTS").
- **Tear-off Spacing**: Built-in 25mm bottom padding with cut line indicator.

---

## 4. Printer Self-Test Slip Specification

The self-test slip (`resources/views/business/printers/test_slip.blade.php`) verifies:
1. Printer name, connection type, IP address, and port.
2. Character density and alignment (Normal, Bold, Double-Width, Inverted).
3. Khmer Unicode rendering compatibility (ភាសាខ្មែរ: សួស្តី SABAYRA).
4. Auto-cutter triggering and paper feed distance.
5. Updates `last_used_at` timestamp on printer record in database.

---

## 5. Khmer Language Thermal Printing

Khmer script contains complex sub-consonants and vowels that traditional thermal printer hardware fonts do not natively support. SABAYRA handles this through two rendering pathways:
1. **Browser Thermal Spooling**: Renders standard UTF-8 Khmer Unicode via high-contrast SVG / Canvas font rasterization directly through the browser print dialog.
2. **Direct ESC/POS Bitmap Printing**: Converts formatted HTML / canvas to monochromatic 1-bit raster graphics before sending raw bytes to port 9100 over TCP socket.
