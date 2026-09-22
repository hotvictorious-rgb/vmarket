<?php

namespace App\Http\Controllers\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryHub;
use App\Models\Lga;
use App\Models\State;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * [AI] Phase A8 — Logistics Hub Decoupling
 *
 * Removed all DeliveryState / DeliveryCity CRUD (those models are
 * legacy and the canonical state/LGA data is managed via the
 * canonical_geography seeders, not by the admin panel).
 *
 * Hub geography: State → LGA (canonical) instead of DeliveryState → DeliveryCity (legacy).
 *
 * Retained methods:
 *   index, storeHub, updateHub, deleteHub, statusHub,
 *   getLgasAjax (renamed from getCitiesAjax), getHubsAjax
 */
class DeliveryHubController extends Controller
{
    /**
     * Display the Delivery Hub Management view.
     */
    public function index(Request $request): View
    {
        $hubQuery = DeliveryHub::with('lga.state');

        if ($request->filled('hub_type') && in_array($request->hub_type, ['landmark', 'motor_park'])) {
            $hubQuery->where('type', $request->hub_type);
        }
        if ($request->filled('lga_id')) {
            $hubQuery->where('lga_id', $request->lga_id);
        }
        if ($request->filled('searchValue')) {
            $hubQuery->where('name', 'like', '%' . $request->searchValue . '%');
        }

        $hubs      = $hubQuery->latest()->paginate(20, ['*'], 'hub_page');
        $allStates = State::active()->orderBy('name')->get();
        // Pre-load all active LGAs for the edit-modal dropdown
        $allLgas   = Lga::active()->orderBy('name')->get();

        return view('admin-views.delivery.hub-management', compact('hubs', 'allStates', 'allLgas'));
    }

    /**
     * Store a new Delivery Hub (Landmark or Motor Park).
     */
    public function storeHub(Request $request): RedirectResponse
    {
        $request->validate([
            'lga_id'                => 'required|exists:lgas,id',
            'name'                  => 'required|string|max:150',
            'type'                  => 'required|in:landmark,motor_park',
            'base_shipping_cost'    => 'required|numeric|min:0',
            'rider_delivery_fee'    => 'nullable|numeric|min:0',
            'estimated_delivery_time' => 'nullable|string|max:100',
        ]);

        DeliveryHub::create([
            'lga_id'                  => $request->lga_id,
            'name'                    => trim($request->name),
            'type'                    => $request->type,
            'base_shipping_cost'      => $request->base_shipping_cost,
            'rider_delivery_fee'      => $request->rider_delivery_fee ?? 0.00,
            'estimated_delivery_time' => $request->estimated_delivery_time,
            'is_active'               => true,
        ]);

        ToastMagic::success(translate($request->type === 'landmark' ? 'Landmark added successfully' : 'Motor Park hub added successfully'));
        return back();
    }

    /**
     * Update an existing Delivery Hub.
     */
    public function updateHub(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'lga_id'                => 'required|exists:lgas,id',
            'name'                  => 'required|string|max:150',
            'type'                  => 'required|in:landmark,motor_park',
            'base_shipping_cost'    => 'required|numeric|min:0',
            'rider_delivery_fee'    => 'nullable|numeric|min:0',
            'estimated_delivery_time' => 'nullable|string|max:100',
        ]);

        $hub = DeliveryHub::findOrFail($id);
        $hub->update([
            'lga_id'                  => $request->lga_id,
            'name'                    => trim($request->name),
            'type'                    => $request->type,
            'base_shipping_cost'      => $request->base_shipping_cost,
            'rider_delivery_fee'      => $request->rider_delivery_fee ?? 0.00,
            'estimated_delivery_time' => $request->estimated_delivery_time,
        ]);

        ToastMagic::success(translate('Delivery hub updated successfully'));
        return back();
    }

    /**
     * Delete a Delivery Hub.
     */
    public function deleteHub($id): RedirectResponse
    {
        $hub = DeliveryHub::findOrFail($id);
        $hub->delete();

        ToastMagic::success(translate('Delivery hub removed successfully'));
        return back();
    }

    /**
     * Toggle Hub active status (AJAX).
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
     * Get LGAs by State (AJAX) — replaces the legacy getCitiesAjax.
     * Route: GET admin/delivery-hubs/get-lgas-ajax/{state_id}
     */
    public function getLgasAjax(int $state_id): JsonResponse
    {
        $lgas = Lga::where('state_id', $state_id)->active()->orderBy('name')->get(['id', 'name']);
        return response()->json($lgas);
    }

    /**
     * Get Hubs by LGA (AJAX) — parameter renamed from $city_id to $lga_id.
     * Route: GET admin/delivery-hubs/get-hubs-ajax/{lga_id}
     */
    public function getHubsAjax(Request $request, int $lga_id): JsonResponse
    {
        $query = DeliveryHub::where('lga_id', $lga_id)->where('is_active', true);
        if ($request->filled('type') && in_array($request->type, ['landmark', 'motor_park'])) {
            $query->where('type', $request->type);
        }
        return response()->json($query->get());
    }
}
