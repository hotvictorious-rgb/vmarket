<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Events\DigitalProductOtpVerificationEvent;
use App\Events\RefundEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\RefundStoreRequest;
use App\Models\Cart;
use App\Models\Currency;
use App\Models\DigitalProductOtpVerification;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderDetailsRewards;
use App\Models\RefundRequest;
use App\Models\Setting;
use App\Models\ShippingAddress;
use App\Services\OrderService;
use App\Traits\CommonTrait;
use App\Traits\FileManagerTrait;
use App\Traits\SmsGateway;
use App\Models\User;
use App\Utils\CartManager;
use App\Utils\Convert;
use App\Utils\CustomerManager;
use App\Utils\Helpers;
use App\Utils\ImageManager;
use App\Utils\OrderManager;
use App\Utils\SMSModule;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use CommonTrait, FileManagerTrait;

    public function __construct(
        private readonly OrderService $orderService,
    )
    {
    }

    public function track_by_order_id(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $user = Helpers::getCustomerInformation($request);
        $order = Order::with(['deliveryMan', 'orderStatusHistory' => function ($query) {
            return $query->latest();
        }])->where(['id' => $request['order_id']])->first();

        if (!$order) {
            return response()->json(['message' => translate('order_not_found')], 404);
        }

        $isOwner = false;
        if ($user != 'offline' && $order->customer_id == $user->id) {
            $isOwner = true;
        } elseif ($order->is_guest) {
            // [AI] Cryptographically Guarded Guest Ownership Verification:
            // Protects customer PII and secret pickup verification code from sequential IDOR scraping.
            // 1. Primary Authority: Unguessable 64-char guest access token (constant-time comparison)
            // 2. Secondary/Fallback: Exact phone number matching order address
            $guestToken = $request->get('guest_token') ?? $request->header('X-Guest-Token');
            if (!empty($order->guest_access_token) && !empty($guestToken) && hash_equals((string)$order->guest_access_token, (string)$guestToken)) {
                $isOwner = true;
            } else {
                $shippingData = is_array($order->shipping_address_data) ? $order->shipping_address_data : (json_decode($order->shipping_address_data, true) ?? []);
                $billingData = is_array($order->billing_address_data) ? $order->billing_address_data : (json_decode($order->billing_address_data, true) ?? []);
                $expectedPhone = $shippingData['phone'] ?? ($billingData['phone'] ?? null);

                $providedPhone = $request->get('phone');
                if (!empty($expectedPhone) && !empty($providedPhone) && preg_replace('/[^0-9]/', '', $expectedPhone) === preg_replace('/[^0-9]/', '', $providedPhone)) {
                    $isOwner = true;
                }
            }
        }

        $data = json_decode(json_encode($order), true);

        // Sanitize sensitive PII if unauthenticated or non-owner caller
        if (!$isOwner) {
            unset($data['customer']);
            unset($data['billing_address_data']);
            unset($data['shipping_address_data']);
            unset($data['transaction_ref']);
            unset($data['verification_code']);
            unset($data['pickup_verification_code']);
            if (isset($data['delivery_man'])) {
                unset($data['delivery_man']['identity_number']);
                unset($data['delivery_man']['identity_image']);
                unset($data['delivery_man']['fcm_token']);
            }
        }

        return response()->json($data, 200);
    }

    public function track_order_details_history(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }
        $orderId = $request['order_id'];
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => translate('order_not_found')], 404);
        }
        $isOrderOnlyDigital = $this->orderService->getCheckIsOrderOnlyDigital(order: $order);
        $getTrackOrderHistory = OrderManager::getTrackOrderStatusHistory(orderId: $orderId, isOrderOnlyDigital: $isOrderOnlyDigital);
        return response()->json($getTrackOrderHistory, 200);
    }

    public function order_cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $user = Helpers::getCustomerInformation($request);
        $order = Order::where(['id' => $request->order_id])->first();

        if (!$order) {
            return response()->json(['message' => translate('order_not_found')], 404);
        }

        // [AI] Ownership verification (registered customer vs guest)
        $isOwner = false;
        if ($user != 'offline' && $order->customer_id == $user->id) {
            $isOwner = true;
        } elseif ($order->is_guest) {
            // [AI] Cryptographically Guarded Guest Ownership Verification:
            $guestToken = $request->get('guest_token') ?? $request->header('X-Guest-Token');
            if (!empty($order->guest_access_token) && !empty($guestToken) && hash_equals((string)$order->guest_access_token, (string)$guestToken)) {
                $isOwner = true;
            } else {
                $shippingData = is_array($order->shipping_address_data) ? $order->shipping_address_data : (json_decode($order->shipping_address_data, true) ?? []);
                $billingData = is_array($order->billing_address_data) ? $order->billing_address_data : (json_decode($order->billing_address_data, true) ?? []);
                $expectedPhone = $shippingData['phone'] ?? ($billingData['phone'] ?? null);

                $providedPhone = $request->get('phone');
                if (!empty($expectedPhone) && !empty($providedPhone) && preg_replace('/[^0-9]/', '', $expectedPhone) === preg_replace('/[^0-9]/', '', $providedPhone)) {
                    $isOwner = true;
                }
            }
        }

        if (!$isOwner) {
            return response()->json(['message' => translate('unauthorized_access')], 403);
        }

        // [AI] Guard: Rider already assigned means order is in transit – cannot cancel
        if (!empty($order->delivery_man_id)) {
            return response()->json(['message' => translate('order_cannot_be_cancelled_rider_assigned')], 403);
        }

        // [AI] In-Shop Pickup Cancellation Rule:
        // Customer can cancel anytime before physical inspection and payment (unpaid status)
        $isPickupUnpaid = ($order['order_type'] === 'pickup' && $order['payment_status'] === 'unpaid' && in_array($order['order_status'], ['pending', 'confirmed']));

        if ($isPickupUnpaid) {
            OrderManager::getStockUpdateOnOrderStatusChange($order, 'canceled');
            Order::where(['id' => $request->order_id])->update([
                'order_status' => 'canceled'
            ]);

            return response()->json(translate('order_canceled_successfully'), 200);
        }

        return response()->json(['message' => translate('status_not_changeable_now')], 403);
    }

    public function refund_request(Request $request): JsonResponse
    {
        $orderDetails = OrderDetail::find($request->order_details_id);
        if (!$orderDetails) {
            return response()->json(['message' => translate('order_details_not_found')], 404);
        }

        $user = $request->user();

        // [AI] Ownership Guard: Ensure the customer owns the parent order
        $order = Order::where('id', $orderDetails->order_id)
            ->where('customer_id', $user->id)
            ->first();
        if (!$order) {
            return response()->json(['message' => translate('unauthorized_access')], 403);
        }

        $loyaltyPointStatus = getWebConfig(name: 'loyalty_point_status');
        if ($loyaltyPointStatus == 1) {
            $loyaltyPoint = CustomerManager::countLoyaltyPointForAmount($request->order_details_id);
            if (($user->loyalty_point ?? 0) < $loyaltyPoint) {
                return response()->json(['message' => translate('you_have_not_sufficient_loyalty_point_to_refund_this_order')], 202);
            }
        }

        if ($orderDetails->delivery_status == 'delivered') {
            $total_product_price = 0;
            $data = [];
            foreach ($order->details as $key => $or_d) {
                $total_product_price += ($or_d->qty * $or_d->price) + $or_d->tax - $or_d->discount;
            }

            $subtotal = ($order_details->price * $order_details->qty) - $order_details->discount + $order_details->tax;
            $coupon_discount = ($order->discount_amount * $subtotal) / $total_product_price;

            $refundInfo = OrderManager::getRefundDetailsForSingleOrderDetails(orderDetailsId: $request['order_details_id']);

            $data['product_price'] = $order_details->price;
            $data['quntity'] = $order_details->qty;
            $data['product_total_discount'] = $order_details->discount;
            $data['product_total_tax'] = $order_details->tax;
            $data['subtotal'] = $subtotal;
            $data['coupon_discount'] = $coupon_discount;
            $data['refund_amount'] = $refundInfo['total_refundable_amount'];
            $data['referral_discount'] = $refundInfo['referral_discount'];

            $expired = false;
            $already_requested = false;
            if ($orderDetails->refund_request != 0) {
                $already_requested = true;
            }
            if (!$order->isWithinRefundWindow()) {
                $expired = true;
            }
            return response()->json(['already_requested' => $already_requested, 'expired' => $expired, 'refund' => $data], 200);
        } else {
            return response()->json(['message' => translate('You_can_request_for_refund_after_order_delivered')], 200);
        }
    }

    public function store_refund(RefundStoreRequest $request): JsonResponse
    {
        $orderDetails = OrderDetail::find($request->order_details_id);
        $user = $request->user();

        // [AI] Ownership Guard: Ensure the authenticated user owns this order_details row.
        // Without this check any logged-in customer can file a refund on another customer's order.
        $parentOrder = Order::where('id', $orderDetails->order_id)
            ->where('customer_id', $user->id)
            ->first();
        if (!$parentOrder) {
            return response()->json(['message' => translate('unauthorized_access')], 403);
        }

        // [AI] Receipt-first guard: merchandise return requires confirmed customer receipt.
        // An order with received_at = NULL has no 24-hour window and cannot enter this path.
        // Undelivered orders follow the separate executeUndeliveredOrderRefund() path only.
        if (!$parentOrder->isWithinRefundWindow()) {
            return response()->json([
                'message' => translate('refund_not_available_order_must_be_received_and_within_24_hour_return_window')
            ], 403);
        }

        // [AI] Delivery status guard: refund only allowed after delivery
        if ($orderDetails->delivery_status !== 'delivered') {
            return response()->json(['message' => translate('You_can_request_for_refund_after_order_delivered')], 403);
        }

        $orderDetailsReward = OrderDetailsRewards::where('order_details_id', $request->order_details_id)
            ->where('reward_type', '!=', 'loyalty_point')
            ->first();

        if ($orderDetailsReward && $user->loyalty_point < $orderDetailsReward['reward_amount']) {
            return response()->json(
                translate('you have not sufficient loyalty point to refund this order!!'),
                200
            );
        }

        if ($orderDetails->refund_request != 0) {
            return response()->json(
                translate('already_applied_for_refund_request!!'),
                302
            );
        }

        $refund_request = new RefundRequest();
        $refund_request->order_details_id = $request->order_details_id;
        $refund_request->customer_id = $user->id;
        $refund_request->status = 'pending';
        $refund_request->amount = OrderManager::getRefundDetailsForSingleOrderDetails(orderDetailsId: $orderDetails->id)['total_refundable_amount'];
        $refund_request->product_id = $orderDetails->product_id;
        $refund_request->order_id = $orderDetails->order_id;
        $refund_request->refund_reason = $request->refund_reason;

        if ($request->hasFile('images')) {
            $images = [];

            foreach ($request->file('images') as $img) {
                $images[] = [
                    'image_name' => ImageManager::upload('refund/', 'webp', $img),
                    'storage' => getWebConfig(name: 'storage_connection_type') ?? 'public',
                ];
            }
            $refund_request->images = $images;
        }

        $refund_request->save();
        $orderDetails->update(['refund_request' => 1]);

        // [AI] Transition third-party order to disputed during refund dispute window
        if ($parentOrder->seller_is === 'seller') {
            $parentOrder->vendor_settlement_status = 'disputed';
            $parentOrder->save();
        }

        $order = Order::find($orderDetails->order_id);

        event(new RefundEvent(
            status: 'refund_request',
            order: $order,
            refund: $refund_request,
            orderDetails: $orderDetails
        ));

        return response()->json(
            translate('refunded_request_updated_successfully!!'),
            200
        );
    }

    public function refund_details(Request $request): JsonResponse
    {
        $orderDetails = OrderDetail::find($request->id);
        if (!$orderDetails) {
            return response()->json(['message' => translate('order_details_not_found')], 404);
        }

        // [AI] Ownership Guard: Ensure the authenticated user owns this order
        $order = Order::where('id', $orderDetails->order_id)
            ->where('customer_id', $request->user()->id)
            ->first();
        if (!$order) {
            return response()->json(['message' => translate('unauthorized_access')], 403);
        }

        $refund = RefundRequest::where('customer_id', $request->user()->id)
            ->with(['refundStatus'])
            ->where('order_details_id', $orderDetails->id)->get();

        $total_product_price = 0;
        $data = [];
        foreach ($order->details as $key => $or_d) {
            $total_product_price += ($or_d->qty * $or_d->price) + $or_d->tax - $or_d->discount;
        }

        $subtotal = ($orderDetails->price * $orderDetails->qty) - $orderDetails->discount + $orderDetails->tax;
        $coupon_discount = ($order->discount_amount * $subtotal) / $total_product_price;

        $data['product_price'] = $orderDetails->price;
        $data['quntity'] = $orderDetails->qty;
        $data['product_total_discount'] = $orderDetails->discount;
        $data['product_total_tax'] = $orderDetails->tax;
        $data['subtotal'] = $subtotal;
        $data['coupon_discount'] = $coupon_discount;
        $data['refund_amount'] = OrderManager::getRefundDetailsForSingleOrderDetails(orderDetailsId: $orderDetails['id'])['total_refundable_amount'];
        $data['refund_request'] = $refund;
        $data['order_place_date'] = $order->created_at;
        $data['referral_discount'] = $order?->refer_and_earn_discount ?? 0;

        return response()->json($data, 200);
    }

    public function digital_product_download($id, Request $request): JsonResponse
    {
        $user = Helpers::getCustomerInformation($request);
        $order_details_data = OrderDetail::with('order.customer')->find($id);

        if ($order_details_data) {
            if ($order_details_data->order->payment_status !== "paid") {
                return response()->json([
                    'status' => 0,
                    'message' => translate('Payment_must_be_confirmed_first') . ' !!',
                ]);
            };

            if ($order_details_data->order->is_guest) {
                $customer_email = $order_details_data->order->shipping_address_data ? $order_details_data->order->shipping_address_data->email : ($order_details_data->order->billing_address_data ? $order_details_data->order->billing_address_data->email : '');

                $customer_phone = $order_details_data->order->shipping_address_data ? $order_details_data->order->shipping_address_data->phone : ($order_details_data->order->billing_address_data ? $order_details_data->order->billing_address_data->phone : '');

                $customer_data = ['email' => $customer_email, 'phone' => $customer_phone];
                return self::digital_product_download_process($order_details_data, $customer_data);
            } else {
                if ($user != 'offline' && $user->id == $order_details_data->order->customer->id) {
                    $file_name = '';
                    if ($order_details_data->product->digital_product_type == 'ready_product' && $order_details_data->product->digital_file_ready) {
                        $file_path = asset('storage/app/public/product/digital-product/' . $order_details_data->product->digital_file_ready);
                        $file_name = $order_details_data->product->digital_file_ready;
                    } else {
                        $file_path = asset('storage/app/public/product/digital-product/' . $order_details_data->digital_file_after_sell);
                        $file_name = $order_details_data->digital_file_after_sell;
                    }

                    if (File::exists(base_path('storage/app/public/product/digital-product/' . $file_name))) {
                        return \response()->download($file_path);
                    } else {
                        return response()->json([
                            'status' => 0,
                            'message' => translate('file_not_found'),
                        ]);
                    }
                } else {
                    $customer_data = ['email' => $order_details_data->order->customer->email ?? '', 'phone' => $order_details_data->order->customer->phone ?? ''];
                    return self::digital_product_download_process($order_details_data, $customer_data);
                }
            }
        } else {
            return response()->json(['message' => translate('order_Not_Found')], 403);
        }
    }

    public function digital_product_download_process($order_details_data, $customer): JsonResponse
    {
        $status = 2;
        $emailServices_smtp = getWebConfig(name: 'mail_config');
        if ($emailServices_smtp['status'] == 0) {
            $emailServices_smtp = getWebConfig(name: 'mail_config_sendgrid');
        }

        $paymentPublishedStatus = config('get_payment_publish_status') ?? 0;

        if ($paymentPublishedStatus == 1) {
            $smsConfigStatus = Setting::where(['settings_type' => 'sms_config', 'is_active' => 1])->count() > 0 ? 1 : 0;
        } else {
            $smsConfigStatus = Setting::where(['settings_type' => 'sms_config', 'is_active' => 1])->whereIn('key_name', Helpers::getDefaultSMSGateways())->count() > 0 ? 1 : 0;
        }

        if ($emailServices_smtp['status'] || $smsConfigStatus) {
            $token = rand(100000, 999999);
            if ($customer['email'] == '' && $customer['phone'] == '') {
                return response()->json([
                    'status' => $status,
                    'file_path' => '',
                    'view' => view(VIEW_FILE_NAMES['digital_product_order_otp_verify_failed'])->render(),
                ]);
            }

            $verification_data = DigitalProductOtpVerification::where('identity', $customer['email'])->orWhere('identity', $customer['phone'])->where('order_details_id', $order_details_data->id)->latest()->first();
            $otp_interval_time = getWebConfig(name: 'otp_resend_time') ?? 1; //second

            if (isset($verification_data) && Carbon::parse($verification_data->created_at)->diffInSeconds() < $otp_interval_time) {
                $time_count_in_second = $otp_interval_time - Carbon::parse($verification_data->created_at)->diffInSeconds();
                return response()->json([
                    'status' => 0,
                    'email_config_status' => $emailServices_smtp['status'],
                    'sms_config_status' => $smsConfigStatus,
                    'time_count_in_second' => $time_count_in_second,
                ]);
            } else {
                $verify_data = [
                    'order_details_id' => $order_details_data->id,
                    'token' => $token,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                DigitalProductOtpVerification::updateOrInsert(['identity' => $customer['email'], 'order_details_id' => $order_details_data->id], $verify_data);
                DigitalProductOtpVerification::updateOrInsert(['identity' => $customer['phone'], 'order_details_id' => $order_details_data->id], $verify_data);

                $reset_data = DigitalProductOtpVerification::where('identity', $customer['email'])->orWhere('identity', $customer['phone'])->where('order_details_id', $order_details_data->id)->latest()->first();
                $otp_resend_time = getWebConfig(name: 'otp_resend_time') > 0 ? getWebConfig(name: 'otp_resend_time') : 0;
                $token_time = Carbon::parse($reset_data->created_at);
                $convert_time = $token_time->addSeconds((int)$otp_resend_time);
                $time_count_in_second = $convert_time > Carbon::now() ? Carbon::now()->diffInSeconds($convert_time) : 0;
                $mail_status = 0;

                if ($emailServices_smtp['status'] == 1) {
                    try {
                        $data = [
                            'userName' => $customer['f_name'],
                            'userType' => 'customer',
                            'templateName' => 'digital-product-otp',
                            'subject' => translate('verification_Code'),
                            'title' => translate('verification_Code') . '!',
                            'verificationCode' => $token,
                        ];
                        event(new DigitalProductOtpVerificationEvent(email: $customer['email'], data: $data));
                        $mail_status = 1;
                    } catch (\Exception $exception) {
                    }
                }

                $response = SMSModule::sendCentralizedSMS($customer['phone'], $token);

                $sms_status = ($response == "not_found" || $smsConfigStatus == 0) ? 0 : 1;
                if ($mail_status || $sms_status) {
                    return response()->json([
                        'status' => 1,
                        'email_config_status' => $emailServices_smtp['status'],
                        'sms_config_status' => $smsConfigStatus,
                        'email_sent_status' => $mail_status,
                        'sms_sent_status' => $sms_status,
                        'time_count_in_second' => $time_count_in_second,
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 0,
                        'email_config_status' => $emailServices_smtp['status'],
                        'sms_config_status' => $smsConfigStatus,
                        'email_sent_status' => $mail_status,
                        'sms_sent_status' => $sms_status,
                        'time_count_in_second' => $time_count_in_second,
                    ], 403);
                }
            }
        } else {
            return response()->json([
                'status' => 0,
                'email_config_status' => $emailServices_smtp['status'],
                'sms_config_status' => $smsConfigStatus,
                'email_config_status' => $emailServices_smtp['status'],
                'sms_config_status' => $smsConfigStatus,
            ], 403);
        }

    }

    public function digital_product_download_otp_verify(Request $request)
    {
        $order_details_data = OrderDetail::with('order.customer')->find($request->order_details_id);
        if (!$order_details_data || !$order_details_data->order || $order_details_data->order->payment_status !== "paid") {
            return response()->json([
                'message' => translate('Payment_must_be_confirmed_first'),
            ], 403);
        }

        $verification = DigitalProductOtpVerification::where(['token' => $request->otp, 'order_details_id' => $request->order_details_id])->first();

        if ($verification) {
            if (Carbon::parse($verification->created_at)->diffInMinutes() > 15) {
                $verification->delete();
                return response()->json([
                    'message' => translate('OTP_is_expired'),
                ], 403);
            }

            if ($order_details_data) {
                if ($order_details_data->product->digital_product_type == 'ready_product' && $order_details_data->product->digital_file_ready) {
                    $file_path = storage_path('app/public/product/digital-product/' . $order_details_data->product->digital_file_ready);
                    $file_name = $order_details_data->product->digital_file_ready;
                } else if ($order_details_data->digital_file_after_sell) {
                    $file_path = storage_path('app/public/product/digital-product/' . $order_details_data->digital_file_after_sell);
                    $file_name = $order_details_data->digital_file_after_sell;
                }
            }

            if ($request->has('action') && $request->action == "download") {
                DigitalProductOtpVerification::where(['token' => $request->otp, 'order_details_id' => $request->order_details_id])->delete();
            }

            if (isset($file_name) && File::exists(base_path('storage/app/public/product/digital-product/' . $file_name))) {
                return \response()->download($file_path);
            } else {
                return response()->json([
                    'status' => 0,
                    'message' => translate('file_not_found'),
                ]);
            }

        } else {
            return response()->json([
                'message' => translate('The_OTP_is_incorrect'),
            ], 403);
        }
    }

    public function digital_product_download_otp_resend(Request $request)
    {
        $token_info = DigitalProductOtpVerification::where(['order_details_id' => $request->order_details_id])->first();
        $otp_interval_time = getWebConfig(name: 'otp_resend_time') ?? 1; //minute
        if (isset($token_info) && Carbon::parse($token_info->created_at)->diffInSeconds() < $otp_interval_time) {
            $time_count_in_second = $otp_interval_time - Carbon::parse($token_info->created_at)->diffInSeconds();

            return response()->json([
                'status' => 0,
                'time_count_in_second' => $time_count_in_second,
                'message' => 'Please try again after ' . CarbonInterval::seconds($time_count_in_second)->cascade()->forHumans()
            ]);
        } else {
            $guest_email = '';
            $guest_phone = '';
            $token = rand(100000, 999999);

            $order_details_data = OrderDetail::with('order.customer')->find($request->order_details_id);

            try {
                if ($order_details_data->order->shipping_address_data) {
                    $guest_name = $order_details_data->order->shipping_address_data ? $order_details_data->order->shipping_address_data->contact_person_name : null;
                    $guest_email = $order_details_data->order->shipping_address_data ? $order_details_data->order->shipping_address_data->email : null;
                    $guest_phone = $order_details_data->order->shipping_address_data ? $order_details_data->order->shipping_address_data->phone : null;
                } else {
                    $guest_name = $order_details_data->order->billing_address_data ? $order_details_data->order->billing_address_data->contact_person_name : null;
                    $guest_email = $order_details_data->order->billing_address_data ? $order_details_data->order->billing_address_data->email : null;
                    $guest_phone = $order_details_data->order->billing_address_data ? $order_details_data->order->billing_address_data->phone : null;
                }
            } catch (\Throwable $th) {

            }

            $emailServices_smtp = getWebConfig(name: 'mail_config');
            if ($emailServices_smtp['status'] == 0) {
                $emailServices_smtp = getWebConfig(name: 'mail_config_sendgrid');
            }
            if ($emailServices_smtp['status'] == 1) {
                try {
                    $data = [
                        'userName' => $guest_name,
                        'userType' => 'customer',
                        'templateName' => 'digital-product-otp',
                        'subject' => translate('verification_Code'),
                        'title' => translate('verification_Code') . '!',
                        'verificationCode' => $token,
                    ];
                    event(new DigitalProductOtpVerificationEvent(email: $guest_email, data: $data));
                    $mail_status = 1;
                } catch (\Exception $exception) {
                    $mail_status = 0;
                }
            } else {
                $mail_status = 0;
            }

            $response = SMSModule::sendCentralizedSMS($guest_phone, $token);

            $sms_status = $response == "not_found" ? 0 : 1;
            if ($mail_status || $sms_status) {
                $verify_data = [
                    'order_details_id' => $order_details_data->id,
                    'token' => $token,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                DigitalProductOtpVerification::updateOrInsert(['identity' => $guest_email, 'order_details_id' => $order_details_data->id], $verify_data);
                DigitalProductOtpVerification::updateOrInsert(['identity' => $guest_phone, 'order_details_id' => $order_details_data->id], $verify_data);
            }

            return response()->json([
                'mail_status' => $mail_status,
                'sms_status' => $sms_status,
                'status' => ($mail_status || $sms_status) ? 1 : 0,
                'new_time' => $otp_interval_time,
                'message' => 'OTP sent successfully',
            ]);

        }
    }

    public function order_again(Request $request): JsonResponse
    {
        $orderData = OrderManager::generateOrderAgain($request);
        $addToCartCount = $orderData['add_to_cart_count'];

        if ($orderData['order_product_count'] == $addToCartCount) {
            return response()->json(['message' => 'Added to cart successfully'], 200);
        } elseif ($addToCartCount > 0) {
            return response()->json(['message' => $addToCartCount . ' item added to cart successfully!'], 200);
        }

        return response()->json(['message' => 'All items were not added to cart as they are currently unavailable for purchase'], 403);
    }


    public function track_order(Request $request): JsonResponse
    {
        $user = Helpers::getCustomerInformation($request);

        $orderExist = null;
        $order = Order::with('shippingAddress', 'billingAddress', 'details')
            ->where(['id' => $request['order_id'], 'order_type' => 'default_type'])
            ->first();

        if ($user != 'offline') {
            if ($order && $order->is_guest) {
                $shippingAddress = (array)($order['shipping_address_data'] ?? []);
                $billingAddress = (array)($order['billing_address_data'] ?? []);
                if ($order?->shippingAddress && $order?->shippingAddress->phone == $request['phone_number']) {
                    $orderExist = $order;
                } else if (($shippingAddress['phone'] ?? '') == $request['phone_number']) {
                    $orderExist = $order;
                } else if ($order && $order?->billingAddress && $order?->billingAddress->phone == $request['phone_number']) {
                    $orderExist = $order;
                } else if (($billingAddress['phone'] ?? '') == $request['phone_number']) {
                    $orderExist = $order;
                }
            } elseif ($user->phone == $request['phone_number']) {
                $orderExist = Order::where(['id' => $request['order_id'], 'order_type' => 'default_type', 'customer_id' => auth('customer')->id()])
                    ->whereHas('details', function ($query) {
                        return $query;
                    })->first();
            }

            if ($request['from_order_details'] == 1) {
                $orderExist = Order::where(['id' => $request['order_id'], 'order_type' => 'default_type'])->whereHas('details', function ($query) {
                    $query->where('customer_id', auth('customer')->id());
                })->first();
            }

        } else {
            $user_id = User::where('phone', $request['phone_number'])->first();
            if ($order && $order->is_guest) {
                $shippingAddress = (array)($order['shipping_address_data'] ?? []);
                $billingAddress = (array)($order['billing_address_data'] ?? []);
                if ($order?->shippingAddress && $order?->shippingAddress->phone == $request['phone_number']) {
                    $orderExist = $order;
                } else if (($shippingAddress['phone'] ?? '') == $request['phone_number']) {
                    $orderExist = $order;
                } else if ($order?->billingAddress && $order?->billingAddress->phone == $request['phone_number']) {
                    $orderExist = $order;
                } else if (($billingAddress['phone'] ?? '') == $request['phone_number']) {
                    $orderExist = $order;
                }
            } elseif ($user_id) {
                $orderExist = Order::where(['customer_id' => $user_id->id, 'id' => $request['order_id'], 'order_type' => 'default_type'])->whereHas('details', function ($query) {
                    return $query;
                })->first();
            } else {
                return response()->json(['message' => 'Invalid Phone Number'], 403);
            }
        }

        if (isset($orderExist)) {
            $details = OrderDetail::with(['order.deliveryMan', 'verificationImages', 'seller.shop', 'product'])
                ->where(['order_id' => $orderExist['id']])
                ->get();

            $details->map(function ($query) {
                $query['variation'] = is_array($query['variation']) ? $query['variation'] : json_decode($query['variation'], true);
                $product = is_array($query['product_details']) ? $query['product_details'] : (json_decode($query['product_details'], true) ?? []);
                if ($product['product_type'] == 'digital' && $product['digital_product_type'] == 'ready_product' && $product['digital_file_ready']) {
                    $checkFilePath = storageLink('product/digital-product', $product['digital_file_ready'], ($product['storage_path'] ?? 'public'));
                    $product['digital_file_ready_full_url'] = $checkFilePath;
                }

                if (!isset($product['thumbnail_full_url']) && isset($query?->product?->thumbnail_full_url)) {
                    $product['thumbnail_full_url'] = $query?->product?->thumbnail_full_url;
                }

                $query['product_details'] = Helpers::product_data_formatting($product);
                return $query;
            });

            return response()->json($details, 200);
        }

        return response()->json(['message' => 'Invalid Order Id or Phone Number'], 403);
    }
}

