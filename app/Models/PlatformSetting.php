<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class PlatformSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'key',
        'value',
        'is_encrypted',
        'description',
        'updated_by',
    ];

    protected $hidden = [
        'value',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Retrieve a typed platform-level setting value (decrypting encrypted secrets automatically).
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('platform_settings')) {
            return $default;
        }

        $record = static::where('key', $key)->first();
        if (! $record || $record->value === null) {
            return $default;
        }

        if ($record->is_encrypted) {
            try {
                $decrypted = Crypt::decryptString($record->value);

                return $decrypted !== '' ? $decrypted : $default;
            } catch (\Throwable) {
                return $default;
            }
        }

        $decoded = json_decode($record->value, true);
        if (is_array($decoded) && array_key_exists('data', $decoded)) {
            return $decoded['data'];
        }

        return $default;
    }

    /**
     * Persist or update a platform-level setting value safely.
     */
    public static function setValue(
        string $key,
        mixed $value,
        bool $encrypted = false,
        ?string $description = null,
        ?int $updatedBy = null
    ): self {
        $serialized = null;

        if ($encrypted) {
            $plain = trim((string) ($value ?? ''));
            $serialized = $plain !== '' ? Crypt::encryptString($plain) : null;
        } else {
            $serialized = json_encode(['data' => $value]);
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $serialized,
                'is_encrypted' => $encrypted,
                'description' => $description,
                'updated_by' => $updatedBy,
            ]
        );
    }

    /**
     * Determine whether Bakong KHQR checkout is enabled platform-wide.
     */
    public static function isBakongEnabled(): bool
    {
        $configured = static::getValue('payments.bakong_enabled');

        if ($configured === null) {
            return (bool) config('services.bakong.enabled', true);
        }

        return (bool) $configured;
    }

    /**
     * Determine whether ABA PayWay integration is enabled platform-wide.
     */
    public static function isAbaPayWayEnabled(): bool
    {
        $configured = static::getValue('payments.aba_payway_enabled');

        if ($configured === null) {
            return (bool) config('services.aba_payway.enabled', true);
        }

        return (bool) $configured;
    }

    /**
     * Resolve the configured NBC Bakong Open API base URL.
     */
    public static function resolveBakongApiBaseUrl(): string
    {
        $stored = static::getValue('payments.bakong_api_base_url');
        $url = is_string($stored) && trim($stored) !== ''
            ? trim($stored)
            : (string) config('services.bakong.api_base_url', 'https://api-bakong.nbc.gov.kh');

        return rtrim($url, '/');
    }

    /**
     * Resolve the NBC Bakong Open API Bearer token without exposing raw secrets in views/logs.
     */
    public static function resolveBakongApiToken(): ?string
    {
        $stored = static::getValue('payments.bakong_api_token');
        if (is_string($stored) && trim($stored) !== '') {
            return trim($stored);
        }

        $envToken = config('services.bakong.api_token');

        return is_string($envToken) && trim($envToken) !== '' ? trim($envToken) : null;
    }
}
