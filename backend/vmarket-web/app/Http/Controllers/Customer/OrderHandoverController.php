<?php

namespace App\Http\Controllers\Customer;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\OrderStatusHistoryRepositoryInterface;
use App\Events\OrderStatusEvent;
use App\Http\Controllers\Controller;
use App\Models\CustomerCashbackLedger;
use App\Models\DeliverymanWallet;
use App\Models\DeliveryManTransaction;
use App\Models\LogisticsCompany;
use App\Models\LogisticsCompanyTransaction;
use App\Models\LogisticsCompanyWallet;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderDeliveryVerification;
use App\Models\OrderHandoverLog;
use App\Models\Transaction;
use App\Services\OrderStatusHistoryService;
use App\Traits\PushNotificationTrait;
use App\Traits\StorageTrait;
use App\Utils\ImageManager;
use App\Utils\OrderManager;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * [AI] Class OrderHandoverController
 * 
 * Enforces the Universal Custody Handshake Standard:
 * "THE RECEIVER MUST ALWAYS BE THE ONE ENTERING THE CODES (WITH A PICTURE FIRST)"
 * 
 * Flow 1: In-Shop Pickup: Merchant reveals code -> Customer snaps photo & enters code.
 * Flow 2: Doorstep Delivery: Rider reveals code -> Customer snaps photo & enters code.
 */
class OrderHandoverController extends Controller
{
    use PushNotificationTrait, StorageTrait;

    public function __construct(
        protected OrderRepositoryInterface $orderRepo,
        protected OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepo,
        protected OrderStatusHistoryService $orderStatusHistoryService,
    ) {
    }

    /**
     * Customer confirms In-Shop Pickup by snapping a photo of the goods on the counter
     * and entering the 6-digit Secret Pickup Code shown by the merchant.
     */
    public function confirmInShopPickup(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'pickup_code' => 'required|string|size:6',
            'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $customerId = auth('customer')->id() ?? (int) ($request->user('customer')?->id ?? 0);
        if (!$customerId && auth('api')->check()) {
            $customerId = (int) auth('api')->id();
        }

        if (!$customerId) {
            $unauthMsg = translate('Unauthenticated customer. Please sign in to verify pickup.');
            return $request->expectsJson()
                ? response()->json(['status' => false, 'message' => $unauthMsg], 401)
                : back()->withErrors(['message' => $unauthMsg]);
        }

        DB::beginTransaction();
        try {
            $order = Order::with(['details', 'seller.shop'])
                ->where('id', $request->order_id)
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                DB::rollBack();
                $notFoundMsg = translate('Order not found or unauthorized.');
                return $request->expectsJson()
                    ? response()->json(['status' => false, 'message' => $notFoundMsg], 404)
                    : back()->withErrors(['message' => $notFoundMsg]);
            }

            $isPickup = in_array($order->order_type, ['pickup', 'in_house_pickup'], true)
                || in_array($order->delivery_type, ['self_pickup'], true);

            if (!$isPickup) {
                DB::rollBack();
                $typeMsg = translate('This order is not designated for in-store pickup.');
                return $request->expectsJson()
                    ? response()->json(['status' => false, 'message' => $typeMsg], 422)
                    : back()->withErrors(['message' => $typeMsg]);
            }

            if (in_array($order->order_status, ['delivered', 'canceled', 'returned', 'failed'], true)) {
                DB::rollBack();
                $doneMsg = translate('Order is already completed or closed.');
                return $request->expectsJson()
                    ? response()->json(['status' => false, 'message' => $doneMsg], 400)
                    : back()->withErrors(['message' => $doneMsg]);
            }

            if ($order->payment_status !== 'paid') {
                DB::rollBack();
                $unpaidMsg = translate('Order is unpaid. You must complete payment online before physical handover.');
                return $request->expectsJson()
                    ? response()->json(['status' => false, 'message' => $unpaidMsg], 403)
                    : back()->withErrors(['message' => $unpaidMsg]);
            }

            // Brute-force rate limiting: 5 attempts per order locked for 15 mins
            $lockKey = "customer_pickup_attempts_{$order->id}";
            $attempts = (int) Cache::get($lockKey, 0);
            if ($attempts >= 5) {
                DB::rollBack();
                $lockMsg = translate('Verification locked due to 5 failed attempts. Please try again in 15 minutes.');
                return $request->expectsJson()
                    ? response()->json(['status' => false, 'message' => $lockMsg], 429)
                    : back()->withErrors(['message' => $lockMsg]);
            }

            // Expected secret is the pickup verification code generated by the system for the merchant
            $expectedCode = (string) ($order->pickup_verification_code ?: $order->verification_code);

            if (!hash_equals($expectedCode, (string) $request->pickup_code)) {
                DB::rollBack();
                Cache::put($lockKey, $attempts + 1, now()->addMinutes(15));
                $remaining = 5 - ($attempts + 1);
                $failedMsg = translate('Invalid Secret Pickup Code. Attempts remaining: ') . $remaining;
                return $request->expectsJson()
                    ? response()->json(['status' => false, 'message' => $failedMsg], 422)
                    : back()->withErrors(['pickup_code' => $failedMsg]);
            }

            // Clear attempt lock on success
            Cache::forget($lockKey);

            // Upload Proof Picture with dynamic downscaling & WebP compression
            $imageName = ImageManager::uploadOptimizedVerificationImage('delivery-man/verification-image/', $request->file('image'));

            $now = now();
            $receivedAt = $order->received_at ?? $now;
            $expiresAt = (clone $receivedAt)->addHours(24);

            $order->order_status = 'delivered';
            $order->delivered_by = 'customer';
            $order->handed_over_at = $now;
            $order->handed_over_by_name = 'Customer In-Store Pickup';
            $order->received_at = $receivedAt;
            $order->refund_window_expires_at = $expiresAt;
            $order->save();

            OrderDetail::where('order_id', $order->id)->update([
                'delivery_status' => 'delivered',
                'payment_status' => 'paid',
            ]);

            // Save Proof of Handover Verification Record
            $verificationRecord = OrderDeliveryVerification::create([
                'order_id' => $order->id,
                'image' => $imageName,
                'handover_type' => 'customer_inshop_pickup',
                'verified_by_type' => 'customer',
                'verified_by_id' => $customerId,
                'pickup_otp_used' => $request->pickup_code,
            ]);

            // Save Audit Log
            OrderHandoverLog::create([
                'order_id' => $order->id,
                'seller_id' => $order->seller_id ?? 0,
                'branch_id' => $order->handover_branch_id ?? ($order->seller?->shop?->id ?? 0),
                'handed_over_by_id' => $customerId,
                'handed_over_by_name' => 'Customer Counter Handover',
                'delivery_man_id' => null,
                'delivery_man_name' => 'Self-Pickup (Verified with Photo Proof)',
                'pickup_otp_used' => $request->pickup_code,
                'handed_over_at' => $now,
                'notes' => 'Customer confirmed counter pickup with proof photo and merchant code verification.',
            ]);

            // Status History
            $historyData = $this->orderStatusHistoryService->getOrderHistoryData(
                orderId: $order->id,
                userId: $customerId,
                userType: 'customer',
                status: 'delivered'
            );
            $this->orderStatusHistoryRepo->add($historyData);
            OrderManager::removeOldStatusHistory(orderId: $order->id, orderStatus: 'delivered');

            // Settle financial positions
            OrderManager::getWalletManageOnOrderStatusChange($order, 'delivered');

            // Credit 5% Customer Cashback Reward Ledger
            $ledger = CustomerCashbackLedger::creditRewardForOrder($order);
            if ($ledger && !empty($ledger->cashback_amount)) {
                $this->sendCashbackEarnedNotification($order, (string)$ledger->cashback_amount);
            }

            $this->sendOrderNotification('pickup_completed_message', 'customer', $order);
            event(new OrderStatusEvent(key: 'delivered', type: 'customer', order: $order));

            DB::commit();

            $successMsg = translate('In-shop pickup confirmed successfully! Your items are now claimed.');
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => $successMsg,
                    'order_status' => 'delivered',
                    'verification_image' => $verificationRecord->image_full_url['path'] ?? null,
                ], 200);
            }

            Toastr::success($successMsg);
            ToastMagic::success($successMsg);
            return back();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('In-shop pickup verification error: ' . $e->getMessage(), ['order_id' => $request->order_id]);
            $errMsg = translate('Failed to finalize pickup: ') . $e->getMessage();
            Toastr::error($errMsg);
            ToastMagic::error($errMsg);
            return $request->expectsJson()
                ? response()->json(['status' => false, 'message' => $errMsg], 500)
                : back()->withErrors(['message' => $errMsg]);
        }
    }

    /**
     * Customer confirms Doorstep Delivery by snapping a picture of the received parcel
     * and entering the 6-digit Delivery Code shown on the delivery rider's phone.
     */
    public function confirmDoorstepDelivery(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'delivery_code' => 'required|string|size:6',
            'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $customerId = auth('customer')->id() ?? (int) ($request->user('customer')?->id ?? 0);
        if (!$customerId && auth('api')->check()) {
            $customerId = (int) auth('api')->id();
        }

        if (!$customerId) {
            $unauthMsg = translate('Unauthenticated customer. Please sign in to verify delivery.');
            return $request->expectsJson()
                ? response()->json(['status' => false, 'message' => $unauthMsg], 401)
                : back()->withErrors(['message' => $unauthMsg]);
        }

        DB::beginTransaction();
        try {
            $order = Order::with(['deliveryMan', 'seller.shop'])
                ->where('id', $request->order_id)
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                DB::rollBack();
                $notFoundMsg = translate('Order not found or unauthorized.');
                return $request->expectsJson()
                    ? response()->json(['status' => false, 'message' => $notFoundMsg], 404)
                    : back()->withErrors(['message' => $notFoundMsg]);
            }

            if ($order->order_status !== 'out_for_delivery') {
                DB::rollBack();
                $statusMsg = translate("Cannot confirm delivery: Order status is currently '{$order->order_status}', expected 'out_for_delivery'.");
                return $request->expectsJson()
                    ? response()->json(['status' => false, 'message' => $statusMsg], 422)
                    : back()->withErrors(['message' => $statusMsg]);
            }

            // Brute-force rate limiting: 5 attempts per order locked for 15 mins
            $lockKey = "customer_delivery_attempts_{$order->id}";
            $attempts = (int) Cache::get($lockKey, 0);
            if ($attempts >= 5) {
                DB::rollBack();
                $lockMsg = translate('Delivery verification locked due to 5 failed attempts. Please try again in 15 minutes.');
                return $request->expectsJson()
                    ? response()->json(['status' => false, 'message' => $lockMsg], 429)
                    : back()->withErrors(['message' => $lockMsg]);
            }

            // Expected code is the verification_code displayed by the rider
            $expectedCode = (string) $order->verification_code;

            if (!hash_equals($expectedCode, (string) $request->delivery_code)) {
                DB::rollBack();
                Cache::put($lockKey, $attempts + 1, now()->addMinutes(15));
                $remaining = 5 - ($attempts + 1);
                $failedMsg = translate('Invalid Delivery Code. Attempts remaining: ') . $remaining;
                return $request->expectsJson()
                    ? response()->json(['status' => false, 'message' => $failedMsg], 422)
                    : back()->withErrors(['delivery_code' => $failedMsg]);
            }

            // Clear attempt lock on success
            Cache::forget($lockKey);

            // Upload Proof Picture with dynamic downscaling & WebP compression
            $imageName = ImageManager::uploadOptimizedVerificationImage('delivery-man/verification-image/', $request->file('image'));

            $now = now();
            $receivedAt = $order->received_at ?? $now;
            $expiresAt = (clone $receivedAt)->addHours(24);

            $order->order_status = 'delivered';
            $order->verification_status = 1;
            $order->received_at = $receivedAt;
            $order->refund_window_expires_at = $expiresAt;
            $order->save();

            OrderDetail::where('order_id', $order->id)->update([
                'delivery_status' => 'delivered',
            ]);

            // Save Proof of Handover Verification Record
            $verificationRecord = OrderDeliveryVerification::create([
                'order_id' => $order->id,
                'image' => $imageName,
                'handover_type' => 'customer_doorstep',
                'verified_by_type' => 'customer',
                'verified_by_id' => $customerId,
                'pickup_otp_used' => $request->delivery_code,
            ]);

            // Status History
            $historyData = $this->orderStatusHistoryService->getOrderHistoryData(
                orderId: $order->id,
                userId: $customerId,
                userType: 'customer',
                status: 'delivered'
            );
            $this->orderStatusHistoryRepo->add($historyData);
            OrderManager::removeOldStatusHistory(orderId: $order->id, orderStatus: 'delivered');

            // Delivery Rider / Logistics Company Settlement (Row-Locked & Idempotent via BCMath):
            $deliveryMan = $order->deliveryMan;
            if ($deliveryMan) {
                $grossDeliveryFee = bcadd((string)($order->shipping_cost > 0 ? $order->shipping_cost : ($order->deliveryman_charge ?? '0.00')), '0', 2);

                $company = null;
                $effectiveCompanyId = $deliveryMan->logistics_company_id ?? $order->logistics_company_id;
                if (!empty($effectiveCompanyId)) {
                    $company = LogisticsCompany::find($effectiveCompanyId);
                }

                $commissionRate = $company
                    ? (string)$company->getEffectiveCommissionRate()
                    : (string)(getWebConfig(name: 'delivery_commission_percentage') ?? '15.00');

                if (bccomp((string)($order->delivery_commission_amount ?? 0), '0.00', 2) > 0) {
                    $commissionAmount = bcadd((string)$order->delivery_commission_amount, '0', 2);
                } else {
                    $commissionAmount = bcdiv(bcmul($grossDeliveryFee, $commissionRate, 4), '100', 2);
                }

                $netPartnerAmount = bcsub($grossDeliveryFee, $commissionAmount, 2);
                if (bccomp($netPartnerAmount, '0.00', 2) < 0) {
                    $netPartnerAmount = '0.00';
                }

                // Mathematical invariant: gross = netPartner + commission
                $calcSum = bcadd($commissionAmount, $netPartnerAmount, 2);
                if (bccomp($grossDeliveryFee, $calcSum, 2) !== 0) {
                    $commissionAmount = bcsub($grossDeliveryFee, $netPartnerAmount, 2);
                }

                if (!empty($deliveryMan->logistics_company_id)) {
                    $companyId = (int) $deliveryMan->logistics_company_id;

                    // Pessimistic Row Lock on Company Wallet
                    $companyWallet = LogisticsCompanyWallet::where('logistics_company_id', $companyId)
                        ->lockForUpdate()
                        ->first();

                    if (!$companyWallet) {
                        $companyWallet = LogisticsCompanyWallet::create([
                            'logistics_company_id' => $companyId,
                            'total_earned' => 0.00,
                            'withdrawn' => 0.00,
                            'pending_withdraw' => 0.00,
                            'current_balance' => 0.00,
                        ]);
                        $companyWallet = LogisticsCompanyWallet::where('id', $companyWallet->id)->lockForUpdate()->first();
                    }

                    // Idempotent delivery credit check per order
                    $alreadyCreditedCompany = LogisticsCompanyTransaction::where('order_id', $order->id)
                        ->where('logistics_company_id', $companyId)
                        ->where('transaction_type', 'delivery_credit')
                        ->exists();

                    if (!$alreadyCreditedCompany && bccomp($netPartnerAmount, '0.00', 2) > 0) {
                        $balBefore = bcadd((string)$companyWallet->current_balance, '0', 2);
                        $balAfter = bcadd($balBefore, $netPartnerAmount, 2);
                        $companyWallet->current_balance = (float)$balAfter;
                        $companyWallet->total_earned = (float)bcadd((string)$companyWallet->total_earned, $netPartnerAmount, 2);
                        $companyWallet->save();

                        LogisticsCompanyTransaction::create([
                            'logistics_company_id' => $companyId,
                            'order_id' => $order->id,
                            'delivery_man_id' => $deliveryMan->id,
                            'gross_delivery_fee' => (float)$grossDeliveryFee,
                            'admin_commission_rate' => (float)$commissionRate,
                            'admin_commission_amount' => (float)$commissionAmount,
                            'net_partner_amount' => (float)$netPartnerAmount,
                            'transaction_type' => 'delivery_credit',
                            'balance_before' => (float)$balBefore,
                            'balance_after' => (float)$balAfter,
                            'transaction_note' => "Order #{$order->id} confirmed by customer with photo proof. Gross: ₦{$grossDeliveryFee}, Admin Fee ({$commissionRate}%): ₦{$commissionAmount}, Net Payout: ₦{$netPartnerAmount}",
                        ]);
                    }
                } else {
                    $charge = (!empty($order->deliveryman_charge) && bccomp((string)$order->deliveryman_charge, '0.00', 2) > 0)
                        ? bcadd((string)$order->deliveryman_charge, '0', 2)
                        : $netPartnerAmount;

                    // Pessimistic Row Lock on Rider Wallet
                    $deliveryManWallet = DeliverymanWallet::where('delivery_man_id', $deliveryMan->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$deliveryManWallet) {
                        $deliveryManWallet = DeliverymanWallet::create([
                            'delivery_man_id' => $deliveryMan->id,
                            'current_balance' => 0.00,
                            'cash_in_hand' => 0,
                            'pending_withdraw' => 0,
                            'total_withdraw' => 0,
                        ]);
                        $deliveryManWallet = DeliverymanWallet::where('id', $deliveryManWallet->id)->lockForUpdate()->first();
                    }

                    $riderTxId = "order-{$order->id}-charge";
                    $alreadyCreditedRider = DeliveryManTransaction::where('delivery_man_id', $deliveryMan->id)
                        ->where('transaction_id', $riderTxId)
                        ->exists();

                    if (!$alreadyCreditedRider && bccomp($charge, '0.00', 2) > 0) {
                        $deliveryManWallet->current_balance = (float)bcadd((string)$deliveryManWallet->current_balance, $charge, 2);
                        $deliveryManWallet->save();

                        DeliveryManTransaction::create([
                            'delivery_man_id' => $deliveryMan->id,
                            'user_id' => 0,
                            'user_type' => 'admin',
                            'credit' => (float)$charge,
                            'transaction_id' => $riderTxId,
                            'transaction_type' => 'deliveryman_charge',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Record platform delivery commission transaction idempotently
                if (bccomp($commissionAmount, '0.00', 2) > 0) {
                    $alreadyCommissioned = Transaction::where('order_id', $order->id)
                        ->where('transaction_type', 'delivery_commission')
                        ->exists();

                    if (!$alreadyCommissioned) {
                        Transaction::create([
                            'order_id' => $order->id,
                            'payment_for' => 'delivery_commission',
                            'payer_id' => $deliveryMan->id,
                            'payment_receiver_id' => 1,
                            'paid_by' => !empty($deliveryMan->logistics_company_id) ? 'logistics_company' : 'delivery_man',
                            'paid_to' => 'admin',
                            'payment_status' => 'received',
                            'amount' => (float)$commissionAmount,
                            'transaction_type' => 'delivery_commission',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // Settle merchant wallet holdings if applicable
            if ($order->seller_id !== null) {
                OrderManager::getWalletManageOnOrderStatusChange($order, 'customer');
            }

            $this->sendOrderNotification('order_delivered_message', 'customer', $order);
            event(new OrderStatusEvent(key: 'delivered', type: 'customer', order: $order));

            DB::commit();

            $successMsg = translate('Doorstep delivery confirmed successfully! Thank you for shopping on Victorious MARKET.');
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => $successMsg,
                    'order_status' => 'delivered',
                    'verification_image' => $verificationRecord->image_full_url['path'] ?? null,
                ], 200);
            }

            Toastr::success($successMsg);
            ToastMagic::success($successMsg);
            return back();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Doorstep delivery verification error: ' . $e->getMessage(), ['order_id' => $order->id]);
            $errMsg = translate('Failed to finalize delivery: ') . $e->getMessage();
            Toastr::error($errMsg);
            ToastMagic::error($errMsg);
            return $request->expectsJson()
                ? response()->json(['status' => false, 'message' => $errMsg], 500)
                : back()->withErrors(['message' => $errMsg]);
        }
    }

    /**
     * Real-time polling endpoint for Givers (Merchants and Riders)
     * to detect the exact instant the receiver snaps the photo and confirms.
     */
    public function checkHandoverStatus(Request $request, int|string $order_id): JsonResponse
    {
        $order = Order::with(['deliveryMan'])
            ->where('id', $order_id)
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found'], 404);
        }

        $latestVerification = OrderDeliveryVerification::where('order_id', $order->id)->latest()->first();

        return response()->json([
            'status' => true,
            'order_id' => $order->id,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'is_delivered' => ($order->order_status === 'delivered'),
            'is_out_for_delivery' => ($order->order_status === 'out_for_delivery'),
            'verified_at' => $order->received_at ? $order->received_at->format('d M Y, h:i A') : null,
            'verification_image' => $latestVerification?->image_full_url['path'] ?? null,
            'handover_type' => $latestVerification?->handover_type ?? null,
        ]);
    }
}
