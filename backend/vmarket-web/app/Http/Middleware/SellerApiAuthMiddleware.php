<?php

namespace App\Http\Middleware;

use App\Models\Seller;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SellerApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $token = explode(' ', $request->header('authorization'));
        if (count($token) > 1 && strlen($token[1]) > 30) {
            $seller = Seller::where(['auth_token' => $token['1']])->first();
            if (isset($seller)) {
                if ($seller->status !== 'approved') {
                    return response()->json([
                        'auth-001' => translate('Your account is not approved or has been suspended.')
                    ], 403);
                }
                $request['seller'] = $seller;
                return $next($request);
            }

            // Check if token belongs to an active Vendor Employee
            $employee = \App\Models\VendorEmployee::with('seller', 'role', 'shop')->where(['auth_token' => $token['1']])->first();
            if (isset($employee)) {
                if (!$employee->status) {
                    return response()->json([
                        'auth-001' => translate('Your employee account has been deactivated by the shop owner.')
                    ], 403);
                }
                if (!$employee->seller || $employee->seller->status !== 'approved') {
                    return response()->json([
                        'auth-001' => translate('The associated vendor shop is not approved yet or has been suspended.')
                    ], 403);
                }

                // [AI] Branch Security Isolation:
                // If route/payload targets a specific shop_id, verify employee is authorized for that branch
                $targetShopId = $request->input('shop_id') ?? $request->route('shop_id');
                if ($targetShopId && !$employee->canAccessShop((int)$targetShopId)) {
                    return response()->json([
                        'auth-001' => translate('Access Denied: You are not authorized to access or modify this physical branch.')
                    ], 403);
                }

                $request['seller'] = $employee->seller;
                $request['vendor_employee'] = $employee;
                $request['employee_shop_id'] = $employee->shop_id;
                return $next($request);
            }
        }

        return response()->json([
            'auth-001' => translate('Your existing session token does not authorize you any more')
        ], 401);
    }
}
