<?php

namespace App\Http\Controllers\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCity;
use App\Models\DeliveryHub;
use App\Models\DeliveryState;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryHubController extends Controller
{
    /**
     * Display the Geographic Hubs Management View
     */
    public function index(Request $request): View
    {
        $states = DeliveryState::withCount('cities')->latest()->paginate(15, ['*'], 'state_page');
        $cities = DeliveryCity::with('state')->withCount(['landmarks', 'motorParks'])->latest()->paginate(15, ['*'], 'city_page');
        
        $hubQuery = DeliveryHub::with('city.state');
        if ($request->has('hub_type') && in_array($request->hub_type, ['landmark', 'motor_park'])) {
            $hubQuery->where('type', $request->hub_type);
        }
        if ($request->has('city_id') && $request->city_id) {
            $hubQuery->where('city_id', $request->city_id);
        }
        if ($request->has('searchValue') && $request->searchValue) {
            $hubQuery->where('name', 'like', '%' . $request->searchValue . '%');
        }
        $hubs = $hubQuery->latest()->paginate(20, ['*'], 'hub_page');
        $allStates = DeliveryState::where('is_active', true)->get();
        $allCities = DeliveryCity::where('is_active', true)->get();

        return view('admin-views.delivery.hub-management', compact('states', 'cities', 'hubs', 'allStates', 'allCities'));
    }

    /**
     * Store State
     */
    public function storeState(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:delivery_states,name',
        ]);

        DeliveryState::create([
            'name' => trim($request->name),
            'is_active' => true,
        ]);

        ToastMagic::success(translate('State added successfully'));
        return back();
    }

    /**
     * Update State
     */
    public function updateState(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:delivery_states,name,' . $id,
        ]);

        $state = DeliveryState::findOrFail($id);
        $state->update([
            'name' => trim($request->name),
        ]);

        ToastMagic::success(translate('State updated successfully'));
        return back();
    }

    /**
     * Delete State
     */
    public function deleteState($id): RedirectResponse
    {
        $state = DeliveryState::with('cities.hubs')->findOrFail($id);
        foreach ($state->cities as $city) {
            $city->hubs()->delete();
            $city->delete();
        }
        $state->delete();

        ToastMagic::success(translate('State and associated cities removed'));
        return back();
    }

    /**
     * Toggle State Status
     */
    public function statusState(Request $request): JsonResponse
    {
        $state = DeliveryState::findOrFail($request->id);
        $state->is_active = $request->status;
        $state->save();

        return response()->json([
            'success' => 1,
            'message' => translate('State status updated successfully'),
        ]);
    }

    /**
     * Store City
     */
    public function storeCity(Request $request): RedirectResponse
    {
        $request->validate([
            'state_id' => 'required|exists:delivery_states,id',
            'name' => 'required|string|max:100',
        ]);

        DeliveryCity::create([
            'state_id' => $request->state_id,
            'name' => trim($request->name),
            'is_active' => true,
        ]);

        ToastMagic::success(translate('City added successfully'));
        return back();
    }

    /**
     * Update City
     */
    public function updateCity(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'state_id' => 'required|exists:delivery_states,id',
            'name' => 'required|string|max:100',
        ]);

        $city = DeliveryCity::findOrFail($id);
        $city->update([
            'state_id' => $request->state_id,
            'name' => trim($request->name),
        ]);

        ToastMagic::success(translate('City updated successfully'));
        return back();
    }

    /**
     * Delete City
     */
    public function deleteCity($id): RedirectResponse
    {
        $city = DeliveryCity::findOrFail($id);
        $city->hubs()->delete();
        $city->delete();

        ToastMagic::success(translate('City and associated hubs deleted successfully'));
        return back();
    }

    /**
     * Toggle City Status
     */
    public function statusCity(Request $request): JsonResponse
    {
        $city = DeliveryCity::findOrFail($request->id);
        $city->is_active = $request->status;
        $city->save();

        return response()->json([
            'success' => 1,
            'message' => translate('City status updated successfully'),
        ]);
    }

    /**
     * Store Hub (Landmark or Motor Park)
     */
    public function storeHub(Request $request): RedirectResponse
    {
        $request->validate([
            'city_id' => 'required|exists:delivery_cities,id',
            'name' => 'required|string|max:150',
            'type' => 'required|in:landmark,motor_park',
            'base_shipping_cost' => 'required|numeric|min:0',
            'rider_delivery_fee' => 'nullable|numeric|min:0',
            'estimated_delivery_time' => 'nullable|string|max:100',
        ]);

        DeliveryHub::create([
            'city_id' => $request->city_id,
            'name' => trim($request->name),
            'type' => $request->type,
            'base_shipping_cost' => $request->base_shipping_cost,
            'rider_delivery_fee' => $request->rider_delivery_fee ?? 0.00,
            'estimated_delivery_time' => $request->estimated_delivery_time,
            'is_active' => true,
        ]);

        ToastMagic::success(translate($request->type == 'landmark' ? 'Landmark added successfully' : 'Motor Park hub added successfully'));
        return back();
    }

    /**
     * Update Hub
     */
    public function updateHub(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'city_id' => 'required|exists:delivery_cities,id',
            'name' => 'required|string|max:150',
            'type' => 'required|in:landmark,motor_park',
            'base_shipping_cost' => 'required|numeric|min:0',
            'rider_delivery_fee' => 'nullable|numeric|min:0',
            'estimated_delivery_time' => 'nullable|string|max:100',
        ]);

        $hub = DeliveryHub::findOrFail($id);
        $hub->update([
            'city_id' => $request->city_id,
            'name' => trim($request->name),
            'type' => $request->type,
            'base_shipping_cost' => $request->base_shipping_cost,
            'rider_delivery_fee' => $request->rider_delivery_fee ?? 0.00,
            'estimated_delivery_time' => $request->estimated_delivery_time,
        ]);

        ToastMagic::success(translate('Delivery hub updated successfully'));
        return back();
    }

    /**
     * Delete Hub
     */
    public function deleteHub($id): RedirectResponse
    {
        $hub = DeliveryHub::findOrFail($id);
        $hub->delete();

        ToastMagic::success(translate('Delivery hub removed successfully'));
        return back();
    }

    /**
     * Toggle Hub Status
     */
    public function statusHub(Request $request): JsonResponse
    {
        $hub = DeliveryHub::findOrFail($request->id);
        $hub->is_active = $request->status;
        $hub->save();

        return response()->json([
            'success' => 1,
            'message' => translate('Hub status updated successfully'),
        ]);
    }

    /**
     * Get Cities by State (AJAX)
     */
    public function getCitiesAjax($state_id): JsonResponse
    {
        $cities = DeliveryCity::where('state_id', $state_id)->where('is_active', true)->get();
        return response()->json($cities);
    }

    /**
     * Get Hubs by City (AJAX)
     */
    public function getHubsAjax(Request $request, $city_id): JsonResponse
    {
        $query = DeliveryHub::where('city_id', $city_id)->where('is_active', true);
        if ($request->has('type') && in_array($request->type, ['landmark', 'motor_park'])) {
            $query->where('type', $request->type);
        }
        $hubs = $query->get();
        return response()->json($hubs);
    }
}
