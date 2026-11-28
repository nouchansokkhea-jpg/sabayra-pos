# SABAYRA SaaS Platform — Database Design & Schema Extensions
**Version:** 1.0.0  
**Date:** November 23, 2026  

---

## 1. Multi-Tenant Architectural Principle
Every business-scoped table MUST include a foreign key referencing `businesses(id)` with cascading deletion:
```php
$table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
```
All queries in controllers and services MUST scope by `where('business_id', $business->id)` or via Tenant Global Scopes.

---

## 2. Planned Schema Extensions

### A. Reservations & Waitlist (`2026_11_23_100000_create_reservations_and_waitlist_tables.php`)
```sql
CREATE TABLE reservations (
    id UUID PRIMARY KEY,
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    branch_id UUID REFERENCES business_branches(id) ON DELETE SET NULL,
    table_id UUID REFERENCES restaurant_tables(id) ON DELETE SET NULL,
    customer_name VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    party_size INTEGER NOT NULL DEFAULT 2,
    status VARCHAR(30) NOT NULL DEFAULT 'pending', -- pending, confirmed, seated, completed, cancelled
    special_requests TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE customer_waitlists (
    id UUID PRIMARY KEY,
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    customer_name VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    party_size INTEGER NOT NULL DEFAULT 2,
    estimated_wait_minutes INTEGER DEFAULT 15,
    status VARCHAR(30) NOT NULL DEFAULT 'waiting', -- waiting, notified, seated, cancelled
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### B. Reviews, Coupons, Happy Hours & Loyalty (`2026_11_23_100001_create_customer_reviews_and_marketing_tables.php`)
```sql
CREATE TABLE customer_reviews (
    id UUID PRIMARY KEY,
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    order_id UUID REFERENCES orders(id) ON DELETE SET NULL,
    customer_name VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(50),
    rating SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    owner_reply TEXT,
    status VARCHAR(30) NOT NULL DEFAULT 'pending', -- pending, approved, rejected
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE coupons (
    id UUID PRIMARY KEY,
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    code VARCHAR(50) NOT NULL,
    discount_type VARCHAR(20) NOT NULL, -- percentage, fixed
    discount_value NUMERIC(10,2) NOT NULL,
    minimum_spend NUMERIC(10,2) DEFAULT 0,
    max_discount NUMERIC(10,2),
    usage_limit INTEGER,
    used_count INTEGER DEFAULT 0,
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE happy_hours (
    id UUID PRIMARY KEY,
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    discount_type VARCHAR(20) NOT NULL, -- percentage, fixed
    discount_value NUMERIC(10,2) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    days_of_week JSONB, -- [1,2,3,4,5]
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### C. Delivery Zones, Printers & Security Logs (`2026_11_23_100002_create_operations_delivery_printers_and_security_tables.php`)
```sql
CREATE TABLE delivery_zones (
    id UUID PRIMARY KEY,
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    branch_id UUID REFERENCES business_branches(id) ON DELETE SET NULL,
    zone_name VARCHAR(100) NOT NULL,
    delivery_fee NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    min_order_amount NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    free_delivery_threshold NUMERIC(10,2),
    estimated_delivery_minutes INTEGER DEFAULT 30,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE printers (
    id UUID PRIMARY KEY,
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    branch_id UUID REFERENCES business_branches(id) ON DELETE SET NULL,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'receipt', -- receipt, kitchen
    connection_type VARCHAR(30) NOT NULL DEFAULT 'network', -- network, usb, bluetooth
    paper_size VARCHAR(20) NOT NULL DEFAULT '80mm', -- 58mm, 80mm, a4
    ip_address VARCHAR(50),
    port INTEGER DEFAULT 9100,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE security_logs (
    id UUID PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    business_id UUID REFERENCES businesses(id) ON DELETE CASCADE,
    event_type VARCHAR(50) NOT NULL, -- login, failed_login, 2fa_enabled, 2fa_disabled
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP
);
```

---

## 3. Category-Based Business Experience Schema Extensions (`2026_11_28_000001_add_category_experience_columns.php`)

Additive, idempotent (`Schema::hasColumn` / `Schema::hasTable` safe) extensions enabling retail barcodes/batches/expiry, fashion variant matrices, beverage customizations, bakery pre-orders, and online service appointments without disrupting existing hospitality records:

1. **`products`**:
   - `brand` (`VARCHAR(120)`, nullable) — Brand / Manufacturer for Retail, Beauty, Clothing.
   - `batch_number` (`VARCHAR(120)`, nullable) — Production or batch/lot ID.
   - `expiry_date` (`DATE`, nullable, indexed via `products_biz_expiry_idx`) — Expiry monitoring for Supermarket, Mini Mart, Bakery, Beauty.
   - `duration_minutes` (`INTEGER`, nullable) — Session length for Services Business.
   - `attributes_json` (`JSON`, nullable) — Extensible category-specific metadata.
2. **`product_variants`**:
   - `barcode` (`VARCHAR(100)`, nullable, indexed via `product_variants_barcode_idx`) — Per-variant barcode scan lookup.
   - `size` (`VARCHAR(50)`, nullable) — Apparel size (`S`, `M`, `L`, `XL`, etc.).
   - `color` (`VARCHAR(50)`, nullable) — Color option.
   - `shade` (`VARCHAR(50)`, nullable) — Cosmetics shade / tone.
3. **`orders`**:
   - `scheduled_for` (`TIMESTAMP`, nullable) — Pickup / pre-order or appointment timestamp.
   - `assigned_staff_id` (`UUID` FK &rarr; `business_members.id`, nullable, `SET NULL` on delete) — Staff / stylist / technician attribution.
   - `pos_metadata` (`JSON`, nullable) — Sector POS checkout metadata.
4. **`order_items`**:
   - `options_json` (`JSON`, nullable) — Beverage customizations (`temp`, `sugar`, `ice`) and line-item preferences.
5. **`reservations`**:
   - `booking_type` (`VARCHAR(30)`, default `'table'`) — Distinguishes `'table'` dining reservations vs `'service'` appointments.
   - `booking_code` (`VARCHAR(40)`, nullable, indexed via `reservations_biz_code_idx`) — Public appointment lookup code (`BK-YYMMDD-XXXX`).
   - `service_product_id` (`UUID` FK &rarr; `products.id`, nullable, `SET NULL` on delete) — Booked catalog service.
   - `staff_member_id` (`UUID` FK &rarr; `business_members.id`, nullable, `SET NULL` on delete) — Assigned staff specialist.
   - `duration_minutes` (`INTEGER`, nullable) & `end_time` (`VARCHAR(20)`, nullable) — Slot overlap & double-booking protection.
   - `price_usd` (`NUMERIC(10,2)`, nullable) & `payment_status` (`VARCHAR(30)`, default `'unpaid'`) — Financial tracking across Appointments & POS settlement.
