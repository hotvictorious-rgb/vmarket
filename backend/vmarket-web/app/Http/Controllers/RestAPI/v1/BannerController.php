<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Traits\CacheManagerTrait;
use App\Utils\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BannerController extends Controller
{
    use CacheManagerTrait;

    public function getBannerList(Request $request): JsonResponse
    {
        $bannerData = Cache::remember('vmarket_api_banner_data_list', CACHE_FOR_3_HOURS, function () {
            $banners = $this->cacheBannerTable();
            if (empty($banners) || count($banners) === 0) {
                $banners = \App\Models\Banner::with(['storage'])->where(['published' => 1, 'theme' => 'default'])->orderBy('id', 'desc')->latest('created_at')->get();
            }
            $productIds = [];
            $shopIds = [];
            $brandIds = [];
            $categoryIds = [];
            $data = [];
            foreach ($banners as $banner) {
                if ($banner['resource_type'] == 'product' && !in_array($banner['resource_id'], $productIds)) {
                    $productIds[] = $banner['resource_id'];
                    $product = Product::find($banner['resource_id']);
                    $banner['product'] = $product ? Helpers::product_data_formatting($product) : null;
                }
                if ($banner['resource_type'] == 'shop' && !in_array($banner['resource_id'], $shopIds)) {
                    $shopIds[] = $banner['resource_id'];
                    $banner['shop'] = Shop::where('id', $banner['resource_id'])->first();
                }
                if ($banner['resource_type'] == 'brand' && !in_array($banner['resource_id'], $brandIds)) {
                    $brandIds[] = $banner['resource_id'];
                    $banner['brand'] = Brand::where('id', $banner['resource_id'])->first();
                }
                if ($banner['resource_type'] == 'category' && !in_array($banner['resource_id'], $categoryIds)) {
                    $categoryIds[] = $banner['resource_id'];
                    $banner['category'] = Category::where('id', $banner['resource_id'])->first();
                }
                $data[] = $banner;
            }
            return $data;
        });

        return response()->json($bannerData, 200);
    }
}
