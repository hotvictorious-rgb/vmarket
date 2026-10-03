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

                // 1. Strict Owner-Only Boundary: Employees cannot perform withdrawals, banking, or employee administration
                if (
                    $request->is('*seller/withdraw*') ||
                    $request->is('*seller/shop/update-bank*') ||
                    $request->is('*seller/payment-info*') ||
                    $request->is('*seller/business-settings*') ||
                    $request->is('*seller/employee*') ||
                    $request->is('*seller/roles*') ||
                    $request->is('*seller/custom-role*') ||
                    $request->is('*seller/profile/delete*')
                ) {
                    return response()->json([
                        'auth-001' => translate('Access Denied: This operation is restricted exclusively to the primary shop owner.')
                    ], 403);
                }

                // 2. Branch Security Isolation:
                $targetShopId = $request->header('X-Branch-ID') ?? $request->input('shop_id') ?? $request->route('shop_id');
                if (!empty($employee->shop_id)) {
                    if ($targetShopId && !$employee->canAccessShop((int)$targetShopId)) {
                        return response()->json([
                            'auth-001' => translate('Access Denied: You are not authorized to access or modify this physical branch.')
                        ], 403);
                    }
                    // Auto-bind employee's assigned branch to enforce downstream isolation
                    $request->headers->set('X-Branch-ID', (string) $employee->shop_id);
                    $request->merge(['shop_id' => (int) $employee->shop_id]);

                    // Resource-level branch ownership enforcement:
                    $pathBase = basename($request->path());
                    // A) Product resource check
                    $targetProductId = $request->route('id') ?? $request->route('product_id') ?? $request->input('product_id') ?? $request->input('id') ?? (is_numeric($pathBase) && ($request->is('*seller/product*') || $request->is('*seller/products*')) ? $pathBase : null);
                    if ($targetProductId && ($request->is('*seller/product*') || $request->is('*seller/products*'))) {
                        $targetProduct = \App\Models\Product::find($targetProductId);
                        if ($targetProduct && !empty($targetProduct->shop_id) && (int)$targetProduct->shop_id !== (int)$employee->shop_id) {
                            return response()->json([
                                'auth-001' => translate('Access Denied: You are not authorized to access or modify resources belonging to another branch.')
                            ], 403);
                        }
                    }

                    // B) Order resource check
                    $targetOrderId = $request->route('id') ?? $request->route('order_id') ?? $request->input('order_id') ?? $request->input('id') ?? (is_numeric($pathBase) && ($request->is('*seller/order*') || $request->is('*seller/orders*')) ? $pathBase : null);
                    if ($targetOrderId && ($request->is('*seller/order*') || $request->is('*seller/orders*'))) {
                        $pickupReservation = \App\Models\PickupReservation::where('order_id', $targetOrderId)->first();
                        if ($pickupReservation && !empty($pickupReservation->shop_id) && (int)$pickupReservation->shop_id !== (int)$employee->shop_id) {
                            return response()->json([
                                'auth-001' => translate('Access Denied: You are not authorized to access or modify orders belonging to another branch.')
                            ], 403);
                        }
                        $otherBranchProductCount = \App\Models\OrderDetail::where('order_id', $targetOrderId)
                            ->whereHas('product', function ($q) use ($employee) {
                                $q->whereNotNull('shop_id')->where('shop_id', '!=', $employee->shop_id);
                            })->count();
                        if ($otherBranchProductCount > 0) {
                            return response()->json([
                                'auth-001' => translate('Access Denied: You are not authorized to access or modify orders belonging to another branch.')
                            ], 403);
                        }
                    }
                }

                // 3. Module Permission Enforcement:
                $module = null;
                if ($request->is('*seller/products*') || $request->is('*seller/product*')) {
                    $module = 'product';
                } elseif ($request->is('*seller/orders*') || $request->is('*seller/order*')) {
                    $module = 'order';
                } elseif ($request->is('*seller/pos*')) {
                    $module = 'pos';
                } elseif ($request->is('*seller/refund*')) {
                    $module = 'refund';
                } elseif ($request->is('*seller/pickup*')) {
                    $module = 'pickup';
                } elseif ($request->is('*seller/messages*') || $request->is('*seller/chat*')) {
                    $module = 'message';
                }

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
                        return response()->json([
                            'auth-001' => translate("Access Denied: Your employee role does not have permission to access the {$module} module.")
                        ], 403);
                    }
                }

                $request['seller'] = $employee->seller;
                $request['vendor_employee'] = $employee;
                $request['employee_shop_id'] = $employee->shop_id;
                $request['is_vendor_employee'] = true;
                $request->merge([
                    'seller' => $employee->seller,
                    'vendor_employee' => $employee,
                    'is_vendor_employee' => true,
                    'employee_shop_id' => $employee->shop_id
                ]);
                return $next($request);
            }
        }

        return response()->json([
            'auth-001' => translate('Your existing session token does not authorize you any more')
        ], 401);
    }
}
