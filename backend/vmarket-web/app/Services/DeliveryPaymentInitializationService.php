<?php

namespace App\Services;

use App\Exceptions\IdempotencyConflictException;
use App\Exceptions\InvalidCartException;
use App\Exceptions\InvalidPaymentStateException;
use App\Exceptions\PaymentInitializationException;
use App\Models\CheckoutIntent;
use App\Models\PaymentRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * [AI] Service DeliveryPaymentInitializationService
 * 
 * Bridges CheckoutIntent -> PaymentRequest -> Paystack Initialization.
 * Invariants:
 * 1. Derives payment amount exclusively from persisted, frozen CheckoutIntent.
 * 2. Enforces exact integer kobo via BCMath; zero float, zero round().
 * 3. Enforces NGN currency.
 * 4. Respects uq_pr_active_order_group: at most one active attempt per order group.
 * 5. Generates canonical unique Paystack reference: 'VM-' + orderedUuid (Paystack allowed: alphanumeric, -, ., =; zero underscores).
 * 6. Ambiguous transport failure: network timeouts preserve original PaymentRequest and reference as pending/recoverable.
 * 7. Definitive REFERENCE_NOT_FOUND recovery: transitions ambiguous attempt to terminal 'failed', clears active_order_group_id,
 *    and generates a clean NEW attempt with a NEW valid Paystack reference ('VM-...'). Old reference is NEVER initialized again.
 * 8. TTL Reconciliation: PaymentAttempt payable window is strictly bounded by CheckoutIntent expiration:
 *    attempt_expires_at = min(now + ttl, CheckoutIntent.expires_at). A payment attempt cannot outlive its parent checkout intent.
 * 9. Reuses existing Step 1 verification contract (PaystackController::getPayStackPaymentData) for recovery.
 * 10. Zero Order, OrderDetail, OrderTransaction, or Cart mutation in this phase.
 */
class DeliveryPaymentInitializationService
{
    public function __construct(
        protected ?PaystackInitializationClient $paystackClient = null
    ) {
        $this->paystackClient = $this->paystackClient ?? new PaystackInitializationClient();
    }

    /**
     * Initializes a Paystack payment attempt for an existing CheckoutIntent.
     *
     * @param int|User $customer Authenticated customer
     * @param int|string|CheckoutIntent $intentInput CheckoutIntent ID or order_group_id
     * @param int|null $ttlMinutes Attempt expiration TTL in minutes (default 30)
     * @return array
     *
     * @throws InvalidCartException
     * @throws InvalidPaymentStateException
     * @throws PaymentInitializationException
     */
    public function initializePayment(
        int|User $customer,
        int|string|CheckoutIntent $intentInput,
        ?int $ttlMinutes = 30
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

        $ttlMinutes = max(5, $ttlMinutes ?? 30);

        return DB::transaction(function () use ($customerId, $customerRecord, $intentInput, $ttlMinutes) {
            // 2. Fetch and Lock CheckoutIntent (IDOR Protected)
            $intentQuery = CheckoutIntent::query()->lockForUpdate();
            if ($intentInput instanceof CheckoutIntent) {
                $intentQuery->where('id', $intentInput->id);
            } elseif (is_numeric($intentInput)) {
                $intentQuery->where('id', (int) $intentInput);
            } else {
                $intentQuery->where('order_group_id', (string) $intentInput);
            }

            $intent = $intentQuery->first();
            if (!$intent) {
                throw new InvalidCartException("Checkout agreement not found.");
            }

            if ((int) $intent->customer_id !== $customerId) {
                throw new InvalidCartException("IDOR Violation: CheckoutIntent does not belong to customer #{$customerId}.");
            }

            if ($intent->status !== 'pending') {
                throw new InvalidPaymentStateException(
                    "CheckoutIntent cannot be paid: status is '{$intent->status}', expected 'pending'."
                );
            }

            if ($intent->isExpired()) {
                $intent->update([
                    'status' => 'expired',
                    'active_cart_token' => null,
                ]);
                throw new InvalidPaymentStateException("CheckoutIntent has expired. Please initiate a new checkout.");
            }

            // 3. Monetary Invariants: Exact Integer Kobo via BCMath & Fail-Closed NGN
            if (strtoupper((string) $intent->currency) !== 'NGN') {
                throw new InvalidPaymentStateException("Unsupported currency '{$intent->currency}'. Victorious MARKET requires NGN.");
            }

            $rawAmount = trim((string) $intent->total_amount);
            if (!preg_match('/^\d+(\.\d{1,4})?$/', $rawAmount)) {
                throw new InvalidPaymentStateException("Malformed total_amount '{$rawAmount}' on CheckoutIntent #{$intent->id}.");
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

            // 4. One Active Attempt Management (under Row Lock)
            $existingActive = PaymentRequest::where('order_group_id', $intent->order_group_id)
                ->whereNotNull('active_order_group_id')
                ->lockForUpdate()
                ->first();

            if ($existingActive) {
                // Check if existing attempt has expired
                if (now()->greaterThanOrEqualTo($existingActive->attempt_expires_at)) {
                    // Stale active attempt expired -> lazily clear active unique token under lock
                    $existingActive->update([
                        'attempt_status' => 'expired',
                        'active_order_group_id' => null,
                    ]);
                } else {
                    // Active attempt is still valid!
                    $additional = is_array($existingActive->additional_data) 
                        ? $existingActive->additional_data 
                        : json_decode($existingActive->additional_data ?? '{}', true);

                    // If authorization URL was already confirmed, replay it safely (No duplicate Paystack transaction)
                    if (!empty($additional['authorization_url'])) {
                        return [
                            'status' => 'success',
                            'is_replayed' => true,
                            'payment_request' => $existingActive,
                            'authorization_url' => $additional['authorization_url'],
                            'gateway_reference' => $existingActive->gateway_reference,
                        ];
                    }

                    // Ambiguous attempt exists without authorization_url -> perform safe recovery
                    return $this->recoverAmbiguousAttempt($existingActive, $intent, $customerRecord, $amountKobo, $ttlMinutes);
                }
            }

            // 5. Create Fresh Attempt and Initialize Paystack
            return $this->createNewAttemptAndInitialize(
                $intent,
                $customerRecord,
                $amountKobo,
                $ttlMinutes
            );
        });
    }

    /**
     * Recovers an existing pending attempt that encountered an ambiguous transport failure.
     * Uses the Step 1 normalized verification contract.
     *
     * State Machine:
     * - SUCCESS / NON_FINAL: Gateway has the transaction. Retain existing attempt with original reference.
     * - REFERENCE_NOT_FOUND: Definitive confirmation that Paystack has no record of this reference.
     *   Transition old attempt to 'failed', clear active token, generate NEW attempt with NEW 'VM-...' reference.
     *   NEVER re-initialize Paystack with the original reference.
     * - GATEWAY_FAILURE: Terminal failure on gateway. Mark attempt 'failed', clear active token.
     * - Ambiguous / Transport Error during verification: Preserve attempt as 'pending' for retry.
     */
    public function recoverAmbiguousAttempt(
        PaymentRequest $paymentRequest,
        CheckoutIntent $intent,
        User $customerRecord,
        int $amountKobo,
        int $ttlMinutes = 30
    ): array {
        $reference = $paymentRequest->gateway_reference;
        $additional = is_array($paymentRequest->additional_data) 
            ? $paymentRequest->additional_data 
            : json_decode($paymentRequest->additional_data ?? '{}', true);

        // Check if Paystack actually created the transaction using Step 1 verification helper
        $verifyData = $this->paystackClient->verifyExistingTransaction($reference);

        switch ($verifyData['class']) {
            case 'REFERENCE_NOT_FOUND':
                // Paystack definitively confirms this reference does not exist on the gateway.
                // Invariant: Do NOT re-initialize Paystack with the same reference (prevents duplicate-reference collisions).
                // 1. Mark original attempt terminal 'failed' for historical auditability.
                // 2. Clear active_order_group_id so the active attempt constraint is released.
                $additional['failure_reason'] = 'REFERENCE_NOT_FOUND_ON_GATEWAY';
                $additional['closed_at'] = now()->toIso8601String();
                $paymentRequest->update([
                    'attempt_status' => 'failed',
                    'active_order_group_id' => null,
                    'additional_data' => json_encode($additional),
                ]);

                // 3. Create a clean NEW payment attempt with a NEW Paystack reference
                return $this->createNewAttemptAndInitialize(
                    $intent,
                    $customerRecord,
                    $amountKobo,
                    $ttlMinutes,
                    supersedesAttemptId: $paymentRequest->id
                );

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
                $additional['failure_reason'] = 'GATEWAY_TERMINAL_FAILURE';
                $additional['closed_at'] = now()->toIso8601String();
                $paymentRequest->update([
                    'attempt_status' => 'failed',
                    'active_order_group_id' => null,
                    'additional_data' => json_encode($additional),
                ]);
                throw new PaymentInitializationException("Paystack reports transaction failed on gateway.");

            default:
                // Ambiguous / Transport error during verification -> preserve pending state and original reference
                return [
                    'status' => 'ambiguous_transport',
                    'message' => 'Paystack verification ambiguous: ' . ($verifyData['class'] ?? 'UNKNOWN'),
                    'payment_request' => $paymentRequest,
                    'gateway_reference' => $reference,
                ];
        }
    }

    /**
     * Creates a new PaymentRequest and initializes it with Paystack.
     * Generates a valid Paystack reference ('VM-' + orderedUuid) and enforces bounded TTL.
     */
    protected function createNewAttemptAndInitialize(
        CheckoutIntent $intent,
        User $customerRecord,
        int $amountKobo,
        int $ttlMinutes,
        ?string $supersedesAttemptId = null
    ): array {
        $now = now();
        $ttlTarget = $now->copy()->addMinutes($ttlMinutes);
        $intentExpiresAt = Carbon::parse($intent->expires_at);

        // Bounded TTL Invariant: attempt_expires_at = min(now + ttl, CheckoutIntent.expires_at)
        $boundedExpiry = $ttlTarget->isBefore($intentExpiresAt) ? $ttlTarget : $intentExpiresAt;

        // Canonical Gateway Reference: 'VM-' + orderedUuid (Alphanumeric and hyphen; zero underscores)
        $gatewayReference = 'VM-' . Str::orderedUuid()->toString();
        $paymentRequestId = (string) Str::uuid();

        $payerInfo = [
            'name' => $customerRecord->name ?? ($customerRecord->f_name . ' ' . $customerRecord->l_name),
            'email' => $customerRecord->email,
            'phone' => $customerRecord->phone,
        ];

        $initialAdditional = [
            'checkout_intent_id' => $intent->id,
            'order_group_id' => $intent->order_group_id,
            'created_at' => $now->toIso8601String(),
        ];

        if ($supersedesAttemptId !== null) {
            $initialAdditional['supersedes_attempt_id'] = $supersedesAttemptId;
        }

        $paymentRequest = PaymentRequest::create([
            'id' => $paymentRequestId,
            'payer_id' => (string) $customerRecord->id,
            'payment_amount' => $intent->total_amount,
            'currency_code' => 'NGN',
            'payment_method' => 'paystack',
            'payment_domain' => 'marketplace_delivery',
            'order_group_id' => $intent->order_group_id,
            'gateway_reference' => $gatewayReference,
            'attempt_status' => 'pending',
            'active_order_group_id' => $intent->order_group_id,
            'attempt_expires_at' => $boundedExpiry,
            'is_paid' => 0,
            'attribute' => 'order',
            'attribute_id' => (string) $now->timestamp,
            'payer_information' => json_encode($payerInfo),
            'additional_data' => json_encode($initialAdditional),
        ]);

        $callbackUrl = route('paystack.callback', ['payment_id' => $paymentRequestId]);
        $metadata = [
            'payment_id' => $paymentRequestId,
            'order_group_id' => $intent->order_group_id,
            'customer_id' => (int) $customerRecord->id,
        ];

        $initResult = $this->paystackClient->initializeTransaction(
            email: $customerRecord->email,
            amountKobo: $amountKobo,
            reference: $gatewayReference,
            callbackUrl: $callbackUrl,
            metadata: $metadata
        );

        if ($initResult['status'] === 'SUCCESS') {
            $initialAdditional['authorization_url'] = $initResult['authorization_url'];
            $initialAdditional['access_code'] = $initResult['access_code'];

            $paymentRequest->update([
                'additional_data' => json_encode($initialAdditional),
            ]);

            return [
                'status' => 'success',
                'is_replayed' => false,
                'is_recovered' => ($supersedesAttemptId !== null),
                'payment_request' => $paymentRequest,
                'authorization_url' => $initResult['authorization_url'],
                'gateway_reference' => $gatewayReference,
                'superseded_attempt_id' => $supersedesAttemptId,
            ];
        }

        if ($initResult['status'] === 'GATEWAY_REJECTED') {
            $initialAdditional['gateway_rejection'] = $initResult['message'];
            $paymentRequest->update([
                'attempt_status' => 'failed',
                'active_order_group_id' => null,
                'additional_data' => json_encode($initialAdditional),
            ]);

            return [
                'status' => 'failed',
                'is_replayed' => false,
                'message' => "Paystack initialization rejected: {$initResult['message']}",
                'payment_request' => $paymentRequest,
                'gateway_reference' => $gatewayReference,
            ];
        }

        // Ambiguous Transport Failure: Timeout / Connection Drop
        // Retain attempt_status='pending' and active_order_group_id
        $initialAdditional['last_transport_error'] = $initResult['message'];
        $paymentRequest->update([
            'additional_data' => json_encode($initialAdditional),
        ]);

        return [
            'status' => 'ambiguous_transport',
            'is_replayed' => false,
            'message' => 'Paystack network timeout. The payment attempt has been safely preserved as pending.',
            'payment_request' => $paymentRequest,
            'gateway_reference' => $gatewayReference,
        ];
    }
}
