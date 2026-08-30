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
        // 1. Check Super Admin / Admin Staff
        if (Auth::guard('admin')->check()) {
            return $next($request);
        }

        // 2. Check Store Cashier Employee
        if (Auth::guard('vendor_employee')->check()) {
            return $next($request);
        }

        // 3. Check Verified / Unverified Merchant Owner
        if (Auth::guard('seller')->check()) {
            $seller = Auth::guard('seller')->user();
            if ($seller && $seller->status != 'suspended') {
                return $next($request);
            }
        }

        // Unauthenticated access
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Unauthorized POS access.'], 401);
        }

        return redirect()->route('vendor.auth.login');
    }
}
