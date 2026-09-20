<?php

namespace App\Http\Controllers\RestAPI\v3\seller;

use App\Events\RefundEvent;
use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\RefundRequest;
use App\Models\RefundStatus;
use App\Models\User;
use App\Utils\CustomerManager;
use App\Utils\Helpers;
use App\Utils\OrderManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RefundController extends Controller
{
    public function list(Request $request):JsonResponse
    {
        $seller = $request->seller;

        $startDate = null;
        $endDate = null;
        if (isset($request['date_type']) && $request['date_type'] == 'custom_date') {
            if (isset($request['start_date']) && !is_null($request['start_date']) && isset($request['end_date']) && !is_null($request['end_date'])) {
                $startFormatted = \Carbon\Carbon::parse($request['start_date'])->format('m/d/Y');
                $endFormatted = \Carbon\Carbon::parse($request['end_date'])->format('m/d/Y');
                $dateRange = $startFormatted . ' - ' . $endFormatted;
            } else {
                $dateRange = now()->subDays(6)->format('m/d/Y') . ' - ' . now()->format('m/d/Y');
            }

            list($startDate, $endDate) = explode(' - ', $dateRange);
            $startDate = Carbon::createFromFormat('m/d/Y', trim($startDate));
            $endDate = Carbon::createFromFormat('m/d/Y', trim($endDate));
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();
        }

        $refund_list = RefundRequest::with([
            'customer' => function ($query) {
                $query->select('id', 'f_name', 'l_name', 'image');
            },
            'product',
            'orderDetails'
        ])
            ->with(['order' => function ($query) {
                $query->select('id', 'payment_method');
            }])
            ->whereHas('order', function ($query) use ($seller) {
                $query->where('seller_is', 'seller')->where('seller_id', $seller['id']);
            })
            ->when($request['search'], function ($query) use ($request) {
                $key = explode(' ', $request['search']);
                foreach ($key as $value) {
                    $query->where('order_id', 'like', "%{$value}%");
                }
            })
            ->when(isset($request['date_type']) && $request['date_type'] == 'this_year', function ($query) {
                return $query->whereYear('created_at', date('Y'));
            })
            ->when(isset($request['date_type']) && $request['date_type'] == 'this_month', function ($query) {
                return $query->whereMonth('created_at', date('m'))
                    ->whereYear('created_at', date('Y'));
            })
            ->when(isset($request['date_type']) && $request['date_type'] == 'this_week', function ($query) {
                return $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            })
            ->when(isset($request['date_type']) && $request['date_type'] == 'today', function ($query) {
                return $query->whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()]);
            })
            ->when(isset($request['date_type']) && $request['date_type'] == 'custom_date' && !empty($startDate) && !empty($endDate), function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->latest()->get();

        $refund_list?->map(function ($refund) {
            return $this->sanitizeRefundCustomer($refund);
        });

        return response()->json($refund_list);
    }

    public function getSingleItem(Request $request): JsonResponse
    {
        $seller = $request->seller;
        $refundList = RefundRequest::with([
            'customer' => function ($query) {
                $query->select('id', 'f_name', 'l_name', 'image');
            },
            'product',
            'orderDetails'
        ])
            ->with(['order' => function ($query) {
                $query->select('id', 'payment_method');
            }])
            ->whereHas('order', function ($query) use ($seller) {
                $query->where('seller_is', 'seller')->where('seller_id', $seller['id']);
            })
            ->where('id', $request['id'])
            ->first();

        if ($refundList) {
            $this->sanitizeRefundCustomer($refundList);
        }

        return response()->json($refundList);
    }

    public function refund_details(Request $request):JsonResponse
    {
        $seller = $request->seller;
        // [AI] Ownership Guard: Order details must belong to authenticated seller
        $order_details = OrderDetail::where(['id' => $request->order_details_id, 'seller_id' => $seller['id']])->first();
        if (!$order_details) {
            return response()->json(['message' => translate('unauthorized_access')], 403);
        }
        $refund_request = RefundRequest::with('refundStatus')->where('order_details_id', $request->order_details_id)->get();

        $order = Order::find($order_details->order_id);


        $data = [];
        $subtotal = ($order_details->price * $order_details->qty) - $order_details->discount + $order_details->tax;
        $refundDetailsSummery = OrderManager::getRefundDetailsForSingleOrderDetails(orderDetailsId: $order_details['id']);

        $data['data'] = $seller;
        $data['product_price'] = $order_details->price;
        $data['quntity'] = $order_details->qty;
        $data['product_total_discount'] = $order_details->discount;
        $data['product_total_tax'] = $order_details->tax;
        $data['subtotal'] = $subtotal;
        $data['coupon_discount'] = $refundDetailsSummery['coupon_discount'];
        $data['refund_amount'] = $refundDetailsSummery['total_refundable_amount'];
        $data['refund_request'] = $refund_request;
        $data['referral_discount'] = $refundDetailsSummery['referral_discount'];
        $data['deliveryman_details'] = DeliveryMan::find($order->delivery_man_id);

        return response()->json($data, 200);


    }

    public function refund_status_update(Request $request):JsonResponse
    {
        $seller = $request->seller;
        $validator = Validator::make($request->all(), [
            'refund_status' => 'required',
            'refund_request_id' => 'required',
            'note' => 'required_if:refund_status,rejected',
        ]);

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)]);
        }

        $refund = RefundRequest::whereHas('order', function ($query) use ($seller) {
            $query->where('seller_is', 'seller')->where('seller_id', $seller['id']);
        })->find($request->refund_request_id);

        if (!$refund) {
            return response()->json(['message' => translate('unauthorized_access')], 403);
        }

        $user = User::find($refund->customer_id);



        if ($refund->change_by == 'admin') {

            return response()->json(['message' => 'refunded status can not be changed!! Admin already changed the status : ' . $refund->status . '!!'], 403);
        }
        if ($refund->status != 'refunded') {
            $orderDetails = OrderDetail::find($refund->order_details_id);
            $refund_status = new RefundStatus;
            $refund_status->refund_request_id = $refund->id;
            $refund_status->change_by = 'seller';
            $refund_status->change_by_id = $seller['id'];
            $refund_status->status = $request->refund_status;

            if ($request->refund_status == 'pending') {
                $orderDetails->refund_request = 1;
            } elseif ($request->refund_status == 'approved') {
                $orderDetails->refund_request = 2;
                $refund->approved_note = $request->note;

                $refund_status->message = $request->note;
            } elseif ($request->refund_status == 'rejected') {
                $orderDetails->refund_request = 3;
                $refund->rejected_note = $request->note;

                $refund_status->message = $request->note;
            }

            $orderDetails->save();

            $refund->status = $request->refund_status;
            $refund->change_by = 'seller';
            $refund->save();
            $refund_status->save();

            $order = Order::find($refund->order_id);
            event(new RefundEvent(status: $request['refund_status'], order: $order, refund: $refund, orderDetails: $orderDetails));
            return response()->json(['message' => 'refund status updated successfully!'], 200);
        } else {
            return response()->json(['message' => 'refunded status can not be changed!!'], 403);
        }

    }

    /**
     * [AI] Zero-Trust Privacy Boundary: Redact customer contact and personal information from refund records.
     */
    private function sanitizeRefundCustomer($refund)
    {
        if ($refund && $refund->customer) {
            $name = trim(($refund->customer->f_name ?? '') . ' ' . ($refund->customer->l_name ?? ''));
            $len = strlen($name);
            $maskedName = ($len <= 2) ? $name : substr($name, 0, 2) . str_repeat('*', min(6, max(0, $len - 2)));

            $refund->customer->f_name = $maskedName;
            $refund->customer->l_name = '';
            $refund->customer->phone = '';
            $refund->customer->email = '';
            unset(
                $refund->customer->street_address,
                $refund->customer->country,
                $refund->customer->city,
                $refund->customer->zip,
                $refund->customer->house_no,
                $refund->customer->apartment_no,
                $refund->customer->cm_firebase_token,
                $refund->customer->wallet_balance,
                $refund->customer->loyalty_point,
                $refund->customer->payment_card_last_four,
                $refund->customer->payment_card_brand,
                $refund->customer->payment_card_fawry_token,
                $refund->customer->referral_code
            );
        }
        return $refund;
    }
}
