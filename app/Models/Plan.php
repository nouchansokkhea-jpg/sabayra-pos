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
        'has_ai_assistant',
        'has_ai_menu_creator',
        'has_ai_marketing',
        'has_ai_forecasting',
        'ai_monthly_credits',
        'ai_max_tokens_per_request',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    /**
     * Get attribute cast definitions.
     *
     * @return array<string, string>
     */
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
            'has_ai_assistant' => 'boolean',
            'has_ai_menu_creator' => 'boolean',
            'has_ai_marketing' => 'boolean',
            'has_ai_forecasting' => 'boolean',
            'ai_monthly_credits' => 'integer',
            'ai_max_tokens_per_request' => 'integer',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Determine whether this subscription plan unlocks the given AI Center feature.
     */
    public function allowsAiFeature(string $feature): bool
    {
        return match ($feature) {
            'assistant', 'insights' => (bool) $this->has_ai_assistant,
            'menu_creator' => (bool) $this->has_ai_menu_creator,
            'marketing_studio' => (bool) $this->has_ai_marketing,
            'inventory_forecast', 'sales_prediction' => (bool) $this->has_ai_forecasting,
            default => false,
        };
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
