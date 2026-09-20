<?php

namespace App\Http\Controllers\RestAPI\v3\seller;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Events\OrderStatusEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\v3\DigitalProductFileUploadAfterSell;
use App\Models\BusinessSetting;
use App\Models\DeliveryManTransaction;
use App\Models\DeliverymanWallet;
use App\Models\DeliveryZipCode;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ReferralCustomer;
use App\Traits\CommonTrait;
use App\Models\User;
use App\Traits\ProductTrait;
use App\Utils\BackEndHelper;
use App\Utils\Convert;
use App\Utils\CustomerManager;
use App\Utils\Helpers;
use App\Utils\ImageManager;
use App\Utils\OrderManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;


class OrderController extends Controller
{
    use CommonTrait;
    use ProductTrait;

    public function __construct(
        private DeliveryZipCode                   $delivery_zip_code,
        private Order                             $order,
        private readonly OrderRepositoryInterface $orderRepo,
    )
    {
    }

    public function list(Request $request): JsonResponse
    {
        $seller = $request->seller;
        $status = $request->status;

        $dateType = $request['date_type'];
        $paymentPaidStatus = json_decode($request['payment_status'] ?? '', true);
        $orderStatus = json_decode($request['order_current_status'] ?? '', true);

        $filters = [
            'filter' => $request['filter'] ?? 'all',
            'date_type' => $request['date_type'],
            'from' => $request['start_date'],
            'to' => $request['end_date'],
            'delivery_man_id' => $request['delivery_man_id'],
            'customer_id' => $request['customer_id'],
            'seller_id' => $seller['id'],
            'seller_is' => 'seller',
            'whereIn_order_status' => $orderStatus,
        ];
        $orderAmountSettlement = json_decode($request['order_amount_settlement'] ?? '');

        if (!empty($orderAmountSettlement)) {
            $filters['has_order_edit_settlement'] = $orderAmountSettlement;
        }

        $filterWhereIn = [];
        if (!empty($paymentPaidStatus)) {
            $filterWhereIn['payment_status'] = $paymentPaidStatus;
        }

        $orderTypes = json_decode($request['order_types'] ?? '');
        if (!empty($orderTypes)) {
            $filterWhereIn['order_type'] = $orderTypes;
        }

        $orders = $this->orderRepo->getListWhereIn(
            orderBy: ['id' => 'desc'],
            searchValue: $request['search_value'],
            filters: $filters,
            whereIn: $filterWhereIn,
            relations: ['customer', 'shipping', 'deliveryMan', 'orderDetails', 'offlinePayments'],
            dataLimit: $request['limit']
        );

        $orders?->map(function ($data) {
            if (isset($data['offlinePayments'])) {
                $data['offlinePayments']->payment_info = $data->offlinePayments->payment_info;
            }

            $totalTaxAmount = 0;
            $totalProductPrice = 0;
            $totalProductDiscount = 0;
            if (isset($data['orderDetails']) && count($data['orderDetails']) > 0) {
                $totalTaxAmount = $data['orderDetails']->sum('tax');
                $totalProductPrice = $data['orderDetails']->sum('price');
                $totalProductDiscount = $data['orderDetails']->sum('discount');
            }
            $data['total_tax_amount'] = $totalTaxAmount;
            $data['total_product_price'] = $totalProductPrice;
            $data['total_product_discount'] = $totalProductDiscount;
            $this->maskOrderData($data);
            return $data;
        });

        return response()->json([
            'total_size' => $orders->total(),
            'limit' => (int)$request['limit'],
            'offset' => (int)$request['offset'],
            'orders' => $orders->items()
        ], 200);
    }

    public function details(Request $request, $id): JsonResponse
    {
        $seller = $request->seller;
        $detailsList = OrderDetail::with(['order.offlinePayments', 'order.customer', 'order.deliveryMan', 'order.shippingAddress', 'order.billingAddress', 'verificationImages'])->where(['seller_id' => $seller['id'], 'order_id' => $id])->get();

        $productList = $this->getProductListWithAllDetails(ids: $detailsList?->pluck('product_id')->toArray());

        $paymentInfo = collect();

        $firstDetails = $detailsList->first();
        if ($firstDetails?->init_order_amount <= 0 && $firstDetails?->order) {
            Order::where('id', $firstDetails->order->id)->update(['init_order_amount' => $firstDetails->order['order_amount']]);
        }

        foreach ($detailsList as $detail) {
            $product = json_decode($detail['product_details'], true) ?? [];

            if (!isset($product['digital_variation'])) {
                $product['digital_variation'] = [];
            }

            $product['thumbnail_full_url'] = $detail?->productAllStatus?->thumbnail_full_url;
            if (isset($product['product_type']) && $product['product_type'] == 'digital' && $product['digital_product_type'] == 'ready_product' && $product['digital_file_ready']) {
                $checkFilePath = storageLink('product/digital-product', $product['digital_file_ready'], ($product['storage_path'] ?? 'public'));
                $product['digital_file_ready_full_url'] = $checkFilePath;
            }
            $detail['product_details'] = Helpers::product_data_formatting_for_json_data($product);


            $detailsVariation = is_array($detail['variation']) ? $detail['variation'] : json_decode($detail['variation'] ?? '', true);
            $modifiedVariation = [];
            if (is_array($detailsVariation) && count($detailsVariation) > 0) {
                foreach ($detailsVariation as $variationKey => $variation) {
                    $modifiedVariation[] = [
                        'key' => $variationKey,
                        'value' => $variation,
                    ];
                }
            }

            $detail['variation'] = $modifiedVariation;
            $detail['modified_variation'] = $modifiedVariation;

            $activeProduct = $productList?->firstWhere('id', $detail['product_id']) ?? $product;
            $unitPrice = $activeProduct ? $activeProduct['unit_price'] : $detail['price'];
            $currentStock = max(0, $activeProduct['current_stock'] ?? 0);
            $variations = is_array($activeProduct['variation'] ?? null) ? $activeProduct['variation'] : json_decode($activeProduct['variation'] ?? '', true);
            $firstVariation = collect($variations)->first(function ($variation) use ($detail) {
                return ($variation['type'] ?? '') == $detail['variant'];
            });

            if ($detail['variant'] && $firstVariation) {
                $currentStock = $firstVariation['qty'] ?? 0;
                $unitPrice = $firstVariation['price'] ?? 0;
            }

            if ($detail && ($detail['is_stock_decreased'] ?? 0) == 1) {
                $currentStock += $detail['qty'] ?? 1;
            }

            $detail['current_stock'] = $currentStock;
            $detail['current_price'] = $unitPrice;
            $detail['edit_order_payment_histories'] = $paymentInfo;
            if ($detail->order) {
                $this->maskOrderData($detail->order);
            }
        }

        return response()->json($detailsList, 200);
    }

    public function assign_delivery_man(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'delivery_man_id' => 'required',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)]);
        }

        $seller = $request->seller;
        $order = Order::with('deliveryMan')->where(['seller_id' => $seller['id'], 'id' => $request['order_id']])->first();

        if (!$order) {
            return response()->json(['success' => 0, 'message' => translate('order_not_found')], 404);
        }

        // [AI] Path Isolation Invariant: Customer self-pickup orders can NEVER be assigned to delivery riders
        $isSelfPickup = ($order->order_type === 'pickup')
            || ($order->delivery_type === 'self_pickup')
            || ($order->shipping && stripos($order->shipping->title, 'pickup') !== false);
        if ($isSelfPickup) {
            return response()->json(['success' => 0, 'message' => translate('Customer self-pickup orders cannot be assigned to delivery riders.')], 403);
        }

        if ($order['delivery_man_id'] != $request['delivery_man_id']) {
            $order->deliveryman_assigned_at = Carbon::now();
        }
        $order->delivery_man_id = $request['delivery_man_id'];
        $order->delivery_type = 'self_delivery';
        $order->delivery_service_name = null;
        $order->third_party_delivery_tracking_id = null;
        $order->save();
        OrderStatusEvent::dispatch('new_order_assigned_message', 'delivery_man', $order);
        return response()->json(['success' => 1, 'message' => translate('order_deliveryman_assigned_successfully')], 200);
    }

    public function amount_date_update(Request $request): JsonResponse
    {
        $seller = $request->seller;

        $deliveryManCharge = $request->deliveryman_charge;

        // [AI] Ownership Guard: Only update order belonging to authenticated seller
        $order = Order::with('deliveryMan')->where(['id' => $request->order_id, 'seller_id' => $seller['id']])->first();
        if (!$order) {
            return response()->json(['success' => 0, 'message' => translate('unauthorized_access')], 403);
        }
        $db_expected_date = $order->expected_delivery_date;

        $order->deliveryman_charge = $deliveryManCharge;
        $order->expected_delivery_date = $request->expected_delivery_date;

        try {
            DB::beginTransaction();

            if (!empty($request->expected_delivery_date) && $db_expected_date != $request->expected_delivery_date) {
                CommonTrait::add_expected_delivery_date_history($request->order_id, $seller['id'], $request->expected_delivery_date, 'seller');
            }
            $order->save();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json(['success' => 0, 'message' => translate('Update fail!')], 403);
        }

        if (!empty($request->expected_delivery_date) && $db_expected_date != $request->expected_delivery_date) {
            OrderStatusEvent::dispatch('expected_delivery_date', 'delivery_man', $order);
        }

        return response()->json(['success' => 0, 'message' => translate('Updated successfully!')], 200);
    }

    /**
     *  Digital file upload after sell
     */
    public function digital_file_upload_after_sell(DigitalProductFileUploadAfterSell $request): JsonResponse
    {
        $seller = $request->seller;
        // [AI] Ownership Guard: Only update order details belonging to authenticated seller
        $order_details = OrderDetail::where(['id' => $request->order_id, 'seller_id' => $seller['id']])->first();
        if ($order_details) {
            $order_details->digital_file_after_sell = ImageManager::update('product/digital-product/', $order_details->digital_file_after_sell, $request->digital_file_after_sell->getClientOriginalExtension(), $request->file('digital_file_after_sell'), 'file');
            $order_details->save();
            return response()->json(['success' => 1, 'message' => translate('File_upload_successfully')], 200);
        } else {
            return response()->json(['success' => 0, 'message' => translate("File_upload_fail!")], 202);
        }
    }

    public function order_detail_status(Request $request): JsonResponse
    {
        $seller = $request->seller;
        // [AI] Ownership Guard: Only update order status for seller's own orders
        $order = Order::with(['customer', 'seller.shop', 'deliveryMan'])->where(['id' => $request['id'], 'seller_id' => $seller['id']])->first();
        if (!$order) {
            return response()->json(['success' => 0, 'message' => translate('unauthorized_access')], 403);
        }
        if (!$order->is_guest && empty($order->customer)) {
            return response()->json(['success' => 0, 'message' => translate("Customer_account_has_been_deleted") . ' ' . translate("you_cant_update_status")], 202);
        }

        $walletStatus = getWebConfig(name: 'wallet_status');
        $loyaltyPointStatus = getWebConfig(name: 'loyalty_point_status');

        $isSelfPickup = ($order->order_type === 'pickup')
            || ($order->delivery_type === 'self_pickup')
            || ($order->shipping && stripos($order->shipping->title, 'pickup') !== false);

        // [AI] Machine-Enforced Invariant 1: pickup + out_for_delivery -> 403 Forbidden
        if ($isSelfPickup && $request['order_status'] === 'out_for_delivery') {
            return response()->json([
                'success' => 0,
                'message' => translate('Customer self-pickup orders never enter out for delivery state.')
            ], 403);
        }

        // [AI] Machine-Enforced Invariant 2: delivery + vendor -> delivered -> 403 Forbidden
        // Delivery orders must be delivered and verified by the assigned delivery rider via customer delivery OTP
        if (!$isSelfPickup && $request['order_status'] === 'delivered') {
            return response()->json([
                'success' => 0,
                'message' => translate('Delivery orders must be delivered and verified by the assigned Victorious Delivery rider via customer delivery OTP.')
            ], 403);
        }

        // [AI] Machine-Enforced Invariant 3: delivery + vendor -> out_for_delivery -> 403 Forbidden
        // Delivery orders enter out_for_delivery ONLY when rider arrives and verifies pickup OTP
        if (!$isSelfPickup && $request['order_status'] === 'out_for_delivery') {
            return response()->json([
                'success' => 0,
                'message' => translate('Delivery orders transition to out for delivery only upon rider pickup OTP custody verification.')
            ], 403);
        }

        // [AI] Machine-Enforced Invariant 4: unverified decommissioned payment method + fulfillment -> 403 Forbidden
        if ($order['payment_status'] !== 'paid' && in_array($order['payment_method'], ['offline_payment', 'cash_on_delivery'])) {
            return response()->json([
                'success' => 0,
                'message' => translate('Unverified orders cannot be fulfilled until digital payment is confirmed.')
            ], 403);
        }

        // [AI] Machine-Enforced Invariant 5: pickup + delivered requires verifyPickupOtp handshake
        if ($isSelfPickup && $request['order_status'] === 'delivered') {
            return response()->json([
                'success' => 0,
                'message' => translate('Customer self-pickup orders must be completed via the Secret Pickup OTP handshake endpoint.')
            ], 403);
        }

        // [AI] Strict Completion Authority Invariant:
        // Generic seller REST endpoints do not have delivery completion authority for marketplace orders.
        // Delivery orders require verified doorstep customer OTP, and pickup orders require verified in-shop handover OTP.
        if (\App\Utils\OrderManager::isVictoriousMarketplaceOrder($order) && $request['order_status'] === 'delivered') {
            return response()->json([
                'status' => false,
                'message' => translate('Generic seller status endpoints cannot mark marketplace orders delivered. Delivery orders require doorstep customer OTP verification, and pickup orders require in-shop handover OTP verification.'),
            ], 403);
        }

        event(new OrderStatusEvent(key: $request['order_status'], type: 'customer', order: $order));
        if ($request->order_status == 'canceled') {
            event(new OrderStatusEvent(key: 'canceled', type: 'delivery_man', order: $order));
        }

        $order->order_status = $request['order_status'];
        if ($request['order_status'] == 'delivered') {
            // [AI] Only COD orders transition payment_status to 'paid' upon vendor delivery
            $newPaymentStatus = ($order['payment_method'] === 'cash_on_delivery') ? 'paid' : $order['payment_status'];
            $order->payment_status = $newPaymentStatus;
            Order::where('id', $order->id)->update(['payment_status' => $newPaymentStatus, 'is_pause' => 0]);
            OrderDetail::where('order_id', $order->id)->update(['delivery_status' => 'delivered', 'payment_status' => $newPaymentStatus]);
            OrderDetail::where('order_id', $order['id'])->whereNull('refund_started_at')->update(['refund_started_at' => now()]);
        }
        OrderManager::getStockUpdateOnOrderStatusChange($order, $request->order_status);
        if ($request->order_status == 'delivered' && $order['seller_id'] != null) {
            $refreshedOrder = Order::find($order->id);
            // [AI] Settlement Invariant: Settlement occurs only if order is verified as paid
            if ($refreshedOrder && $refreshedOrder->payment_status === 'paid') {
                OrderManager::getWalletManageOnOrderStatusChange($refreshedOrder, 'seller');
            }
        }

        $order->save();

        if ($order->delivery_man_id && $request->order_status == 'delivered') {
            $deliverymanWallet = DeliverymanWallet::where('delivery_man_id', $order->delivery_man_id)->first();

            if (empty($deliverymanWallet)) {
                DeliverymanWallet::create([
                    'delivery_man_id' => $order->delivery_man_id,
                    'current_balance' => $order?->deliveryman_charge ?? 0,
                    'cash_in_hand' => 0,
                    'pending_withdraw' => 0,
                    'total_withdraw' => 0,
                ]);
            } else {
                $deliverymanWallet->current_balance += $order?->deliveryman_charge ?? 0;
                $deliverymanWallet->save();
            }

            if ($order->deliveryman_charge && $request->order_status == 'delivered') {
                DeliveryManTransaction::create([
                    'delivery_man_id' => $order->delivery_man_id,
                    'user_id' => $seller->id,
                    'user_type' => 'seller',
                    'credit' => $order?->deliveryman_charge ?? 0,
                    'transaction_id' => Uuid::uuid4(),
                    'transaction_type' => 'deliveryman_charge'
                ]);
            }
        }

        if ($request['order_status'] == 'delivered') {
            $referredUser = ReferralCustomer::where('user_id', $order?->customer?->id)->first();
            if ($referredUser?->delivered_notify != 1) {
                event(new OrderStatusEvent(key: 'your_referred_customer_order_has_been_delivered', type: 'promoter', order: $order));
                ReferralCustomer::where('user_id', $order?->customer?->id)->update(['delivered_notify' => 1]);
            }
        }
        self::add_order_status_history($order->id, $seller->id, $request->order_status, 'seller');

        return response()->json(['success' => 1, 'message' => translate('order_status_updated_successfully')], 200);
    }

    public function assign_third_party_delivery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'delivery_service_name' => 'required',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)]);
        }

        $seller = $request->seller;
        // [AI] Ownership Guard: Only assign third party delivery to own orders
        $order = Order::where(['id' => $request->order_id, 'seller_id' => $seller['id']])->first();
        if (!$order) {
            return response()->json(['success' => 0, 'message' => translate('unauthorized_access')], 403);
        }
        $order->delivery_type = 'third_party_delivery';
        $order->delivery_service_name = $request->delivery_service_name;
        $order->third_party_delivery_tracking_id = $request->third_party_delivery_tracking_id;
        $order->delivery_man_id = null;
        $order->deliveryman_charge = 0;
        $order->expected_delivery_date = null;
        $order->save();

        return response()->json(['success' => 1, 'message' => translate('third_party_delivery_assigned_successfully')], 200);
    }

    public function update_payment_status(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'payment_status' => 'required|in:paid,unpaid'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }
        if ($request->payment_status != 'paid') {
            return response()->json(['success' => 0, 'message' => translate('When payment status paid then you can`t change payment status paid to unpaid') . '.'], 200);
        }
        $seller = $request->seller;
        // [AI] Ownership Guard: Only update payment status for own orders
        $order = Order::where(['id' => $request['order_id'], 'seller_id' => $seller['id']])->first();
        if (isset($order)) {
            if ($order->is_guest == '0' && empty($order->customer)) {
                return response()->json(['success' => 0, 'message' => translate("Customer account has been deleted. you can't update status!")], 202);
            }

            // [AI] Payment Authority Invariant: Victorious MARKET backend / payment gateway / admin is the sole payment authority.
            // Vendors CANNOT manually declare Paystack, bank transfer, or any other digital payment as paid.
            if ($order['payment_method'] !== 'cash_on_delivery') {
                return response()->json([
                    'errors' => [
                        ['code' => 'payment_method', 'message' => translate('Only the payment gateway or admin can verify digital or offline payments. Vendors cannot manually update payment status.')]
                    ]
                ], 403);
            }

            if ($order['payment_method'] == 'cash_on_delivery' && $order['order_status'] != 'delivered' && $request['payment_status'] == 'paid') {
                return response()->json([
                    'errors' => [
                        ['code' => 'order', 'message' => translate('Can not change payment status before order delivered!')]
                    ]
                ], 403);
            }

            $order->payment_status = $request['payment_status'];
            $order->save();
            return response()->json(['message' => translate('Payment status updated')], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('not found!')]
            ]
        ], 404);
    }

    public function address_update(Request $request)
    {
        // [AI] Zero-Trust Vendor Privacy Boundary: Customer delivery addresses are managed exclusively by Victorious Delivery.
        // Merchants are strictly forbidden from modifying customer delivery addresses.
        return response()->json([
            'status' => false,
            'message' => translate('Customer delivery addresses are managed exclusively by Victorious Delivery. Merchants cannot modify customer addresses.')
        ], 403);
    }

    public function updateOrderDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'payment_status' => 'required|in:paid,unpaid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $seller = $request->seller;
        // [AI] Ownership Guard: Only update order details for own orders
        $order = Order::with(['customer', 'seller.shop', 'deliveryMan'])->where(['id' => $request['order_id'], 'seller_id' => $seller['id']])->first();

        if (isset($order)) {
            if ($order['payment_status'] == 'paid' && $request['payment_status'] != 'paid') {
                return response()->json(['success' => 0, 'message' => translate('when_payment_status_paid_then_you_can_not_change_payment_status_paid_to_unpaid.')], 403);
            }

            // [AI] Payment Authority Invariant (P1-A): Vendors CANNOT establish payment for non-COD digital/offline methods
            if ($request->has('payment_status') && $request['payment_status'] === 'paid' && $order['payment_status'] !== 'paid') {
                if ($order['payment_method'] !== 'cash_on_delivery') {
                    return response()->json([
                        'status' => false,
                        'message' => translate('Only_platform_administrators_or_payment_gateways_can_verify_digital_payments._Vendors_cannot_manually_mark_non-COD_orders_as_paid.'),
                    ], 403);
                }
            }


            if ($request['order_status'] == 'delivered') {
                // [AI] Guard: An unpaid non-COD order CANNOT be marked as delivered by a vendor
                if ($order['payment_status'] !== 'paid' && $order['payment_method'] !== 'cash_on_delivery') {
                    return response()->json([
                        'status' => false,
                        'message' => translate('Unpaid_digital_or_offline_orders_cannot_be_marked_as_delivered_until_payment_is_confirmed_by_gateway_or_admin.'),
                    ], 403);
                }

                foreach ($order['details'] as $orderDetail) {
                    $productDetails = json_decode($orderDetail?->product_details ?? '', true) ?? [];
                    if (
                        $productDetails['product_type'] == 'digital' &&
                        (isset($productDetails['digital_product_type']) && $productDetails['digital_product_type'] == 'ready_after_sell') &&
                        is_null($orderDetail['digital_file_after_sell'])
                    ) {
                        return response()->json(['success' => 0, 'message' => translate('Please_upload_the_digital_product_files_first')], 403);
                    }
                }
            }

            if ($request['delivery_type'] == 'third_party_delivery') {
                Order::where('id', $request['order_id'])->update([
                    'delivery_man_id' => null,
                    'deliveryman_charge' => 0,
                    'expected_delivery_date' => null,
                    'delivery_type' => 'third_party_delivery',
                    'delivery_service_name' => $request['delivery_service_name'] ?? '',
                    'third_party_delivery_tracking_id' => $request['third_party_delivery_tracking_id'] ?? '',
                ]);
            } elseif ($request->has('delivery_man_id') && !empty($request['delivery_man_id']) && ($order['delivery_man_id'] != $request['delivery_man_id'])) {
                Order::where('id', $request['order_id'])->update([
                    'delivery_man_id' => $request['delivery_man_id'],
                    'delivery_type' => 'self_delivery',
                    'delivery_service_name' => null,
                    'third_party_delivery_tracking_id' => null,
                ]);
                OrderStatusEvent::dispatch('new_order_assigned_message', 'delivery_man', $order);
            }

            if ($request->has('deliveryman_charge') && !is_null($request['deliveryman_charge']) && ($order['deliveryman_charge'] != $request['deliveryman_charge'])) {
                Order::where(['id' => $request['order_id']])->update([
                    'deliveryman_charge' => $request['deliveryman_charge'],
                ]);
            }

            $orderInfo = Order::with('deliveryMan')->find($request['order_id']);
            if (!empty($request['expected_delivery_date']) && $orderInfo['expected_delivery_date'] != $request['expected_delivery_date']) {
                $orderInfo->expected_delivery_date = $request['expected_delivery_date'];
                try {
                    DB::beginTransaction();
                    $this->add_expected_delivery_date_history($request['order_id'], $seller['id'], $request['expected_delivery_date'], 'seller');
                    $orderInfo->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    DB::rollback();
                }

                OrderStatusEvent::dispatch('expected_delivery_date', 'delivery_man', $order);
            }

            // Order Status
            if ($request->has('order_status') && !empty($request['order_status'])) {
                $order = Order::with(['customer', 'seller.shop', 'deliveryMan'])->find($request['order_id']);

                if ($order['is_guest'] == 0 && empty($order->customer)) {
                    return response()->json([
                        'success' => 0,
                        'message' => translate("Customer_account_has_been_deleted") . ' ' . translate("you_cant_update_status")
                    ], 202);
                }

                $walletStatus = getWebConfig(name: 'wallet_status');
                $loyaltyPointStatus = getWebConfig(name: 'loyalty_point_status');

                // [AI] Strict Completion Authority Invariant:
                // Generic seller REST endpoints do not have delivery completion authority for marketplace orders.
                if (\App\Utils\OrderManager::isVictoriousMarketplaceOrder($order) && $request['order_status'] === 'delivered') {
                    return response()->json([
                        'success' => 0,
                        'message' => translate('Generic seller status endpoints cannot mark marketplace orders delivered. Delivery orders require doorstep customer OTP verification, and pickup orders require in-shop handover OTP verification.'),
                    ], 403);
                }

                if ($order['order_status'] == 'delivered' && !in_array($request['order_status'], ['returned', 'failed', 'canceled'])) {
                    return response()->json(['success' => 0, 'message' => translate('order_is_already_delivered')], 200);
                }

                event(new OrderStatusEvent(key: $request['order_status'], type: 'customer', order: $order));
                if ($request['order_status'] == 'canceled') {
                    event(new OrderStatusEvent(key: 'canceled', type: 'delivery_man', order: $order));
                }

                Order::where('id', $request['order_id'])->update(['order_status' => $request['order_status']]);
                if ($request['order_status'] == 'delivered') {
                    // [AI] Only COD orders transition payment_status to 'paid' upon vendor delivery
                    $newPaymentStatus = ($order['payment_method'] === 'cash_on_delivery') ? 'paid' : $order['payment_status'];
                    Order::where('id', $request['order_id'])->update([
                        'payment_status' => $newPaymentStatus,
                        'is_pause' => 0,
                    ]);
                    OrderDetail::where('order_id', $order->id)->update([
                        'delivery_status' => 'delivered',
                        'payment_status' => $newPaymentStatus,
                    ]);
                }
                OrderManager::getStockUpdateOnOrderStatusChange($order, $request['order_status']);
                if ($request['order_status'] == 'delivered' && $order['seller_id'] != null) {
                    $refreshedOrder = Order::find($request['order_id']);
                    // [AI] Settlement Invariant: Settlement occurs only if order is verified as paid
                    if ($refreshedOrder && $refreshedOrder->payment_status === 'paid') {
                        OrderManager::getWalletManageOnOrderStatusChange($refreshedOrder, 'seller');
                    }
                }

                if ($order['delivery_man_id'] && $request['order_status'] == 'delivered') {
                    $deliverymanWallet = DeliverymanWallet::where('delivery_man_id', $order['delivery_man_id'])->first();

                    if (empty($deliverymanWallet)) {
                        DeliverymanWallet::create([
                            'delivery_man_id' => $order['delivery_man_id'],
                            'current_balance' => $order?->deliveryman_charge ?? 0,
                            'cash_in_hand' => 0,
                            'pending_withdraw' => 0,
                            'total_withdraw' => 0,
                        ]);
                    } else {
                        DeliverymanWallet::where('delivery_man_id', $order['delivery_man_id'])->update([
                            'current_balance' => $deliverymanWallet->current_balance + ($order?->deliveryman_charge ?? 0),
                        ]);
                    }

                    if ($order['deliveryman_charge'] && $request['order_status'] == 'delivered') {
                        DeliveryManTransaction::create([
                            'delivery_man_id' => $order['delivery_man_id'],
                            'user_id' => $seller->id,
                            'user_type' => 'seller',
                            'credit' => $order?->deliveryman_charge ?? 0,
                            'transaction_id' => Uuid::uuid4(),
                            'transaction_type' => 'deliveryman_charge'
                        ]);
                    }
                }

                self::add_order_status_history($order['id'], $seller->id, $request['order_status'], 'seller');
            }

            $order = Order::with(['customer', 'seller.shop', 'deliveryMan'])->find($request['order_id']);
            if ($order['payment_status'] != 'paid' && $request['payment_status'] == 'paid') {
                // [AI] Redundant security check: Double enforce non-COD rejection
                if ($order['payment_method'] !== 'cash_on_delivery') {
                    return response()->json([
                        'status' => false,
                        'message' => translate('Only_platform_administrators_or_payment_gateways_can_verify_digital_payments._Vendors_cannot_manually_mark_non-COD_orders_as_paid.'),
                    ], 403);
                }
                if ($order['is_guest'] == '0' && empty($order?->customer)) {
                    return response()->json([
                        'success' => 0,
                        'message' => translate("customer_account_has_been_deleted.") . ' ' . translate('you_can_not_update_status.'),
                    ], 200);
                }
                Order::where('id', $request['order_id'])->update(['payment_status' => 'paid']);
            }

            return response()->json([
                'success' => 1,
                'message' => translate("Order_updated_successfully")
            ], 200);
        }

        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('not found!')]
            ]
        ], 404);
    }

    private function maskName($name) {
        if (!$name) return '';
        $len = strlen($name);
        if ($len <= 2) return $name;
        return substr($name, 0, 2) . str_repeat('*', min(8, $len - 2));
    }

    private function maskPhone($phone) {
        // [AI] Complete disintermediation: merchants must NOT receive customer phone numbers
        return '';
    }

    private function maskEmail($email) {
        // [AI] Complete disintermediation: merchants must NOT receive customer email addresses
        return '';
    }

    private function maskAddress($address) {
        if (!$address) return '';
        return 'Detailed address hidden for privacy';
    }

    private function maskOrderData($order) {
        if ($order->customer) {
            $order->customer->f_name = $this->maskName($order->customer->f_name);
            $order->customer->l_name = $this->maskName($order->customer->l_name);
            $order->customer->phone = $this->maskPhone($order->customer->phone);
            $order->customer->email = $this->maskEmail($order->customer->email);
            unset($order->customer->street_address, $order->customer->house_no, $order->customer->apartment_no);
            unset($order->customer->cm_firebase_token, $order->customer->wallet_balance, $order->customer->loyalty_point);
            unset($order->customer->payment_card_last_four, $order->customer->payment_card_brand);
        }
        $order->verification_code = '****';
        
        if ($order->relationLoaded('shippingAddress') && $order->shippingAddress) {
            $order->shippingAddress->contact_person_name = $this->maskName($order->shippingAddress->contact_person_name);
            $order->shippingAddress->phone = $this->maskPhone($order->shippingAddress->phone);
            $order->shippingAddress->email = $this->maskEmail($order->shippingAddress->email);
            $order->shippingAddress->address = $this->maskAddress($order->shippingAddress->address);
            $order->shippingAddress->latitude = '-33.8688';
            $order->shippingAddress->longitude = '151.2195';
        }
        if ($order->relationLoaded('billingAddress') && $order->billingAddress) {
            $order->billingAddress->contact_person_name = $this->maskName($order->billingAddress->contact_person_name);
            $order->billingAddress->phone = $this->maskPhone($order->billingAddress->phone);
            $order->billingAddress->email = $this->maskEmail($order->billingAddress->email);
            $order->billingAddress->address = $this->maskAddress($order->billingAddress->address);
            $order->billingAddress->latitude = '-33.8688';
            $order->billingAddress->longitude = '151.2195';
        }
        
        $billingData = $order->billing_address_data;
        if (is_string($billingData)) {
            $billingData = json_decode($billingData);
        }
        if (is_object($billingData)) {
            if (isset($billingData->contact_person_name)) $billingData->contact_person_name = $this->maskName($billingData->contact_person_name);
            if (isset($billingData->phone)) $billingData->phone = $this->maskPhone($billingData->phone);
            if (isset($billingData->email)) $billingData->email = $this->maskEmail($billingData->email);
            if (isset($billingData->address)) $billingData->address = $this->maskAddress($billingData->address);
            if (isset($billingData->latitude)) $billingData->latitude = '-33.8688';
            if (isset($billingData->longitude)) $billingData->longitude = '151.2195';
            $order->billing_address_data = $billingData;
        }

        $shippingData = $order->shipping_address_data;
        if (is_string($shippingData)) {
            $shippingData = json_decode($shippingData);
        }
        if (is_object($shippingData)) {
            if (isset($shippingData->contact_person_name)) $shippingData->contact_person_name = $this->maskName($shippingData->contact_person_name);
            if (isset($shippingData->phone)) $shippingData->phone = $this->maskPhone($shippingData->phone);
            if (isset($shippingData->email)) $shippingData->email = $this->maskEmail($shippingData->email);
            if (isset($shippingData->address)) $shippingData->address = $this->maskAddress($shippingData->address);
            if (isset($shippingData->latitude)) $shippingData->latitude = '-33.8688';
            if (isset($shippingData->longitude)) $shippingData->longitude = '151.2195';
            $order->shipping_address_data = $shippingData;
        }
        return $order;
    }
}
