<?php

namespace App\Services;

use App\Traits\FileManagerTrait;
use Illuminate\Support\Str;

class FlashDealService
{
    use FileManagerTrait;

    public function getAddData(object $request): array
    {
        return [
            'title' => $request['title'][array_search('en', $request['lang'])],
            'start_date' => $request['start_date'],
            'end_date' => $request['end_date'],
            'background_color' => $request['background_color'],
            'text_color' => $request['text_color'],
            'banner' => $request->has('image') ? $this->upload(dir:'deal/', format: 'webp', image: $request->file('image')) : 'def.webp',
            'slug' => Str::slug($request['title'][array_search('en', $request['lang'])]),
            'featured' => $request['featured'] == 1 ? 1 : 0,
            'deal_type' => $request['deal_type'] == 'flash_deal' ? 'flash_deal' : 'feature_deal',
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function getUpdateData(object $request, object $data): array
    {
        return [
            'title' => $request['title'][array_search('en', $request['lang'])],
            'start_date' => $request['start_date'],
            'end_date' => $request['end_date'],
            'background_color' => $request['background_color'],
            'text_color' => $request['text_color'],
            'banner' => $request->file('image') ? $this->update('deal/', $data['banner'],'webp', $request->file('image')) : $data['banner'],
            'slug' => Str::slug($request['title'][array_search('en', $request->lang)]),
            'featured' => $request['featured'] == 'on' ? 1 : 0,
            'deal_type' => $request['deal_type'] == 'flash_deal' ? 'flash_deal' : 'feature_deal',
            'updated_at' => now(),
        ];
    }


    public function getAddProduct(object $request, string|int $productId, string|int $id): array
    {
        $discount = isset($request['discount']) ? (float)$request['discount'] : 0.0;
        $discountType = $request['discount_type'] ?? 'percent';

        // [AI] Retail Margin Floor Guard: Fetch product and ensure discount does not wipe out base cost
        $product = \App\Models\Product::find($productId);
        if ($product) {
            $costPrice = (float)($product->purchase_price > 0 ? $product->purchase_price : 0);
            $unitPrice = (float)$product->unit_price;

            if ($unitPrice > 0) {
                if ($discountType === 'percent') {
                    $maxPercent = $costPrice > 0 ? max(0, min(90, (($unitPrice - $costPrice) / $unitPrice) * 100)) : 90;
                    if ($discount > $maxPercent && $costPrice > 0) {
                        $discount = round($maxPercent, 2);
                    }
                } elseif ($discountType === 'flat' || $discountType === 'amount') {
                    $maxFlat = $costPrice > 0 ? max(0, $unitPrice - $costPrice) : ($unitPrice * 0.9);
                    if ($discount > $maxFlat && $costPrice > 0) {
                        $discount = round($maxFlat, 2);
                    }
                }
            }
        }

        return [
            'product_id' => $productId,
            'flash_deal_id' => $id,
            'discount' => $discount,
            'discount_type' => $discountType,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

}
