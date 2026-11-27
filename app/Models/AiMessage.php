<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'business_id',
        'conversation_id',
        'user_id',
        'role',
        'content',
        'language_detected',
        'tool_calls_json',
        'tool_results_json',
        'data_sources_json',
        'provider',
        'model',
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
            'tool_calls_json' => 'array',
            'tool_results_json' => 'array',
            'data_sources_json' => 'array',
        ];
    }

    /**
     * Get the parent conversation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    /**
     * Get the user associated with the message, if any.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI usage record backing this assistant reply.
     */
    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(AiUsageLog::class, 'usage_log_id');
    }
}
