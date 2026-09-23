<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Order;

/**
 * [AI] Class OrderPolicy
 *
 * Zero-Trust Administrative Order Authorization Policy.
 * Enforces permission checks for order status updates, fulfillment, and refunds.
 */
class OrderPolicy
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
     * View orders listing and order details.
     */
    public function view(Admin $admin, Order $order = null): bool
    {
        return $admin->hasExactModuleAccess('order_management')
            || $admin->hasExactModuleAccess('order.view');
    }

    /**
     * Update order status or details.
     */
    public function update(Admin $admin, Order $order): bool
    {
        return $admin->hasExactModuleAccess('order_management')
            || $admin->hasExactModuleAccess('order.update');
    }

    /**
     * Process order refund.
     */
    public function refund(Admin $admin, Order $order): bool
    {
        return $admin->hasExactModuleAccess('order_management')
            || $admin->hasExactModuleAccess('refund_management');
    }
}
