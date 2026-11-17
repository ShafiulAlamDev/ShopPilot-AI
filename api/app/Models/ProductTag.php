<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProductTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'slug',
    ];

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProductTag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The store this tag belongs to.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Products associated with this tag.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_tag',
            'tag_id',
            'product_id'
        );
    }
}
