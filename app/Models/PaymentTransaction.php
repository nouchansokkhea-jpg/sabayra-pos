<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'business_id',
        'order_id',
        'gateway',
        'reference_number',
        'external_transaction_id',
        'khqr_md5',
        'khqr_payload',
        'qr_image_url',
        'checkout_url',
        'amount',
        'currency',
        'status',
        'idempotency_key',
        'expires_at',
        'verified_at',
        'verified_by',
        'gateway_payload',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'gateway_payload' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class, 'payment_transaction_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
