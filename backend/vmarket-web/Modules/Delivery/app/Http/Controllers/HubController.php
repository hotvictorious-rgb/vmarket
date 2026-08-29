<?php

namespace Modules\Delivery\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCity;
use App\Models\DeliveryHub;
use App\Models\DeliveryState;
use App\Models\Shop;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HubController extends Controller
{
    /**
     * [AI] Display list of official logistics hubs.
     */
    public function index(Request $request): View
    {
        $query = DeliveryHub::with(['city.state']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhereHas('city', function ($cq) use ($s) {
                      $cq->where('name', 'like', "%{$s}%");
                  });
            });
        }

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        $hubs = $query->latest()->paginate(15)->appends($request->all());
        $states = DeliveryState::where('is_active', 1)->get();
        $cities = DeliveryCity::where('is_active', 1)->get();

        return view('delivery::hubs.index', compact('hubs', 'states', 'cities'));
    }

    /**
     * [AI] Show form to create a new logistics hub.
     */
    public function create(): View
    {
        $states = DeliveryState::where('is_active', 1)->get();
        return view('delivery::hubs.create', compact('states'));
    }

    /**
     * [AI] Store newly created hub.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'city_id' => 'required|exists:delivery_cities,id',
            'name' => 'required|string|max:150',
            'type' => 'required|in:landmark,motor_park',
            'base_shipping_cost' => 'required|numeric|min:0',
            'rider_delivery_fee' => 'required|numeric|min:0',
            'estimated_delivery_time' => 'nullable|string|max:100',
        ]);

        DeliveryHub::create([
            'city_id' => $request->city_id,
            'name' => $request->name,
            'type' => $request->type,
            'base_shipping_cost' => $request->base_shipping_cost,
            'rider_delivery_fee' => $request->rider_delivery_fee,
            'estimated_delivery_time' => $request->estimated_delivery_time ?? '1 - 3 Hours',
            'is_active' => true,
        ]);

        Toastr::success('Logistics hub created successfully!');
        return redirect()->route('delivery.hubs.index');
    }

    /**
     * [AI] Show hub details & attached vendor shops.
     */
    public function show(int $id): View
    {
        $hub = DeliveryHub::with(['city.state'])->findOrFail($id);
        $attachedShops = Shop::where('delivery_hub_id', $id)->with(['seller'])->paginate(15);

        return view('delivery::hubs.show', compact('hub', 'attachedShops'));
    }

    /**
     * [AI] Update hub.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $hub = DeliveryHub::findOrFail($id);

        $request->validate([
            'city_id' => 'required|exists:delivery_cities,id',
            'name' => 'required|string|max:150',
            'type' => 'required|in:landmark,motor_park',
            'base_shipping_cost' => 'required|numeric|min:0',
            'rider_delivery_fee' => 'required|numeric|min:0',
            'estimated_delivery_time' => 'nullable|string|max:100',
        ]);

        $hub->update([
            'city_id' => $request->city_id,
            'name' => $request->name,
            'type' => $request->type,
            'base_shipping_cost' => $request->base_shipping_cost,
            'rider_delivery_fee' => $request->rider_delivery_fee,
            'estimated_delivery_time' => $request->estimated_delivery_time ?? '1 - 3 Hours',
        ]);

        Toastr::success('Logistics hub updated successfully!');
        return back();
    }

    /**
     * [AI] Toggle active status.
     */
    public function toggleStatus(Request $request): JsonResponse
    {
        $hub = DeliveryHub::findOrFail($request->id);
        $hub->is_active = !$hub->is_active;
        $hub->save();

        return response()->json([
            'status' => true,
            'is_active' => $hub->is_active,
            'message' => 'Hub status updated successfully.',
        ]);
    }

    /**
     * [AI] Dynamic AJAX selector: Get LGAs / Cities for a State.
     */
    public function ajaxGetCities(int $stateId): JsonResponse
    {
        $cities = DeliveryCity::where('state_id', $stateId)->where('is_active', 1)->get(['id', 'name']);
        return response()->json($cities);
    }
}
