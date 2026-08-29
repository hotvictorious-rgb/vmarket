<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCity;
use App\Models\DeliveryHub;
use App\Models\DeliveryState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DeliveryHubApiController extends Controller
{
    // [AI] Cache TTL for read-only hub data (seconds). Adjust per environment needs.
    private const CACHE_TTL = 600;

    /**
     * Get All Active States
     * [AI] Cached: data changes only when admin mutates states. TTL = 10 min.
     * Cache is invalidated by DeliveryHubController on any state/city/hub mutation.
     */
    public function getStates(): JsonResponse
    {
        $states = Cache::remember('delivery_hub_states', self::CACHE_TTL, function () {
            return DeliveryState::where('is_active', true)
                ->withCount('activeCities')
                ->orderBy('name', 'asc')
                ->get();
        });

        return response()->json($states, 200);
    }

    /**
     * Get Active Cities for a State
     * [AI] Cached per state_id. Cache key: delivery_hub_cities_{state_id}.
     */
    public function getCities($state_id): JsonResponse
    {
        $cacheKey = "delivery_hub_cities_{$state_id}";

        $cities = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($state_id) {
            return DeliveryCity::where('state_id', $state_id)
                ->where('is_active', true)
                ->withCount(['landmarks', 'motorParks'])
                ->orderBy('name', 'asc')
                ->get();
        });

        return response()->json($cities, 200);
    }

    /**
     * Get Active Hubs (Landmarks / Motor Parks) for a City
     * [AI] Cached per city_id + optional type filter.
     * Cache key: delivery_hub_hubs_{city_id} or delivery_hub_hubs_{city_id}_{type}.
     */
    public function getHubs(Request $request, $city_id): JsonResponse
    {
        $type = $request->has('type') && in_array($request->type, ['landmark', 'motor_park'])
            ? $request->type
            : null;

        // [AI] Include type in cache key so landmark-only and motor_park-only filters are cached separately
        $cacheKey = $type ? "delivery_hub_hubs_{$city_id}_{$type}" : "delivery_hub_hubs_{$city_id}";

        $hubs = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($city_id, $type) {
            $query = DeliveryHub::where('city_id', $city_id)->where('is_active', true);
            if ($type) {
                $query->where('type', $type);
            }
            return $query->orderBy('name', 'asc')->get();
        });

        return response()->json($hubs, 200);
    }

    /**
     * Calculate Shipping Cost based on Origin Hub & Destination Hub
     * [AI] Not cached — depends on dynamic request params (hub IDs).
     * Client: Customer App checkout, Web Storefront checkout.
     */
    public function calculateHubShipping(Request $request): JsonResponse
    {
        $request->validate([
            'destination_hub_id' => 'required|exists:delivery_hubs,id',
            'origin_hub_id' => 'nullable|exists:delivery_hubs,id',
        ]);

        $destHub = DeliveryHub::with('city.state')->find($request->destination_hub_id);
        $originHub = $request->origin_hub_id ? DeliveryHub::with('city.state')->find($request->origin_hub_id) : null;

        $isSameCity = false;
        if ($originHub && $destHub) {
            $isSameCity = ($originHub->city_id == $destHub->city_id);
        }

        $shippingCost = $destHub->base_shipping_cost ?? 1000.00;
        $deliveryType = ($destHub->type == 'motor_park' || !$isSameCity) ? 'interstate_park_waybill' : 'intra_city_landmark';

        return response()->json([
            'status' => 'success',
            'delivery_type' => $deliveryType,
            'shipping_cost' => $shippingCost,
            'estimated_time' => $destHub->estimated_delivery_time ?? ($deliveryType == 'intra_city_landmark' ? '2-4 hours' : '24-48 hours'),
            'hub' => $destHub,
        ], 200);
    }
}
