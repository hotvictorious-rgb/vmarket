<?php

namespace App\Services;

use App\Models\AdminWallet;
use App\Models\CustomerCashbackLedger;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\RefundTransaction;
use App\Models\SellerWallet;
use App\Utils\OrderManager;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [AI] PaystackRefundService
 *
 * Implements the 3-Phase Distributed Asynchronous Paystack Refund Engine with:
 * 1. Mandatory HMAC-SHA512 Cryptographic Webhook Signature Verification (Fail-Closed).
 * 2. Strict Multi-Identifier Correlation Hierarchy (Never Match by Amount Alone).
 * 3. Authoritative Paystack Status Lookup (Webhook Fallback).
 * 4. Exact-Once Financial Reversal Idempotency.
 * 5. BCMath Precision for Pre-Settlement Escrow vs Post-Settlement Ledger Reversals.
 * 6. Proportional Partial-Refund Settlement State & Cashback Adjustment.
 */
class PaystackRefundService
{
    /**
     * Verify Paystack webhook cryptographic signature (HMAC-SHA512).
     * Fails closed if secret is missing or signature does not match.
     */
    public function verifyWebhookSignature(string $rawPayload, ?string $signature): bool
    {
        $secretKey = PaystackBankService::getSecretKey();
        if (empty($secretKey) || empty($signature)) {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $rawPayload, $secretKey), $signature);
    }

    /**
     * Resolves the authoritative Paystack gateway reference for an Order.
     * New delivery & pickup orders store the Paystack reference in payment_requests.gateway_reference
     * and an internal OrderManager ID in orders.transaction_ref.
     */
    public static function resolvePaystackReferenceForOrder(Order $order): ?string
    {
        // 1. Check PaymentRequest via order_group_id
        if (!empty($order->order_group_id)) {
            $pr = \App\Models\PaymentRequest::where('order_group_id', $order->order_group_id)
                ->where('is_paid', 1)
                ->where('attempt_status', 'successful')
                ->first();
            if ($pr && !empty($pr->gateway_reference)) {
                return $pr->gateway_reference;
            }
        }

        // 2. Check if pickup reservation exists
        $reservation = \App\Models\PickupReservation::where('order_id', $order->id)->first();
        if ($reservation) {
            $pr = \App\Models\PaymentRequest::where('pickup_reservation_id', $reservation->id)
                ->where('is_paid', 1)
                ->where('attempt_status', 'successful')
                ->first();
            if ($pr && !empty($pr->gateway_reference)) {
                return $pr->gateway_reference;
            }
        }

        // 3. Fallback to order's transaction_ref (legacy orders)
        // [AI] Canonical orders must never fall back to their internal accounting reference.
        return empty($order->order_group_id) && !$reservation && !empty($order->transaction_ref)
            ? $order->transaction_ref : null;
    }

    /**
     * Check for existing refund on Paystack using strict multi-tier correlation hierarchy.
     * FORBIDDEN IDENTITY RULE: Never match by amount alone or transaction+amount alone!
     */
    public function checkExistingRefund(string $transactionReference, int $refundRequestId, ?string $paystackRefundId = null): array
    {
        $secretKey = PaystackBankService::getSecretKey();
        $expectedNote = 'vmarket_refund_' . $refundRequestId;

        // Tier 1: Stored local Paystack refund ID
        if (!empty($paystackRefundId)) {
            $fetched = $this->fetchRefundById($paystackRefundId);
            if ($fetched['status'] && isset($fetched['data'])) {
                $refData = $fetched['data'];
                // Safety verification layer: verify provider record against local request expectations
                $refTx = (string)($refData['transaction_reference'] ?? ($refData['transaction']['reference'] ?? ''));
                $refCurrency = strtoupper((string)($refData['currency'] ?? ''));

                if ($refCurrency === 'NGN' && (empty($refTx) || $refTx === $transactionReference)) {
                    return [
                        'found' => true,
                        'exact_match' => true,
                        'refund' => $refData,
                    ];
                }

                return [
                    'found' => true,
                    'exact_match' => false,
                    'has_ambiguous_refunds' => true,
                    'status' => 'reconciliation_required',
                ];
            }
        }

        // Tier 2: Query Paystack by transaction reference
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Cache-Control' => 'no-cache',
            ])->timeout(12)->get('https://api.paystack.co/refund', [
                'transaction' => $transactionReference,
            ]);

            if ($response->successful() && isset($response->json()['data'])) {
                $refunds = $response->json()['data'];
                if (empty($refunds)) {
                    return [
                        'found' => false,
                        'exact_match' => false,
                        'has_ambiguous_refunds' => false,
                    ];
                }

                // Search for exact match on merchant_note
                foreach ($refunds as $ref) {
                    $note = $ref['merchant_note'] ?? $ref['customer_note'] ?? '';
                    if ($note === $expectedNote) {
                        return [
                            'found' => true,
                            'exact_match' => true,
                            'refund' => $ref,
                        ];
                    }
                }

                // Tier 3: Existing refunds found on this transaction, but NONE have exact correlation!
                // DO NOT use amount alone as identity! Require reconciliation!
                return [
                    'found' => false,
                    'exact_match' => false,
                    'has_ambiguous_refunds' => true,
                    'status' => 'reconciliation_required',
                ];
            }
        } catch (Exception $e) {
            Log::error("[AI] Paystack checkExistingRefund exception: " . $e->getMessage());
        }

        return [
            'found' => false,
            'exact_match' => false,
            'has_ambiguous_refunds' => false,
        ];
    }

    /**
     * Fetch refund resource directly from Paystack by ID
     */
    public function fetchRefundById(string $refundId): array
    {
        $secretKey = PaystackBankService::getSecretKey();
        try {
            $identifier = rawurlencode(trim($refundId));
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Cache-Control' => 'no-cache',
            ])->timeout(12)->get("https://api.paystack.co/refund/{$identifier}");

            if ($response->successful() && isset($response->json()['data'])) {
                return [
                    'status' => true,
                    'data' => $response->json()['data'],
                ];
            }
        } catch (Exception $e) {
            Log::error("[AI] Paystack fetchRefundById exception: " . $e->getMessage());
        }

        return ['status' => false];
    }

    /**
     * Phase B: Initiate Paystack Refund with Pre-Flight Duplicate Guard
     */
    public function initiateRefund(RefundRequest $refundRequest, string $transactionReference): array
    {
        // 1. Assign stable execution reference if missing
        if (empty($refundRequest->execution_ref)) {
            $refundRequest->execution_ref = 'vmarket_refund_' . $refundRequest->id;
            $refundRequest->save();
        }

        // 2. Pre-Flight Duplicate Guard
        $existingCheck = $this->checkExistingRefund(
            $transactionReference,
            $refundRequest->id,
            $refundRequest->paystack_refund_id
        );

        // CASE 1: Exact existing refund found on provider
        if ($existingCheck['found'] && $existingCheck['exact_match']) {
            $matchedRefund = $existingCheck['refund'];
            $refundRequest->paystack_refund_id = (string)($matchedRefund['id'] ?? $refundRequest->paystack_refund_id);
            $providerStatus = $matchedRefund['status'] ?? 'pending';

            if ($providerStatus === 'processed') {
                $this->finalizeRefundAccounting($refundRequest, $matchedRefund);
                return [
                    'status' => true,
                    'message' => 'Existing Paystack refund discovered and reconciled as processed.',
                    'execution_status' => 'succeeded',
                ];
            }

            $refundRequest->execution_status = 'pending_provider_processing';
            $refundRequest->save();
            return [
                'status' => true,
                'message' => "Existing Paystack refund discovered in status '{$providerStatus}'.",
                'execution_status' => 'pending_provider_processing',
            ];
        }

        // CASE 3: Ambiguous refunds exist on transaction
        if (!empty($existingCheck['has_ambiguous_refunds'])) {
            $refundRequest->execution_status = 'reconciliation_required';
            $refundRequest->save();
            return [
                'status' => false,
                'message' => 'Existing refunds found on transaction with uncertain correlation. Set to reconciliation_required.',
                'execution_status' => 'reconciliation_required',
            ];
        }

        // CASE 2: No existing refund found -> Call POST /refund
        $secretKey = PaystackBankService::getSecretKey();
        if (empty($secretKey)) {
            $refundRequest->execution_status = 'reconciliation_required';
            $refundRequest->save();
            return [
                'status' => false,
                'message' => 'Paystack secret key is not configured.',
                'execution_status' => 'reconciliation_required',
            ];
        }

        // Raw DB decimal string bypass: getRawOriginal() avoids the float cast on RefundRequest.amount
        $amountInKobo = (int) round(bcmul((string)($refundRequest->getRawOriginal('amount') ?? '0.00'), '100', 2));
        $postData = [
            'transaction' => $transactionReference,
            'amount' => $amountInKobo,
            'currency' => 'NGN',
            'merchant_note' => $refundRequest->execution_ref,
            'customer_note' => "Victorious MARKET Refund for Order #{$refundRequest->order_id}",
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache',
            ])->timeout(15)->post('https://api.paystack.co/refund', $postData);

            $result = $response->json();
            if ($response->successful() && isset($result['status']) && $result['status'] === true) {
                $data = $result['data'] ?? [];
                $refundRequest->paystack_refund_id = (string)($data['id'] ?? '');
                $providerStatus = $data['status'] ?? 'pending';

                if ($providerStatus === 'processed') {
                    $this->finalizeRefundAccounting($refundRequest, $data);
                    return [
                        'status' => true,
                        'message' => 'Paystack refund created and immediately processed.',
                        'execution_status' => 'succeeded',
                    ];
                }

                if ($providerStatus === 'needs-attention') {
                    $refundRequest->execution_status = 'needs_attention';
                    $refundRequest->save();
                    return [
                        'status' => true,
                        'message' => 'Paystack refund created with status needs-attention.',
                        'execution_status' => 'needs_attention',
                    ];
                }

                $refundRequest->execution_status = 'pending_provider_processing';
                $refundRequest->save();
                return [
                    'status' => true,
                    'message' => 'Paystack refund initiated and awaiting asynchronous processing.',
                    'execution_status' => 'pending_provider_processing',
                ];
            }

            Log::error("[AI] Paystack create refund error: " . json_encode($result));
            $refundRequest->execution_status = 'failed';
            $refundRequest->rejected_note = $result['message'] ?? 'Provider creation error';
            $refundRequest->save();

            return [
                'status' => false,
                'message' => $result['message'] ?? 'Provider rejected refund creation.',
                'execution_status' => 'failed',
            ];
        } catch (Exception $e) {
            Log::error("[AI] Paystack create refund timeout/exception: " . $e->getMessage());
            $refundRequest->execution_status = 'reconciliation_required';
            $refundRequest->save();

            return [
                'status' => false,
                'message' => 'Network error during refund initiation. Flagged for reconciliation.',
                'execution_status' => 'reconciliation_required',
            ];
        }
    }

    /**
     * Handle incoming Paystack Webhook for refund lifecycle events.
     * Enforces fail-closed signature check, payload validation, and exact correlation.
     */
    public function handleRefundWebhook(array $payload, string $signature, string $rawBody): array
    {
        // 1. Mandatory HMAC-SHA512 Cryptographic Signature Verification
        if (!$this->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning("[AI] Paystack Refund Webhook: Invalid or missing cryptographic signature.");
            return [
                'status' => false,
                'code' => 401,
                'message' => 'Invalid signature.',
            ];
        }

        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];

        if (!str_starts_with($event, 'refund.')) {
            return [
                'status' => true,
                'code' => 200,
                'message' => 'Ignored non-refund event.',
            ];
        }

        // [AI] Implementation Caution: Use provider's actual refund reference / transaction reference
        // present in the event payload to retrieve full refund resource if merchant_note is absent.
        $providerRefundRef = (string)($data['refund_reference'] ?? ($data['id'] ?? ($data['reference'] ?? '')));
        $fullRefund = $data;
        if (!empty($providerRefundRef) && empty($data['merchant_note'])) {
            $fetched = $this->fetchRefundById($providerRefundRef);
            if ($fetched['status'] && !empty($fetched['data'])) {
                $fullRefund = $fetched['data'];
            }
        }

        // Extract correlation identifiers
        $refundId = (string)($fullRefund['id'] ?? ($fullRefund['refund_reference'] ?? $providerRefundRef));
        $txRef = (string)($fullRefund['transaction_reference'] ?? ($fullRefund['transaction']['reference'] ?? ($data['transaction_reference'] ?? '')));
        $merchantNote = (string)($fullRefund['merchant_note'] ?? ($fullRefund['customer_note'] ?? ''));
        $amountInKobo = (int)($fullRefund['amount'] ?? ($data['amount'] ?? 0));
        $currency = strtoupper((string)($fullRefund['currency'] ?? ($data['currency'] ?? '')));

        // Currency validation
        if ($currency !== 'NGN') {
            Log::warning("[AI] Paystack Refund Webhook: Rejected non-NGN currency '{$currency}'.");
            return [
                'status' => false,
                'code' => 400,
                'message' => 'Invalid currency. Must be NGN.',
            ];
        }

        // Conclusively correlate to exactly ONE local RefundRequest
        $refundRequest = null;

        // Tier 1: Correlation via stored paystack_refund_id
        if (!empty($refundId) || !empty($providerRefundRef)) {
            $query = RefundRequest::query();
            if (!empty($refundId)) {
                $query->where('paystack_refund_id', $refundId);
            }
            if (!empty($providerRefundRef) && $providerRefundRef !== $refundId) {
                $query->orWhere('paystack_refund_id', $providerRefundRef);
            }
            $refundRequest = $query->first();
        }

        // Tier 2: Correlation via execution_ref or merchant_note
        if (!$refundRequest && !empty($merchantNote)) {
            $refundRequest = RefundRequest::where('execution_ref', $merchantNote)->first();
            if (!$refundRequest && str_starts_with($merchantNote, 'vmarket_refund_')) {
                $reqId = (int) str_replace('vmarket_refund_', '', $merchantNote);
                if ($reqId > 0) {
                    $refundRequest = RefundRequest::find($reqId);
                }
            }
        }

        // Tier 3: Authoritative fallback via transaction reference and order lookup
        if (!$refundRequest && !empty($txRef)) {
            $paymentRequest = \App\Models\PaymentRequest::where('gateway_reference', $txRef)->first();
            $orderIds = [];
            if ($paymentRequest && !empty($paymentRequest->order_group_id)) {
                $orderIds = Order::where('order_group_id', $paymentRequest->order_group_id)->pluck('id')->toArray();
            }
            if (empty($orderIds)) {
                $matchedOrder = Order::where('transaction_ref', $txRef)->first();
                if ($matchedOrder) {
                    $orderIds = [$matchedOrder->id];
                }
            }
            if (!empty($orderIds)) {
                $candidates = RefundRequest::whereIn('order_id', $orderIds)
                    ->whereIn('execution_status', ['pending_provider_processing', 'idle', 'needs_attention'])
                    ->get();
                if ($candidates->count() === 1) {
                    $candidate = $candidates->first();
                    $expectedCandidateKobo = (int) round(bcmul((string)($candidate->getRawOriginal('amount') ?? '0.00'), '100', 2));
                    if ($amountInKobo === 0 || $amountInKobo === $expectedCandidateKobo) {
                        $refundRequest = $candidate;
                    }
                } elseif ($candidates->count() > 1) {
                    // Ambiguous candidate refunds on order -> Flag for reconciliation rather than guess
                    Log::warning("[AI] Paystack Refund Webhook: Ambiguous refund requests on Orders " . json_encode($orderIds) . ". Setting reconciliation_required.");
                    foreach ($candidates as $cand) {
                        $cand->execution_status = 'reconciliation_required';
                        $cand->save();
                    }
                    return [
                        'status' => false,
                        'code' => 409,
                        'message' => 'Multiple pending refunds on transaction. Flagged for reconciliation.',
                    ];
                }
            }
        }

        // If not found or ambiguous
        if (!$refundRequest) {
            Log::warning("[AI] Paystack Refund Webhook: Could not correlate refund ID '{$refundId}' or ref '{$providerRefundRef}' to local request.");
            PaymentExceptionRecorder::record($txRef, $fullRefund, 'uncorrelated_refund', null, $event);
            return [
                'status' => true,
                'code' => 200,
                'message' => 'Event logged; local correlation not found.',
            ];
        }

        // [AI] Late provider notifications cannot reopen completed accounting.
        if ($refundRequest->status === 'refunded' || in_array($refundRequest->execution_status, ['succeeded', 'already_refunded'], true)) {
            return ['status' => true, 'code' => 200, 'message' => 'Completed refund already recorded.'];
        }

        // Transaction reference validation
        $order = Order::find($refundRequest->order_id);
        if ($order && !empty($txRef)) {
            $expectedGatewayRef = self::resolvePaystackReferenceForOrder($order);
            if (empty($expectedGatewayRef) || $txRef !== $expectedGatewayRef) {
                Log::warning("[AI] Paystack Refund Webhook: Transaction reference mismatch for RefundRequest #{$refundRequest->id}. Expected '{$expectedGatewayRef}', got '{$txRef}'.");
                return [
                    'status' => false,
                    'code' => 400,
                    'message' => 'Transaction reference mismatch.',
                ];
            }
        }

        // Amount validation
        $expectedKobo = (int) round(bcmul((string)($refundRequest->getRawOriginal('amount') ?? '0.00'), '100', 2));
        if ($amountInKobo > 0 && $amountInKobo !== $expectedKobo) {
            Log::warning("[AI] Paystack Refund Webhook: Amount mismatch for RefundRequest #{$refundRequest->id}. Expected {$expectedKobo}, got {$amountInKobo}.");
            RefundRequest::where('id', $refundRequest->id)->where('status', '!=', 'refunded')
                ->whereNotIn('execution_status', ['succeeded', 'already_refunded'])
                ->update(['execution_status' => 'reconciliation_required']);
            return [
                'status' => false,
                'code' => 400,
                'message' => 'Refund amount mismatch.',
            ];
        }

        // Update paystack_refund_id if not stored
        if (empty($refundRequest->paystack_refund_id) && !empty($refundId)) {
            RefundRequest::where('id', $refundRequest->id)->whereNull('paystack_refund_id')
                ->update(['paystack_refund_id' => $refundId]);
        }

        // Process Event Transitions
        $transition = RefundRequest::where('id', $refundRequest->id)->where('status', '!=', 'refunded')
            ->whereNotIn('execution_status', ['succeeded', 'already_refunded']);
        switch ($event) {
            case 'refund.pending':
            case 'refund.processing':
                $transition->update(['execution_status' => 'pending_provider_processing']);
                break;

            case 'refund.needs-attention':
                $transition->update(['execution_status' => 'needs_attention']);
                break;

            case 'refund.failed':
                $transition->update(['execution_status' => 'failed',
                    'rejected_note' => $fullRefund['provider_response'] ?? ($fullRefund['status'] ?? 'Provider failed refund')]);
                break;

            case 'refund.processed':
                $fullRefund['status'] = $fullRefund['status'] ?? 'processed';
                $this->finalizeRefundAccounting($refundRequest, $fullRefund);
                break;
        }

        return [
            'status' => true,
            'code' => 200,
            'message' => "Paystack refund event {$event} processed successfully.",
        ];
    }

    /**
     * Phase C: Finalize Refund Accounting (Exact-Once Idempotent Execution)
     */
    public function finalizeRefundAccounting(RefundRequest $refundRequest, array $providerData = []): void
    {
        DB::transaction(function () use ($refundRequest, $providerData) {
            // [AI] Order is the common serialization anchor for refunds and cashback maturation.
            $orderId = RefundRequest::where('id', $refundRequest->id)->value('order_id');
            $order = Order::where('id', $orderId)->lockForUpdate()->first();
            if (!$order) {
                return;
            }
            // 1. Pessimistic Row-Level Lock
            $lockedRequest = RefundRequest::where('id', $refundRequest->id)->lockForUpdate()->first();
            if (!$lockedRequest) {
                return;
            }

            // 2. Exact-Once Idempotency Guard & Item-Level Duplicate Guard
            if ($lockedRequest->execution_status === 'succeeded' || $lockedRequest->status === 'refunded') {
                return; // Zero duplicate financial mutations
            }

            $lockedDetail = \App\Models\OrderDetail::where('id', $lockedRequest->order_details_id)->lockForUpdate()->first();
            if ($lockedDetail && (int)$lockedDetail->refund_request === 4) {
                Log::warning("[AI] finalizeRefundAccounting blocked: OrderDetail #{$lockedDetail->id} already refunded.");
                $lockedRequest->execution_status = 'already_refunded';
                $lockedRequest->status = 'refunded';
                $lockedRequest->save();
                return;
            }

            $otherExecuted = RefundRequest::where('order_details_id', $lockedRequest->order_details_id)
                ->where('id', '!=', $lockedRequest->id)
                ->where(function ($q) {
                    $q->where('status', 'refunded')->orWhere('execution_status', 'succeeded');
                })
                ->exists();
            if ($otherExecuted) {
                Log::warning("[AI] finalizeRefundAccounting blocked: OrderDetail #{$lockedRequest->order_details_id} already has a completed refund request.");
                $lockedRequest->execution_status = 'already_refunded';
                $lockedRequest->status = 'refunded';
                $lockedRequest->save();
                return;
            }

            // [AI] Lock customer before reward lots; shared with maturation and redemption.
            if ($order) {
                DB::table('users')->where('id', $order->customer_id)->lockForUpdate()->first();
            }
            if (!$order) {
                return;
            }

            // [AI] Strict Provider Proof Verification Invariant:
            // [AI] Strict Provider Proof Verification Invariant:
            // Internal financial finalization is ONLY permitted with authoritative Paystack proof.
            // Reject any execution without verified providerData or with contradictory provider fields.
            if (empty($providerData)) {
                Log::error("[AI] Paystack finalizeRefundAccounting blocked: Missing authoritative provider data for RefundRequest #{$lockedRequest->id}");
                return;
            }

            // 1. Authoritative provider status must be an accepted processed/success state
            $providerStatus = strtolower((string)($providerData['status'] ?? ''));
            if (!in_array($providerStatus, ['processed', 'success', 'succeeded'], true)) {
                Log::warning("[AI] Paystack finalizeRefundAccounting blocked: Provider status is '{$providerStatus}', not 'processed', for RefundRequest #{$lockedRequest->id}");
                $lockedRequest->execution_status = 'reconciliation_required';
                $lockedRequest->save();
                return;
            }

            // 2. Currency must be present and exactly NGN
            $providerCurrency = strtoupper((string)($providerData['currency'] ?? ''));
            if ($providerCurrency !== 'NGN') {
                Log::warning("[AI] Paystack finalizeRefundAccounting blocked: Missing or non-NGN currency '{$providerCurrency}' for RefundRequest #{$lockedRequest->id}");
                $lockedRequest->execution_status = 'reconciliation_required';
                $lockedRequest->save();
                return;
            }

            // 3. Provider refund amount must be present, positive, and exactly equal expected kobo
            $expectedKobo = (int) bcmul((string)($lockedRequest->getRawOriginal('amount') ?? '0.00'), '100', 0);
            $providerKobo = (int)($providerData['amount'] ?? 0);
            if ($providerKobo <= 0 || $providerKobo !== $expectedKobo) {
                Log::warning("[AI] Paystack finalizeRefundAccounting blocked: Amount mismatch for RefundRequest #{$lockedRequest->id}. Expected {$expectedKobo}, got {$providerKobo}");
                $lockedRequest->execution_status = 'reconciliation_required';
                $lockedRequest->save();
                return;
            }

            // 4. Provider transaction reference must be present and exactly match order transaction reference
            $orderTxRef = (string) self::resolvePaystackReferenceForOrder($order);
            $providerTxRef = (string)($providerData['transaction_reference'] ?? ($providerData['transaction']['reference'] ?? ''));
            if (empty($providerTxRef) || empty($orderTxRef) || $providerTxRef !== $orderTxRef) {
                Log::warning("[AI] Paystack finalizeRefundAccounting blocked: Missing or mismatched transaction reference for RefundRequest #{$lockedRequest->id}. Expected '{$orderTxRef}', got '{$providerTxRef}'");
                $lockedRequest->execution_status = 'reconciliation_required';
                $lockedRequest->save();
                return;
            }

            // 5. Provider refund/execution correlation must be present and uniquely identify this RefundRequest
            $providerNote = (string)($providerData['merchant_note'] ?? ($providerData['customer_note'] ?? ''));
            $providerRefundId = (string)($providerData['id'] ?? ($providerData['refund_reference'] ?? ''));
            $expectedNote = (string)($lockedRequest->execution_ref ?? ('vmarket_refund_' . $lockedRequest->id));

            $hasValidCorrelation = false;
            if (!empty($providerNote) && ($providerNote === $expectedNote || $providerNote === 'vmarket_refund_' . $lockedRequest->id)) {
                $hasValidCorrelation = true;
            } elseif (!empty($providerRefundId) && !empty($lockedRequest->paystack_refund_id) && $providerRefundId === (string)$lockedRequest->paystack_refund_id) {
                $hasValidCorrelation = true;
            }

            if (!$hasValidCorrelation) {
                Log::warning("[AI] Paystack finalizeRefundAccounting blocked: Missing or ambiguous provider execution correlation for RefundRequest #{$lockedRequest->id}. Note: '{$providerNote}', ProviderId: '{$providerRefundId}'");
                $lockedRequest->execution_status = 'reconciliation_required';
                $lockedRequest->save();
                return;
            }

            // 6. Local order/request relationship must be valid
            if ((int)$lockedRequest->order_id !== (int)$order->id) {
                Log::warning("[AI] Paystack finalizeRefundAccounting blocked: Order ID mismatch on RefundRequest #{$lockedRequest->id}");
                $lockedRequest->execution_status = 'reconciliation_required';
                $lockedRequest->save();
                return;
            }

            // Seed BCMath from raw DB decimal string — bypasses the float cast on RefundRequest.amount
            $refundAmount = bcadd((string)($lockedRequest->getRawOriginal('amount') ?? '0.00'), '0', 2);
            $isSettled = ($order->vendor_settlement_status === 'settled') || \App\Models\OrderTransaction::where('order_id', $order->id)->where('status', 'disburse')->exists();

            // [AI] Calculate redeemed cashback spent on this order
            $redeemedCashbackOnOrder = '0.00';
            if ($order->discount_type === 'cashback' && bccomp((string)($order->discount_amount ?? '0.00'), '0.00', 2) > 0) {
                $redeemedCashbackOnOrder = bcadd((string)$order->discount_amount, '0', 2);
            }

            // Exclude shipping and tax to get pure merchandise value of the order
            $rawOrderAmount = (string)($order->getRawOriginal('order_amount') ?? '0.00');
            $rawInitAmount = (string)($order->getRawOriginal('init_order_amount') ?? '0.00');
            $shippingCost = bcadd((string)($order->getRawOriginal('shipping_cost') ?? '0.00'), '0', 2);
            $taxAmount = bcadd((string)($order->getRawOriginal('total_tax_amount') ?? '0.00'), '0', 2);

            // Calculate merchandise subtotal using BCMath on raw original attributes — no float reads
            $orderSubtotal = '0.00';
            if ($order->details && $order->details->count() > 0) {
                foreach ($order->details as $detail) {
                    $itemPrice = (string)($detail->getRawOriginal('price') ?? '0.00');
                    $itemQty = (string)($detail->getRawOriginal('qty') ?? '1');
                    $itemDiscount = (string)($detail->getRawOriginal('discount') ?? '0.00');
                    $lineTotal = bcsub(bcmul($itemPrice, $itemQty, 2), $itemDiscount, 2);
                    if (bccomp($lineTotal, '0.00', 2) < 0) {
                        $lineTotal = '0.00';
                    }
                    $orderSubtotal = bcadd($orderSubtotal, $lineTotal, 2);
                }
            }
            if ($order->discount_type !== 'cashback') {
                $couponDiscount = (string)($order->getRawOriginal('discount_amount') ?? '0.00');
                $orderSubtotal = bcsub($orderSubtotal, $couponDiscount, 2);
            }
            if (bccomp($orderSubtotal, '0.00', 2) <= 0) {
                $orderSubtotal = bcsub(bcsub($rawOrderAmount, $shippingCost, 2), $taxAmount, 2);
            }

            // Determine pure merchandise money paid by customer (strictly excluding shipping and tax)
            $actualMerchandiseMoneyPaid = bcsub($orderSubtotal, $redeemedCashbackOnOrder, 2);
            if (bccomp($actualMerchandiseMoneyPaid, '0.00', 2) < 0) {
                $actualMerchandiseMoneyPaid = '0.00';
            }

            // Read item allocation breakdown from payment_info (if available)
            $payInfo = json_decode($lockedRequest->payment_info ?? '{}', true) ?: [];
            $itemMerchandiseMoney = (string)($payInfo['merchandise_money'] ?? '');
            $itemCashbackAllocated = (string)($payInfo['cashback_amount'] ?? '');
            $itemMerchandiseValue = (string)($payInfo['merchandise_value'] ?? '');
            $itemTaxAmount = (string)($payInfo['tax_amount'] ?? '0.00');

            if ($itemMerchandiseMoney === '') {
                $itemMerchandiseMoney = bcsub($refundAmount, $itemTaxAmount, 2);
                if (bccomp($itemMerchandiseMoney, '0.00', 2) < 0) {
                    $itemMerchandiseMoney = '0.00';
                }
            }

            // [AI] Calculate proportional cashback restoration based strictly on pure merchandise money refunded vs merchandise money paid (both tax-exclusive)
            $cashbackToRestore = '0.00';
            $alreadyRestored = '0.00';
            if (bccomp($redeemedCashbackOnOrder, '0.00', 2) > 0) {
                if ($itemCashbackAllocated !== '' && bccomp($itemCashbackAllocated, '0.00', 2) >= 0) {
                    $cashbackToRestore = $itemCashbackAllocated;
                } elseif (bccomp($actualMerchandiseMoneyPaid, '0.00', 2) <= 0) {
                    $cashbackToRestore = $itemMerchandiseMoney;
                } else {
                    $ratio = bcdiv($itemMerchandiseMoney, $actualMerchandiseMoneyPaid, 4);
                    if (bccomp($ratio, '1.0000', 4) > 0) {
                        $ratio = '1.0000';
                    }
                    $cashbackToRestore = bcmul($redeemedCashbackOnOrder, $ratio, 2);
                }

                // Cumulative restoration guard across all refunds on this order using immutable RefundTransaction records
                $alreadyRestored = (string)(RefundTransaction::where('order_id', $order->id)
                    ->where('payment_method', 'cashback')
                    ->where('payment_status', 'paid')
                    ->where('refund_id', '!=', $lockedRequest->id)
                    ->sum('amount') ?: '0.00');

                if (bccomp($alreadyRestored, '0.00', 2) <= 0) {
                    $completedRefunds = RefundRequest::where('order_id', $order->id)
                        ->where('id', '!=', $lockedRequest->id)
                        ->where(function ($q) {
                            $q->where('status', 'refunded')->orWhere('execution_status', 'succeeded');
                        })
                        ->get();
                    foreach ($completedRefunds as $cr) {
                        $pInfo = json_decode($cr->payment_info ?? '{}', true) ?: [];
                        if (!empty($pInfo['cashback_restored'])) {
                            $alreadyRestored = bcadd($alreadyRestored, (string)$pInfo['cashback_restored'], 2);
                        }
                    }
                }

                $maxRestorable = bcsub($redeemedCashbackOnOrder, $alreadyRestored, 2);
                if (bccomp($maxRestorable, '0.00', 2) < 0) {
                    $maxRestorable = '0.00';
                }
                if (bccomp($cashbackToRestore, $maxRestorable, 2) > 0) {
                    $cashbackToRestore = $maxRestorable;
                }
            }

            // Total returned merchandise value (strictly tax-exclusive)
            $returnedMerchandiseValue = ($itemMerchandiseValue !== '')
                ? $itemMerchandiseValue
                : bcadd($itemMerchandiseMoney, $cashbackToRestore, 2);

            // 3. Financial Reversals: Pre-Settlement Escrow vs Post-Settlement (Pure BCMath Precision)
            // Reversal is strictly based on the returned merchandise value (90% vendor, 10% commission, 0% tax)
            if (!$isSettled) {
                // [AI] Release only this order's funded hold including returned cash, tax and rewards.
                OrderManager::releaseRefundEscrow($order, bcadd($refundAmount, $cashbackToRestore, 2));
            } else {
                // [AI] Reverse the recognized merchandise/tax partition through the shared accounting helper.
                OrderManager::reverseRecognizedRefund($order, $returnedMerchandiseValue, bcadd($refundAmount, $cashbackToRestore, 2));
            }
            // Check if all items in order have been refunded
            $unrefundedItemsCount = \App\Models\OrderDetail::where('order_id', $order->id)
                ->where('id', '!=', $lockedRequest->order_details_id)
                ->where(function ($q) {
                    $q->where('refund_request', '!=', 4)->orWhereNull('refund_request');
                })
                ->count();
            $isFullOrderRefund = ($unrefundedItemsCount === 0);

            // Cumulative total merchandise value refunded across all items
            $totalMerchandiseRefundedSoFar = (string)(RefundTransaction::where('order_id', $order->id)
                ->where('payment_status', 'paid')
                ->where('refund_id', '!=', $lockedRequest->id)
                ->sum('amount') ?: '0.00');

            $cumulativeRefundedValue = bcadd($totalMerchandiseRefundedSoFar, $returnedMerchandiseValue, 2);
            $remainingMerchandise = bcsub($orderSubtotal, $cumulativeRefundedValue, 2);
            if ($isFullOrderRefund || bccomp($remainingMerchandise, '0.00', 2) <= 0) {
                $remainingMerchandise = '0.00';
            }

            // Cashback Adjustment & Settlement Status (Scoped strictly to pending rewards)
            $cashback = CustomerCashbackLedger::where('order_id', $order->id)->where('status', 'pending')->lockForUpdate()->first();

            if (bccomp($remainingMerchandise, '0.00', 2) > 0) {
                // Partial refund: adjust earning against remaining NEW MONEY allocation
                if ($cashback) {
                    $moneyRatio = (bccomp($orderSubtotal, '0.00', 2) > 0)
                        ? bcdiv($actualMerchandiseMoneyPaid, $orderSubtotal, 4)
                        : '1.0000';
                    $remainingNewMoney = bcmul($remainingMerchandise, $moneyRatio, 2);
                    $cashback->adjustForPartialRefund($remainingNewMoney);
                }
                // Order settlement status remains held/eligible
                if (empty($order->vendor_settlement_status) && $order->seller_is === 'seller') {
                    $order->vendor_settlement_status = 'held';
                    $order->save();
                }
            } else {
                // Full 100% refund: order transitions to terminal 'refunded'
                if ($order->seller_is === 'seller') {
                    $order->vendor_settlement_status = 'refunded';
                    $order->save();
                }
                if ($cashback) {
                    $cashback->status = 'cancelled';
                    $cashback->description = "Cancelled due to full merchandise refund for Order #{$order->id}";
                    $cashback->save();
                }
            }

            // [AI] Restores redeemed cashback spent on this order back to customer pool and ledger
            if (bccomp($cashbackToRestore, '0.00', 2) > 0) {
                $exchangeRate = (float) (getWebConfig(name: 'loyalty_point_exchange_rate') ?: 1.0);
                $pointsToRestore = (float) bcdiv($cashbackToRestore, (string) $exchangeRate, 4);

                if ($pointsToRestore > 0) {
                    DB::table('users')->where('id', $order->customer_id)->increment('loyalty_point', $pointsToRestore);
                    $freshBalance = (float) DB::table('users')->where('id', $order->customer_id)->value('loyalty_point');

                    DB::table('loyalty_point_transactions')->insert([
                        'user_id' => $order->customer_id,
                        'transaction_id' => \Illuminate\Support\Str::uuid()->toString(),
                        'credit' => $pointsToRestore,
                        'debit' => 0.0000,
                        'balance' => $freshBalance,
                        'reference' => 'cashback-refund-' . $order->id,
                        'transaction_type' => 'point_transfer',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $validityMonths = (int) (getWebConfig(name: 'loyalty_point_validity_months') ?: 6);
                    CustomerCashbackLedger::create([
                        'customer_id' => $order->customer_id,
                        'order_id' => $order->id,
                        'merchandise_amount' => '0.00',
                        'cashback_rate' => '0.00',
                        'cashback_amount' => $cashbackToRestore,
                        'status' => 'available',
                        'available_at' => now(),
                        'expires_at' => now()->addMonths($validityMonths),
                        'description' => "Restored cashback reward from refunded Order #{$order->id}",
                    ]);

                    Log::info("[AI] PaystackRefundService: Restored {$pointsToRestore} cashback points (₦{$cashbackToRestore}) and ledger lot to customer #{$order->customer_id} for refunded Order #{$order->id}.");
                }
            }

            // 5. Create Auditable RefundTransaction for Money (Exact Decimal String)
            $paystackId = $providerData['id'] ?? ($lockedRequest->paystack_refund_id ?? '');
            RefundTransaction::create([
                'order_id' => $lockedRequest->order_id,
                'payment_for' => 'Refund Request',
                'payer_id' => $order->seller_id ?? 1,
                'payment_receiver_id' => $lockedRequest->customer_id,
                'paid_by' => $order->seller_is ?? 'admin',
                'paid_to' => 'customer',
                'payment_method' => 'paystack',
                'payment_status' => 'paid',
                'amount' => $refundAmount,
                'transaction_type' => 'Refund',
                'order_details_id' => $lockedRequest->order_details_id,
                'refund_id' => $lockedRequest->id,
            ]);

            // Create Auditable RefundTransaction for Restored Cashback (Immutable Accounting)
            if (bccomp($cashbackToRestore, '0.00', 2) > 0) {
                RefundTransaction::create([
                    'order_id' => $lockedRequest->order_id,
                    'payment_for' => 'Refund Request',
                    'payer_id' => $order->seller_id ?? 1,
                    'payment_receiver_id' => $lockedRequest->customer_id,
                    'paid_by' => $order->seller_is ?? 'admin',
                    'paid_to' => 'customer',
                    'payment_method' => 'cashback',
                    'payment_status' => 'paid',
                    'amount' => $cashbackToRestore,
                    'transaction_type' => 'Refund',
                    'order_details_id' => $lockedRequest->order_details_id,
                    'refund_id' => $lockedRequest->id,
                ]);
            }

            // 6. Update RefundRequest to Succeeded / Refunded and record immutable restored cashback
            $payInfo['cashback_restored'] = $cashbackToRestore;
            $lockedRequest->payment_info = json_encode($payInfo);
            $lockedRequest->execution_status = 'succeeded';
            $lockedRequest->status = 'refunded';
            if (!empty($paystackId)) {
                $lockedRequest->paystack_refund_id = (string)$paystackId;
            }
            $lockedRequest->paystack_processed_at = now();
            $lockedRequest->save();

            // 7. Update OrderDetail to canonical 4 (4 = refunded)
            \App\Models\OrderDetail::where('id', $lockedRequest->order_details_id)->update([
                'refund_request' => 4,
            ]);

            Log::info("[AI] Finalized refund accounting for RefundRequest #{$lockedRequest->id} on Order #{$order->id}.", [
                'refund_amount' => $refundAmount,
                'is_settled' => $isSettled,
                'remaining_merchandise' => $remainingMerchandise,
                'paystack_id' => $paystackId,
            ]);
        });
    }

    /**
     * [AI] Finalize Manual Payment Confirmation (Executing Accounting and Status Change Atomically)
     * Executes atomic internal accounting reversal, vendor/admin wallet deductions,
     * customer cashback/loyalty points restoration, immutable RefundTransaction creation,
     * proportional partial-refund pending cashback recalculation, and status transitions
     * WITHOUT calling external payment gateways.
     */
    public function finalizeManualPaymentConfirmation(RefundRequest $refundRequest, Order $order, array $paymentData = []): array
    {
        return DB::transaction(function () use ($refundRequest, $order, $paymentData) {
            // [AI] Persisted identity and common Order -> RefundRequest lock order.
            $orderId = RefundRequest::where('id', $refundRequest->id)->value('order_id');
            $lockedOrder = Order::where('id', $orderId)->lockForUpdate()->first();
            if (!$lockedOrder || (int) $lockedOrder->id !== (int) $order->id) {
                return ['status' => false, 'message' => 'Order not found or mismatched.'];
            }
            $lockedRequest = RefundRequest::where('id', $refundRequest->id)->lockForUpdate()->first();
            if (!$lockedRequest) {
                return ['status' => false, 'message' => 'Refund request not found.'];
            }

            if ($lockedRequest->execution_status === 'succeeded' || $lockedRequest->status === 'refunded') {
                return ['status' => false, 'message' => 'Refund payment has already been confirmed and completed.'];
            }

            // Item-Level Duplicate Guard & Pessimistic Row Lock on OrderDetail
            $lockedDetail = \App\Models\OrderDetail::where('id', $lockedRequest->order_details_id)->lockForUpdate()->first();
            if ($lockedDetail && (int)$lockedDetail->refund_request === 4) {
                Log::warning("[AI] finalizeManualPaymentConfirmation blocked: OrderDetail #{$lockedDetail->id} already refunded.");
                $lockedRequest->execution_status = 'already_refunded';
                $lockedRequest->status = 'refunded';
                $lockedRequest->save();
                return ['status' => false, 'message' => 'Order detail is already marked as refunded.'];
            }

            $otherExecuted = RefundRequest::where('order_details_id', $lockedRequest->order_details_id)
                ->where('id', '!=', $lockedRequest->id)
                ->where(function ($q) {
                    $q->where('status', 'refunded')->orWhere('execution_status', 'succeeded');
                })
                ->exists();
            if ($otherExecuted) {
                Log::warning("[AI] finalizeManualPaymentConfirmation blocked: OrderDetail #{$lockedRequest->order_details_id} already has a completed refund request.");
                $lockedRequest->execution_status = 'already_refunded';
                $lockedRequest->status = 'refunded';
                $lockedRequest->save();
                return ['status' => false, 'message' => 'Order detail already has an active completed refund request.'];
            }

            // State Machine Transition Guard:
            // Payment confirmation requires the request to be 'approved' with 'awaiting_manual_payment'
            // (Exception: Pure cashback refunds completing internally during approval)
            // [AI] Only persisted reward funding may use internal completion; caller labels have no authority.
            $isPureCashbackFlow = $lockedOrder->payment_method === 'cashback';
            if ($lockedRequest->status !== 'approved') {
                return ['status' => false, 'message' => 'Refund must be approved before completion.'];
            }
            if (!$isPureCashbackFlow) {
                if ($lockedRequest->status !== 'approved' || $lockedRequest->execution_status !== 'awaiting_manual_payment') {
                    Log::warning("[AI] finalizeManualPaymentConfirmation rejected: Request #{$lockedRequest->id} is in status '{$lockedRequest->status}' ('{$lockedRequest->execution_status}'), not approved/awaiting_manual_payment.");
                    return [
                        'status' => false,
                        'message' => "Payment confirmation can only be executed for requests in 'approved' status awaiting manual payment. Current status: {$lockedRequest->status} ({$lockedRequest->execution_status}).",
                    ];
                }
            }

            if (!$lockedOrder) {
                return ['status' => false, 'message' => 'Order not found.'];
            }
            // [AI] Lock customer before reward lots; shared with maturation and redemption.
            DB::table('users')->where('id', $lockedOrder->customer_id)->lockForUpdate()->first();

            $payInfo = json_decode($lockedRequest->payment_info ?? '{}', true) ?: [];
            $refundAmount = bcadd((string)($lockedRequest->getRawOriginal('amount') ?? '0.00'), '0', 2);

            // Determine exact cashback allocation to restore
            $cashbackToRestore = '0.00';
            if (isset($payInfo['cashback_amount']) && bccomp((string)$payInfo['cashback_amount'], '0.00', 2) > 0) {
                $cashbackToRestore = bcadd((string)$payInfo['cashback_amount'], '0', 2);
            } elseif ($lockedOrder->payment_method === 'cashback') {
                if (!empty($payInfo['merchandise_value']) && bccomp((string)$payInfo['merchandise_value'], '0.00', 2) > 0) {
                    $cashbackToRestore = bcadd((string)$payInfo['merchandise_value'], '0', 2);
                } elseif (bccomp($refundAmount, '0.00', 2) > 0) {
                    $cashbackToRestore = $refundAmount;
                } elseif ($lockedDetail) {
                    $qty = (string)($lockedDetail->qty ?? '1');
                    $price = (string)($lockedDetail->price ?? '0.00');
                    $discount = (string)($lockedDetail->discount ?? '0.00');
                    $cashbackToRestore = bcsub(bcmul($qty, $price, 2), $discount, 2);
                }
            }

            // Determine pure returned merchandise value (strictly tax-exclusive)
            $returnedMerchandiseValue = '0.00';
            if (!empty($payInfo['refundable_merchandise_value']) && bccomp((string)$payInfo['refundable_merchandise_value'], '0.00', 2) > 0) {
                $returnedMerchandiseValue = bcadd((string)$payInfo['refundable_merchandise_value'], '0', 2);
            } elseif (!empty($payInfo['merchandise_value']) && bccomp((string)$payInfo['merchandise_value'], '0.00', 2) > 0) {
                $returnedMerchandiseValue = bcadd((string)$payInfo['merchandise_value'], '0', 2);
            } elseif ($lockedDetail) {
                $qty = (string)($lockedDetail->qty ?? '1');
                $price = (string)($lockedDetail->price ?? '0.00');
                $discount = (string)($lockedDetail->discount ?? '0.00');
                $returnedMerchandiseValue = bcsub(bcmul($qty, $price, 2), $discount, 2);
            } else {
                $returnedMerchandiseValue = $refundAmount;
            }

            // Determine money portion to record for manual offline refund with exact amount validation
            $expectedMoney = (string)($payInfo['refundable_money_amount'] ?? ($payInfo['money_amount'] ?? '0.00'));
            if (bccomp($expectedMoney, '0.00', 2) <= 0 && $lockedOrder->payment_method !== 'cashback') {
                $expectedMoney = bcsub($refundAmount, $cashbackToRestore, 2);
                if (bccomp($expectedMoney, '0.00', 2) < 0) {
                    $expectedMoney = '0.00';
                }
            }

            $moneyToRefund = '0.00';
            if (bccomp($expectedMoney, '0.00', 2) > 0 && ($isPureCashbackFlow || ($paymentData['payment_method'] ?? '') === 'cashback')) {
                return ['status' => false, 'message' => 'Cash-backed refunds require confirmation of the exact money payment.'];
            }
            if (!$isPureCashbackFlow && bccomp($expectedMoney, '0.00', 2) > 0) {
                if (!isset($paymentData['amount']) || $paymentData['amount'] === '' || $paymentData['amount'] === null) {
                    return [
                        'status' => false,
                        'message' => 'The confirmed payment amount is required for money refunds.',
                    ];
                }
                $submittedAmount = bcadd((string)$paymentData['amount'], '0', 2);
                if (bccomp($submittedAmount, $expectedMoney, 2) !== 0) {
                    return [
                        'status' => false,
                        'message' => "Confirmed amount (₦{$submittedAmount}) does not match the exact refundable money amount (₦{$expectedMoney}).",
                    ];
                }
                $moneyToRefund = $submittedAmount;
            } elseif ($isPureCashbackFlow) {
                $moneyToRefund = '0.00';
            }

            // Calculate order subtotal and pure merchandise money paid
            $orderSubtotal = '0.00';
            if ($lockedOrder->details && $lockedOrder->details->count() > 0) {
                foreach ($lockedOrder->details as $detail) {
                    $itemPrice = (string)($detail->getRawOriginal('price') ?? '0.00');
                    $itemQty = (string)($detail->getRawOriginal('qty') ?? '1');
                    $itemDiscount = (string)($detail->getRawOriginal('discount') ?? '0.00');
                    $lineTotal = bcsub(bcmul($itemPrice, $itemQty, 2), $itemDiscount, 2);
                    if (bccomp($lineTotal, '0.00', 2) < 0) {
                        $lineTotal = '0.00';
                    }
                    $orderSubtotal = bcadd($orderSubtotal, $lineTotal, 2);
                }
            }
            if ($lockedOrder->discount_type !== 'cashback') {
                $couponDiscount = (string)($lockedOrder->getRawOriginal('discount_amount') ?? '0.00');
                $orderSubtotal = bcsub($orderSubtotal, $couponDiscount, 2);
            }
            if (bccomp($orderSubtotal, '0.00', 2) <= 0) {
                $shippingCost = bcadd((string)($lockedOrder->getRawOriginal('shipping_cost') ?? '0.00'), '0', 2);
                $taxAmount = bcadd((string)($lockedOrder->getRawOriginal('total_tax_amount') ?? '0.00'), '0', 2);
                $rawOrderAmount = (string)($lockedOrder->getRawOriginal('order_amount') ?? '0.00');
                $orderSubtotal = bcsub(bcsub($rawOrderAmount, $shippingCost, 2), $taxAmount, 2);
            }

            // Cumulative restoration guard across all refunds on this order using immutable RefundTransaction records
            $redeemedCashbackOnOrder = ($lockedOrder->discount_type === 'cashback')
                ? bcadd((string)($lockedOrder->getRawOriginal('discount_amount') ?? '0.00'), '0', 2)
                : '0.00';

            $alreadyRestored = (string)(RefundTransaction::where('order_id', $lockedOrder->id)
                ->where('payment_method', 'cashback')
                ->where('payment_status', 'paid')
                ->where('refund_id', '!=', $lockedRequest->id)
                ->sum('amount') ?: '0.00');

            if (bccomp($alreadyRestored, '0.00', 2) <= 0) {
                $completedRefunds = RefundRequest::where('order_id', $lockedOrder->id)
                    ->where('id', '!=', $lockedRequest->id)
                    ->where(function ($q) {
                        $q->where('status', 'refunded')->orWhere('execution_status', 'succeeded');
                    })
                    ->get();
                foreach ($completedRefunds as $cr) {
                    $pInfo = json_decode($cr->payment_info ?? '{}', true) ?: [];
                    if (!empty($pInfo['cashback_restored'])) {
                        $alreadyRestored = bcadd($alreadyRestored, (string)$pInfo['cashback_restored'], 2);
                    }
                }
            }

            if (bccomp($redeemedCashbackOnOrder, '0.00', 2) > 0) {
                $maxRestorable = bcsub($redeemedCashbackOnOrder, $alreadyRestored, 2);
                if (bccomp($maxRestorable, '0.00', 2) < 0) {
                    $maxRestorable = '0.00';
                }
                if (bccomp($cashbackToRestore, $maxRestorable, 2) > 0) {
                    $cashbackToRestore = $maxRestorable;
                }
            }

            $actualMerchandiseMoneyPaid = bcsub($orderSubtotal, $redeemedCashbackOnOrder, 2);
            if (bccomp($actualMerchandiseMoneyPaid, '0.00', 2) < 0) {
                $actualMerchandiseMoneyPaid = '0.00';
            }

            $isSettled = ($lockedOrder->vendor_settlement_status === 'settled') || \App\Models\OrderTransaction::where('order_id', $lockedOrder->id)->where('status', 'disburse')->exists();

            // 1. Reversals: Pre-Settlement Escrow vs Post-Settlement (based on pure returned merchandise value)
            if (!$isSettled) {
                // [AI] Release only this order's funded hold including returned cash, tax and rewards.
                OrderManager::releaseRefundEscrow($lockedOrder, bcadd($moneyToRefund, $cashbackToRestore, 2));
            } else {
                // [AI] Reverse the recognized merchandise/tax partition through the shared accounting helper.
                OrderManager::reverseRecognizedRefund($lockedOrder, $returnedMerchandiseValue, bcadd($moneyToRefund, $cashbackToRestore, 2));
            }
            // 2. Restore Cashback Points and Ledger Lot
            $exchangeRate = (float) (getWebConfig(name: 'loyalty_point_exchange_rate') ?: 1.0);
            $pointsToRestore = (float) bcdiv($cashbackToRestore, (string) $exchangeRate, 4);

            if ($pointsToRestore > 0) {
                DB::table('users')->where('id', $lockedOrder->customer_id)->increment('loyalty_point', $pointsToRestore);
                $freshBalance = (float) DB::table('users')->where('id', $lockedOrder->customer_id)->value('loyalty_point');

                DB::table('loyalty_point_transactions')->insert([
                    'user_id' => $lockedOrder->customer_id,
                    'transaction_id' => \Illuminate\Support\Str::uuid()->toString(),
                    'credit' => $pointsToRestore,
                    'debit' => 0.0000,
                    'balance' => $freshBalance,
                    'reference' => 'cashback-refund-' . $lockedOrder->id,
                    'transaction_type' => 'point_transfer',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $validityMonths = (int) (getWebConfig(name: 'loyalty_point_validity_months') ?: 6);
                CustomerCashbackLedger::create([
                    'customer_id' => $lockedOrder->customer_id,
                    'order_id' => $lockedOrder->id,
                    'merchandise_amount' => '0.00',
                    'cashback_rate' => '0.00',
                    'cashback_amount' => $cashbackToRestore,
                    'status' => 'available',
                    'available_at' => now(),
                    'expires_at' => now()->addMonths($validityMonths),
                    'description' => "Restored cashback from internal refund on Order #{$lockedOrder->id}",
                ]);

                // Create Immutable RefundTransaction for Cashback Restoration
                RefundTransaction::create([
                    'order_id' => $lockedRequest->order_id,
                    'payment_for' => 'Refund Request',
                    'payer_id' => $lockedOrder->seller_id ?? 1,
                    'payment_receiver_id' => $lockedRequest->customer_id,
                    'paid_by' => $lockedOrder->seller_is ?? 'admin',
                    'paid_to' => 'customer',
                    'payment_method' => 'cashback',
                    'payment_status' => 'paid',
                    'amount' => $cashbackToRestore,
                    'transaction_type' => 'Refund',
                    'order_details_id' => $lockedRequest->order_details_id,
                    'refund_id' => $lockedRequest->id,
                ]);
            }

            // 3. Create Immutable RefundTransaction for Manual Money Refund
            $moneyPaymentMethod = $paymentData['payment_method'] ?? 'manual_offline';
            if ($moneyPaymentMethod === 'cashback') {
                $moneyPaymentMethod = 'manual_offline';
            }
            $paymentRef = $paymentData['payment_reference'] ?? ($paymentData['payment_info'] ?? ('manual_refund_' . $lockedRequest->id));
            $paymentDate = !empty($paymentData['payment_date']) ? \Carbon\Carbon::parse($paymentData['payment_date'])->toDateString() : now()->toDateString();
            $paymentEvidence = $paymentData['payment_evidence'] ?? null;
            $confirmingAdminId = $paymentData['confirmed_by'] ?? (auth('admin')->id() ?? 1);

            if (bccomp($moneyToRefund, '0.00', 2) > 0) {
                RefundTransaction::create([
                    'order_id' => $lockedRequest->order_id,
                    'payment_for' => 'Refund Request',
                    'payer_id' => $lockedOrder->seller_id ?? 1,
                    'payment_receiver_id' => $lockedRequest->customer_id,
                    'paid_by' => $lockedOrder->seller_is ?? 'admin',
                    'paid_to' => 'customer',
                    'payment_method' => $moneyPaymentMethod,
                    'payment_status' => 'paid',
                    'amount' => $moneyToRefund,
                    'transaction_type' => 'Refund',
                    'order_details_id' => $lockedRequest->order_details_id,
                    'refund_id' => $lockedRequest->id,
                ]);
            }

            // 4. Update RefundRequest status and record immutable confirmed payment details
            $payInfo['cashback_restored'] = $cashbackToRestore;
            $payInfo['money_refunded'] = $moneyToRefund;
            $payInfo['confirmed_amount'] = $moneyToRefund;
            $payInfo['confirmed_payment_method'] = $moneyPaymentMethod;
            $payInfo['confirmed_payment_info'] = $paymentRef;
            $payInfo['payment_reference'] = $paymentRef;
            $payInfo['payment_date'] = $paymentDate;
            if ($paymentEvidence) {
                $payInfo['payment_evidence'] = $paymentEvidence;
            }
            $payInfo['confirmed_by_admin_id'] = $confirmingAdminId;

            $lockedRequest->payment_info = json_encode($payInfo);
            $lockedRequest->execution_status = 'succeeded';
            $lockedRequest->status = 'refunded';
            $lockedRequest->change_by = 'admin';
            $lockedRequest->save();

            // 5. Update OrderDetail to canonical 4 (4 = refunded)
            if ($lockedDetail) {
                $lockedDetail->update([
                    'refund_request' => 4, // 4 = Refunded (canonical)
                ]);
            }

            // 6. Proportional Partial-Refund vs Full-Refund Settlement & Pending Cashback Adjustment
            $unrefundedItemsCount = \App\Models\OrderDetail::where('order_id', $lockedOrder->id)
                ->where('id', '!=', $lockedRequest->order_details_id)
                ->where(function ($q) {
                    $q->where('refund_request', '!=', 4)->orWhereNull('refund_request');
                })
                ->count();
            $isFullOrderRefund = ($unrefundedItemsCount === 0);

            // [AI] Sum immutable merchandise-return allocations separately from tax refunds and payment transactions
            $previouslyRefundedRequests = RefundRequest::where('order_id', $lockedOrder->id)
                ->where('id', '!=', $lockedRequest->id)
                ->where('status', 'refunded')
                ->get();

            $previouslyReturnedMerchandise = '0.00';
            foreach ($previouslyRefundedRequests as $prevReq) {
                $prevPayInfo = json_decode($prevReq->payment_info ?? '{}', true) ?: [];
                $prevMerch = (string)($prevPayInfo['refundable_merchandise_value'] ?? '0.00');
                if (bccomp($prevMerch, '0.00', 2) <= 0 && $prevReq->orderDetails) {
                    $prevQty = (string)($prevReq->orderDetails->qty ?? 1);
                    $prevPrice = (string)($prevReq->orderDetails->price ?? 0);
                    $prevDiscount = (string)($prevReq->orderDetails->discount ?? 0);
                    $prevMerch = bcsub(bcmul($prevQty, $prevPrice, 2), $prevDiscount, 2);
                }
                $previouslyReturnedMerchandise = bcadd($previouslyReturnedMerchandise, $prevMerch, 2);
            }

            $cumulativeRefundedValue = bcadd($previouslyReturnedMerchandise, $returnedMerchandiseValue, 2);
            $remainingMerchandise = bcsub($orderSubtotal, $cumulativeRefundedValue, 2);
            if ($isFullOrderRefund || bccomp($remainingMerchandise, '0.00', 2) <= 0) {
                $remainingMerchandise = '0.00';
            }

            $cashback = CustomerCashbackLedger::where('order_id', $lockedOrder->id)->where('status', 'pending')->lockForUpdate()->first();

            if (bccomp($remainingMerchandise, '0.00', 2) > 0) {
                // Partial refund: adjust pending reward to remaining eligible new money
                if ($cashback) {
                    $moneyRatio = (bccomp($orderSubtotal, '0.00', 2) > 0)
                        ? bcdiv($actualMerchandiseMoneyPaid, $orderSubtotal, 4)
                        : '1.0000';
                    $remainingNewMoney = bcmul($remainingMerchandise, $moneyRatio, 2);
                    $cashback->adjustForPartialRefund($remainingNewMoney);
                }
                if (empty($lockedOrder->vendor_settlement_status) && $lockedOrder->seller_is === 'seller') {
                    $lockedOrder->vendor_settlement_status = 'held';
                    $lockedOrder->save();
                }
            } else {
                // Full refund: order transitions to refunded
                $lockedOrder->order_status = 'refunded';
                if ($lockedOrder->seller_is === 'seller') {
                    $lockedOrder->vendor_settlement_status = 'refunded';
                }
                $lockedOrder->save();

                if ($cashback) {
                    $cashback->status = 'cancelled';
                    $cashback->description = "Cancelled due to full merchandise refund for Order #{$lockedOrder->id}";
                    $cashback->save();
                }
            }

            Log::info("[AI] PaystackRefundService: Completed manual payment confirmation for RefundRequest #{$lockedRequest->id} on Order #{$lockedOrder->id}.");
            return ['status' => true, 'message' => 'Refund payment confirmed and finalized successfully.'];
        });
    }

    public function finalizeManualOrderRefund(RefundRequest $refundRequest, Order $order): void
    {
        $this->finalizeManualPaymentConfirmation($refundRequest, $order);
    }

    /**
     * [AI] Backward compatibility wrapper for cashback-funded orders.
     */
    public function finalizeCashbackOrderRefund(RefundRequest $refundRequest, Order $order): void
    {
        $this->finalizeManualPaymentConfirmation($refundRequest, $order, ['payment_method' => 'cashback']);
    }
}
