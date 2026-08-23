<?php

namespace App\Services;

use App\Traits\FileManagerTrait;

class DealOfTheDayService
{
    use FileManagerTrait;

    public function getAddData(object $request, object $product): array
    {
        $discount = $product['discount_type'] == 'amount' ? usdToDefaultCurrency(amount:$product['discount']) : $product['discount'];
        $discountType = $product['discount_type'] ?? 'percent';

        // [AI] Retail Margin Floor Guard: Ensure Deal of the Day discount never erodes base purchase cost
        $costPrice = (float)($product['purchase_price'] > 0 ? $product['purchase_price'] : 0);
        $unitPrice = (float)$product['unit_price'];

        if ($unitPrice > 0 && $costPrice > 0) {
            if ($discountType === 'percent') {
                $maxPercent = max(0, min(90, (($unitPrice - $costPrice) / $unitPrice) * 100));
                if ($discount > $maxPercent) {
                    $discount = round($maxPercent, 2);
                }
            } elseif ($discountType === 'amount' || $discountType === 'flat') {
                $maxFlat = max(0, $unitPrice - $costPrice);
                if ($discount > $maxFlat) {
                    $discount = round($maxFlat, 2);
                }
            }
        }

        return [
            'title' => $request['title'][array_search('en', $request->lang)],
            'discount' => $discount,
            'discount_type' => $discountType,
            'product_id' => $request['product_id'],
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function getUpdateData(object $request, object $product): array
    {
        $discount = $product['discount_type'] == 'amount' ? usdToDefaultCurrency(amount:$product['discount']) : $product['discount'];
        $discountType = $product['discount_type'] ?? 'percent';

        // [AI] Retail Margin Floor Guard: Ensure Deal of the Day discount never erodes base purchase cost
        $costPrice = (float)($product['purchase_price'] > 0 ? $product['purchase_price'] : 0);
        $unitPrice = (float)$product['unit_price'];

        if ($unitPrice > 0 && $costPrice > 0) {
            if ($discountType === 'percent') {
                $maxPercent = max(0, min(90, (($unitPrice - $costPrice) / $unitPrice) * 100));
                if ($discount > $maxPercent) {
                    $discount = round($maxPercent, 2);
                }
            } elseif ($discountType === 'amount' || $discountType === 'flat') {
                $maxFlat = max(0, $unitPrice - $costPrice);
                if ($discount > $maxFlat) {
                    $discount = round($maxFlat, 2);
                }
            }
        }

        return [
            'title' => $request['title'][array_search('en', $request->lang)],
            'discount' => $discount,
            'discount_type' => $discountType,
            'product_id' => $request['product_id'],
            'status' => $product['status'],
            'updated_at' => now(),
        ];
    }



}
