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
     * Create a new worker account with assigned role and permissions.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:seller_employees,email',
            'phone'    => 'required|string|max:20',
            'password' => 'required|min:6',
            'role'     => 'required|string',
        ]);

        $sellerId = $this->resolveAuthSellerId();
        $shopId   = $request->warehouse_id ? (int) $request->warehouse_id : null;

        $employee = VendorEmployee::create([
            'seller_id'    => $sellerId,
            'name'         => $request->name,
            'phone'        => $request->phone,
            'email'        => strtolower(trim($request->email)),
            'password'     => Hash::make($request->password),
            'status'       => 1,
            'seller_role_id' => 1,
        ]);

        return redirect()->route('pos.users.index')->with('success', "✓ Worker account for {$employee->name} created successfully!");
    }

    public function update(Request $request, $id)
    {
        $sellerId = $this->resolveAuthSellerId();
        $employee = VendorEmployee::where('id', $id)->where('seller_id', $sellerId)->firstOrFail();

        $employee->update([
            'name'  => $request->name ?? $employee->name,
            'phone' => $request->phone ?? $employee->phone,
        ]);

        if ($request->filled('password')) {
            $employee->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('pos.users.index')->with('success', "✓ Worker account updated successfully!");
    }

    public function toggleStatus($id)
    {
        $sellerId = $this->resolveAuthSellerId();
        $employee = VendorEmployee::where('id', $id)->where('seller_id', $sellerId)->firstOrFail();
        $employee->status = $employee->status == 1 ? 0 : 1;
        $employee->save();

        return redirect()->route('pos.users.index')->with('success', "Worker status updated.");
    }

    public function resetPassword(Request $request, $id)
    {
        $request->validate(['password' => 'required|min:6']);
        $sellerId = $this->resolveAuthSellerId();
        $employee = VendorEmployee::where('id', $id)->where('seller_id', $sellerId)->firstOrFail();
        $employee->password = Hash::make($request->password);
        $employee->save();

        return redirect()->route('pos.users.index')->with('success', "Password reset successfully!");
    }
}
