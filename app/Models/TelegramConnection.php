<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramConnection extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'business_id',
        'user_id',
        'connection_mode',
        'bot_token',
        'bot_username',
        'chat_id',
        'telegram_user_id',
        'telegram_username',
        'telegram_display_name',
        'is_active',
        'notifications_enabled',
        'connected_at',
        'last_notification_at',
        'last_tested_at',
        'last_error',
    ];

    /**
     * Never expose encrypted secrets or raw chat identifiers in arrays/JSON.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'bot_token',
        'chat_id',
    ];

    /**
     * Get the attribute casts for encrypted credentials and timestamps.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bot_token' => 'encrypted',
            'chat_id' => 'encrypted',
            'is_active' => 'boolean',
            'notifications_enabled' => 'boolean',
            'connected_at' => 'datetime',
            'last_notification_at' => 'datetime',
            'last_tested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(TelegramNotificationLog::class, 'telegram_connection_id');
    }

    /**
     * Return a masked version of the linked Telegram chat ID for safe UI display.
     */
    public function getMaskedChatIdAttribute(): ?string
    {
        $chatId = $this->chat_id;
        if (empty($chatId)) {
            return null;
        }

        $length = strlen($chatId);
        if ($length <= 4) {
            return str_repeat('•', max(2, $length));
        }

        $prefix = str_starts_with($chatId, '-') ? substr($chatId, 0, 2) : substr($chatId, 0, 1);

        return $prefix . str_repeat('•', max(4, $length - strlen($prefix) - 3)) . substr($chatId, -3);
    }

    /**
     * Resolve the active bot token depending on official vs custom bot configuration.
     */
    public function resolveEffectiveBotToken(): ?string
    {
        if ($this->connection_mode === 'custom_bot') {
            return $this->bot_token;
        }

        $platformToken = config('services.telegram.bot_token');

        return is_string($platformToken) && $platformToken !== '' ? $platformToken : null;
    }
}
