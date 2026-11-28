<?php

namespace App\Services\Business;

use App\Models\Business;
use App\Models\BusinessSetting;

class CategoryExperienceService
{
    /**
     * Configurable module toggles allowed for customizable / optional business workspaces.
     *
     * @var array<int, string>
     */
    public const CONFIGURABLE_MODULES = [
        'pos',
        'inventory',
        'barcode',
        'suppliers',
        'purchase_orders',
        'delivery_zones',
        'service_bookings',
        'reservations',
        'happy_hours',
        'tables',
        'kitchen',
        'recipes',
    ];

    /**
     * Color/template presets matched by default_template keys.
     *
     * @var array<string, array{primary_color: string, secondary_color: string, theme_preset: string, layout: string}>
     */
    public const TEMPLATE_THEMES = [
        'restaurant' => ['primary_color' => '#ea580c', 'secondary_color' => '#0f172a', 'theme_preset' => 'orange', 'layout' => 'modern'],
        'cafe' => ['primary_color' => '#d97706', 'secondary_color' => '#1c1917', 'theme_preset' => 'coffee', 'layout' => 'modern'],
        'milk_tea' => ['primary_color' => '#ec4899', 'secondary_color' => '#18181b', 'theme_preset' => 'orange', 'layout' => 'modern'],
        'bakery' => ['primary_color' => '#eab308', 'secondary_color' => '#292524', 'theme_preset' => 'coffee', 'layout' => 'grid'],
        'supermarket' => ['primary_color' => '#10b981', 'secondary_color' => '#09090b', 'theme_preset' => 'emerald', 'layout' => 'grid'],
        'mini_mart' => ['primary_color' => '#06b6d4', 'secondary_color' => '#0f172a', 'theme_preset' => 'emerald', 'layout' => 'grid'],
        'retail' => ['primary_color' => '#6366f1', 'secondary_color' => '#18181b', 'theme_preset' => 'minimal', 'layout' => 'grid'],
        'fashion' => ['primary_color' => '#a855f7', 'secondary_color' => '#000000', 'theme_preset' => 'luxury', 'layout' => 'grid'],
        'beauty' => ['primary_color' => '#f43f5e', 'secondary_color' => '#1c1917', 'theme_preset' => 'luxury', 'layout' => 'grid'],
        'services' => ['primary_color' => '#3b82f6', 'secondary_color' => '#0f172a', 'theme_preset' => 'minimal', 'layout' => 'list'],
    ];

    /**
     * Return all 12 registered categories and their full configuration metadata.
     *
     * @return array<string, array<string, mixed>>
     */
    public function categories(): array
    {
        return (array) config('business_features.categories', []);
    }

    /**
     * Resolve canonical category key for a business, falling back cleanly to 'restaurant'.
     */
    public function resolveCategoryKey(?Business $business): string
    {
        if (! $business) {
            return 'restaurant';
        }

        $key = (string) ($business->business_type ?: 'restaurant');
        $categories = $this->categories();

        return array_key_exists($key, $categories) ? $key : 'restaurant';
    }

    /**
     * Resolve effective merged configuration for a business including stored custom overrides.
     *
     * @return array<string, mixed>
     */
    public function configFor(?Business $business): array
    {
        $key = $this->resolveCategoryKey($business);
        $base = (array) config("business_features.categories.{$key}", []);

        if ($business) {
            $overrides = BusinessSetting::getValue($business->id, 'category.custom_modules', []);
            if (is_array($overrides) && $overrides !== []) {
                foreach ($overrides as $feature => $enabled) {
                    if (in_array($feature, self::CONFIGURABLE_MODULES, true)) {
                        $base['features'][$feature] = (bool) $enabled;
                    }
                }
            }
        }

        return $base;
    }

    /**
     * Determine whether an operational module/feature is active for this business.
     */
    public function isModuleEnabled(?Business $business, string $feature): bool
    {
        if (! $business) {
            return false;
        }

        $config = $this->configFor($business);
        $features = (array) ($config['features'] ?? []);

        if (! array_key_exists($feature, $features)) {
            return true;
        }

        $categoryAllows = (bool) $features[$feature];
        if (! $categoryAllows) {
            return false;
        }

        if ((bool) config('subscription.plans_enabled', false)) {
            $plan = $business->activeSubscription()->with('plan')->first()?->plan;
            if ($plan) {
                $planGate = match ($feature) {
                    'pos' => (bool) $plan->has_pos,
                    'inventory', 'recipes' => (bool) $plan->has_inventory,
                    default => true,
                };
                if (! $planGate) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Return localized category metadata (English + Khmer names and descriptions).
     *
     * @return array{key: string, name_en: string, name_km: string, display_name: string, description: string, icon: string}
     */
    public function meta(?Business $business): array
    {
        $key = $this->resolveCategoryKey($business);
        $config = $this->configFor($business);
        $isKhmer = app()->getLocale() === 'km';

        $nameEn = (string) ($config['name_en'] ?? 'Business');
        $nameKm = (string) ($config['name_km'] ?? $nameEn);

        return [
            'key' => $key,
            'name_en' => $nameEn,
            'name_km' => $nameKm,
            'display_name' => $isKhmer ? $nameKm : $nameEn,
            'description' => (string) ($isKhmer ? ($config['description_km'] ?? $config['description_en'] ?? '') : ($config['description_en'] ?? '')),
            'icon' => (string) ($config['icon'] ?? 'building'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardConfig(?Business $business): array
    {
        return (array) ($this->configFor($business)['dashboard'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function posConfig(?Business $business): array
    {
        $pos = (array) ($this->configFor($business)['pos'] ?? []);
        if ($business && ($this->resolveCategoryKey($business) === 'other')) {
            $pos['show_table_selector'] = $this->isModuleEnabled($business, 'tables');
            $pos['show_barcode_scanner'] = $this->isModuleEnabled($business, 'barcode');
            $pos['show_service_booking'] = $this->isModuleEnabled($business, 'service_bookings');
        }

        return $pos;
    }

    /**
     * @return array<string, mixed>
     */
    public function storefrontConfig(?Business $business): array
    {
        $storefront = (array) ($this->configFor($business)['storefront'] ?? []);
        if ($business && ($this->resolveCategoryKey($business) === 'other')) {
            if ($this->isModuleEnabled($business, 'service_bookings')) {
                $storefront['mode'] = 'booking';
                $storefront['show_booking_calendar'] = true;
            }
            if ($this->isModuleEnabled($business, 'tables')) {
                $storefront['show_table_banner'] = true;
                $storefront['allowed_order_types'] = ['dine_in', 'takeaway', 'delivery'];
            }
        }

        return $storefront;
    }

    /**
     * @return array<string, mixed>
     */
    public function reportConfig(?Business $business): array
    {
        return (array) ($this->configFor($business)['reports'] ?? []);
    }

    /**
     * @return array<string, string>
     */
    public function terminology(?Business $business): array
    {
        return (array) ($this->configFor($business)['terminology'] ?? []);
    }

    /**
     * Build category default theme settings preserving optional user custom fields.
     *
     * @return array<string, mixed>
     */
    public function defaultThemeForCategory(string $categoryKey): array
    {
        $templateKey = (string) config("business_features.categories.{$categoryKey}.default_template", 'restaurant');
        $palette = self::TEMPLATE_THEMES[$templateKey] ?? self::TEMPLATE_THEMES['restaurant'];

        return [
            'template' => $templateKey,
            'theme_preset' => $palette['theme_preset'],
            'primary_color' => $palette['primary_color'],
            'secondary_color' => $palette['secondary_color'],
            'font' => 'Inter, Kantumruy Pro, sans-serif',
            'layout' => $palette['layout'],
        ];
    }

    /**
     * Initialize sensible category defaults in business_settings upon onboarding or category migration.
     */
    public function initializeDefaultsForBusiness(Business $business, bool $preserveCustomModules = false): void
    {
        $key = $this->resolveCategoryKey($business);
        $config = (array) config("business_features.categories.{$key}", []);
        $features = (array) ($config['features'] ?? []);

        if (! $preserveCustomModules) {
            BusinessSetting::setValue($business->id, 'category.custom_modules', []);
        }

        BusinessSetting::setValue($business->id, 'category.active_type', $key);
        BusinessSetting::setValue($business->id, 'category.initialized_at', now()->toIso8601String());
        BusinessSetting::setValue($business->id, 'general.default_product_type', $config['pos']['default_product_type'] ?? 'standard');
        BusinessSetting::setValue($business->id, 'category.default_features_snapshot', $features);
    }

    /**
     * Return user-hidden dashboard widget keys for widget personalization.
     *
     * @return array<int, string>
     */
    public function hiddenWidgets(Business $business): array
    {
        $hidden = BusinessSetting::getValue($business->id, 'dashboard.hidden_widgets', []);

        return is_array($hidden) ? array_values($hidden) : [];
    }

    /**
     * Compare module changes before switching categories without deleting any existing tenant data.
     *
     * @return array{from: string, to: string, enabled_modules: array<int, string>, disabled_modules: array<int, string>, preserves_data: bool}
     */
    public function diffCategoryChange(Business $business, string $newCategory): array
    {
        $fromKey = $this->resolveCategoryKey($business);
        $fromFeatures = (array) config("business_features.categories.{$fromKey}.features", []);
        $toFeatures = (array) config("business_features.categories.{$newCategory}.features", []);

        $gained = [];
        $removed = [];

        foreach ($toFeatures as $module => $willEnable) {
            $wasEnabled = (bool) ($fromFeatures[$module] ?? false);
            if ($willEnable && ! $wasEnabled) {
                $gained[] = $module;
            } elseif (! $willEnable && $wasEnabled) {
                $removed[] = $module;
            }
        }

        return [
            'from' => $fromKey,
            'to' => $newCategory,
            'enabled_modules' => $gained,
            'disabled_modules' => $removed,
            'preserves_data' => true,
        ];
    }
}
