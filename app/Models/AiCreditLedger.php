<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiCreditLedger extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'business_id',
        'period_ym',
        'allocated_credits',
        'bonus_credits',
        'used_credits',
        'reserved_credits',
    ];

    /**
     * Get attribute cast definitions.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allocated_credits' => 'integer',
            'bonus_credits' => 'integer',
            'used_credits' => 'integer',
            'reserved_credits' => 'integer',
        ];
    }

    /**
     * Compute total available credits including bonus grants.
     */
    public function totalCredits(): int
    {
        return max(0, (int) $this->allocated_credits + (int) $this->bonus_credits);
    }

    /**
     * Compute remaining unreserved credits for the billing period.
     */
    public function remainingCredits(): int
    {
        return max(0, $this->totalCredits() - (int) $this->used_credits - (int) $this->reserved_credits);
    }
}
