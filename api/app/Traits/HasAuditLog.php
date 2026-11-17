<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

/**
 * Automatically writes audit log entries on model create, update, and delete.
 *
 * Sensitive fields (password, tokens) are stripped before logging.
 */
trait HasAuditLog
{
    public static function bootHasAuditLog(): void
    {
        static::created(function ($model) {
            static::writeAuditLog('created', $model, [], $model->getAttributes());
        });

        static::updated(function ($model) {
            static::writeAuditLog(
                'updated',
                $model,
                $model->getOriginal(),
                $model->getChanges()
            );
        });

        static::deleted(function ($model) {
            static::writeAuditLog('deleted', $model, $model->getAttributes(), []);
        });
    }

    protected static function writeAuditLog(
        string $action,
        $model,
        array $oldValues,
        array $newValues
    ): void {
        $sensitiveFields = ['password', 'remember_token', 'invitation_token'];

        $oldValues = array_diff_key($oldValues, array_flip($sensitiveFields));
        $newValues = array_diff_key($newValues, array_flip($sensitiveFields));

        AuditLog::create([
            'organization_id' => $model->organization_id ?? null,
            'user_id'         => auth()->id(),
            'action'          => $action,
            'auditable_type'  => get_class($model),
            'auditable_id'    => $model->id,
            'old_values'      => empty($oldValues) ? null : $oldValues,
            'new_values'      => empty($newValues) ? null : $newValues,
            'ip_address'      => Request::ip(),
            'user_agent'      => Request::userAgent(),
        ]);
    }
}
