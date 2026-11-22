<?php

namespace App\Services\Tenant;

use App\Models\Business;

class TenantContext
{
    protected static ?Business $currentTenant = null;

    /**
     * Set the current tenant.
     */
    public static function set(?Business $business): void
    {
        static::$currentTenant = $business;
    }

    /**
     * Get the current active tenant model.
     */
    public static function get(): ?Business
    {
        return static::$currentTenant;
    }

    /**
     * Get the UUID of the current active tenant.
     */
    public static function id(): ?string
    {
        return static::$currentTenant?->id;
    }

    /**
     * Determine if a tenant is currently active.
     */
    public static function check(): bool
    {
        return static::$currentTenant !== null;
    }

    /**
     * Clear the current tenant context.
     */
    public static function clear(): void
    {
        static::$currentTenant = null;
    }
}
