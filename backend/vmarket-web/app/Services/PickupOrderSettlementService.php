<?php

namespace App\Services;

use App\Exceptions\PostPaymentStockFailureException;
use App\Models\Cart;
use App\Models\CashbackRedemption;
use App\Models\Order;
use App\Models\PaymentReconciliation;
use App\Models\PaymentRequest;
use App\Models\PickupReservation;
use App\Models\Product;
use App\Models\User;
use App\Utils\OrderManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * [AI] Service PickupOrderSettlementService
 *
 * Handles verified payment settlement for in-shop pickup reservations.
 *
 * Core Invariants:
 * 1. Zero external network I/O inside database transactions (Verification occurs outside).
 * 2. Canonical lock hierarchy: User -> PickupReservation -> PaymentRequest.
 * 3. Exact integer kobo amount verification via BCMath.
 * 4. Decoupled payment semantics: is_paid=1 alone is NEVER treated as successful order.
 *    Only attempt_status='successful' AND is_paid=1 AND order_id IS NOT NULL = ALREADY_SETTLED.
 * 5. Exactly ONE Order generated per settled pickup reservation (order_type = 'pickup').
 * 6. Atomic inventory deduction on Product.current_stock (authoritative physical quantity).
 * 7. Two-phase stock failure: Phase 1 complete transaction rollback; Phase 2 persistent reconciliation.
 * 8. Financial hold: OrderTransaction inserted with status='hold' and AdminWallet.pending_amount incremented.
 *    Seller wallet is never credited at settlement.
 * 9. Handover OTP: generated only upon Order creation (Order.pickup_verification_code, 6 digits).
 * 10. Competing reservation cancellation: overlapping reservations set to 'canceled' (existing enum),
 *     their pending attempts superseded; quarantined reconciliation_required attempts preserved.
 * 11. Targeted post-commit cart pruning: only snapshot cart IDs removed; unrelated cart items preserved.
 * 12. [AI] Pickup Cashback Award: After Order creation, PickupCashbackAwardService awards 5%
 *     (admin-config-driven) Victorious Cashback. Inside the transaction = rolls back if order fails.
 *     Earning event only (no reserve phase). cashback_earned included in settlement response.
 */
class PickupOrderSettlementService
{
    public function __construct(
        protected PickupCashbackAwardService $cashbackAwardService = new PickupCashbackAwardService()
    ) {}
    /**
     * Settles a verified Paystack payment for a marketplace pickup reservation.
     *
     * @param string $verifiedReference Authoritative Paystack transaction reference (VM-...)
     * @param array $gatewayData Verified Paystack transaction data payload
     * @return array
     */
    public function settleVerifiedPayment(string $verifiedReference, array $gatewayData): array
    {
        // STEP 1: Unlocked Read & Basic Sanity
        $initialPR = PaymentRequest::where('gateway_reference', $verifiedReference)->first();
        if (!$initialPR) {
            Log::warning("PickupOrderSettlement: PaymentRequest not found for reference '{$verifiedReference}'.");
            return ['status' => 'not_found', 'message' => 'PaymentRequest not found.'];
        }

        if ($initialPR->payment_domain !== 'marketplace_pickup') {
            Log::warning("PickupOrderSettlement: PaymentRequest #{$initialPR->id} is not 'marketplace_pickup' domain ({$initialPR->payment_domain}).");
            return ['status' => 'domain_mismatch', 'message' => 'PaymentRequest is not for marketplace pickup.'];
        }

        $reservationId = (int) ($initialPR->active_pickup_reservation_id ?: $this->resolveReservationIdFromPR($initialPR));
        if ($reservationId <= 0) {
            return ['status' => 'invalid_state', 'message' => 'Cannot resolve associated pickup reservation.'];
        }

        // STEP 2: Fast-Path Replay Check (Zero DB Locks)
        if ($initialPR->attempt_status === 'successful' && (int) $initialPR->is_paid === 1) {
            $reservation = PickupReservation::find($reservationId);
            if ($reservation && $reservation->order_id !== null) {
                Log::info("PickupOrderSettlement: PaymentRequest #{$initialPR->id} already settled with Order #{$reservation->order_id}. Replaying.");
                return [
                    'status' => 'ALREADY_SETTLED',
                    'is_replayed' => true,
                    'message' => 'Payment already settled and pickup order created.',
                    'payment_request' => $initialPR,
                    'order_id' => $reservation->order_id,
                ];
            }
        }

        if ($initialPR->attempt_status === 'reconciliation_required' && (int) $initialPR->is_paid === 1) {
            Log::info("PickupOrderSettlement: PaymentRequest #{$initialPR->id} already in reconciliation. Replaying quarantine response.");
            return [
                'status' => 'RECONCILIATION_ALREADY_RECORDED',
                'is_replayed' => true,
                'message' => 'Payment anomaly already quarantined in reconciliation.',
                'payment_request' => $initialPR,
            ];
        }

        // STEP 3: Atomic Database Settlement Transaction
        try {
            $result = DB::transaction(function () use ($initialPR, $reservationId, $verifiedReference, $gatewayData) {
                $customerId = (int) $initialPR->payer_id;

                // Lock 1: Customer Serialization Anchor
                User::where('id', $customerId)->lockForUpdate()->first();

                // Lock 2: PickupReservation
                $reservation = PickupReservation::where('id', $reservationId)->lockForUpdate()->first();
                if (!$reservation) {
                    return ['status' => 'not_found', 'message' => 'PickupReservation not found under lock.'];
                }

                // Lock 3: PaymentRequest
                $paymentRequest = PaymentRequest::where('id', $initialPR->id)->lockForUpdate()->first();
                if (!$paymentRequest) {
                    return ['status' => 'not_found', 'message' => 'PaymentRequest not found under lock.'];
                }

                // Re-evaluate replay under lock
                if ($paymentRequest->attempt_status === 'successful' && (int) $paymentRequest->is_paid === 1 && $reservation->order_id !== null) {
                    return [
                        'status' => 'ALREADY_SETTLED',
                        'is_replayed' => true,
                        'message' => 'Payment already settled and pickup order created.',
                        'payment_request' => $paymentRequest,
                        'order_id' => $reservation->order_id,
                    ];
                }

                if ($paymentRequest->attempt_status === 'reconciliation_required' && (int) $paymentRequest->is_paid === 1) {
                    return [
                        'status' => 'RECONCILIATION_ALREADY_RECORDED',
                        'is_replayed' => true,
                        'message' => 'Payment anomaly already quarantined in reconciliation.',
                        'payment_request' => $paymentRequest,
                    ];
                }

                // ANOMALY CHECK 1: PaymentRequest status must be pending
                if ($paymentRequest->attempt_status !== 'pending') {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $reservation,
                        'stale_superseded_attempt',
                        $gatewayData,
                        "PaymentRequest status is '{$paymentRequest->attempt_status}', expected 'pending'."
                    );
                }

                // ANOMALY CHECK 2: Customer ownership match
                if ((int) $reservation->customer_id !== (int) $paymentRequest->payer_id) {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $reservation,
                        'invalid_snapshot',
                        $gatewayData,
                        "Customer mismatch: Reservation customer #{$reservation->customer_id} vs PaymentRequest payer #{$paymentRequest->payer_id}."
                    );
                }

                // ANOMALY CHECK 3: Reservation status must be inspected_accepted
                if ($reservation->status !== 'inspected_accepted') {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $reservation,
                        'stale_reservation_state',
                        $gatewayData,
                        "PickupReservation status is '{$reservation->status}', expected 'inspected_accepted'."
                    );
                }

                // ANOMALY CHECK 4: Reservation order_id must be null
                if ($reservation->order_id !== null) {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $reservation,
                        'duplicate_capture',
                        $gatewayData,
                        "PickupReservation #{$reservation->id} already has order #{$reservation->order_id}."
                    );
                }

                // ANOMALY CHECK 5: Late Capture on Expired Reservation
                if ($reservation->isExpired()) {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $reservation,
                        'late_capture_expired',
                        $gatewayData,
                        "PickupReservation #{$reservation->id} has expired (expired_at: {$reservation->expires_at})."
                    );
                }

                // ANOMALY CHECK 6: Currency Mismatch
                $gatewayCurrency = strtoupper((string) ($gatewayData['currency'] ?? 'NGN'));
                if ($gatewayCurrency !== 'NGN' || strtoupper((string) $paymentRequest->currency_code) !== 'NGN') {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $reservation,
                        'currency_mismatch',
                        $gatewayData,
                        "Currency mismatch: gateway sent '{$gatewayCurrency}', expected NGN."
                    );
                }

                // ANOMALY CHECK 7: Exact Integer Kobo Amount Equality (BCMath)
                $expectedKobo = (int) bcmul(bcadd((string) $paymentRequest->payment_amount, '0', 2), '100', 0);
                $paidKobo = (int) ($gatewayData['amount'] ?? 0);
                if ($paidKobo !== $expectedKobo) {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $reservation,
                        'amount_mismatch',
                        $gatewayData,
                        "Amount mismatch: expected {$expectedKobo} kobo, gateway paid {$paidKobo} kobo."
                    );
                }

                // ANOMALY CHECK 8: Overlap Concurrency Guard
                $snapshot = $reservation->reservation_items ?: (is_array($reservation->snapshot)
                    ? $reservation->snapshot
                    : json_decode($reservation->snapshot ?? '{}', true));

                $cartIds = [];
                foreach ($snapshot['items'] ?? [] as $item) {
                    if (!empty($item['cart_id'])) {
                        $cartIds[] = (int) $item['cart_id'];
                    }
                }

                if (!empty($cartIds)) {
                    $otherReservations = PickupReservation::where('customer_id', $customerId)
                        ->where('id', '!=', $reservation->id)
                        ->get();

                    foreach ($otherReservations as $otherRes) {
                        $otherSnapshot = $otherRes->reservation_items ?: (is_array($otherRes->snapshot)
                            ? $otherRes->snapshot
                            : json_decode($otherRes->snapshot ?? '{}', true));

                        $otherCartIds = [];
                        foreach ($otherSnapshot['items'] ?? [] as $otherItem) {
                            if (!empty($otherItem['cart_id'])) {
                                $otherCartIds[] = (int) $otherItem['cart_id'];
                            }
                        }

                        $overlap = array_intersect($cartIds, $otherCartIds);
                        if (!empty($overlap) && $otherRes->order_id !== null) {
                            return $this->handlePermanentAnomaly(
                                $paymentRequest,
                                $reservation,
                                'stale_reservation_state',
                                $gatewayData,
                                "Items in this reservation were already fulfilled in competing Reservation #{$otherRes->id} (Order #{$otherRes->order_id})."
                            );
                        }
                    }
                }

                // STEP 3B: Single Order Creation from Immutable Snapshot
                $createdOrderId = $this->createPickupOrderFromSnapshot($reservation, $paymentRequest);
                $createdOrder = Order::find($createdOrderId);

                // STEP 3C: [AI] Capture Reserved Cashback (Spend Path)
                // If customer redeemed points before payment, capture the reservation now
                $cashbackRedeemed = ['captured' => false, 'points' => '0.0000', 'cashback_amount' => '0.00'];
                $additional = is_array($paymentRequest->additional_data)
                    ? $paymentRequest->additional_data
                    : json_decode($paymentRequest->additional_data ?? '{}', true);

                if (!empty($additional['cashback_reservation']['redemption_id'])) {
                    $redemptionId = $additional['cashback_reservation']['redemption_id'];
                    $redemption = CashbackRedemption::find($redemptionId);
                    if ($redemption && $redemption->status === 'reserved') {
                        $redemption->capture(); // captures immediately, no points returned
                        $cashbackRedeemed = [
                            'captured' => true,
                            'points' => $redemption->points,
                            'cashback_amount' => $redemption->cashback_amount,
                        ];
                        Log::info("[AI] PickupOrderSettlement: Captured cashback redemption #{$redemptionId} " .
                            "({$redemption->points} pts, ₦{$redemption->cashback_amount}) for Order #{$createdOrderId}.");
                    }
                }

                // STEP 3D: [AI] Pickup Cashback Award (inside transaction — rolls back if order fails)
                // Earning event: 5% of total_amount awarded immediately as 'captured' (no reserve phase).
                // lockForUpdate() on User was acquired in Lock 1 above; cashbackAwardService trusts that lock.
                $cashbackResult = ['awarded' => false, 'cashback_amount' => '0.00', 'points' => '0.0000'];
                if ($createdOrder) {
                    try {
                        $cashbackResult = $this->cashbackAwardService->award($reservation, $createdOrder, $customerId);
                    } catch (\Throwable $cbEx) {
                        // [AI] Non-fatal: log and continue. Order is already created; cashback failure
                        // should NOT roll back the payment settlement. Cashback can be reconciled manually.
                        Log::error('[AI] PickupOrderSettlement: Cashback award failed (non-fatal).', [
                            'reservation_id' => $reservation->id,
                            'order_id' => $createdOrderId,
                            'error' => $cbEx->getMessage(),
                        ]);
                    }
                }

                // STEP 3E: Atomic State Transitions
                $reservation->update([
                    'status' => 'order_placed',
                    'order_id' => $createdOrderId,
                    'active_reservation_token' => null,
                ]);

                $additionalData = is_array($paymentRequest->additional_data)
                    ? $paymentRequest->additional_data
                    : json_decode($paymentRequest->additional_data ?? '{}', true);

                $additionalData['settled_order_id'] = $createdOrderId;
                $additionalData['settled_at'] = now()->toIso8601String();

                // Record cashback redeemed (spend path)
                if ($cashbackRedeemed['captured']) {
                    $additionalData['cashback_redeemed_amount'] = $cashbackRedeemed['cashback_amount'];
                    $additionalData['cashback_redeemed_points'] = $cashbackRedeemed['points'];
                }

                // Record cashback earned (earn path)
                if ($cashbackResult['awarded']) {
                    $additionalData['cashback_earned_amount'] = $cashbackResult['cashback_amount'];
                    $additionalData['cashback_earned_points'] = $cashbackResult['points'];
                }

                $paymentRequest->update([
                    'attempt_status' => 'successful',
                    'is_paid' => 1,
                    'active_pickup_reservation_id' => null,
                    'additional_data' => json_encode($additionalData),
                ]);

                // STEP 3F: Cancel Competing Overlapping Reservations
                $this->cancelCompetingOverlappingReservations($reservation, $cartIds);

                return [
                    'status' => 'CLAIMED',
                    'is_replayed' => false,
                    'message' => 'Pickup payment settled successfully and pickup order generated.',
                    'payment_request' => $paymentRequest->fresh(),
                    'order_id' => $createdOrderId,
                    'snapshot' => $snapshot,
                    'customer_id' => $customerId,
                    // [AI] Cashback redeemed (spend path) — surfaced for customer confirmation
                    'cashback_redeemed' => [
                        'captured'        => $cashbackRedeemed['captured'],
                        'points'          => $cashbackRedeemed['points'],
                        'amount_naira'    => $cashbackRedeemed['cashback_amount'],
                    ],
                    // [AI] Cashback earned (earn path) — surfaced to Paystack callback for customer notification
                    'cashback_earned' => [
                        'awarded'         => $cashbackResult['awarded'],
                        'points'          => $cashbackResult['points'],
                        'amount_naira'    => $cashbackResult['cashback_amount'],
                    ],
                ];
            });

            // STEP 4: Post-Commit Actions (Strictly after transaction commits successfully)
            if (($result['status'] ?? '') === 'CLAIMED') {
                $customerId = (int) ($result['customer_id'] ?? 0);
                $snapshot = $result['snapshot'] ?? [];
                $this->pruneSnapshotCartItems($customerId, $snapshot, $result['payment_request'] ?? null);
            }

            return $result;
        } catch (PostPaymentStockFailureException $e) {
            // PHASE 1 COMPLETED: Settlement transaction rolled back completely. Zero partial orders or holds.
            // PHASE 2: Start a NEW independent transaction to persist the reconciliation anomaly record.
            Log::warning("PickupOrderSettlement: Post-payment stock failure detected. Entering Phase 2 reconciliation: " . $e->getMessage());
            return $this->persistStockFailureReconciliation($verifiedReference, $gatewayData, $e->getMessage());
        }
    }

    /**
     * Creates exactly one pickup Order, OrderDetails, OrderStatusHistory, and OrderTransaction
     * derived strictly from the immutable reservation snapshot.
     */
    protected function createPickupOrderFromSnapshot(
        PickupReservation $reservation,
        PaymentRequest $paymentRequest
    ): int {
        $snapshot = $reservation->reservation_items ?: (is_array($reservation->snapshot)
            ? $reservation->snapshot
            : json_decode($reservation->snapshot ?? '{}', true));

        $items = $snapshot['items'] ?? [];
        if (empty($items)) {
            throw new \RuntimeException("Reservation snapshot contains zero items.");
        }

        // Establish authoritative seller_is convention: snapshot -> shop author_type
        $sellerIs = $snapshot['seller_is'] ?? null;
        if (empty($sellerIs)) {
            $shopAuthor = Shop::where('id', $reservation->shop_id)->value('author_type');
            $sellerIs = ($shopAuthor === 'admin') ? 'admin' : 'seller';
        }

        $orderId = 100000 + Order::all()->count() + 1;
        $verificationCode = (string) rand(100000, 999999);
        $handoverCode = (string) rand(100000, 999999);
        $customerId = (int) $reservation->customer_id;

        // Internal transaction_ref for OrderManager (<= 20 chars, unique)
        $internalTxRef = 'PKP' . substr(str_replace('-', '', Str::orderedUuid()->toString()), 0, 16);

        // Exact Money Calculations via BCMath (Zero Float Drift: Δ = ₦0.00)
        $subtotal = bcadd((string) ($snapshot['subtotal'] ?? '0.00'), '0', 2);
        $orderAmount = bcadd((string) $reservation->total_amount, '0', 2);
        $rawCommission = bcdiv(bcmul($subtotal, '10', 4), '100', 4);
        $adminCommission = bcadd($rawCommission, '0', 2);
        $sellerAmount = bcsub($orderAmount, $adminCommission, 2);

        $ordersData = [
            'id' => $orderId,
            'verification_code' => $verificationCode,
            'pickup_verification_code' => $handoverCode,
            'customer_id' => $customerId,
            'is_guest' => 0,
            'guest_access_token' => null,
            'seller_id' => $reservation->seller_id,
            'seller_is' => $sellerIs,
            'customer_type' => 'customer',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
            'vendor_settlement_status' => ($sellerIs === 'seller') ? 'held' : null,
            'payment_method' => 'paystack',
            'transaction_ref' => $internalTxRef, // strictly internal ID; NEVER the Paystack reference
            'order_group_id' => 'pickup-' . $reservation->reservation_code,
            'discount_amount' => '0.00',
            'discount_type' => null,
            'coupon_code' => null,
            'coupon_discount_bearer' => 'inhouse',
            'order_amount' => $orderAmount,
            'init_order_amount' => $orderAmount,
            'total_tax_amount' => '0.00',
            'tax_type' => 'percent',
            'tax_model' => 'exclude',
            'admin_commission' => $adminCommission,
            'order_type' => 'pickup',
            'shipping_address' => 0,
            'shipping_address_data' => null,
            'billing_address' => null,
            'billing_address_data' => null,
            'shipping_responsibility' => 'inhouse_shipping',
            'shipping_cost' => '0.00',
            'shipping_method_id' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('orders')->insert($ordersData);

        // Record status history (Preserving OrderManager semantics)
        OrderManager::add_order_status_history($orderId, $customerId, 'confirmed', 'customer');

        // Order details & Atomic Inventory Deduction on Product.current_stock
        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $qty = (int) $item['quantity'];
            $product = Product::find($productId);

            // Atomic Physical Inventory Deduction Guard with Ownership Scoping:
            if ($product && $product->product_type === 'physical') {
                $stockQuery = Product::where('id', $productId)
                    ->where('current_stock', '>=', $qty);

                if ($sellerIs === 'seller') {
                    $stockQuery->where('added_by', 'seller')
                        ->where('user_id', $reservation->seller_id);
                } elseif ($sellerIs === 'admin') {
                    $stockQuery->where('added_by', 'admin');
                }

                if (!empty($reservation->shop_id)) {
                    $stockQuery->where(function ($q) use ($reservation) {
                        $q->where('shop_id', $reservation->shop_id)
                          ->orWhereNull('shop_id');
                    });
                }

                $affected = $stockQuery->decrement('current_stock', $qty);

                if ($affected === 0) {
                    $prodName = $product->name ?? "Product #{$productId}";
                    $freshProd = Product::find($productId);
                    $currentStock = $freshProd?->current_stock ?? 0;

                    // Verify if failure is due to ownership mismatch vs insufficient stock
                    if ($freshProd) {
                        $sellerMatch = ($sellerIs === 'seller')
                            ? ($freshProd->added_by === 'seller' && (int) $freshProd->user_id === (int) $reservation->seller_id)
                            : ($freshProd->added_by === 'admin');
                        $shopMatch = empty($reservation->shop_id) || empty($freshProd->shop_id) || ((int) $freshProd->shop_id === (int) $reservation->shop_id);

                        if (!$sellerMatch || !$shopMatch) {
                            throw new PostPaymentStockFailureException(
                                "{$prodName} [Ownership mismatch: product belongs to seller #{$freshProd->user_id} shop #{$freshProd->shop_id}, expected seller #{$reservation->seller_id} shop #{$reservation->shop_id}]",
                                $productId,
                                $qty,
                                $currentStock
                            );
                        }
                    }

                    throw new PostPaymentStockFailureException($prodName, $productId, $qty, $currentStock);
                }
            }

            DB::table('order_details')->insert([
                'order_id' => $orderId,
                'product_id' => $productId,
                'seller_id' => $reservation->seller_id,
                'product_details' => json_encode($product ? $product->toArray() : ['name' => $item['product_name'] ?? 'Item']),
                'qty' => $qty,
                'price' => bcadd((string) ($item['unit_price'] ?? '0.00'), '0', 2),
                'discount' => bcadd((string) ($item['discount'] ?? '0.00'), '0', 2),
                'tax' => bcadd((string) ($item['tax'] ?? '0.00'), '0', 2),
                'discount_type' => 'discount_on_product',
                'variant' => $item['variant'] ?? null,
                'delivery_status' => 'pending',
                'payment_status' => 'paid',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Financial Hold & AdminWallet Invariant:
        // OrderManager::getAddOrderTransactionsOnGenerateOrder creates the hold transaction
        // and increments AdminWallet pending_amount by $order['order_amount'].
        // Seller wallet is NOT credited at this time (escrow hold active).
        $order = Order::with(['customer', 'seller.shop', 'details'])->find($orderId);
        OrderManager::getAddOrderTransactionsOnGenerateOrder(order: $order, ordersData: $ordersData);

        return $orderId;
    }

    /**
     * Cancels competing overlapping reservations after successful order placement.
     */
    protected function cancelCompetingOverlappingReservations(PickupReservation $settledRes, array $cartIds): void
    {
        if (empty($cartIds)) {
            return;
        }

        $customerId = (int) $settledRes->customer_id;
        $competitors = PickupReservation::where('customer_id', $customerId)
            ->where('id', '!=', $settledRes->id)
            ->whereIn('status', ['pending_inspection', 'inspected_accepted'])
            ->get();

        foreach ($competitors as $comp) {
            $compSnapshot = $comp->reservation_items ?: (is_array($comp->snapshot)
                ? $comp->snapshot
                : json_decode($comp->snapshot ?? '{}', true));

            $compCartIds = [];
            foreach ($compSnapshot['items'] ?? [] as $item) {
                if (!empty($item['cart_id'])) {
                    $compCartIds[] = (int) $item['cart_id'];
                }
            }

            if (!empty(array_intersect($cartIds, $compCartIds))) {
                // Cancel competing reservation using existing 'canceled' enum
                $comp->update([
                    'status' => 'canceled',
                    'active_reservation_token' => null,
                ]);

                // Supersede its pending payment request if exists (do NOT overwrite reconciliation_required)
                $compPR = PaymentRequest::where('active_pickup_reservation_id', $comp->id)->first();
                if ($compPR && $compPR->attempt_status === 'pending') {
                    $add = json_decode($compPR->additional_data ?? '{}', true);
                    $add['superseded_by_reservation_id'] = $settledRes->id;
                    $add['superseded_at'] = now()->toIso8601String();
                    $compPR->update([
                        'attempt_status' => 'superseded',
                        'active_pickup_reservation_id' => null,
                        'additional_data' => json_encode($add),
                    ]);
                }
            }
        }
    }

    /**
     * Phase 2 Anomaly Persistence for Post-Payment Stock Failure.
     */
    protected function persistStockFailureReconciliation(
        string $verifiedReference,
        array $gatewayData,
        string $reason
    ): array {
        return DB::transaction(function () use ($verifiedReference, $gatewayData, $reason) {
            $paymentRequest = PaymentRequest::where('gateway_reference', $verifiedReference)
                ->lockForUpdate()
                ->first();

            if (!$paymentRequest) {
                return ['status' => 'not_found', 'message' => 'PaymentRequest not found during stock failure reconciliation.'];
            }

            $additional = is_array($paymentRequest->additional_data)
                ? $paymentRequest->additional_data
                : json_decode($paymentRequest->additional_data ?? '{}', true);

            $additional['failure_reason'] = 'post_payment_stock_failure';
            $additional['stock_failure_detail'] = $reason;

            $paymentRequest->update([
                'attempt_status' => 'reconciliation_required',
                'active_pickup_reservation_id' => null,
                'is_paid' => 1,
                'additional_data' => json_encode($additional),
            ]);

            $capturedNaira = bcdiv((string) ($gatewayData['amount'] ?? 0), '100', 4);

            $reconciliation = PaymentReconciliation::create([
                'case_number' => 'REC-' . Str::orderedUuid()->toString(),
                'gateway_reference' => $verifiedReference,
                'payment_request_id' => $paymentRequest->id,
                'payment_domain' => 'marketplace_pickup',
                'order_group_id' => null,
                'customer_id' => $paymentRequest->payer_id,
                'gateway_name' => 'paystack',
                'captured_amount' => $capturedNaira,
                'expected_amount' => $paymentRequest->payment_amount,
                'currency' => strtoupper((string) ($gatewayData['currency'] ?? 'NGN')),
                'initial_anomaly_type' => 'post_payment_stock_failure',
                'current_status' => 'open',
                'audit_events' => json_encode([[
                    'event' => 'ANOMALY_DETECTED',
                    'type' => 'post_payment_stock_failure',
                    'reason' => $reason,
                    'gateway_data' => $gatewayData,
                    'timestamp' => now()->toIso8601String(),
                ]]),
            ]);

            return [
                'status' => 'reconciliation_required',
                'anomaly_type' => 'post_payment_stock_failure',
                'message' => $reason,
                'reconciliation_case' => $reconciliation->case_number,
                'payment_request' => $paymentRequest->fresh(),
            ];
        });
    }

    /**
     * Handles permanent business anomalies by persisting a reconciliation case in a committed transaction.
     */
    protected function handlePermanentAnomaly(
        PaymentRequest $paymentRequest,
        PickupReservation $reservation,
        string $anomalyType,
        array $gatewayData,
        string $reason
    ): array {
        if ($anomalyType === 'late_capture_expired') {
            $reservation->update([
                'status' => 'expired',
                'active_reservation_token' => null,
            ]);
        }

        $additional = is_array($paymentRequest->additional_data)
            ? $paymentRequest->additional_data
            : json_decode($paymentRequest->additional_data ?? '{}', true);

        $additional['failure_reason'] = $anomalyType;
        $additional['anomaly_detail'] = $reason;

        $paymentRequest->update([
            'attempt_status' => 'reconciliation_required',
            'active_pickup_reservation_id' => null,
            'is_paid' => 1,
            'additional_data' => json_encode($additional),
        ]);

        $capturedNaira = bcdiv((string) ($gatewayData['amount'] ?? 0), '100', 4);

        $reconciliation = PaymentReconciliation::create([
            'case_number' => 'REC-' . Str::orderedUuid()->toString(),
            'gateway_reference' => $paymentRequest->gateway_reference,
            'payment_request_id' => $paymentRequest->id,
            'payment_domain' => 'marketplace_pickup',
            'order_group_id' => null,
            'customer_id' => $paymentRequest->payer_id,
            'gateway_name' => 'paystack',
            'captured_amount' => $capturedNaira,
            'expected_amount' => $paymentRequest->payment_amount,
            'currency' => strtoupper((string) ($gatewayData['currency'] ?? 'NGN')),
            'initial_anomaly_type' => $anomalyType,
            'current_status' => 'open',
            'audit_events' => json_encode([[
                'event' => 'ANOMALY_DETECTED',
                'type' => $anomalyType,
                'reason' => $reason,
                'gateway_data' => $gatewayData,
                'timestamp' => now()->toIso8601String(),
            ]]),
        ]);

        return [
            'status' => 'reconciliation_required',
            'anomaly_type' => $anomalyType,
            'message' => $reason,
            'reconciliation_case' => $reconciliation->case_number,
            'payment_request' => $paymentRequest->fresh(),
        ];
    }

    /**
     * Prunes only the cart items present in the immutable reservation snapshot inside one short atomic transaction.
     * Quantity-Safe: Decrements cart quantity by snapshot quantity; deletes row only if
     * remaining quantity is <= 0. Unrelated or subsequent cart items are preserved.
     * Idempotent & Retry-Safe: Tracks cleanup in payment_requests.additional_data under pessimistic lock.
     * Crash-Consistent: A failure at any point rolls back all cart mutations and omits marker persistence.
     *
     * @param int $customerId Authenticated customer ID
     * @param array $snapshot Immutable reservation snapshot containing items array
     * @param PaymentRequest|null $paymentRequest Optional PaymentRequest idempotency anchor
     * @return int Count of affected cart rows
     */
    public function pruneSnapshotCartItems(int $customerId, array $snapshot, ?PaymentRequest $paymentRequest = null): int
    {
        $items = $snapshot['items'] ?? [];
        if (empty($items)) {
            return 0;
        }

        return DB::transaction(function () use ($customerId, $items, $paymentRequest) {
            // 1. Idempotency anchor: lock PaymentRequest and inspect completion marker
            $lockedPR = null;
            if ($paymentRequest) {
                $lockedPR = PaymentRequest::where('id', $paymentRequest->id)
                    ->lockForUpdate()
                    ->first();

                if ($lockedPR) {
                    $add = is_array($lockedPR->additional_data)
                        ? $lockedPR->additional_data
                        : json_decode($lockedPR->additional_data ?? '{}', true);

                    if (!empty($add['cart_cleaned_at'])) {
                        // Idempotent: cart cleanup has already run and committed for this payment request
                        return 0;
                    }
                }
            }

            // 2. Collect unique target cart IDs and sort ascending to enforce deterministic row locking (deadlock prevention)
            $targetCartMap = [];
            foreach ($items as $item) {
                $cartId = (int) ($item['cart_id'] ?? 0);
                if ($cartId > 0) {
                    $targetCartMap[$cartId] = $item;
                }
            }

            if (empty($targetCartMap)) {
                return 0;
            }

            $sortedCartIds = array_keys($targetCartMap);
            sort($sortedCartIds, SORT_NUMERIC);

            // 3. Lock all target Cart rows deterministically in ascending ID order
            $lockedCartRows = Cart::whereIn('id', $sortedCartIds)
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $affectedCount = 0;
            $prunedSummary = [];

            foreach ($sortedCartIds as $targetCartId) {
                $item = $targetCartMap[$targetCartId];
                $targetProdId = (int) ($item['product_id'] ?? 0);
                $targetVariant = (string) ($item['variant'] ?? '');
                $snapshotQty = max(1, (int) ($item['quantity'] ?? 1));

                $cartRow = $lockedCartRows->get($targetCartId);
                if (!$cartRow) {
                    // Cart row was already deleted, expired, or belongs to another customer
                    continue;
                }

                // Verify customer ownership under lock
                if ((int) $cartRow->customer_id !== $customerId) {
                    continue;
                }

                // Verify product identity matches snapshot
                if ((int) $cartRow->product_id !== $targetProdId) {
                    // Cart row identity changed (recreated with different product); preserve row completely
                    continue;
                }

                // Verify variant identity matches snapshot (if variant specified in snapshot or cart)
                $cartRowVariant = (string) ($cartRow->variant ?? '');
                if ($targetVariant !== '' && $cartRowVariant !== '' && $targetVariant !== $cartRowVariant) {
                    // Variant mismatch; preserve row completely
                    continue;
                }

                $currentCartQty = (int) $cartRow->quantity;

                if ($currentCartQty <= $snapshotQty) {
                    // Entire quantity consumed by reservation -> delete cart row
                    $cartRow->delete();
                    $affectedCount++;
                    $prunedSummary[] = [
                        'cart_id' => $targetCartId,
                        'action' => 'deleted',
                        'snapshot_qty' => $snapshotQty,
                        'previous_qty' => $currentCartQty,
                    ];
                } else {
                    // Customer added more quantity post-reservation -> decrement ONLY snapshot quantity
                    $newCartQty = $currentCartQty - $snapshotQty;
                    $cartRow->update(['quantity' => $newCartQty]);
                    $affectedCount++;
                    $prunedSummary[] = [
                        'cart_id' => $targetCartId,
                        'action' => 'decremented',
                        'snapshot_qty' => $snapshotQty,
                        'previous_qty' => $currentCartQty,
                        'remaining_qty' => $newCartQty,
                    ];
                }
            }

            // 4. Persist cleanup completion marker on PaymentRequest before commit
            if ($lockedPR) {
                $add = is_array($lockedPR->additional_data)
                    ? $lockedPR->additional_data
                    : json_decode($lockedPR->additional_data ?? '{}', true);

                $add['cart_cleaned_at'] = now()->toIso8601String();
                $add['cart_prune_summary'] = $prunedSummary;
                $lockedPR->update([
                    'additional_data' => json_encode($add),
                ]);
            }

            return $affectedCount;
        });
    }

    /**
     * Resolves reservation ID from additional_data if active_pickup_reservation_id was cleared.
     */
    protected function resolveReservationIdFromPR(PaymentRequest $pr): ?int
    {
        $additional = is_array($pr->additional_data)
            ? $pr->additional_data
            : json_decode($pr->additional_data ?? '{}', true);

        return !empty($additional['reservation_id']) ? (int) $additional['reservation_id'] : null;
    }
}
