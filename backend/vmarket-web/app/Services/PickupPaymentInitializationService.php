<?php

namespace App\Services;

use App\Exceptions\InvalidCartException;
use App\Exceptions\InvalidPaymentStateException;
use App\Exceptions\PaymentInitializationException;
use App\Models\CashbackRedemption;
use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\PickupReservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * [AI] Service PickupPaymentInitializationService
 *
 * Handles Paystack payment initialization for inspected & accepted in-shop pickup reservations.
 *
 * Core Invariants:
 * 1. ZERO DB locks held during external Paystack HTTP requests (Decoupled Phase A & Phase B).
 * 2. Shared serialization anchor: User row lock (User::lockForUpdate()) serializes all overlapping
 *    reservation attempts for the same customer.
 * 3. Canonical lock hierarchy: User -> PickupReservation -> PaymentRequest.
 * 4. Exact integer kobo via BCMath (Zero PHP float arithmetic).
 * 5. Bounded TTL: attempt_expires_at = min(now + ttl, PickupReservation.expires_at).
 * 6. Existing pending attempt recovery: Never blindly rotate reference without definitive REFERENCE_NOT_FOUND.
 */
class PickupPaymentInitializationService
{
    /**
     * Duration in seconds an external initialization attempt holds the active lease.
     * Prevents concurrent requests from rotating the reference while an external call is in flight.
     */
    public const INITIALIZATION_LEASE_SECONDS = 45;

    public function __construct(
        protected PaystackInitializationClient $paystackClient
    ) {
    }

    /** [AI] Customer web/mobile review uses backend totals; this call never contacts a gateway. */
    public function quote(int $customerId, string $code, bool $useCashback): array
    {
        return DB::transaction(function () use ($customerId, $code, $useCashback) {
            $customer = User::where('id', $customerId)->lockForUpdate()->first();
            $reservation = PickupReservation::where('customer_id', $customerId)->where('reservation_code', $code)->lockForUpdate()->first();
            if (!$customer || !$reservation || $reservation->status !== 'inspected_accepted' || $reservation->isExpired()) {
                throw new InvalidPaymentStateException('Pickup reservation must be accepted and unexpired before quoting.');
            }
            $attempt = PaymentRequest::where('active_pickup_reservation_id', $reservation->id)->lockForUpdate()->first();
            $additional = $attempt ? json_decode($attempt->additional_data ?? '{}', true) : [];
            if ($attempt) {
                $cashback = (string)($additional['cashback_amount'] ?? '0');
                if (!$useCashback && bccomp($cashback, '0', 2) > 0) {
                    throw new InvalidPaymentStateException('An existing payment attempt must finish before changing funding.');
                }
            } else {
                $cashback = $this->reserveCashbackForPickup($reservation, $customerId, $customer, $useCashback, 'pickup-'.$code, false)['cashback_amount'];
            }
            $snapshot = $reservation->reservation_items ?: [];
            $expires = now()->addMinutes(5)->min($reservation->expires_at);
            $token = Str::random(64);
            $quote = ['currency' => 'NGN', 'merchandise_subtotal' => bcadd((string)($snapshot['subtotal'] ?? '0'), '0', 2),
                'tax_total' => bcadd((string)($snapshot['tax'] ?? '0'), '0', 2), 'shipping_total' => '0.00',
                'gross_amount' => bcadd((string)$reservation->total_amount, '0', 2), 'cashback_amount' => bcadd($cashback, '0', 2),
                'total_amount' => bcsub((string)$reservation->total_amount, $cashback, 2), 'items' => $snapshot['items'] ?? [],
                'expires_at' => $expires->toIso8601String(), 'reservation_code' => $code];
            Cache::put('pickup_quote_'.$token, ['customer_id' => $customerId, 'reservation_id' => (int)$reservation->id,
                'use_cashback' => $useCashback, 'gross_amount' => $quote['gross_amount'], 'cashback_amount' => $quote['cashback_amount']], $expires);
            return ['status' => true, 'quote_token' => $token, 'quote' => $quote];
        });
    }

    /** [AI] Persist gateway responses only against the still-owned lease and current state. */
    protected function finishInitialization(PaymentRequest $original, array $response): array
    {
        $result = DB::transaction(function () use ($original, $response) {
            $customer = User::where('id', $original->payer_id)->lockForUpdate()->first();
            $reservation = PickupReservation::where('id', $original->pickup_reservation_id)->lockForUpdate()->first();
            $payment = PaymentRequest::where('id', $original->id)->lockForUpdate()->firstOrFail();
            if ($payment->attempt_status !== 'pending' || (int)$payment->is_paid === 1) {
                return ['status' => $payment->attempt_status === 'successful' ? 'settled' : $payment->attempt_status,
                    'payment_request' => $payment, 'order_id' => $reservation?->order_id, 'gateway_reference' => $payment->gateway_reference];
            }
            $additional = json_decode($payment->additional_data ?? '{}', true);
            $old = json_decode($original->additional_data ?? '{}', true);
            if (($additional['init_claim_token'] ?? null) !== ($old['init_claim_token'] ?? null)) {
                return ['status' => 'initialization_in_progress', 'payment_request' => $payment, 'gateway_reference' => $payment->gateway_reference];
            }
            if (!$reservation || $reservation->status !== 'inspected_accepted' || $reservation->isExpired() || now()->greaterThanOrEqualTo($payment->attempt_expires_at)) {
                $payment->update(['attempt_status' => 'expired', 'active_pickup_reservation_id' => null]);
                if ($reservation && $customer) { $this->releaseCashbackForPickup($reservation, (int)$customer->id, $customer); }
                return ['status' => 'expired', 'payment_request' => $payment];
            }
            if (($response['status'] ?? '') === 'SUCCESS') {
                $additional['authorization_url'] = $response['authorization_url'];
                $additional['access_code'] = $response['access_code'] ?? null;
                $additional['init_claim_expires_at'] = null;
                $payment->update(['additional_data' => json_encode($additional)]);
                return ['status' => 'success', 'payment_request' => $payment, 'is_replayed' => false,
                    'gateway_reference' => $payment->gateway_reference, 'authorization_url' => $response['authorization_url']];
            }
            if (($response['status'] ?? '') === 'GATEWAY_REJECTED') {
                $additional['failure_reason'] = $response['message'] ?? 'Gateway rejected initialization';
                $additional['init_claim_expires_at'] = null;
                $payment->update(['attempt_status' => 'failed', 'active_pickup_reservation_id' => null, 'additional_data' => json_encode($additional)]);
                if ($customer) { $this->releaseCashbackForPickup($reservation, (int)$customer->id, $customer); }
                return ['status' => 'failed', 'message' => $additional['failure_reason'], 'payment_request' => $payment];
            }
            return ['status' => 'ambiguous_transport', 'is_ambiguous' => true, 'payment_request' => $payment, 'gateway_reference' => $payment->gateway_reference];
        });
        return $result;
    }

    /**
     * Initiates or replays a payment attempt for an inspected & accepted pickup reservation.
     *
     * @param int|User $customer Authenticated customer instance or ID
     * @param string|int|PickupReservation $reservationInput Reservation model, ID, or reservation_code
     * @param bool $useCashback Whether to redeem Victorious Cashback points
     * @param int $ttlMinutes Attempt validity duration in minutes (default 30)
     * @param string|null $callbackUrl Optional custom callback URL
     * @return array
     */
    public function initializePayment(
        int|User $customer,
        string|int|PickupReservation $reservationInput,
        bool $useCashback = false,
        int $ttlMinutes = 30,
        ?string $callbackUrl = null,
        ?string $quoteToken = null
    ): array {
        // 1. Resolve Authenticated Customer
        $customerId = $customer instanceof User ? (int) $customer->id : (int) $customer;
        if ($customerId <= 0) {
            throw new InvalidCartException("Authentication required: invalid customer ID [{$customerId}].");
        }

        $customerRecord = User::find($customerId);
        if (!$customerRecord) {
            throw new InvalidCartException("Customer #{$customerId} does not exist.");
        }

        $ttlMinutes = max(5, $ttlMinutes);
        $callbackUrl = $callbackUrl ?: route('paystack.callback');
        $confirmed = $quoteToken ? Cache::get('pickup_quote_'.$quoteToken) : null;
        if ($quoteToken && (!$confirmed || $confirmed['customer_id'] !== $customerId || $confirmed['use_cashback'] !== $useCashback)) {
            throw new InvalidPaymentStateException('Pickup quote expired or changed. Request a new quote.');
        }

        // =========================================================================
        // PHASE A: Short Database Transaction (Zero External Network Calls)
        // =========================================================================
        $phaseAResult = DB::transaction(function () use ($customerId, $customerRecord, $reservationInput, $useCashback, $ttlMinutes, $confirmed) {
            // Step 1: Pessimistic Row Lock on Customer Serialization Anchor
            $lockedCustomer = User::where('id', $customerId)->lockForUpdate()->first();

            // Step 2: Fetch and Lock PickupReservation (IDOR Protected)
            $resQuery = PickupReservation::query()->lockForUpdate();
            if ($reservationInput instanceof PickupReservation) {
                $resQuery->where('id', $reservationInput->id);
            } elseif (is_numeric($reservationInput)) {
                $resQuery->where('id', (int) $reservationInput);
            } else {
                $resQuery->where('reservation_code', (string) $reservationInput);
            }

            $reservation = $resQuery->first();
            if (!$reservation) {
                throw new InvalidCartException("Pickup reservation not found.");
            }

            if ((int) $reservation->customer_id !== $customerId) {
                throw new InvalidCartException("IDOR Violation: Reservation does not belong to customer #{$customerId}.");
            }
            if ($confirmed && $confirmed['reservation_id'] !== (int)$reservation->id) {
                throw new InvalidPaymentStateException('Pickup quote belongs to another reservation.');
            }

            if ($reservation->order_id !== null || $reservation->status === 'order_placed') {
                $existingOrder = Order::find($reservation->order_id);
                return [
                    'action' => 'SETTLED_INTERNALLY',
                    'is_replayed' => true,
                    'status' => 'settled',
                    'order_id' => $reservation->order_id,
                    'verification_code' => (string) ($existingOrder?->pickup_verification_code ?? ''),
                    'paid_amount' => '0.00',
                    'cashback_redeemed' => (string) ($existingOrder?->discount_amount ?? $reservation->total_amount),
                    'gateway_reference' => (string) ($existingOrder?->transaction_ref ?? ''),
                    'authorization_url' => null,
                    'message' => 'Pickup order already placed and settled.',
                ];
            }

            if ($reservation->status !== 'inspected_accepted') {
                throw new InvalidPaymentStateException(
                    "Pickup reservation cannot be paid: status is '{$reservation->status}', expected 'inspected_accepted'."
                );
            }

            if ($reservation->isExpired()) {
                $reservation->update([
                    'status' => 'expired',
                    'active_reservation_token' => null,
                ]);
                return [
                    'action' => 'EXPIRED',
                    'reservation' => $reservation,
                ];
            }

            // Step 3: Exact Integer Kobo Amount via BCMath & Fail-Closed NGN
            $rawAmount = trim((string) $reservation->total_amount);
            if (!preg_match('/^\d+(\.\d{1,4})?$/', $rawAmount)) {
                throw new InvalidPaymentStateException("Malformed total_amount '{$rawAmount}' on PickupReservation #{$reservation->id}.");
            }

            $twoDecimals = bcadd($rawAmount, '0', 2);
            $fourDecimals = bcadd($rawAmount, '0', 4);
            if (bccomp($fourDecimals, $twoDecimals, 4) !== 0) {
                throw new InvalidPaymentStateException("Malformed total_amount '{$rawAmount}': non-zero sub-kobo fraction detected.");
            }

            $amountKoboString = bcmul($twoDecimals, '100', 0);
            if (bccomp($amountKoboString, (string) PHP_INT_MAX) > 0) {
                throw new InvalidPaymentStateException("Amount exceeds maximum safe integer range.");
            }
            $amountKobo = (int) $amountKoboString;
            if ($amountKobo <= 0) {
                throw new InvalidPaymentStateException("Payment amount must be greater than zero.");
            }

            // Step 3B: Active Attempt Management on THIS reservation (REPLAY BEFORE RESERVE)
            $existingActive = PaymentRequest::where('active_pickup_reservation_id', $reservation->id)
                ->lockForUpdate()
                ->first();

            if ($existingActive) {
                if (now()->greaterThanOrEqualTo($existingActive->attempt_expires_at)) {
                    // Stale active attempt expired -> lazily clear active unique token under lock
                    $existingActive->update([
                        'attempt_status' => 'expired',
                        'active_pickup_reservation_id' => null,
                    ]);
                    // [AI] Restore previously deducted cashback points if attempt expired
                    $this->releaseCashbackForPickup($reservation, $customerId, $lockedCustomer);
                    $existingActive = null;
                } else {
                    // Active attempt is still valid!
                    $additional = is_array($existingActive->additional_data)
                        ? $existingActive->additional_data
                        : json_decode($existingActive->additional_data ?? '{}', true);

                    if (!empty($additional['authorization_url'])) {
                        return [
                            'action' => 'REPLAY_CACHED',
                            'is_replayed' => true,
                            'payment_request' => $existingActive,
                            'authorization_url' => $additional['authorization_url'],
                            'gateway_reference' => $existingActive->gateway_reference,
                        ];
                    }

                    // Ambiguous attempt exists without authorization_url
                    // Check initialization lease: Is another external initialization in-flight?
                    $claimExpiresAtStr = $additional['init_claim_expires_at'] ?? null;
                    $claimExpiresAt = $claimExpiresAtStr ? Carbon::parse($claimExpiresAtStr) : null;
                    $isLeaseActive = $claimExpiresAt && now()->isBefore($claimExpiresAt);

                    if ($isLeaseActive) {
                        // Gateway initialization is currently IN-FLIGHT by another request!
                        // Do NOT rotate reference. Do NOT query gateway verify yet.
                        return [
                            'action' => 'WAIT_IN_FLIGHT',
                            'payment_request' => $existingActive,
                            'gateway_reference' => $existingActive->gateway_reference,
                            'lease_expires_at' => $claimExpiresAt,
                        ];
                    }

                    // Lease is demonstrably STALE or missing! Acquire recovery lease under lock:
                    $now = now();
                    $additional['init_claimed_at'] = $now->toIso8601String();
                    $additional['init_claim_expires_at'] = $now->copy()->addSeconds(self::INITIALIZATION_LEASE_SECONDS)->toIso8601String();
                    $additional['init_claim_token'] = Str::random(32);
                    $existingActive->update([
                        'additional_data' => json_encode($additional),
                    ]);

                    return [
                        'action' => 'RECOVER_EXISTING',
                        'payment_request' => $existingActive,
                        'reservation' => $reservation,
                        'amount_kobo' => (int) bcmul(bcadd((string) $existingActive->payment_amount, '0', 2), '100', 0),
                        'customer' => $customerRecord,
                        'ttl_minutes' => $ttlMinutes,
                    ];
                }
            }

            // Step 4: Overlap Concurrency Guard
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
                // Find all reservations belonging to this customer
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
                    if (!empty($overlap)) {
                        // Check if other reservation already placed an order
                        if ($otherRes->order_id !== null) {
                            throw new InvalidPaymentStateException(
                                "One or more items in this reservation have already been ordered in Reservation #{$otherRes->id}."
                            );
                        }

                        // Check if other reservation has an active unexpired payment request
                        $activeOtherPR = PaymentRequest::where('active_pickup_reservation_id', $otherRes->id)
                            ->where('attempt_expires_at', '>', now())
                            ->first();

                        if ($activeOtherPR) {
                            if ($activeOtherPR->is_paid == 1) {
                                throw new InvalidPaymentStateException(
                                    "An overlapping reservation (#{$otherRes->id}) has already been paid and is currently settling."
                                );
                            }
                            if ($activeOtherPR->attempt_status === 'pending') {
                                throw new InvalidPaymentStateException(
                                    "Another reservation (#{$otherRes->id}) containing identical items is currently pending payment."
                                );
                            }
                        }
                    }
                }
            }

            // Step 5: Cashback Reserve (Spend/Redeem Path - fresh attempt creation only)
            $cashbackReserved = $this->reserveCashbackForPickup(
                $reservation,
                $customerId,
                $lockedCustomer,
                $useCashback,
                'pickup-' . $reservation->reservation_code
            );

            // Adjust payment amount after cashback discount
            $cashbackAmount = $cashbackReserved['cashback_amount'] ?? '0.00';
            $discountedNaira = bcsub($twoDecimals, $cashbackAmount, 2);
            if ($confirmed && (bccomp($confirmed['gross_amount'], $twoDecimals, 2) !== 0 || bccomp($confirmed['cashback_amount'], $cashbackAmount, 2) !== 0)) {
                throw new InvalidPaymentStateException('Pickup funding changed. Request and confirm a new quote.');
            }
            if (bccomp($discountedNaira, '0.00', 2) < 0) {
                $discountedNaira = '0.00';
            }

            // [AI] 100% Fully Reward-Funded Pickup Order:
            if (bccomp($discountedNaira, '0.00', 2) === 0 && bccomp($cashbackAmount, '0.00', 2) > 0) {
                $now = now();
                $ttlTarget = $now->copy()->addMinutes($ttlMinutes);
                $resExpiry = Carbon::parse($reservation->expires_at);
                $boundedExpiry = $ttlTarget->isBefore($resExpiry) ? $ttlTarget : $resExpiry;

                $gatewayReference = 'CB-' . substr(str_replace('-', '', Str::orderedUuid()->toString()), 0, 18);

                $newPaymentRequest = PaymentRequest::create([
                    'id' => Str::orderedUuid()->toString(),
                    'payer_id' => (string) $customerId,
                    'payment_amount' => '0.00',
                    'currency_code' => 'NGN',
                    'payment_method' => 'cashback',
                    'payment_platform' => 'web',
                    'payment_domain' => 'marketplace_pickup',
                    'gateway_reference' => $gatewayReference,
                    'attempt_status' => 'pending',
                    'is_paid' => 0,
                    'pickup_reservation_id' => $reservation->id,
                    'active_pickup_reservation_id' => $reservation->id,
                    'attempt_expires_at' => $boundedExpiry,
                    'additional_data' => json_encode([
                        'payment_domain' => 'marketplace_pickup',
                        'reservation_id' => $reservation->id,
                        'reservation_code' => $reservation->reservation_code,
                        'customer_id' => $customerId,
                        'seller_id' => $reservation->seller_id,
                        'shop_id' => $reservation->shop_id,
                        'gross_amount' => $twoDecimals,
                        'cashback_amount' => $cashbackAmount,
                        'amount_kobo' => 0,
                        'created_at' => $now->toIso8601String(),
                        'cashback_reservation' => [
                            'redemption_id' => $cashbackReserved['redemption_id'],
                            'points' => $cashbackReserved['points'],
                            'cashback_amount' => $cashbackReserved['cashback_amount'],
                        ],
                    ]),
                ]);

                return [
                    'action' => 'SETTLE_INTERNALLY',
                    'payment_request' => $newPaymentRequest,
                    'reservation' => $reservation,
                    'customer' => $customerRecord,
                    'gateway_reference' => $gatewayReference,
                    'gross_amount' => $twoDecimals,
                    'cashback_amount' => $cashbackAmount,
                ];
            }

            // Recalculate kobo amount after discount
            if (bccomp($cashbackAmount, '0.00', 2) > 0) {
                $amountKoboString = bcmul($discountedNaira, '100', 0);
                $amountKobo = (int) $amountKoboString;
            }

            // Step 6: Create Fresh PaymentRequest Attempt with Initial Claim Lease
            $now = now();
            $ttlTarget = $now->copy()->addMinutes($ttlMinutes);
            $resExpiry = Carbon::parse($reservation->expires_at);
            $boundedExpiry = $ttlTarget->isBefore($resExpiry) ? $ttlTarget : $resExpiry;

            $gatewayReference = 'VM-' . Str::orderedUuid()->toString();
            $claimExpiresAt = $now->copy()->addSeconds(self::INITIALIZATION_LEASE_SECONDS);

            $newPaymentRequest = PaymentRequest::create([
                'id' => Str::orderedUuid()->toString(),
                'payer_id' => (string) $customerId,
                'payment_amount' => $discountedNaira, // [AI] Authoritative net gateway payable amount
                'currency_code' => 'NGN',
                'payment_method' => 'paystack',
                'payment_platform' => 'web',
                'payment_domain' => 'marketplace_pickup',
                'gateway_reference' => $gatewayReference,
                'attempt_status' => 'pending',
                'is_paid' => 0,
                'pickup_reservation_id' => $reservation->id,
                'active_pickup_reservation_id' => $reservation->id,
                'attempt_expires_at' => $boundedExpiry,
                'additional_data' => json_encode([
                    'payment_domain' => 'marketplace_pickup',
                    'reservation_id' => $reservation->id,
                    'reservation_code' => $reservation->reservation_code,
                    'customer_id' => $customerId,
                    'seller_id' => $reservation->seller_id,
                    'shop_id' => $reservation->shop_id,
                    'gross_amount' => $twoDecimals,
                    'cashback_amount' => $cashbackAmount,
                    'amount_kobo' => $amountKobo,
                    'created_at' => $now->toIso8601String(),
                    'init_claimed_at' => $now->toIso8601String(),
                    'init_claim_expires_at' => $claimExpiresAt->toIso8601String(),
                    'init_claim_token' => Str::random(32),
                ]),
            ]);

            // Store cashback reservation details in additional_data if reserved
            if ($cashbackReserved['reserved']) {
                $additional = json_decode($newPaymentRequest->additional_data, true);
                $additional['cashback_reservation'] = [
                    'redemption_id' => $cashbackReserved['redemption_id'],
                    'points' => $cashbackReserved['points'],
                    'cashback_amount' => $cashbackReserved['cashback_amount'],
                ];
                $newPaymentRequest->update(['additional_data' => json_encode($additional)]);
            }

            return [
                'action' => 'INITIALIZE_NEW',
                'payment_request' => $newPaymentRequest,
                'reservation' => $reservation,
                'amount_kobo' => $amountKobo,
                'customer' => $customerRecord,
                'gateway_reference' => $gatewayReference,
                'cashback_reserved' => $cashbackReserved,
            ];
        });

        // =========================================================================
        // PHASE B: External Gateway Execution (Zero DB Locks Held)
        // =========================================================================
        $action = $phaseAResult['action'];

        if ($action === 'EXPIRED') {
            throw new InvalidPaymentStateException("Pickup reservation has expired. Please create a new reservation.");
        }

        if ($action === 'REPLAY_CACHED') {
            return [
                'status' => 'success',
                'is_replayed' => true,
                'payment_request' => $phaseAResult['payment_request'],
                'authorization_url' => $phaseAResult['authorization_url'],
                'gateway_reference' => $phaseAResult['gateway_reference'],
            ];
        }

        if ($action === 'WAIT_IN_FLIGHT') {
            $paymentRequest = $phaseAResult['payment_request'];
            $gatewayReference = $phaseAResult['gateway_reference'];

            // Bounded poll: up to 2.5 seconds (8 iterations x 300ms) for in-flight initialization to complete
            for ($i = 0; $i < 8; $i++) {
                usleep(300000); // 300ms
                $freshPR = PaymentRequest::where('id', $paymentRequest->id)->first();
                if ($freshPR) {
                    $add = json_decode($freshPR->additional_data ?? '{}', true);
                    if (!empty($add['authorization_url'])) {
                        return [
                            'status' => 'success',
                            'is_replayed' => true,
                            'payment_request' => $freshPR,
                            'authorization_url' => $add['authorization_url'],
                            'gateway_reference' => $gatewayReference,
                        ];
                    }
                }
            }

            // Still in flight after bounded wait -> return status indicating initialization is in progress
            return [
                'status' => 'initialization_in_progress',
                'is_in_flight' => true,
                'message' => 'Paystack gateway initialization is currently in progress for this reservation. Please retry in a moment.',
                'payment_request' => $paymentRequest->fresh(),
                'gateway_reference' => $gatewayReference,
            ];
        }

        if ($action === 'RECOVER_EXISTING') {
            $paymentRequest = $phaseAResult['payment_request'];
            if ($paymentRequest->payment_method === 'cashback' || bccomp((string)$paymentRequest->payment_amount, '0.00', 2) === 0) {
                // [AI] Recover interrupted internal cashback settlement through shared authoritative pipeline!
                $settlementService = new PickupOrderSettlementService();
                $settlementResult = $settlementService->settleVerifiedPayment(
                    $paymentRequest->gateway_reference,
                    [
                        'status' => 'success',
                        'amount' => 0,
                        'currency' => 'NGN',
                        'reference' => $paymentRequest->gateway_reference,
                    ]
                );

                if (($settlementResult['status'] ?? '') === 'CLAIMED' || ($settlementResult['status'] ?? '') === 'ALREADY_SETTLED') {
                    $orderId = $settlementResult['order_id'];
                    $order = Order::find($orderId);
                    return [
                        'action' => 'SETTLED_INTERNALLY',
                        'is_replayed' => true,
                        'status' => 'settled',
                        'order_id' => $orderId,
                        'verification_code' => (string) ($order?->pickup_verification_code ?? ($settlementResult['verification_code'] ?? '')),
                        'paid_amount' => '0.00',
                        'cashback_redeemed' => (string) ($order?->discount_amount ?? $phaseAResult['reservation']->total_amount),
                        'gateway_reference' => $paymentRequest->gateway_reference,
                        'authorization_url' => null,
                        'message' => 'Pickup order settled internally upon recovery of interrupted attempt.',
                    ];
                }
            }

            return $this->recoverAmbiguousAttempt(
                $phaseAResult['payment_request'],
                $phaseAResult['reservation'],
                $phaseAResult['customer'],
                $phaseAResult['amount_kobo'],
                $phaseAResult['ttl_minutes'],
                $callbackUrl
            );
        }

        if ($action === 'SETTLE_INTERNALLY') {
            // Settle immediately using the authoritative SHARED PickupOrderSettlementService pipeline!
            $settlementService = new PickupOrderSettlementService();
            $settlementResult = $settlementService->settleVerifiedPayment(
                $phaseAResult['gateway_reference'],
                [
                    'status' => 'success',
                    'amount' => 0,
                    'currency' => 'NGN',
                    'reference' => $phaseAResult['gateway_reference'],
                ]
            );

            if (($settlementResult['status'] ?? '') === 'CLAIMED' || ($settlementResult['status'] ?? '') === 'ALREADY_SETTLED') {
                $orderId = $settlementResult['order_id'];
                $order = Order::find($orderId);
                return [
                    'action' => 'SETTLED_INTERNALLY',
                    'status' => 'settled',
                    'order_id' => $orderId,
                    'verification_code' => (string) ($order?->pickup_verification_code ?? ($settlementResult['verification_code'] ?? '')),
                    'paid_amount' => '0.00',
                    'cashback_redeemed' => $phaseAResult['gross_amount'],
                    'gateway_reference' => $phaseAResult['gateway_reference'],
                    'authorization_url' => null,
                    'message' => 'Pickup order settled internally with 100% Victorious Cashback rewards.',
                ];
            }

            throw new PaymentInitializationException("Internal settlement failed: " . ($settlementResult['message'] ?? 'Unknown error'));
        }

        if ($action === 'SETTLED_INTERNALLY') {
            return $phaseAResult;
        }

        // Action === INITIALIZE_NEW
        $paymentRequest = $phaseAResult['payment_request'];
        $customerRecord = $phaseAResult['customer'];
        $amountKobo = $phaseAResult['amount_kobo'];
        $gatewayReference = $phaseAResult['gateway_reference'];

        $metadata = [
            'payment_id' => $paymentRequest->id,
            'payment_domain' => 'marketplace_pickup',
            'reservation_id' => $paymentRequest->active_pickup_reservation_id,
            'customer_id' => $customerId,
        ];

        $customerEmail = !empty($customerRecord->email) ? $customerRecord->email : "customer_{$customerId}@victoriousmarket.local";

        $initData = $this->paystackClient->initializeTransaction(
            $customerEmail,
            $amountKobo,
            $gatewayReference,
            $callbackUrl,
            $metadata
        );

        return $this->finishInitialization($paymentRequest, $initData);
    }

    /**
     * Recovers an existing pending attempt without authorization URL.
     * Enforces the mandatory recovery rule:
     * Only a definitive REFERENCE_NOT_FOUND allows rotating to a new reference.
     */
    protected function recoverAmbiguousAttempt(
        PaymentRequest $paymentRequest,
        PickupReservation $reservation,
        User $customerRecord,
        int $amountKobo,
        int $ttlMinutes,
        string $callbackUrl
    ): array {
        $reference = $paymentRequest->gateway_reference;
        $verifyData = $this->paystackClient->verifyExistingTransaction($reference);

        switch ($verifyData['class']) {
            case 'REFERENCE_NOT_FOUND':
                // Gateway definitively has no record of this reference!
                // Safely mark old attempt failed, release token, and create fresh attempt in short DB transaction.
                $newAttempt = DB::transaction(function () use ($paymentRequest, $reservation, $customerRecord, $amountKobo, $ttlMinutes) {
                    User::where('id', $customerRecord->id)->lockForUpdate()->first();
                    $freshReservation = PickupReservation::where('id', $reservation->id)->lockForUpdate()->first();
                    $pr = PaymentRequest::where('id', $paymentRequest->id)->lockForUpdate()->first();
                    $originalAdd = json_decode($paymentRequest->additional_data ?? '{}', true);
                    $currentAdd = $pr ? json_decode($pr->additional_data ?? '{}', true) : [];
                    if (!$freshReservation || $freshReservation->status !== 'inspected_accepted' || $freshReservation->isExpired() || !$pr || $pr->attempt_status !== 'pending' || (int)$pr->is_paid === 1 || now()->greaterThanOrEqualTo($pr->attempt_expires_at) || ($currentAdd['init_claim_token'] ?? null) !== ($originalAdd['init_claim_token'] ?? null)) {
                        throw new InvalidPaymentStateException('Pickup attempt changed during gateway recovery. Poll its status.');
                    }

                    if ($pr && $pr->attempt_status === 'pending') {
                        $add = json_decode($pr->additional_data ?? '{}', true);
                        $add['failure_reason'] = 'REFERENCE_NOT_FOUND_ON_GATEWAY';
                        $add['closed_at'] = now()->toIso8601String();
                        $pr->update([
                            'attempt_status' => 'failed',
                            'active_pickup_reservation_id' => null,
                            'additional_data' => json_encode($add),
                        ]);
                    }

                    $now = now();
                    $ttlTarget = $now->copy()->addMinutes($ttlMinutes);
                    $resExpiry = Carbon::parse($reservation->expires_at);
                    $boundedExpiry = $ttlTarget->isBefore($resExpiry) ? $ttlTarget : $resExpiry;

                    $newRef = 'VM-' . Str::orderedUuid()->toString();

                    return PaymentRequest::create([
                        'id' => Str::orderedUuid()->toString(),
                        'payer_id' => (string) $customerRecord->id,
                        'payment_amount' => bcdiv((string) $amountKobo, '100', 2),
                        'currency_code' => 'NGN',
                        'payment_method' => 'paystack',
                        'payment_platform' => 'web',
                        'payment_domain' => 'marketplace_pickup',
                        'gateway_reference' => $newRef,
                        'attempt_status' => 'pending',
                        'is_paid' => 0,
                        'pickup_reservation_id' => $reservation->id,
                        'active_pickup_reservation_id' => $reservation->id,
                        'attempt_expires_at' => $boundedExpiry,
                        'additional_data' => json_encode([
                            'payment_domain' => 'marketplace_pickup',
                            'reservation_id' => $reservation->id,
                            'reservation_code' => $reservation->reservation_code,
                            'customer_id' => $customerRecord->id,
                            'seller_id' => $reservation->seller_id,
                            'shop_id' => $reservation->shop_id,
                            'amount_kobo' => $amountKobo,
                            'gross_amount' => $add['gross_amount'] ?? (string)$reservation->total_amount,
                            'cashback_amount' => $add['cashback_amount'] ?? '0.00',
                            'cashback_reservation' => $add['cashback_reservation'] ?? null,
                            'supersedes_attempt_id' => $paymentRequest->id,
                            'created_at' => $now->toIso8601String(),
                            'init_claimed_at' => $now->toIso8601String(),
                            'init_claim_expires_at' => $now->copy()->addSeconds(self::INITIALIZATION_LEASE_SECONDS)->toIso8601String(),
                            'init_claim_token' => Str::random(32),
                        ]),
                    ]);
                });

                // Initialize the new attempt with Paystack outside DB transaction
                $metadata = [
                    'payment_id' => $newAttempt->id,
                    'payment_domain' => 'marketplace_pickup',
                    'reservation_id' => $reservation->id,
                    'customer_id' => $customerRecord->id,
                ];

                $customerEmail = !empty($customerRecord->email) ? $customerRecord->email : "customer_{$customerRecord->id}@victoriousmarket.local";

                $initData = $this->paystackClient->initializeTransaction(
                    $customerEmail,
                    $amountKobo,
                    $newAttempt->gateway_reference,
                    $callbackUrl,
                    $metadata
                );

                return $this->finishInitialization($newAttempt, $initData);

            case 'SUCCESS':
                // [AI] Verified captured recovery uses the same settlement/reconciliation pipeline.
                $settlement = (new PickupOrderSettlementService())->settleVerifiedPayment($reference, $verifyData['data'] ?? []);
                return ['status' => in_array($settlement['status'] ?? '', ['CLAIMED', 'ALREADY_SETTLED']) ? 'settled' : ($settlement['status'] ?? 'reconciliation_required'),
                    'is_replayed' => true, 'order_id' => $settlement['order_id'] ?? null, 'payment_request' => $paymentRequest->fresh(), 'gateway_reference' => $reference];
            case 'NON_FINAL':
                // Transaction exists on Paystack! Safely reuse existing attempt without rotating reference.
                return [
                    'status' => 'pending_on_gateway',
                    'is_replayed' => true,
                    'payment_request' => $paymentRequest,
                    'gateway_reference' => $reference,
                    'gateway_status' => $verifyData['class'],
                ];

            case 'GATEWAY_FAILURE':
                return $this->finishInitialization($paymentRequest, ['status' => 'GATEWAY_REJECTED', 'message' => 'Gateway terminal failure']);

            default:
                // Ambiguous / Transport / HTTP error during verification -> preserve pending state and reference
                return [
                    'status' => 'ambiguous_transport',
                    'message' => 'Paystack verification ambiguous: ' . ($verifyData['class'] ?? 'UNKNOWN'),
                    'payment_request' => $paymentRequest,
                    'gateway_reference' => $reference,
                ];
        }
    }

    /**
     * Reserves Victorious Cashback points for a pickup payment (spend/redeem path).
     *
     * MUST be called inside an existing DB::transaction() with User already locked via lockForUpdate().
     *
     * @param PickupReservation $reservation
     * @param int $customerId
     * @param User $lockedCustomer User row already locked by caller
     * @param bool $useCashback
     * @param string $orderGroupId
     * @return array{reserved: bool, points: string, cashback_amount: string, redemption_id: int|null}
     */
    protected function reserveCashbackForPickup(
        PickupReservation $reservation,
        int $customerId,
        User $lockedCustomer,
        bool $useCashback,
        string $orderGroupId,
        bool $persist = true
    ): array {
        // Default: no cashback reserved
        $defaultResult = [
            'reserved' => false,
            'points' => '0.0000',
            'cashback_amount' => '0.00',
            'redemption_id' => null,
        ];

        if (!$useCashback) {
            return $defaultResult;
        }

        // Check loyalty system enabled
        $loyaltyStatus = (int) (getWebConfig(name: 'loyalty_point_status') ?: 0);
        if ($loyaltyStatus !== 1) {
            Log::info("[AI] PickupPayment: Loyalty disabled, no cashback reserved for Reservation #{$reservation->id}.");
            return $defaultResult;
        }

        // Load config
        $exchangeRate = (float) (getWebConfig(name: 'loyalty_point_exchange_rate') ?: 1.0);
        $maxCapPercentage = (float) (getWebConfig(name: 'loyalty_point_max_order_redemption_percentage') ?? 100.0);
        $minPoint = (float) (getWebConfig(name: 'loyalty_point_minimum_point') ?: 0.0);

        // Calculate effective available points (excluding already reserved points from other checkouts)
        $activeReservedPoints = CashbackRedemption::where('customer_id', $customerId)
            ->where('status', 'reserved')
            ->lockForUpdate()
            ->sum('points') ?: '0.0000';

        $userPoints = (string) ($lockedCustomer->loyalty_point ?? '0.0000');
        $effectiveAvailable = bcsub($userPoints, (string) $activeReservedPoints, 4);
        if (bccomp($effectiveAvailable, '0.0000', 4) < 0) {
            $effectiveAvailable = '0.0000';
        }

        // Check minimum point threshold
        if (bccomp($effectiveAvailable, (string) $minPoint, 4) < 0) {
            Log::info("[AI] PickupPayment: Customer #{$customerId} has {$effectiveAvailable} pts, below minimum {$minPoint}. No cashback reserved.");
            return $defaultResult;
        }

        // Calculate maximum discount allowed (e.g. 100% of reservation total)
        $snapshot = $reservation->reservation_items ?: [];
        $reservationTotal = bcadd((string)($snapshot['subtotal'] ?? '0'), '0', 2);
        $maxNairaDiscount = bcmul($reservationTotal, bcdiv((string) $maxCapPercentage, '100', 4), 2);

        // Convert customer's effective points to Naira
        $pointsInNaira = bcmul($effectiveAvailable, (string) $exchangeRate, 2);
        // [AI] New funding cannot consume expired or unbacked aggregate points before gateway I/O.
        $coverage = '0.00';
        $lots = \App\Models\CustomerCashbackLedger::where('customer_id', $customerId)->where('status', 'available')
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->lockForUpdate()->get();
        foreach ($lots as $lot) { $coverage = bcadd($coverage, (string)$lot->getRawOriginal('cashback_amount'), 2); }
        $heldMoney = CashbackRedemption::where('customer_id', $customerId)->where('status', 'reserved')->sum('cashback_amount');
        $coverage = bcsub($coverage, (string)$heldMoney, 2);
        if (bccomp($coverage, '0', 2) < 0) { $coverage = '0.00'; }
        if (bccomp($pointsInNaira, $coverage, 2) > 0) { $pointsInNaira = $coverage; }

        // Actual cashback discount: min(pointsInNaira, maxNairaDiscount)
        $cashbackAmount = (bccomp($pointsInNaira, $maxNairaDiscount, 2) > 0) ? $maxNairaDiscount : $pointsInNaira;

        // Must be at least ₦0.01 to reserve
        if (bccomp($cashbackAmount, '0.01', 2) < 0) {
            return $defaultResult;
        }

        // Exact points corresponding to the cashback amount
        $pointsToReserve = bcdiv($cashbackAmount, (string) $exchangeRate, 4);

        // Create CashbackRedemption reservation record (points held in status 'reserved' without premature balance deduction)
        $redemption = $persist ? CashbackRedemption::create([
            'customer_id' => $customerId,
            'checkout_intent_id' => null, // delivery FK; null for pickup
            'pickup_reservation_id' => $reservation->id,
            'order_group_id' => $orderGroupId,
            'points' => $pointsToReserve,
            'cashback_amount' => $cashbackAmount,
            'status' => 'reserved',
        ]) : null;

        Log::info("[AI] PickupPayment: Reserved {$pointsToReserve} pts (₦{$cashbackAmount}) from customer #{$customerId} " .
            "for Reservation #{$reservation->id}.");

        return [
            'reserved' => true,
            'points' => $pointsToReserve,
            'cashback_amount' => $cashbackAmount,
            'redemption_id' => $redemption?->id,
        ];
    }

    /**
     * [AI] Restores reserved cashback points when an active pickup payment attempt expires or is superseded.
     */
    protected function releaseCashbackForPickup(PickupReservation $reservation, int $customerId, User $lockedCustomer): void
    {
        $activeRedemptions = CashbackRedemption::where('pickup_reservation_id', $reservation->id)
            ->where('status', 'reserved')
            ->lockForUpdate()
            ->get();

        foreach ($activeRedemptions as $redemption) {
            $redemption->release();
        }
    }
}
