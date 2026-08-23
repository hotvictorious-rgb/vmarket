<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReceiptOcrAiService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        $this->model = 'gemini-1.5-flash';
    }

    /**
     * [AI] Inspects a bank transfer receipt screenshot with Gemini 1.5 Flash Vision,
     * extracts Nigerian bank transaction metadata, and executes anti-duplicate & anti-fraud checks.
     */
    public function inspectReceipt(string $imageFullPath, ?Order $order = null, float $expectedAmount = 0): array
    {
        if (!file_exists($imageFullPath)) {
            return [
                'status' => false,
                'message' => 'Receipt file not found on server.',
                'verdict' => 'error',
            ];
        }

        // 1. Convert image to Base64
        $imageData = base64_encode(file_get_contents($imageFullPath));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imageFullPath) ?: 'image/webp';
        finfo_close($finfo);

        // 2. Query Gemini 1.5 Flash Vision
        $extracted = $this->queryGeminiVision($imageData, $mimeType);

        if (!$extracted['success']) {
            return [
                'status' => false,
                'message' => $extracted['error'] ?? 'Could not parse receipt image.',
                'verdict' => 'unreadable',
                'raw_text' => $extracted['raw_text'] ?? null,
            ];
        }

        $data = $extracted['data'];
        $sessionId = trim($data['session_id'] ?? '');
        $amount = (float)($data['amount'] ?? 0);
        $senderName = trim($data['sender_name'] ?? 'Unknown Sender');
        $bankName = trim($data['bank_name'] ?? 'Nigerian Bank / MFB');
        $timestampStr = $data['timestamp'] ?? null;
        $tamperingSuspected = (bool)($data['tampering_suspected'] ?? false);
        $tamperingReason = $data['tampering_reason'] ?? null;

        // 3. Security Check 1: Duplicate Session ID Collision Guard
        $isDuplicate = false;
        $duplicateOrderCount = 0;
        if (!empty($sessionId) && strlen($sessionId) >= 6) {
            $duplicateQuery = Order::where('bank_session_id', $sessionId);
            if ($order && $order->id) {
                $duplicateQuery->where('id', '!=', $order->id);
            }
            $duplicateOrderCount = $duplicateQuery->count();
            $isDuplicate = ($duplicateOrderCount > 0);
        }

        // 4. Security Check 2: Monetary Amount Matching Guard
        $targetAmount = $expectedAmount > 0 ? $expectedAmount : ($order ? (float)$order->order_amount : 0);
        $isAmountMatched = ($targetAmount <= 0) || (abs($amount - $targetAmount) < 1.0); // Allow ±₦1.00 tolerance for rounding
        $isUnderpaid = ($targetAmount > 0) && ($amount < $targetAmount - 1.0);

        // 5. Security Check 3: Receipt Freshness / Timestamp Guard
        $isFresh = true;
        $receiptAgeHours = null;
        if ($timestampStr) {
            try {
                $parsedDate = Carbon::parse($timestampStr);
                $receiptAgeHours = $parsedDate->diffInHours(now());
                if ($receiptAgeHours > 48) {
                    $isFresh = false;
                }
            } catch (Exception $e) {
                $isFresh = true;
            }
        }

        // 6. Compute Verdict & Security Flags
        $flags = [];
        if ($isDuplicate) {
            $flags[] = 'DUPLICATE_SESSION_ID_REUSED';
        }
        if ($isUnderpaid) {
            $flags[] = 'UNDERPAID_AMOUNT_MISMATCH';
        }
        if ($tamperingSuspected) {
            $flags[] = 'POSSIBLE_IMAGE_TAMPERING';
        }
        if (!$isFresh) {
            $flags[] = 'STALE_OLD_RECEIPT';
        }

        $verdict = 'valid';
        if ($isDuplicate || $tamperingSuspected) {
            $verdict = 'suspicious';
        } elseif ($isUnderpaid || !$isFresh) {
            $verdict = 'review_required';
        }

        return [
            'status' => true,
            'verdict' => $verdict,
            'session_id' => $sessionId,
            'amount' => $amount,
            'formatted_amount' => '₦' . number_format($amount, 2),
            'target_amount' => $targetAmount,
            'formatted_target_amount' => '₦' . number_format($targetAmount, 2),
            'sender_name' => $senderName,
            'bank_name' => $bankName,
            'timestamp' => $timestampStr,
            'receipt_age_hours' => $receiptAgeHours,
            'is_duplicate' => $isDuplicate,
            'is_amount_matched' => $isAmountMatched,
            'is_underpaid' => $isUnderpaid,
            'is_fresh' => $isFresh,
            'tampering_suspected' => $tamperingSuspected,
            'tampering_reason' => $tamperingReason,
            'flags' => $flags,
            'inspected_at' => now()->toIso8601String(),
        ];
    }

    /**
     * [AI] Executes Gemini 1.5 Flash Vision OCR with structured JSON output schema.
     */
    protected function queryGeminiVision(string $base64Image, string $mimeType): array
    {
        if (empty($this->apiKey)) {
            // Fallback mock parser if API key is not yet set in local environment
            return [
                'success' => true,
                'data' => [
                    'session_id' => 'REF' . time() . rand(1000, 9999),
                    'amount' => 0.00,
                    'sender_name' => 'Transfer Customer',
                    'bank_name' => 'Nigerian Bank',
                    'timestamp' => now()->toDateTimeString(),
                    'tampering_suspected' => false,
                    'tampering_reason' => null,
                ],
            ];
        }

        $prompt = <<<PROMPT
You are an expert Nigerian Banking Fraud & Receipt Inspection AI for Victorious MARKET (Vmarket) in Uyo, Nigeria.
Analyze this uploaded bank transfer receipt screenshot (OPay, Moniepoint, GTBank, Kuda, Zenith, FirstBank, Access, PalmPay, etc.) and extract the transaction details.

Carefully inspect the image for digital manipulation (e.g. font mismatch, pixel distortion around the amount, mismatched kerning, altered dates).

Return ONLY a valid JSON object with the following schema:
{
  "session_id": "Extracted unique 30-digit Bank Session ID, Transaction Reference, or RRR (string)",
  "amount": 15000.00,
  "sender_name": "Name of the sender if visible (string)",
  "bank_name": "Name of the sender/receiving bank e.g. OPay, Moniepoint, GTBank (string)",
  "timestamp": "YYYY-MM-DD HH:MM:SS or ISO string of transfer time",
  "tampering_suspected": false,
  "tampering_reason": null or "Description of font/pixel irregularities detected"
}
Do not include markdown codeblocks, output only raw JSON.
PROMPT;

        try {
            $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(25)->post($apiUrl, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Image,
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'response_mime_type' => 'application/json',
                ],
            ]);

            if (!$response->successful()) {
                Log::error('[ReceiptOcrAiService Gemini Error]', ['body' => $response->body()]);
                return ['success' => false, 'error' => 'Vision API request failed: ' . $response->status()];
            }

            $jsonText = $response->json('candidates.0.content.parts.0.text');
            $cleanJson = trim(preg_replace('/^```json\s*|\s*```$/i', '', $jsonText));
            $parsed = json_decode($cleanJson, true);

            if (!is_array($parsed)) {
                return ['success' => false, 'error' => 'Failed to parse JSON from AI Vision response', 'raw_text' => $jsonText];
            }

            return ['success' => true, 'data' => $parsed];

        } catch (Exception $e) {
            Log::error('[ReceiptOcrAiService Exception] ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
