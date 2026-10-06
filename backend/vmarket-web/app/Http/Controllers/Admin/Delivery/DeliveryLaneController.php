<?php

namespace App\Http\Controllers\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
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
     * Display the Hierarchical Delivery Lanes View (Intra-State LGA & Inter-State State Lanes)
     */
    public function index(Request $request): View
    {
        $laneType = $request->get('lane_type', 'intra_state');
        $searchValue = $request->get('searchValue');

        $query = DeliveryLane::with([
            'originCountry', 'originState', 'originLga',
            'destinationCountry', 'destinationState', 'destinationLga'
        ]);

        if ($laneType === 'inter_state') {
            $query->interState();
        } elseif ($laneType === 'intra_state') {
            $query->intraState();
        }

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('originLga', function ($sub) use ($searchValue) {
                    $sub->where('name', 'like', "%{$searchValue}%");
                })->orWhereHas('destinationLga', function ($sub) use ($searchValue) {
                    $sub->where('name', 'like', "%{$searchValue}%");
                })->orWhereHas('originState', function ($sub) use ($searchValue) {
                    $sub->where('name', 'like', "%{$searchValue}%");
                })->orWhereHas('destinationState', function ($sub) use ($searchValue) {
                    $sub->where('name', 'like', "%{$searchValue}%");
                });
            });
        }

        $lanes = $query->latest()->paginate(25);
        $countries = Country::where('is_active', true)->get();
        $states = State::where('is_active', true)->orderBy('name')->get();

        $defaultIntraFee = (float) (BusinessSetting::where('type', 'default_intrastate_delivery_fee')->value('value') ?? 2000.00);
        $defaultInterFee = (float) (BusinessSetting::where('type', 'default_interstate_delivery_fee')->value('value') ?? 5000.00);

        return view('admin-views.delivery.delivery-lane', compact(
            'lanes', 'countries', 'states', 'laneType', 'searchValue', 'defaultIntraFee', 'defaultInterFee'
        ));
    }

    /**
     * Store Delivery Lane (Intra-State LGA-to-LGA or Inter-State State-to-State)
     */
    public function store(Request $request): RedirectResponse
    {
        if (!\App\Utils\Helpers::module_permission_check('delivery.lane.manage') && !\App\Utils\Helpers::module_permission_check('order_management')) {
            ToastMagic::error(translate('Access Denied: Permission required to manage delivery lanes.'));
            return back();
        }

        $laneType = $request->input('lane_type', 'intra_state');

        if ($laneType === 'inter_state') {
            $request->validate([
                'origin_country_id' => 'required|exists:countries,id',
                'origin_state_id' => 'required|exists:states,id',
                'destination_country_id' => 'required|exists:countries,id',
                'destination_state_id' => 'required|exists:states,id|different:origin_state_id',
                'delivery_fee' => 'required|numeric|min:0',
                'estimated_delivery_time' => 'required|string|max:100',
            ]);

            // Check for duplicate State-to-State lane
            $exists = DeliveryLane::where('origin_state_id', $request->origin_state_id)
                ->where('destination_state_id', $request->destination_state_id)
                ->where('lane_type', 'inter_state')
                ->exists();

            if ($exists) {
                ToastMagic::error(translate('Interstate delivery lane already exists between selected States'));
                return back()->withInput();
            }

            $lane = DeliveryLane::create([
                'origin_country_id' => $request->origin_country_id,
                'origin_state_id' => $request->origin_state_id,
                'origin_lga_id' => null,
                'destination_country_id' => $request->destination_country_id,
                'destination_state_id' => $request->destination_state_id,
                'destination_lga_id' => null,
                'lane_type' => 'inter_state',
                'delivery_fee' => $request->delivery_fee,
                'estimated_delivery_time' => $request->estimated_delivery_time,
                'is_enabled' => true,
            ]);
        } else {
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

            // Check for duplicate LGA-to-LGA lane
            $exists = DeliveryLane::where('origin_lga_id', $request->origin_lga_id)
                ->where('destination_lga_id', $request->destination_lga_id)
                ->where('lane_type', 'intra_state')
                ->exists();

            if ($exists) {
                ToastMagic::error(translate('Delivery lane already exists between selected LGAs'));
                return back()->withInput();
            }

            $lane = DeliveryLane::create([
                'origin_country_id' => $request->origin_country_id,
                'origin_state_id' => $request->origin_state_id,
                'origin_lga_id' => $request->origin_lga_id,
                'destination_country_id' => $request->destination_country_id,
                'destination_state_id' => $request->destination_state_id,
                'destination_lga_id' => $request->destination_lga_id,
                'lane_type' => 'intra_state',
                'delivery_fee' => $request->delivery_fee,
                'estimated_delivery_time' => $request->estimated_delivery_time,
                'is_enabled' => true,
            ]);
        }

        \App\Services\AdminAuditService::log(
            action: 'delivery_lane.created',
            resourceType: DeliveryLane::class,
            resourceId: $lane->id,
            afterState: $lane->toArray(),
            reason: $request->input('reason', "New directional {$lane->lane_type} delivery lane created")
        );

        ToastMagic::success(translate('Delivery lane added successfully'));
        return back();
    }

    /**
     * Update Delivery Lane Fee / ETA
     */
    public function update(Request $request, $id): RedirectResponse
    {
        if (!\App\Utils\Helpers::module_permission_check('delivery.fee.update') && !\App\Utils\Helpers::module_permission_check('delivery.lane.manage') && !\App\Utils\Helpers::module_permission_check('order_management')) {
            ToastMagic::error(translate('Access Denied: Permission required to update delivery lane terms.'));
            return back();
        }

        $request->validate([
            'delivery_fee' => 'required|numeric|min:0',
            'estimated_delivery_time' => 'required|string|max:100',
        ]);

        $lane = DeliveryLane::findOrFail($id);
        $beforeState = $lane->toArray();

        $lane->update([
            'delivery_fee' => $request->delivery_fee,
            'estimated_delivery_time' => $request->estimated_delivery_time,
        ]);

        \App\Services\AdminAuditService::log(
            action: 'delivery_lane.updated',
            resourceType: DeliveryLane::class,
            resourceId: $lane->id,
            beforeState: $beforeState,
            afterState: $lane->fresh()->toArray(),
            reason: $request->input('reason', 'Delivery fee / ETA updated')
        );

        ToastMagic::success(translate('Delivery lane updated successfully'));
        return back();
    }

    /**
     * Update National Default Fallback Rates
     */
    public function updateDefaultRates(Request $request): RedirectResponse
    {
        if (!\App\Utils\Helpers::module_permission_check('delivery.lane.manage') && !\App\Utils\Helpers::module_permission_check('order_management')) {
            ToastMagic::error(translate('Access Denied: Permission required.'));
            return back();
        }

        $request->validate([
            'default_intrastate_delivery_fee' => 'required|numeric|min:0',
            'default_interstate_delivery_fee' => 'required|numeric|min:0',
        ]);

        BusinessSetting::updateOrInsert(
            ['type' => 'default_intrastate_delivery_fee'],
            ['value' => (string) $request->default_intrastate_delivery_fee, 'updated_at' => now()]
        );

        BusinessSetting::updateOrInsert(
            ['type' => 'default_interstate_delivery_fee'],
            ['value' => (string) $request->default_interstate_delivery_fee, 'updated_at' => now()]
        );

        ToastMagic::success(translate('Default fallback delivery rates updated successfully'));
        return back();
    }

    /**
     * Delete / Disable Delivery Lane
     */
    public function delete($id): RedirectResponse
    {
        if (!\App\Utils\Helpers::module_permission_check('delivery.lane.manage') && !\App\Utils\Helpers::module_permission_check('order_management')) {
            ToastMagic::error(translate('Access Denied: Permission required to delete delivery lanes.'));
            return back();
        }

        $lane = DeliveryLane::findOrFail($id);
        $beforeState = $lane->toArray();

        // Check if orders exist referencing this lane
        $hasOrders = \App\Models\Order::where('lane_id', $lane->id)->exists();

        if ($hasOrders) {
            $lane->update(['is_enabled' => false]);
            \App\Services\AdminAuditService::log(
                action: 'delivery_lane.disabled_for_historical_preservation',
                resourceType: DeliveryLane::class,
                resourceId: $lane->id,
                beforeState: $beforeState,
                afterState: $lane->fresh()->toArray(),
                reason: 'Soft disabled because historical order snapshots reference this lane'
            );
            ToastMagic::warning(translate('Lane has historical orders. Soft-disabled to preserve order snapshots.'));
            return back();
        }

        $lane->delete();

        \App\Services\AdminAuditService::log(
            action: 'delivery_lane.deleted',
            resourceType: DeliveryLane::class,
            resourceId: $id,
            beforeState: $beforeState,
            reason: 'Delivery lane removed'
        );

        ToastMagic::success(translate('Delivery lane removed'));
        return back();
    }

    /**
     * Toggle Delivery Lane Status
     */
    public function status(Request $request): JsonResponse
    {
        if (!\App\Utils\Helpers::module_permission_check('delivery.lane.manage') && !\App\Utils\Helpers::module_permission_check('order_management')) {
            return response()->json(['success' => false, 'message' => translate('Access Denied')], 403);
        }

        $lane = DeliveryLane::findOrFail($request->id);
        $beforeStatus = $lane->is_enabled;
        $lane->is_enabled = $request->status;
        $lane->save();

        \App\Services\AdminAuditService::log(
            action: $lane->is_enabled ? 'delivery_lane.enabled' : 'delivery_lane.disabled',
            resourceType: DeliveryLane::class,
            resourceId: $lane->id,
            beforeState: ['is_enabled' => $beforeStatus],
            afterState: ['is_enabled' => $lane->is_enabled],
            reason: $request->input('reason', 'Status toggled')
        );

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
            ->orderBy('name')
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
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($lgas);
    }
}
