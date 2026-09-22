<?php

namespace App\Http\Controllers\Payment_Methods;

use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\PaystackBankService;
use App\Traits\Processor;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaystackController extends Controller
{
    use Processor;

    private PaymentRequest $payment;
    private $user;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('paystack', 'payment_config');
        $values = false;
        if (!is_null($config) && $config->mode == 'live') {
            $values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $values = json_decode($config->test_values);
        }

        if ($values) {
            $config = array(
                'publicKey' => env('PAYSTACK_PUBLIC_KEY', $values->public_key),
                'secretKey' => env('PAYSTACK_SECRET_KEY', $values->secret_key),
                'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
                'merchantEmail' => env('MERCHANT_EMAIL', $values->merchant_email),
            );
            Config::set('paystack', $config);
        }

        $this->payment = $payment;
        $this->user = $user;
    }

    public function index(Request $request): JsonResponse|Redirector|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        // Fail-Closed Currency Policy: Explicitly Require NGN (Step 2 - L)
        $currencyCode = strtoupper((string)($data['currency_code'] ?? ''));
        if ($currencyCode !== 'NGN') {
            Log::error("Paystack initialize: Unsupported or missing currency '{$currencyCode}' on payment #{$data['id']}. Victorious MARKET requires NGN.");
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, ['message' => 'Unsupported currency. Victorious MARKET requires NGN.']), 400);
        }

        // [AI] Customer Ownership Check — UUID secrecy is NOT a sufficient authorization mechanism.
        // For marketplace payments (delivery/pickup), verify the authenticated customer owns this PaymentRequest.
        if (in_array($data->payment_domain, ['marketplace_delivery', 'marketplace_pickup'], true)) {
            $authenticatedCustomerId = auth('customer')->id() ?? auth('api')->id();
            if ($authenticatedCustomerId && (string) $data->payer_id !== (string) $authenticatedCustomerId) {
                Log::warning('Paystack initialize: Customer ownership check failed.', [
                    'payment_id'             => $data->id,
                    'payment_domain'         => $data->payment_domain,
                    'expected_payer_id'      => $data->payer_id,
                    'authenticated_customer' => $authenticatedCustomerId,
                ]);
                return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, ['message' => 'Unauthorized payment request.']), 403);
            }
        }

        $payer = json_decode($data['payer_information'], true);

        $url = "https://api.paystack.co/transaction/initialize";

        // High-Entropy Cryptographic Reference: Collision-Free Within Same Second (Step 2 - L)
        $highEntropyReference = 'VM_' . \Illuminate\Support\Str::orderedUuid()->toString();

        $fields = [
            'email' => $payer['email'] ?? "customer@email.com",
            'amount' => (int) round(($data['payment_amount'] ?? 0) * 100),
            'currency' => 'NGN',
            'reference' => $highEntropyReference,
            'callback_url' => route('paystack.callback', ['payment_id' => $data['id']]),
            'metadata' => [
                'payment_id' => $data['id'],
            ]
        ];

        $fields_string = http_build_query($fields);
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . Config::get('paystack.secretKey'),
            "Cache-Control: no-cache",
        ));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);

        if ($response['status'] && isset($response['data']['authorization_url'])) {
            return redirect($response['data']['authorization_url']);
        }

        return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
    }

    public function handleGatewayCallback(Request $request): Redirector|RedirectResponse
    {
        $paymentDetails = self::getPayStackPaymentData(request: $request);
        $routePaymentId = $request->query('payment_id');

        switch ($paymentDetails['class']) {
            case 'SUCCESS':
                $txData = $paymentDetails['data'] ?? [];
                $verifiedReference = $paymentDetails['reference']; // Authoritative Paystack reference (Step 2 - B & L)
                $metadata = $txData['metadata'] ?? [];
                $metadataPaymentId = $metadata['payment_id'] ?? null;

                // Validate response shape before touching PaymentRequest (Step 2 - L)
                if (empty($verifiedReference) || empty($metadataPaymentId)) {
                    Log::error("Paystack callback: SUCCESS missing authoritative reference or metadata.payment_id.", [
                        'reference' => $verifiedReference,
                        'metadata' => $metadata,
                    ]);
                    return $this->payment_response(null, 'fail');
                }

                // Identity consistency check: route parameter vs gateway metadata (Step 2 - L)
                if (!empty($routePaymentId) && $routePaymentId !== $metadataPaymentId) {
                    Log::error("Paystack callback: Route payment_id ({$routePaymentId}) does not match gateway metadata ({$metadataPaymentId}).");
                    return $this->payment_response(null, 'fail');
                }

                // Load PaymentRequest using verified identity
                $paymentRequest = $this->payment::where('id', $metadataPaymentId)->first();
                if (!$paymentRequest) {
                    Log::warning("Paystack callback: PaymentRequest #{$metadataPaymentId} not found in database.");
                    return $this->payment_response(null, 'fail');
                }

                // Explicit 2-Step Currency Validation (Step 2 - L)
                $expectedCurrency = strtoupper((string) $paymentRequest->currency_code);
                if ($expectedCurrency !== 'NGN') {
                    Log::error("Paystack callback: Unsupported currency configured for payment: {$expectedCurrency}");
                    return $this->payment_response($paymentRequest, 'fail');
                }
                $gatewayCurrency = strtoupper($txData['currency'] ?? '');
                if ($gatewayCurrency !== $expectedCurrency) {
                    Log::error("Paystack callback: Gateway currency ({$gatewayCurrency}) does not match expected ({$expectedCurrency}).");
                    return $this->payment_response($paymentRequest, 'fail');
                }

                // [AI] Commit 4 & Commit 6 Order Settlement Routing
                if ($paymentRequest->payment_domain === 'marketplace_delivery') {
                    $settlementService = app(\App\Services\DeliveryOrderSettlementService::class);
                    $settlementResult = $settlementService->settleVerifiedPayment($verifiedReference, $txData);
                    if (in_array($settlementResult['status'] ?? '', ['CLAIMED', 'ALREADY_PAID'], true)) {
                        return $this->payment_response($settlementResult['payment_request'] ?? $paymentRequest, 'success');
                    }
                    return $this->payment_response($settlementResult['payment_request'] ?? $paymentRequest, 'fail');
                } elseif ($paymentRequest->payment_domain === 'marketplace_pickup') {
                    $settlementService = app(\App\Services\PickupOrderSettlementService::class);
                    $settlementResult = $settlementService->settleVerifiedPayment($verifiedReference, $txData);
                    if (in_array($settlementResult['status'] ?? '', ['CLAIMED', 'ALREADY_SETTLED', 'ALREADY_PAID'], true)) {
                        return $this->payment_response($settlementResult['payment_request'] ?? $paymentRequest, 'success');
                    }
                    return $this->payment_response($settlementResult['payment_request'] ?? $paymentRequest, 'fail');
                }

                // [AI] Legacy digital_payment_success fallback removed.
                // All e-commerce checkout payments MUST have payment_domain = 'marketplace_delivery'
                // or 'marketplace_pickup' and are settled exclusively by their respective settlement
                // services. A PaymentRequest without a recognized payment_domain reaching this point
                // is an architectural violation — reject it hard to prevent unauthorized order creation.
                Log::error('Paystack callback: PaymentRequest has no recognized payment_domain. Rejecting to prevent legacy path execution.', [
                    'payment_id'     => $metadataPaymentId,
                    'payment_domain' => $paymentRequest->payment_domain ?? 'null',
                    'reference'      => $verifiedReference,
                ]);
                return $this->payment_response($paymentRequest, 'fail');

            case 'NON_FINAL':
                Log::info("Paystack callback: Transaction non-final ({$paymentDetails['error_msg']}).", [
                    'requested_reference' => $paymentDetails['requested_reference'] ?? null,
                ]);
                $paymentRequest = !empty($routePaymentId) ? $this->payment::where('id', $routePaymentId)->first() : null;
                return $this->payment_response($paymentRequest, 'fail');

            case 'GATEWAY_FAILURE':
            case 'REFERENCE_NOT_FOUND':
                Log::info("Paystack callback: Gateway definitive failure: {$paymentDetails['error_msg']}", [
                    'requested_reference' => $paymentDetails['requested_reference'] ?? null,
                ]);
                $paymentRequest = !empty($routePaymentId) ? $this->payment::where('id', $routePaymentId)->first() : null;
                if ($paymentRequest && function_exists($paymentRequest->failure_hook)) {
                    call_user_func($paymentRequest->failure_hook, $paymentRequest);
                }
                return $this->payment_response($paymentRequest, 'fail');

            case 'TRANSPORT_ERROR':
            case 'HTTP_ERROR':
            case 'MALFORMED_GATEWAY_RESPONSE':
            case 'REQUEST_ERROR':
            default:
                Log::warning("Paystack callback indeterminate / integration error ({$paymentDetails['class']}): {$paymentDetails['error_msg']}", [
                    'requested_reference' => $paymentDetails['requested_reference'] ?? null,
                ]);
                $paymentRequest = !empty($routePaymentId) ? $this->payment::where('id', $routePaymentId)->first() : null;
                return $this->payment_response($paymentRequest, 'fail');
        }
    }

    public function cancel(Request $request): Application|JsonResponse|Redirector|RedirectResponse
    {
        $payment_data = $this->payment::where(['id' => $request['payments_id']])->first();
        if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }

    protected function getPayStackPaymentData(Request|array $request): array
    {
        $reference = $request instanceof Request ? $request->query('reference') : ($request['reference'] ?? ($request['trxref'] ?? null));

        // 1. REQUEST_ERROR: Missing or invalid callback reference / malformed input (Precondition C: http_code = 0)
        if (empty($reference) || !is_string($reference)) {
            $errorMsg = 'Missing or invalid transaction reference.';
            Log::warning("Paystack verify request error: {$errorMsg}");
            return [
                'class' => 'REQUEST_ERROR',
                'status' => false,
                'http_code' => 0,
                'error_code' => 0,
                'error_msg' => $errorMsg,
                'requested_reference' => is_string($reference) ? $reference : null,
                'reference' => null,
                'data' => [],
            ];
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . Config::get('paystack.secretKey'),
                "Cache-Control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $errno = curl_errno($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // 2. TRANSPORT_ERROR: cURL connection failure, DNS failure, timeout, socket failure
        if ($response === false) {
            $errorMsg = $err ?: 'Transport communication failure.';
            Log::warning("Paystack verify transport failure [errno: {$errno}]: {$errorMsg}", [
                'requested_reference' => $reference,
            ]);
            return [
                'class' => 'TRANSPORT_ERROR',
                'status' => false,
                'http_code' => $httpCode ?: 0,
                'error_code' => $errno,
                'error_msg' => $errorMsg,
                'requested_reference' => $reference,
                'reference' => null,
                'data' => [],
            ];
        }

        // 3. Precondition D: HTTP 404 is deterministic REFERENCE_NOT_FOUND regardless of JSON decode
        if ($httpCode === 404) {
            $decoded = json_decode($response, true);
            $errorMsg = (is_array($decoded) && !empty($decoded['message'])) ? $decoded['message'] : 'Transaction reference not found.';
            Log::info("Paystack verify: Transaction reference not found on gateway.", [
                'requested_reference' => $reference,
                'http_code' => 404,
            ]);
            return [
                'class' => 'REFERENCE_NOT_FOUND',
                'status' => false,
                'http_code' => 404,
                'error_code' => 0,
                'error_msg' => $errorMsg,
                'requested_reference' => $reference,
                'reference' => null,
                'data' => is_array($decoded) && isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : [],
            ];
        }

        $decoded = json_decode($response, true);

        // 4. Decode handling: JSON decode failure
        if (!is_array($decoded)) {
            // Upstream HTTP 5xx / 4xx non-JSON body
            if ($httpCode >= 400) {
                $errorMsg = "Gateway returned HTTP {$httpCode} with non-JSON body.";
                Log::warning("Paystack verify returned HTTP error: {$errorMsg}", [
                    'requested_reference' => $reference,
                    'http_code' => $httpCode,
                ]);
                return [
                    'class' => 'HTTP_ERROR',
                    'status' => false,
                    'http_code' => $httpCode,
                    'error_code' => $errno,
                    'error_msg' => $errorMsg,
                    'requested_reference' => $reference,
                    'reference' => null,
                    'data' => [],
                ];
            }

            // HTTP 200 with malformed JSON body
            $errorMsg = 'Malformed JSON response from gateway: ' . json_last_error_msg();
            Log::warning("Paystack verify returned malformed gateway response: {$errorMsg}", [
                'requested_reference' => $reference,
            ]);
            return [
                'class' => 'MALFORMED_GATEWAY_RESPONSE',
                'status' => false,
                'http_code' => $httpCode,
                'error_code' => json_last_error(),
                'error_msg' => $errorMsg,
                'requested_reference' => $reference,
                'reference' => null,
                'data' => [],
            ];
        }

        $message = (string)($decoded['message'] ?? '');
        $topStatus = $decoded['status'] ?? null;
        $txData = $decoded['data'] ?? null;

        // 5. Explicit reference not found in 200 responses
        $isNotFoundMessage = (stripos($message, 'not found') !== false || stripos($message, 'invalid reference') !== false);
        if ($topStatus === false && $isNotFoundMessage) {
            $errorMsg = $message ?: 'Transaction reference not found.';
            Log::info("Paystack verify: Transaction reference not found on gateway.", [
                'requested_reference' => $reference,
                'http_code' => $httpCode,
            ]);
            return [
                'class' => 'REFERENCE_NOT_FOUND',
                'status' => false,
                'http_code' => $httpCode,
                'error_code' => 0,
                'error_msg' => $errorMsg,
                'requested_reference' => $reference,
                'reference' => null,
                'data' => is_array($txData) ? $txData : [],
            ];
        }

        // 6. HTTP_ERROR: Upstream HTTP failure (400, 401, 429, 500, 502, 503)
        if ($httpCode >= 400) {
            $errorMsg = $message ?: "Gateway returned HTTP {$httpCode}.";
            Log::warning("Paystack verify returned HTTP error: {$errorMsg}", [
                'requested_reference' => $reference,
                'http_code' => $httpCode,
            ]);
            return [
                'class' => 'HTTP_ERROR',
                'status' => false,
                'http_code' => $httpCode,
                'error_code' => 0,
                'error_msg' => $errorMsg,
                'requested_reference' => $reference,
                'reference' => null,
                'data' => is_array($txData) ? $txData : [],
            ];
        }

        // 7. Top-level status false without "not found"
        if ($topStatus === false) {
            $errorMsg = $message ?: 'Gateway returned false status.';
            Log::warning("Paystack verify gateway failure: {$errorMsg}", [
                'requested_reference' => $reference,
            ]);
            return [
                'class' => 'GATEWAY_FAILURE',
                'status' => false,
                'http_code' => $httpCode,
                'error_code' => 0,
                'error_msg' => $errorMsg,
                'requested_reference' => $reference,
                'reference' => null,
                'data' => is_array($txData) ? $txData : [],
            ];
        }

        // 8. Structural validation of success responses (missing data array)
        if (!is_array($txData)) {
            $errorMsg = 'Response body missing transaction data object.';
            Log::warning("Paystack verify returned malformed gateway response: {$errorMsg}", [
                'requested_reference' => $reference,
            ]);
            return [
                'class' => 'MALFORMED_GATEWAY_RESPONSE',
                'status' => false,
                'http_code' => $httpCode,
                'error_code' => 0,
                'error_msg' => $errorMsg,
                'requested_reference' => $reference,
                'reference' => null,
                'data' => [],
            ];
        }

        $txStatus = strtolower((string)($txData['status'] ?? ''));
        $verifiedRef = !empty($txData['reference']) ? (string)$txData['reference'] : null;

        // 9. SUCCESS: valid response, status true, data.status == success, valid data.reference present
        if ($topStatus === true && $txStatus === 'success') {
            if (empty($verifiedRef)) {
                $errorMsg = 'Success response missing authoritative data.reference.';
                Log::warning("Paystack verify returned malformed gateway response: {$errorMsg}", [
                    'requested_reference' => $reference,
                ]);
                return [
                    'class' => 'MALFORMED_GATEWAY_RESPONSE',
                    'status' => false,
                    'http_code' => $httpCode,
                    'error_code' => 0,
                    'error_msg' => $errorMsg,
                    'requested_reference' => $reference,
                    'reference' => null,
                    'data' => $txData,
                ];
            }

            return [
                'class' => 'SUCCESS',
                'status' => true,
                'http_code' => $httpCode,
                'error_code' => 0,
                'error_msg' => '',
                'requested_reference' => $reference,
                'reference' => $verifiedRef,
                'data' => $txData,
            ];
        }

        // 10. NON_FINAL: pending, ongoing, processing, queued
        if (in_array($txStatus, ['pending', 'ongoing', 'processing', 'queued'], true)) {
            return [
                'class' => 'NON_FINAL',
                'status' => false,
                'http_code' => $httpCode,
                'error_code' => 0,
                'error_msg' => "Transaction status is {$txStatus}.",
                'requested_reference' => $reference,
                'reference' => null,
                'data' => $txData,
            ];
        }

        // 11. GATEWAY_FAILURE: failed, abandoned
        if (in_array($txStatus, ['failed', 'abandoned'], true)) {
            $gatewayResponse = (string)($txData['gateway_response'] ?? '');
            $errorMsg = "Transaction failed with status '{$txStatus}'." . ($gatewayResponse ? " Reason: {$gatewayResponse}" : "");
            return [
                'class' => 'GATEWAY_FAILURE',
                'status' => false,
                'http_code' => $httpCode,
                'error_code' => 0,
                'error_msg' => $errorMsg,
                'requested_reference' => $reference,
                'reference' => null,
                'data' => $txData,
            ];
        }

        // 12. MALFORMED_GATEWAY_RESPONSE: unexpected payload shape or unknown data.status
        $errorMsg = "Unexpected transaction status: '{$txStatus}'.";
        Log::warning("Paystack verify returned malformed gateway response: {$errorMsg}", [
            'requested_reference' => $reference,
        ]);
        return [
            'class' => 'MALFORMED_GATEWAY_RESPONSE',
            'status' => false,
            'http_code' => $httpCode,
            'error_code' => 0,
            'error_msg' => $errorMsg,
            'requested_reference' => $reference,
            'reference' => null,
            'data' => $txData,
        ];
    }

    /**
     * Handle Paystack Asynchronous Webhooks with Strict HMAC-SHA512 Cryptographic Signature Verification
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function webhook(Request $request): JsonResponse
    {
        $secretKey = Config::get('paystack.secretKey');
        if (empty($secretKey)) {
            $paystackBankService = app(PaystackBankService::class);
            $secretKey = $paystackBankService->getSecretKey();
        }

        $paystackSignature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        // 1. Strict Cryptographic Verification
        if (empty($paystackSignature) || empty($secretKey) || !hash_equals(hash_hmac('sha512', $payload, $secretKey), $paystackSignature)) {
            Log::warning('Paystack Webhook: Signature verification failed or invalid signature header.', [
                'has_signature' => !empty($paystackSignature),
                'ip' => $request->ip(),
            ]);
            return response()->json(['status' => false, 'message' => 'Invalid signature'], 401);
        }

        $event = json_decode($payload, true);
        if (!$event || !isset($event['event'])) {
            return response()->json(['status' => false, 'message' => 'Invalid event payload'], 400);
        }

        Log::info('Paystack Webhook received event: ' . $event['event']);

        // 2. Process Successful Charge
        if ($event['event'] === 'charge.success') {
            $data = $event['data'] ?? [];
            $reference = $data['reference'] ?? null;
            $amountPaid = $data['amount'] ?? 0; // in kobo
            $metadata = $data['metadata'] ?? [];

            // A. Check standard e-commerce PaymentRequest
            $paymentId = $metadata['payment_id'] ?? null;
            $paymentRequest = null;
            if ($paymentId) {
                $paymentRequest = $this->payment::where('id', $paymentId)->first();
            } elseif ($reference) {
                $paymentRequest = $this->payment::where('gateway_reference', $reference)->first();
            }

            if ($paymentRequest) {
                // [AI] Commit 4 & Commit 6 Order Settlement Routing
                if ($paymentRequest->payment_domain === 'marketplace_delivery') {
                    $settlementService = app(\App\Services\DeliveryOrderSettlementService::class);
                    $settlementResult = $settlementService->settleVerifiedPayment($reference, $data);
                    Log::info("Paystack Webhook: Delivery settlement for reference '{$reference}' returned: " . ($settlementResult['status'] ?? 'unknown'));
                    return response()->json([
                        'status' => true,
                        'settlement' => $settlementResult['status'] ?? 'unknown',
                    ], 200);
                } elseif ($paymentRequest->payment_domain === 'marketplace_pickup') {
                    $settlementService = app(\App\Services\PickupOrderSettlementService::class);
                    $settlementResult = $settlementService->settleVerifiedPayment($reference, $data);
                    Log::info("Paystack Webhook: Pickup settlement for reference '{$reference}' returned: " . ($settlementResult['status'] ?? 'unknown'));
                    return response()->json([
                        'status' => true,
                        'settlement' => $settlementResult['status'] ?? 'unknown',
                    ], 200);
                }

                // [AI] Legacy direct webhook settlement removed.
                // All marketplace checkout PaymentRequests must have payment_domain = 'marketplace_delivery'
                // or 'marketplace_pickup', and are settled by their respective settlement services above.
                // A PaymentRequest without a recognized payment_domain reaching this webhook branch
                // is an architectural violation. Log and skip — never call digital_payment_success hook.
                Log::warning('Paystack Webhook: PaymentRequest reached legacy fallback branch — no settlement action taken.', [
                    'payment_id'     => $paymentRequest->id,
                    'payment_domain' => $paymentRequest->payment_domain ?? 'null',
                    'reference'      => $reference,
                ]);
            }

            // B. Check Delivery Rider Cash-on-Delivery Order Payment Link
            $orderId = $metadata['order_id'] ?? null;
            $type = $metadata['type'] ?? null;
            if ($orderId && $type === 'delivery_payment') {
                $order = Order::with(['customer', 'deliveryMan'])->find($orderId);
                if ($order) {
                    $expectedAmount = (int) round($order['order_amount'] * 100);
                    $amountPaid = (int) ($data['amount'] ?? 0);
                    if ($amountPaid === $expectedAmount) {
                        // [AI] Receipt Authority Invariant:
                        // Payment confirmation marks payment_status = 'paid', but NEVER marks order_status = 'delivered'.
                        // Customer receipt requires physical OTP code verification at the doorstep.
                        $order->update([
                            'payment_status' => 'paid',
                            'payment_method' => 'paystack',
                            'transaction_ref' => $reference,
                        ]);

                        Log::info("Paystack Webhook: Successfully processed Payment for Delivery Order #{$orderId} with ref {$reference}.");
                    }
                }
            }
        }

        // 3. Process Refund Lifecycle Events (refund.pending, refund.processing, refund.needs-attention, refund.failed, refund.processed)
        if (str_starts_with($event['event'], 'refund.')) {
            $refundService = app(\App\Services\PaystackRefundService::class);
            $result = $refundService->handleRefundWebhook($event, $paystackSignature, $payload);
            return response()->json($result, $result['code'] ?? 200);
        }

        // Paystack requires a 200 OK HTTP response
        return response()->json(['status' => true, 'message' => 'Webhook received and processed'], 200);
    }
}
