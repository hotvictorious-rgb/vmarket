<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [AI] Class AdminAuditLog
 * Append-only immutable administrative audit log model.
 * Spec Reference: Sections 42, 43
 */
class AdminAuditLog extends Model
{
    protected $table = 'admin_audit_logs';

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
