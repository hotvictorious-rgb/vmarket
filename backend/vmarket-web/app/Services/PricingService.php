<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Seller;

class PricingService
{
    /**
     * Calculate customer retail price from vendor purchase/cost price dynamically.
     *
     * @param float $purchasePrice
     * @param int|null $categoryId
     * @param int|null $sellerId
     * @return array
     */
    public function calculateRetailPrice(float $purchasePrice, ?int $categoryId = null, ?int $sellerId = null): array
    {
        if ($purchasePrice <= 0) {
            return [
                'purchase_price' => 0.0,
                'markup_rate' => 0.0,
                'markup_type' => 'percentage',
                'markup_amount' => 0.0,
                'unit_price' => 0.0,
            ];
        }

        $markupType = 'percentage';
        $markupRate = null;

        // 1. Check if Category has specific markup configured
        if ($categoryId) {
            $category = Category::find($categoryId);
            if ($category && isset($category->markup_percentage) && (float)$category->markup_percentage > 0) {
                $markupRate = (float) $category->markup_percentage;
                $markupType = $category->markup_type ?? 'percentage';
            }
        }

        // 2. Fallback to global platform default markup
        if ($markupRate === null) {
            $markupRate = (float) (getWebConfig(name: 'default_platform_markup_percentage') ?? 10.0);
            $markupType = 'percentage';
        }

        // 3. Compute markup spread
        if ($markupType === 'flat_amount') {
            $markupAmount = $markupRate;
        } else {
            $markupAmount = $purchasePrice * ($markupRate / 100.0);
        }

        $rawRetailPrice = $purchasePrice + $markupAmount;

        // 4. Apply dynamic rounding rule
        $roundingStrategy = getWebConfig(name: 'price_rounding_strategy') ?? 'none';
        $finalRetailPrice = $this->applyRoundingStrategy($rawRetailPrice, $roundingStrategy);

        return [
            'purchase_price' => round($purchasePrice, 2),
            'markup_rate' => $markupRate,
            'markup_type' => $markupType,
            'markup_amount' => round($markupAmount, 2),
            'unit_price' => round($finalRetailPrice, 2),
        ];
    }

    /**
     * Apply configured price rounding strategy.
     *
     * @param float $price
     * @param string $strategy
     * @return float
     */
    public function applyRoundingStrategy(float $price, string $strategy = 'none'): float
    {
        return match ($strategy) {
            'round_50' => ceil($price / 50.0) * 50.0,
            'round_100' => ceil($price / 100.0) * 100.0,
            'psychological_99' => (floor($price / 100.0) * 100.0) - 1.0 > 0 ? (floor($price / 100.0) * 100.0) - 1.0 : $price,
            default => round($price, 2),
        };
    }

    /**
     * Calculate retail prices for an array of physical product variations.
     *
     * @param array|string $variations
     * @param int|null $categoryId
     * @param int|null $sellerId
     * @return array
     */
    public function calculateVariationPrices(array|string $variations, ?int $categoryId = null, ?int $sellerId = null): array
    {
        $varArray = is_string($variations) ? json_decode($variations, true) : $variations;
        if (!is_array($varArray)) {
            return [];
        }

        foreach ($varArray as &$variation) {
            if (isset($variation['price'])) {
                $varPurchasePrice = (float) ($variation['purchase_price'] ?? $variation['price']);
                $calc = $this->calculateRetailPrice($varPurchasePrice, $categoryId, $sellerId);
                $variation['purchase_price'] = $varPurchasePrice;
                $variation['price'] = $calc['unit_price'];
            }
        }

        return $varArray;
    }
}
