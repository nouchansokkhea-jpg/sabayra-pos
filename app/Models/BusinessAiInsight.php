<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessAiInsight extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'business_id',
        'insight_type',
        'title',
        'summary',
        'recommended_action',
        'payload',
        'metrics_json',
        'priority',
        'period_start',
        'period_end',
        'status',
        'reviewed_by',
        'reviewed_at',
        'generated_at',
    ];

    /**
     * Get attribute cast definitions.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'metrics_json' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'generated_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get the user who reviewed or actioned this insight.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
