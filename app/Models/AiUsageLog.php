<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'business_id',
        'user_id',
        'request_uuid',
        'feature',
        'provider',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'credits_charged',
        'estimated_cost_usd',
        'latency_ms',
        'status',
        'error_code',
        'error_message',
    ];

    /**
     * Get attribute cast definitions.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'credits_charged' => 'integer',
            'estimated_cost_usd' => 'decimal:6',
            'latency_ms' => 'integer',
        ];
    }

    /**
     * Get the user who triggered this AI request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
