<?php

namespace App\Http\Controllers\RestAPI\v3\seller;

use App\Http\Controllers\Controller;
use App\Models\VendorEmployee;
use App\Models\VendorRole;
use App\Models\Shop;
use App\Utils\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * [AI] List employees belonging to the authenticated vendor
     */
    public function list(Request $request): JsonResponse
    {
        $seller = $request->seller;
        $employees = VendorEmployee::with('role', 'shop')
            ->where('seller_id', $seller->id)
            ->latest()
            ->get();

        $formatted = $employees->map(function ($emp) {
            return [
                'id' => $emp->id,
                'name' => $emp->name,
                'email' => $emp->email,
                'phone' => $emp->phone,
                'role_id' => $emp->vendor_role_id,
                'role_name' => $emp->role?->name ?? 'Staff',
                'shop_id' => $emp->shop_id,
                'shop_name' => $emp->shop?->name,
                'status' => (bool) $emp->status,
                'created_at' => $emp->created_at?->toIso8601String(),
            ];
        });

        return response()->json($formatted, 200);
    }

    /**
     * [AI] Store new employee under the authenticated vendor
     */
    public function store(Request $request): JsonResponse
    {
        $seller = $request->seller;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|unique:vendor_employees,email',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:vendor_roles,id',
            'shop_id' => 'nullable|exists:shops,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        // Verify role belongs to this seller
        $role = VendorRole::where(['id' => $request->role_id, 'seller_id' => $seller->id])->first();
        if (!$role) {
            return response()->json(['message' => translate('Selected role is invalid or does not belong to your shop.')], 403);
        }

        // Verify shop belongs to this seller if provided
        $shopId = $request->shop_id;
        if ($shopId) {
            $shop = Shop::where(['id' => $shopId, 'seller_id' => $seller->id])->first();
            if (!$shop) {
                return response()->json(['message' => translate('Selected branch is invalid or does not belong to your shop.')], 403);
            }
        } else {
            $shop = Shop::where('seller_id', $seller->id)->first();
            $shopId = $shop?->id;
        }

        $employee = VendorEmployee::create([
            'seller_id' => $seller->id,
            'shop_id' => $shopId,
            'vendor_role_id' => $role->id,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => translate('Employee added successfully.'),
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'role_id' => $employee->vendor_role_id,
                'role_name' => $role->name,
                'shop_id' => $employee->shop_id,
                'status' => (bool)$employee->status,
            ]
        ], 200);
    }

    /**
     * [AI] Toggle status of employee belonging to authenticated vendor
     */
    public function status(Request $request): JsonResponse
    {
        $seller = $request->seller;

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $employee = VendorEmployee::where(['id' => $request->id, 'seller_id' => $seller->id])->first();
        if (!$employee) {
            return response()->json(['message' => translate('Employee not found or unauthorized.')], 404);
        }

        $status = in_array($request->status, [1, '1', true, 'true'], true) ? 1 : 0;
        $employee->status = $status;
        $employee->save();

        return response()->json([
            'status' => true,
            'message' => translate('Employee status updated successfully.'),
        ], 200);
    }

    /**
     * [AI] Delete employee belonging to authenticated vendor
     */
    public function delete(Request $request): JsonResponse
    {
        $seller = $request->seller;

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $employee = VendorEmployee::where(['id' => $request->id, 'seller_id' => $seller->id])->first();
        if (!$employee) {
            return response()->json(['message' => translate('Employee not found or unauthorized.')], 404);
        }

        $employee->delete();

        return response()->json([
            'status' => true,
            'message' => translate('Employee deleted successfully.'),
        ], 200);
    }
}
