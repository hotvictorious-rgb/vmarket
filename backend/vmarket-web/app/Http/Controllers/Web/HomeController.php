<?php

namespace App\Http\Controllers\Web;

use App\Traits\CacheManagerTrait;
use App\Traits\EmailTemplateTrait;
use App\Traits\InHouseTrait;
use App\Utils\BrandManager;
use App\Utils\CategoryManager;
use App\Utils\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DealOfTheDay;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Review;
use App\Utils\ProductManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    use InHouseTrait, EmailTemplateTrait;
    use CacheManagerTrait;

    public function __construct(
        private readonly Product      $product,
        private readonly Order        $order,
        private readonly OrderDetail  $orderDetails,
        private readonly Category     $category,
        private readonly Seller       $seller,
        private readonly Review       $review,
        private readonly DealOfTheDay $dealOfTheDay,
        private readonly Banner       $banner,
    )
    {
    }


    public function index(): View
    {
        $themeName = theme_root_path();
        return match ($themeName) {
            'default' => self::default_theme(),
            'theme_fashion' => self::theme_fashion(),
            'theme_vmarket' => self::theme_vmarket(),
            default => self::theme_vmarket(),
        };
    }

    public function default_theme(): View
    {
        $brands = $this->cachePriorityWiseBrandList();
        $homeCategories = $this->cacheHomeCategoriesList();
        $topRatedProducts = $this->cacheTopRatedProductList();
        $latestProductsList = $this->cacheHomePageLatestProductList()->take(8);
        $bestSellProduct = $this->cacheBestSellProductList();
        $recommendedProduct = $this->cacheHomePageRandomSingleProductItem();
        $bannerTypeMainBanner = $this->cacheBannerForTypeMainBanner();
        $bannerTypeMainSectionBanner = $this->cacheBannerTable(bannerType: 'Main Section Banner');
        $topVendorsList = ProductManager::getPriorityWiseTopVendorQuery($this->cacheHomePageTopVendorsList());
        $bannerTypeFooterBanner = $this->cacheBannerTable(bannerType: 'Footer Banner', dataLimit: 10);
        $clearanceSaleProducts = $this->cacheHomePageClearanceSaleProducts();

        $categories = CategoryManager::getCategoriesWithCountingAndPriorityWiseSorting();
        $userId = Auth::guard('customer')->user() ? Auth::guard('customer')->id() : 0;
        $flashDeal = ProductManager::getPriorityWiseFlashDealsProductsQuery(userId: $userId);
        $current_date = date('Y-m-d H:i:s');

        $bestSellProduct = $bestSellProduct->count() == 0 ? $latestProductsList : $bestSellProduct;
        $topRatedProducts = $topRatedProducts->count() == 0 ? $bestSellProduct : $topRatedProducts;

        $featuredProductsList = Cache::remember('home_featured_products_list_default', CACHE_FOR_3_HOURS, function () {
            return ProductManager::getPriorityWiseFeaturedProductsQuery(query: $this->product->active()->with(['clearanceSale' => function ($query) {
                return $query->active();
            }]), dataLimit: 12);
        });

        $newArrivalProducts = Cache::remember('home_new_arrival_products_list_default', CACHE_FOR_3_HOURS, function () {
            return ProductManager::getPriorityWiseNewArrivalProductsQuery(query: $this->product->active()->with(['clearanceSale' => function ($query) {
                return $query->active();
            }]), dataLimit: 8);
        });

        $dealOfTheDay = Cache::remember('home_deal_of_the_day_default', CACHE_FOR_3_HOURS, function () {
            return DealOfTheDay::with(['product' => function ($query) {
                return $query->active()->with(['clearanceSale' => function ($query) {
                    return $query->active();
                }]);
            }])
                ->join('products', 'products.id', '=', 'deal_of_the_days.product_id')
                ->select('deal_of_the_days.*', 'products.unit_price')
                ->where('products.status', 1)
                ->where('deal_of_the_days.status', 1)
                ->first();
        });
        return view(VIEW_FILE_NAMES['home'],
            compact(
                'flashDeal', 'featuredProductsList', 'topRatedProducts', 'bestSellProduct', 'latestProductsList', 'categories', 'brands',
                'dealOfTheDay', 'topVendorsList', 'homeCategories', 'bannerTypeMainBanner', 'bannerTypeMainSectionBanner',
                'current_date', 'recommendedProduct', 'bannerTypeFooterBanner', 'newArrivalProducts', 'clearanceSaleProducts'
            )
        );
    }

    public function theme_fashion(): View
    {
        $singlePageProductCount = 20;
        $currentDate = date('Y-m-d H:i:s');
        $user = Helpers::getCustomerInformation();
        $activeBrands = BrandManager::getActiveBrandWithCountingAndPriorityWiseSorting();
        $categories = CategoryManager::getCategoriesWithCountingAndPriorityWiseSorting();
        $userId = Auth::guard('customer')->user() ? Auth::guard('customer')->id() : 0;
        $flashDeal = ProductManager::getPriorityWiseFlashDealsProductsQuery(userId: $userId);
        $mostVisitedCategories = CategoryManager::getCategoriesWithCountingAndPriorityWiseSorting();
        $topVendorsList = ProductManager::getPriorityWiseTopVendorQuery(query: $this->cacheHomePageTopVendorsList());
        $mostDemandedProducts = $this->cacheMostDemandedProductItem();
        $bannerTypeMainBanner = $this->cacheBannerForTypeMainBanner();
        $bannerTypePromoBannerLeft = $this->cacheBannerTable(bannerType: 'Promo Banner Left');
        $bannerTypePromoBannerMiddleTop = $this->cacheBannerTable(bannerType: 'Promo Banner Middle Top');
        $bannerTypePromoBannerMiddleBottom = $this->cacheBannerTable(bannerType: 'Promo Banner Middle Bottom');
        $bannerTypePromoBannerRight = $this->cacheBannerTable(bannerType: 'Promo Banner Right');
        $bannerTypePromoBannerBottom = $this->cacheBannerTable(bannerType: 'Promo Banner Bottom');
        $bannerTypeSidebarBanner = $this->cacheBannerTable(bannerType: 'Sidebar Banner');
        $bannerTypeTopSideBanner = $this->cacheBannerTable(bannerType: 'Top Side Banner');
        $latestProductsList = $this->cacheHomePageLatestProductList();
        $randomSingleProduct = $this->cacheHomePageRandomSingleProductItem();
        $allProductsColorList = $this->cacheProductsColorsArray();
        $clearanceSaleProducts = $this->cacheHomePageClearanceSaleProducts();
        $recommendedProduct = $this->cacheHomePageRandomSingleProductItem();

        $featuredProductsList = Cache::remember(CACHE_FOR_FEATURED_PRODUCTS_LIST, CACHE_FOR_3_HOURS, function () {
            $featuredProductsList = $this->product->with(['clearanceSale' => function ($query) {
                $query->active();
            }])
                ->active()
                ->where('featured', 1)
                ->withCount(['reviews']);
            return ProductManager::getPriorityWiseFeaturedProductsQuery(query: $featuredProductsList, dataLimit: 15);
        });

        $mostSearchingProducts = Cache::remember(CACHE_FOR_MOST_SEARCHING_PRODUCTS_LIST, CACHE_FOR_3_HOURS, function () {
            return Product::active()->with(['category', 'clearanceSale' => function ($query) {
                return $query->active();
            }])
                ->withCount('reviews')
                ->withSum('tags', 'visit_count')->orderBy('tags_sum_visit_count', 'desc')->get()->take(10);
        });

        $dealOfTheDay = $this->dealOfTheDay->with(['product' => function ($query) {
            $query->active()->with(['clearanceSale' => function ($query) {
                $query->active();
            }]);
        }, 'product.clearanceSale' => function ($query) {
            $query->active();
        }])
            ->where('status', 1)
            ->first();

        $vendorList = $this->cacheShopTable();
        $newSellers = $vendorList->sortByDesc('id')->take(12);
        $topRatedShops = $vendorList->where('review_count', '!=', 0)->sortByDesc('average_rating')->take(12);

        $baseProductQuery = $this->product->with(['category', 'compareList', 'reviews', 'flashDealProducts.flashDeal', 'clearanceSale' => function ($query) {
            $query->active();
        }])
            ->withSum('orderDetails', 'qty')
            ->active();

        $allProductsList = $baseProductQuery->orderBy('order_details_sum_qty', 'DESC')->paginate(20);
        $allProductsList?->map(function ($product) use ($currentDate) {
            $flashDealStatus = 0;
            $flashDealEndDate = 0;
            if (count($product->flashDealProducts) > 0) {
                $flash_deal = $product->flashDealProducts[0]->flashDeal;
                if ($flash_deal) {
                    $start_date = date('Y-m-d H:i:s', strtotime($flash_deal->start_date));
                    $end_date = date('Y-m-d H:i:s', strtotime($flash_deal->end_date));
                    $flashDealStatus = $flash_deal->status == 1 && (($currentDate >= $start_date) && ($currentDate <= $end_date)) ? 1 : 0;
                    $flashDealEndDate = $flash_deal->end_date;
                }
            }
            $product['flash_deal_status'] = $flashDealStatus;
            $product['flash_deal_end_date'] = $flashDealEndDate;
            return $product;
        });

        $recentOrderShopList = [];
        if ($user != 'offline') {
            $recentOrderShopList = $this->product->with('seller.shop')
                ->whereHas('seller.orders', function ($query) {
                    $query->where(['customer_id' => auth('customer')->id(), 'seller_is' => 'seller']);
                })
                ->active()
                ->inRandomOrder()->take(12)->get();
        }

        $allProductSectionOrders = $this->order->where(['order_type' => 'default_type'])->whereHas('orderDetails', function ($query) {
            return $query->whereHas('product', function ($query) {
                return $query->active();
            });
        });

        $allProductsGroupInfo = [
            'total_products' => $this->product->active()->count(),
            'total_orders' => $allProductSectionOrders->count(),
            'total_delivery' => $allProductSectionOrders->where(['payment_status' => 'paid', 'order_status' => 'delivered'])->count(),
            'total_reviews' => $this->review->active()->where('product_id', '!=', 0)->whereHas('product', function ($query) {
                return $query->active();
            })->whereNull('delivery_man_id')->count(),
        ];

        $data = [];
        return view(VIEW_FILE_NAMES['home'],
            compact(
                'activeBrands', 'latestProductsList', 'dealOfTheDay', 'topVendorsList', 'topRatedShops', 'bannerTypeMainBanner', 'mostVisitedCategories', 'randomSingleProduct', 'newSellers', 'bannerTypeSidebarBanner', 'bannerTypeTopSideBanner', 'recentOrderShopList',
                'categories', 'allProductsColorList', 'allProductsGroupInfo', 'mostSearchingProducts', 'mostDemandedProducts', 'featuredProductsList', 'bannerTypePromoBannerLeft', 'bannerTypePromoBannerMiddleTop', 'bannerTypePromoBannerMiddleBottom', 'bannerTypePromoBannerRight', 'bannerTypePromoBannerBottom', 'currentDate', 'allProductsList', 'flashDeal', 'data', 'clearanceSaleProducts', 'singlePageProductCount', 'recommendedProduct'
            )
        );
    }

    public function theme_vmarket(): View
    {
        $categories = Cache::remember('theme_vmarket_home_categories', 3600, function () {
            return Category::with(['childes' => function ($q) {
                $q->orderBy('priority', 'asc');
            }])
                ->where('position', 0)
                ->where('home_status', 1)
                ->orderBy('priority', 'asc')
                ->get();
        });

        $bannerTypeMainBanner = Cache::remember('theme_vmarket_banners_main', 3600, function () {
            return Banner::where(['published' => 1, 'banner_type' => 'Main Banner'])
                ->where(function ($q) {
                    $q->where('theme', 'theme_vmarket')->orWhere('theme', 'default');
                })
                ->orderBy('id', 'desc')
                ->get();
        });

        $bannerTypeFooterBanner = Cache::remember('theme_vmarket_banners_footer', 3600, function () {
            return Banner::where(['published' => 1, 'banner_type' => 'Footer Banner'])
                ->where(function ($q) {
                    $q->where('theme', 'theme_vmarket')->orWhere('theme', 'default');
                })
                ->orderBy('id', 'desc')
                ->get();
        });

        $bannerTypePopupBanner = Cache::remember('theme_vmarket_banners_popup', 3600, function () {
            return Banner::where(['published' => 1, 'banner_type' => 'Popup Banner'])
                ->where(function ($q) {
                    $q->where('theme', 'theme_vmarket')->orWhere('theme', 'default');
                })
                ->latest('id')
                ->first();
        });

        $activeCity = session('customer_city', 'Uyo');
        $activeState = session('customer_state', 'Akwa Ibom');
        $fulfillmentMode = session('fulfillment_mode', 'delivery');
        $activeLgaId = session('customer_lga_id');
        if (empty($activeLgaId)) {
            $activeLgaId = Cache::remember('lga_id_' . $activeCity, 86400, function () use ($activeCity) {
                return \App\Models\Lga::where('name', $activeCity)->value('id') ?? 69;
            });
        }

        $homeCacheKeys = ['home_featured_products_vmarket_lga_' . $activeLgaId, 'home_latest_products_vmarket_lga_' . $activeLgaId];
        $featuredProductsList = Cache::remember($homeCacheKeys[0], 900, function () use ($activeLgaId) {
            return Product::marketplaceEligible()
                ->where('featured', 1)
                ->availableInLga($activeLgaId)
                ->with(['seller.shop', 'rating'])
                ->take(12)
                ->get();
        });

        $latestProductsList = Cache::remember($homeCacheKeys[1], 900, function () use ($activeLgaId) {
            return Product::marketplaceEligible()
                ->availableInLga($activeLgaId)
                ->with(['seller.shop', 'rating'])
                ->latest('id')
                ->take(12)
                ->get();
        });

        $topVendorsList = ProductManager::getPriorityWiseTopVendorQuery(query: $this->cacheHomePageTopVendorsList());
        $brands = $this->cachePriorityWiseBrandList();

        // [AI] Verified Shops available for customer LGA (Tier 1 Same LGA -> Tier 2 Same State -> Nationwide)
        $nearbyShops = Cache::remember('theme_vmarket_nearby_shops_lga_' . $activeLgaId, 1800, function () use ($activeLgaId) {
            $customerLga = \App\Models\Lga::find($activeLgaId);
            $stateId = (int) ($customerLga?->state_id ?? 0);

            return \App\Models\Shop::where('temporary_close', 0)
                ->where(function ($query) {
                    $query->where('author_type', 'admin')->orWhereHas('seller', function ($seller) {
                        $seller->where('status', 'approved')->where('marketplace_status', 'approved');
                    });
                })
                ->when($stateId > 0, function ($q) use ($activeLgaId, $stateId) {
                    $q->orderByRaw("CASE WHEN lga_id = {$activeLgaId} THEN 0 WHEN state_id = {$stateId} THEN 1 ELSE 2 END");
                })
                ->with(['seller'])
                ->take(8)
                ->get();
        });

        return view(VIEW_FILE_NAMES['home'], [
            'categories' => $categories,
            'bannerTypeMainBanner' => $bannerTypeMainBanner,
            'bannerTypeFooterBanner' => $bannerTypeFooterBanner,
            'bannerTypePopupBanner' => $bannerTypePopupBanner,
            'featuredProductsList' => $featuredProductsList,
            'latestProductsList' => $latestProductsList,
            'topVendorsList' => $topVendorsList,
            'brands' => $brands,
            'activeCity' => $activeCity,
            'activeState' => $activeState,
            'fulfillmentMode' => $fulfillmentMode,
            'nearbyShops' => $nearbyShops,
        ]);
    }
}
