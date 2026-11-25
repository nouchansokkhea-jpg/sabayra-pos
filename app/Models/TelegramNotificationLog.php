<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramNotificationLog extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'business_id',
        'telegram_connection_id',
        'event_type',
        'status',
        'recipient_masked',
        'message_preview',
        'error_message',
        'sent_at',
    ];

    /**
     * Get the attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(TelegramConnection::class, 'telegram_connection_id');
    }
}
