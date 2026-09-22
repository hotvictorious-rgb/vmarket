<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryHub;
use App\Models\Lga;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [AI] Phase A8 — Logistics Hub Decoupling
 *
 * Migrated geography queries from legacy DeliveryState / DeliveryCity to
 * canonical State / Lga models per CLAUDE.md authoritative-implementations table.
 *
 * Endpoint contracts preserved; response shape uses canonical model fields:
 *   getStates  → State records (id, name, code, …)
 *   getLgas    → Lga records for a given state_id
 *   getHubs    → DeliveryHub records for a given lga_id
 */
class DeliveryHubApiController extends Controller
{
    /**
     * Get All Active States (canonical geography).
     */
    public function getStates(): JsonResponse
    {
        $states = State::active()
            ->withCount(['lgas' => fn ($q) => $q->active()])
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'code']);

        return response()->json($states, 200);
    }

    /**
     * Get Active LGAs for a State.
     */
    public function getLgas(int $state_id): JsonResponse
    {
        $lgas = Lga::where('state_id', $state_id)
            ->active()
            ->withCount(['deliveryHubs' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name', 'asc')
            ->get(['id', 'state_id', 'name']);

        return response()->json($lgas, 200);
    }

    /**
     * Get Active Hubs (Landmarks / Motor Parks) for an LGA.
     */
    public function getHubs(Request $request, int $lga_id): JsonResponse
    {
        $query = DeliveryHub::where('lga_id', $lga_id)->where('is_active', true);

        if ($request->has('type') && in_array($request->type, ['landmark', 'motor_park'])) {
            $query->where('type', $request->type);
        }

        $hubs = $query->orderBy('name', 'asc')->get();

        return response()->json($hubs, 200);
    }

    /**
     * Calculate Shipping Cost based on Origin Hub & Destination Hub.
     */
    public function calculateHubShipping(Request $request): JsonResponse
    {
        $request->validate([
            'destination_hub_id' => 'required|exists:delivery_hubs,id',
            'origin_hub_id'      => 'nullable|exists:delivery_hubs,id',
        ]);

        $destHub   = DeliveryHub::with('lga.state')->find($request->destination_hub_id);
        $originHub = $request->origin_hub_id
            ? DeliveryHub::with('lga.state')->find($request->origin_hub_id)
            : null;

        $isSameLga = false;
        if ($originHub && $destHub) {
            $isSameLga = ($originHub->lga_id == $destHub->lga_id);
        }

        $shippingCost = $destHub->base_shipping_cost ?? 1000.00;
        $deliveryType = ($destHub->type == 'motor_park' || !$isSameLga)
            ? 'interstate_park_waybill'
            : 'intra_city_landmark';

        return response()->json([
            'status'         => 'success',
            'delivery_type'  => $deliveryType,
            'shipping_cost'  => $shippingCost,
            'estimated_time' => $destHub->estimated_delivery_time
                ?? ($deliveryType == 'intra_city_landmark' ? '2-4 hours' : '24-48 hours'),
            'hub'            => $destHub,
        ], 200);
    }
}
