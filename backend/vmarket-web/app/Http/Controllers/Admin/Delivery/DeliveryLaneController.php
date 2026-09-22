<?php

namespace App\Http\Controllers\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\DeliveryLane;
use App\Models\Lga;
use App\Models\State;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryLaneController extends Controller
{
    /**
     * Display the Delivery Lanes View
     */
    public function index(Request $request): View
    {
        $query = DeliveryLane::with([
            'originCountry', 'originState', 'originLga',
            'destinationCountry', 'destinationState', 'destinationLga'
        ]);

        if ($request->has('searchValue') && $request->searchValue) {
            $search = $request->searchValue;
            $query->whereHas('originLga', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('destinationLga', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $lanes = $query->latest()->paginate(20);
        $countries = Country::where('is_active', true)->get();

        return view('admin-views.delivery.delivery-lane', compact('lanes', 'countries'));
    }

    /**
     * Store Delivery Lane
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'origin_country_id' => 'required|exists:countries,id',
            'origin_state_id' => 'required|exists:states,id',
            'origin_lga_id' => 'required|exists:lgas,id',
            'destination_country_id' => 'required|exists:countries,id',
            'destination_state_id' => 'required|exists:states,id',
            'destination_lga_id' => 'required|exists:lgas,id',
            'delivery_fee' => 'required|numeric|min:0',
            'estimated_delivery_time' => 'required|string|max:100',
        ]);

        // Check for duplicates
        $exists = DeliveryLane::where('origin_lga_id', $request->origin_lga_id)
            ->where('destination_lga_id', $request->destination_lga_id)
            ->exists();

        if ($exists) {
            ToastMagic::error(translate('Delivery lane already exists between selected LGAs'));
            return back()->withInput();
        }

        DeliveryLane::create([
            'origin_country_id' => $request->origin_country_id,
            'origin_state_id' => $request->origin_state_id,
            'origin_lga_id' => $request->origin_lga_id,
            'destination_country_id' => $request->destination_country_id,
            'destination_state_id' => $request->destination_state_id,
            'destination_lga_id' => $request->destination_lga_id,
            'delivery_fee' => $request->delivery_fee,
            'estimated_delivery_time' => $request->estimated_delivery_time,
            'is_enabled' => true,
        ]);

        ToastMagic::success(translate('Delivery lane added successfully'));
        return back();
    }

    /**
     * Update Delivery Lane
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'delivery_fee' => 'required|numeric|min:0',
            'estimated_delivery_time' => 'required|string|max:100',
        ]);

        $lane = DeliveryLane::findOrFail($id);
        $lane->update([
            'delivery_fee' => $request->delivery_fee,
            'estimated_delivery_time' => $request->estimated_delivery_time,
        ]);

        ToastMagic::success(translate('Delivery lane updated successfully'));
        return back();
    }

    /**
     * Delete Delivery Lane
     */
    public function delete($id): RedirectResponse
    {
        Configuration:
        $lane = DeliveryLane::findOrFail($id);
        $lane->delete();

        ToastMagic::success(translate('Delivery lane removed'));
        return back();
    }

    /**
     * Toggle Delivery Lane Status
     */
    public function status(Request $request): JsonResponse
    {
        $lane = DeliveryLane::findOrFail($request->id);
        $lane->is_enabled = $request->status;
        $lane->save();

        return response()->json([
            'success' => true,
            'message' => translate('Lane status updated successfully')
        ], 200);
    }

    /**
     * Get States via AJAX
     */
    public function getStatesAjax(Request $request): JsonResponse
    {
        $states = State::where('country_id', $request->country_id)
            ->where('is_active', true)
            ->get(['id', 'name']);

        return response()->json($states);
    }

    /**
     * Get LGAs via AJAX
     */
    public function getLgasAjax(Request $request): JsonResponse
    {
        $lgas = Lga::where('state_id', $request->state_id)
            ->where('is_active', true)
            ->get(['id', 'name']);

        return response()->json($lgas);
    }
}
