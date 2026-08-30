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
 * Now supports dynamic location hierarchy: Country ➔ State ➔ LGA (City) ➔ Hub.
 */
use Modules\Pos\app\Traits\PosAuthTrait;

class WarehouseController extends Controller
{
    use PosAuthTrait;

    public function index()
    {
        $sellerId = $this->resolveAuthSellerId();
        $branches = DB::table('shops')
            ->leftJoin('delivery_states', 'shops.state_id', '=', 'delivery_states.id')
            ->leftJoin('delivery_cities', 'shops.lga_id', '=', 'delivery_cities.id')
            ->leftJoin('delivery_hubs', 'shops.hub_id', '=', 'delivery_hubs.id')
            ->where('shops.seller_id', $sellerId)
            ->select('shops.*', 'delivery_states.name as state_name', 'delivery_cities.name as lga_name', 'delivery_hubs.name as hub_name')
            ->orderBy('shops.name')
            ->paginate(20);
        return view('pos::warehouses.index', compact('branches'));
    }

    public function create()
    {
        $states = DB::table('delivery_states')->where('is_active', 1)->orderBy('name')->get();
        return view('pos::warehouses.create', compact('states'));
    }

    public function store(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'nullable|string|max:20',
            'country'  => 'required|string|max:100',
            'state_id' => 'required|integer|exists:delivery_states,id',
            'lga_id'   => 'required|integer|exists:delivery_cities,id',
            'hub_id'   => 'required|integer|exists:delivery_hubs,id',
            'address'  => 'required|string|max:500',
        ]);

        $url = Str::slug($request->name . '-' . Str::random(4));
        DB::table('shops')->insert([
            'seller_id'  => $sellerId,
            'name'       => $request->name,
            'url'        => $url,
            'address'    => $request->address,
            'contact'    => $request->phone,
            'country'    => $request->country,
            'state_id'   => $request->state_id,
            'lga_id'     => $request->lga_id,
            'hub_id'     => $request->hub_id,
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

        $states = DB::table('delivery_states')->where('is_active', 1)->orderBy('name')->get();
        $cities = $branch->state_id ? DB::table('delivery_cities')->where('state_id', $branch->state_id)->where('is_active', 1)->orderBy('name')->get() : collect();
        $hubs   = $branch->lga_id ? DB::table('delivery_hubs')->where('city_id', $branch->lga_id)->where('is_active', 1)->orderBy('name')->get() : collect();

        return view('pos::warehouses.edit', compact('branch', 'states', 'cities', 'hubs'));
    }

    public function update(Request $request, int $id)
    {
        $sellerId = $this->resolveAuthSellerId();
        $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'nullable|string|max:20',
            'country'  => 'required|string|max:100',
            'state_id' => 'required|integer|exists:delivery_states,id',
            'lga_id'   => 'required|integer|exists:delivery_cities,id',
            'hub_id'   => 'required|integer|exists:delivery_hubs,id',
            'address'  => 'required|string|max:500',
        ]);

        $affected = DB::table('shops')
            ->where('id', $id)
            ->where('seller_id', $sellerId)
            ->update([
                'name'       => $request->name,
                'address'    => $request->address,
                'contact'    => $request->phone,
                'country'    => $request->country,
                'state_id'   => $request->state_id,
                'lga_id'     => $request->lga_id,
                'hub_id'     => $request->hub_id,
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
        $branch   = DB::table('shops')
            ->leftJoin('delivery_states', 'shops.state_id', '=', 'delivery_states.id')
            ->leftJoin('delivery_cities', 'shops.lga_id', '=', 'delivery_cities.id')
            ->leftJoin('delivery_hubs', 'shops.hub_id', '=', 'delivery_hubs.id')
            ->where('shops.id', $id)
            ->where('shops.seller_id', $sellerId)
            ->select('shops.*', 'delivery_states.name as state_name', 'delivery_cities.name as lga_name', 'delivery_hubs.name as hub_name')
            ->first();
        abort_if(!$branch, 404);
        return view('pos::warehouses.show', compact('branch'));
    }

    /**
     * [AI] AJAX Location Lookup Methods (Scoped under Merchant/Seller session)
     */
    public function getCitiesAjax(int $state_id)
    {
        $cities = DB::table('delivery_cities')
            ->where('state_id', $state_id)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();
        return response()->json($cities);
    }

    public function getHubsAjax(int $city_id)
    {
        $hubs = DB::table('delivery_hubs')
            ->where('city_id', $city_id)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();
        return response()->json($hubs);
    }
}
