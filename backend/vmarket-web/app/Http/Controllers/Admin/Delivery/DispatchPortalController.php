<?php

namespace App\Http\Controllers\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryHub;
use App\Models\DeliveryMan;
use App\Models\Lga;
use App\Models\Order;
use App\Models\State;
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
        $selectedLgaId = $request->get('lga_id');
        $selectedDeliveryType = $request->get('delivery_type');

        // 1. Fetch Active Orders eligible for Dispatch
        $ordersQuery = Order::with(['seller.shop.deliveryHub', 'originHub', 'destinationHub', 'deliveryMan', 'customer'])
            ->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery'])
            ->where('order_type', 'default_type');

        if ($selectedDeliveryType) {
            $ordersQuery->where('delivery_type', $selectedDeliveryType);
        }

        if ($selectedLgaId) {
            $ordersQuery->whereHas('destinationHub', function ($q) use ($selectedLgaId) {
                $q->where('lga_id', $selectedLgaId);
            });
        }

        $allOrders = $ordersQuery->latest()->get();

        // 2. Cluster Orders by Corridor (Origin Hub -> Destination Hub)
        $corridors = [];
        foreach ($allOrders as $order) {
            $originName = $order->originHub?->name ?? ($order->seller?->shop?->deliveryHub?->name ?? translate('Uyo Central Hub'));
            $originId = $order->origin_hub_id ?? ($order->seller?->shop?->delivery_hub_id ?? 0);
            
            $destName = $order->destinationHub?->name ?? translate('General Area');
            $destId = $order->destination_hub_id ?? 0;
            $destType = $order->destinationHub?->type ?? 'landmark';

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
            ->with(['deliveryHub'])
            ->withCount(['orders' => function ($q) {
                $q->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery']);
            }])
            ->get();

        $states = State::active()->orderBy('name')->get();
        $lgas = $selectedStateId
            ? Lga::where('state_id', $selectedStateId)->active()->orderBy('name')->get()
            : Lga::active()->orderBy('name')->get();

        return view('admin-views.delivery.dispatch-portal', compact('corridors', 'deliveryMen', 'states', 'lgas', 'selectedStateId', 'selectedLgaId', 'selectedDeliveryType'));
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

        $customRiderFee = $request->filled('custom_rider_fee') ? (float) $request->custom_rider_fee : null;
        $batchId = 'BATCH-' . strtoupper(Str::random(6)) . '-' . time();
        $selectedCount = count($request->order_ids);
        $assignedOrders = [];
        $deliveryMan = null;

        try {
            DB::transaction(function () use ($request, $customRiderFee, $batchId, $selectedCount, &$assignedOrders, &$deliveryMan) {
                // Lock rider and enforce active status
                $deliveryMan = DeliveryMan::where('id', $request->delivery_man_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (isset($deliveryMan->is_active) && !$deliveryMan->is_active) {
                    throw new \Exception(translate("Selected rider {$deliveryMan->f_name} is currently inactive and cannot be assigned orders."));
                }

                if (isset($deliveryMan->application_status) && $deliveryMan->application_status !== 'approved') {
                    throw new \Exception(translate("Selected rider {$deliveryMan->f_name} does not have an approved application."));
                }

                // Lock and count active orders for capacity
                $currentLoad = Order::where('delivery_man_id', $deliveryMan->id)
                    ->whereIn('order_status', ['confirmed', 'processing', 'out_for_delivery'])
                    ->lockForUpdate()
                    ->count();

                $maxCapacity = $deliveryMan->max_active_orders_limit ?? 4;
                if (($currentLoad + $selectedCount) > $maxCapacity) {
                    $availableSlots = max(0, $maxCapacity - $currentLoad);
                    throw new \Exception(translate("Capacity limit exceeded for {$deliveryMan->f_name}. Available slots: {$availableSlots}, selected: {$selectedCount}. Max capacity is {$maxCapacity}."));
                }

                // Lock selected orders
                $orders = Order::with(['originHub', 'destinationHub'])
                    ->whereIn('id', $request->order_ids)
                    ->lockForUpdate()
                    ->get();

                if ($orders->count() !== $selectedCount) {
                    throw new \Exception(translate('One or more selected orders could not be found.'));
                }

                foreach ($orders as $order) {
                    // 1. Must be delivery type (not pickup)
                    if ($order->order_type === 'in_house_pickup' || $order->order_type === 'pickup') {
                        throw new \Exception(translate("Order #{$order->id} is an in-store pickup order and cannot be assigned to a delivery rider."));
                    }

                    // 2. Must be paid
                    if ($order->payment_status !== 'paid') {
                        throw new \Exception(translate("Order #{$order->id} is unpaid. Only paid orders can be dispatched."));
                    }

                    // 3. Must be in assignable status
                    if (!in_array($order->order_status, ['confirmed', 'processing'])) {
                        throw new \Exception(translate("Order #{$order->id} has status '{$order->order_status}'. Only confirmed or processing orders can be dispatched."));
                    }

                    // Ensure Pickup OTP exists (6 digits, CSPRNG)
                    if (empty($order->pickup_verification_code)) {
                        $order->pickup_verification_code = (string) random_int(100000, 999999);
                    }
                    // Ensure Delivery OTP exists (6 digits, CSPRNG)
                    if (empty($order->verification_code)) {
                        $order->verification_code = (string) random_int(100000, 999999);
                    }

                    // Standard Rider Payout Fee (capped at order shipping_cost if authority exists)
                    if ($customRiderFee !== null) {
                        $order->deliveryman_charge = min($customRiderFee, (float)($order->shipping_cost > 0 ? $order->shipping_cost : $customRiderFee));
                    } elseif ($order->destinationHub && $order->destinationHub->rider_delivery_fee > 0) {
                        $order->deliveryman_charge = min((float)$order->destinationHub->rider_delivery_fee, (float)($order->shipping_cost > 0 ? $order->shipping_cost : $order->destinationHub->rider_delivery_fee));
                    } else {
                        $isInterstate = ($order->destinationHub && $order->destinationHub->type == 'motor_park')
                            || ($order->originHub && $order->destinationHub && $order->originHub->lga_id != $order->destinationHub->lga_id);
                        $defaultFee = $isInterstate ? 1000.00 : 500.00;
                        $order->deliveryman_charge = min((float)$defaultFee, (float)($order->shipping_cost > 0 ? $order->shipping_cost : $defaultFee));
                    }

                    $order->delivery_man_id = $deliveryMan->id;
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

        // Send Push Notifications outside transaction
        if ($deliveryMan && !empty($deliveryMan->fcm_token)) {
            foreach ($assignedOrders as $order) {
                try {
                    $data = [
                        'title' => translate('New Order Batch Assigned'),
                        'description' => translate("Order #{$order->id} assigned to you in batch {$batchId}"),
                        'order_id' => $order->id,
                        'image' => '',
                        'type' => 'order',
                    ];
                    Helpers::send_push_notif_to_device($deliveryMan->fcm_token, $data);
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

        $orders = Order::with(['seller.shop.deliveryHub', 'originHub', 'destinationHub.lga.state', 'deliveryMan', 'customer', 'details'])
            ->whereIn('id', $orderIds)
            ->get();

        if ($orders->isEmpty()) {
            ToastMagic::error(translate('No matching orders found'));
            return back();
        }

        $firstOrder = $orders->first();
        $batchId = $firstOrder->batch_dispatch_id ?? ('MANIFEST-' . strtoupper(Str::random(6)));
        $originName = $firstOrder->originHub?->name ?? ($firstOrder->seller?->shop?->deliveryHub?->name ?? 'Plaza / Central Sorting Hub');
        $destName = $firstOrder->destinationHub?->name ?? 'General Landmark Corridor';
        $destCity = $firstOrder->destinationHub?->lga?->state?->name ?? 'Akwa Ibom';
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
        $order = Order::with(['seller.shop.deliveryHub', 'originHub', 'destinationHub.lga.state', 'deliveryMan', 'customer', 'details'])
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
