<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'domain', 'logo', 'email',
        'phone', 'website', 'description', 'plan', 'is_active', 'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings'  => 'array',
    ];

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Organization $organization) {
            if (empty($organization->slug)) {
                $organization->slug = static::generateUniqueSlug($organization->name);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public static function generateUniqueSlug(string $name): string
    {
        $slug  = Str::slug($name);
        $count = static::withTrashed()->where('slug', 'like', "{$slug}%")->count();

        return $count > 0 ? "{$slug}-{$count}" : $slug;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_users')
                    ->withPivot(['role', 'is_primary', 'is_active', 'joined_at'])
                    ->withTimestamps();
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function primaryStore(): HasOne
    {
        return $this->hasOne(Store::class)->where('is_active', true)->oldest();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }
}
