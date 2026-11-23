# SABAYRA — Order Status Workflow & State Machine

## 1. Overview

SABAYRA utilizes a strict state machine to govern order lifecycle transitions. This guarantees data consistency across kitchen preparation, inventory depletion, cashier reconciliations, and customer updates.

---

## 2. Order Status State Machine

| Status | Code | Description | Allowed Next States | Triggered By |
| :--- | :--- | :--- | :--- | :--- |
| **Pending** | `pending` | Order submitted by customer or cashier; awaiting confirmation. | `confirmed`, `cancelled` | Customer QR / POS Draft |
| **Confirmed** | `confirmed` | Staff accepted the order; sent to kitchen / barista. | `preparing`, `cancelled` | Cashier / Floor Staff |
| **Preparing** | `preparing` | Kitchen is cooking or assembling items. | `ready`, `cancelled` | Kitchen Chef / KDS Bump |
| **Ready** | `ready` | Items prepared and plated; ready for table service or pickup. | `paid`, `completed`, `cancelled` | Kitchen Chef / KDS Bump |
| **Paid** | `paid` | Customer completed payment (Cash or Bakong KHQR). | `completed` | Cashier / POS Checkout |
| **Completed** | `completed` | Order fully fulfilled, customer departed, stock depleted. | *Terminal state* | Cashier / Manager |
| **Cancelled** | `cancelled` | Order rejected or voided by authorized staff. | *Terminal state* | Cashier / Manager |

---

## 3. Side Effects & Automation Triggers

### Upon `confirmed`
- Ticket appears on KDS screen.
- Auto-print to kitchen printer (if auto-print enabled).
- Audible chime sounds on KDS and POS stations.

### Upon `ready`
- Floor runner notification with table identifier.
- Mobile storefront updates to show "Ready for Pickup" for counter orders.

### Upon `paid`
- Payment record generated in ledger.
- Cash drawer opens (if configured).
- Thermal customer receipt printed.

### Upon `completed`
- Linked table marked as `available`.
- Raw ingredient inventory deducted based on product recipes.
- Sales metrics and item velocity aggregated to daily analytics cache.

---

## 4. Role-Based Permissions for Status Updates

- **Cashier**: Can transition `pending` &rarr; `confirmed`, `ready` &rarr; `paid`, `paid` &rarr; `completed`. Can cancel before preparation begins.
- **Kitchen Chef**: Can transition `confirmed` &rarr; `preparing` and `preparing` &rarr; `ready`.
- **Store Manager / Owner**: Full override permissions across all status transitions, refunds, and voids.
