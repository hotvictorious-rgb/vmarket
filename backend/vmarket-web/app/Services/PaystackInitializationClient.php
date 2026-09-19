<?php

namespace App\Services;

use App\Http\Controllers\Payment_Methods\PaystackController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * [AI] Client PaystackInitializationClient
 * 
 * Handles Paystack transaction initialization with deterministic classification:
 * 1. SUCCESS: Confirmed initialization with authorization_url.
 * 2. GATEWAY_REJECTED: Confirmed gateway rejection (HTTP 4xx with business error).
 * 3. TRANSPORT_ERROR: Ambiguous timeout / connection drop; preserved as recoverable.
 * 
 * Step 1 normalized verification contract (PaystackController::getPayStackPaymentData)
 * is reused for transport failure recovery without modifying the frozen Step 1 helper.
 */
class PaystackInitializationClient
{
    /**
     * Initializes a transaction with Paystack.
     *
     * @param string $email Customer email address
     * @param int $amountKobo Exact integer kobo amount
     * @param string $reference Canonical gateway reference (VM-...)
     * @param string $callbackUrl Callback URL
     * @param array $metadata Custom metadata
     * @return array
     */
    public function initializeTransaction(
        string $email,
        int $amountKobo,
        string $reference,
        string $callbackUrl,
        array $metadata = []
    ): array {
        $secretKey = Config::get('paystack.secretKey');
        $url = "https://api.paystack.co/transaction/initialize";

        $fields = [
            'email' => $email,
            'amount' => $amountKobo,
            'currency' => 'NGN',
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$secretKey}",
                "Cache-Control: no-cache",
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $responseBody = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Ambiguous Transport Failure: Timeout / Network Disconnect
        if ($curlErrno !== 0) {
            Log::warning("Paystack initialize transport failure for reference '{$reference}': [{$curlErrno}] {$curlError}");
            return [
                'status' => 'TRANSPORT_ERROR',
                'message' => "Paystack transport failure: [{$curlErrno}] {$curlError}",
                'errno' => $curlErrno,
                'is_ambiguous' => true,
            ];
        }

        $decoded = json_decode($responseBody, true);

        // Confirmed Gateway Success
        if ($httpCode === 200 && is_array($decoded) && !empty($decoded['status']) && !empty($decoded['data']['authorization_url'])) {
            return [
                'status' => 'SUCCESS',
                'authorization_url' => $decoded['data']['authorization_url'],
                'access_code' => $decoded['data']['access_code'] ?? null,
                'reference' => $decoded['data']['reference'] ?? $reference,
                'raw_response' => $decoded,
            ];
        }

        // Confirmed Gateway Rejection (e.g. invalid key, invalid amount, account disabled)
        $errorMessage = $decoded['message'] ?? "Paystack rejected initialization with HTTP code {$httpCode}";
        Log::warning("Paystack initialize rejected for reference '{$reference}': HTTP {$httpCode} - {$errorMessage}");

        return [
            'status' => 'GATEWAY_REJECTED',
            'message' => $errorMessage,
            'http_code' => $httpCode,
            'raw_response' => $decoded,
        ];
    }

    /**
     * Recovers or verifies an attempt using the Step 1 normalized contract.
     * Used when an initial attempt encountered an ambiguous transport timeout.
     *
     * @param string $reference Canonical gateway reference
     * @return array
     */
    public function verifyExistingTransaction(string $reference): array
    {
        return PaystackController::getPayStackPaymentData($reference);
    }
}
