<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneratedContent extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'business_id',
        'user_id',
        'feature',
        'content_type',
        'platform',
        'product_id',
        'language',
        'tone',
        'title',
        'input_payload',
        'output_text',
        'structured_output',
        'status',
        'usage_log_id',
    ];

    /**
     * Get attribute cast definitions.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_payload' => 'array',
            'structured_output' => 'array',
        ];
    }

    /**
     * Get the user who generated this content.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the linked product when applicable.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the AI usage record for this generation.
     */
    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(AiUsageLog::class, 'usage_log_id');
    }
}
