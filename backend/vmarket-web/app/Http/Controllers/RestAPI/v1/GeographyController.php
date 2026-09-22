<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Lga;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function getStates(Request $request, int|string $countryId): JsonResponse
    {
        $states = State::query()
            ->where('country_id', (int) $countryId)
            ->where('is_active', true)
            ->select(['id', 'country_id', 'name', 'state_code'])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'States retrieved successfully.',
            'country_id' => (int) $countryId,
            'data' => $states,
        ], 200);
    }

    /**
     * Retrieve list of active LGAs for a specific state.
     */
    public function getLgas(Request $request, int|string $stateId): JsonResponse
    {
        $lgas = Lga::query()
            ->where('state_id', (int) $stateId)
            ->where('is_active', true)
            ->select(['id', 'state_id', 'name'])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'LGAs retrieved successfully.',
            'state_id' => (int) $stateId,
            'data' => $lgas,
        ], 200);
    }
}
