<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'yearly_price',
        'currency',
        'product_limit',
        'branch_limit',
        'staff_limit',
        'has_pos',
        'has_inventory',
        'has_online_ordering',
        'has_analytics',
        'has_custom_branding',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'product_limit' => 'integer',
            'branch_limit' => 'integer',
            'staff_limit' => 'integer',
            'has_pos' => 'boolean',
            'has_inventory' => 'boolean',
            'has_online_ordering' => 'boolean',
            'has_analytics' => 'boolean',
            'has_custom_branding' => 'boolean',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
