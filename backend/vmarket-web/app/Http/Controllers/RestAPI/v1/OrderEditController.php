<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Events\DigitalProductOtpVerificationEvent;
use App\Events\RefundEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\RefundStoreRequest;
use App\Models\AdminWallet;
use App\Models\Cart;
use App\Models\Currency;
use App\Models\DigitalProductOtpVerification;
// [AI] OfflinePaymentMethod import removed — offline payment permanently decommissioned in Victorious MARKET.
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderDetailsRewards;
use App\Models\OrderEditHistory;
use App\Models\RefundRequest;
use App\Models\Setting;
use App\Models\ShippingAddress;
use App\Services\OrderService;
use App\Traits\CommonTrait;
use App\Traits\FileManagerTrait;
use App\Traits\OrderEditManager;
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

class OrderEditController extends Controller
{
    use CommonTrait, FileManagerTrait, OrderEditManager;

    public function __construct(
        private readonly OrderService $orderService,
    )
    {
    }

    public function duePaymentByWallet(Request $request): JsonResponse
    {
        // [AI] Customer Wallet Decommissioned: Reject active wallet due payment
        return response()->json([
            'status' => false,
            'message' => 'Wallet payment is permanently decommissioned in Victorious MARKET. Please pay online via Paystack (Doorstep Delivery) or select Customer Pickup — Pay After Inspection.',
        ], 403);
    }

    public function duePaymentByCod(Request $request): JsonResponse
    {
        // [AI] V1 Authority Invariant: Cash on delivery is permanently decommissioned in Victorious MARKET.
        return response()->json([
            'status' => false,
            'message' => 'Cash on delivery is permanently decommissioned in Victorious MARKET. Please pay online via Paystack.',
        ], 403);
    }

    public function duePaymentByOfflinePayment(Request $request): JsonResponse
    {
        // [AI] Offline payment permanently decommissioned in Victorious MARKET.
        // This endpoint is preserved as a fail-closed stub to prevent 500 errors
        // from any legacy client that still calls this path. Route was already removed.
        return response()->json([
            'status' => false,
            'message' => 'Offline payment is permanently decommissioned in Victorious MARKET. Please pay online via Paystack (Doorstep Delivery) or select Customer Pickup — Pay After Inspection.',
        ], 403);
    }

    public function duePaymentByDigitalPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'payment_method' => 'required',
            'current_currency_code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $order = Order::with(['latestEditHistory'])->where('id', $request['order_id'])->first();
        if (!$order) {
            return response()->json(['message' => translate('Order_not_found')], 404);
        }

        $customer = Helpers::getCustomerInformation($request);

        $isOwner = false;
        if ($customer != 'offline' && $order->customer_id == $customer->id) {
            $isOwner = true;
        } elseif ($order->is_guest && $request->has('guest_id') && $order->customer_id == $request['guest_id'] && is_numeric($request['guest_id'])) {
            $isOwner = true;
        }

        if (!$isOwner) {
            return response()->json(['message' => translate('unauthorized_access')], 403);
        }

        $response = $this->payEditOrderDueByDigitalPayment(request: $request, order: $order, customer: $customer);

        if (!$response['status'] && isset($response['message'])) {
            return response()->json(['message' => $response['message']], 401);
        }

        if ($response['redirect_link']) {
            return response()->json(['redirect_link' => $response['redirect_link']]);
        }

        return response()->json(['message' => $response['message'] ?? 'Failed'], 403);
    }

}
