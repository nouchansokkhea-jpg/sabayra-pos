<?php

namespace App\Services\Qr;

use App\Models\Business;
use Illuminate\Support\Str;

class QrCodeService
{
    /**
     * Get the base URL for QR codes that mobile devices can reach.
     */
    public static function getBaseUrl(?Business $business = null): string
    {
        // 1. If currently accessed on a live public web host (e.g. sabayra-pos.onrender.com or production domain)
        if (request()->hasHeader('Host')) {
            $currentHost = request()->getHost();
            if (!in_array($currentHost, ['localhost', '127.0.0.1', '::1'], true)) {
                // If the business has a custom domain that is valid and matches the current active host
                $customPublicUrl = $business?->theme_settings['public_url'] ?? $business?->theme_settings['custom_domain'] ?? null;
                if ($customPublicUrl && !Str::contains($customPublicUrl, ['kheatopup.store', 'localhost', '127.0.0.1'])) {
                    $parsedHost = parse_url(str_starts_with($customPublicUrl, 'http') ? $customPublicUrl : 'https://' . $customPublicUrl, PHP_URL_HOST);
                    if ($parsedHost === $currentHost) {
                        return rtrim(request()->schemeAndHttpHost(), '/');
                    }
                }
                // When running on website hosting (Render, Cloud PaaS, etc.), ALWAYS use the live application origin
                return rtrim(request()->schemeAndHttpHost(), '/');
            }
        }

        // 2. Explicit QR_BASE_URL in config / .env (for local Wi-Fi LAN / IP testing)
        $qrUrl = config('menuhub.qr_base_url');
        if ($qrUrl && !Str::contains($qrUrl, ['localhost', '127.0.0.1', 'kheatopup.store'])) {
            return rtrim($qrUrl, '/');
        }

        // 3. Business-specific public custom domain or tunnel URL (for local 5G cellular scanning)
        if ($business) {
            $customPublicUrl = $business->theme_settings['public_url'] ?? $business->theme_settings['custom_domain'] ?? null;
            if ($customPublicUrl && !Str::contains($customPublicUrl, ['kheatopup.store'])) {
                $clean = rtrim($customPublicUrl, '/');
                if (!str_starts_with($clean, 'http://') && !str_starts_with($clean, 'https://')) {
                    $clean = 'https://' . $clean;
                }
                return $clean;
            }
        }

        // 4. If APP_URL is configured and is NOT localhost/127.0.0.1, use it
        $appUrl = config('app.url');
        if ($appUrl && !Str::contains($appUrl, ['localhost', '127.0.0.1', 'kheatopup.store'])) {
            return rtrim($appUrl, '/');
        }

        // 5. If an explicit QR_BASE_URL was provided even if localhost, return it
        if ($qrUrl) {
            return rtrim($qrUrl, '/');
        }

        // 6. Fallback
        return rtrim(request()->schemeAndHttpHost() ?: ($appUrl ?: 'http://localhost'), '/');
    }

    /**
     * Generate the public menu URL for a business.
     */
    public static function getMenuUrl(Business $business): string
    {
        return self::getBaseUrl($business) . '/' . $business->slug;
    }

    /**
     * Generate the table-specific ordering URL.
     */
    public static function getTableUrl(Business $business, string $tableNumber): string
    {
        return self::getBaseUrl($business) . '/' . $business->slug . '/table/' . $tableNumber;
    }
}
