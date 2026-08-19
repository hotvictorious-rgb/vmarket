<?php

use App\Models\Currency;

if (!function_exists('loadCurrency')) {
    /**
     * @return void
     */
    function loadCurrency(): void
    {
        $defaultCurrency = getWebConfig(name: 'system_default_currency');
        $currentCurrencyInfo = session('system_default_currency_info');
        $currentId = is_object($currentCurrencyInfo) ? ($currentCurrencyInfo->id ?? null) : ($currentCurrencyInfo['id'] ?? null);

        if (!session()->has('system_default_currency_info') || $defaultCurrency != $currentId || !$currentCurrencyInfo) {
            $id = getWebConfig(name: 'system_default_currency');
            $currency = $id ? Currency::find($id) : Currency::first();
            if (!$currency) {
                $currency = Currency::first();
            }

            if ($currency) {
                session()->put('system_default_currency_info', $currency);
                session()->put('currency_code', $currency->code ?? 'NGN');
                session()->put('currency_symbol', $currency->symbol ?? '₦');
                session()->put('currency_exchange_rate', $currency->exchange_rate ?? 1);
            }
            session()->forget('usd');
            session()->forget('default');
            $usd = exchangeRate(USD);
            session()->put('usd', $usd);
        }
    }
}

if (!function_exists('currencyConverter')) {
    /** system default currency to usd convert
     * @param float|null $amount
     * @param string $to
     * @return float|int
     */
    function currencyConverter(float|null $amount = 0, string $to = USD): float|int
    {
        $amount = is_null($amount) ? 0 : $amount;
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            $curr = Currency::find(getWebConfig('system_default_currency'));
            $default = $curr?->exchange_rate ?? 1;
            $exchangeRate = exchangeRate($to);
            $rate = $exchangeRate > 0 ? ($default / $exchangeRate) : 1;
            if ($amount == 0 || floatval($rate) == 0.0) {
                $value = $amount;
            } else {
                $value = $amount / floatval($rate);
            }
        } else {
            $value = $amount;
        }
        return $value;
    }
}

if (!function_exists('usdToDefaultCurrency')) {
    /**
     * system usd currency to default convert
     * @param float|int|null $amount
     * @return float|int
     */
    function usdToDefaultCurrency(float|int|null $amount = 0): float|int
    {
        $amount = $amount ?? 0;
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('default')) {
                $default = session('default');
            } else {
                $curr = Currency::find(getWebConfig('system_default_currency'));
                $default = $curr?->exchange_rate ?? 1;
                session()->put('default', $default);
            }

            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usd = exchangeRate(USD);
                session()->put('usd', $usd);
            }

            $rate = ($usd > 0) ? ($default / $usd) : 1;
            $value = $amount * floatval($rate);
        } else {
            $value = $amount;
        }

        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        return round($value, $decimalPointSettings);
    }
}

if (!function_exists('webCurrencyConverter')) {
    /**
     * currency convert for web panel
     * @param string|int|float|null $amount
     * @return float|string
     */
    function webCurrencyConverter(string|int|float|null $amount = 0): float|string
    {
        loadCurrency();
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usdCurr = Currency::where(['code' => 'USD'])->first();
                $usd = $usdCurr?->exchange_rate ?? 1;
                session()->put('usd', $usd);
            }
            $myCurrency = session('currency_exchange_rate') ?? 1;
            $rate = ($usd > 0) ? ($myCurrency / $usd) : 1;
        } else {
            $rate = 1;
        }
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        return setCurrencySymbol(amount: round(($amount ?? 0) * $rate, $decimalPointSettings), currencyCode: getCurrencyCode(type: 'web'), type: 'web');
    }
}

if (!function_exists('localToDefaultCurrency')) {
    /** system default currency to usd convert
     * @param float|null $amount
     * @param string $type
     * @return float|int|string
     */
    function localToDefaultCurrency(float|null $amount = 0, string $type = 'default'): float|int|string
    {
        $rate = session('currency_exchange_rate') ?: 1;
        $value = is_null($amount) || $amount != 0 ? ($amount / $rate) : 0;
        if ($type == 'web') {
            return setCurrencySymbol(amount: $value, currencyCode: getCurrencyCode(type: 'web'), type: 'web');
        }
        return $value;
    }
}

if (!function_exists('loyaltyPointToLocalCurrency')) {
    /** system default currency to usd convert
     * @param float|null $amount
     * @param string $type
     * @return string
     */
    function loyaltyPointToLocalCurrency(float|null $amount = 0, string $type = 'default'): string
    {
        $loyaltyPointExchangeRate = getWebConfig(name: 'loyalty_point_exchange_rate') ?: 1;
        $rate = session('currency_exchange_rate') ?: 1;
        $value = (($rate * 1) / $loyaltyPointExchangeRate) * ($amount ?? 0);
        if ($type == 'web') {
            return setCurrencySymbol(amount: $value, currencyCode: session('currency_code') ?? 'NGN', type: 'web');
        }
        return $value;
    }
}

if (!function_exists('webCurrencyConverterOnlyDigit')) {
    /**
     * currency convert for web panel
     * @param string|int|float|null $amount
     * @return float|string
     */
    function webCurrencyConverterOnlyDigit(string|int|float|null $amount = 0): float|string
    {
        loadCurrency();
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usdCurr = Currency::where(['code' => 'USD'])->first();
                $usd = $usdCurr?->exchange_rate ?? 1;
                session()->put('usd', $usd);
            }
            $myCurrency = session('currency_exchange_rate') ?? 1;
            $rate = ($usd > 0) ? ($myCurrency / $usd) : 1;
        } else {
            $rate = 1;
        }

        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        return round(($amount ?? 0) * $rate, $decimalPointSettings);
    }
}

if (!function_exists('usdToAnotherCurrencyConverter')) {
    /**
     * currency convert for web panel
     * @param string $currencyCode
     * @param string|int|float|null $amount
     * @return float|string
     */
    function usdToAnotherCurrencyConverter(string $currencyCode, string|int|float|null $amount = 0): float|string
    {
        if ($currencyCode == 'USD') {
            return $amount ?? 0;
        }
        $usdCurr = Currency::where(['code' => 'USD'])->first();
        $usd = $usdCurr?->exchange_rate ?? 1;
        $myCurr = Currency::where(['code' => $currencyCode])->first();
        $myCurrency = $myCurr?->exchange_rate ?? 1;
        $rate = ($usd > 0) ? ($myCurrency / $usd) : 1;
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        return round(($amount ?? 0) * $rate, $decimalPointSettings);
    }
}

if (!function_exists('exchangeRate')) {
    /**
     * @param string $currencyCode
     * @return float|int
     */
    function exchangeRate(string $currencyCode = USD): float|int
    {
        return Currency::where('code', $currencyCode)->first()?->exchange_rate ?? 1;
    }
}

if (!function_exists('getCurrencySymbol')) {
    /**
     * @param string $currencyCode
     * @param string $type
     * @return float|int|string
     */
    function getCurrencySymbol(string $currencyCode = USD, string $type = 'default'): float|int|string
    {
        loadCurrency();
        if ($type == 'web' && session()->has('currency_symbol')) {
            $currentSymbol = session('currency_symbol');
        } else {
            $systemDefaultCurrencyInfo = session('system_default_currency_info');
            $currentSymbol = is_object($systemDefaultCurrencyInfo) 
                ? ($systemDefaultCurrencyInfo->symbol ?? '₦') 
                : ($systemDefaultCurrencyInfo['symbol'] ?? '₦');
        }
        return $currentSymbol ?? '₦';
    }
}

if (!function_exists('setCurrencySymbol')) {
    /**
     * @param string|int|float $amount
     * @param string $currencyCode
     * @param string $type
     * @return string
     */
    function setCurrencySymbol(string|int|float $amount, string $currencyCode = USD, string $type = 'default'): string
    {
        $decimalPointSettings = getWebConfig('decimal_point_settings');
        $position = getWebConfig('currency_symbol_position') ?? 'left';
        if ($position === 'left') {
            $string = getCurrencySymbol(currencyCode: $currencyCode, type: $type) . '' . number_format((float)$amount, (!empty($decimalPointSettings) ? $decimalPointSettings : 0));
        } else {
            $string = number_format((float)$amount, !empty($decimalPointSettings) ? $decimalPointSettings : 0) . getCurrencySymbol(currencyCode: $currencyCode, type: $type);
        }
        return $string;
    }
}

if (!function_exists('getCurrencyCode')) {
    /**
     * @param string $type default,web
     * @return string
     */
    function getCurrencyCode(string $type = 'default'): string
    {
        if ($type == 'web') {
            $currencyCode = session('currency_code');
        } else {
            if (session()->has('system_default_currency_info') && session('system_default_currency_info')) {
                $info = session('system_default_currency_info');
                $currencyCode = is_object($info) ? ($info->code ?? 'NGN') : ($info['code'] ?? 'NGN');
            } else {
                $currencyId = getWebConfig('system_default_currency');
                $curr = $currencyId ? Currency::find($currencyId) : null;
                $currencyCode = $curr?->code ?? Currency::first()?->code ?? 'NGN';
            }
        }
        return $currencyCode ?? 'NGN';
    }
}

if (!function_exists('getFormatCurrency')) {
    /**
     * @param string|int|float $amount
     * @return string
     */
    function getFormatCurrency(string|int|float $amount): string
    {
        $suffixes = ["1t+" => 1000000000000, "B+" => 1000000000, "M+" => 1000000, "K+" => 1000];
        foreach ($suffixes as $suffix => $factor) {
            if ($amount >= $factor) {
                $div = $amount / $factor;
                $formattedValue = number_format($div, 1) . $suffix;
                break;
            }
        }

        if (!isset($formattedValue)) {
            $formattedValue = number_format($amount, 2);
        }

        return $formattedValue;
    }
}


if (!function_exists('getProductPriceByType')) {
    function getProductPriceByType($product, $type, $result = 'value', $price = 0, $from = 'web'): float|int|string
    {
        if ($type == 'discount') {
            if ((isset($product['clearanceSale']) && $product['clearanceSale']) || isset($product['clearance_sale']) && $product['clearance_sale']) {
                $clearanceSale = $product['clearanceSale'] ?? $product['clearance_sale'];
                if ($clearanceSale['discount_type'] == 'percentage') {
                    $amount = round($clearanceSale['discount_amount'], (!empty($decimalPointSettings) ? $decimalPointSettings: 0));
                    return $result == 'value' ? $amount : $amount.'%';
                } else if ($clearanceSale['discount_type'] =='flat') {
                    return $result == 'value' ? $clearanceSale['discount_amount'] : webCurrencyConverter(amount: $clearanceSale['discount_amount']);
                }
            } else if (isset($product['discount_type']) && $product['discount_type'] == 'percent') {
                $amount = round($product['discount'], (!empty($decimalPointSettings) ? $decimalPointSettings: 0));
                return $result == 'value' ? $amount : $amount.'%';
            } else if (isset($product['discount_type']) && $product['discount_type'] =='flat') {
                return $result == 'value' ? $product['discount'] : webCurrencyConverter(amount: $product['discount']);
            }
        }

        if ($type == 'discount_type') {
            $discountType = $product['discount_type'];
            if ((isset($product['clearanceSale']) && $product['clearanceSale']) || isset($product['clearance_sale']) && $product['clearance_sale']) {
                $clearanceSale = $product['clearanceSale'] ?? $product['clearance_sale'];
                $discountType = $clearanceSale['discount_type'];
            }
            return $discountType;
        }

        if ($type == 'discounted_unit_price') {
            $unitPrice = $price != 0 ? $price : $product['unit_price'];
            if ((isset($product['clearanceSale']) && $product['clearanceSale']) || isset($product['clearance_sale']) && $product['clearance_sale']) {
                $amount = $unitPrice - getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $unitPrice);
            } else {
                $amount = $unitPrice - (getProductDiscount(product: $product, price: $unitPrice));
            }

            if ($from == 'panel') {
                return $result == 'value' ? $amount : setCurrencySymbol(amount: usdToDefaultCurrency(amount: $amount), currencyCode: getCurrencyCode());
            }
            return $result == 'value' ? $amount : webCurrencyConverter(amount: $amount);
        }

        if ($type == 'discounted_amount') {
            if ((isset($product['clearanceSale']) && $product['clearanceSale']) || isset($product['clearance_sale']) && $product['clearance_sale']) {
                $clearanceSale = $product['clearanceSale'] ?? $product['clearance_sale'];
                $discountAmount = 0;
                if ($clearanceSale['discount_type'] == 'percentage') {
                    $discountAmount = ($price * getProductPriceByType(product: $product, type: 'discount', result: 'value')) / 100;
                } else if ($clearanceSale['discount_type'] == 'flat') {
                    $discountAmount =  $clearanceSale['discount_amount'];
                }

                $amount = floatval($discountAmount);
            } else {
                $amount = getProductDiscount(product: $product, price: $price);
            }
            if ($from == 'panel') {
                return $result == 'value' ? $amount : setCurrencySymbol(amount: usdToDefaultCurrency(amount: $amount), currencyCode: getCurrencyCode());
            }
            return $result == 'value' ? $amount : webCurrencyConverter(amount: $amount);
        }

        return 0;
    }
}

