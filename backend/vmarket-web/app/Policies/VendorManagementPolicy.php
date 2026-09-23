<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Seller;

/**
 * [AI] Class VendorManagementPolicy
 *
 * Zero-Trust Administrative Vendor Authorization Policy.
 * Controls merchant onboarding, status suspension, shop approval, and commission configurations.
 */
class VendorManagementPolicy
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
     * View vendor profiles and shop details.
     */
    public function view(Admin $admin, Seller $seller = null): bool
    {
        return $admin->hasExactModuleAccess('user_section')
            || $admin->hasExactModuleAccess('vendor.view');
    }

    /**
     * Approve, suspend, or update vendor status.
     */
    public function manage(Admin $admin, Seller $seller = null): bool
    {
        return $admin->hasExactModuleAccess('user_section')
            || $admin->hasExactModuleAccess('vendor.manage');
    }
}
