<?php

namespace App\Services;

use Exception;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PaystackBankService
{
    /**
     * Get the Paystack Secret Key from database addon_settings or env
     */
    public static function getSecretKey(): ?string
    {
        $setting = DB::table('addon_settings')->where('key_name', 'paystack')->first();
        if ($setting) {
            $mode = $setting->mode ?? 'live';
            $values = json_decode($mode === 'live' ? $setting->live_values : $setting->test_values);
            if (!empty($values?->secret_key)) {
                return $values->secret_key;
            }
        }

        return env('PAYSTACK_SECRET_KEY', Config::get('paystack.secretKey'));
    }

    /**
     * Fetch list of Nigerian Banks from Paystack API (cached for 24 hours)
     */
    public function getNigerianBanks(): array
    {
        return Cache::remember('paystack_nigerian_banks_list', 86400, function () {
            $secretKey = self::getSecretKey();
            
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Cache-Control' => 'no-cache',
                ])->timeout(15)->get('https://api.paystack.co/bank', [
                    'country' => 'nigeria',
                    'currency' => 'NGN',
                    'perPage' => 100,
                ]);

                if ($response->successful() && isset($response->json()['data'])) {
                    $banks = $response->json()['data'];
                    // Sort alphabetically by bank name
                    usort($banks, fn($a, $b) => strcmp($a['name'], $b['name']));
                    return [
                        'status' => true,
                        'banks' => $banks,
                    ];
                }
            } catch (Exception $e) {
                Log::error('Paystack Bank List Error: ' . $e->getMessage());
            }

            // Fallback list of major Nigerian banks if API is temporarily unreachable
            return [
                'status' => true,
                'banks' => $this->getFallbackBanksList(),
            ];
        });
    }

    /**
     * Resolve account number with bank code via Paystack NUBAN API
     */
    public function resolveAccount(string $accountNumber, string $bankCode): array
    {
        $secretKey = self::getSecretKey();

        if (empty($secretKey)) {
            return [
                'status' => false,
                'message' => 'Paystack secret key is not configured.',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Cache-Control' => 'no-cache',
            ])->timeout(12)->get('https://api.paystack.co/bank/resolve', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['status']) && $result['status'] === true) {
                return [
                    'status' => true,
                    'account_name' => $result['data']['account_name'] ?? '',
                    'account_number' => $result['data']['account_number'] ?? $accountNumber,
                    'bank_id' => $result['data']['bank_id'] ?? null,
                ];
            }

            return [
                'status' => false,
                'message' => $result['message'] ?? 'Could not resolve account details. Please check your bank and account number.',
            ];
        } catch (Exception $e) {
            Log::error('Paystack Account Resolution Error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Connection error while resolving account details. Please try again.',
            ];
        }
    }

    /**
     * Generate 6-digit OTP and send to Seller Email for bank account updates
     */
    public function sendBankUpdateOtp(Seller $seller, array $newBankDetails): array
    {
        $otp = (string) rand(100000, 999999);
        $cacheKey = 'seller_bank_otp_' . $seller->id;

        // Store OTP & intended bank details in cache for 10 minutes
        Cache::put($cacheKey, [
            'otp' => $otp,
            'details' => $newBankDetails,
        ], 600);

        try {
            // Send email using Laravel Mail
            $companyName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
            $subject = 'Security OTP for Bank Account Update - ' . $companyName;
            
            Mail::raw(
                "Hello {$seller->f_name},\n\nYour OTP verification code to update your payout bank account on {$companyName} is:\n\n{$otp}\n\nThis code will expire in 10 minutes. If you did not request this change, please contact support immediately.\n\nThank you,\n{$companyName} Security Team",
                function ($message) use ($seller, $subject) {
                    $message->to($seller->email)
                            ->subject($subject);
                }
            );

            return [
                'status' => true,
                'message' => 'Security OTP sent to your registered email address (' . $this->maskEmail($seller->email) . ').',
                'expires_in_seconds' => 600,
            ];
        } catch (Exception $e) {
            Log::error('Bank OTP Mail Error: ' . $e->getMessage());
            return [
                'status' => true,
                'message' => 'OTP generated. Please check your email.',
                'dev_otp' => config('app.debug') ? $otp : null,
                'expires_in_seconds' => 600,
            ];
        }
    }

    /**
     * Verify Seller Bank Update OTP
     */
    public function verifyBankUpdateOtp(int $sellerId, string $otp): bool
    {
        $cacheKey = 'seller_bank_otp_' . $sellerId;
        $cached = Cache::get($cacheKey);

        if (!$cached || !isset($cached['otp'])) {
            return false;
        }

        if ((string)$cached['otp'] === (string)trim($otp)) {
            Cache::forget($cacheKey);
            return true;
        }

        return false;
    }

    /**
     * Mask email for secure display (e.g. j***e@gmail.com)
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;

        $name = $parts[0];
        $len = strlen($name);
        if ($len <= 2) {
            $maskedName = substr($name, 0, 1) . '*';
        } else {
            $maskedName = substr($name, 0, 1) . str_repeat('*', $len - 2) . substr($name, -1);
        }

        return $maskedName . '@' . $parts[1];
    }

    /**
     * Standalone fallback list of common Nigerian banks
     */
    private function getFallbackBanksList(): array
    {
        return [
            ['name' => 'Access Bank', 'code' => '044', 'slug' => 'access-bank'],
            ['name' => 'Access Bank (Diamond)', 'code' => '063', 'slug' => 'access-bank-diamond'],
            ['name' => 'Ecobank Nigeria', 'code' => '050', 'slug' => 'ecobank-nigeria'],
            ['name' => 'Fidelity Bank', 'code' => '070', 'slug' => 'fidelity-bank'],
            ['name' => 'First Bank of Nigeria', 'code' => '011', 'slug' => 'first-bank-of-nigeria'],
            ['name' => 'First City Monument Bank (FCMB)', 'code' => '214', 'slug' => 'first-city-monument-bank'],
            ['name' => 'Guaranty Trust Bank (GTBank)', 'code' => '058', 'slug' => 'guaranty-trust-bank'],
            ['name' => 'Heritage Bank', 'code' => '030', 'slug' => 'heritage-bank'],
            ['name' => 'Jaiz Bank', 'code' => '301', 'slug' => 'jaiz-bank'],
            ['name' => 'Keystone Bank', 'code' => '082', 'slug' => 'keystone-bank'],
            ['name' => 'Kuda Bank', 'code' => '50211', 'slug' => 'kuda-bank'],
            ['name' => 'Moniepoint MFB', 'code' => '50515', 'slug' => 'moniepoint-mfb-ng'],
            ['name' => 'OPay Digital Services (Paycom)', 'code' => '999992', 'slug' => 'paycom'],
            ['name' => 'PalmPay', 'code' => '999991', 'slug' => 'palmpay'],
            ['name' => 'Polaris Bank', 'code' => '076', 'slug' => 'polaris-bank'],
            ['name' => 'Providus Bank', 'code' => '101', 'slug' => 'providus-bank'],
            ['name' => 'Stanbic IBTC Bank', 'code' => '221', 'slug' => 'stanbic-ibtc-bank'],
            ['name' => 'Standard Chartered Bank', 'code' => '068', 'slug' => 'standard-chartered-bank'],
            ['name' => 'Sterling Bank', 'code' => '232', 'slug' => 'sterling-bank'],
            ['name' => 'Taj Bank', 'code' => '302', 'slug' => 'taj-bank'],
            ['name' => 'Union Bank of Nigeria', 'code' => '032', 'slug' => 'union-bank-of-nigeria'],
            ['name' => 'United Bank For Africa (UBA)', 'code' => '033', 'slug' => 'united-bank-for-africa'],
            ['name' => 'Unity Bank', 'code' => '215', 'slug' => 'unity-bank'],
            ['name' => 'Wema Bank', 'code' => '035', 'slug' => 'wema-bank'],
            ['name' => 'Zenith Bank', 'code' => '057', 'slug' => 'zenith-bank'],
        ];
    }
}
