<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'name', 'slug', 'currency', 'timezone',
        'locale', 'logo', 'favicon', 'primary_color', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Store $store) {
            if (empty($store->slug)) {
                $store->slug = Str::slug($store->name) . '-' . Str::random(6);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(StoreSetting::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(ProductTag::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings()->where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    public function setSetting(string $key, mixed $value, string $type = 'string'): void
    {
        $this->settings()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type'  => $type,
            ]
        );
    }
}
