<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Business extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'business_type',
        'tagline',
        'description',
        'logo_url',
        'cover_url',
        'phone',
        'email',
        'telegram_handle',
        'address',
        'city',
        'country',
        'default_currency',
        'exchange_rate_khr',
        'tax_percent',
        'tax_name',
        'tax_inclusive',
        'service_charge_enabled',
        'service_charge_rate',
        'status',
        'theme_settings',
        'opening_hours',
    ];

    protected function casts(): array
    {
        return [
            'theme_settings' => 'array',
            'opening_hours' => 'array',
            'exchange_rate_khr' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_inclusive' => 'boolean',
            'service_charge_enabled' => 'boolean',
            'service_charge_rate' => 'decimal:2',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(BusinessMember::class, 'business_id')->withoutGlobalScopes();
    }

    public function staffMembers(): HasMany
    {
        return $this->hasMany(BusinessMember::class, 'business_id')
            ->withoutGlobalScopes()
            ->where('user_id', '!=', $this->owner_id);
    }

    public function ownerMember(): HasOne
    {
        return $this->hasOne(BusinessMember::class, 'business_id')
            ->withoutGlobalScopes()
            ->where('user_id', $this->owner_id);
    }

    /**
     * Ensure the business has a valid owner membership record.
     */
    public function ensureOwnerMembership(?string $branchId = null): BusinessMember
    {
        return BusinessMember::withoutGlobalScopes()->firstOrCreate(
            [
                'business_id' => $this->id,
                'user_id' => $this->owner_id,
            ],
            [
                'role' => 'owner',
                'branch_id' => $branchId,
                'is_active' => true,
                'joined_at' => $this->created_at ?? now(),
            ]
        );
    }

    public function branches(): HasMany
    {
        return $this->hasMany(BusinessBranch::class, 'business_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'business_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'business_id')->orderBy('sort_order');
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class, 'business_id');
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class, 'business_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'business_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'business_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'business_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'business_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'business_id');
    }

    public function posSessions(): HasMany
    {
        return $this->hasMany(PosSession::class, 'business_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'business_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'business_id')->withoutGlobalScopes();
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'business_id')
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->latest('created_at');
    }

    public function telegramConnection(): HasOne
    {
        return $this->hasOne(TelegramConnection::class, 'business_id')->withoutGlobalScopes();
    }

    public function telegramNotificationLogs(): HasMany
    {
        return $this->hasMany(TelegramNotificationLog::class, 'business_id')->withoutGlobalScopes();
    }

    public function paymentConfig(): HasOne
    {
        return $this->hasOne(BusinessPaymentConfig::class, 'business_id')->withoutGlobalScopes();
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'business_id')->withoutGlobalScopes();
    }

    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class, 'business_id')->withoutGlobalScopes();
    }

    /**
     * Calculate Cambodian Riel equivalent based on business exchange rate.
     */
    public function khrEquivalent(float $usdAmount): float
    {
        $rate = $this->exchange_rate_khr > 0 ? (float) $this->exchange_rate_khr : 4100.00;
        return round($usdAmount * $rate, -2); // Round to nearest 100 Riels
    }

    /**
     * Format a price string in USD.
     */
    public function formatUsd(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }

    /**
     * Format a price string in KHR.
     */
    public function formatKhr(float $amount): string
    {
        return number_format($amount, 0) . ' ៛';
    }
}
