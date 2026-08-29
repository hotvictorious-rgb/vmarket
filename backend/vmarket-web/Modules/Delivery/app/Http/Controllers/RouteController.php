<?php

namespace Modules\Delivery\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeliveryHub;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Delivery\app\Models\DeliveryRoute;

class RouteController extends Controller
{
    /**
     * [AI] Display corridor route matrix & fee structure.
     */
    public function index(Request $request): View
    {
        $query = DeliveryRoute::with(['originHub.city.state', 'destinationHub.city.state']);

        if ($request->filled('origin_id')) {
            $query->where('origin_hub_id', $request->origin_id);
        }

        if ($request->filled('destination_id')) {
            $query->where('destination_hub_id', $request->destination_id);
        }

        $routes = $query->latest()->paginate(15)->appends($request->all());
        $hubs = DeliveryHub::where('is_active', 1)->orderBy('name')->get();

        return view('delivery::routes.index', compact('routes', 'hubs'));
    }

    /**
     * [AI] Store a new corridor route.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'origin_hub_id' => 'required|exists:delivery_hubs,id|different:destination_hub_id',
            'destination_hub_id' => 'required|exists:delivery_hubs,id',
            'customer_fee' => 'required|numeric|min:0',
            'rider_payout' => 'required|numeric|min:0',
            'logistics_partner_margin' => 'required|numeric|min:0',
            'estimated_hours' => 'required|numeric|min:0.5',
            'transit_type' => 'required|in:intra_city,inter_city_linehaul,regional',
        ]);

        $exists = DeliveryRoute::where('origin_hub_id', $request->origin_hub_id)
            ->where('destination_hub_id', $request->destination_hub_id)
            ->exists();

        if ($exists) {
            Toastr::error('This corridor route already exists! You can edit its rates instead.');
            return back();
        }

        DeliveryRoute::create([
            'origin_hub_id' => $request->origin_hub_id,
            'destination_hub_id' => $request->destination_hub_id,
            'customer_fee' => $request->customer_fee,
            'rider_payout' => $request->rider_payout,
            'logistics_partner_margin' => $request->logistics_partner_margin,
            'estimated_hours' => $request->estimated_hours,
            'transit_type' => $request->transit_type,
            'is_active' => true,
        ]);

        Toastr::success('Corridor route created successfully!');
        return redirect()->route('delivery.routes.index');
    }

    /**
     * [AI] Update route fee and transit parameters.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $route = DeliveryRoute::findOrFail($id);

        $request->validate([
            'customer_fee' => 'required|numeric|min:0',
            'rider_payout' => 'required|numeric|min:0',
            'logistics_partner_margin' => 'required|numeric|min:0',
            'estimated_hours' => 'required|numeric|min:0.5',
            'transit_type' => 'required|in:intra_city,inter_city_linehaul,regional',
        ]);

        $route->update([
            'customer_fee' => $request->customer_fee,
            'rider_payout' => $request->rider_payout,
            'logistics_partner_margin' => $request->logistics_partner_margin,
            'estimated_hours' => $request->estimated_hours,
            'transit_type' => $request->transit_type,
        ]);

        Toastr::success('Corridor route rates updated successfully!');
        return back();
    }

    /**
     * [AI] Toggle active status.
     */
    public function toggleStatus(Request $request): JsonResponse
    {
        $route = DeliveryRoute::findOrFail($request->id);
        $route->is_active = !$route->is_active;
        $route->save();

        return response()->json([
            'status' => true,
            'is_active' => $route->is_active,
            'message' => 'Corridor status updated successfully.',
        ]);
    }
}
