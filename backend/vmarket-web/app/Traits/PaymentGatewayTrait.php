<?php

namespace App\Traits;

use App\Models\Currency;

trait PaymentGatewayTrait
{
    public function getPaymentGatewaySupportedCurrencies($key = null): array
    {
        // [AI] Directive 57326: V1 authorized gateway = Paystack only.
        // All 36 other gateways have been physically removed.
        $paymentGateway = [
            "paystack" => [
                "NGN" => "Nigerian Naira",
                "GHS" => "Ghanaian Cedi",
                "ZAR" => "South African Rand",
                "KES" => "Kenyan Shilling",
                "XOF" => "West African CFA franc",
                "EGP" => "Egyptian Pound"
            ],
        ];

        if ($key) {
            return $paymentGateway[$key] ?? [];
        }
        return $paymentGateway;
    }

    public function getPaymentGatewayCurrencyCode($key = null, $currentCurrency = null): string
    {
        $getSupportedCurrencies = $this->getPaymentGatewaySupportedCurrencies(key: $key);
        if ($currentCurrency && array_key_exists($currentCurrency, $getSupportedCurrencies) && Currency::where(['code' => $currentCurrency, 'status' => 1])->first()) {
            return $currentCurrency;
        } else if (count($getSupportedCurrencies) >= 1 && $key == 'paystack') {
            $currencyCode = Currency::whereIn('code', ['NGN', 'GHS'])->where(['status' => 1])->first();
            if ($currencyCode) {
                return $currencyCode?->code;
            }
        }
        return 'NGN';
    }
}
