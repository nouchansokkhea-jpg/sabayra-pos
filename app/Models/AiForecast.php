<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiForecast extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'business_id',
        'user_id',
        'forecast_type',
        'horizon',
        'status',
        'historical_days_available',
        'data_points_count',
        'confidence_level',
        'statistical_payload',
        'ai_explanation',
        'limitations',
        'assumptions_json',
        'usage_log_id',
        'generated_at',
        'expires_at',
    ];

    /**
     * Get attribute cast definitions.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'historical_days_available' => 'integer',
            'data_points_count' => 'integer',
            'statistical_payload' => 'array',
            'assumptions_json' => 'array',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user who generated this forecast.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI usage record for the forecast narrative.
     */
    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(AiUsageLog::class, 'usage_log_id');
    }
}
