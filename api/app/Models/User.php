<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'phone',
        'is_active', 'last_login_at', 'invitation_token', 'invitation_accepted_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'invitation_token',
    ];

    protected $casts = [
        'email_verified_at'      => 'datetime',
        'last_login_at'          => 'datetime',
        'invitation_accepted_at' => 'datetime',
        'is_active'              => 'boolean',
        'password'               => 'hashed',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
                    ->withPivot(['role', 'is_primary', 'is_active', 'joined_at'])
                    ->withTimestamps();
    }

    public function primaryOrganization(): ?Organization
    {
        return $this->organizations()
                    ->wherePivot('is_primary', true)
                    ->wherePivot('is_active', true)
                    ->first();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Check if user belongs to a given organization.
     */
    public function belongsToOrganization(int|Organization $organization): bool
    {
        $orgId = $organization instanceof Organization ? $organization->id : $organization;

        return $this->organizations()->where('organizations.id', $orgId)->exists();
    }

    /**
     * Get user's role in a specific organization.
     */
    public function roleInOrganization(int|Organization $organization): ?string
    {
        $orgId = $organization instanceof Organization ? $organization->id : $organization;

        return $this->organizations()
                    ->where('organizations.id', $orgId)
                    ->first()
                    ?->pivot
                    ->role;
    }

    /**
     * Check if this user is a Super Admin (global platform role).
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // Gravatar fallback
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
    }
}
