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

    public function index(): RedirectResponse
    {
        return redirect()->route('vendor.employee.list')->with('info', translate('All merchant employee roles are predetermined. Select a role when adding staff.'));
    }

    public function store(Request $request): RedirectResponse
    {
        ToastMagic::error(translate('Custom role creation is disabled. All employee roles are predetermined.'));
        return redirect()->route('vendor.employee.list');
    }

    public function edit(int|string $id): RedirectResponse
    {
        ToastMagic::info(translate('System employee roles are predetermined and immutable.'));
        return redirect()->route('vendor.employee.list');
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        ToastMagic::error(translate('System employee roles are predetermined and cannot be modified.'));
        return redirect()->route('vendor.employee.list');
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'message' => translate('System roles cannot be modified.')], 403);
    }
}
