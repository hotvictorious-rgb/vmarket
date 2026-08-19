<?php

namespace App\Http\Controllers\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCity;
use App\Models\DeliveryHub;
use App\Models\DeliveryMan;
use App\Models\DeliveryState;
use App\Models\Order;
use App\Utils\Helpers;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DispatchPortalController extends Controller
{
    /**
     * Display the Corridor & Cluster Batch Dispatch Portal
     */
    public function index(Request $request): View
    {
        $selectedStateId = $request->get('state_id');
        $selectedCityId = $request->get('city_id');
        $selectedDeliveryType = $request->get('delivery_type');

        // 1. Fetch Active Orders eligible for Dispatch
        $ordersQuery = Order::with(['seller.shop.deliveryHub', 'originHub', 'destinationHub', 'deliveryMan', 'customer'])
            ->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery'])
            ->where('order_type', 'default_type');

        if ($selectedDeliveryType) {
            $ordersQuery->where('delivery_type', $selectedDeliveryType);
        }

        if ($selectedCityId) {
            $ordersQuery->whereHas('destinationHub', function ($q) use ($selectedCityId) {
                $q->where('city_id', $selectedCityId);
            });
        }

        $allOrders = $ordersQuery->latest()->get();

        // 2. Cluster Orders by Corridor (Origin Hub -> Destination Hub)
        $corridors = [];
        foreach ($allOrders as $order) {
            $originName = $order->originHub->name ?? ($order->seller->shop->deliveryHub->name ?? translate('Uyo Central Hub'));
            $originId = $order->origin_hub_id ?? ($order->seller->shop->delivery_hub_id ?? 0);
            
            $destName = $order->destinationHub->name ?? translate('General Area');
            $destId = $order->destination_hub_id ?? 0;
            $destType = $order->destinationHub->type ?? 'landmark';

            $corridorKey = $originId . '_' . $destId . '_' . $destType;

            if (!isset($corridors[$corridorKey])) {
                $corridors[$corridorKey] = [
                    'key' => $corridorKey,
                    'origin_name' => $originName,
                    'origin_id' => $originId,
                    'dest_name' => $destName,
                    'dest_id' => $destId,
                    'dest_type' => $destType,
                    'orders' => [],
                    'total_amount' => 0,
                    'unassigned_count' => 0,
                ];
            }

            $corridors[$corridorKey]['orders'][] = $order;
            $corridors[$corridorKey]['total_amount'] += $order->order_amount;
            if (!$order->delivery_man_id) {
                $corridors[$corridorKey]['unassigned_count']++;
            }
        }

        // 3. Fetch Delivery Men with Active Workload calculation
        $deliveryMen = DeliveryMan::where('is_active', 1)
            ->withCount(['orders' => function ($q) {
                $q->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery']);
            }])
            ->get();

        $states = DeliveryState::where('is_active', true)->get();
        $cities = $selectedStateId ? DeliveryCity::where('state_id', $selectedStateId)->where('is_active', true)->get() : DeliveryCity::where('is_active', true)->get();

        return view('admin-views.delivery.dispatch-portal', compact('corridors', 'deliveryMen', 'states', 'cities', 'selectedStateId', 'selectedCityId', 'selectedDeliveryType'));
    }

    /**
     * Assign Batch of Selected Orders to a Delivery Rider with Capacity Validation
     */
    public function assignBatch(Request $request): RedirectResponse
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id',
            'delivery_man_id' => 'required|exists:delivery_men,id',
        ]);

        $deliveryMan = DeliveryMan::withCount(['orders' => function ($q) {
            $q->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery']);
        }])->findOrFail($request->delivery_man_id);

        $selectedCount = count($request->order_ids);
        $currentLoad = $deliveryMan->orders_count;
        $maxCapacity = $deliveryMan->max_active_orders_limit ?? 4;

        // Rider Capacity Guard Check
        if (($currentLoad + $selectedCount) > $maxCapacity) {
            $availableSlots = max(0, $maxCapacity - $currentLoad);
            ToastMagic::error(translate("Capacity limit exceeded for {$deliveryMan->f_name}. Available slots: {$availableSlots}, selected: {$selectedCount}. Max capacity is {$maxCapacity}."));
            return back();
        }

        $batchId = 'BATCH-' . strtoupper(Str::random(6)) . '-' . time();
        $customRiderFee = $request->filled('custom_rider_fee') ? (float) $request->custom_rider_fee : null;

        foreach ($request->order_ids as $orderId) {
            $order = Order::with(['originHub', 'destinationHub'])->find($orderId);
            if ($order) {
                // Ensure Pickup OTP exists (4 digits)
                if (empty($order->pickup_verification_code)) {
                    $order->pickup_verification_code = (string) rand(1000, 9999);
                }
                // Ensure Delivery OTP exists (4 digits)
                if (empty($order->verification_code)) {
                    $order->verification_code = (string) rand(1000, 9999);
                }

                // [AI] Standard Rider Payout Fee (Independent from Customer Shipping Fee)
                if ($customRiderFee !== null) {
                    $order->deliveryman_charge = $customRiderFee;
                } elseif ($order->destinationHub && $order->destinationHub->rider_delivery_fee > 0) {
                    $order->deliveryman_charge = $order->destinationHub->rider_delivery_fee;
                } else {
                    $isInterstate = ($order->destinationHub && $order->destinationHub->type == 'motor_park')
                        || ($order->originHub && $order->destinationHub && $order->originHub->city_id != $order->destinationHub->city_id);
                    $order->deliveryman_charge = $isInterstate ? 1000.00 : 500.00;
                }

                $order->delivery_man_id = $deliveryMan->id;
                $order->deliveryman_assigned_at = Carbon::now();
                $order->batch_dispatch_id = $batchId;
                $order->save();

                // Send Push Notification to Delivery Man
                try {
                    $fcmToken = $deliveryMan->fcm_token;
                    if (!empty($fcmToken)) {
                        $data = [
                            'title' => translate('New Order Batch Assigned'),
                            'description' => translate("Order #{$order->id} assigned to you in batch {$batchId}"),
                            'order_id' => $order->id,
                            'image' => '',
                            'type' => 'order',
                        ];
                        Helpers::send_push_notif_to_device($fcmToken, $data);
                    }
                } catch (\Exception $e) {
                    // Fail-safe notification catch
                }
            }
        }

        ToastMagic::success(translate("Successfully assigned {$selectedCount} order(s) to {$deliveryMan->f_name} {$deliveryMan->l_name} (Batch: {$batchId})"));
        return back();
    }

    /**
     * Print Corridor Batch Dispatch Manifest (Rider Trip Sheet)
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

        $orders = Order::with(['seller.shop.deliveryHub', 'originHub', 'destinationHub.city.state', 'deliveryMan', 'customer', 'details'])
            ->whereIn('id', $orderIds)
            ->get();

        if ($orders->isEmpty()) {
            ToastMagic::error(translate('No matching orders found'));
            return back();
        }

        $firstOrder = $orders->first();
        $batchId = $firstOrder->batch_dispatch_id ?? ('MANIFEST-' . strtoupper(Str::random(6)));
        $originName = $firstOrder->originHub->name ?? ($firstOrder->seller->shop->deliveryHub->name ?? 'Plaza / Central Sorting Hub');
        $destName = $firstOrder->destinationHub->name ?? 'General Landmark Corridor';
        $destCity = $firstOrder->destinationHub->city->name ?? 'Uyo';
        $deliveryMan = $firstOrder->deliveryMan;

        $companyName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
        $companyPhone = getWebConfig(name: 'company_phone');

        return view('admin-views.delivery.batch-manifest', compact(
            'orders', 'batchId', 'originName', 'destName', 'destCity', 'deliveryMan', 'companyName', 'companyPhone'
        ));
    }

    /**
     * Print Official Parcel Shipping Waybill Label (4x6 / Thermal Sticker)
     */
    public function printWaybill(string|int $id): View|RedirectResponse
    {
        $order = Order::with(['seller.shop.deliveryHub', 'originHub', 'destinationHub.city.state', 'deliveryMan', 'customer', 'details'])
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
