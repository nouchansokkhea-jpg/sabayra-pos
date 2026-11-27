<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'business_id',
        'user_id',
        'title',
        'preferred_language',
        'message_count',
        'last_message_at',
    ];

    /**
     * Get attribute cast definitions.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'message_count' => 'integer',
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * Get the staff or owner user participating in this conversation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get chronological messages within this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')->orderBy('created_at');
    }
}
