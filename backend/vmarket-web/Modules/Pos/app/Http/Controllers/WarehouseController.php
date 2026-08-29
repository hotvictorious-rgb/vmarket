<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * [AI] WarehouseController — POS branch/shop management.
 * Ported from Hysam standalone WarehouseController.
 *
 * In the unified system, Hysam "warehouses" = Vmarket "shops".
 * All branch operations use the `shops` table, scoped to seller_id.
 */
class WarehouseController extends Controller
{
    protected function resolveAuthSellerId(): int
    {
        if (Auth::guard('vendor_employee')->check()) {
            return (int) Auth::guard('vendor_employee')->user()->seller_id;
        }
        return (int) Auth::guard('seller')->id();
    }

    public function index()
    {
        $sellerId = $this->resolveAuthSellerId();
        $branches = DB::table('shops')->where('seller_id', $sellerId)->orderBy('name')->paginate(20);
        return view('pos::warehouses.index', compact('branches'));
    }

    public function create()
    {
        return view('pos::warehouses.create');
    }

    public function store(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone'   => 'nullable|string|max:20',
        ]);

        $url = Str::slug($request->name . '-' . Str::random(4));
        DB::table('shops')->insert([
            'seller_id'  => $sellerId,
            'name'       => $request->name,
            'url'        => $url,
            'address'    => $request->address,
            'contact'    => $request->phone,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('pos.warehouses.index')->with('success', "✓ Branch [{$request->name}] created.");
    }

    public function edit(int $id)
    {
        $sellerId = $this->resolveAuthSellerId();
        // [AI] IDOR: scope to this seller
        $branch   = DB::table('shops')->where('id', $id)->where('seller_id', $sellerId)->first();
        abort_if(!$branch, 404);
        return view('pos::warehouses.edit', compact('branch'));
    }

    public function update(Request $request, int $id)
    {
        $sellerId = $this->resolveAuthSellerId();
        $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $affected = DB::table('shops')
            ->where('id', $id)
            ->where('seller_id', $sellerId)
            ->update([
                'name'       => $request->name,
                'address'    => $request->address,
                'contact'    => $request->phone,
                'updated_at' => now(),
            ]);

        abort_if($affected === 0, 403, 'Unauthorized.');
        return redirect()->route('pos.warehouses.index')->with('success', "✓ Branch updated.");
    }

    public function destroy(int $id)
    {
        $sellerId = $this->resolveAuthSellerId();
        $affected = DB::table('shops')->where('id', $id)->where('seller_id', $sellerId)->delete();
        abort_if($affected === 0, 403, 'Unauthorized.');
        return redirect()->route('pos.warehouses.index')->with('success', 'Branch deleted.');
    }

    public function show(int $id)
    {
        $sellerId = $this->resolveAuthSellerId();
        $branch   = DB::table('shops')->where('id', $id)->where('seller_id', $sellerId)->first();
        abort_if(!$branch, 404);
        return view('pos::warehouses.show', compact('branch'));
    }
}
