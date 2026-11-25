<?php

namespace App\Traits;

use App\Models\Business;
use App\Scopes\TenantScope;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
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

    /**
     * Generate a collision-free daily sequential document number scoped to a business.
     */
    public static function generateDailySequenceNumber(
        string $businessId,
        string $column,
        string $prefix,
        int $padLength = 4
    ): string {
        $todayDate = Carbon::now()->format('Ymd');
        $prefixPattern = "{$prefix}-{$todayDate}-";

        $existingNumbers = static::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where($column, 'like', $prefixPattern . '%')
            ->pluck($column);

        $maxSequence = 0;
        foreach ($existingNumbers as $existingNumber) {
            $suffix = substr((string) $existingNumber, strlen($prefixPattern));
            if (ctype_digit($suffix)) {
                $maxSequence = max($maxSequence, (int) $suffix);
            }
        }

        $nextSequence = $maxSequence + 1;

        do {
            $candidate = sprintf('%s%0' . $padLength . 'd', $prefixPattern, $nextSequence);
            $nextSequence++;
        } while (
            static::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->where($column, $candidate)
                ->exists()
        );

        return $candidate;
    }
}
