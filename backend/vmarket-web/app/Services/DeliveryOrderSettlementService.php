<?php

namespace App\Services;

use App\Exceptions\PostPaymentStockFailureException;
use App\Models\AdminWallet;
use App\Models\Cart;
use App\Models\CashbackRedemption;
use App\Models\CheckoutIntent;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentReconciliation;
use App\Models\PaymentRequest;
use App\Models\Product;
use App\Models\User;
use App\Utils\OrderManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * [AI] Service DeliveryOrderSettlementService
 * 
 * Authoritative settlement engine converting verified Paystack payments (charge.success)
 * into atomic multi-vendor Delivery Orders, OrderDetails, OrderTransactions, and AdminWallet escrow holds.
 *
 * Invariants:
 * 1. Canonical Lock Ordering: CheckoutIntent -> PaymentRequest (strictly prevents lock-inversion deadlocks).
 * 2. Exact Integer Kobo via BCMath (zero float, zero drift, Δ = ₦0.00).
 * 3. Two-Phase Stock Failure Handling:
 *    - Phase 1: Order transaction rolls back completely (0 orders, 0 stock changes, 0 financial effects).
 *    - Phase 2: A new transaction locks PaymentRequest, sets attempt_status = 'reconciliation_required',
 *      inserts payment_reconciliations with 'post_payment_stock_failure', and COMMITS (HTTP 200).
 * 4. Separation of Payment vs Checkout Anomalies:
 *    - Payment anomalies (amount/currency mismatch, stock failure) quarantine only PaymentRequest.
 *    - CheckoutIntent is ONLY marked 'expired' if it has actually expired (late_capture_expired).
 * 5. Idempotency: Replaying verified payment returns ALREADY_PAID with 0 duplicate orders and ₦0 duplicate drift.
 * 6. Reference Isolation: Paystack reference is stored exclusively in payment_requests.gateway_reference.
 *    orders.transaction_ref receives internal OrderManager unique ID (<= 21 chars), NEVER the Paystack ref.
 * 7. OrderManager Parity: Preserves verification codes, order status history, atomic inventory deduction,
 *    OrderTransactions with status='hold', and AdminWallet pending holds without double-counting.
 * 8. Post-Commit Targeted Cart Pruning: Only snapshot cart item IDs are deleted; unrelated cart items are preserved.
 */
class DeliveryOrderSettlementService
{
    /**
     * Settles a verified Paystack delivery payment.
     *
     * @param string $verifiedReference Authoritative reference verified by Paystack gateway
     * @param array $gatewayData Verified transaction data from Paystack
     * @return array
     */
    public function settleVerifiedPayment(string $verifiedReference, array $gatewayData): array
    {
        // 1. Initial lookup by canonical Paystack reference (unlocked read to discover order_group_id)
        $unlockedPR = PaymentRequest::where('gateway_reference', $verifiedReference)->first();
        if (!$unlockedPR) {
            Log::warning("DeliveryOrderSettlement: PaymentRequest not found for gateway reference '{$verifiedReference}'");
            return [
                'status' => 'not_found',
                'message' => "PaymentRequest not found for gateway reference '{$verifiedReference}'.",
            ];
        }

        if ($unlockedPR->payment_domain !== 'marketplace_delivery') {
            Log::warning("DeliveryOrderSettlement: PaymentRequest #{$unlockedPR->id} is not marketplace_delivery domain.");
            return [
                'status' => 'unsupported_domain',
                'message' => "Payment domain '{$unlockedPR->payment_domain}' not supported by delivery settlement engine.",
            ];
        }

        // 2. Execute Settlement Transaction with Canonical Lock Order: CheckoutIntent -> PaymentRequest
        try {
            $result = DB::transaction(function () use ($verifiedReference, $gatewayData, $unlockedPR) {
                // STEP A: Lock CheckoutIntent FIRST (Canonical Lock Order matching Commit 3)
                $intent = CheckoutIntent::where('order_group_id', $unlockedPR->order_group_id)
                    ->lockForUpdate()
                    ->first();

                // STEP B: Lock PaymentRequest SECOND
                $paymentRequest = PaymentRequest::where('gateway_reference', $verifiedReference)
                    ->lockForUpdate()
                    ->first();

                if (!$paymentRequest) {
                    return ['status' => 'not_found', 'message' => 'PaymentRequest not found under lock.'];
                }

                // Invariant: Assert verified gateway reference matches PaymentRequest.gateway_reference exactly
                if (!hash_equals($verifiedReference, (string) $paymentRequest->gateway_reference)) {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $intent,
                        'other',
                        $gatewayData,
                        "Reference mismatch: verified '{$verifiedReference}' != stored '{$paymentRequest->gateway_reference}'."
                    );
                }

                // IDEMPOTENCY GUARD: Already settled payment
                if ($paymentRequest->is_paid == 1 && $paymentRequest->attempt_status === 'successful') {
                    return [
                        'status' => 'ALREADY_PAID',
                        'is_replayed' => true,
                        'message' => 'Payment has already been successfully settled.',
                        'payment_request' => $paymentRequest,
                        'orders' => $this->getSettledOrderIds($paymentRequest),
                    ];
                }

                if ($intent && $intent->status === 'converted_to_orders') {
                    return [
                        'status' => 'ALREADY_PAID',
                        'is_replayed' => true,
                        'message' => 'CheckoutIntent has already been converted to orders.',
                        'payment_request' => $paymentRequest,
                        'orders' => $this->getSettledOrderIds($paymentRequest),
                    ];
                }

                // ANOMALY CHECK 1: Stale or superseded payment attempt
                if ($paymentRequest->attempt_status !== 'pending') {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $intent,
                        'stale_order_group',
                        $gatewayData,
                        "PaymentRequest attempt_status is '{$paymentRequest->attempt_status}', expected 'pending'."
                    );
                }

                // ANOMALY CHECK 2: Missing CheckoutIntent
                if (!$intent) {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        null,
                        'stale_order_group',
                        $gatewayData,
                        "CheckoutIntent missing for order_group_id '{$paymentRequest->order_group_id}'."
                    );
                }

                // ANOMALY CHECK 3: IDOR Customer Ownership Binding
                if ((int) $intent->customer_id !== (int) $paymentRequest->payer_id) {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $intent,
                        'other',
                        $gatewayData,
                        "Customer mismatch between CheckoutIntent #{$intent->id} and PaymentRequest #{$paymentRequest->id}."
                    );
                }

                // ANOMALY CHECK 4: CheckoutIntent status must be pending
                if ($intent->status !== 'pending') {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $intent,
                        'stale_order_group',
                        $gatewayData,
                        "CheckoutIntent status is '{$intent->status}', expected 'pending'."
                    );
                }

                // ANOMALY CHECK 5: Late Capture on Expired CheckoutIntent
                if ($intent->isExpired()) {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $intent,
                        'late_capture_expired',
                        $gatewayData,
                        "CheckoutIntent #{$intent->id} has expired (expired_at: {$intent->expires_at})."
                    );
                }

                // ANOMALY CHECK 6: Currency Mismatch
                $gatewayCurrency = strtoupper((string) ($gatewayData['currency'] ?? 'NGN'));
                if ($gatewayCurrency !== 'NGN' || strtoupper((string) $intent->currency) !== 'NGN') {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $intent,
                        'currency_mismatch',
                        $gatewayData,
                        "Currency mismatch: gateway sent '{$gatewayCurrency}', expected NGN."
                    );
                }

                // ANOMALY CHECK 7: Exact Integer Kobo Amount Equality (BCMath)
                $intentRaw = trim((string) $intent->total_amount);
                $twoDecimals = bcadd($intentRaw, '0', 2);
                $expectedKobo = (int) bcmul($twoDecimals, '100', 0);
                $paidKobo = (int) ($gatewayData['amount'] ?? 0);

                if ($paidKobo !== $expectedKobo) {
                    return $this->handlePermanentAnomaly(
                        $paymentRequest,
                        $intent,
                        'amount_mismatch',
                        $gatewayData,
                        "Amount mismatch: expected {$expectedKobo} kobo, gateway paid {$paidKobo} kobo."
                    );
                }

                // STEP C: Multi-Vendor Order Creation from Immutable Snapshot
                $createdOrderIds = $this->createOrdersFromDeliverySnapshot($intent, $paymentRequest);

                // STEP D: Atomic State Transitions & Cashback Capture
                $intent->update([
                    'status' => 'converted_to_orders',
                    'active_cart_token' => null,
                ]);

                // [AI] Victorious MARKET V1: Atomic Capture of Reserved Cashback (if used)
                $redemption = CashbackRedemption::where('order_group_id', $intent->order_group_id)
                    ->lockForUpdate()
                    ->first();

                if ($redemption && $redemption->status === 'reserved') {
                    $redemption->update([
                        'status' => 'captured',
                        'captured_at' => now(),
                    ]);

                    $customer = User::where('id', $intent->customer_id)->lockForUpdate()->first();
                    if ($customer) {
                        $customer->decrement('loyalty_point', (float) $redemption->points);

                        // Immutable audit record in loyalty_point_transactions
                        DB::table('loyalty_point_transactions')->insert([
                            'user_id' => $customer->id,
                            'transaction_id' => Str::uuid()->toString(),
                            'credit' => 0.000,
                            'debit' => (float) $redemption->points,
                            'balance' => (float) $customer->fresh()->loyalty_point,
                            'reference' => $intent->order_group_id,
                            'transaction_type' => 'cashback_redemption',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $additional = is_array($paymentRequest->additional_data)
                    ? $paymentRequest->additional_data
                    : json_decode($paymentRequest->additional_data ?? '{}', true);

                $additional['settled_orders'] = $createdOrderIds;
                $additional['settled_at'] = now()->toIso8601String();

                $paymentRequest->update([
                    'attempt_status' => 'successful',
                    'is_paid' => 1,
                    'active_order_group_id' => null,
                    'additional_data' => json_encode($additional),
                ]);

                return [
                    'status' => 'CLAIMED',
                    'is_replayed' => false,
                    'message' => 'Payment settled successfully and vendor orders generated.',
                    'payment_request' => $paymentRequest->fresh(),
                    'orders' => $createdOrderIds,
                    'snapshot' => $intent->checkout_snapshot,
                ];
            });

            // POST-COMMIT ACTIONS (Executed strictly after transaction commits successfully)
            if (($result['status'] ?? '') === 'CLAIMED') {
                $customerId = (int) $result['payment_request']->payer_id;
                $snapshot = $result['snapshot'] ?? [];
                $this->pruneSnapshotCartItems($customerId, $snapshot);
            }

            return $result;
        } catch (PostPaymentStockFailureException $e) {
            // PHASE 1 COMPLETED: Settlement transaction rolled back completely. Zero partial orders or financial holds exist.
            // PHASE 2: Start a NEW independent transaction to persist the reconciliation anomaly record.
            Log::warning("DeliveryOrderSettlement: Post-payment stock failure detected. Entering Phase 2 reconciliation: " . $e->getMessage());

            return $this->persistStockFailureReconciliation($verifiedReference, $gatewayData, $e->getMessage());
        }
    }

    /**
     * Phase 2 Anomaly Persistence for Post-Payment Stock Failure.
     * Executes in a fresh independent transaction after Order creation rollback.
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
                'active_order_group_id' => null,
                'is_paid' => 1,
                'additional_data' => json_encode($additional),
            ]);

            // Release any reserved cashback points back to customer pool
            CashbackRedemption::where('order_group_id', $paymentRequest->order_group_id)
                ->where('status', 'reserved')
                ->update([
                    'status' => 'released',
                    'released_at' => now(),
                ]);

            $capturedNaira = bcdiv((string) ($gatewayData['amount'] ?? 0), '100', 4);

            $reconciliation = PaymentReconciliation::create([
                'case_number' => 'REC-' . Str::orderedUuid()->toString(),
                'gateway_reference' => $verifiedReference,
                'payment_request_id' => $paymentRequest->id,
                'payment_domain' => 'marketplace_delivery',
                'order_group_id' => $paymentRequest->order_group_id,
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
        ?CheckoutIntent $intent,
        string $anomalyType,
        array $gatewayData,
        string $reason
    ): array {
        // Invariant: Only actual checkout-expiry/staleness conditions transition CheckoutIntent -> expired.
        if ($anomalyType === 'late_capture_expired' && $intent) {
            $intent->update([
                'status' => 'expired',
                'active_cart_token' => null,
            ]);
        }

        $additional = is_array($paymentRequest->additional_data)
            ? $paymentRequest->additional_data
            : json_decode($paymentRequest->additional_data ?? '{}', true);

        $additional['failure_reason'] = $anomalyType;
        $additional['anomaly_detail'] = $reason;

        $paymentRequest->update([
            'attempt_status' => 'reconciliation_required',
            'active_order_group_id' => null,
            'is_paid' => 1,
            'additional_data' => json_encode($additional),
        ]);

        // Release any reserved cashback points back to customer pool
        CashbackRedemption::where('order_group_id', $paymentRequest->order_group_id)
            ->where('status', 'reserved')
            ->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

        $capturedNaira = bcdiv((string) ($gatewayData['amount'] ?? 0), '100', 4);

        $reconciliation = PaymentReconciliation::create([
            'case_number' => 'REC-' . Str::orderedUuid()->toString(),
            'gateway_reference' => $paymentRequest->gateway_reference,
            'payment_request_id' => $paymentRequest->id,
            'payment_domain' => 'marketplace_delivery',
            'order_group_id' => $paymentRequest->order_group_id,
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
     * Creates multi-vendor Orders, OrderDetails, OrderStatusHistory, and OrderTransactions
     * derived strictly from the immutable checkout_snapshot.
     */
    protected function createOrdersFromDeliverySnapshot(
        CheckoutIntent $intent,
        PaymentRequest $paymentRequest
    ): array {
        $snapshot = is_array($intent->checkout_snapshot)
            ? $intent->checkout_snapshot
            : json_decode($intent->checkout_snapshot ?? '{}', true);

        $vendors = $snapshot['vendors'] ?? [];
        if (empty($vendors)) {
            throw new \RuntimeException("Immutable checkout_snapshot contains no vendors.");
        }

        $customerId = (int) $intent->customer_id;
        $shippingAddressData = $snapshot['shipping_address'] ?? [];
        $billingAddressData = $snapshot['billing_address'] ?? null;
        $createdOrderIds = [];

        foreach ($vendors as $vendor) {
            $orderId = OrderManager::generateNewOrderID();
            $internalTxRef = OrderManager::generateUniqueOrderID(); // strictly <= 21 chars, preserves VARCHAR(30)
            $verificationCode = random_int(100000, 999999);
            $pickupCode = random_int(100000, 999999);

            // Exact Integer / Decimal String via BCMath (zero float, zero drift, Δ = ₦0.00)
            $subtotal = bcadd((string) ($vendor['subtotal'] ?? '0.00'), '0', 2);
            $shippingCost = bcadd((string) ($vendor['shipping_cost'] ?? '0.00'), '0', 2);
            $orderAmount = bcadd((string) ($vendor['total'] ?? '0.00'), '0', 2);
            $rawCommission = bcdiv(bcmul($subtotal, '10', 4), '100', 4);
            $adminCommission = bcadd($rawCommission, '0', 2);
            $sellerAmount = bcsub($subtotal, $adminCommission, 2);

            // [AI] Phase 11: Extract canonical geography snapshot from vendor group
            $originLgaId = $vendor['origin_lga_id'] ?? null;
            $originLgaName = $vendor['origin_lga_name'] ?? null;
            $originStateName = $vendor['origin_state_name'] ?? null;
            $destinationLgaId = $vendor['destination_lga_id'] ?? null;
            $destinationLgaName = $vendor['destination_lga_name'] ?? null;
            $destinationStateName = $vendor['destination_state_name'] ?? null;
            $authoritativeFee = isset($vendor['shipping_cost']) ? bcadd((string) $vendor['shipping_cost'], '0', 4) : null;
            $estimatedTime = $vendor['estimated_delivery_time'] ?? null;

            $ordersData = [
                'id' => $orderId,
                'verification_code' => $verificationCode,
                'pickup_verification_code' => $pickupCode,
                'customer_id' => $customerId,
                'is_guest' => 0,
                'guest_access_token' => null,
                'seller_id' => $vendor['seller_id'],
                'seller_is' => $vendor['seller_is'],
                'customer_type' => 'customer',
                'payment_status' => 'paid',
                'order_status' => 'confirmed',
                'vendor_settlement_status' => ($vendor['seller_is'] === 'seller') ? 'held' : null,
                'payment_method' => 'paystack',
                'transaction_ref' => $internalTxRef, // strictly internal ID; NEVER the Paystack reference
                'order_group_id' => $intent->order_group_id,
                'discount_amount' => 0.00,
                'discount_type' => null,
                'coupon_code' => null,
                'coupon_discount_bearer' => 'inhouse',
                'order_amount' => $orderAmount,
                'init_order_amount' => $orderAmount,
                'total_tax_amount' => 0.00,
                'tax_type' => 'percent',
                'tax_model' => 'exclude',
                'admin_commission' => $adminCommission,
                'order_type' => 'default_type',
                'shipping_address' => $shippingAddressData['id'] ?? 0,
                'shipping_address_data' => json_encode($shippingAddressData),
                'billing_address' => $billingAddressData['id'] ?? null,
                'billing_address_data' => $billingAddressData ? json_encode($billingAddressData) : null,
                'shipping_responsibility' => 'inhouse_shipping',
                'shipping_cost' => $shippingCost,
                'shipping_method_id' => (int) ($vendor['shipping_method_id'] ?? 0),
                'origin_lga_id' => $originLgaId,
                'origin_lga_name' => $originLgaName,
                'origin_state_name' => $originStateName,
                'destination_lga_id' => $destinationLgaId,
                'destination_lga_name' => $destinationLgaName,
                'destination_state_name' => $destinationStateName,
                'authoritative_delivery_fee' => $authoritativeFee,
                'estimated_delivery_time' => $estimatedTime,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('orders')->insert($ordersData);

            // Record status history (Preserving OrderManager semantics)
            OrderManager::add_order_status_history($orderId, $customerId, 'confirmed', 'customer');

            // Order details & atomic inventory reduction
            foreach ($vendor['items'] as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) $item['quantity'];
                $product = Product::find($productId);

                // Atomic Inventory Deduction Guard:
                if ($product && $product->product_type === 'physical') {
                    $affected = Product::where('id', $productId)
                        ->where('current_stock', '>=', $qty)
                        ->decrement('current_stock', $qty);

                    if ($affected === 0) {
                        $prodName = $product->name ?? "Product #{$productId}";
                        $currentStock = $product->fresh()?->current_stock ?? 0;
                        throw new PostPaymentStockFailureException($prodName, $productId, $qty, $currentStock);
                    }
                }

                DB::table('order_details')->insert([
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'seller_id' => $vendor['seller_id'],
                    'product_details' => json_encode($product ? $product->toArray() : ['name' => $item['product_name'] ?? 'Item']),
                    'qty' => $qty,
                    'price' => (float) ($item['unit_price'] ?? $item['price'] ?? 0.00),
                    'discount' => $item['discount'] ?? 0.00,
                    'tax' => $item['tax'] ?? 0.00,
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

            $createdOrderIds[] = $orderId;
        }

        return $createdOrderIds;
    }

    /**
     * Prunes only the cart items present in the immutable checkout snapshot.
     */
    public function pruneSnapshotCartItems(int $customerId, array $snapshot): int
    {
        $cartIds = [];
        foreach ($snapshot['vendors'] ?? [] as $vendor) {
            foreach ($vendor['items'] ?? [] as $item) {
                if (!empty($item['cart_id'])) {
                    $cartIds[] = (int) $item['cart_id'];
                }
            }
        }

        if (empty($cartIds)) {
            return 0;
        }

        return Cart::where('customer_id', $customerId)
            ->whereIn('id', $cartIds)
            ->delete();
    }

    /**
     * Resolves settled order IDs from PaymentRequest additional_data or Order table.
     */
    protected function getSettledOrderIds(PaymentRequest $paymentRequest): array
    {
        $additional = is_array($paymentRequest->additional_data)
            ? $paymentRequest->additional_data
            : json_decode($paymentRequest->additional_data ?? '{}', true);

        if (!empty($additional['settled_orders'])) {
            return (array) $additional['settled_orders'];
        }

        return Order::where('order_group_id', $paymentRequest->order_group_id)
            ->pluck('id')
            ->toArray();
    }
}
