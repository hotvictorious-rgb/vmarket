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

        $zoneIntraLgaFee = (float) (BusinessSetting::where('type', 'zone_intra_lga_fee')->value('value') ?? 1000.00);
        $zoneIntraLgaEta = (string) (BusinessSetting::where('type', 'zone_intra_lga_eta')->value('value') ?? '2-4 hours');
        $zoneInterLgaFee = (float) (BusinessSetting::where('type', 'zone_inter_lga_fee')->value('value') ?? 2500.00);
        $zoneInterLgaEta = (string) (BusinessSetting::where('type', 'zone_inter_lga_eta')->value('value') ?? 'Same day / 24 hours');
        $zoneInterStateFee = (float) (BusinessSetting::where('type', 'zone_inter_state_fee')->value('value') ?? 4500.00);
        $zoneInterStateEta = (string) (BusinessSetting::where('type', 'zone_inter_state_eta')->value('value') ?? '2-4 business days');
        $zoneBulkyCargoSurcharge = (float) (BusinessSetting::where('type', 'zone_bulky_cargo_surcharge')->value('value') ?? 2500.00);

        // Backward compatibility
        $defaultIntraFee = $zoneInterLgaFee;
        $defaultInterFee = $zoneInterStateFee;

        return view('admin-views.delivery.delivery-lane', compact(
            'lanes', 'countries', 'states', 'laneType', 'searchValue',
            'zoneIntraLgaFee', 'zoneIntraLgaEta',
            'zoneInterLgaFee', 'zoneInterLgaEta',
            'zoneInterStateFee', 'zoneInterStateEta',
            'zoneBulkyCargoSurcharge',
            'defaultIntraFee', 'defaultInterFee'
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
        $isBidirectional = $request->boolean('is_bidirectional', true);

        if ($laneType === 'inter_state') {
            $request->validate([
                'origin_country_id' => 'required|exists:countries,id',
                'origin_state_id' => 'required|exists:states,id',
                'destination_country_id' => 'required|exists:countries,id',
                'destination_state_id' => 'required|exists:states,id|different:origin_state_id',
                'delivery_fee' => 'required|numeric|min:0',
                'estimated_delivery_time' => 'required|string|max:100',
            ]);

            // Check for duplicate State-to-State lane (accounting for bidirectional matches)
            $exists = DeliveryLane::where(function ($q) use ($request, $isBidirectional) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('origin_state_id', $request->origin_state_id)
                        ->where('destination_state_id', $request->destination_state_id);
                });
                if ($isBidirectional) {
                    $q->orWhere(function ($sub) use ($request) {
                        $sub->where('origin_state_id', $request->destination_state_id)
                            ->where('destination_state_id', $request->origin_state_id)
                            ->where('is_bidirectional', true);
                    });
                }
            })->where('lane_type', 'inter_state')->exists();

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
                'is_bidirectional' => $isBidirectional,
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

            // Check for duplicate LGA-to-LGA lane (accounting for bidirectional matches)
            $exists = DeliveryLane::where(function ($q) use ($request, $isBidirectional) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('origin_lga_id', $request->origin_lga_id)
                        ->where('destination_lga_id', $request->destination_lga_id);
                });
                if ($isBidirectional) {
                    $q->orWhere(function ($sub) use ($request) {
                        $sub->where('origin_lga_id', $request->destination_lga_id)
                            ->where('destination_lga_id', $request->origin_lga_id)
                            ->where('is_bidirectional', true);
                    });
                }
            })->where('lane_type', 'intra_state')->exists();

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
                'is_bidirectional' => $isBidirectional,
                'is_enabled' => true,
            ]);
        }

        \App\Services\AdminAuditService::log(
            action: 'delivery_lane.created',
            resourceType: DeliveryLane::class,
            resourceId: $lane->id,
            afterState: $lane->toArray(),
            reason: $request->input('reason', "New " . ($lane->is_bidirectional ? 'bidirectional' : 'directional') . " {$lane->lane_type} delivery lane created")
        );

        ToastMagic::success(translate('Delivery lane added successfully'));
        return back();
    }

    /**
     * Update Delivery Lane Fee / ETA / Bidirectional Flag
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
            'is_bidirectional' => 'nullable|boolean',
        ]);

        $lane = DeliveryLane::findOrFail($id);
        $beforeState = $lane->toArray();

        $lane->update([
            'delivery_fee' => $request->delivery_fee,
            'estimated_delivery_time' => $request->estimated_delivery_time,
            'is_bidirectional' => $request->has('is_bidirectional') ? $request->boolean('is_bidirectional') : $lane->is_bidirectional,
        ]);

        \App\Services\AdminAuditService::log(
            action: 'delivery_lane.updated',
            resourceType: DeliveryLane::class,
            resourceId: $lane->id,
            beforeState: $beforeState,
            afterState: $lane->fresh()->toArray(),
            reason: $request->input('reason', 'Delivery fee / ETA / bidirectional terms updated')
        );

        ToastMagic::success(translate('Delivery lane updated successfully'));
        return back();
    }

    /**
     * Update 3-Tier Zonal Distance Baseline Rates & Bulky Cargo Surcharge
     */
    public function updateZonalRates(Request $request): RedirectResponse
    {
        if (!\App\Utils\Helpers::module_permission_check('delivery.lane.manage') && !\App\Utils\Helpers::module_permission_check('order_management')) {
            ToastMagic::error(translate('Access Denied: Permission required.'));
            return back();
        }

        $request->validate([
            'zone_intra_lga_fee' => 'required|numeric|min:0',
            'zone_intra_lga_eta' => 'required|string|max:100',
            'zone_inter_lga_fee' => 'required|numeric|min:0',
            'zone_inter_lga_eta' => 'required|string|max:100',
            'zone_inter_state_fee' => 'required|numeric|min:0',
            'zone_inter_state_eta' => 'required|string|max:100',
            'zone_bulky_cargo_surcharge' => 'required|numeric|min:0',
        ]);

        $settings = [
            'zone_intra_lga_fee' => (string) $request->zone_intra_lga_fee,
            'zone_intra_lga_eta' => (string) $request->zone_intra_lga_eta,
            'zone_inter_lga_fee' => (string) $request->zone_inter_lga_fee,
            'zone_inter_lga_eta' => (string) $request->zone_inter_lga_eta,
            'zone_inter_state_fee' => (string) $request->zone_inter_state_fee,
            'zone_inter_state_eta' => (string) $request->zone_inter_state_eta,
            'zone_bulky_cargo_surcharge' => (string) $request->zone_bulky_cargo_surcharge,
            // Synchronize legacy keys
            'default_intrastate_delivery_fee' => (string) $request->zone_inter_lga_fee,
            'default_interstate_delivery_fee' => (string) $request->zone_inter_state_fee,
        ];

        foreach ($settings as $type => $value) {
            BusinessSetting::updateOrInsert(
                ['type' => $type],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        if (function_exists('clearWebConfigCacheKeys')) {
            clearWebConfigCacheKeys();
        }

        \App\Services\AdminAuditService::log(
            action: 'delivery_lane.zonal_rates_updated',
            resourceType: DeliveryLane::class,
            resourceId: 0,
            afterState: $settings,
            reason: '3-Tier Zonal Distance Baseline Pricing and Bulky Surcharge updated'
        );

        ToastMagic::success(translate('3-Tier Zonal Distance Baseline Rates updated successfully'));
        return back();
    }

    /**
     * Update National Default Fallback Rates (Legacy alias for updateZonalRates)
     */
    public function updateDefaultRates(Request $request): RedirectResponse
    {
        if ($request->has('zone_intra_lga_fee')) {
            return $this->updateZonalRates($request);
        }

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

        // Also sync zonal
        BusinessSetting::updateOrInsert(
            ['type' => 'zone_inter_lga_fee'],
            ['value' => (string) $request->default_intrastate_delivery_fee, 'updated_at' => now()]
        );
        BusinessSetting::updateOrInsert(
            ['type' => 'zone_inter_state_fee'],
            ['value' => (string) $request->default_interstate_delivery_fee, 'updated_at' => now()]
        );

        if (function_exists('clearWebConfigCacheKeys')) {
            clearWebConfigCacheKeys();
        }

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
