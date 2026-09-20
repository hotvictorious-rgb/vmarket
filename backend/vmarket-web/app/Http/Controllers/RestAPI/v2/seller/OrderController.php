<?php

namespace App\Http\Controllers\RestAPI\v2\seller;

use App\Events\OrderStatusEvent;
use App\Http\Controllers\Controller;
use App\Models\DeliveryManTransaction;
use App\Models\DeliverymanWallet;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ReferralCustomer;
use App\Traits\CommonTrait;
use App\Utils\BackEndHelper;
use App\Utils\Convert;
use App\Utils\CustomerManager;
use App\Utils\Helpers;
use App\Utils\ImageManager;
use App\Utils\OrderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;


class OrderController extends Controller
{
    use CommonTrait;

    public function list(Request $request):JsonResponse
    {
        $data = Helpers::get_seller_by_token($request);

        if ($data['success'] == 1) {
            $seller = $data['data'];
        } else {
            return response()->json([
                'auth-001' => translate('Your existing session token does not authorize you any more')
            ], 401);
        }

        $order_ids = OrderDetail::where(['seller_id' => $seller['id']])->pluck('order_id')->toArray();
        $orders = Order::with(['customer', 'shipping'])->where(['seller_is' => 'seller'])->whereIn('id', $order_ids)->get();
        $orders->map(function ($data) {
            $data['billing_address_data'] = json_decode($data['billing_address_data']);
            $this->maskOrderData($data);
            return $data;
        });

        return response()->json($orders, 200);
    }

    public function details(Request $request, $id):JsonResponse
    {
        $data = Helpers::get_seller_by_token($request);

        if ($data['success'] == 1) {
            $seller = $data['data'];
        } else {
            return response()->json([
                'auth-001' => translate('Your existing session token does not authorize you any more')
            ], 401);
        }

        $details = OrderDetail::with(['order'])->where(['seller_id' => $seller['id'], 'order_id' => $id])->get();
        foreach ($details as $det) {
            $det['product_details'] = Helpers::product_data_formatting(json_decode($det['product_details'], true));
            if ($det->order) {
                $this->maskOrderData($det->order);
            }
        }

        return response()->json($details, 200);
    }

    public function assign_delivery_man(Request $request):JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'delivery_man_id' => 'required',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)]);
        }

        $data = Helpers::get_seller_by_token($request);

        if ($data['success'] == 1) {
            $seller = $data['data'];
        } else {
            return response()->json([
                'auth-001' => translate('Your existing session token does not authorize you any more')
            ], 401);
        }

        $order = Order::where(['seller_id' => $seller['id'], 'id' => $request['order_id']])->first();

        $order->delivery_man_id = $request['delivery_man_id'];
        $order->delivery_type = 'self_delivery';
        $order->delivery_service_name = null;
        $order->third_party_delivery_tracking_id = null;
        $order->save();

        OrderStatusEvent::dispatch('new_order_assigned_message', 'delivery_man', $order);

        return response()->json(['success' => 1, 'message' => translate('order_deliveryman_assigned_successfully')], 200);
    }

    public function amount_date_update(Request $request):JsonResponse
    {
        $data = Helpers::get_seller_by_token($request);
        if ($data['success'] == 1) {
            $seller = $data['data'];
        } else {
            return response()->json([
                'auth-001' => translate('Your existing session token does not authorize you any more')
            ], 401);
        }

        $deliveryman_charge = $request->deliveryman_charge;

        $order = Order::find($request->order_id);
        $db_expected_date = $order->expected_delivery_date;

        $order->deliveryman_charge = $deliveryman_charge;
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
    public function digital_file_upload_after_sell(Request $request):JsonResponse
    {
        $data = Helpers::get_seller_by_token($request);

        if ($data['success'] == 1) {
            $seller = $data['data'];
        } else {
            return response()->json([
                'auth-001' => translate('Your existing session token does not authorize you any more')
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'digital_file_after_sell' => 'required|mimes:jpg,jpeg,png,gif,zip,pdf',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)]);
        }

        $order_details = OrderDetail::find($request->order_id);
        if ($order_details) {
            $order_details->digital_file_after_sell = ImageManager::update('product/digital-product/', $order_details->digital_file_after_sell, $request->digital_file_after_sell->getClientOriginalExtension(), $request->file('digital_file_after_sell'), 'file');
            $order_details->save();
            return response()->json(['success' => 1, 'message' => translate('File_upload_successfully')], 200);
        } else {
            return response()->json(['success' => 0, 'message' => translate("File_upload_fail!")], 202);
        }
    }

    public function order_detail_status(Request $request):JsonResponse
    {
        $data = Helpers::get_seller_by_token($request);

        if ($data['success'] == 1) {
            $seller = $data['data'];
        } else {
            return response()->json([
                'auth-001' => translate('Your existing session token does not authorize you any more')
            ], 401);
        }

        $order = Order::find($request->id);
        if (empty($order->customer)) {
            return response()->json(['success' => 0, 'message' => translate("Customer account has been deleted. you can't update status!")], 202);
        }

        $wallet_status = getWebConfig(name: 'wallet_status');
        $loyalty_point_status = getWebConfig(name: 'loyalty_point_status');

        // [AI] Strict Completion Authority Invariant:
        // Generic seller REST endpoints do not have delivery completion authority for marketplace orders.
        // Delivery orders require verified doorstep customer OTP, and pickup orders require verified in-shop handover OTP.
        if (\App\Utils\OrderManager::isVictoriousMarketplaceOrder($order) && $request->order_status === 'delivered') {
            return response()->json([
                'success' => 0,
                'message' => translate('Generic seller status endpoints cannot mark marketplace orders delivered. Delivery orders require doorstep customer OTP verification, and pickup orders require in-shop handover OTP verification.'),
            ], 403);
        }

        if ($order->order_status == 'delivered') {
            return response()->json(['success' => 0, 'message' => translate('order is already delivered')], 200);
        }

        OrderStatusEvent::dispatch($request['order_status'], 'customer', $order);
        if ($request->order_status == 'canceled') {
            OrderStatusEvent::dispatch('canceled', 'delivery_man', $order);
        }

        $order->order_status = $request->order_status;
        OrderManager::getStockUpdateOnOrderStatusChange($order, $request->order_status);

        if ($request->order_status == 'delivered' && $order['seller_id'] != null) {
            OrderManager::getWalletManageOnOrderStatusChange($order, 'seller');
            OrderDetail::where('order_id', $order->id)->update(
                ['delivery_status' => 'delivered']
            );
        }

        $order->save();

        if ($order->delivery_man_id && $request->order_status == 'delivered') {
            $dm_wallet = DeliverymanWallet::where('delivery_man_id', $order->delivery_man_id)->first();

            if (empty($dm_wallet)) {
                DeliverymanWallet::create([
                    'delivery_man_id' => $order->delivery_man_id,
                    'current_balance' => BackEndHelper::currency_to_usd($order->deliveryman_charge) ?? 0,
                    'cash_in_hand' => 0,
                    'pending_withdraw' => 0,
                    'total_withdraw' => 0,
                ]);
            } else {
                $dm_wallet->current_balance += BackEndHelper::currency_to_usd($order->deliveryman_charge) ?? 0;
                $dm_wallet->save();
            }

            if ($order->deliveryman_charge && $request->order_status == 'delivered') {
                DeliveryManTransaction::create([
                    'delivery_man_id' => $order->delivery_man_id,
                    'user_id' => $seller->id,
                    'user_type' => 'seller',
                    'credit' => BackEndHelper::currency_to_usd($order->deliveryman_charge) ?? 0,
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

    public function assign_third_party_delivery(Request $request):JsonResponse
    {

        $data = Helpers::get_seller_by_token($request);

        if ($data['success'] == 1) {
            $seller = $data['data'];
        } else {
            return response()->json([
                'auth-001' => translate('Your existing session token does not authorize you any more')
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'delivery_service_name' => 'required',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)]);
        }

        $order = Order::find($request->order_id);
        $order->delivery_type = 'third_party_delivery';
        $order->delivery_service_name = $request->delivery_service_name;
        $order->third_party_delivery_tracking_id = $request->third_party_delivery_tracking_id;
        $order->delivery_man_id = null;
        $order->deliveryman_charge = 0;
        $order->expected_delivery_date = null;
        $order->save();

        return response()->json(['success' => 1, 'message' => translate('third_party_delivery_assigned_successfully')], 200);
    }

    public function update_payment_status(Request $request):JsonResponse
    {
        $data = Helpers::get_seller_by_token($request);

        if ($data['success'] == 1) {
            $seller = $data['data'];
        } else {
            return response()->json([
                'auth-001' => translate('Your existing session token does not authorize you any more')
            ], 401);
        }

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

        // [AI] Ownership Guard: Only update payment status for own orders
        $order = Order::where(['id' => $request['order_id'], 'seller_id' => $seller['id']])->first();
        if (isset($order)) {
            if ($order->is_guest == '0' && empty($order->customer)) {
                return response()->json(['success' => 0, 'message' => translate("Customer account has been deleted. you can't update status!")], 202);
            }

            // [AI] Payment Authority Invariant: Victorious MARKET backend / payment gateway / admin is the sole payment authority.
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
