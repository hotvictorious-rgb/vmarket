<?php

namespace App\Http\Controllers\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Models\Lga;
use App\Models\LogisticsCompany;
use App\Models\Order;
use App\Models\State;
use App\Utils\Helpers;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DispatchPortalController extends Controller
{
    /**
     * Display the Canonical LGA Delivery Lane & Dual-Fleet Dispatch Console
     */
    public function index(Request $request): View
    {
        $selectedOriginLgaId = $request->get('origin_lga_id');
        $selectedDestLgaId = $request->get('destination_lga_id');
        $selectedPackageTier = $request->get('package_tier');
        $selectedAssignmentStatus = $request->get('assignment_status', 'all');

        // 1. Query Active Dispatchable Orders
        $ordersQuery = Order::with(['seller.shop', 'deliveryMan', 'customer', 'logisticsCompany'])
            ->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery'])
            ->where('order_type', 'default_type');

        // Filter by Assignment Status
        if ($selectedAssignmentStatus === 'unassigned') {
            $ordersQuery->whereNull('delivery_man_id')->whereNull('logistics_company_id');
        } elseif ($selectedAssignmentStatus === 'assigned') {
            $ordersQuery->where(function ($q) {
                $q->whereNotNull('delivery_man_id')->orWhereNotNull('logistics_company_id');
            });
        }

        // Filter by Package Tier
        if (!empty($selectedPackageTier) && in_array($selectedPackageTier, ['small', 'large'])) {
            $ordersQuery->where('package_tier', $selectedPackageTier);
        }

        // Filter by LGA Lanes
        if (!empty($selectedOriginLgaId)) {
            $ordersQuery->where('origin_lga_id', $selectedOriginLgaId);
        }

        if (!empty($selectedDestLgaId)) {
            $ordersQuery->where('destination_lga_id', $selectedDestLgaId);
        }

        $allOrders = $ordersQuery->latest()->get();

        // 2. Cluster Orders into Directional LGA Corridors (Origin LGA -> Destination LGA)
        $corridors = [];
        $totalOrdersCount = $allOrders->count();
        $unassignedOrdersCount = 0;
        $largeCargoCount = 0;

        foreach ($allOrders as $order) {
            $originState = $order->origin_state_name ?? 'Akwa Ibom';
            $destState = $order->destination_state_name ?? 'Akwa Ibom';
            $isInterState = (strcasecmp(trim($originState), trim($destState)) !== 0);

            if ($isInterState) {
                $originId = 0;
                $originName = $originState;
                $destId = 0;
                $destName = $destState . ' (National Waybill)';
                $isInterLga = false;
                $corridorKey = 'INTER_' . Str::slug($originState) . '_' . Str::slug($destState);
            } else {
                $originId = $order->origin_lga_id ?? 0;
                $originName = !empty($order->origin_lga_name) ? $order->origin_lga_name : ($order->seller?->shop?->name ?? translate('Uyo Central'));
                $destId = $order->destination_lga_id ?? 0;
                $destName = !empty($order->destination_lga_name) ? $order->destination_lga_name : translate('Local Delivery Area');
                $isInterLga = ($originId > 0 && $destId > 0 && $originId != $destId);
                $corridorKey = 'INTRA_' . $originId . '_' . $destId;
            }

            if ($order->package_tier === 'large') {
                $largeCargoCount++;
            }

            $isUnassigned = (empty($order->delivery_man_id) && empty($order->logistics_company_id));
            if ($isUnassigned) {
                $unassignedOrdersCount++;
            }

            if (!isset($corridors[$corridorKey])) {
                $corridors[$corridorKey] = [
                    'key' => $corridorKey,
                    'origin_id' => $originId,
                    'origin_name' => $originName,
                    'dest_id' => $destId,
                    'dest_name' => $destName,
                    'is_inter_lga' => $isInterLga,
                    'is_inter_state' => $isInterState,
                    'orders' => [],
                    'total_amount' => 0.0,
                    'unassigned_count' => 0,
                    'large_count' => 0,
                ];
            }

            $corridors[$corridorKey]['orders'][] = $order;
            $corridors[$corridorKey]['total_amount'] += (float)$order->order_amount;
            if ($isUnassigned) {
                $corridors[$corridorKey]['unassigned_count']++;
            }
            if ($order->package_tier === 'large') {
                $corridors[$corridorKey]['large_count']++;
            }
        }

        // 3. Fetch In-House Couriers with Live Load Capacity
        $deliveryMen = DeliveryMan::where('is_active', 1)
            ->with(['logisticsCompany'])
            ->withCount(['orders' => function ($q) {
                $q->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery']);
            }])
            ->orderBy('f_name')
            ->get();

        // 4. Fetch Accredited 3rd-Party Logistics Partners
        $logisticsCompanies = LogisticsCompany::where('status', 'active')
            ->withCount(['orders' => function ($q) {
                $q->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery']);
            }, 'deliveryMen'])
            ->orderBy('name')
            ->get();

        // 5. Active LGAs for Filter
        $allLgas = Lga::active()->orderBy('name')->get();

        return view('admin-views.delivery.dispatch-portal', compact(
            'corridors', 'deliveryMen', 'logisticsCompanies', 'allLgas',
            'selectedOriginLgaId', 'selectedDestLgaId', 'selectedPackageTier',
            'selectedAssignmentStatus', 'totalOrdersCount', 'unassignedOrdersCount', 'largeCargoCount'
        ));
    }

    /**
     * Batch Assign Orders to either a Delivery Rider OR a Logistics Partner Company
     */
    public function assignBatch(Request $request): RedirectResponse
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id',
            'target_type' => 'required|in:rider,company',
        ]);

        $targetType = $request->target_type;
        $batchId = 'BATCH-' . strtoupper(Str::random(6)) . '-' . time();
        $selectedCount = count($request->order_ids);
        $assignedOrders = [];
        $targetEntity = null;

        try {
            DB::transaction(function () use ($request, $targetType, $batchId, $selectedCount, &$assignedOrders, &$targetEntity) {
                // 1. Lock and Validate Target Dispatch Principal
                if ($targetType === 'company') {
                    if (!$request->filled('logistics_company_id')) {
                        throw new \Exception(translate('Please select a 3rd-Party Logistics Company.'));
                    }
                    $company = LogisticsCompany::where('id', $request->logistics_company_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($company->status !== 'active') {
                        throw new \Exception(translate("Logistics company '{$company->name}' is currently {$company->status} and cannot receive dispatches."));
                    }
                    $targetEntity = $company;

                } else {
                    // Rider Assignment
                    if (!$request->filled('delivery_man_id')) {
                        throw new \Exception(translate('Please select a Delivery Rider.'));
                    }
                    $rider = DeliveryMan::where('id', $request->delivery_man_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (!$rider->is_active) {
                        throw new \Exception(translate("Selected rider {$rider->f_name} is currently inactive and cannot be assigned orders."));
                    }

                    // Check live workload capacity
                    $currentLoad = Order::where('delivery_man_id', $rider->id)
                        ->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery'])
                        ->lockForUpdate()
                        ->count();

                    $maxCapacity = $rider->max_active_orders_limit ?? 6;
                    if (($currentLoad + $selectedCount) > $maxCapacity) {
                        $availableSlots = max(0, $maxCapacity - $currentLoad);
                        throw new \Exception(translate("Capacity limit exceeded for {$rider->f_name}. Available slots: {$availableSlots}, selected: {$selectedCount}. Max capacity is {$maxCapacity}."));
                    }
                    $targetEntity = $rider;
                }

                // 2. Lock Selected Orders
                $orders = Order::whereIn('id', $request->order_ids)
                    ->lockForUpdate()
                    ->get();

                if ($orders->count() !== $selectedCount) {
                    throw new \Exception(translate('One or more selected orders could not be found.'));
                }

                // 3. Vehicle compatibility check for riders
                if ($targetType === 'rider' && in_array($targetEntity->vehicle_type, ['bicycle', 'motorcycle'])) {
                    $hasLargePackage = $orders->contains(function ($ord) {
                        return $ord->package_tier === 'large';
                    });
                    if ($hasLargePackage && $targetEntity->vehicle_type === 'bicycle') {
                        throw new \Exception(translate("This batch contains bulky cargo (Package Tier: LARGE). A bicycle rider cannot fulfill this batch. Please select a Van/Car rider or a Logistics Partner."));
                    }
                }

                // 4. Update Orders
                foreach ($orders as $order) {
                    if ($order->order_type === 'in_house_pickup' || $order->order_type === 'pickup') {
                        throw new \Exception(translate("Order #{$order->id} is an in-shop pickup order and cannot be assigned to delivery dispatch."));
                    }

                    if (!in_array($order->order_status, ['confirmed', 'processing', 'out_for_delivery'])) {
                        throw new \Exception(translate("Order #{$order->id} has status '{$order->order_status}' and cannot be dispatched."));
                    }

                    // Ensure Cryptographic 6-Digit OTPs Exist
                    if (empty($order->pickup_verification_code)) {
                        $order->pickup_verification_code = (string) random_int(100000, 999999);
                    }
                    if (empty($order->verification_code)) {
                        $order->verification_code = (string) random_int(100000, 999999);
                    }

                    if ($targetType === 'company') {
                        $order->logistics_company_id = $targetEntity->id;
                        $order->delivery_man_id = null; // Company dispatcher allocates from their own fleet
                        $companyRate = $targetEntity->getEffectiveCommissionRate();
                        $order->delivery_commission_amount = round(((float)$order->shipping_cost * $companyRate) / 100, 2);
                    } else {
                        $order->delivery_man_id = $targetEntity->id;
                        $order->logistics_company_id = $targetEntity->logistics_company_id ?? null;
                        if (!empty($targetEntity->logistics_company)) {
                            $companyRate = $targetEntity->logistics_company->getEffectiveCommissionRate();
                            $order->delivery_commission_amount = round(((float)$order->shipping_cost * $companyRate) / 100, 2);
                        }
                    }

                    $order->deliveryman_assigned_at = Carbon::now();
                    $order->batch_dispatch_id = $batchId;
                    $order->save();

                    $assignedOrders[] = $order;
                }
            });
        } catch (\Throwable $e) {
            ToastMagic::error($e->getMessage());
            return back();
        }

        // Send Push Notifications for Rider if applicable
        if ($targetType === 'rider' && !empty($targetEntity->fcm_token)) {
            foreach ($assignedOrders as $order) {
                try {
                    $data = [
                        'title' => translate('New Order Batch Assigned'),
                        'description' => translate("Order #{$order->id} assigned to you in batch {$batchId}"),
                        'order_id' => $order->id,
                        'image' => '',
                        'type' => 'order',
                    ];
                    Helpers::send_push_notif_to_device($targetEntity->fcm_token, $data);
                } catch (\Exception $e) {
                    // Notification fail-safe
                }
            }
        }

        $targetName = ($targetType === 'company') ? $targetEntity->name : ($targetEntity->f_name . ' ' . $targetEntity->l_name);
        ToastMagic::success(translate("Successfully dispatched {$selectedCount} order(s) to {$targetName} (Batch: {$batchId})"));
        return back();
    }

    /**
     * Fast 1-Click Inline Single Order Dispatch (AJAX)
     */
    public function assignSingle(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'target_type' => 'required|in:rider,company,unassign',
        ]);

        $orderId = $request->order_id;
        $targetType = $request->target_type;

        try {
            DB::transaction(function () use ($request, $orderId, $targetType) {
                $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail();

                if (empty($order->pickup_verification_code)) {
                    $order->pickup_verification_code = (string) random_int(100000, 999999);
                }
                if (empty($order->verification_code)) {
                    $order->verification_code = (string) random_int(100000, 999999);
                }

                if ($targetType === 'unassign') {
                    $order->delivery_man_id = null;
                    $order->logistics_company_id = null;
                    $order->save();
                    return;
                }

                if ($targetType === 'company') {
                    $companyId = $request->target_id;
                    $company = LogisticsCompany::where('id', $companyId)->firstOrFail();
                    if ($company->status !== 'active') {
                        throw new \Exception(translate('Selected company is not active.'));
                    }
                    $order->logistics_company_id = $company->id;
                    $order->delivery_man_id = null;
                } else {
                    $riderId = $request->target_id;
                    $rider = DeliveryMan::where('id', $riderId)->firstOrFail();
                    if (!$rider->is_active) {
                        throw new \Exception(translate('Selected rider is inactive.'));
                    }
                    $order->delivery_man_id = $rider->id;
                    $order->logistics_company_id = $rider->logistics_company_id ?? null;
                }

                $order->deliveryman_assigned_at = now();
                $order->save();
            });

            return response()->json([
                'status' => true,
                'message' => translate('Order dispatch updated successfully'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Print Canonical Route Dispatch Manifest (Rider Trip Sheet)
     */
    public function printBatchManifest(Request $request): View|RedirectResponse
    {
        $orderIds = $request->get('order_ids');
        if (is_string($orderIds)) {
            $orderIds = explode(',', $orderIds);
        }

        if (empty($orderIds)) {
            ToastMagic::error(translate('No orders selected for manifest'));
            return back();
        }

        $orders = Order::with(['seller.shop', 'deliveryMan', 'customer', 'details', 'logisticsCompany'])
            ->whereIn('id', $orderIds)
            ->get();

        if ($orders->isEmpty()) {
            ToastMagic::error(translate('No matching orders found'));
            return back();
        }

        $firstOrder = $orders->first();
        $batchId = $firstOrder->batch_dispatch_id ?? ('MANIFEST-' . strtoupper(Str::random(6)));
        $originName = !empty($firstOrder->origin_lga_name) ? $firstOrder->origin_lga_name : ($firstOrder->seller?->shop?->name ?? 'Origin');
        $destName = !empty($firstOrder->destination_lga_name) ? $firstOrder->destination_lga_name : 'Destination Corridor';
        $destCity = $firstOrder->destination_state_name ?? 'Akwa Ibom';
        $deliveryMan = $firstOrder->deliveryMan;
        $logisticsCompany = $firstOrder->logisticsCompany;

        $companyName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
        $companyPhone = getWebConfig(name: 'company_phone');

        return view('admin-views.delivery.batch-manifest', compact(
            'orders', 'batchId', 'originName', 'destName', 'destCity', 'deliveryMan', 'logisticsCompany', 'companyName', 'companyPhone'
        ));
    }

    /**
     * Print Official Parcel Shipping Waybill Label (Thermal Sticker)
     */
    public function printWaybill(string|int $id): View|RedirectResponse
    {
        $order = Order::with(['seller.shop', 'deliveryMan', 'customer', 'details', 'logisticsCompany'])
            ->find($id);

        if (!$order) {
            ToastMagic::error(translate('Order not found'));
            return back();
        }

        $companyName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
        $companyPhone = getWebConfig(name: 'company_phone');

        return view('admin-views.delivery.waybill-label', compact('order', 'companyName', 'companyPhone'));
    }
}
