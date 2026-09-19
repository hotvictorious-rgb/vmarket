<?php

namespace App\Services;

use App\Models\AdminWallet;
use App\Models\CustomerCashbackLedger;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\SellerWallet;
use App\Models\Transaction;
use App\Utils\CustomerManager;
use App\Utils\OrderManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * [AI] Service VendorSettlementService
 *
 * Implements the VMarket Post-Receipt Lifecycle and Manual Settlement Engine (Commit 7).
 *
 * Invariants:
 * 1. Third-Party Vendor Boundary: Only applies to third-party vendor orders (seller_is === 'seller').
 *    VMarket-owned orders (seller_is === 'admin') have vendor_settlement_status = NULL and are never disbursed to SellerWallet.
 * 2. Terminal Refunded State: An order with vendor_settlement_status = 'refunded' can NEVER transition to 'eligible' or 'settled'.
 * 3. Legacy Sentinel Guard: Orders marked 'legacy_hold' are blocked from automatic eligibility and payout, requiring manual admin review.
 * 4. Authoritative 24-Hour Return Window: Settled only after received_at + 24 hours has elapsed with no open disputes.
 * 5. Delivery Fee Refund Precision: Non-refundable if actual delivery occurred (received_at != null); full refund only if undelivered (received_at == null).
 */
class VendorSettlementService
{
    /**
     * [AI] Evaluates if a third-party vendor order is eligible for manual settlement.
     * Transitions status from 'held' or 'disputed' to 'eligible' if all criteria are satisfied.
     */
    public function evaluateOrderSettlementEligibility(Order $order): bool
    {
        // Boundary: In-house VMarket orders do not undergo vendor settlement
        if ($order->seller_is !== 'seller') {
            return false;
        }

        // Terminal Invariant: Refunded orders can never be eligible or settled
        if ($order->vendor_settlement_status === 'refunded') {
            return false;
        }

        // Legacy Sentinel: legacy_hold requires explicit Super Admin review; blocked from automatic eligibility
        if ($order->vendor_settlement_status === 'legacy_hold') {
            return false;
        }

        // Already settled pass-through
        if ($order->vendor_settlement_status === 'settled') {
            return true;
        }

        // 24-hour return window after customer receipt must be complete
        $isWindowExpired = $order->received_at !== null
            && $order->refund_window_expires_at !== null
            && now()->greaterThan($order->refund_window_expires_at);

        if (!$isWindowExpired) {
            return false;
        }

        // Order must be delivered and have no open disputes
        if ($order->order_status !== 'delivered' || $order->hasUnresolvedRefund()) {
            return false;
        }

        // Promote to eligible if currently held or disputed
        if (in_array($order->vendor_settlement_status, ['held', 'disputed'], true)) {
            $order->vendor_settlement_status = 'eligible';
            $order->save();
        }

        return true;
    }

    /**
     * [AI] Resolves a customer refund dispute on an order.
     * If approved: cancels pending cashback; sets vendor_settlement_status = 'refunded' (seller orders only).
     * If rejected: restores vendor_settlement_status to 'eligible' (if window expired) or 'held'.
     */
    public function resolveRefundDispute(Order $order, string $decision): void
    {
        if ($decision === 'approved') {
            // Cancel pending cashback reward (vocabulary lock: double-L 'cancelled')
            CustomerCashbackLedger::where('order_id', $order->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'cancelled',
                    'updated_at' => now(),
                ]);

            if ($order->seller_is === 'seller') {
                $order->vendor_settlement_status = 'refunded';
            }
            // For seller_is === 'admin', vendor_settlement_status remains NULL

            // Delivery fee refund rule
            if ($order->received_at === null) {
                // Actual customer delivery never occurred: refund includes delivery fee
                $this->executeUndeliveredOrderRefund($order, 'Refund dispute approved for undelivered order');
            } else {
                // Actual delivery occurred: merchandise-only refund; delivery fee remains in AdminWallet
                $order->is_delivery_fee_refunded = 0;
            }

            $order->save();
        } elseif ($decision === 'rejected') {
            if ($order->seller_is === 'seller') {
                if ($order->isRefundWindowExpired()) {
                    $order->vendor_settlement_status = 'eligible';
                } else {
                    $order->vendor_settlement_status = 'held';
                }
                $order->save();
            }
            // For seller_is === 'admin', vendor_settlement_status remains NULL
        }
    }

    /**
     * [AI] Executes Super Admin manual settlement payout for an eligible third-party vendor order.
     */
    public function executeManualSettlement(
        int $orderId,
        int $adminId,
        string $paymentMethod,
        string $paymentReference,
        string $notes = ''
    ): array {
        $order = Order::find($orderId);
        if (!$order) {
            return ['status' => false, 'message' => 'Order not found.'];
        }

        // Boundary Guard: In-house orders do not undergo vendor settlement
        if ($order->seller_is !== 'seller') {
            return ['status' => false, 'message' => 'In-house VMarket orders do not undergo vendor settlement.'];
        }

        // Terminal Guard: Refunded orders cannot be disbursed
        if ($order->vendor_settlement_status === 'refunded') {
            throw new \RuntimeException("Cannot settle order #{$orderId}: order has been refunded.");
        }

        // Legacy Sentinel Guard
        if ($order->vendor_settlement_status === 'legacy_hold') {
            throw new \RuntimeException("Cannot settle order #{$orderId}: order is on legacy hold and requires manual review resolution.");
        }

        // Eligibility Check
        if ($order->vendor_settlement_status !== 'eligible') {
            return [
                'status' => false,
                'message' => "Order #{$orderId} is not eligible for settlement (status: {$order->vendor_settlement_status}).",
            ];
        }

        DB::transaction(function () use ($order, $adminId, $paymentReference, $notes) {
            OrderManager::disburseSettledVendorOrder($order, $paymentReference, $adminId);

            // Create auditable settlement transaction
            $transaction = new Transaction();
            $transaction->order_id = $order->id;
            $transaction->payment_for = 'vendor_settlement';
            $transaction->payer_id = $adminId;
            $transaction->payment_receiver_id = $order->seller_id;
            $transaction->paid_by = 'admin';
            $transaction->paid_to = 'seller';
            $transaction->payment_status = 'disburse';
            $transaction->amount = $order->order_amount;
            $transaction->transaction_type = 'expense';
            $transaction->save();
        });

        return [
            'status' => true,
            'message' => 'Vendor settlement executed successfully.',
            'order_id' => $orderId,
            'reference' => $paymentReference,
        ];
    }

    /**
     * [AI] Full refund execution for orders where actual customer delivery NEVER occurred (received_at === null).
     * Reverses delivery fee from AdminWallet and sets is_delivery_fee_refunded = 1.
     */
    public function executeUndeliveredOrderRefund(Order $order, string $reason = ''): array
    {
        if ($order->received_at !== null) {
            return [
                'status' => false,
                'message' => 'Cannot execute undelivered order refund on an order with confirmed customer receipt.',
            ];
        }

        // Idempotency guard
        if ($order->is_delivery_fee_refunded) {
            return [
                'status' => false,
                'message' => 'Delivery fee has already been refunded for this order.',
            ];
        }

        $fullAmount = (float)($order->order_amount ?? 0.00);
        $shippingCost = (float)($order->shipping_cost ?? 0.00);

        DB::transaction(function () use ($order, $shippingCost, $fullAmount) {
            // Reverse delivery fee from AdminWallet if shipping cost > 0
            if ($shippingCost > 0) {
                $adminWallet = AdminWallet::where('admin_id', 1)->lockForUpdate()->first();
                if ($adminWallet) {
                    $shippingStr = bcadd((string)$shippingCost, '0', 2);
                    $adminWallet->delivery_charge_earned = max(0.00, (float)bcsub((string)$adminWallet->delivery_charge_earned, $shippingStr, 2));
                    $adminWallet->save();
                }

                $tx = new Transaction();
                $tx->order_id = $order->id;
                $tx->payment_for = 'delivery_fee_refund';
                $tx->payer_id = 1;
                $tx->payment_receiver_id = $order->customer_id ?? 0;
                $tx->paid_by = 'admin';
                $tx->paid_to = 'customer';
                $tx->payment_status = 'disburse';
                $tx->amount = $shippingCost;
                $tx->transaction_type = 'refund';
                $tx->save();
            }

            // Record customer refund transaction (Customer cash wallet is decommissioned in Victorious MARKET)
            if ($order->customer_id && $fullAmount > 0) {
                $custTx = new Transaction();
                $custTx->order_id = $order->id;
                $custTx->payment_for = 'order_refund';
                $custTx->payer_id = 1;
                $custTx->payment_receiver_id = $order->customer_id;
                $custTx->paid_by = 'admin';
                $custTx->paid_to = 'customer';
                $custTx->payment_status = 'disburse';
                $custTx->amount = $fullAmount;
                $custTx->transaction_type = 'refund';
                $custTx->save();
            }

            $order->is_delivery_fee_refunded = 1;

            // Safeguard 2: Only mark vendor_settlement_status = 'refunded' for third-party sellers
            if ($order->seller_is === 'seller') {
                $order->vendor_settlement_status = 'refunded';
            }
            // For seller_is === 'admin', vendor_settlement_status remains NULL

            $order->save();
        });

        return [
            'status' => true,
            'message' => 'Undelivered order full refund processed successfully.',
            'refunded_amount' => $fullAmount,
            'is_delivery_fee_refunded' => 1,
        ];
    }

    /**
     * [AI] Resolves a legacy hold on an order based on verified administrative evidence.
     * Decisions:
     * - 'release_to_vendor': Releases hold, making order 'eligible' for manual settlement.
     * - 'return_to_platform': Closes hold as 'refunded', retaining funds in platform escrow.
     * Emits a neutral audit log with [AUDIT] tag.
     */
    public function resolveLegacyHold(
        Order $order,
        string $decision,
        int $adminId,
        string $reference,
        string $notes = ''
    ): array {
        if ($order->vendor_settlement_status !== 'legacy_hold') {
            return [
                'status' => false,
                'message' => "Order #{$order->id} is not on legacy hold (current status: {$order->vendor_settlement_status}).",
            ];
        }

        if (!in_array($decision, ['release_to_vendor', 'return_to_platform'], true)) {
            throw new \InvalidArgumentException("Invalid resolution decision '{$decision}'. Must be 'release_to_vendor' or 'return_to_platform'.");
        }

        DB::transaction(function () use ($order, $decision, $adminId, $reference, $notes) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            if ($decision === 'release_to_vendor') {
                $lockedOrder->vendor_settlement_status = 'eligible';
                $lockedOrder->save();

                Log::info("[AUDIT] Super Admin resolved legacy hold on order #{$lockedOrder->id} -> 'release_to_vendor'", [
                    'order_id' => $lockedOrder->id,
                    'admin_id' => $adminId,
                    'reference' => $reference,
                    'notes' => $notes,
                    'new_status' => 'eligible',
                    'timestamp' => now()->toIso8601String(),
                ]);
            } elseif ($decision === 'return_to_platform') {
                $lockedOrder->vendor_settlement_status = 'refunded';
                $lockedOrder->save();

                Log::info("[AUDIT] Super Admin resolved legacy hold on order #{$lockedOrder->id} -> 'return_to_platform'", [
                    'order_id' => $lockedOrder->id,
                    'admin_id' => $adminId,
                    'reference' => $reference,
                    'notes' => $notes,
                    'new_status' => 'refunded',
                    'timestamp' => now()->toIso8601String(),
                ]);
            }
        });

        return [
            'status' => true,
            'message' => "Legacy hold for order #{$order->id} resolved successfully ({$decision}).",
            'decision' => $decision,
            'order_id' => $order->id,
        ];
    }
}
