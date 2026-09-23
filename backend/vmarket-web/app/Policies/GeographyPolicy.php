<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\DeliveryLane;

/**
 * [AI] Class GeographyPolicy
 *
 * Zero-Trust Administrative Geography & Delivery Lane Policy.
 * Controls access to country, state, LGA, and directional delivery lane configurations.
 */
class GeographyPolicy
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
     * View geography setup and delivery lanes.
     */
    public function view(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('system_settings')
            || $admin->hasExactModuleAccess('geography.view');
    }

    /**
     * Modify geography settings or delivery lanes.
     */
    public function manage(Admin $admin): bool
    {
        return $admin->hasExactModuleAccess('system_settings')
            || $admin->hasExactModuleAccess('geography.manage');
    }
}
