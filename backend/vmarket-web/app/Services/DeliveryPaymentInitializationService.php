<?php

namespace App\Services;

use App\Exceptions\IdempotencyConflictException;
use App\Exceptions\InvalidCartException;
use App\Exceptions\InvalidPaymentStateException;
use App\Exceptions\PaymentInitializationException;
use App\Models\CheckoutIntent;
use App\Models\PaymentRequest;
use App\Models\User;
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
 * 5. Generates canonical unique reference: 'VM_' + orderedUuid.
 * 6. Transport safety: ambiguous network timeouts do NOT mark attempts failed or rotate references.
 * 7. Reuses existing Step 1 verification contract (PaystackController::getPayStackPaymentData) for recovery.
 * 8. Zero Order, OrderDetail, OrderTransaction, or Cart mutation in this phase.
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
                    return $this->recoverAmbiguousAttempt($existingActive, $intent, $customerRecord, $amountKobo);
                }
            }

            // 5. Generate Canonical Gateway Reference
            $gatewayReference = 'VM_' . Str::orderedUuid()->toString();
            $paymentRequestId = (string) Str::uuid();

            // 6. Insert New PaymentRequest in Pending State
            $payerInfo = [
                'name' => $customerRecord->name ?? ($customerRecord->f_name . ' ' . $customerRecord->l_name),
                'email' => $customerRecord->email,
                'phone' => $customerRecord->phone,
            ];

            $initialAdditional = [
                'checkout_intent_id' => $intent->id,
                'order_group_id' => $intent->order_group_id,
                'created_at' => now()->toIso8601String(),
            ];

            $paymentRequest = PaymentRequest::create([
                'id' => $paymentRequestId,
                'payer_id' => (string) $customerId,
                'payment_amount' => $intent->total_amount,
                'currency_code' => 'NGN',
                'payment_method' => 'paystack',
                'payment_domain' => 'marketplace_delivery',
                'order_group_id' => $intent->order_group_id,
                'gateway_reference' => $gatewayReference,
                'attempt_status' => 'pending',
                'active_order_group_id' => $intent->order_group_id,
                'attempt_expires_at' => now()->addMinutes($ttlMinutes),
                'is_paid' => 0,
                'attribute' => 'order',
                'attribute_id' => (string) now()->timestamp,
                'payer_information' => json_encode($payerInfo),
                'additional_data' => json_encode($initialAdditional),
            ]);

            // 7. Initialize Transaction with Paystack
            $callbackUrl = route('paystack.callback', ['payment_id' => $paymentRequestId]);
            $metadata = [
                'payment_id' => $paymentRequestId,
                'order_group_id' => $intent->order_group_id,
                'customer_id' => $customerId,
            ];

            $initResult = $this->paystackClient->initializeTransaction(
                email: $customerRecord->email,
                amountKobo: $amountKobo,
                reference: $gatewayReference,
                callbackUrl: $callbackUrl,
                metadata: $metadata
            );

            // 8. Handle Paystack Initialization Response Deterministically
            if ($initResult['status'] === 'SUCCESS') {
                $initialAdditional['authorization_url'] = $initResult['authorization_url'];
                $initialAdditional['access_code'] = $initResult['access_code'];

                $paymentRequest->update([
                    'additional_data' => json_encode($initialAdditional),
                ]);

                return [
                    'status' => 'success',
                    'is_replayed' => false,
                    'payment_request' => $paymentRequest,
                    'authorization_url' => $initResult['authorization_url'],
                    'gateway_reference' => $gatewayReference,
                ];
            }

            if ($initResult['status'] === 'GATEWAY_REJECTED') {
                // Confirmed gateway rejection (e.g. invalid credentials or blocked merchant)
                $initialAdditional['gateway_rejection'] = $initResult['message'];
                $paymentRequest->update([
                    'attempt_status' => 'failed',
                    'active_order_group_id' => null, // Release active constraint so user can re-try after fixing
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
            // CRITICAL: Do NOT mark failed. Do NOT generate new reference. Retain attempt_status='pending'.
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
        });
    }

    /**
     * Recovers an existing pending attempt that encountered an ambiguous transport failure.
     * Uses the Step 1 normalized verification contract without rotating references.
     */
    public function recoverAmbiguousAttempt(
        PaymentRequest $paymentRequest,
        CheckoutIntent $intent,
        User $customerRecord,
        int $amountKobo
    ): array {
        $reference = $paymentRequest->gateway_reference;
        $additional = is_array($paymentRequest->additional_data) 
            ? $paymentRequest->additional_data 
            : json_decode($paymentRequest->additional_data ?? '{}', true);

        // Check if Paystack actually created the transaction
        $verifyData = $this->paystackClient->verifyExistingTransaction($reference);

        switch ($verifyData['class']) {
            case 'REFERENCE_NOT_FOUND':
                // Paystack never received or processed it. Safe to re-initialize with Paystack using the SAME reference!
                $callbackUrl = route('paystack.callback', ['payment_id' => $paymentRequest->id]);
                $metadata = [
                    'payment_id' => $paymentRequest->id,
                    'order_group_id' => $intent->order_group_id,
                    'customer_id' => $paymentRequest->payer_id,
                ];

                $initResult = $this->paystackClient->initializeTransaction(
                    email: $customerRecord->email,
                    amountKobo: $amountKobo,
                    reference: $reference,
                    callbackUrl: $callbackUrl,
                    metadata: $metadata
                );

                if ($initResult['status'] === 'SUCCESS') {
                    $additional['authorization_url'] = $initResult['authorization_url'];
                    $additional['access_code'] = $initResult['access_code'];
                    $paymentRequest->update([
                        'additional_data' => json_encode($additional),
                    ]);

                    return [
                        'status' => 'success',
                        'is_replayed' => false,
                        'is_recovered' => true,
                        'payment_request' => $paymentRequest,
                        'authorization_url' => $initResult['authorization_url'],
                        'gateway_reference' => $reference,
                    ];
                }

                if ($initResult['status'] === 'GATEWAY_REJECTED') {
                    $paymentRequest->update([
                        'attempt_status' => 'failed',
                        'active_order_group_id' => null,
                        'additional_data' => json_encode(array_merge($additional, ['gateway_rejection' => $initResult['message']])),
                    ]);
                    throw new PaymentInitializationException("Paystack re-initialization rejected: {$initResult['message']}");
                }

                // Still ambiguous
                return [
                    'status' => 'ambiguous_transport',
                    'message' => 'Paystack network timeout during recovery.',
                    'payment_request' => $paymentRequest,
                    'gateway_reference' => $reference,
                ];

            case 'NON_FINAL':
            case 'SUCCESS':
                // Transaction exists on Paystack!
                return [
                    'status' => 'pending_on_gateway',
                    'is_replayed' => true,
                    'payment_request' => $paymentRequest,
                    'gateway_reference' => $reference,
                    'gateway_status' => $verifyData['class'],
                ];

            case 'GATEWAY_FAILURE':
                $paymentRequest->update([
                    'attempt_status' => 'failed',
                    'active_order_group_id' => null,
                ]);
                throw new PaymentInitializationException("Paystack reports transaction failed on gateway.");

            default:
                // Ambiguous / Transport error during verification -> preserve pending state
                return [
                    'status' => 'ambiguous_transport',
                    'message' => 'Paystack verification ambiguous: ' . ($verifyData['class'] ?? 'UNKNOWN'),
                    'payment_request' => $paymentRequest,
                    'gateway_reference' => $reference,
                ];
        }
    }
}
