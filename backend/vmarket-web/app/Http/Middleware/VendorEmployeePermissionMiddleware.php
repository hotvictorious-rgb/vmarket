<?php

namespace App\Http\Middleware;

use Closure;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorEmployeePermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $module = null): Response
    {
        if (session('is_vendor_employee')) {
            $employee = session('vendor_employee_data');
            $role = session('vendor_employee_role');

            // Hard security barrier: Vendor employees cannot access bank info, withdrawals, or sensitive business settings
            if ($request->is('*seller/withdraw*') || 
                $request->is('*seller/payment-info*') || 
                $request->is('*seller/shop/update-bank*') ||
                $request->is('*seller/business-settings*')) {
                
                ToastMagic::error(translate('Access Denied: Financial & payout settings are restricted to the primary shop owner.'));
                return redirect()->route('vendor.dashboard.index');
            }

            // Check module-specific permission if specified
            if ($module && !empty($role['module_access'])) {
                $accessList = is_array($role['module_access']) ? $role['module_access'] : (json_decode($role['module_access'], true) ?? []);
                if (!in_array($module, $accessList)) {
                    ToastMagic::error(translate('Access Denied: You do not have permission to access this module.'));
                    return redirect()->route('vendor.dashboard.index');
                }
            }
        }

        return $next($request);
    }
}
