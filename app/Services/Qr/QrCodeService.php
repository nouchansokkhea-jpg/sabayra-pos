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
        // 1. Business-specific public custom domain or tunnel URL (for 5G cellular scanning)
        if ($business) {
            $customPublicUrl = $business->theme_settings['public_url'] ?? $business->theme_settings['custom_domain'] ?? null;
            if ($customPublicUrl) {
                $clean = rtrim($customPublicUrl, '/');
                if (!str_starts_with($clean, 'http://') && !str_starts_with($clean, 'https://')) {
                    $clean = 'https://' . $clean;
                }
                return $clean;
            }
        }

        // 2. Explicit QR_BASE_URL in config / .env
        $qrUrl = config('menuhub.qr_base_url');
        if ($qrUrl && !Str::contains($qrUrl, ['localhost', '127.0.0.1'])) {
            return rtrim($qrUrl, '/');
        }

        // 3. If APP_URL is configured and is NOT localhost/127.0.0.1, use it
        $appUrl = config('app.url');
        if ($appUrl && !Str::contains($appUrl, ['localhost', '127.0.0.1'])) {
            return rtrim($appUrl, '/');
        }

        // 4. If current request host is accessed via a public domain or LAN IP
        if (request()->hasHeader('Host')) {
            $host = request()->getHost();
            if (!in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                return rtrim(request()->schemeAndHttpHost(), '/');
            }
        }

        // 5. If an explicit QR_BASE_URL was provided even if localhost, return it
        if ($qrUrl) {
            return rtrim($qrUrl, '/');
        }

        // 6. Fallback
        return rtrim($appUrl ?: request()->schemeAndHttpHost(), '/');
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
