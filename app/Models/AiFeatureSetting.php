<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiFeatureSetting extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'business_id',
        'preferred_language',
        'preferred_tone',
        'provider_override',
        'model_override',
        'temperature',
        'assistant_enabled',
        'menu_creator_enabled',
        'marketing_studio_enabled',
        'forecasting_enabled',
        'insights_enabled',
    ];

    /**
     * Get attribute cast definitions.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'assistant_enabled' => 'boolean',
            'menu_creator_enabled' => 'boolean',
            'marketing_studio_enabled' => 'boolean',
            'forecasting_enabled' => 'boolean',
            'insights_enabled' => 'boolean',
        ];
    }

    /**
     * Determine whether the specified AI feature key is enabled for this business.
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return match ($feature) {
            'assistant' => (bool) $this->assistant_enabled,
            'menu_creator' => (bool) $this->menu_creator_enabled,
            'marketing_studio' => (bool) $this->marketing_studio_enabled,
            'inventory_forecast', 'sales_prediction' => (bool) $this->forecasting_enabled,
            'insights' => (bool) $this->insights_enabled,
            default => true,
        };
    }
}
