<?php

namespace App\Services\Business;

use App\Models\Business;

class BusinessFeatureService
{
    /**
     * Check if a given business category has access to a specific operational feature.
     */
    public static function canAccess(?Business $business, string $feature): bool
    {
        return app(CategoryExperienceService::class)->isModuleEnabled($business, $feature);
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
        if (! $business) {
            return $default;
        }

        $terminology = app(CategoryExperienceService::class)->terminology($business);

        return (string) ($terminology[$key] ?? $default);
    }

    /**
     * Get the default storefront template for this business category.
     */
    public static function getDefaultTemplate(?Business $business): string
    {
        if (! $business) {
            return 'restaurant';
        }

        $config = app(CategoryExperienceService::class)->configFor($business);

        return (string) ($config['default_template'] ?? 'restaurant');
    }
}
