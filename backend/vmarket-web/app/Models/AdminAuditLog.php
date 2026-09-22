<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * [AI] Class AdminAuditLog
 * Append-only immutable administrative audit log model.
 * Spec Reference: Sections 42, 43
 */
class AdminAuditLog extends Model
{
    protected $table = 'admin_audit_logs';

    /**
     * [AI] Append-only immutability enforcement.
     * Audit records are evidence and can never be edited or removed
     * through any code path (Eloquent mass update, force delete, or event).
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('AdminAuditLog is append-only and cannot be updated.');
    }

    public function delete(): bool
    {
        throw new LogicException('AdminAuditLog is append-only and cannot be deleted.');
    }

    public function forceDelete(): bool
    {
        throw new LogicException('AdminAuditLog is append-only and cannot be deleted.');
    }

    protected static function boot(): void
    {
        parent::boot();
        static::updating(function () {
            throw new LogicException('AdminAuditLog is append-only and cannot be updated.');
        });
        static::deleting(function () {
            throw new LogicException('AdminAuditLog is append-only and cannot be deleted.');
        });
    }

    protected $fillable = [
        'admin_id',
        'admin_name',
        'admin_role',
        'action',
        'resource_type',
        'resource_id',
        'before_state',
        'after_state',
        'reason',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
