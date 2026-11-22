<?php

namespace App\Traits;

use App\Models\Business;
use App\Scopes\TenantScope;
use App\Services\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the trait and apply the tenant scope and auto-assignment.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (empty($model->business_id) && TenantContext::check()) {
                $model->business_id = TenantContext::id();
            }
        });
    }

    /**
     * Get the business that owns this model.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
