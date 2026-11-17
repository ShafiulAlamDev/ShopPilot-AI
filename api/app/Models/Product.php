<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'short_description',
        'price',
        'compare_price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'track_stock',
        'status',
        'is_featured',
        'weight',
        'weight_unit',
        'meta_title',
        'meta_description',
        'ai_description_generated',
    ];

    protected $casts = [
        'price'                   => 'decimal:2',
        'compare_price'           => 'decimal:2',
        'cost_price'              => 'decimal:2',
        'weight'                  => 'decimal:2',
        'stock_quantity'          => 'integer',
        'low_stock_threshold'     => 'integer',
        'track_stock'             => 'boolean',
        'is_featured'             => 'boolean',
        'ai_description_generated'=> 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . $product->store_id;
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The store this product belongs to.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * The category this product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * All images for this product, ordered by sort_order.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * The primary image for this product.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Tags associated with this product.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductTag::class,
            'product_tag',
            'product_id',
            'tag_id'
        );
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope to only active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to only featured products.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to only in-stock products.
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Scope to products belonging to a specific store.
     */
    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Determine whether this product is currently in stock.
     * Returns true if stock tracking is disabled OR stock_quantity > 0.
     */
    public function isInStock(): bool
    {
        return !$this->track_stock || $this->stock_quantity > 0;
    }

    /**
     * Determine whether this product is at a low stock level.
     * Returns true only when stock is tracked, quantity is above 0, and at/below the threshold.
     */
    public function isLowStock(): bool
    {
        return $this->track_stock
            && $this->stock_quantity <= $this->low_stock_threshold
            && $this->stock_quantity > 0;
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Calculate the discount percentage relative to the compare (original) price.
     * Returns null when no compare_price is set.
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->compare_price || $this->compare_price <= 0) {
            return null;
        }

        return (int) round(
            (($this->compare_price - $this->price) / $this->compare_price) * 100
        );
    }
}
