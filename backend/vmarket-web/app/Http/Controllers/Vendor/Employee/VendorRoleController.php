<?php

namespace App\Http\Controllers\Vendor\Employee;

use App\Http\Controllers\Controller;
use App\Models\VendorRole;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorRoleController extends Controller
{
    public const MODULE_PERMISSIONS = [
        'order_management'   => 'Orders & Packing Slips',
        'product_management' => 'Product Catalog & Pricing',
        'pos_management'     => 'In-Store POS System',
        'report_management'  => 'Sales & Stock Reports',
    ];

    public function index(): View
    {
        $sellerId = auth('seller')->id();
        $roles = VendorRole::where('seller_id', $sellerId)->latest()->paginate(10);
        $modules = self::MODULE_PERMISSIONS;

        return view('vendor-views.employee.roles.index', compact('roles', 'modules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'modules' => 'required|array|min:1',
        ], [
            'name.required' => translate('Role name is required'),
            'modules.required' => translate('Please select at least one permission module'),
        ]);

        $sellerId = auth('seller')->id();

        VendorRole::create([
            'seller_id' => $sellerId,
            'name' => $request->name,
            'module_access' => $request->modules,
            'status' => true,
        ]);

        ToastMagic::success(translate('Employee role created successfully'));
        return back();
    }

    public function edit(int|string $id): View|RedirectResponse
    {
        $sellerId = auth('seller')->id();
        $role = VendorRole::where('seller_id', $sellerId)->find($id);

        if (!$role) {
            ToastMagic::error(translate('Role not found'));
            return redirect()->route('vendor.employee-role.index');
        }

        $modules = self::MODULE_PERMISSIONS;
        return view('vendor-views.employee.roles.edit', compact('role', 'modules'));
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'modules' => 'required|array|min:1',
        ]);

        $sellerId = auth('seller')->id();
        $role = VendorRole::where('seller_id', $sellerId)->find($id);

        if (!$role) {
            ToastMagic::error(translate('Role not found'));
            return redirect()->route('vendor.employee-role.index');
        }

        $role->update([
            'name' => $request->name,
            'module_access' => $request->modules,
        ]);

        ToastMagic::success(translate('Role updated successfully'));
        return redirect()->route('vendor.employee-role.index');
    }

    public function status(Request $request): JsonResponse
    {
        $sellerId = auth('seller')->id();
        $role = VendorRole::where('seller_id', $sellerId)->find($request->id);

        if (!$role) {
            return response()->json(['success' => false, 'message' => translate('Role not found')], 404);
        }

        $role->status = $request->status;
        $role->save();

        return response()->json(['success' => true, 'message' => translate('Role status updated')]);
    }
}
