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
            $matchedOrder = Order::where('transaction_ref', $txRef)->first();
            if ($matchedOrder) {
                $candidates = RefundRequest::where('order_id', $matchedOrder->id)
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
                    Log::warning("[AI] Paystack Refund Webhook: Ambiguous refund requests on Order #{$matchedOrder->id}. Setting reconciliation_required.");
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
            return [
                'status' => true,
                'code' => 200,
                'message' => 'Event logged; local correlation not found.',
            ];
        }

        // Transaction reference validation
        $order = Order::find($refundRequest->order_id);
        if ($order && !empty($txRef) && !empty($order->transaction_ref) && $txRef !== $order->transaction_ref) {
            Log::warning("[AI] Paystack Refund Webhook: Transaction reference mismatch for RefundRequest #{$refundRequest->id}.");
            return [
                'status' => false,
                'code' => 400,
                'message' => 'Transaction reference mismatch.',
            ];
        }

        // Amount validation
        $expectedKobo = (int) round(bcmul((string)($refundRequest->getRawOriginal('amount') ?? '0.00'), '100', 2));
        if ($amountInKobo > 0 && $amountInKobo !== $expectedKobo) {
            Log::warning("[AI] Paystack Refund Webhook: Amount mismatch for RefundRequest #{$refundRequest->id}. Expected {$expectedKobo}, got {$amountInKobo}.");
            $refundRequest->execution_status = 'reconciliation_required';
            $refundRequest->save();
            return [
                'status' => false,
                'code' => 400,
                'message' => 'Refund amount mismatch.',
            ];
        }

        // Update paystack_refund_id if not stored
        if (empty($refundRequest->paystack_refund_id) && !empty($refundId)) {
            $refundRequest->paystack_refund_id = $refundId;
            $refundRequest->save();
        }

        // Process Event Transitions
        switch ($event) {
            case 'refund.pending':
            case 'refund.processing':
                $refundRequest->execution_status = 'pending_provider_processing';
                $refundRequest->save();
                break;

            case 'refund.needs-attention':
                $refundRequest->execution_status = 'needs_attention';
                $refundRequest->save();
                break;

            case 'refund.failed':
                $refundRequest->execution_status = 'failed';
                $refundRequest->rejected_note = $fullRefund['provider_response'] ?? ($fullRefund['status'] ?? 'Provider failed refund');
                $refundRequest->save();
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
            // 1. Pessimistic Row-Level Lock
            $lockedRequest = RefundRequest::where('id', $refundRequest->id)->lockForUpdate()->first();
            if (!$lockedRequest) {
                return;
            }

            // 2. Exact-Once Idempotency Guard
            if ($lockedRequest->execution_status === 'succeeded' || $lockedRequest->status === 'refunded') {
                return; // Zero duplicate financial mutations
            }

            $order = Order::where('id', $lockedRequest->order_id)->lockForUpdate()->first();
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
            $orderTxRef = (string)($order->getRawOriginal('transaction_ref') ?? '');
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
            $isSettled = ($order->vendor_settlement_status === 'settled');

            // 3. Financial Reversals: Pre-Settlement Escrow vs Post-Settlement (Pure BCMath Precision)
            if (!$isSettled) {
                // Pre-Settlement Reversal:
                // Full funds still sit in platform escrow (AdminWallet.pending_amount).
                // Seller has NOT been paid; commission has NOT been recognized.
                // Reversal releases funds strictly from AdminWallet.pending_amount.
                // SellerWallet: 0.00 movement; Admin commission: 0.00 movement.
                $adminWallet = AdminWallet::where('admin_id', 1)->lockForUpdate()->first();
                if ($adminWallet) {
                    // getRawOriginal() bypasses the float cast on AdminWallet.pending_amount
                    $currentPending = bcadd((string)($adminWallet->getRawOriginal('pending_amount') ?? '0.00'), '0', 2);
                    $newPendingDiff = bcsub($currentPending, $refundAmount, 2);
                    $adminWallet->pending_amount = (bccomp($newPendingDiff, '0.00', 2) < 0) ? '0.00' : $newPendingDiff;
                    $adminWallet->save();
                }
            } else {
                // Post-Settlement Reversal:
                // Vendor was already paid (90%) and Commission was earned (10%).
                // Reverse 90% from SellerWallet.total_earning, and 10% from AdminWallet.commission_earned.
                $vendorShare = bcmul($refundAmount, '0.90', 2);
                $commissionShare = bcmul($refundAmount, '0.10', 2);

                $sellerWallet = SellerWallet::where('seller_id', $order->seller_id)->lockForUpdate()->first();
                if ($sellerWallet) {
                    // getRawOriginal() bypasses float casts on all SellerWallet monetary columns
                    $currentEarning = bcadd((string)($sellerWallet->getRawOriginal('total_earning') ?? '0.00'), '0', 2);
                    // Merchant Recoverable Debt Accounting:
                    // If vendor earnings are insufficient to cover vendorShare,
                    // floor wallet balance at 0.00 and post unrecovered variance to collected_cash.
                    if (bccomp($currentEarning, $vendorShare, 2) >= 0) {
                        $newEarning = bcsub($currentEarning, $vendorShare, 2);
                        $unrecoveredDebt = '0.00';
                    } else {
                        $newEarning = '0.00';
                        $unrecoveredDebt = bcsub($vendorShare, $currentEarning, 2);
                    }
                    $sellerWallet->total_earning = $newEarning;
                    if (bccomp($unrecoveredDebt, '0.00', 2) > 0) {
                        $currentCollectedCash = bcadd((string)($sellerWallet->getRawOriginal('collected_cash') ?? '0.00'), '0', 2);
                        $sellerWallet->collected_cash = bcadd($currentCollectedCash, $unrecoveredDebt, 2);
                    }
                    $currentCommissionGiven = bcadd((string)($sellerWallet->getRawOriginal('commission_given') ?? '0.00'), '0', 2);
                    $commDiff = bcsub($currentCommissionGiven, $commissionShare, 2);
                    $sellerWallet->commission_given = (bccomp($commDiff, '0.00', 2) < 0) ? '0.00' : $commDiff;
                    $sellerWallet->save();
                }

                $adminWallet = AdminWallet::where('admin_id', 1)->lockForUpdate()->first();
                if ($adminWallet) {
                    // getRawOriginal() bypasses float cast on AdminWallet.commission_earned
                    $currentCommissionEarned = bcadd((string)($adminWallet->getRawOriginal('commission_earned') ?? '0.00'), '0', 2);
                    $adminCommDiff = bcsub($currentCommissionEarned, $commissionShare, 2);
                    $adminWallet->commission_earned = (bccomp($adminCommDiff, '0.00', 2) < 0) ? '0.00' : $adminCommDiff;
                    $adminWallet->save();
                }
            }

            // 4. Proportional Partial Refund vs Full 100% Refund
            // Raw SQL CAST(SUM AS CHAR) avoids PHP float aggregation from Eloquent sum()
            $totalRefundedSoFarResult = DB::select(
                'SELECT CAST(COALESCE(SUM(amount), 0.00) AS CHAR) AS total FROM refund_requests WHERE order_id = ? AND status = ? AND id != ?',
                [$order->id, 'refunded', $lockedRequest->id]
            );
            $totalRefundedSoFar = (string)(isset($totalRefundedSoFarResult[0]) ? $totalRefundedSoFarResult[0]->total : '0.00');
            $cumulativeRefunded = bcadd($totalRefundedSoFar, $refundAmount, 2);

            // Calculate merchandise subtotal using BCMath on raw original attributes — no float reads
            $orderSubtotal = '0.00';
            if ($order->details && $order->details->count() > 0) {
                foreach ($order->details as $detail) {
                    $itemPrice = (string)($detail->getRawOriginal('price') ?? '0.00');
                    $itemQty = (string)($detail->getRawOriginal('qty') ?? '1');
                    $lineTotal = bcmul($itemPrice, $itemQty, 2);
                    $orderSubtotal = bcadd($orderSubtotal, $lineTotal, 2);
                }
            }
            if (bccomp($orderSubtotal, '0.00', 2) <= 0) {
                $rawOrderAmount = (string)($order->getRawOriginal('order_amount') ?? '0.00');
                $rawShippingCost = (string)($order->getRawOriginal('shipping_cost') ?? '0.00');
                $orderSubtotal = bcsub($rawOrderAmount, $rawShippingCost, 2);
            }
            $remainingMerchandise = bcsub($orderSubtotal, $cumulativeRefunded, 2);

            // Cashback Adjustment & Settlement Status (Scoped strictly to pending rewards)
            $cashback = CustomerCashbackLedger::where('order_id', $order->id)->where('status', 'pending')->lockForUpdate()->first();

            if (bccomp($remainingMerchandise, '0.00', 2) > 0) {
                // Partial refund: order preserves remaining entitlement; NOT terminal refunded
                if ($cashback) {
                    $cashback->adjustForPartialRefund($remainingMerchandise);
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

            // 5. Create Auditable RefundTransaction (Exact Decimal String)
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

            // 6. Update RefundRequest to Succeeded / Refunded
            $lockedRequest->execution_status = 'succeeded';
            $lockedRequest->status = 'refunded';
            if (!empty($paystackId)) {
                $lockedRequest->paystack_refund_id = (string)$paystackId;
            }
            $lockedRequest->paystack_processed_at = now();
            $lockedRequest->save();

            Log::info("[AI] Finalized refund accounting for RefundRequest #{$lockedRequest->id} on Order #{$order->id}.", [
                'refund_amount' => $refundAmount,
                'is_settled' => $isSettled,
                'remaining_merchandise' => $remainingMerchandise,
                'paystack_id' => $paystackId,
            ]);
        });
    }
}
