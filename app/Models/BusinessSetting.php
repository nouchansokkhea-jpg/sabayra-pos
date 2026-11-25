<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'business_id',
        'key',
        'value',
    ];

    /**
     * Get the attribute casts for the model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /**
     * Retrieve a typed setting value for a specific business and key.
     */
    public static function getValue(string $businessId, string $key, mixed $default = null): mixed
    {
        $record = static::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('key', $key)
            ->first();

        if (!$record || !is_array($record->value) || !array_key_exists('data', $record->value)) {
            return $default;
        }

        return $record->value['data'];
    }

    /**
     * Store or update a setting value for a specific business and key.
     */
    public static function setValue(string $businessId, string $key, mixed $value): self
    {
        return static::withoutGlobalScopes()->updateOrCreate(
            [
                'business_id' => $businessId,
                'key' => $key,
            ],
            [
                'value' => ['data' => $value],
            ]
        );
    }

    /**
     * Retrieve all keyed settings for a business as an associative map.
     *
     * @return array<string, mixed>
     */
    public static function getAllForBusiness(string $businessId): array
    {
        $records = static::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->get(['key', 'value']);

        $result = [];
        foreach ($records as $record) {
            if (is_array($record->value) && array_key_exists('data', $record->value)) {
                $result[$record->key] = $record->value['data'];
            }
        }

        return $result;
    }
}
