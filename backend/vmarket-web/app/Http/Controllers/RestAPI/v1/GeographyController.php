<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\DeliveryLane;
use App\Models\Lga;
use App\Models\State;
use App\Services\AddressAutocompleteService;
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

        $originLga = \App\Models\Lga::find($originLgaId);
        $destinationLga = \App\Models\Lga::find($destinationLgaId);

        if (!$originLga || !$destinationLga) {
            return response()->json([
                'status' => false,
                'message' => 'Delivery is not serviceable for this directional route.',
                'errors' => [
                    [
                        'code' => 'LANE_NOT_SERVICEABLE',
                        'message' => 'Selected LGA was not found in the canonical database.'
                    ]
                ]
            ], 422);
        }

        $resolved = DeliveryLane::resolveLane(
            $originLga->state_id,
            $originLgaId,
            $destinationLga->state_id,
            $destinationLgaId
        );

        $fee = (float) $resolved['fee'];
        if ($request->input('package_tier') === 'large') {
            $surcharge = (float) (getWebConfig(name: 'zone_bulky_cargo_surcharge') ?? 2500.00);
            $fee += $surcharge;
        }

        return response()->json([
            'status' => true,
            'message' => 'Delivery lane fee calculated successfully.',
            'data' => [
                'origin_lga_id' => $originLgaId,
                'destination_lga_id' => $destinationLgaId,
                'fee' => $fee,
                'estimated_days' => $resolved['eta'],
                'is_enabled' => true,
                'lane_id' => $resolved['lane']?->id ?? null,
                'lane_type' => $resolved['lane_type'],
                'zone_tier' => $resolved['zone_tier'],
                'is_custom_override' => $resolved['is_custom_override'],
            ]
        ], 200);
    }

    /**
     * [AI] Zero-key hierarchical address autocomplete scoped strictly to the selected Nigerian LGA.
     *
     * @param Request $request
     * @param AddressAutocompleteService $autocompleteService
     * @return JsonResponse
     */
    public function autocompleteAddress(Request $request, AddressAutocompleteService $autocompleteService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lga_id' => 'required|integer|exists:lgas,id',
            'q' => 'required|string|min:2|max:150',
            'limit' => 'nullable|integer|min:1|max:20',
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

        $lgaId = (int) $request->input('lga_id');
        $query = (string) $request->input('q');
        $limit = (int) ($request->input('limit') ?? 8);

        $suggestions = $autocompleteService->getSuggestions($lgaId, $query, $limit);

        return response()->json([
            'status' => true,
            'message' => 'Address suggestions retrieved successfully.',
            'lga_id' => $lgaId,
            'query' => $query,
            'data' => $suggestions,
        ], 200);
    }
}
