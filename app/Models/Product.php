<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'business_id',
        'category_id',
        'name_en',
        'name_km',
        'slug',
        'sku',
        'barcode',
        'description_en',
        'description_km',
        'product_type',
        'base_price',
        'cost_price',
        'discount_price',
        'primary_image_url',
        'is_available',
        'is_featured',
        'track_inventory',
        'stock_quantity',
        'low_stock_threshold',
        'unit',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'stock_quantity' => 'decimal:2',
            'low_stock_threshold' => 'decimal:2',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'track_inventory' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(ProductAddon::class, 'product_addon_pivot', 'product_id', 'addon_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }

    public function recipe(): HasOne
    {
        return $this->hasOne(Recipe::class, 'product_id');
    }

    /**
     * Get the active selling price (considers discount if active).
     */
    public function getEffectivePriceAttribute(): float
    {
        if ($this->discount_price !== null && $this->discount_price > 0 && $this->discount_price < $this->base_price) {
            return (float) $this->discount_price;
        }

        return (float) $this->base_price;
    }

    /**
     * Get the localized product name.
     */
    public function getLocalizedNameAttribute(): string
    {
        if (app()->getLocale() === 'km' && !empty($this->name_km)) {
            return $this->name_km;
        }
        return $this->name_en;
    }

    /**
     * Get the localized product description.
     */
    public function getLocalizedDescriptionAttribute(): ?string
    {
        if (app()->getLocale() === 'km' && !empty($this->description_km)) {
            return $this->description_km;
        }
        return $this->description_en;
    }
}
