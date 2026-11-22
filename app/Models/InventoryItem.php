<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'business_id',
        'name_en',
        'name_km',
        'sku',
        'unit',
        'current_stock',
        'low_stock_threshold',
        'unit_cost',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:3',
            'low_stock_threshold' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'inventory_item_id')->latest('created_at');
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->low_stock_threshold;
    }

    public function getLocalizedNameAttribute(): string
    {
        if (app()->getLocale() === 'km' && !empty($this->name_km)) {
            return $this->name_km;
        }
        return $this->name_en;
    }
}
