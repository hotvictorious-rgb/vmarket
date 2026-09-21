<?php

namespace App\Traits\API\v3;

trait VendorPOSManagement
{
    public function getOrderDetailsAddData($cartItem, $product): array
    {
        $variant = $cartItem['variant'];
        $unitPrice = $product['unit_price'];
        $price = $product['unit_price'];
        $productDiscount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $product['unit_price'], from: 'panel');
        $productSubtotal = ($product['unit_price']) * $cartItem['quantity'];

        if ($cartItem['variant'] != null) {
            foreach (json_decode($product['variation'], true) as $variation) {
                if ($cartItem['variant'] == $variation['type']) {
                    $unitPrice = $variation['price'];
                    $price = $variation['price'];
                    $productDiscount = getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $variation['price'], from: 'panel');
                    $productSubtotal = $variation['price'] * $cartItem['quantity'];
                }
            }
        }

        $product['unit_price_amount'] = $unitPrice;
        return [
            'tax' => 0,
            'price' => $price,
            'variant' => $variant,
            'product' => $product,
            'productDiscount' => $productDiscount,
            'productSubtotal' => $productSubtotal,
        ];
    }
}
