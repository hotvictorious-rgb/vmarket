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
            $employeeId = session('vendor_employee_id') ?? (session('vendor_employee_data')['id'] ?? null);
            if (!$employeeId) {
                session()->forget(['is_vendor_employee', 'vendor_employee_id', 'vendor_employee_data', 'vendor_employee_role']);
                auth('seller')->logout();
                return redirect()->route('vendor.auth.login');
            }

            // Always reload employee and role directly from database to enforce immediate revocation
            $employee = \App\Models\VendorEmployee::with(['role', 'seller'])->find($employeeId);
            if (!$employee || !$employee->status || !$employee->seller || $employee->seller->status !== 'approved') {
                session()->forget(['is_vendor_employee', 'vendor_employee_id', 'vendor_employee_data', 'vendor_employee_role']);
                auth('seller')->logout();
                ToastMagic::error(translate('Access Denied: Your employee account has been deactivated or removed.'));
                return redirect()->route('vendor.auth.login');
            }

            // Refresh session cache with authoritative DB record
            session([
                'vendor_employee_id' => $employee->id,
                'vendor_employee_data' => $employee->toArray(),
                'vendor_employee_role' => $employee->role ? $employee->role->toArray() : [],
            ]);

            // 1. Strict Owner-Only Boundary: Employees cannot touch employee/role management or financial settings
            if ($request->is('*vendor/employee*') || $request->is('*seller/employee*') ||
                $request->is('*vendor/roles*') || $request->is('*seller/roles*') ||
                $request->is('*vendor/custom-role*') || $request->is('*seller/custom-role*') ||
                $request->is('*vendor/withdraw*') || $request->is('*vendor/delivery-man/withdraw*') || $request->is('*seller/withdraw*') ||
                $request->is('*vendor/payment-info*') || $request->is('*seller/payment-info*') ||
                $request->is('*vendor/shop/update-bank*') || $request->is('*seller/shop/update-bank*') ||
                $request->is('*vendor/business-settings*') || $request->is('*seller/business-settings*') ||
                $request->is('*vendor/profile/delete*') || $request->is('*seller/profile/delete*')) {

                ToastMagic::error(translate('Access Denied: This action is restricted exclusively to the primary shop owner.'));
                return redirect()->route('vendor.dashboard.index');
            }

            // 2. Branch Security Isolation: Check target shop if employee is branch-restricted
            if (!empty($employee->shop_id)) {
                $targetShopId = $request->header('X-Branch-ID') ?? $request->input('shop_id');
                if ($targetShopId && !$employee->canAccessShop((int) $targetShopId)) {
                    ToastMagic::error(translate('Access Denied: You are not authorized to access this physical branch.'));
                    return redirect()->route('vendor.dashboard.index');
                }

                $orderId = $request->route('id') ?? $request->input('order_id');
                if ($orderId && is_numeric($orderId)) {
                    $order = \App\Models\Order::find((int) $orderId);
                    if ($order && ($order->order_type === 'pickup' || $order->order_type === 'in_house_pickup')) {
                        $reservation = \App\Models\PickupReservation::where('order_id', $order->id)->first();
                        if ($reservation && !$employee->canAccessShop((int) $reservation->shop_id)) {
                            ToastMagic::error(translate('Access Denied: This pickup order belongs to another branch.'));
                            return redirect()->route('vendor.dashboard.index');
                        }
                    }
                }
            }

            // 3. Owner-Only Enclaves (Strictly denied to all vendor employees)
            if (
                $request->is('*vendor/employee*') || $request->is('*seller/employee*') ||
                $request->is('*vendor/business-settings*') || $request->is('*seller/business-settings*') ||
                $request->is('*vendor/withdraw*') || $request->is('*seller/withdraw*') ||
                $request->is('*vendor/wallet*') || $request->is('*seller/wallet*') ||
                $request->is('*vendor/bank*') || $request->is('*seller/bank*') ||
                $request->is('*vendor/payout*') || $request->is('*seller/payout*') ||
                $request->is('*vendor/kyc*') || $request->is('*seller/kyc*') ||
                $request->is('*vendor/shop/update*') || $request->is('*seller/shop-info-update*')
            ) {
                ToastMagic::error(translate('Access Denied: Only shop owners can access this administrative function.'));
                return redirect()->route('vendor.dashboard.index');
            }

            // 4. Module permission check (explicit or deduced from route prefix)
            if (!$module) {
                if ($request->is('*vendor/orders*') || $request->is('*seller/orders*')) {
                    $module = 'order';
                } elseif ($request->is('*vendor/products*') || $request->is('*seller/products*')) {
                    $module = 'product';
                } elseif ($request->is('*vendor/pos*') || $request->is('*seller/pos*')) {
                    $module = 'pos';
                } elseif ($request->is('*vendor/refund*') || $request->is('*seller/refund*')) {
                    $module = 'refund';
                } elseif ($request->is('*vendor/pickup*') || $request->is('*seller/pickup*')) {
                    $module = 'pickup';
                } elseif ($request->is('*vendor/messages*') || $request->is('*seller/messages*')) {
                    $module = 'message';
                } elseif ($request->is('*vendor/report*') || $request->is('*seller/report*')) {
                    $module = 'report';
                } elseif ($request->is('*vendor/coupon*') || $request->is('*seller/coupon*')) {
                    $module = 'coupon';
                } elseif ($request->is('*vendor/clearance-sale*') || $request->is('*seller/clearance-sale*')) {
                    $module = 'clearance_sale';
                } elseif ($request->is('*vendor/delivery-man*') || $request->is('*seller/delivery-man*')) {
                    $module = 'delivery_man';
                }
            }

            // 5. Allow safe utility routes (dashboard, logout, profile view)
            $isSafeUtilityRoute = $request->is('*vendor/dashboard*') || 
                                  $request->is('*vendor/auth/logout*') || 
                                  $request->is('*vendor/profile*');

            if ($module) {
                $variants = [$module, $module . 's', rtrim($module, 's'), $module . '_management'];
                $hasAccess = false;
                foreach ($variants as $v) {
                    if ($employee->hasModuleAccess($v)) {
                        $hasAccess = true;
                        break;
                    }
                }

                if (!$hasAccess) {
                    ToastMagic::error(translate('Access Denied: You do not have permission to access the ' . $module . ' module.'));
                    return redirect()->route('vendor.dashboard.index');
                }
            } elseif (!$isSafeUtilityRoute) {
                // [AI] Default-Deny: Unmapped or unknown routes are rejected for employees
                ToastMagic::error(translate('Access Denied: Your employee role is not authorized to access this area.'));
                return redirect()->route('vendor.dashboard.index');
            }
        }

        return $next($request);
    }
}
