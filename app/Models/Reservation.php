<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'business_id',
        'branch_id',
        'booking_type',
        'booking_code',
        'customer_name',
        'customer_phone',
        'customer_email',
        'party_size',
        'reservation_date',
        'reservation_time',
        'duration_minutes',
        'end_time',
        'price_usd',
        'table_id',
        'service_product_id',
        'staff_member_id',
        'special_requests',
        'status',
        'payment_status',
    ];

    /**
     * Get attribute cast definitions.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'party_size' => 'integer',
            'reservation_date' => 'date',
            'duration_minutes' => 'integer',
            'price_usd' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'branch_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    /**
     * Get the service catalog item booked for service appointments.
     */
    public function serviceProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'service_product_id');
    }

    /**
     * Get the assigned staff specialist for service appointments.
     */
    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(BusinessMember::class, 'staff_member_id');
    }
}
