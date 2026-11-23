<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HappyHour extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'business_id',
        'name',
        'discount_percent',
        'start_time',
        'end_time',
        'days_of_week',
        'applicable_categories',
        'is_active',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'days_of_week' => 'array',
        'applicable_categories' => 'array',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $currentDay = strtolower(now()->format('D')); // mon, tue, wed...
        if (!empty($this->days_of_week) && !in_array($currentDay, array_map('strtolower', $this->days_of_week), true)) {
            return false;
        }

        $currentTime = now()->format('H:i:s');
        return $currentTime >= $this->start_time && $currentTime <= $this->end_time;
    }
}
