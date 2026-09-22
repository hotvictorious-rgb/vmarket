<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class YourModel
 *
 * @property int $id Primary
 * @property string $name
 * @property string $module_access
 * @property bool $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @package App\Models
 */

class AdminRole extends Model
{
    public const SUPER_ADMIN_ROLE_ID = 1;

    /**
     * [AI] Phase A3: Authoritative 15-role RBAC schema.
     * Maps canonical role identifiers to their granular permission slug sets.
     * Super Admin (id=1) is implicit and bypasses all checks through Admin::isSuperAdmin().
     */
    public const ROLE_DEFINITIONS = [
        'SUPER_ADMIN' => [],
        'ORDER_ADMIN' => ['orders.view', 'orders.cancel', 'orders.view_sensitive', 'pickup.manage'],
        'FINANCE_ADMIN' => ['payments.view', 'payments.reconcile', 'orders.refund', 'cashback.manage'],
        'DELIVERY_ADMIN' => ['delivery.lane.view', 'delivery.lane.manage', 'delivery.fee.update'],
        'BRANCH_MANAGER' => ['orders.view', 'pickup.manage', 'delivery.lane.view'],
        'GEOGRAPHY_ADMIN' => ['geography.manage', 'delivery.lane.view'],
        'MERCHANT_OPS_ADMIN' => ['merchants.view', 'merchants.review', 'merchants.approve'],
        'KYC_MERCHANT_ADMIN' => ['merchants.view', 'merchants.review'],
        'CUSTOMER_SUPPORT_ADMIN' => ['orders.view', 'orders.cancel'],
        'REFUNDS_ADMIN' => ['orders.view', 'orders.refund'],
        'DISPATCH_CONTROLLER' => ['orders.view', 'delivery.lane.view', 'pickup.manage'],
        'PICKUP_OPS_ADMIN' => ['pickup.manage', 'orders.view'],
        'CASHBACK_ADMIN' => ['cashback.manage', 'payments.view'],
        'AUDIT_ADMIN' => ['audit_log.view'],
        'READ_ONLY_AUDITOR' => ['orders.view', 'payments.view', 'cashback.manage', 'audit_log.view'],
    ];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'module_access' => 'string',
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'module_access',
        'status',
    ];

    /**
     * [AI] Phase A3: Decoded granular/coarse permission list.
     */
    public function permissions(): array
    {
        return json_decode($this->module_access ?? '[]', true) ?: [];
    }

    /**
     * [AI] Phase A3: Granular permission check scoped to this role.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

}
