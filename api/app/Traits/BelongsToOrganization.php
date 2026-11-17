<?php

namespace App\Traits;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Automatically scopes Eloquent queries to the resolved organization context.
 *
 * Add `use BelongsToOrganization;` to any tenant-scoped model.
 * The SetOrganizationContext middleware binds 'current_organization_id'
 * to the IoC container, which this trait reads automatically.
 */
trait BelongsToOrganization
{
    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    public static function bootBelongsToOrganization(): void
    {
        // Apply organization scope when context is set
        static::addGlobalScope('organization', function (Builder $builder) {
            $organizationId = app('current_organization_id');

            if ($organizationId) {
                $builder->where(
                    (new static)->getTable() . '.organization_id',
                    $organizationId
                );
            }
        });

        // Auto-assign organization_id on create
        static::creating(function ($model) {
            if (empty($model->organization_id)) {
                $model->organization_id = app('current_organization_id');
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
}
