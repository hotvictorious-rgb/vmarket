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
     * Alias for backward compatibility with mobile clients calling /cities/{state_id}.
     */
    public function getCities(int $state_id): JsonResponse
    {
        return $this->getLgas($state_id);
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
}

