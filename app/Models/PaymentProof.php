<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PaymentProof extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'business_id',
        'order_id',
        'payment_transaction_id',
        'payment_method',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'encoded_content',
        'customer_reference',
        'customer_note',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'uploaded_ip',
    ];

    protected $hidden = [
        'encoded_content',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'reviewed_at' => 'datetime',
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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    /**
     * Staff user who reviewed this uploaded payment proof.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Alias for reviewedBy relationship.
     */
    public function reviewer(): BelongsTo
    {
        return $this->reviewedBy();
    }

    /**
     * Determine whether raw proof image bytes exist on private local disk or in persistent database storage.
     */
    public function hasStoredBinary(): bool
    {
        if (filled($this->encoded_content)) {
            return true;
        }

        return filled($this->file_path) && Storage::disk('local')->exists($this->file_path);
    }

    /**
     * Resolve raw proof image bytes from private local disk or persistent database backup,
     * re-hydrating the local disk cache automatically after ephemeral container restarts.
     */
    public function resolveBinaryContents(): ?string
    {
        if (filled($this->file_path) && Storage::disk('local')->exists($this->file_path)) {
            $diskBytes = Storage::disk('local')->get($this->file_path);
            if (is_string($diskBytes) && $diskBytes !== '') {
                return $diskBytes;
            }
        }

        if (filled($this->encoded_content)) {
            $decoded = base64_decode((string) $this->encoded_content, true);
            if (is_string($decoded) && $decoded !== '') {
                if (filled($this->file_path)) {
                    try {
                        Storage::disk('local')->put($this->file_path, $decoded);
                    } catch (\Throwable) {
                        // Best-effort local disk cache hydration on ephemeral containers.
                    }
                }

                return $decoded;
            }
        }

        return null;
    }
}
