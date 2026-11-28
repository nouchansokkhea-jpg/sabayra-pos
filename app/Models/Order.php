<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'business_id',
        'branch_id',
        'order_number',
        'order_type',
        'table_id',
        'customer_id',
        'assigned_staff_id',
        'customer_name',
        'customer_phone',
        'delivery_address',
        'subtotal',
        'discount_amount',
        'tax_name',
        'tax_rate',
        'tax_inclusive',
        'tax_amount',
        'service_charge_enabled',
        'service_charge_rate',
        'service_charge_amount',
        'total_amount',
        'total_khr',
        'currency',
        'exchange_rate_khr',
        'status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'gateway_transaction_id',
        'payment_verified_at',
        'payment_verified_by',
        'payment_collected_at',
        'payment_collected_by',
        'paid_at',
        'notes',
        'scheduled_for',
        'pos_metadata',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_inclusive' => 'boolean',
            'tax_amount' => 'decimal:2',
            'service_charge_enabled' => 'boolean',
            'service_charge_rate' => 'decimal:2',
            'service_charge_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'total_khr' => 'decimal:2',
            'exchange_rate_khr' => 'decimal:2',
            'payment_verified_at' => 'datetime',
            'payment_collected_at' => 'datetime',
            'paid_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'pos_metadata' => 'array',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'branch_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Staff member or specialist assigned to this order or service appointment.
     */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(BusinessMember::class, 'assigned_staff_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id')->orderBy('created_at', 'asc');
    }

    public function posTransaction(): HasOne
    {
        return $this->hasOne(PosTransaction::class, 'order_id');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'order_id')->latest();
    }

    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class, 'order_id')->latest();
    }

    /**
     * Alias for paymentProofs relationship used across views and order services.
     */
    public function proofs(): HasMany
    {
        return $this->paymentProofs();
    }

    public function paymentVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function paymentCollectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_collected_by');
    }

    /**
     * Return human-readable display label for the order payment method.
     */
    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'bank_transfer' => 'Bank Transfer',
            'static_qr' => 'QR Payment',
            'cod' => 'Cash on Delivery',
            'cash' => $this->order_type === 'delivery' ? 'Cash on Delivery' : 'Cash',
            'aba_payway' => 'ABA PayWay',
            'khqr', 'bakong_khqr' => 'Bakong KHQR',
            'card' => 'Card',
            default => strtoupper((string) ($this->payment_method ?? 'UNSPECIFIED')),
        };
    }

    /**
     * Return human-readable label for the order payment status.
     */
    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'unpaid' => 'Unpaid',
            'pending_verification' => 'Pending Verification',
            'pending_collection' => 'Pending Collection',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'rejected' => 'Rejected',
            'refunded' => 'Refunded',
            default => ucfirst(str_replace('_', ' ', (string) ($this->payment_status ?? 'unpaid'))),
        };
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
