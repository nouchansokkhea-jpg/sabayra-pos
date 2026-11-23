# SABAYRA SaaS — Production Environment Requirements

**Target Domain:** `https://sabayra.site` (with `https://www.sabayra.site` redirect)  
**Hosting Infrastructure:** Laravel Cloud  
**Database:** Managed PostgreSQL 16+ (or Supabase PostgreSQL)  
**DNS Registrar:** Vercel  

---

## 1. Environment Variables Specification

The following variables must be configured in Laravel Cloud under **Application &rarr; Environment &rarr; Variables**:

### A. Core Application
```env
APP_NAME="SABAYRA"
APP_ENV=production
APP_KEY=base64:E9b14qAwF8TgrNZdPPt1gFPztYipD1QuXNEE1ijdeYI=
APP_DEBUG=false
APP_URL=https://sabayra.site
APP_TIMEZONE=Asia/Phnom_Penh
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

### B. Logging & Maintenance
```env
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12
```

### C. Database Connection (PostgreSQL)
When using an attached Laravel Cloud PostgreSQL database, these are injected automatically. When using an external/Supabase database:
```env
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host.internal
DB_PORT=5432
DB_DATABASE=sabayra_production
DB_USERNAME=your_db_username
DB_PASSWORD=your_secure_db_password
DB_SSLMODE=prefer
```

### D. Session, Cache & Queue
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.sabayra.site

CACHE_STORE=database
QUEUE_CONNECTION=database
```

### E. Persistent Object Storage (Logos, Product Images, Covers)
```env
FILESYSTEM_DISK=public
SUPABASE_URL=https://zdqqrqtujqwvlmbasuqo.supabase.co
SUPABASE_ANON_KEY=your_supabase_anon_key
SUPABASE_SERVICE_ROLE_KEY=your_supabase_service_role_key
SUPABASE_STORAGE_BUCKET=menuhub-assets
SUPABASE_VERIFY_SSL=true
```

### F. Multi-Currency Standards (Cambodia First)
```env
DEFAULT_CURRENCY=USD
DEFAULT_EXCHANGE_RATE_KHR=4100
```

---

## 2. Server Compute & Scaling Specifications

- **Compute Size**: Flex (scales to zero on idle or low traffic) or Small (persistent 0.5-1 vCPU, 1GB RAM) for 24/7 restaurant operations.
- **Region**: Singapore (`ap-southeast-1`) or Tokyo (`ap-northeast-1`) for sub-50ms latency to Phnom Penh, Siem Reap, and Battambang.
- **PHP Version**: PHP 8.3 / 8.4 runtime with OPcache enabled.
- **Web Server**: Caddy / Nginx reverse proxy with HTTP/2 and TLS 1.3 support.

---

## 3. Storage & Persistence Requirements

1. **Database Persistence**: PostgreSQL tables with hourly automated backups and 7-day retention.
2. **Ephemeral App Containers**: Application releases are stateless Docker containers. No user uploads may reside on local container filesystems.
3. **Asset Delivery**: Cloudflare CDN / Supabase S3 storage for all product photos, shop logos, and cover images.

---

## 4. DNS Record Blueprint (Vercel)

| Type | Name | Target / Value | TTL | Purpose |
| :---: | :---: | :---: | :---: | :--- |
| **A** or **CNAME** | `@` | Provided by Laravel Cloud | Auto | Primary apex domain (`sabayra.site`) |
| **CNAME** | `www` | Provided by Laravel Cloud | Auto | Subdomain (`www.sabayra.site`) |
| **TXT** | `_acme-challenge` | Provided by Laravel Cloud | 60 | Automatic Let's Encrypt SSL Validation |

---

## 5. Security & Isolation Matrix

- **HTTPS Redirection**: Enforced with HSTS header.
- **Cross-Tenant Guard**: Strict SQL scoping via `TenantScope` and UUID keys.
- **Super Admin Protection**: Restricted routes under `/admin` with `RequireSuperAdmin` middleware.
- **2FA Option**: TOTP-ready 2FA toggle for business owners.
