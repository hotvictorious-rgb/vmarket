<?php

namespace App\Http\Controllers\RestAPI\v2\delivery_man;

use App\Events\OrderStatusEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\v2\DeliveryMan\DeliveryManOrderDeliveryVerificationRequest;
use App\Http\Requests\API\v2\DeliveryMan\DeliveryManUpdateInfoRequest;
use App\Models\BusinessSetting;
use App\Models\DeliveryHistory;
use App\Models\DeliveryMan;
use App\Models\DeliverymanNotification;
use App\Models\DeliveryManTransaction;
use App\Models\DeliverymanWallet;
use App\Models\EmergencyContact;
use App\Models\Order;
use App\Models\OrderDeliveryVerification;
use App\Models\OrderDetail;
use App\Models\OrderEditHistory;
use App\Models\Review;
use App\Traits\CommonTrait;
use App\Models\User;
use App\Utils\CustomerManager;
use App\Utils\Helpers;
use App\Utils\ImageManager;
use App\Utils\OrderManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class DeliveryManController extends Controller
{
    use CommonTrait;

    public function __construct(
        private Order $order,
    )
    {

    }

    public function info(Request $request):JsonResponse
    {
        $d_man = $request['delivery_man'];
        $delivery_man = DeliveryMan::where(['id' => $d_man['id']])->with(['review'])->first();
        $wallet = DeliverymanWallet::where('delivery_man_id', $request['delivery_man']['id'])->first();
        $withdrawable_balance = CommonTrait::delivery_man_withdrawable_balance($request['delivery_man']['id']);
        $total_earn = CommonTrait::delivery_man_total_earn($request['delivery_man']['id']);
        $order = Order::where('delivery_man_id', $request['delivery_man']['id'])->get();
        $completed_delivery = $order->where('order_status', 'delivered')->count();
        $pause_delivery = $order->where('is_pause', 1)->count();
        $pending_delivery = $order->where('order_status', 'pending')->count();
        $total_deposit = DeliveryManTransaction::where(['delivery_man_id' => $request['delivery_man']['id'], 'transaction_type' => 'cash_in_hand'])->sum('credit');

        $request['delivery_man']['withdrawable_balance'] = $withdrawable_balance;
        $request['delivery_man']['current_balance'] = $wallet->current_balance ?? 0;
        $request['delivery_man']['cash_in_hand'] = $wallet->cash_in_hand ?? 0;
        $request['delivery_man']['pending_withdraw'] = $wallet->pending_withdraw ?? 0;
        $request['delivery_man']['total_withdraw'] = $wallet->total_withdraw ?? 0;
        $request['delivery_man']['total_earn'] = $total_earn;
        $request['delivery_man']['completed_delivery'] = $completed_delivery;
        $request['delivery_man']['pending_delivery'] = $pending_delivery;
        $request['delivery_man']['total_delivery'] = $order->count();
        $request['delivery_man']['pause_delivery'] = $pause_delivery;
        $request['delivery_man']['total_deposit'] = $total_deposit;
        $request['delivery_man']['average_rating'] = count($delivery_man->rating) > 0 ? number_format($delivery_man->rating[0]->average, 2, '.', ' ') : 0;

        return response()->json($request['delivery_man'], 200);
    }

    public function get_current_orders(Request $request)
    {
        $deliveryMan = $request['delivery_man'];
        $orders = Order::with(['shippingAddress', 'customer', 'seller.shop', 'originHub.city.state', 'destinationHub.city.state'])
            ->whereIn('order_status', ['pending', 'processing', 'out_for_delivery', 'confirmed'])
            ->where(['delivery_man_id' => $deliveryMan['id']])
            ->orderBy('expected_delivery_date', 'asc')
            ->get();
        return response()->json($orders, 200);
    }

    public function record_location_data(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'longitude' => 'required',
            'latitude' => 'required',
            'location' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $deliveryMan = $request['delivery_man'];
        DB::table('delivery_histories')->insert([
            'order_id' => $request['order_id'],
            'deliveryman_id' => $deliveryMan['id'],
            'longitude' => $request['longitude'],
            'latitude' => $request['latitude'],
            'time' => now(),
            'location' => $request['location'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
        return response()->json(['message' => 'location recorded'], 200);
    }

    public function get_order_history(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }
        $deliveryMan = $request['delivery_man'];
        $history = DeliveryHistory::where(['order_id' => $request['order_id'], 'deliveryman_id' => $deliveryMan['id']])->get();
        return response()->json($history, 200);
    }

    public function update_order_status(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'status' => 'required|in:delivered,canceled,returned,out_for_delivery'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $deliveryMan = $request['delivery_man'];
        $cause = null;
        if ($request['status'] == 'canceled') {
            $cause = $request['cause'];
        }

        $order = Order::with(['customer', 'deliveryMan', 'latestEditHistory'])->where(['delivery_man_id' => $deliveryMan['id'], 'id' => $request['order_id']])->first();

        if (!$order) {
            return response()->json(['success' => 0, 'message' => translate('order_not_found')], 404);
        }

        if ($order->order_status == 'delivered') {
            return response()->json(['success' => 0, 'message' => 'order is already delivered.'], 200);
        }

        if ($request['status'] == 'out_for_delivery') {
            if (!isset($request['pickup_verification_code']) || $order->pickup_verification_code != $request['pickup_verification_code']) {
                return response()->json(['success' => 0, 'message' => translate('invalid_pickup_otp')], 403);
            }
        }

        if ($request['status'] == 'delivered') {
            $order_verification = getWebConfig(name: 'order_verification');
            if ($order_verification == 1) {
                if (isset($request['verification_code']) && $order->verification_code == $request['verification_code']) {
                    $order->verification_status = 1;
                    $order->save();
                } elseif ($order->verification_status != 1) {
                    return response()->json(['success' => 0, 'message' => translate('order_is_not_verified_by_customer_delivery_otp')], 403);
                }
            }
        }

        DB::beginTransaction();
        try {
            $affected = Order::where(['id' => $request['order_id'], 'delivery_man_id' => $deliveryMan['id']])
                ->where('order_status', '!=', 'delivered')
                ->update([
                    'order_status' => $request['status'],
                    'cause' => $cause
                ]);

            if ($affected == 0 && $request['status'] == 'delivered') {
                DB::rollBack();
                return response()->json(['success' => 0, 'message' => 'order is already delivered.'], 200);
            }

            if (isset($deliveryMan['id']) && $request['status'] == 'delivered') {
                Order::where(['id' => $request['order_id']])->update([
                    'order_amount' => $order['order_amount'] + $order['edit_due_amount'],
                    'payment_status' => 'paid',
                    'edit_due_amount' => 0,
                ]);
                if ($order?->latestEditHistory) {
                    OrderEditHistory::where(['id' => $order?->latestEditHistory?->id])->update([
                        'order_due_payment_status' => 'paid',
                        'order_due_payment_note' => 'Marked as paid by Delivery Man',
                    ]);
                }

                $order = Order::with(['customer', 'deliveryMan', 'latestEditHistory'])
                    ->where(['delivery_man_id' => $deliveryMan['id'], 'id' => $request['order_id']])->first();
                $deliveryManWallet = DeliverymanWallet::where('delivery_man_id', $deliveryMan['id'])->first();

                $cashInHand = 0;
                if ($order->payment_method == 'cash_on_delivery') {
                    $cashInHand = $order->order_amount;
                } else {
                    if (
                        $order?->latestEditHistory &&
                        $order?->latestEditHistory?->order_due_payment_status == 'paid' &&
                        $order?->latestEditHistory?->order_due_payment_method == 'cash_on_delivery'
                    ) {
                        $cashInHand += $order?->latestEditHistory?->order_due_amount ?? 0;
                    }
                }

                if (empty($deliveryManWallet)) {
                    DeliverymanWallet::create([
                        'delivery_man_id' => $deliveryMan['id'],
                        'current_balance' => $order?->deliveryman_charge ?? 0,
                        'cash_in_hand' => $cashInHand,
                        'pending_withdraw' => 0,
                        'total_withdraw' => 0,
                    ]);
                } else {
                    $deliveryManWallet->cash_in_hand += $cashInHand;
                    $deliveryManWallet->current_balance += $order->deliveryman_charge ?? 0;
                    $deliveryManWallet->save();
                }
            }

            if ($request['status'] == 'delivered' && $order['seller_id'] != null) {
                OrderManager::getWalletManageOnOrderStatusChange($order, 'delivery man');
                OrderDetail::where('order_id', $order->id)->update(
                    ['delivery_status' => 'delivered']
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update order status', 'error' => $e->getMessage()], 500);
        }

        if ($request['status'] == 'out_for_delivery') {
            event(new OrderStatusEvent(key: 'out_for_delivery', type: 'customer', order: $order));
        } elseif ($request['status'] == 'delivered') {
            event(new OrderStatusEvent(key: 'delivered', type: 'customer', order: $order));
        } elseif ($request['status'] == 'canceled') {
            event(new OrderStatusEvent(key: 'canceled', type: 'delivery_man', order: $order));
        }

        OrderManager::getStockUpdateOnOrderStatusChange($order, $request['status']);
        OrderManager::generateReferBonusForFirstOrder(orderId: $order['id']);

        $refEarningStatus = BusinessSetting::where('type', 'ref_earning_status')->first()->value ?? 0;
        $refEarningExchangeRate = BusinessSetting::where('type', 'ref_earning_exchange_rate')->first()->value ?? 0;

        $walletStatus = getWebConfig(name: 'wallet_status');
        if (!$order->is_guest && $walletStatus == 1 && $refEarningStatus == 1 && $request['status'] == 'delivered' && $order->payment_status == 'paid') {

            $customer = User::find($order->customer_id);
            $isFirstOrder = Order::where(['customer_id' => $order->customer_id, 'order_status' => 'delivered', 'payment_status' => 'paid'])->count();
            $referredByUser = User::find($customer->referred_by);

            if ($isFirstOrder == 1 && isset($customer->referred_by) && isset($referredByUser)) {
                CustomerManager::create_wallet_transaction($referredByUser->id, floatval($refEarningExchangeRate), 'add_fund_by_admin', 'earned_by_referral');
            }
        }
        self::add_order_status_history($order->id, $deliveryMan['id'], $request['status'], 'delivery_man', $request['cause']);
        return response()->json(['message' => 'Order status updated successfully!'], 200);
    }

    public function update_expected_delivery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'expected_delivery_date' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $d_man = $request['delivery_man'];
        $order = Order::with(['deliveryMan'])->where(['delivery_man_id' => $d_man['id'], 'id' => $request['order_id']])->first();

        if ($order->order_status == 'delivered') {
            return response()->json(['success' => 0, 'message' => 'order is already delivered.'], 200);
        }

        $order->expected_delivery_date = $request['expected_delivery_date'];
        $order->cause = $request['cause'];
        $order->save();
        CommonTrait::add_expected_delivery_date_history($order->id, $d_man['id'], $request['expected_delivery_date'], 'delivery_man', $request['cause']);

        OrderStatusEvent::dispatch('order_rescheduled_message', 'delivery_man', $order);

        return response()->json(['message' => 'Order status updated successfully!'], 200);
    }

    public function order_update_is_pause(Request $request) :JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'is_pause' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $deliveryMan = $request['delivery_man'];
        $order = Order::where(['delivery_man_id' => $deliveryMan['id'], 'id' => $request['order_id']])->first();

        if ($order->order_status == 'delivered') {
            return response()->json(['success' => 0, 'message' => 'order is already delivered.'], 200);
        }

        $order->is_pause = $request['is_pause'];
        $order->cause = $request['cause'];
        $order->save();

        return response()->json(['message' => 'Order status updated successfully!'], 200);
    }

    public function get_order_details(Request $request): JsonResponse
    {
        $deliveryMan = $request['delivery_man'];
        $order = Order::with(['details' => function ($query) {
            return $query->with(['order' => function ($query) {
                return $query->with(['shippingAddress', 'customer', 'seller.shop']);
            }, 'latestEditHistory']);
        }, 'shippingAddress', 'customer', 'seller.shop', 'latestEditHistory'])
            ->where(['delivery_man_id' => $deliveryMan['id'], 'id' => $request['order_id']])->first();

        $details = $order?->details?->map(function ($detail) use ($order) {
            $data = $detail->toArray();
            $data['is_pause'] = $order['is_pause'];
            $data['variation'] = is_array($data['variation']) ? $data['variation'] : json_decode($data['variation'], true);

            // [AI] Sanitize price, discount, and tax at the backend API response level for delivery rider privacy
            $data['price'] = 0;
            $data['discount'] = 0;
            $data['tax'] = 0;

            $product = is_array($data['product_details']) ? $data['product_details'] : (json_decode($data['product_details'], true) ?? []);
            $product['thumbnail_full_url'] = $detail?->productAllStatus?->thumbnail_full_url;
            $product['unit_price'] = 0;
            $product['purchase_price'] = 0;
            $data['product_details'] = Helpers::product_data_formatting_for_json_data($product);

            return $data;
        });

        return response()->json($details, 200);
    }

    public function get_all_orders(Request $request): JsonResponse
    {
        $deliveryMan = $request['delivery_man'];

        $orders = Order::with(['shippingAddress', 'customer', 'seller.shop'])
            ->where(['delivery_man_id' => $deliveryMan->id])
            ->when(!empty($request->search), function ($query) use ($request, $deliveryMan) {
                return $query->where('id', 'like', "%{$request['search']}%")
                    ->orWhere(function ($query) use ($request, $deliveryMan) {
                        return $query->whereHas('customer', function ($query) use ($request) {
                            return $query->where('phone', 'like', "%{$request['search']}%");
                        })->whereNotNull('delivery_man_id')->where(['delivery_man_id' => $deliveryMan->id]);
                    });
            })
            ->when(isset($request['status']) && !empty($request['status']), function ($query) use ($request) {
                return $query->where('order_status', $request['status']);
            })
            ->when(isset($request['is_pause']) && in_array($request['is_pause'], [0, 1]), function ($query) use ($request) {
                return $query->where('is_pause', $request['is_pause']);
            })
            ->when(isset($request['date_type']) && $request['date_type'] == "today", function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when(isset($request['date_type']) && $request['date_type'] == "this_week", function ($query) {
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            })
            ->when(isset($request['date_type']) && $request['date_type'] == "this_month", function ($query) {
                $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
            })
            ->when(isset($request['date_type']) && $request['date_type'] == "this_year", function ($query) {
                $query->whereYear('created_at', Carbon::now()->year);
            })
            ->when(isset($request['date_type']) && $request['date_type'] == "custom_date" && !empty($request->start_date) && !empty($request->end_date), function ($query) use ($request) {
                $start_date = Carbon::parse($request['start_date'])->format('Y-m-d 00:00:00');
                $end_data = Carbon::parse($request['end_date'])->format('Y-m-d 23:59:59');

                return $query->whereBetween('created_at', [$start_date, $end_data]);
            })
            ->latest()->get();

        return response()->json($orders, 200);
    }

    public function getOrderItem(Request $request): JsonResponse
    {
        $order = Order::with(['shippingAddress', 'customer', 'seller.shop'])->where(['id' => $request['id']])->first();
        return response()->json($order, 200);
    }

    public function get_last_location(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $last_data = DeliveryHistory::where(['order_id' => $request['order_id']])->latest()->first();
        return response()->json($last_data, 200);
    }

    public function order_payment_status_update(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'payment_status' => 'required|in:paid'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $deliveryMan = $request['delivery_man'];
        $order = Order::where(['delivery_man_id' => $deliveryMan['id'], 'id' => $request['order_id']])->first();
        if (!empty($order)) {
            $order->payment_status = $request['payment_status'];
            $order->save();

            $order->update([
                'order_amount' => $order['order_amount'] + $order['edit_due_amount'],
                'payment_status' => 'paid',
                'edit_due_amount' => 0,
            ]);

            if ($order?->latestEditHistory) {
                OrderEditHistory::where(['id' => $order?->latestEditHistory?->id])->update([
                    'order_due_payment_status' => 'paid',
                    'order_due_payment_method' => $order?->latestEditHistory?->order_due_payment_method ?? 'cash_on_delivery',
                    'order_due_transaction_ref' => '',
                    'order_due_payment_note' => 'Marked as paid by deliveryman',
                ]);
            }
            return response()->json(['message' => translate('Payment status updated')], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('not found!')]
            ]
        ], 404);
    }

    public function update_fcm_token(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $deliveryMan = $request['delivery_man'];
        DeliveryMan::where(['id' => $deliveryMan['id']])->update([
            'fcm_token' => $request['fcm_token']
        ]);

        return response()->json(['message' => 'successfully updated!'], 200);
    }

    public function delivery_wise_earned(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'offset' => 'required',
            'limit' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }
        $dateType = $request['type'] ?? "all";
        $delivery_man = $request->delivery_man;

        $order = Order::with(['seller.shop', 'customer'])->where(['delivery_man_id' => $delivery_man->id, 'payment_status' => 'paid']);

        if (isset($request->start_date) && isset($request->end_date)) {
            $start_date = Carbon::parse($request['start_date'])->format('Y-m-d 00:00:00');
            $end_data = Carbon::parse($request['end_date'])->format('Y-m-d 23:59:59');

            $order->whereBetween('updated_at', [$start_date, $end_data]);
        } elseif ($dateType == 'TodayEarn') {
            $start_time = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
            $end_time = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

            $order->whereBetween('created_at', [$start_time, $end_time]);
        } elseif ($dateType == 'ThisWeekEarn') {
            $start_date = Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
            $end_data = Carbon::now()->endOfWeek()->format('Y-m-d H:i:s');

            $order->whereBetween('created_at', [$start_date, $end_data]);
        } elseif ($dateType == 'ThisMonthEarn') {
            $start_date = date('Y-m-01 00:00:00');
            $end_data = date('Y-m-t 23:59:59');

            $order->whereBetween('created_at', [$start_date, $end_data]);
        }

        $orders = $order->latest()->paginate($request['limit'], ['*'], 'page', $request['offset']);

        $data['total_size'] = $orders->total();
        $data['limit'] = $request['limit'];
        $data['offset'] = $request['offset'];
        $data['orders'] = $orders->items();
        return response()->json($data, 200);

    }

    public function search(Request $request):JsonResponse
    {

        $delivery_man = $request['delivery_man'];
        $order = Order::where('id', 'like', '%' . $request->input('search') . '%')
            ->where('delivery_man_id', $delivery_man->id)->get();

        if (empty(json_decode($order))) {
            $terms = explode(" ", $request->input('search'));

            $users = User::where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->orWhere('f_name', 'like', '%' . $term . '%')
                        ->orWhere('l_name', 'like', '%' . $term . '%');
                }
            })->pluck('id');

            $order = Order::whereIn('customer_id', $users)->where('delivery_man_id', $delivery_man->id)->get();

            if (!empty(json_decode($order))) {
                return response()->json($order, 200);
            }
            return response()->json('No Result Found', 400);
        }

        return response()->json($order, 200);

    }

    public function profile_dashboard_counts(Request $request):JsonResponse
    {
        $delivery_man = $request['delivery_man'];
        $orders = Order::where('delivery_man_id', $delivery_man->id);
        $data = DeliverymanWallet::where('delivery_man_id', $delivery_man->id)->first();

        $data['total_delivery_count'] = $orders->count();
        $data['delivered_orders'] = $orders->where('order_status', 'delivered')->count();
        return response()->json($data);
    }

    public function change_status(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }
        $delivery_man = $request['delivery_man'];
        $delivery_man = DeliveryMan::find($delivery_man->id);
        $delivery_man->is_active = $request->status;

        if ($delivery_man->save()) {
            return response()->json('Status changed successfully', 200);
        } else {
            return response()->json('Status change failed!', 403);
        }
    }

    public function update_info(DeliveryManUpdateInfoRequest $request):JsonResponse
    {
        $delivery_man = DeliveryMan::find($request['delivery_man']->id);
        $image = $request->file('image');
        if ($image != null) {
            $imageName = ImageManager::update('delivery-man/', $delivery_man->image, 'webp', $request->file('image'));
        } else {
            $imageName = $delivery_man->image;
        }

        $delivery_man->f_name = $request['f_name'];
        $delivery_man->l_name = $request['l_name'];
        $delivery_man->address = $request['address'];
        $delivery_man->image = $imageName;
        if (!empty($request->password)) {
            $delivery_man->password = bcrypt(str_replace(' ', '', $request['password']));
        }

        if ($delivery_man->save()) {
            return response()->json(['message' => translate('Profile updated successfully')], 200);
        } else {
            return response()->json(['message' => translate('Profile update failed!'), 403]);
        }
    }

    public function bank_info(Request $request):JsonResponse
    {
        $delivery_man = $request['delivery_man'];

        if (!empty($delivery_man->bank_name) || !empty($delivery_man->account_no)) {
            return response()->json(['message' => translate('Bank Info is locked upon creation. Please contact Admin to edit.')], 403);
        }

        $delivery_man->bank_name = $request->bank_name;
        $delivery_man->branch = $request->branch;
        $delivery_man->account_no = $request->account_no;
        $delivery_man->holder_name = $request->holder_name;

        if ($delivery_man->save()) {
            return response()->json(['message' => translate('Bank Info updated successfully')], 200);
        } else {
            return response()->json(['message' => translate('Bank Info update failed!'), 403]);
        }
    }

    public function collected_cash_history(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'offset' => 'required',
            'limit' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $dateType = $request->type;
        $delivery_man_id = $request['delivery_man']->id;

        $collect_cash_history = DeliveryManTransaction::where(['delivery_man_id' => $delivery_man_id, 'transaction_type' => 'cash_in_hand']);

        if (isset($request->start_date) && isset($request->end_date)) {
            $start_date = Carbon::parse($request['start_date'])->format('Y-m-d 00:00:00');
            $end_data = Carbon::parse($request['end_date'])->format('Y-m-d 23:59:59');

            $collect_cash_history->whereBetween('created_at', [$start_date, $end_data]);
        } elseif ($dateType == 'TodayPaid') {
            $start_time = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
            $end_time = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');

            $collect_cash_history->whereBetween('created_at', [$start_time, $end_time]);
        } elseif ($dateType == 'ThisWeekPaid') {
            $start_date = Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
            $end_data = Carbon::now()->endOfWeek()->format('Y-m-d H:i:s');

            $collect_cash_history->whereBetween('created_at', [$start_date, $end_data]);
        } elseif ($dateType == 'ThisMonthPaid') {
            $start_date = date('Y-m-01 00:00:00');
            $end_data = date('Y-m-t 23:59:59');

            $collect_cash_history->whereBetween('created_at', [$start_date, $end_data]);
        }
        $collect_cash_history = $collect_cash_history->latest()->paginate($request['limit'], ['*'], 'page', $request['offset']);

        $data = array();
        $data['total_size'] = $collect_cash_history->total();
        $data['limit'] = $request['limit'];
        $data['offset'] = $request['offset'];
        $data['deposit'] = $collect_cash_history->items();

        return response()->json($data, 200);
    }

    public function emergency_contact_list(Request $request)
    {

        $list = EmergencyContact::where(['user_id' => $request['delivery_man']->seller_id, 'status' => 1])->get();
        $data = array();
        $data['contact_list'] = $list;

        return response()->json($data, 200);
    }

    public function review_list(Request $request):JsonResponse
    {
        $dm = $request['delivery_man'];

        $reviews = Review::with(['customer', 'order', 'reply'])
            ->when($request->is_saved, function ($query) use ($request) {
                $query->where('is_saved', 1);
            })
            ->where('delivery_man_id', $dm->id)
            ->latest('updated_at')
            ->paginate($request['limit'], ['*'], 'page', $request['offset']);

        $data = array();
        $data['total_size'] = $reviews->total();
        $data['limit'] = $request['limit'];
        $data['offset'] = $request['offset'];
        $data['review'] = $reviews->items();

        return response()->json($data, 200);
    }

    public function is_online(Request $request):JsonResponse
    {
        $dm = $request['delivery_man'];
        $delivery_man = '';
        if ($request->is_online == '0') {
            $delivery_man = DeliveryMan::whereHas('orders', function ($query) {
                $query->where(['order_status' => 'out_for_delivery', 'is_pause' => 0]);
            })->find($request['delivery_man']->id);
        }

        if ($request->is_online == '0' && $delivery_man) {
            return response()->json(["message" => translate("You have ongoing order. You can't go offline now!")], 403);
        } else {
            $dm->is_online = $request->is_online;
            $dm->save();
            return response()->json(["message" => translate("update successfully!")], 200);
        }
    }

    public function get_all_notification(Request $request):JsonResponse
    {
        $notifications = DeliverymanNotification::with('order')
            ->where(['delivery_man_id' => $request['delivery_man']->id])
            ->orderBy('id', 'desc')
            ->paginate($request['limit'], ['*'], 'page', $request['offset']);

        $data = array();
        $data['total_size'] = $notifications->total();
        $data['limit'] = $request['limit'];
        $data['offset'] = $request['offset'];
        $data['notifications'] = $notifications->items();

        return response()->json($data, 200);
    }

    public function distance_api(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'origin_lat' => 'required',
            'origin_lng' => 'required',
            'destination_lat' => 'required',
            'destination_lng' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $api_key = getWebConfig(name: 'map_api_key_server');

        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . $request['origin_lat'] . ',' . $request['origin_lng'] . '&destinations=' . $request['destination_lat'] . ',' . $request['destination_lng'] . '&key=' . $api_key);

        return response()->json($response->json(), 200);
    }

    public function is_saved(Request $request):JsonResponse
    {
        $dm = $request['delivery_man'];
        $get_review = Review::where(['id' => $request->review_id, 'delivery_man_id' => $dm->id])->first();

        if (!$get_review) {
            return response()->json([
                'errors' => [[
                    'code' => 'review',
                    'message' => translate('not_found!')]
                ]], 404);
        }
        $get_review->is_saved = $request->is_saved;

        if ($get_review->save()) {
            return response()->json(['message' => translate('update_successfully!')], 200);

        }
        return response()->json([
            'errors' => [[
                'code' => 'update',
                'message' => translate('failed!')]
            ]], 403);

    }

    /** Dellivery man order verification */
    public function verify_order_delivery_otp(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'verification_code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $deliveryMan = $request['delivery_man'];
        $order = $this->order->where(['id' => $request['order_id'], 'delivery_man_id' => $deliveryMan['id']])->first();

        if (!$order) {
            return response()->json(['message' => translate('order_not_found_or_not_assigned_to_you')], 404);
        }

        if ($order->verification_code == $request['verification_code']) {
            $order->verification_status = 1;
            $order->save();
            return response()->json(['message' => translate('otp_verified_successfully')], 200);
        } else {
            return response()->json(["message" => translate("invalid_otp")], 403);
        }
    }

    /**Order Delivery verification */
    public function order_delivery_verification(DeliveryManOrderDeliveryVerificationRequest $request):JsonResponse
    {
        if($request->hasFile('image')){
            foreach ($request->file('image') as $key => $img) {
                $data = [
                    'order_id' => $request->order_id,
                    'image' => ImageManager::upload('delivery-man/verification-image/', 'webp', $img),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                OrderDeliveryVerification::create($data);
            }
        }


        return response()->json(['message' => 'successfully_uploaded'], 200);
    }

    /**
     * Handover parcel to Interstate Bus Driver at Motor Park & generate Transit Code
     */
    public function interstate_driver_handover(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'driver_phone' => 'required|string|max:50',
            'driver_vehicle_no' => 'required|string|max:50',
            'waybill_slip_no' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $deliveryMan = $request['delivery_man'];
        $order = Order::with('customer')->where(['id' => $request['order_id'], 'delivery_man_id' => $deliveryMan['id']])->first();

        if (!$order) {
            return response()->json(['message' => translate('Order not found or not assigned to you')], 404);
        }

        $transitCode = 'TR-' . rand(1000, 9999);
        $order->driver_phone = $request->driver_phone;
        $order->driver_vehicle_no = $request->driver_vehicle_no;
        $order->waybill_slip_no = $request->waybill_slip_no;
        $order->driver_transit_code = $transitCode;
        $order->order_status = 'out_for_delivery';
        $order->save();

        // Send push notification & SMS to Customer
        try {
            $fcm_token = $order->customer->cm_firebase_token ?? null;
            if ($fcm_token) {
                $data = [
                    'title' => translate('Order Dispatched via Interstate Park Waybill'),
                    'description' => translate("Your order #{$order->id} is on the way. Driver: {$request->driver_phone}, Bus: {$request->driver_vehicle_no}"),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order'
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
            }
        } catch (\Exception $e) {
            // Fail-safe notification
        }

        return response()->json([
            'status' => 'success',
            'message' => translate('Interstate park handover recorded successfully'),
            'driver_transit_code' => $transitCode,
            'driver_phone' => $request->driver_phone,
            'driver_vehicle_no' => $request->driver_vehicle_no,
        ], 200);
    }

    /**
     * Generate / Print Waybill Label for assigned order
     */
    public function get_waybill_label(Request $request): \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
    {
        $deliveryMan = $request['delivery_man'];
        $orderId = $request->get('order_id');

        $order = Order::with(['customer', 'originHub.city.state', 'destinationHub.city.state', 'seller.shop.deliveryHub'])
            ->where('id', $orderId)
            ->where(function ($query) use ($deliveryMan) {
                $query->where('delivery_man_id', $deliveryMan['id'])
                      ->orWhereNull('delivery_man_id');
            })
            ->first();

        if (!$order) {
            return response()->json(['message' => translate('Order not found or not assigned to you')], 404);
        }

        $companyName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
        return view('admin-views.delivery.waybill-label', compact('order', 'companyName'));
    }

    /** Resend OTP Verification */
    public function resend_verification_code(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $deliveryMan = $request['delivery_man'];
        $order = $this->order::with('customer')->where(['id' => $request['order_id'], 'delivery_man_id' => $deliveryMan['id']])->first();

        if (!$order) {
            return response()->json(['message' => translate('order_not_found_or_not_assigned_to_you')], 404);
        }

        $fcm_token = $order->customer->cm_firebase_token ?? null;
        $verification_code = rand(100000, 999999);
        $order->verification_code = $verification_code;
        if ($order->save()) {
            if (!$order->is_guest && $fcm_token) {
                $data = [
                    'title' => translate('order_verification_code'),
                    'description' => translate('order_verification_code') . ' ' . $verification_code,
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order'
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
            }
            return response()->json(['message' => translate('successfully_sent_verification_code')], 200);
        } else {
            return response()->json(["message" => translate("verification_code_send_failed")], 403);
        }
    }

    public function language_change(Request $request):JsonResponse
    {
        $delivery_man = $request->delivery_man;
        $delivery_man->app_language = $request->current_language;
        $delivery_man->save();

        return response()->json(['message' => 'Successfully change'], 200);
    }

    private function _set_paystack_config()
    {
        $config = \Illuminate\Support\Facades\DB::table('addon_settings')->where('key_name', 'paystack')
            ->where('settings_type', 'payment_config')->first();
        $values = false;
        if (!is_null($config) && $config->mode == 'live') {
            $values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $values = json_decode($config->test_values);
        }

        if ($values) {
            $configArray = array(
                'publicKey' => env('PAYSTACK_PUBLIC_KEY', $values->public_key),
                'secretKey' => env('PAYSTACK_SECRET_KEY', $values->secret_key),
                'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
                'merchantEmail' => env('MERCHANT_EMAIL', $values->merchant_email),
            );
            \Illuminate\Support\Facades\Config::set('paystack', $configArray);
        }
    }

    public function generate_paystack_link(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $deliveryMan = $request['delivery_man'];
        $order = Order::with(['customer'])->where(['delivery_man_id' => $deliveryMan['id'], 'id' => $request['order_id']])->first();

        if (!$order) {
            return response()->json(['success' => 0, 'message' => 'Order not found or not assigned to you.'], 200);
        }

        if ($order->payment_status == 'paid') {
            return response()->json(['success' => 0, 'message' => 'Order is already paid.'], 200);
        }

        $this->_set_paystack_config();
        
        $amount = $order->order_amount + $order->edit_due_amount;

        $url = "https://api.paystack.co/transaction/initialize";

        $fields = [
            'email' => $order->customer->email ?? "customer@email.com",
            'amount' => $amount * 100,
            'currency' => \App\Utils\Helpers::currency_code() ?? 'NGN',
            'reference' => (string)('REF' . time() . 'RANDOM'),
            'callback_url' => route('paystack-delivery.callback', ['order_id' => $order->id]),
            'metadata' => [
                'order_id' => $order->id,
            ]
        ];

        $fields_string = http_build_query($fields);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . \Illuminate\Support\Facades\Config::get('paystack.secretKey'),
            "Cache-Control: no-cache",
        ));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);

        if ($response['status'] && isset($response['data']['authorization_url'])) {
            return response()->json([
                'success' => 1,
                'authorization_url' => $response['data']['authorization_url']
            ], 200);
        }

        return response()->json(['success' => 0, 'message' => 'Paystack integration error'], 403);
    }

    public function paystack_delivery_callback(Request $request)
    {
        $this->_set_paystack_config();
        
        $reference = $request->query('reference');
        $order_id = $request->query('order_id');
        
        if(!$reference || !$order_id) {
             return response()->json(['message' => 'Invalid callback'], 400);
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/$reference",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . \Illuminate\Support\Facades\Config::get('paystack.secretKey'),
                "Cache-Control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        
        $paymentDetails = json_decode($response, true);
        
        if ($paymentDetails['status'] == true && $paymentDetails['data']['status'] == 'success') {
            $order = Order::with(['customer', 'deliveryMan', 'latestEditHistory'])->find($order_id);
            if ($order && $order->order_status != 'delivered') {
                $expected_amount = round(($order['order_amount'] + $order['edit_due_amount']) * 100);
                $paid_amount = $paymentDetails['data']['amount'];

                if ($paid_amount >= $expected_amount) {
                    DB::beginTransaction();
                    try {
                        $affected = Order::where(['id' => $order_id])
                            ->where('order_status', '!=', 'delivered')
                            ->where('payment_status', '!=', 'paid')
                            ->update([
                                'order_status' => 'delivered',
                                'order_amount' => $order['order_amount'] + $order['edit_due_amount'],
                                'payment_status' => 'paid',
                                'edit_due_amount' => 0,
                                'payment_method' => 'paystack'
                            ]);

                        if ($affected > 0) {
                            if ($order?->latestEditHistory) {
                                OrderEditHistory::where(['id' => $order?->latestEditHistory?->id])->update([
                                    'order_due_payment_status' => 'paid',
                                    'order_due_payment_note' => 'Marked as paid by Paystack at Door',
                                ]);
                            }
                            
                            $deliveryMan = $order->deliveryMan;
                            if ($deliveryMan) {
                                $deliveryManWallet = \App\Models\DeliverymanWallet::where('delivery_man_id', $deliveryMan['id'])->first();
                                
                                if (empty($deliveryManWallet)) {
                                    \App\Models\DeliverymanWallet::create([
                                        'delivery_man_id' => $deliveryMan['id'],
                                        'current_balance' => $order?->deliveryman_charge ?? 0,
                                        'cash_in_hand' => 0,
                                        'pending_withdraw' => 0,
                                        'total_withdraw' => 0,
                                    ]);
                                } else {
                                    $deliveryManWallet->current_balance += $order->deliveryman_charge ?? 0;
                                    $deliveryManWallet->save();
                                }
                            }

                            if ($order['seller_id'] != null) {
                                OrderManager::getWalletManageOnOrderStatusChange($order, 'delivery man');
                                OrderDetail::where('order_id', $order->id)->update(['delivery_status' => 'delivered']);
                            }
                            
                            DB::commit();

                            event(new \App\Events\OrderStatusEvent(key: 'delivered', type: 'customer', order: $order));
                            OrderManager::getStockUpdateOnOrderStatusChange($order, 'delivered');
                            OrderManager::generateReferBonusForFirstOrder(orderId: $order['id']);

                            if(isset($deliveryMan->fcm_token)) {
                                $data = [
                                    'title' => 'Payment Received!',
                                    'description' => 'Customer paid via Paystack. Order automatically marked as delivered.',
                                    'order_id' => $order->id,
                                    'image' => '',
                                    'type' => 'order_status'
                                ];
                                Helpers::send_push_notif_to_device($deliveryMan->fcm_token, $data);
                            }
                        } else {
                            DB::rollBack();
                        }

                        return response("<div style='text-align:center; padding: 50px; font-family: sans-serif;'><h2>Payment Successful!</h2><p>Your order has been marked as paid and delivered. You can close this window.</p></div>");
                    } catch (\Exception $e) {
                        DB::rollBack();
                        return response()->json(['message' => 'Callback processing error', 'error' => $e->getMessage()], 500);
                    }
                }
            }
        }
        return response()->json(['message' => 'Payment failed or already delivered'], 400);
    }

    public function remit_cash_paystack_init(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $deliveryMan = $request['delivery_man'];
        $wallet = \App\Models\DeliverymanWallet::where('delivery_man_id', $deliveryMan['id'])->first();

        if (!$wallet || $wallet->cash_in_hand <= 0) {
            return response()->json(['success' => 0, 'message' => 'No cash in hand available to remit.'], 400);
        }

        $amount = (float)$request['amount'];
        if ($amount > (float)$wallet->cash_in_hand) {
            return response()->json(['success' => 0, 'message' => 'Remittance amount cannot exceed cash in hand (₦' . number_format($wallet->cash_in_hand, 2) . ').'], 400);
        }

        $this->_set_paystack_config();

        $reference = (string)('REMIT_' . $deliveryMan['id'] . '_' . time() . '_' . rand(1000, 9999));
        $url = "https://api.paystack.co/transaction/initialize";

        $fields = [
            'email' => $deliveryMan['email'] ?? "deliveryman_" . $deliveryMan['id'] . "@victoriousmarket.com.ng",
            'amount' => $amount * 100,
            'currency' => \App\Utils\Helpers::currency_code() ?? 'NGN',
            'reference' => $reference,
            'callback_url' => route('paystack-remittance.callback', [
                'delivery_man_id' => $deliveryMan['id'],
                'amount' => $amount,
            ]),
            'metadata' => [
                'type' => 'cash_remittance',
                'delivery_man_id' => $deliveryMan['id'],
                'amount' => $amount,
            ]
        ];

        $fields_string = http_build_query($fields);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . \Illuminate\Support\Facades\Config::get('paystack.secretKey'),
            "Cache-Control: no-cache",
        ));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);

        if ($response && isset($response['status']) && $response['status'] && isset($response['data']['authorization_url'])) {
            return response()->json([
                'success' => 1,
                'authorization_url' => $response['data']['authorization_url']
            ], 200);
        }

        return response()->json(['success' => 0, 'message' => 'Paystack initialization failed. Please try again.'], 403);
    }

    public function paystack_remittance_callback(Request $request)
    {
        $this->_set_paystack_config();

        $reference = $request->query('reference');
        $deliveryManId = $request->query('delivery_man_id');
        $remitAmount = (float)$request->query('amount');

        if (!$reference || !$deliveryManId) {
            return response()->json(['message' => 'Invalid remittance callback'], 400);
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "authorization: Bearer " . \Illuminate\Support\Facades\Config::get('paystack.secretKey'),
                "cache-control: no-cache"
            ],
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return response()->json(['message' => 'Paystack verification failed', 'error' => $err], 500);
        }

        $tranx = json_decode($response);
        if ($tranx && isset($tranx->status) && $tranx->status) {
            if ('success' == $tranx->data->status) {
                $actualAmount = $tranx->data->amount / 100;
                $deliveryMan = \App\Models\DeliveryMan::find($deliveryManId);
                if ($deliveryMan) {
                    DB::beginTransaction();
                    try {
                        $wallet = \App\Models\DeliverymanWallet::where('delivery_man_id', $deliveryManId)->first();
                        if ($wallet) {
                            $deductAmount = min($wallet->cash_in_hand, $actualAmount);
                            $wallet->cash_in_hand = max(0, $wallet->cash_in_hand - $deductAmount);
                            $wallet->save();

                            // Record in delivery_man_transactions for audit trail
                            \App\Models\DeliveryManTransaction::create([
                                'delivery_man_id' => $deliveryManId,
                                'user_id' => 0,
                                'user_type' => 'admin',
                                'transaction_id' => $reference,
                                'debit' => 0,
                                'credit' => $actualAmount,
                                'transaction_type' => 'cash_collect_by_admin',
                                'transaction_note' => 'Remitted via Paystack in-app',
                            ]);
                        }
                        DB::commit();

                        if (isset($deliveryMan->fcm_token)) {
                            $data = [
                                'title' => 'Cash Remitted Successfully!',
                                'description' => '₦' . number_format($actualAmount, 2) . ' cash in hand has been successfully remitted to Admin.',
                                'order_id' => '',
                                'image' => '',
                                'type' => 'wallet'
                            ];
                            Helpers::send_push_notif_to_device($deliveryMan->fcm_token, $data);
                        }

                        return response("<div style='text-align:center; padding: 60px 20px; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background-color: #f7f9fc;'><div style='background: white; max-width: 480px; margin: 0 auto; padding: 40px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);'><div style='font-size: 54px; color: #4A148C;'>✓</div><h2 style='color:#4A148C; margin-top: 10px;'>Remittance Successful!</h2><p style='font-size:18px; font-weight: bold; color: #333;'>₦" . number_format($actualAmount, 2) . "</p><p style='color: #666; font-size: 14px;'>Your Cash in Hand balance has been updated successfully. You can return to the app.</p></div></div>");
                    } catch (\Exception $e) {
                        DB::rollBack();
                        return response()->json(['message' => 'Remittance processing error', 'error' => $e->getMessage()], 500);
                    }
                }
            }
        }

        return response()->json(['message' => 'Remittance payment failed or incomplete'], 400);
    }
}
