<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCity;
use App\Models\DeliveryHub;
use App\Models\DeliveryState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryHubApiController extends Controller
{
    /**
     * Get All Active States
     */
    public function getStates(): JsonResponse
    {
        $states = DeliveryState::where('is_active', true)
            ->withCount('activeCities')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($states, 200);
    }

    /**
     * Get Active Cities for a State
     */
    public function getCities($state_id): JsonResponse
    {
        $cities = DeliveryCity::where('state_id', $state_id)
            ->where('is_active', true)
            ->withCount(['landmarks', 'motorParks'])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($cities, 200);
    }

    /**
     * Get Active Hubs (Landmarks / Motor Parks) for a City
     */
    public function getHubs(Request $request, $city_id): JsonResponse
    {
        $query = DeliveryHub::where('city_id', $city_id)->where('is_active', true);

        if ($request->has('type') && in_array($request->type, ['landmark', 'motor_park'])) {
            $query->where('type', $request->type);
        }

        $hubs = $query->orderBy('name', 'asc')->get();

        return response()->json($hubs, 200);
    }

    /**
     * Calculate Shipping Cost based on Origin Hub & Destination Hub
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
