<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\Request;

/**
 * [AI] Class AdminAuditService
 * Central domain service to record immutable administrative audit entries.
 * Spec Reference: Sections 42, 43
 */
class AdminAuditService
{
    /**
     * Record an administrative action audit entry.
     *
     * @param string $action Action slug (e.g. 'delivery_lane.disabled', 'fee.updated')
     * @param string|null $resourceType Target model/resource class
     * @param mixed|null $resourceId Target resource primary key
     * @param array|null $beforeState State before mutation
     * @param array|null $afterState State after mutation
     * @param string|null $reason Mandatory or optional justification
     * @return AdminAuditLog
     */
    public static function log(
        string $action,
        ?string $resourceType = null,
        mixed $resourceId = null,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?string $reason = null
    ): AdminAuditLog {
        $admin = auth('admin')->user();

        return AdminAuditLog::create([
            'admin_id' => $admin?->id,
            'admin_name' => $admin ? ($admin->name ?? ($admin->f_name . ' ' . $admin->l_name)) : 'System/CLI',
            'admin_role' => $admin?->role?->name ?? ($admin?->admin_role_id == 1 ? 'Super Admin' : 'Admin Staff'),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId ? (string) $resourceId : null,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'reason' => $reason ?? Request::input('reason'),
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
        ]);
    }
}
