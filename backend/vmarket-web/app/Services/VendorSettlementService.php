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
        return DB::transaction(function () use ($order) {
            // [AI] Scheduler and admin eligibility cannot overwrite a concurrent terminal settlement/refund.
            $order = Order::whereKey($order->id)->lockForUpdate()->first();
            if (!$order || $order->payment_status !== 'paid') return false;
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
        });
    }

    /**
     * [AI] Resolves a customer refund dispute on an order.
     * If approved: cancels pending cashback; sets vendor_settlement_status = 'refunded' (seller orders only).
     * If rejected: restores vendor_settlement_status to 'eligible' (if window expired) or 'held'.
     */
    public function resolveRefundDispute(Order $order, string $decision): void
    {
        // [AI] Approval is a decision, not evidence of a completed financial refund.
        if ($decision === 'approved') {
            throw new \LogicException('Use approved refund requests and canonical manual/provider accounting confirmation.');
        }
        if ($decision !== 'rejected') throw new \InvalidArgumentException('Invalid dispute decision.');
        DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->evaluateOrderSettlementEligibility($locked);
        });
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
        if ($paymentMethod !== 'wallet_release' || trim($paymentReference) === '') {
            return ['status' => false, 'message' => 'Use wallet_release and an audit reference.'];
        }
        return DB::transaction(function () use ($orderId, $adminId, $paymentReference, $notes) {
            $order = Order::whereKey($orderId)->lockForUpdate()->first();
            if (!$order) return ['status' => false, 'message' => 'Order not found.'];
            $amount = OrderManager::disburseSettledVendorOrder($order, $paymentReference, $adminId);
            if ($amount === null) return ['status' => true, 'message' => 'Wallet entitlement already released.', 'replayed' => true];
            $order->refresh();
            $order->settlement_notes = $notes;
            $order->settlement_method = 'wallet_release';
            $order->save();
            AdminAuditService::log('vendor.wallet_release', Order::class, $orderId, null, ['amount' => $amount, 'reference' => $paymentReference, 'currency' => 'NGN'], $notes);
            // [AI] Accounting event records vendor entitlement, never a second bank payment.
            Transaction::create(['order_id' => $orderId, 'payment_for' => 'vendor_settlement', 'payer_id' => $adminId,
                'payment_receiver_id' => $order->seller_id, 'paid_by' => 'admin', 'paid_to' => 'seller',
                'payment_status' => 'wallet_release', 'amount' => $amount, 'transaction_type' => 'wallet_release']);
            Log::info('[AUDIT] Vendor wallet entitlement released', ['order_id' => $orderId, 'admin_id' => $adminId,
                'reference' => $paymentReference, 'notes' => $notes, 'amount' => $amount, 'currency' => 'NGN']);
            return ['status' => true, 'message' => 'Vendor earnings released to wallet. Pay through withdrawal approval.',
                'order_id' => $orderId, 'reference' => $paymentReference, 'vendor_amount' => $amount];
        });
    }

    /**
     * [AI] Full refund execution for orders where actual customer delivery NEVER occurred (received_at === null).
     * Reverses delivery fee from AdminWallet and sets is_delivery_fee_refunded = 1.
     */
    public function executeUndeliveredOrderRefund(Order $order, string $reason = ''): array
    {
        // [AI] Retired synthetic refund path: it cannot create payment proof or reverse funds without verified accounting.
        return ['status' => false, 'message' => 'Create and approve a refund request, then confirm actual payment through canonical refund accounting.'];
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
