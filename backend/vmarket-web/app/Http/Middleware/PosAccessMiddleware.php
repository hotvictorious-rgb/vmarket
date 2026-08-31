<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PosAccessMiddleware
{
    /**
     * Handle an incoming request for the integrated POS module.
     * Allows authenticated Super Admins, Sellers (Merchants), and Vendor Employees (Cashiers).
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // 1. Check Store Cashier Employee
        if (Auth::guard('vendor_employee')->check()) {
            $employee = Auth::guard('vendor_employee')->user();
            session([
                'is_super_admin' => false,
                'seller_id'      => $employee->seller_id,
                'user_role'      => 'staff',
                'user_name'      => $employee->name ?? 'Cashier',
                'user_email'     => $employee->email ?? '',
            ]);
            return $next($request);
        }

        // 2. Check Verified / Unverified Merchant Owner
        if (Auth::guard('seller')->check()) {
            $seller = Auth::guard('seller')->user();
            if ($seller && $seller->status != 'suspended') {
                session([
                    'is_super_admin' => false,
                    'seller_id'      => $seller->id,
                    'user_role'      => $seller->status === 'approved' ? 'verified_merchant' : 'unverified_merchant',
                    'seller_status'  => $seller->status,
                    'user_name'      => ($seller->f_name . ' ' . $seller->l_name),
                    'user_email'     => $seller->email,
                ]);
                return $next($request);
            }
        }

        // 3. Check Super Admin / Admin Staff
        if (Auth::guard('admin')->check()) {
            $adminUser = Auth::guard('admin')->user();
            session([
                'is_super_admin' => true,
                'user_role'      => 'admin',
                'user_name'      => $adminUser->name ?? 'Super Admin',
                'user_email'     => $adminUser->email ?? 'admin@admin.com',
            ]);
            return $next($request);
        }

        // Unauthenticated access
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Unauthorized POS access.'], 401);
        }

        return redirect()->route('vendor.auth.login');
    }
}
