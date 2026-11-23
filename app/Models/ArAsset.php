<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArAsset extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'business_id',
        'product_id',
        'title',
        'asset_url',
        'preview_image_url',
        'file_size_bytes',
        'format',
        'is_active',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
