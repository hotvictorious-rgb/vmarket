<?php

namespace App\Http\Controllers\Vendor\Order;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\OrderStatusHistoryRepositoryInterface;
use App\Events\OrderStatusEvent;
use App\Http\Controllers\Controller;
use App\Models\CustomerCashbackLedger;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderHandoverLog;
use App\Services\OrderStatusHistoryService;
use App\Utils\OrderManager;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * [AI] Class InShopHandoverController
 * Implements the Staff-Attributed Handshake Protocol for:
 * 1. Customer In-Shop Pickup: Customer inspects goods, pays online (Paystack), and provides 6-digit Pickup Code.
 * 2. Motorized Delivery Rider In-Shop Parcel Custody Transfer (Doorstep Delivery).
 */
class InShopHandoverController extends Controller
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepo,
        protected OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepo,
        protected OrderStatusHistoryService $orderStatusHistoryService,
    ) {
    }

    public function verifyPickupOtp(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'order_id' => 'required|integer',
            'pickup_otp' => 'required|string|size:6',
        ]);

        $seller = auth('seller')->user() ?? $request->seller;
        $sellerId = auth('seller')->id() ?? (is_array($request->seller) ? ($request->seller['id'] ?? null) : ($request->seller->id ?? null));

        if (!$sellerId) {
            return response()->json(['status' => false, 'message' => translate('unauthorized_access')], 403);
        }

        $order = Order::with(['shipping', 'deliveryMan'])
            ->where('id', $request->order_id)
            ->where('seller_id', $sellerId)
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => translate('order_not_found')], 404);
        }

        // [AI] Guard: Order already completed or closed cannot be replayed
        if (in_array($order->order_status, ['delivered', 'canceled', 'returned', 'failed'])) {
            $message = translate('Order_is_already_completed_or_closed.');
            if ($request->ajax() || $request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => false, 'message' => $message], 400);
            }
            ToastMagic::error($message);
            return back();
        }

        $isCustomerPickup = ($order->order_type === 'pickup' || $order->delivery_type === 'self_pickup');

        // [AI] Customer Pickup Security Invariant: Order MUST be paid before physical release
        if ($isCustomerPickup && $order->payment_status !== 'paid') {
            $unpaidMessage = translate('Cannot release item: Order is unpaid. Customer must complete payment online through Victorious MARKET before handover.');
            if ($request->ajax() || $request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => false, 'message' => $unpaidMessage], 403);
            }
            ToastMagic::error($unpaidMessage);
            return back();
        }

        // [AI] Doorstep Delivery Invariant: Requires assigned delivery rider
        if (!$isCustomerPickup && empty($order->delivery_man_id)) {
            $message = translate('Delivery orders require an assigned Victorious Delivery rider before in-shop parcel dispatch.');
            if ($request->ajax() || $request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => false, 'message' => $message], 422);
            }
            ToastMagic::error($message);
            return back();
        }

        $expectedCode = (string)($order->pickup_verification_code ?: $order->verification_code);
        if (!$expectedCode) {
            $message = translate('No_pickup_verification_code_assigned_to_this_order');
            if ($request->ajax() || $request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => false, 'message' => $message], 422);
            }
            ToastMagic::error($message);
            return back();
        }

        // [AI] Brute-Force Guard: Max 5 failed attempts per order locked for 15 minutes
        $lockKey = "pickup_attempts_{$order->id}";
        $attempts = (int) Cache::get($lockKey, 0);
        if ($attempts >= 5) {
            $lockMessage = translate('Pickup_verification_locked_due_to_5_failed_attempts._Please_try_again_in_15_minutes.');
            if ($request->ajax() || $request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => false, 'message' => $lockMessage], 429);
            }
            ToastMagic::error($lockMessage);
            return back();
        }

        // [AI] Constant-time OTP comparison to prevent timing attacks
        $validOtp = hash_equals($expectedCode, (string)$request->pickup_otp)
            || ($order->verification_code && hash_equals((string)$order->verification_code, (string)$request->pickup_otp));

        if (!$validOtp) {
            Cache::put($lockKey, $attempts + 1, now()->addMinutes(15));
            $remaining = 5 - ($attempts + 1);
            $failedMessage = translate('Invalid_6-digit_Secret_Pickup_OTP._Attempts_remaining: ') . $remaining;
            if ($request->ajax() || $request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => $failedMessage,
                ], 422);
            }
            ToastMagic::error($failedMessage);
            return back();
        }

        // Clear attempt lock on success
        Cache::forget($lockKey);

        $staffName = is_object($seller)
            ? ($seller->name ?? ($seller->f_name . ' ' . $seller->l_name . ' (Owner)'))
            : (($seller['name'] ?? ($seller['f_name'] . ' ' . $seller['l_name'] . ' (Owner)')) ?? 'Merchant');
        $branchId = is_object($seller)
            ? ($seller->shop->id ?? null)
            : ($seller['shop']['id'] ?? null);

        DB::transaction(function () use ($order, $sellerId, $staffName, $branchId, $request, $isCustomerPickup) {
            if ($isCustomerPickup) {
                // [AI] Customer Pickup Complete Handover:
                // Order transitions directly to 'delivered'.
                // Merchant 90% settlement and 5% Customer Cashback are settled.
                $order->order_status = 'delivered';
                $order->payment_status = 'paid';
                $order->handed_over_by_id = $sellerId;
                $order->handed_over_by_name = $staffName;
                $order->handed_over_at = now();
                $order->handover_branch_id = $branchId;
                $order->save();

                OrderDetail::where('order_id', $order->id)->update([
                    'delivery_status' => 'delivered',
                    'payment_status' => 'paid',
                ]);

                OrderHandoverLog::create([
                    'order_id' => $order->id,
                    'seller_id' => $sellerId,
                    'branch_id' => $branchId,
                    'handed_over_by_id' => $sellerId,
                    'handed_over_by_name' => $staffName,
                    'delivery_man_id' => null,
                    'delivery_man_name' => 'Customer In-Person Pickup',
                    'pickup_otp_used' => $request->pickup_otp,
                    'handed_over_at' => now(),
                    'notes' => $request->notes ?? 'In-shop customer pickup verified and released via 6-digit cryptographic code',
                ]);

                $historyData = $this->orderStatusHistoryService->getOrderHistoryData(
                    orderId: $order->id,
                    userId: $sellerId,
                    userType: 'seller',
                    status: 'delivered'
                );
                $this->orderStatusHistoryRepo->add($historyData);
                OrderManager::removeOldStatusHistory(orderId: $order->id, orderStatus: 'delivered');

                // Settle merchant wallet (90%) and admin commission (10%)
                OrderManager::getWalletManageOnOrderStatusChange($order, 'delivered');

                // Credit 5% Customer Cashback Reward Ledger
                CustomerCashbackLedger::creditRewardForOrder($order);

                event(new OrderStatusEvent(key: 'delivered', type: 'customer', order: $order));
            } else {
                // [AI] Vendor -> Rider Delivery Handshake:
                // Order ready -> rider arrives with pickup OTP -> order becomes OUT_FOR_DELIVERY.
                // Final settlement is deferred until customer delivery OTP is verified at doorstep.
                $order->order_status = 'out_for_delivery';
                $order->handed_over_by_id = $sellerId;
                $order->handed_over_by_name = $staffName;
                $order->handed_over_at = now();
                $order->handover_branch_id = $branchId;
                $order->save();

                OrderDetail::where('order_id', $order->id)->update([
                    'delivery_status' => 'out_for_delivery',
                ]);

                OrderHandoverLog::create([
                    'order_id' => $order->id,
                    'seller_id' => $sellerId,
                    'branch_id' => $branchId,
                    'handed_over_by_id' => $sellerId,
                    'handed_over_by_name' => $staffName,
                    'delivery_man_id' => $order->delivery_man_id,
                    'delivery_man_name' => $order->deliveryMan ? ($order->deliveryMan->f_name . ' ' . $order->deliveryMan->l_name) : 'Assigned Rider',
                    'pickup_otp_used' => $request->pickup_otp,
                    'handed_over_at' => now(),
                    'notes' => $request->notes ?? 'In-shop custody transferred to rider via staff-attributed OTP handshake',
                ]);

                $historyData = $this->orderStatusHistoryService->getOrderHistoryData(
                    orderId: $order->id,
                    userId: $sellerId,
                    userType: 'seller',
                    status: 'out_for_delivery'
                );
                $this->orderStatusHistoryRepo->add($historyData);
                OrderManager::removeOldStatusHistory(orderId: $order->id, orderStatus: 'out_for_delivery');

                event(new OrderStatusEvent(key: 'out_for_delivery', type: 'customer', order: $order));
            }
        });

        $finalStatus = $isCustomerPickup ? 'delivered' : 'out_for_delivery';
        $actionLabel = $isCustomerPickup
            ? translate('Customer_pickup_completed_successfully._Item_released.')
            : translate('Custody_transferred_to_rider_successfully!');
        $successMessage = $actionLabel . ' ' . translate('Staff_') . $staffName . ' ' . translate('recorded_on_Audit_Log.');

        if ($request->ajax() || $request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => true,
                'message' => $successMessage,
                'order_status' => $finalStatus,
                'handed_over_by' => $staffName,
                'handed_over_at' => now()->format('d M Y, h:i A'),
            ]);
        }

        ToastMagic::success($successMessage);
        return back();
    }
}
