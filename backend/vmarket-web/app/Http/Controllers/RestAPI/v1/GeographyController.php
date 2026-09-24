<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\DeliveryLane;
use App\Models\Lga;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * [AI] GeographyController (Authoritative Geography REST API)
 *
 * Exposes canonical Country -> State -> LGA hierarchy for client mobile apps
 * and web storefronts.
 *
 * Invariants:
 * - Single authoritative source of geographic routing.
 * - Only active geographic units are returned for customer selection.
 * - Client apps consume these canonical IDs (country_id, state_id, lga_id)
 *   and NEVER invent local geographic fees or routing decisions.
 *
 * Scope: Customer App, Vendor App, Delivery App, Web Storefront
 */
class GeographyController extends Controller
{
    /**
     * Retrieve list of active countries.
     */
    public function getCountries(): JsonResponse
    {
        $countries = Country::query()
            ->where('is_active', true)
            ->select(['id', 'name', 'iso_code', 'phone_code', 'currency_code'])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Countries retrieved successfully.',
            'data' => $countries,
        ], 200);
    }

    /**
     * Retrieve list of active states for a specific country.
     */
    public function getStates(Request $request, int|string|null $countryId = null): JsonResponse
    {
        $resolvedCountryId = $countryId ?? $request->query('country_id') ?? 1;

        $states = State::query()
            ->where('country_id', (int) $resolvedCountryId)
            ->where('is_active', true)
            ->select(['id', 'country_id', 'name', 'state_code'])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'States retrieved successfully.',
            'country_id' => (int) $resolvedCountryId,
            'data' => $states,
        ], 200);
    }

    /**
     * Retrieve list of active LGAs for a specific state.
     */
    public function getLgas(Request $request, int|string|null $stateId = null): JsonResponse
    {
        $resolvedStateId = $stateId ?? $request->query('state_id');

        if (!$resolvedStateId) {
            return response()->json([
                'status' => false,
                'message' => 'State ID is required.',
                'errors' => [
                    [
                        'code' => 'STATE_ID_REQUIRED',
                        'message' => 'State ID is required to fetch LGAs.'
                    ]
                ]
            ], 422);
        }

        $lgas = Lga::query()
            ->where('state_id', (int) $resolvedStateId)
            ->where('is_active', true)
            ->select(['id', 'state_id', 'name'])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'LGAs retrieved successfully.',
            'state_id' => (int) $resolvedStateId,
            'data' => $lgas,
        ], 200);
    }

    /**
     * Calculate exact delivery fee for directional Origin LGA -> Destination LGA lane.
     * Enforces authoritative DeliveryLane pricing without client-side calculation.
     */
    public function calculateLaneFee(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'origin_lga_id' => 'required|integer|exists:lgas,id',
            'destination_lga_id' => 'required|integer|exists:lgas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => collect($validator->errors()->all())->map(fn($msg) => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $msg,
                ])->values()->all(),
            ], 422);
        }

        $originLgaId = (int) $request->origin_lga_id;
        $destinationLgaId = (int) $request->destination_lga_id;

        $lane = DeliveryLane::findLane($originLgaId, $destinationLgaId);

        if (!$lane || !$lane->is_enabled) {
            return response()->json([
                'status' => false,
                'message' => 'Delivery is not serviceable for this directional route.',
                'errors' => [
                    [
                        'code' => 'LANE_NOT_SERVICEABLE',
                        'message' => 'No active delivery lane exists between the selected LGAs.'
                    ]
                ]
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Delivery lane fee calculated successfully.',
            'data' => [
                'origin_lga_id' => $originLgaId,
                'destination_lga_id' => $destinationLgaId,
                'fee' => (float) $lane->delivery_fee,
                'estimated_days' => $lane->estimated_delivery_time ?? '2-3 business days',
                'is_enabled' => (bool) $lane->is_enabled,
                'lane_id' => $lane->id,
            ]
        ], 200);
    }
}
