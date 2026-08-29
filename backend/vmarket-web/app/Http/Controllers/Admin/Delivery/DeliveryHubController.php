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
use Illuminate\Support\Facades\Cache;

class DeliveryHubController extends Controller
{
    // [AI] Shared TTL with DeliveryHubApiController. Invalidation is explicit on mutations.
    private const CACHE_TTL = 600;

    /**
     * Display the Geographic Hubs Management View
     * [AI] Performance: $allStates is cached to eliminate repeated full-table reads.
     * $allCities removed — the edit modal now loads cities on-demand via getCitiesAjax() AJAX.
     * Client: Admin Web Panel only.
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

        // [AI] Cached: full list of active states for the Add/Edit modal state dropdowns.
        // Invalidated by any state/city/hub mutation below.
        $allStates = Cache::remember('delivery_hub_all_states', self::CACHE_TTL, function () {
            return DeliveryState::where('is_active', true)->orderBy('name')->get();
        });

        // [AI] $allCities removed from page load — edit modal fetches cities via getCitiesAjax() AJAX,
        // triggered automatically when the modal pre-selects the hub's state.

        return view('admin-views.delivery.hub-management', compact('states', 'cities', 'hubs', 'allStates'));
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

        // [AI] Invalidate all state/city/hub caches — new state affects dropdowns everywhere
        $this->flushDeliveryHubCaches();

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

        // [AI] Invalidate caches after name change
        $this->flushDeliveryHubCaches();

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

        // [AI] Invalidate all caches — cascaded delete removes cities and hubs
        $this->flushDeliveryHubCaches();

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

        // [AI] Status change affects which states appear in active dropdowns
        $this->flushDeliveryHubCaches();

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

        // [AI] Invalidate cities for this state in the API cache
        $this->flushDeliveryHubCaches($request->state_id);

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
        $oldStateId = $city->state_id;
        $city->update([
            'state_id' => $request->state_id,
            'name' => trim($request->name),
        ]);

        // [AI] Flush both old and new state city caches if state changed
        $this->flushDeliveryHubCaches($oldStateId);
        if ($oldStateId !== (int) $request->state_id) {
            $this->flushDeliveryHubCaches($request->state_id);
        }

        ToastMagic::success(translate('City updated successfully'));
        return back();
    }

    /**
     * Delete City
     */
    public function deleteCity($id): RedirectResponse
    {
        $city = DeliveryCity::findOrFail($id);
        $stateId = $city->state_id;
        $city->hubs()->delete();
        $city->delete();

        // [AI] Flush city and hub caches for the affected state
        $this->flushDeliveryHubCaches($stateId, $id);

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

        // [AI] Flush caches for the state this city belongs to
        $this->flushDeliveryHubCaches($city->state_id, $city->id);

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

        // [AI] Flush hub caches for the affected city (all type variants)
        $this->flushHubCacheForCity($request->city_id);

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
        $oldCityId = $hub->city_id;
        $hub->update([
            'city_id' => $request->city_id,
            'name' => trim($request->name),
            'type' => $request->type,
            'base_shipping_cost' => $request->base_shipping_cost,
            'rider_delivery_fee' => $request->rider_delivery_fee ?? 0.00,
            'estimated_delivery_time' => $request->estimated_delivery_time,
        ]);

        // [AI] Flush hub caches for old city and new city (if city changed)
        $this->flushHubCacheForCity($oldCityId);
        if ($oldCityId !== (int) $request->city_id) {
            $this->flushHubCacheForCity($request->city_id);
        }

        ToastMagic::success(translate('Delivery hub updated successfully'));
        return back();
    }

    /**
     * Delete Hub
     */
    public function deleteHub($id): RedirectResponse
    {
        $hub = DeliveryHub::findOrFail($id);
        $cityId = $hub->city_id;
        $hub->delete();

        // [AI] Flush hub caches for the affected city
        $this->flushHubCacheForCity($cityId);

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

        // [AI] Status change affects which hubs appear in customer dropdowns
        $this->flushHubCacheForCity($hub->city_id);

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

    // ─────────────────────────────────────────────────────────────────
    // [AI] PRIVATE CACHE INVALIDATION HELPERS
    // Called from every mutating action to keep caches fresh.
    // ─────────────────────────────────────────────────────────────────

    /**
     * [AI] Flush all top-level delivery hub caches.
     * Optionally flush city-specific and hub-specific caches when $stateId / $cityId provided.
     */
    private function flushDeliveryHubCaches(?int $stateId = null, ?int $cityId = null): void
    {
        // Global state lists (API + admin dropdown)
        Cache::forget('delivery_hub_states');
        Cache::forget('delivery_hub_all_states');

        // State-scoped city list
        if ($stateId) {
            Cache::forget("delivery_hub_cities_{$stateId}");
        }

        // City-scoped hub lists (all type variants)
        if ($cityId) {
            $this->flushHubCacheForCity($cityId);
        }
    }

    /**
     * [AI] Flush all hub cache variants for a given city_id.
     * Covers the unfiltered list plus landmark-only and motor_park-only variants.
     */
    private function flushHubCacheForCity(int $cityId): void
    {
        Cache::forget("delivery_hub_hubs_{$cityId}");
        Cache::forget("delivery_hub_hubs_{$cityId}_landmark");
        Cache::forget("delivery_hub_hubs_{$cityId}_motor_park");
    }
}
