<?php

namespace App\Services\Qr;

use App\Models\Business;
use Illuminate\Support\Str;

class QrCodeService
{
    /**
     * Get the base URL for QR codes that mobile devices can reach.
     */
    public static function getBaseUrl(): string
    {
        // 1. Explicit QR_BASE_URL in config / .env
        $qrUrl = config('menuhub.qr_base_url');
        if ($qrUrl && !Str::contains($qrUrl, ['localhost', '127.0.0.1'])) {
            return rtrim($qrUrl, '/');
        }

        // 2. If APP_URL is configured and is NOT localhost/127.0.0.1, use it
        $appUrl = config('app.url');
        if ($appUrl && !Str::contains($appUrl, ['localhost', '127.0.0.1'])) {
            return rtrim($appUrl, '/');
        }

        // 3. If current request host is accessed via a LAN IP or domain (e.g. 172.20.10.3:8000)
        if (request()->hasHeader('Host')) {
            $host = request()->getHost();
            if (!in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                return rtrim(request()->schemeAndHttpHost(), '/');
            }
        }

        // 4. If an explicit QR_BASE_URL was provided even if localhost, return it
        if ($qrUrl) {
            return rtrim($qrUrl, '/');
        }

        // 5. Fallback
        return rtrim($appUrl ?: request()->schemeAndHttpHost(), '/');
    }

    /**
     * Generate the public menu URL for a business.
     */
    public static function getMenuUrl(Business $business): string
    {
        return self::getBaseUrl() . '/' . $business->slug;
    }

    /**
     * Generate the table-specific ordering URL.
     */
    public static function getTableUrl(Business $business, string $tableNumber): string
    {
        return self::getBaseUrl() . '/' . $business->slug . '/table/' . $tableNumber;
    }
}
