<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Printer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'business_id',
        'branch_id',
        'name',
        'printer_type',
        'connection_type',
        'printing_purpose',
        'paper_size',
        'ip_address',
        'port',
        'is_active',
        'is_default',
        'last_used_at',
    ];

    protected $casts = [
        'port' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'branch_id');
    }

    public function isReceiptPrinter(): bool
    {
        return in_array($this->printer_type, ['receipt', 'general'], true) ||
            in_array($this->printing_purpose, ['customer_receipts', 'both'], true);
    }

    public function isKitchenPrinter(): bool
    {
        return in_array($this->printer_type, ['kitchen', 'general'], true) ||
            in_array($this->printing_purpose, ['kitchen_tickets', 'both'], true);
    }
}
