<?php

namespace App\Services;

use App\Exceptions\InvalidCartException;
use App\Exceptions\InvalidPaymentStateException;
use App\Exceptions\PaymentInitializationException;
use App\Models\PaymentRequest;
use App\Models\PickupReservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

    /**
     * Initiates or replays a payment attempt for an inspected & accepted pickup reservation.
     *
     * @param int|User $customer Authenticated customer instance or ID
     * @param string|int|PickupReservation $reservationInput Reservation model, ID, or reservation_code
     * @param int $ttlMinutes Attempt validity duration in minutes (default 30)
     * @param string|null $callbackUrl Optional custom callback URL
     * @return array
     */
    public function initializePayment(
        int|User $customer,
        string|int|PickupReservation $reservationInput,
        int $ttlMinutes = 30,
        ?string $callbackUrl = null
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
        $callbackUrl = $callbackUrl ?: url('/paystack/callback');

        // =========================================================================
        // PHASE A: Short Database Transaction (Zero External Network Calls)
        // =========================================================================
        $phaseAResult = DB::transaction(function () use ($customerId, $customerRecord, $reservationInput, $ttlMinutes) {
            // Step 1: Pessimistic Row Lock on Customer Serialization Anchor
            User::where('id', $customerId)->lockForUpdate()->first();

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

            if ($reservation->status !== 'inspected_accepted') {
                throw new InvalidPaymentStateException(
                    "Pickup reservation cannot be paid: status is '{$reservation->status}', expected 'inspected_accepted'."
                );
            }

            if ($reservation->order_id !== null) {
                throw new InvalidPaymentStateException(
                    "Pickup reservation has already been converted to Order #{$reservation->order_id}."
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

            // Step 5: Active Attempt Management on THIS reservation
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
                        'amount_kobo' => $amountKobo,
                        'customer' => $customerRecord,
                        'ttl_minutes' => $ttlMinutes,
                    ];
                }
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
                'payment_amount' => $twoDecimals,
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
                    'amount_kobo' => $amountKobo,
                    'created_at' => $now->toIso8601String(),
                    'init_claimed_at' => $now->toIso8601String(),
                    'init_claim_expires_at' => $claimExpiresAt->toIso8601String(),
                    'init_claim_token' => Str::random(32),
                ]),
            ]);

            return [
                'action' => 'INITIALIZE_NEW',
                'payment_request' => $newPaymentRequest,
                'reservation' => $reservation,
                'amount_kobo' => $amountKobo,
                'customer' => $customerRecord,
                'gateway_reference' => $gatewayReference,
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
            return $this->recoverAmbiguousAttempt(
                $phaseAResult['payment_request'],
                $phaseAResult['reservation'],
                $phaseAResult['customer'],
                $phaseAResult['amount_kobo'],
                $phaseAResult['ttl_minutes'],
                $callbackUrl
            );
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

        if ($initData['status'] === 'SUCCESS') {
            $additional = json_decode($paymentRequest->additional_data ?? '{}', true);
            $additional['authorization_url'] = $initData['authorization_url'];
            $additional['access_code'] = $initData['access_code'] ?? null;
            $additional['init_claim_expires_at'] = null; // Release lease upon success
            $paymentRequest->update([
                'additional_data' => json_encode($additional),
            ]);

            return [
                'status' => 'success',
                'is_replayed' => false,
                'payment_request' => $paymentRequest->fresh(),
                'authorization_url' => $initData['authorization_url'],
                'gateway_reference' => $gatewayReference,
            ];
        }

        if ($initData['status'] === 'GATEWAY_REJECTED') {
            $additional = json_decode($paymentRequest->additional_data ?? '{}', true);
            $additional['failure_reason'] = $initData['message'] ?? 'Gateway rejected initialization';
            $additional['closed_at'] = now()->toIso8601String();
            $additional['init_claim_expires_at'] = null;
            $paymentRequest->update([
                'attempt_status' => 'failed',
                'active_pickup_reservation_id' => null,
                'additional_data' => json_encode($additional),
            ]);

            throw new PaymentInitializationException("Paystack rejected initialization: " . ($initData['message'] ?? 'Unknown gateway rejection'));
        }

        // Ambiguous transport failure (CURL timeout, disconnect)
        // Keep PaymentRequest as 'pending' with active token so recovery works
        return [
            'status' => 'ambiguous_transport',
            'message' => $initData['message'] ?? 'Paystack initialization ambiguous / timed out.',
            'payment_request' => $paymentRequest->fresh(),
            'gateway_reference' => $gatewayReference,
            'is_ambiguous' => true,
        ];
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
                    $pr = PaymentRequest::where('id', $paymentRequest->id)->lockForUpdate()->first();

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
                        'payment_amount' => bcadd((string) $reservation->total_amount, '0', 2),
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

                if ($initData['status'] === 'SUCCESS') {
                    $add = json_decode($newAttempt->additional_data ?? '{}', true);
                    $add['authorization_url'] = $initData['authorization_url'];
                    $add['access_code'] = $initData['access_code'] ?? null;
                    $add['init_claim_expires_at'] = null; // Release lease upon success
                    $newAttempt->update(['additional_data' => json_encode($add)]);

                    return [
                        'status' => 'success',
                        'is_replayed' => false,
                        'payment_request' => $newAttempt->fresh(),
                        'authorization_url' => $initData['authorization_url'],
                        'gateway_reference' => $newAttempt->gateway_reference,
                    ];
                }

                return [
                    'status' => 'ambiguous_transport',
                    'message' => $initData['message'] ?? 'Paystack initialization timed out.',
                    'payment_request' => $newAttempt->fresh(),
                    'gateway_reference' => $newAttempt->gateway_reference,
                    'is_ambiguous' => true,
                ];

            case 'NON_FINAL':
            case 'SUCCESS':
                // Transaction exists on Paystack! Safely reuse existing attempt without rotating reference.
                return [
                    'status' => 'pending_on_gateway',
                    'is_replayed' => true,
                    'payment_request' => $paymentRequest,
                    'gateway_reference' => $reference,
                    'gateway_status' => $verifyData['class'],
                ];

            case 'GATEWAY_FAILURE':
                $additional = json_decode($paymentRequest->additional_data ?? '{}', true);
                $additional['failure_reason'] = 'GATEWAY_TERMINAL_FAILURE';
                $paymentRequest->update([
                    'attempt_status' => 'failed',
                    'active_pickup_reservation_id' => null,
                    'additional_data' => json_encode($additional),
                ]);
                throw new PaymentInitializationException("Paystack reports transaction failed on gateway.");

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
}
