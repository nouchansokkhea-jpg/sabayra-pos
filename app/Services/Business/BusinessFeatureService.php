<?php

namespace App\Services\Business;

use App\Models\Business;

class BusinessFeatureService
{
    /**
     * Check if a given business type has access to a specific operational feature.
     */
    public static function canAccess(?Business $business, string $feature): bool
    {
        if (!$business) {
            return false;
        }

        $type = $business->business_type ?? $business->category ?? 'restaurant';
        $config = config("business_features.categories.{$type}.features");

        if (is_array($config) && array_key_exists($feature, $config)) {
            return (bool) $config[$feature];
        }

        // Default to true if unconfigured
        return true;
    }

    /**
     * Alias for canAccess.
     */
    public static function isEnabled(?Business $business, string $feature): bool
    {
        return self::canAccess($business, $feature);
    }

    /**
     * Retrieve localized terminology for this business category.
     */
    public static function getTerminology(?Business $business, string $key, string $default): string
    {
        if (!$business) {
            return $default;
        }

        $type = $business->business_type ?? $business->category ?? 'restaurant';
        return config("business_features.categories.{$type}.terminology.{$key}", $default);
    }

    /**
     * Get the default storefront template for this business category.
     */
    public static function getDefaultTemplate(?Business $business): string
    {
        if (!$business) {
            return 'restaurant';
        }

        $type = $business->business_type ?? $business->category ?? 'restaurant';
        return config("business_features.categories.{$type}.default_template", 'restaurant');
    }
}
