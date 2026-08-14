<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedSyncController extends Controller
{
    public function getInitialFeed(Request $request): JsonResponse
    {
        $config = (new ConfigController())->configuration()->getData();
        
        $banners = (new BannerController())->getBannerList($request)->getData();
        
        $categories = (new CategoryController())->get_categories($request)->getData();
        
        $flashDeals = (new FlashDealController())->getFlashDeal()->getData();
        
        $featuredDeals = (new DealController())->getFeaturedDealProducts($request)->getData();
        
        // Pass request parameters for latest products or default limit=10, offset=1
        $request->merge(['limit' => 10, 'offset' => 1]);
        $productController = app(ProductController::class);
        $latestProducts = $productController->get_latest_products($request)->getData();

        return response()->json([
            'config' => $config,
            'banners' => $banners,
            'categories' => $categories,
            'flash_deals' => $flashDeals,
            'featured_deals' => $featuredDeals,
            'latest_products' => $latestProducts,
        ], 200);
    }
}
