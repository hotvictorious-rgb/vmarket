<?php

namespace App\Policies;

use App\Models\Admin;

/**
 * [AI] Class AdminPolicy
 *
 * Phase A3: Zero-Trust Administrative Authorization Policy.
 * Single authoritative Laravel Policy for every admin resource.
 *
 * Authorization model:
 *  - Super Admin (id=1 or role_id=1) bypasses all checks via before().
 *  - Every other ability resolves through Admin::hasExactModuleAccess(),
 *    which requires an EXPLICIT grant (granular slug OR legacy coarse module)
 *    in the role's module_access array. Implied prefix coverage is never
 *    used here, so a read-only grant like 'audit_log.view' can never
 *    escalate into write abilities like staff/role management.
 *
 * Legacy coarse modules are honored as explicit grants for backward
 * compatibility with previously-configured roles (see EmployeeController
 * and module route wiring).
 *
 * Spec Reference: Sections 42, 43; ADMIN_PANEL_ALIGNMENT.md rules.
 */
class AdminPolicy
{
    /**
     * Super Admin bypass.
     */
    public function before(Admin $admin, string $ability): ?bool
    {
        if ($admin->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Staff account creation, editing, permission assignment, and status management.
     */
    public function manageStaff(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('staff.manage')
            || $admin->hasExactModuleAccess('user_section')
            || $admin->hasExactModuleAccess('system_settings');
    }

    /**
     * Custom role creation/editing/status/deletion. Strictly Super Admin.
     */
    public function manageRoles(Admin $admin): bool
    {
        return $admin->isSuperAdmin();
    }

    /**
     * View and search the immutable administrative audit trail.
     */
    public function viewAuditLogs(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('audit_log.view')
            || $admin->hasExactModuleAccess('system_settings');
    }

    /**
     * View sensitive order/customer data.
     */
    public function viewSensitiveOrders(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('orders.view_sensitive')
            || $admin->hasExactModuleAccess('order_management');
    }

    /**
     * View marketplace/order listings (read-only).
     */
    public function viewOrders(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('orders.view')
            || $admin->hasExactModuleAccess('orders.view_sensitive')
            || $admin->hasExactModuleAccess('order_management');
    }

    /**
     * Cancel and manage marketplace orders (write-level).
     */
    public function manageOrders(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('orders.cancel')
            || $admin->hasExactModuleAccess('order_management');
    }

    /**
     * Approve refunds / trigger automated Paystack source refund.
     */
    public function approveRefunds(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('orders.refund')
            || $admin->hasExactModuleAccess('order_management');
    }

    /**
     * Reconcile payment exceptions.
     */
    public function reconcilePayments(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('payments.reconcile')
            || $admin->hasExactModuleAccess('report');
    }

    /**
     * Manage directional delivery lanes.
     */
    public function manageDeliveryLanes(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('delivery.lane.manage')
            || $admin->hasExactModuleAccess('delivery.fee.update')
            || $admin->hasExactModuleAccess('business_settings');
    }

    /**
     * Update canonical geography (Country -> State -> LGA).
     */
    public function manageGeography(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('geography.manage')
            || $admin->hasExactModuleAccess('business_settings');
    }

    /**
     * Approve/suspend/review merchant applications.
     */
    public function manageMerchants(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('merchants.approve')
            || $admin->hasExactModuleAccess('merchants.review')
            || $admin->hasExactModuleAccess('merchants.view');
    }

    /**
     * Oversee in-shop pickup reservations and inspection outcomes.
     */
    public function managePickup(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('pickup.manage')
            || $admin->hasExactModuleAccess('order_management');
    }

    /**
     * View/manage Victorious cashback ledgers.
     */
    public function manageCashback(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('cashback.manage')
            || $admin->hasExactModuleAccess('report');
    }

    /**
     * Manual digital payment status overrides.
     * Phase A7 supersedes this: forced to Paystack server verification;
     * Super Admin only exception path quarantined to the Payment Exception Queue.
     */
    public function overridePaymentStatus(Admin $admin): bool
    {
        return $admin->isSuperAdmin();
    }
}