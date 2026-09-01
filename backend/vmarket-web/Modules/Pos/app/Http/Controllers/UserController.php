<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Seller;
use App\Models\VendorEmployee;
use Modules\Pos\app\Traits\PosAuthTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use PosAuthTrait;

    /**
     * Display all workers, roles, and branch assignments.
     */
    public function index()
    {
        $sellerId = $this->resolveAuthSellerId();
        $users = VendorEmployee::where('seller_id', $sellerId)->orderBy('name')->get();
        $warehouses = Shop::where('seller_id', $sellerId)->where('temporary_close', 0)->get();

        return view('pos::users.index', compact('users', 'warehouses'));
    }

    /**
     * Create a new worker account with assigned role and branch custody.
     */
    public function store(Request $request)
    {
        // [AI] Role-Awareness Gate: Cashiers/employees cannot create or manage other staff accounts
        abort_if(Auth::guard('vendor_employee')->check(), 403, 'Unauthorized. Only business owners can create worker accounts.');

        $request->validate([
            'name'         => 'required|string|max:100',
            'email'        => 'required|email|unique:vendor_employees,email',
            'phone'        => 'required|string|max:20',
            'password'     => 'required|min:6',
            'role'         => 'nullable|string',
            'warehouse_id' => 'nullable|integer',
        ]);

        $sellerId = $this->resolveAuthSellerId();
        $shopId   = $request->warehouse_id ? (int) $request->warehouse_id : null;

        if ($shopId) {
            $branch = DB::table('shops')->where('id', $shopId)->where('seller_id', $sellerId)->first();
            abort_if(!$branch, 403, 'Unauthorized branch selection.');
        }

        $employee = VendorEmployee::create([
            'seller_id'          => $sellerId,
            'name'               => $request->name,
            'phone'              => $request->phone,
            'email'              => strtolower(trim($request->email)),
            'password'           => Hash::make($request->password),
            'status'             => 1,
            'vendor_role_id'     => 1,
            'image'              => 'def.png',
            'assigned_branch_id' => $shopId,
        ]);

        return redirect()->route('pos.users.index')->with('success', "✓ Worker account for {$employee->name} created successfully!");
    }

    public function update(Request $request, $id)
    {
        // [AI] Role-Awareness Gate: Cashiers/employees cannot update other staff accounts
        abort_if(Auth::guard('vendor_employee')->check(), 403, 'Unauthorized. Only business owners can update worker accounts.');

        $sellerId = $this->resolveAuthSellerId();
        $employee = VendorEmployee::where('id', $id)->where('seller_id', $sellerId)->firstOrFail();

        $updateData = [
            'name'  => $request->name ?? $employee->name,
            'phone' => $request->phone ?? $employee->phone,
        ];

        if ($request->has('warehouse_id')) {
            $shopId = (int) $request->warehouse_id;
            if ($shopId > 0) {
                $branch = DB::table('shops')->where('id', $shopId)->where('seller_id', $sellerId)->first();
                abort_if(!$branch, 403, 'Unauthorized branch selection.');
                $updateData['assigned_branch_id'] = $shopId;
            }
        }

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $employee->update($updateData);

        return redirect()->route('pos.users.index')->with('success', "✓ Worker account updated successfully!");
    }

    public function toggleStatus($id)
    {
        abort_if(Auth::guard('vendor_employee')->check(), 403, 'Unauthorized. Only business owners can toggle worker status.');

        $sellerId = $this->resolveAuthSellerId();
        $employee = VendorEmployee::where('id', $id)->where('seller_id', $sellerId)->firstOrFail();
        $employee->status = $employee->status == 1 ? 0 : 1;
        $employee->save();

        return redirect()->route('pos.users.index')->with('success', "Worker status updated.");
    }

    public function resetPassword(Request $request, $id)
    {
        abort_if(Auth::guard('vendor_employee')->check(), 403, 'Unauthorized. Only business owners can reset staff passwords.');

        $request->validate(['password' => 'required|min:6']);
        $sellerId = $this->resolveAuthSellerId();
        $employee = VendorEmployee::where('id', $id)->where('seller_id', $sellerId)->firstOrFail();
        $employee->password = Hash::make($request->password);
        $employee->save();

        return redirect()->route('pos.users.index')->with('success', "Password reset successfully!");
    }
}

