# Role Security & Endpoint Access Policy: Unauthenticated Visitor / Guest

> **Role Scope:** `Unauthenticated Visitor / Guest`  
> **Total Allowed Endpoints:** `258`  
> **Total Disallowed / Blocked Endpoints:** `1325`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

Public internet visitor browsing public storefront catalog before registration.

### Core Authorized Capabilities:
- ✅ **Public Product Catalog Browsing, Categories & Brand Directory**
- ✅ **Storefront Themes (Default, Aster, Fashion) & Blog Articles**
- ✅ **Registration and Login Handshakes**

### Strict Architectural Restrictions:
- ⛔ **Zero-Trust Gate: Any attempt to access private portals (/admin, /vendor, /pos) returns HTTP 302/401/404**

---

## 2. Authorized Endpoints Access Matrix (258 Endpoints)

| # | Method | URI | Route Name | Action / Controller |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/_debugbar/open` | `debugbar.openhandler` | `Barryvdh\Debugbar\Controllers\OpenHandlerController@handle` |
| 2 | `GET` | `/_debugbar/clockwork/{id}` | `debugbar.clockwork` | `Barryvdh\Debugbar\Controllers\OpenHandlerController@clockwork` |
| 3 | `GET` | `/_debugbar/assets/stylesheets` | `debugbar.assets.css` | `Barryvdh\Debugbar\Controllers\AssetController@css` |
| 4 | `GET` | `/_debugbar/assets/javascript` | `debugbar.assets.js` | `Barryvdh\Debugbar\Controllers\AssetController@js` |
| 5 | `GET` | `/oauth/authorize` | `passport.authorizations.authorize` | `Laravel\Passport\Http\Controllers\AuthorizationController@authorize` |
| 6 | `GET` | `/oauth/tokens` | `passport.tokens.index` | `Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController@forUser` |
| 7 | `GET` | `/oauth/clients` | `passport.clients.index` | `Laravel\Passport\Http\Controllers\ClientController@forUser` |
| 8 | `GET` | `/oauth/scopes` | `passport.scopes.index` | `Laravel\Passport\Http\Controllers\ScopeController@all` |
| 9 | `GET` | `/oauth/personal-access-tokens` | `passport.personal.tokens.index` | `Laravel\Passport\Http\Controllers\PersonalAccessTokenController@forUser` |
| 10 | `GET` | `/sanctum/csrf-cookie` | `sanctum.csrf-cookie` | `Laravel\Sanctum\Http\Controllers\CsrfCookieController@show` |
| 11 | `GET` | `/_ignition/health-check` | `ignition.healthCheck` | `Spatie\LaravelIgnition\Http\Controllers\HealthCheckController` |
| 12 | `GET` | `/api/v1/pos/products` | `unnamed` | `App\Http\Controllers\RestAPI\v1\PosSyncApiController@getProducts` |
| 13 | `GET` | `/api/v1/webhooks/whatsapp` | `unnamed` | `App\Http\Controllers\RestAPI\v1\WhatsAppWebhookController@verify` |
| 14 | `GET` | `/api/v1/feed/sync` | `unnamed` | `App\Http\Controllers\RestAPI\v1\FeedSyncController@getInitialFeed` |
| 15 | `GET` | `/api/v1/config` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ConfigController@configuration` |
| 16 | `GET` | `/api/v1/business-pages` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ConfigController@getBusinessPagesList` |
| 17 | `GET` | `/api/v1/auth/logout` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\PassportAuthController@logout` |
| 18 | `GET` | `/api/v1/shipping-method/detail/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ShippingMethodController@get_shipping_method_info` |
| 19 | `GET` | `/api/v1/shipping-method/by-seller/{id}/{seller_is}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ShippingMethodController@shipping_methods_by_seller` |
| 20 | `GET` | `/api/v1/shipping-method/chosen` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ShippingMethodController@chosen_shipping_methods` |
| 21 | `GET` | `/api/v1/shipping-method/check-shipping-type` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ShippingMethodController@check_shipping_type` |
| 22 | `GET` | `/api/v1/cart` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CartController@getCartList` |
| 23 | `GET` | `/api/v1/customer/order/get-order-by-id` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@getOrderById` |
| 24 | `GET` | `/api/v1/notifications` | `unnamed` | `App\Http\Controllers\RestAPI\v1\NotificationController@list` |
| 25 | `GET` | `/api/v1/notifications/seen` | `unnamed` | `App\Http\Controllers\RestAPI\v1\NotificationController@notification_seen` |
| 26 | `GET` | `/api/v1/attributes` | `unnamed` | `App\Http\Controllers\RestAPI\v1\AttributeController@get_attributes` |
| 27 | `GET` | `/api/v1/flash-deals` | `unnamed` | `App\Http\Controllers\RestAPI\v1\FlashDealController@getFlashDeal` |
| 28 | `GET` | `/api/v1/flash-deals/products/{deal_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\FlashDealController@getFlashDealProducts` |
| 29 | `GET` | `/api/v1/deals/featured` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DealController@getFeaturedDealProducts` |
| 30 | `GET` | `/api/v1/dealsoftheday/deal-of-the-day` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DealOfTheDayController@getDealOfTheDayProduct` |
| 31 | `GET` | `/api/v1/products/reviews/{slug}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_product_reviews` |
| 32 | `GET` | `/api/v1/products/rating/{product_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_product_rating` |
| 33 | `GET` | `/api/v1/products/counter/{slug}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@counter` |
| 34 | `GET` | `/api/v1/products/shipping-methods` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_shipping_methods` |
| 35 | `GET` | `/api/v1/products/social-share-link/{product_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@socialShareLink` |
| 36 | `GET` | `/api/v1/products/review/{product_id}/{order_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getProductReviewByOrder` |
| 37 | `GET` | `/api/v1/products/latest` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_latest_products` |
| 38 | `GET` | `/api/v1/products/new-arrival` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getNewArrivalProducts` |
| 39 | `GET` | `/api/v1/products/featured` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getFeaturedProductsList` |
| 40 | `GET` | `/api/v1/products/top-rated` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getTopRatedProducts` |
| 41 | `GET` | `/api/v1/products/search` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_searched_products` |
| 42 | `GET` | `/api/v1/products/suggestion-product` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_suggestion_product` |
| 43 | `GET` | `/api/v1/products/details/{slug}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getProductDetails` |
| 44 | `GET` | `/api/v1/products/related-products/{slug}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_related_products` |
| 45 | `GET` | `/api/v1/products/best-sellings` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getBestSellingProducts` |
| 46 | `GET` | `/api/v1/products/home-categories` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_home_categories` |
| 47 | `GET` | `/api/v1/products/discounted-product` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_discounted_product` |
| 48 | `GET` | `/api/v1/products/most-demanded-product` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_most_demanded_product` |
| 49 | `GET` | `/api/v1/products/shop-again-product` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getShopAgainProduct` |
| 50 | `GET` | `/api/v1/products/just-for-you` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@just_for_you` |
| 51 | `GET` | `/api/v1/products/most-searching` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getMostSearchingProductsList` |
| 52 | `GET` | `/api/v1/products/digital-author-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getDigitalProductsAuthorList` |
| 53 | `GET` | `/api/v1/products/digital-publishing-house-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getDigitalPublishingHouseList` |
| 54 | `GET` | `/api/v1/products/clearance-sale` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getClearanceSale` |
| 55 | `GET` | `/api/v1/products/feed/google-merchant.xml` | `unnamed` | `App\Http\Controllers\ProductFeedExportController@googleMerchantXml` |
| 56 | `GET` | `/api/v1/products/feed/facebook-catalog.csv` | `unnamed` | `App\Http\Controllers\ProductFeedExportController@facebookCatalogCsv` |
| 57 | `GET` | `/api/v1/products/feed/tiktok-catalog.csv` | `unnamed` | `App\Http\Controllers\ProductFeedExportController@tiktokCatalogCsv` |
| 58 | `GET` | `/api/v1/seller/{slug}/products` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@getVendorProducts` |
| 59 | `GET` | `/api/v1/seller/{slug}/seller-best-selling-products` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@get_seller_best_selling_products` |
| 60 | `GET` | `/api/v1/seller/{slug}/seller-featured-product` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@get_sellers_featured_product` |
| 61 | `GET` | `/api/v1/seller/{slug}/seller-recommended-products` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@get_sellers_recommended_products` |
| 62 | `GET` | `/api/v1/categories` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CategoryController@get_categories` |
| 63 | `GET` | `/api/v1/categories/products/{category_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CategoryController@get_products` |
| 64 | `GET` | `/api/v1/categories/find-what-you-need` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CategoryController@find_what_you_need` |
| 65 | `GET` | `/api/v1/brands` | `unnamed` | `App\Http\Controllers\RestAPI\v1\BrandController@get_brands` |
| 66 | `GET` | `/api/v1/brands/products/{brand_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\BrandController@get_products` |
| 67 | `GET` | `/api/v1/delivery-hubs/states` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@getStates` |
| 68 | `GET` | `/api/v1/delivery-hubs/cities/{state_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@getCities` |
| 69 | `GET` | `/api/v1/delivery-hubs/hubs/{city_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@getHubs` |
| 70 | `GET` | `/api/v1/customer/get-restricted-country-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_restricted_country_list` |
| 71 | `GET` | `/api/v1/customer/get-restricted-zip-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_restricted_zip_list` |
| 72 | `GET` | `/api/v1/customer/address/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@address_list` |
| 73 | `GET` | `/api/v1/customer/order/place` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@place_order` |
| 74 | `GET` | `/api/v1/customer/order/offline-payment-method-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@offline_payment_method_list` |
| 75 | `GET` | `/api/v1/customer/order/details` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_order_details` |
| 76 | `GET` | `/api/v1/customer/order/generate-invoice` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@getOrderInvoice` |
| 77 | `GET` | `/api/v1/customer/order/deliveryman-review` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ReviewController@getReview` |
| 78 | `GET` | `/api/v1/customer/info` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@info` |
| 79 | `GET` | `/api/v1/customer/account-delete/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@account_delete` |
| 80 | `GET` | `/api/v1/customer/address/get/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_address` |
| 81 | `GET` | `/api/v1/customer/support-ticket/get` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_support_tickets` |
| 82 | `GET` | `/api/v1/customer/support-ticket/conv/{ticket_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_support_ticket_conv` |
| 83 | `GET` | `/api/v1/customer/support-ticket/close/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@support_ticket_close` |
| 84 | `GET` | `/api/v1/customer/compare/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CompareController@list` |
| 85 | `GET` | `/api/v1/customer/compare/product-replace` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CompareController@compare_product_replace` |
| 86 | `GET` | `/api/v1/customer/wish-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@wish_list` |
| 87 | `GET` | `/api/v1/customer/restock-requests/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerRestockRequestController@restockRequestsList` |
| 88 | `GET` | `/api/v1/customer/order/place-by-wallet` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@placeOrderByWallet` |
| 89 | `GET` | `/api/v1/customer/order/refund` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@refund_request` |
| 90 | `GET` | `/api/v1/customer/order/refund-details` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@refund_details` |
| 91 | `GET` | `/api/v1/customer/order/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_order_list` |
| 92 | `GET` | `/api/v1/customer/chat/list/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ChatController@list` |
| 93 | `GET` | `/api/v1/customer/chat/get-messages/{type}/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ChatController@get_message` |
| 94 | `GET` | `/api/v1/customer/chat/search/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ChatController@search` |
| 95 | `GET` | `/api/v1/customer/wallet/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\UserWalletController@list` |
| 96 | `GET` | `/api/v1/customer/wallet/bonus-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\UserWalletController@bonus_list` |
| 97 | `GET` | `/api/v1/customer/loyalty/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\UserLoyaltyController@list` |
| 98 | `GET` | `/api/v1/customer/order/digital-product-download/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@digital_product_download` |
| 99 | `GET` | `/api/v1/customer/order/digital-product-download-otp-verify` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@digital_product_download_otp_verify` |
| 100 | `GET` | `/api/v1/order/track` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@track_by_order_id` |
| 101 | `GET` | `/api/v1/order/track-order-details` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@track_order_details_history` |
| 102 | `GET` | `/api/v1/order/cancel-order` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@order_cancel` |
| 103 | `GET` | `/api/v1/banners` | `unnamed` | `App\Http\Controllers\RestAPI\v1\BannerController@getBannerList` |
| 104 | `GET` | `/api/v1/seller` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@get_seller_info` |
| 105 | `GET` | `/api/v1/seller/list/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@getSellerList` |
| 106 | `GET` | `/api/v1/seller/more` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@more_sellers` |
| 107 | `GET` | `/api/v1/coupon/apply` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CouponController@apply` |
| 108 | `GET` | `/api/v1/coupon/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CouponController@list` |
| 109 | `GET` | `/api/v1/coupon/applicable-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CouponController@applicable_list` |
| 110 | `GET` | `/api/v1/coupons/{slug}/seller-wise-coupons` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CouponController@getSellerWiseCoupon` |
| 111 | `GET` | `/api/v1/mapapi/place-api-autocomplete` | `unnamed` | `App\Http\Controllers\RestAPI\v1\MapApiController@placeApiAutocomplete` |
| 112 | `GET` | `/api/v1/mapapi/distance-api` | `unnamed` | `App\Http\Controllers\RestAPI\v1\MapApiController@distanceApi` |
| 113 | `GET` | `/api/v1/mapapi/place-api-details` | `unnamed` | `App\Http\Controllers\RestAPI\v1\MapApiController@placeApiDetails` |
| 114 | `GET` | `/api/v1/mapapi/geocode-api` | `unnamed` | `App\Http\Controllers\RestAPI\v1\MapApiController@geocode_api` |
| 115 | `GET` | `/api/v1/faq` | `unnamed` | `App\Http\Controllers\RestAPI\v1\GeneralController@faq` |
| 116 | `GET` | `/api/v1/get-guest-id` | `unnamed` | `App\Http\Controllers\RestAPI\v1\GeneralController@get_guest_id` |
| 117 | `GET` | `/api/v2/seller/seller-info` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@seller_info` |
| 118 | `GET` | `/api/v2/seller/account-delete` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@account_delete` |
| 119 | `GET` | `/api/v2/seller/seller-delivery-man` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@seller_delivery_man` |
| 120 | `GET` | `/api/v2/seller/shop-product-reviews` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@shop_product_reviews` |
| 121 | `GET` | `/api/v2/seller/shop-product-reviews-status` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@shop_product_reviews_status` |
| 122 | `GET` | `/api/v2/seller/monthly-earning` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@monthly_earning` |
| 123 | `GET` | `/api/v2/seller/monthly-commission-given` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@monthly_commission_given` |
| 124 | `GET` | `/api/v2/seller/shop-info` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@shop_info` |
| 125 | `GET` | `/api/v2/seller/transactions` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@transaction` |
| 126 | `GET` | `/api/v2/seller/brands` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\BrandController@getBrands` |
| 127 | `GET` | `/api/v2/seller/products/list` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@list` |
| 128 | `GET` | `/api/v2/seller/products/stock-out-list` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@stock_out_list` |
| 129 | `GET` | `/api/v2/seller/products/status-update` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@status_update` |
| 130 | `GET` | `/api/v2/seller/products/edit/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@edit` |
| 131 | `GET` | `/api/v2/seller/products/barcode/generate` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@barcode_generate` |
| 132 | `GET` | `/api/v2/seller/orders/list` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\OrderController@list` |
| 133 | `GET` | `/api/v2/seller/orders/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\OrderController@details` |
| 134 | `GET` | `/api/v2/seller/refund/list` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\RefundController@list` |
| 135 | `GET` | `/api/v2/seller/refund/refund-details` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\RefundController@refund_details` |
| 136 | `GET` | `/api/v2/seller/shipping/get-shipping-method` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\shippingController@get_shipping_type` |
| 137 | `GET` | `/api/v2/seller/shipping/selected-shipping-method` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\shippingController@selected_shipping_type` |
| 138 | `GET` | `/api/v2/seller/shipping/all-category-cost` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\shippingController@all_category_cost` |
| 139 | `GET` | `/api/v2/seller/shipping-method/list` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ShippingMethodController@list` |
| 140 | `GET` | `/api/v2/seller/shipping-method/edit/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ShippingMethodController@edit` |
| 141 | `GET` | `/api/v2/seller/messages/list/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ChatController@list` |
| 142 | `GET` | `/api/v2/seller/messages/get-message/{type}/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ChatController@get_message` |
| 143 | `GET` | `/api/v2/seller/messages/search/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ChatController@search` |
| 144 | `GET` | `/search` | `unnamed` | `Closure` |
| 145 | `GET` | `/g-recaptcha-session-store` | `g-recaptcha-session-store` | `App\Http\Controllers\SharedController@storeRecaptchaSession` |
| 146 | `GET` | `/activation-check` | `system.activation-check` | `App\Http\Controllers\SharedController@getActivationCheckView` |
| 147 | `GET` | `/login/{loginUrl}` | `unnamed` | `App\Http\Controllers\Admin\Auth\LoginController@index` |
| 148 | `GET` | `/login/recaptcha/{tmp}` | `recaptcha` | `App\Http\Controllers\Admin\Auth\LoginController@generateReCaptcha` |
| 149 | `GET` | `/image-proxy` | `unnamed` | `Closure` |
| 150 | `GET` | `/maintenance-mode` | `maintenance-mode` | `App\Http\Controllers\Web\WebController@maintenance_mode` |
| 151 | `GET` | `/product-compare/index` | `product-compare.index` | `App\Http\Controllers\Web\ProductCompareController@index` |
| 152 | `GET` | `/product-compare/delete` | `product-compare.delete` | `App\Http\Controllers\Web\ProductCompareController@delete` |
| 153 | `GET` | `/product-compare/delete-all` | `product-compare.delete-all` | `App\Http\Controllers\Web\ProductCompareController@deleteAllCompareProduct` |
| 154 | `GET` | `/` | `home` | `App\Http\Controllers\Web\HomeController@index` |
| 155 | `GET` | `/quick-view` | `quick-view` | `App\Http\Controllers\Web\WebController@getQuickView` |
| 156 | `GET` | `/searched-products` | `searched-products` | `App\Http\Controllers\Web\WebController@getSearchedProducts` |
| 157 | `GET` | `/checkout-details` | `checkout-details` | `App\Http\Controllers\Web\WebController@checkout_details` |
| 158 | `GET` | `/checkout-shipping` | `checkout-shipping` | `App\Http\Controllers\Web\WebController@checkout_details` |
| 159 | `GET` | `/checkout-payment` | `checkout-payment` | `App\Http\Controllers\Web\WebController@checkout_payment` |
| 160 | `GET` | `/checkout-review` | `checkout-review` | `App\Http\Controllers\Web\WebController@checkout_payment` |
| 161 | `GET` | `/checkout-complete` | `checkout-complete` | `App\Http\Controllers\Web\WebController@getCashOnDeliveryCheckoutComplete` |
| 162 | `GET` | `/order-placed` | `order-placed` | `App\Http\Controllers\Web\WebController@order_placed` |
| 163 | `GET` | `/order-placed-success` | `order-placed-success` | `App\Http\Controllers\Web\WebController@getOrderPlaceView` |
| 164 | `GET` | `/shop-cart` | `shop-cart` | `App\Http\Controllers\Web\WebController@shop_cart` |
| 165 | `GET` | `/digital-product-download/{id}` | `digital-product-download` | `App\Http\Controllers\Web\WebController@getDigitalProductDownload` |
| 166 | `GET` | `/pay-offline-method-list` | `pay-offline-method-list` | `App\Http\Controllers\Web\WebController@pay_offline_method_list` |
| 167 | `GET` | `/checkout-complete-wallet` | `checkout-complete-wallet` | `App\Http\Controllers\Web\WebController@checkout_complete_wallet` |
| 168 | `GET` | `/search-shop` | `search-shop` | `App\Http\Controllers\Web\WebController@search_shop` |
| 169 | `GET` | `/categories` | `categories` | `App\Http\Controllers\Web\WebController@getAllCategoriesView` |
| 170 | `GET` | `/category-ajax/{id}` | `category-ajax` | `App\Http\Controllers\Web\WebController@categories_by_category` |
| 171 | `GET` | `/brands` | `brands` | `App\Http\Controllers\Web\WebController@getAllBrandsView` |
| 172 | `GET` | `/seller-profile/{id}` | `seller-profile` | `App\Http\Controllers\Web\WebController@seller_profile` |
| 173 | `GET` | `/business-page/{slug}` | `business-page.view` | `App\Http\Controllers\Web\PageController@getPageView` |
| 174 | `GET` | `/contacts` | `contacts` | `App\Http\Controllers\Web\PageController@getContactView` |
| 175 | `GET` | `/helpTopic` | `helpTopic` | `App\Http\Controllers\Web\PageController@getHelpTopicView` |
| 176 | `GET` | `/product/{slug}` | `product` | `App\Http\Controllers\Web\ProductDetailsController@index` |
| 177 | `GET` | `/products` | `products` | `App\Http\Controllers\Web\ProductListController@products` |
| 178 | `GET` | `/flash-deals/{id}` | `flash-deals` | `App\Http\Controllers\Web\ProductListController@getFlashDealsView` |
| 179 | `GET` | `/brand/{slug}` | `brand-products` | `App\Http\Controllers\Web\ProductListController@getBrandProductsView` |
| 180 | `GET` | `/category/{slug}` | `category-products` | `App\Http\Controllers\Web\ProductListController@getCategoryProductsView` |
| 181 | `GET` | `/featured-products` | `featured-products` | `App\Http\Controllers\Web\ProductListController@getFeaturedProductsView` |
| 182 | `GET` | `/featured-deal-products` | `featured-deal-products` | `App\Http\Controllers\Web\ProductListController@getFeaturedDealProductsView` |
| 183 | `GET` | `/latest-products` | `latest-products` | `App\Http\Controllers\Web\ProductListController@getLatestProductsView` |
| 184 | `GET` | `/best-selling-products` | `best-selling-products` | `App\Http\Controllers\Web\ProductListController@getBestSellingProductsView` |
| 185 | `GET` | `/top-rated-products` | `top-rated-products` | `App\Http\Controllers\Web\ProductListController@getTopRatedProductsView` |
| 186 | `GET` | `/most-favorite-products` | `most-favorite-products` | `App\Http\Controllers\Web\ProductListController@getMostFavoriteProductsView` |
| 187 | `GET` | `/discounted-products` | `discounted-products` | `App\Http\Controllers\Web\ProductListController@getDiscountedProductsView` |
| 188 | `GET` | `/clearance-sale-products` | `clearance-sale-products` | `App\Http\Controllers\Web\ProductListController@getClearanceSaleProductsView` |
| 189 | `GET` | `/wishlists` | `wishlists` | `App\Http\Controllers\Web\WebController@viewWishlist` |
| 190 | `GET` | `/delete-wishlist-all` | `delete-wishlist-all` | `App\Http\Controllers\Web\WebController@deleteAllWishListItems` |
| 191 | `GET` | `/searched-products-for-compare` | `searched-products-compare` | `App\Http\Controllers\Web\WebController@getSearchedProductsForCompareList` |
| 192 | `GET` | `/support-ticket/{id}` | `support-ticket.index` | `App\Http\Controllers\Web\UserProfileController@single_ticket` |
| 193 | `GET` | `/support-ticket/delete/{id}` | `support-ticket.delete` | `App\Http\Controllers\Web\UserProfileController@support_ticket_delete` |
| 194 | `GET` | `/support-ticket/close/{id}` | `support-ticket.close` | `App\Http\Controllers\Web\UserProfileController@support_ticket_close` |
| 195 | `GET` | `/track-order` | `track-order.index` | `App\Http\Controllers\Web\UserProfileController@track_order` |
| 196 | `GET` | `/track-order/result-view` | `track-order.result-view` | `App\Http\Controllers\Web\UserProfileController@track_order_result` |
| 197 | `GET` | `/track-order/last` | `track-order.last` | `App\Http\Controllers\Web\UserProfileController@track_last_order` |
| 198 | `GET` | `/track-order/result` | `track-order.result` | `App\Http\Controllers\Web\UserProfileController@track_order_result` |
| 199 | `GET` | `/track-order/order-wise-result-view` | `track-order.order-wise-result-view` | `App\Http\Controllers\Web\UserProfileController@track_order_wise_result` |
| 200 | `GET` | `/user-profile` | `user-profile` | `App\Http\Controllers\Web\UserProfileController@user_profile` |
| 201 | `GET` | `/user-account` | `user-account` | `App\Http\Controllers\Web\UserProfileController@user_account` |
| 202 | `GET` | `/account-address-add` | `account-address-add` | `App\Http\Controllers\Web\UserProfileController@account_address_add` |
| 203 | `GET` | `/account-address` | `account-address` | `App\Http\Controllers\Web\UserProfileController@account_address` |
| 204 | `GET` | `/account-address-delete` | `address-delete` | `App\Http\Controllers\Web\UserProfileController@address_delete` |
| 205 | `GET` | `/account-address-edit/{id}` | `address-edit` | `App\Http\Controllers\Web\UserProfileController@address_edit` |
| 206 | `GET` | `/account-payment` | `account-payment` | `App\Http\Controllers\Web\UserProfileController@account_payment` |
| 207 | `GET` | `/account-oder` | `account-oder` | `App\Http\Controllers\Web\UserProfileController@account_order` |
| 208 | `GET` | `/account-order-details` | `account-order-details` | `App\Http\Controllers\Web\UserProfileController@account_order_details` |
| 209 | `GET` | `/account-order-details-vendor-info` | `account-order-details-vendor-info` | `App\Http\Controllers\Web\UserProfileController@account_order_details_seller_info` |
| 210 | `GET` | `/account-order-details-delivery-man-info` | `account-order-details-delivery-man-info` | `App\Http\Controllers\Web\UserProfileController@account_order_details_delivery_man_info` |
| 211 | `GET` | `/account-order-details-reviews` | `account-order-details-reviews` | `App\Http\Controllers\Web\UserProfileController@getAccountOrderDetailsReviewsView` |
| 212 | `GET` | `/generate-invoice/{id}` | `generate-invoice` | `App\Http\Controllers\Web\UserProfileController@generate_invoice` |
| 213 | `GET` | `/account-wishlist` | `account-wishlist` | `App\Http\Controllers\Web\UserProfileController@account_wishlist` |
| 214 | `GET` | `/refund-request/{id}` | `refund-request` | `App\Http\Controllers\Web\UserProfileController@refund_request` |
| 215 | `GET` | `/refund-details/{id}` | `refund-details` | `App\Http\Controllers\Web\UserProfileController@refund_details` |
| 216 | `GET` | `/account-tickets` | `account-tickets` | `App\Http\Controllers\Web\UserProfileController@account_tickets` |
| 217 | `GET` | `/order-cancel/{id}` | `order-cancel` | `App\Http\Controllers\Web\UserProfileController@order_cancel` |
| 218 | `GET` | `/account-delete/{id}` | `account-delete` | `App\Http\Controllers\Web\UserProfileController@account_delete` |
| 219 | `GET` | `/refer-earn` | `refer-earn` | `App\Http\Controllers\Web\UserProfileController@refer_earn` |
| 220 | `GET` | `/user-coupons` | `user-coupons` | `App\Http\Controllers\Web\UserProfileController@user_coupons` |
| 221 | `GET` | `/user-restock-requests` | `user-restock-requests` | `App\Http\Controllers\Web\UserProfileController@restockRequestsView` |
| 222 | `GET` | `/user-restock-request-delete` | `user-restock-request-delete` | `App\Http\Controllers\Web\UserProfileController@deleteRestockRequest` |
| 223 | `GET` | `/user-all-restock-request-delete/{ids}` | `user-all-restock-request-delete` | `App\Http\Controllers\Web\UserProfileController@deleteRestockRequest` |
| 224 | `GET` | `/chat/{type}` | `chat` | `App\Http\Controllers\Web\ChattingController@index` |
| 225 | `GET` | `/message` | `messages` | `App\Http\Controllers\Web\ChattingController@getMessageByUser` |
| 226 | `GET` | `/wallet-account` | `wallet-account` | `App\Http\Controllers\Web\UserWalletController@myWalletAccount` |
| 227 | `GET` | `/wallet` | `wallet` | `App\Http\Controllers\Web\UserWalletController@index` |
| 228 | `GET` | `/loyalty` | `loyalty` | `App\Http\Controllers\Web\UserLoyaltyController@index` |
| 229 | `GET` | `/ajax-loyalty-currency-amount` | `ajax-loyalty-currency-amount` | `App\Http\Controllers\Web\UserLoyaltyController@getLoyaltyCurrencyAmount` |
| 230 | `GET` | `/digital-product-download-pos` | `digital-product-download-pos.index` | `App\Http\Controllers\Web\DigitalProductDownloadController@index` |
| 231 | `GET` | `/ajax-shop-vacation-check` | `ajax-shop-vacation-check` | `App\Http\Controllers\Web\ShopViewController@ajax_shop_vacation_check` |
| 232 | `GET` | `/top-rated` | `topRated` | `App\Http\Controllers\Web\ProductListController@getTopRatedProductsView` |
| 233 | `GET` | `/best-sell` | `bestSell` | `App\Http\Controllers\Web\ProductListController@getBestSellingProductsView` |
| 234 | `GET` | `/new-product` | `newProduct` | `App\Http\Controllers\Web\ProductListController@getLatestProductsView` |
| 235 | `GET` | `/contact/code/captcha/{tmp}` | `contact.default-captcha` | `App\Http\Controllers\Web\WebController@captcha` |
| 236 | `GET` | `/cart/remove-all` | `cart.remove-all` | `App\Http\Controllers\Web\CartController@remove_all_cart` |
| 237 | `GET` | `/coupon/remove` | `coupon.remove` | `App\Http\Controllers\Web\CouponController@removeCoupon` |
| 238 | `GET` | `/authentication-failed` | `authentication-failed` | `Closure` |
| 239 | `GET` | `/web-payment` | `web-payment-success` | `App\Http\Controllers\Customer\PaymentController@web_payment_success` |
| 240 | `GET` | `/payment-success` | `payment-success` | `App\Http\Controllers\Customer\PaymentController@success` |
| 241 | `GET` | `/payment-fail` | `payment-fail` | `App\Http\Controllers\Customer\PaymentController@fail` |
| 242 | `GET` | `/payment/paystack/pay` | `paystack.pay` | `App\Http\Controllers\Payment_Methods\PaystackController@index` |
| 243 | `GET` | `/payment/paystack/callback` | `paystack.callback` | `App\Http\Controllers\Payment_Methods\PaystackController@handleGatewayCallback` |
| 244 | `GET` | `/payment/paystack/cancel` | `paystack.cancel` | `App\Http\Controllers\Payment_Methods\PaystackController@cancel` |
| 245 | `GET` | `/payment/paystack-delivery/callback` | `paystack-delivery.callback` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@paystack_delivery_callback` |
| 246 | `GET` | `/payment/paystack-remittance/callback` | `paystack-remittance.callback` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@paystack_remittance_callback` |
| 247 | `GET` | `/ai` | `ai.index` | `Modules\AI\app\Http\Controllers\AIController@index` |
| 248 | `GET` | `/ai/create` | `ai.create` | `Modules\AI\app\Http\Controllers\AIController@create` |
| 249 | `GET` | `/ai/{ai}` | `ai.show` | `Modules\AI\app\Http\Controllers\AIController@show` |
| 250 | `GET` | `/ai/{ai}/edit` | `ai.edit` | `Modules\AI\app\Http\Controllers\AIController@edit` |
| 251 | `GET` | `/blog` | `frontend.blog.index` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@index` |
| 252 | `GET` | `/popular-blog` | `frontend.blog.popular-blog` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@getPopularBlogs` |
| 253 | `GET` | `/blog/{slug}` | `frontend.blog.details` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@getDetailsView` |
| 254 | `GET` | `/app/blog` | `app.blog.index` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@index` |
| 255 | `GET` | `/app/popular-blog` | `app.blog.popular-blog` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@getPopularBlogs` |
| 256 | `GET` | `/app/blog/{slug}` | `app.blog.details` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@getDetailsView` |
| 257 | `GET` | `/api/v1/taxmodule` | `api.taxmodule` | `Closure` |
| 258 | `GET` | `/api/v1/vat-tax/get-taxVat-list` | `v1.vat-tax.` | `Modules\TaxModule\app\Http\Controllers\Api\V1\TaxController@getTaxVatList` |

---

## 3. Disallowed & Gated Endpoints Summary (1325 Endpoints Blocked)

Attempting to access any of the 1325 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

### Sample Gated Endpoints for this Role:

| # | Method | Gated URI | Guard Interceptor | Reason for Gating |
|:---:|:---:|---|---|---|
| 1 | `DELETE` | `/_debugbar/cache/{key}/{tags?}` | `auth / rbac` | Strictly isolated outside role boundary |
| 2 | `POST` | `/_debugbar/queries/explain` | `auth / rbac` | Strictly isolated outside role boundary |
| 3 | `POST` | `/oauth/token` | `auth / rbac` | Strictly isolated outside role boundary |
| 4 | `POST` | `/oauth/token/refresh` | `auth / rbac` | Strictly isolated outside role boundary |
| 5 | `POST` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 6 | `DELETE` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 7 | `DELETE` | `/oauth/tokens/{token_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 8 | `POST` | `/oauth/clients` | `auth / rbac` | Strictly isolated outside role boundary |
| 9 | `PUT` | `/oauth/clients/{client_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 10 | `DELETE` | `/oauth/clients/{client_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 11 | `POST` | `/oauth/personal-access-tokens` | `auth / rbac` | Strictly isolated outside role boundary |
| 12 | `DELETE` | `/oauth/personal-access-tokens/{token_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 13 | `POST` | `/_ignition/execute-solution` | `auth / rbac` | Strictly isolated outside role boundary |
| 14 | `POST` | `/_ignition/update-config` | `auth / rbac` | Strictly isolated outside role boundary |
| 15 | `POST` | `/api/v1/pos/sync-stock` | `auth / rbac` | Strictly isolated outside role boundary |
| 16 | `POST` | `/api/v1/pos/order-dispatch/{orderId}` | `auth / rbac` | Strictly isolated outside role boundary |
| 17 | `POST` | `/api/v1/webhooks/whatsapp` | `auth / rbac` | Strictly isolated outside role boundary |
| 18 | `POST` | `/api/v1/auth/register` | `auth / rbac` | Strictly isolated outside role boundary |
| 19 | `POST` | `/api/v1/auth/login` | `auth / rbac` | Strictly isolated outside role boundary |
| 20 | `POST` | `/api/v1/auth/check-email` | `auth / rbac` | Strictly isolated outside role boundary |
| 21 | `POST` | `/api/v1/auth/check-phone` | `auth / rbac` | Strictly isolated outside role boundary |
| 22 | `POST` | `/api/v1/auth/firebase-auth-verify` | `auth / rbac` | Strictly isolated outside role boundary |
| 23 | `POST` | `/api/v1/auth/firebase-auth-token-store` | `auth / rbac` | Strictly isolated outside role boundary |
| 24 | `POST` | `/api/v1/auth/verify-otp` | `auth / rbac` | Strictly isolated outside role boundary |
| 25 | `POST` | `/api/v1/auth/verify-email` | `auth / rbac` | Strictly isolated outside role boundary |


