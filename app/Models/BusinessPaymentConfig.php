<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class BusinessPaymentConfig extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'business_id',
        'bank_transfer_enabled',
        'bank_name',
        'bank_account_holder',
        'bank_account_number',
        'bank_instructions',
        'bank_reference_instructions',
        'static_qr_enabled',
        'static_qr_label',
        'static_qr_image_path',
        'static_qr_instructions',
        'static_qr_require_proof',
        'cash_enabled',
        'cod_enabled',
        'cod_instructions',
        'aba_payway_enabled',
        'aba_payway_link',
        'aba_merchant_id',
        'aba_api_key',
        'aba_environment',
        'aba_last_tested_at',
        'aba_test_status',
        'aba_test_message',
        'bakong_enabled',
        'bakong_account_id',
        'bakong_merchant_name',
        'bakong_verified',
        'bakong_verified_at',
        'bakong_verification_status',
        'bakong_verification_message',
    ];

    /**
     * Never expose encrypted merchant keys or raw credentials via JSON/array serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'aba_api_key',
    ];

    protected function casts(): array
    {
        return [
            'bank_transfer_enabled' => 'boolean',
            'static_qr_enabled' => 'boolean',
            'static_qr_require_proof' => 'boolean',
            'cash_enabled' => 'boolean',
            'cod_enabled' => 'boolean',
            'aba_payway_enabled' => 'boolean',
            'aba_api_key' => 'encrypted',
            'aba_last_tested_at' => 'datetime',
            'bakong_enabled' => 'boolean',
            'bakong_verified' => 'boolean',
            'bakong_verified_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Resolve a business's payment configuration, hydrating legacy BusinessSetting defaults when needed.
     */
    public static function resolveForBusiness(Business $business): self
    {
        if (Schema::hasTable('business_payment_configs')) {
            $existing = static::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->first();

            if ($existing instanceof self) {
                return $existing;
            }
        }

        $legacyBakongId = BusinessSetting::getValue($business->id, 'payment.bakong_account_id');
        $legacyMerchantName = BusinessSetting::getValue($business->id, 'payment.bakong_merchant_name');
        $legacyKhqrEnabled = BusinessSetting::getValue($business->id, 'payment.khqr_enabled');
        $legacyCashEnabled = BusinessSetting::getValue($business->id, 'payment.cash_enabled', true);

        return new self([
            'business_id' => $business->id,
            'bank_transfer_enabled' => false,
            'static_qr_enabled' => false,
            'static_qr_label' => 'QR Payment',
            'static_qr_require_proof' => true,
            'cash_enabled' => (bool) $legacyCashEnabled,
            'cod_enabled' => (bool) $legacyCashEnabled,
            'aba_payway_enabled' => false,
            'aba_environment' => 'sandbox',
            'aba_test_status' => 'untested',
            'bakong_enabled' => $legacyKhqrEnabled !== null ? (bool) $legacyKhqrEnabled : false,
            'bakong_account_id' => is_string($legacyBakongId) && $legacyBakongId !== '' ? $legacyBakongId : null,
            'bakong_merchant_name' => is_string($legacyMerchantName) && $legacyMerchantName !== '' ? $legacyMerchantName : $business->name,
            'bakong_verified' => false,
            'bakong_verification_status' => 'not_verified',
        ]);
    }

    /**
     * Retrieve or persist a dedicated BusinessPaymentConfig row for the given tenant.
     */
    public static function firstOrCreateForBusiness(Business $business): self
    {
        $resolved = static::resolveForBusiness($business);

        if (! $resolved->exists) {
            $resolved->save();
        }

        return $resolved;
    }

    /**
     * Determine whether Bank Transfer has complete merchant banking details configured.
     */
    public function hasBankTransferDetails(): bool
    {
        return filled($this->bank_name)
            && filled($this->bank_account_holder)
            && filled($this->bank_account_number);
    }

    /**
     * Determine whether Static QR Payment has a valid uploaded QR image and label.
     */
    public function hasStaticQrConfigured(): bool
    {
        return filled($this->static_qr_label) && filled($this->static_qr_image_path);
    }

    /**
     * Determine whether ABA PayWay merchant API credentials (merchant ID + API key) are stored.
     */
    public function hasAbaMerchantApiCredentials(): bool
    {
        return filled($this->aba_merchant_id) && filled($this->aba_api_key);
    }

    /**
     * Determine whether a valid HTTPS ABA PayWay payment link is stored.
     */
    public function hasValidAbaPaywayLink(): bool
    {
        $link = trim((string) ($this->aba_payway_link ?? ''));
        if ($link === '' || ! str_starts_with(strtolower($link), 'https://')) {
            return false;
        }

        $host = strtolower((string) parse_url($link, PHP_URL_HOST));

        return $host === 'payway.com.kh' || str_ends_with($host, '.payway.com.kh');
    }
}
