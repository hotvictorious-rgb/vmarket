<?php

namespace App\Policies;

use App\Models\Admin;

/**
 * [AI] Class FinancePolicy
 *
 * Zero-Trust Administrative Financial Authorization Policy.
 * Enforces permissions for merchant payouts, withdrawals, transaction ledgers, and financial settings.
 */
class FinancePolicy
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
     * View financial transactions and revenue reports.
     */
    public function view(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('report')
            || $admin->hasExactModuleAccess('business_section')
            || $admin->hasExactModuleAccess('finance.view');
    }

    /**
     * Approve or reject vendor withdrawal requests.
     */
    public function manageWithdrawals(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('user_section')
            || $admin->hasExactModuleAccess('finance.withdraw');
    }
}
