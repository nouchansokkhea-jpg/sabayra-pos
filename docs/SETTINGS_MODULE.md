# SABAYRA Settings Module Architecture

## Overview

The SABAYRA Settings Hub consolidates storefront configuration, business identity, currency/payment/tax rules, notification readiness, personal security controls, and user account management into a responsive, permission-aware SaaS interface matching SABAYRA's dark navy and orange design system.

## Routes

| Method | URI | Name | Access |
|---|---|---|---|
| `GET` | `/business/{path}` | — | Redirects to `/app/{path}` preserving query strings |
| `GET` | `/app/settings` | `business.settings.index` | Authenticated business members (`?section=` selects active tab) |
| `PUT` | `/app/settings` | `business.settings.update` | Owner / Super Admin / `manage_settings` (full backward-compatible update) |
| `PUT` | `/app/settings/general` | `business.settings.general.update` | Owner / Super Admin / `manage_settings` |
| `PUT` | `/app/settings/business` | `business.settings.business.update` | Owner / Super Admin / `manage_settings` |
| `PUT` | `/app/settings/payment` | `business.settings.payment.update` | Owner / Super Admin / `manage_settings` |
| `PUT` | `/app/settings/notifications` | `business.settings.notifications.update` | Owner / Super Admin / `manage_settings` |
| `PUT` | `/app/settings/account` | `business.settings.account.update` | Any authenticated member (own account only) |
| `PUT` | `/app/settings/account/password` | `business.settings.account.password` | Any authenticated member (current password required) |
| `POST` | `/app/notifications/{notificationId}/read` | `business.notifications.read` | Authenticated member (active business & own user only) |
| `POST` | `/app/notifications/read-all` | `business.notifications.read-all` | Authenticated member (active business & own user only) |
| `GET` | `/app/security` | `business.security.index` | Any authenticated member (renders unified Security view) |
| `POST` | `/app/security/2fa` | `business.security.2fa` | Legacy endpoint delegating to verified TOTP setup / disable |
| `POST` | `/app/security/2fa/setup` | `business.security.2fa.setup` | Any authenticated user (generates encrypted Base32 TOTP secret & QR SVG) |
| `POST` | `/app/security/2fa/confirm` | `business.security.2fa.confirm` | Any authenticated user (verifies TOTP code, enables 2FA, issues recovery codes) |
| `POST` | `/app/security/2fa/cancel` | `business.security.2fa.cancel` | Any authenticated user (cancels unconfirmed setup) |
| `POST` | `/app/security/2fa/recovery-codes` | `business.security.2fa.recovery-codes` | Any authenticated user (`current_password` required) |
| `DELETE` | `/app/security/2fa` | `business.security.2fa.disable` | Any authenticated user (`current_password` required) |
| `DELETE` | `/app/security/sessions/{sessionRef}` | `business.security.sessions.destroy` | Authenticated user (revokes specific non-current session by HMAC ref) |
| `POST` | `/app/security/sessions/revoke-others` | `business.security.sessions.revoke-others` | Authenticated user (`current_password` required; rotates `remember_token`) |
| `PUT` | `/app/settings/security` | `business.settings.security.update` | Owner / Super Admin / `manage_settings` |
| `GET`/`POST` | `/two-factor-challenge` | `two-factor.login` / `.store` | Guest middleware (`auth.two_factor_pending` + rate limiting + TOTP/recovery verify) |

## Navigation & Sections

1. **General Settings** (`section=general`): Storefront status, Unicode/Khmer public menu slug, custom mobile scan domain/URL, theme preset/colors/layout/font, `general.show_khr_prices`, and `general.auto_accept_orders`.
2. **Business Information** (`section=business`): Business profile, category, location, Supabase logo/cover media upload, opening hours, and social links.
3. **Payment & Tax** (`section=payment`): Primary currency (`USD`/`KHR`), USD-to-KHR exchange rate, VAT percentage, Bakong KHQR Account ID & display merchant name (consumed dynamically by `App\Services\Payment\KHQRService`), channel toggles, and thermal receipt footer.
4. **Notifications** (`section=notifications`): Telegram bot connection (one-click official bot deep link + custom business bot), branch-filtered multi-channel Notification Preferences matrix (`Telegram`, `Email`, `In-App`, `Browser` across all 14 Order, Reservation, Review, Inventory & Business events), accurate channel readiness badges, and recent Telegram delivery audit log.
5. **Security** (`section=security`): RFC 6238 TOTP Two-Factor Authentication (QR code + manual key setup, 6-digit verification, 8 single-use SHA-256 hashed backup recovery codes, password-protected regeneration/disable, and real login challenge flow), `Active Devices & Sessions` (HMAC-signed session tracking, single-device revocation, password-confirmed "Sign Out Other Devices" with remember token rotation), `Workspace Security Settings` (idle timeout enforced in `ResolveTenant`, unfamiliar-IP & failed-attempt alert toggles), and `Recent Login Activity` (filterable 30-day audit feed with browser/OS/device parsing, new-IP detection, failed attempt counters, and logout/password/reset/role change tracking).
6. **Account Settings** (`section=account`): Personal profile (`name`, `email`, `phone`, `preferred_locale`) and password update with `current_password` verification and `SecurityLog` audit entry.

## Database & Models

- `businesses`: Stores core profile, slug, currency/tax, opening hours, and JSON `theme_settings`.
- `business_settings` (`App\Models\BusinessSetting`): Key-value JSON settings store scoped by `business_id` (`BelongsToTenant`), persisting `general.*` and `payment.*` keys.
- `notification_preferences` (`App\Models\NotificationPreference`): Per-business / branch-filtered channel toggles across the 14 supported notification event keys.
- `notifications`: Standard Laravel database notification storage scoped to active business inside JSON payload and verified user ownership.

## Authorization & Multi-Tenant Isolation

- `App\Models\User::canManageBusinessSettings()` checks `isSuperAdmin()`, direct business ownership (`isOwnerOf()`), `owner` membership role, or Spatie permission `manage_settings`.
- Staff members without `manage_settings` are restricted from viewing (`?section=general|business|payment|notifications`) or mutating business-level settings (`403 Forbidden`), while retaining access to their own `Security` and `Account` sections.
- `SettingController` always resolves the target workspace via `TenantContext::get()` and ignores forged `business_id` inputs.

## Automated Tests

- `tests/Feature/SettingsModuleTest.php`: Settings Hub navigation, legacy `/business/settings` query-preserving redirect, General/Business/Payment updates, `KHQRService` integration, role restrictions (`403` for cashier), cross-tenant isolation, and personal account/password updates.
- `tests/Feature/TelegramIntegrationTest.php`: One-click official bot linking, webhook secret & replay protection, custom bot verification & encryption, polling fallback, and multi-tenant isolation.
- `tests/Feature/NotificationPreferencesAndDeliveryTest.php`: Multi-channel preferences, branch scope filtering, queued Telegram/Email/In-App dispatch, PII minimization, and daily sales summary generation.
- `tests/Feature/TwoFactorAuthenticationTest.php`: TOTP setup/confirmation, recovery code single-use login, challenge rate limiting, and password-verified disable/regeneration.
- `tests/Feature/LoginActivityAndSecurityAuditTest.php`: Sign-in/logout/failed-attempt/lockout audit trail, user-agent parsing, new-IP detection, role change logs, and filter tabs.
- `tests/Feature/SessionManagementAndSecuritySettingsTest.php`: Active devices listing, raw session ID masking, remote session revocation, `remember_token` rotation, workspace idle timeout, and unfamiliar-IP alert policies.
