# Role Security & Endpoint Access Policy: Customer (Online Shopper & In-Store Buyer)

> **Role Scope:** `Customer (Online Shopper & In-Store Buyer)`  
> **Total Allowed Endpoints:** `458`  
> **Total Disallowed / Blocked Endpoints:** `1125`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

End-user consumer using web storefront, Aster theme, or mobile apps for browsing, purchasing, and order tracking.

### Core Authorized Capabilities:
- ✅ **Product Browsing, Flash Deals, Clearance Sales, and Search**
- ✅ **Cart Management, Split Checkout, and Digital Wallet Transactions**
- ✅ **Order Tracking, Digital Invoices, and Product Review Submissions**

### Strict Architectural Restrictions:
- ⛔ **Scoped strictly to customer_id (Zero-Trust IDOR Protection)**
- ⛔ **Blocked from all back-office portals (Admin, Merchant, POS, Delivery Hubs)**

---

## 2. Authorized Endpoints Access Matrix (458 Endpoints)

| # | Method | URI | Route Name | Action / Controller |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/_debugbar/open` | `debugbar.openhandler` | `Barryvdh\Debugbar\Controllers\OpenHandlerController@handle` |
| 2 | `GET` | `/_debugbar/clockwork/{id}` | `debugbar.clockwork` | `Barryvdh\Debugbar\Controllers\OpenHandlerController@clockwork` |
| 3 | `GET` | `/_debugbar/assets/stylesheets` | `debugbar.assets.css` | `Barryvdh\Debugbar\Controllers\AssetController@css` |
| 4 | `GET` | `/_debugbar/assets/javascript` | `debugbar.assets.js` | `Barryvdh\Debugbar\Controllers\AssetController@js` |
| 5 | `DELETE` | `/_debugbar/cache/{key}/{tags?}` | `debugbar.cache.delete` | `Barryvdh\Debugbar\Controllers\CacheController@delete` |
| 6 | `POST` | `/_debugbar/queries/explain` | `debugbar.queries.explain` | `Barryvdh\Debugbar\Controllers\QueriesController@explain` |
| 7 | `POST` | `/oauth/token` | `passport.token` | `Laravel\Passport\Http\Controllers\AccessTokenController@issueToken` |
| 8 | `GET` | `/oauth/authorize` | `passport.authorizations.authorize` | `Laravel\Passport\Http\Controllers\AuthorizationController@authorize` |
| 9 | `POST` | `/oauth/token/refresh` | `passport.token.refresh` | `Laravel\Passport\Http\Controllers\TransientTokenController@refresh` |
| 10 | `POST` | `/oauth/authorize` | `passport.authorizations.approve` | `Laravel\Passport\Http\Controllers\ApproveAuthorizationController@approve` |
| 11 | `DELETE` | `/oauth/authorize` | `passport.authorizations.deny` | `Laravel\Passport\Http\Controllers\DenyAuthorizationController@deny` |
| 12 | `GET` | `/oauth/tokens` | `passport.tokens.index` | `Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController@forUser` |
| 13 | `DELETE` | `/oauth/tokens/{token_id}` | `passport.tokens.destroy` | `Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController@destroy` |
| 14 | `GET` | `/oauth/clients` | `passport.clients.index` | `Laravel\Passport\Http\Controllers\ClientController@forUser` |
| 15 | `POST` | `/oauth/clients` | `passport.clients.store` | `Laravel\Passport\Http\Controllers\ClientController@store` |
| 16 | `PUT` | `/oauth/clients/{client_id}` | `passport.clients.update` | `Laravel\Passport\Http\Controllers\ClientController@update` |
| 17 | `DELETE` | `/oauth/clients/{client_id}` | `passport.clients.destroy` | `Laravel\Passport\Http\Controllers\ClientController@destroy` |
| 18 | `GET` | `/oauth/scopes` | `passport.scopes.index` | `Laravel\Passport\Http\Controllers\ScopeController@all` |
| 19 | `GET` | `/oauth/personal-access-tokens` | `passport.personal.tokens.index` | `Laravel\Passport\Http\Controllers\PersonalAccessTokenController@forUser` |
| 20 | `POST` | `/oauth/personal-access-tokens` | `passport.personal.tokens.store` | `Laravel\Passport\Http\Controllers\PersonalAccessTokenController@store` |
| 21 | `DELETE` | `/oauth/personal-access-tokens/{token_id}` | `passport.personal.tokens.destroy` | `Laravel\Passport\Http\Controllers\PersonalAccessTokenController@destroy` |
| 22 | `GET` | `/sanctum/csrf-cookie` | `sanctum.csrf-cookie` | `Laravel\Sanctum\Http\Controllers\CsrfCookieController@show` |
| 23 | `GET` | `/_ignition/health-check` | `ignition.healthCheck` | `Spatie\LaravelIgnition\Http\Controllers\HealthCheckController` |
| 24 | `POST` | `/_ignition/execute-solution` | `ignition.executeSolution` | `Spatie\LaravelIgnition\Http\Controllers\ExecuteSolutionController` |
| 25 | `POST` | `/_ignition/update-config` | `ignition.updateConfig` | `Spatie\LaravelIgnition\Http\Controllers\UpdateConfigController` |
| 26 | `GET` | `/api/v1/pos/products` | `unnamed` | `App\Http\Controllers\RestAPI\v1\PosSyncApiController@getProducts` |
| 27 | `POST` | `/api/v1/pos/sync-stock` | `unnamed` | `App\Http\Controllers\RestAPI\v1\PosSyncApiController@syncStock` |
| 28 | `POST` | `/api/v1/pos/order-dispatch/{orderId}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\PosSyncApiController@confirmDispatch` |
| 29 | `GET` | `/api/v1/webhooks/whatsapp` | `unnamed` | `App\Http\Controllers\RestAPI\v1\WhatsAppWebhookController@verify` |
| 30 | `POST` | `/api/v1/webhooks/whatsapp` | `unnamed` | `App\Http\Controllers\RestAPI\v1\WhatsAppWebhookController@handle` |
| 31 | `GET` | `/api/v1/feed/sync` | `unnamed` | `App\Http\Controllers\RestAPI\v1\FeedSyncController@getInitialFeed` |
| 32 | `GET` | `/api/v1/config` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ConfigController@configuration` |
| 33 | `GET` | `/api/v1/business-pages` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ConfigController@getBusinessPagesList` |
| 34 | `GET` | `/api/v1/auth/logout` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\PassportAuthController@logout` |
| 35 | `POST` | `/api/v1/auth/register` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\CustomerAPIAuthController@register` |
| 36 | `POST` | `/api/v1/auth/login` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\CustomerAPIAuthController@login` |
| 37 | `POST` | `/api/v1/auth/check-email` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\CustomerAPIAuthController@checkEmail` |
| 38 | `POST` | `/api/v1/auth/check-phone` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\CustomerAPIAuthController@checkPhone` |
| 39 | `POST` | `/api/v1/auth/firebase-auth-verify` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\CustomerAPIAuthController@firebaseAuthVerify` |
| 40 | `POST` | `/api/v1/auth/firebase-auth-token-store` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\CustomerAPIAuthController@firebaseAuthTokenStore` |
| 41 | `POST` | `/api/v1/auth/verify-otp` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\CustomerAPIAuthController@verifyOTP` |
| 42 | `POST` | `/api/v1/auth/verify-email` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\CustomerAPIAuthController@verifyEmail` |
| 43 | `POST` | `/api/v1/auth/verify-phone` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\CustomerAPIAuthController@verifyPhone` |
| 44 | `POST` | `/api/v1/auth/registration-with-otp` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\CustomerAPIAuthController@registrationWithOTP` |
| 45 | `POST` | `/api/v1/auth/existing-account-check` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\SocialAuthController@existingAccountCheck` |
| 46 | `POST` | `/api/v1/auth/registration-with-social-media` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\SocialAuthController@registrationWithSocialMedia` |
| 47 | `POST` | `/api/v1/auth/forgot-password` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\CustomerAPIAuthController@passwordResetRequest` |
| 48 | `POST` | `/api/v1/auth/verify-profile-info` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\CustomerAPIAuthController@verifyProfileInfo` |
| 49 | `POST` | `/api/v1/auth/resend-otp-check-phone` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\PhoneVerificationController@resend_otp_check_phone` |
| 50 | `POST` | `/api/v1/auth/resend-otp-check-email` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\EmailVerificationController@resend_otp_check_email` |
| 51 | `POST` | `/api/v1/auth/verify-token` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\ForgotPasswordController@tokenVerificationSubmit` |
| 52 | `PUT` | `/api/v1/auth/reset-password` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\ForgotPasswordController@reset_password_submit` |
| 53 | `POST` | `/api/v1/auth/social-login` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\SocialAuthController@social_login` |
| 54 | `POST` | `/api/v1/auth/update-phone` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\SocialAuthController@update_phone` |
| 55 | `POST` | `/api/v1/auth/social-customer-login` | `unnamed` | `App\Http\Controllers\RestAPI\v1\auth\SocialAuthController@customerSocialLogin` |
| 56 | `GET` | `/api/v1/shipping-method/detail/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ShippingMethodController@get_shipping_method_info` |
| 57 | `GET` | `/api/v1/shipping-method/by-seller/{id}/{seller_is}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ShippingMethodController@shipping_methods_by_seller` |
| 58 | `POST` | `/api/v1/shipping-method/choose-for-order` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ShippingMethodController@choose_for_order` |
| 59 | `GET` | `/api/v1/shipping-method/chosen` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ShippingMethodController@chosen_shipping_methods` |
| 60 | `GET` | `/api/v1/shipping-method/check-shipping-type` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ShippingMethodController@check_shipping_type` |
| 61 | `GET` | `/api/v1/cart` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CartController@getCartList` |
| 62 | `POST` | `/api/v1/cart/add` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CartController@addToCart` |
| 63 | `PUT` | `/api/v1/cart/update` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CartController@update_cart` |
| 64 | `DELETE` | `/api/v1/cart/remove` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CartController@remove_from_cart` |
| 65 | `DELETE` | `/api/v1/cart/remove-all` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CartController@remove_all_from_cart` |
| 66 | `POST` | `/api/v1/cart/select-cart-items` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CartController@updateCheckedCartItems` |
| 67 | `POST` | `/api/v1/cart/product-restock-request` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CartController@addProductRestockRequest` |
| 68 | `POST` | `/api/v1/cart/get-referral-discount-redeem` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CartController@getReferralDiscountRedeem` |
| 69 | `POST` | `/api/v1/cart/get-merge-guest-cart` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CartController@getMergeGuestCart` |
| 70 | `GET` | `/api/v1/customer/order/get-order-by-id` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@getOrderById` |
| 71 | `GET` | `/api/v1/notifications` | `unnamed` | `App\Http\Controllers\RestAPI\v1\NotificationController@list` |
| 72 | `GET` | `/api/v1/notifications/seen` | `unnamed` | `App\Http\Controllers\RestAPI\v1\NotificationController@notification_seen` |
| 73 | `GET` | `/api/v1/attributes` | `unnamed` | `App\Http\Controllers\RestAPI\v1\AttributeController@get_attributes` |
| 74 | `GET` | `/api/v1/flash-deals` | `unnamed` | `App\Http\Controllers\RestAPI\v1\FlashDealController@getFlashDeal` |
| 75 | `GET` | `/api/v1/flash-deals/products/{deal_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\FlashDealController@getFlashDealProducts` |
| 76 | `GET` | `/api/v1/deals/featured` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DealController@getFeaturedDealProducts` |
| 77 | `GET` | `/api/v1/dealsoftheday/deal-of-the-day` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DealOfTheDayController@getDealOfTheDayProduct` |
| 78 | `GET` | `/api/v1/products/reviews/{slug}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_product_reviews` |
| 79 | `GET` | `/api/v1/products/rating/{product_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_product_rating` |
| 80 | `GET` | `/api/v1/products/counter/{slug}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@counter` |
| 81 | `GET` | `/api/v1/products/shipping-methods` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_shipping_methods` |
| 82 | `GET` | `/api/v1/products/social-share-link/{product_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@socialShareLink` |
| 83 | `POST` | `/api/v1/products/reviews/submit` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@submit_product_review` |
| 84 | `PUT` | `/api/v1/products/review/update` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@updateProductReview` |
| 85 | `GET` | `/api/v1/products/review/{product_id}/{order_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getProductReviewByOrder` |
| 86 | `DELETE` | `/api/v1/products/review/delete-image` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@deleteReviewImage` |
| 87 | `GET` | `/api/v1/products/latest` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_latest_products` |
| 88 | `GET` | `/api/v1/products/new-arrival` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getNewArrivalProducts` |
| 89 | `GET` | `/api/v1/products/featured` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getFeaturedProductsList` |
| 90 | `GET` | `/api/v1/products/top-rated` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getTopRatedProducts` |
| 91 | `GET` | `/api/v1/products/search` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_searched_products` |
| 92 | `POST` | `/api/v1/products/filter` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getProductsFilter` |
| 93 | `GET` | `/api/v1/products/suggestion-product` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_suggestion_product` |
| 94 | `GET` | `/api/v1/products/details/{slug}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getProductDetails` |
| 95 | `GET` | `/api/v1/products/related-products/{slug}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_related_products` |
| 96 | `GET` | `/api/v1/products/best-sellings` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getBestSellingProducts` |
| 97 | `GET` | `/api/v1/products/home-categories` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_home_categories` |
| 98 | `GET` | `/api/v1/products/discounted-product` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_discounted_product` |
| 99 | `GET` | `/api/v1/products/most-demanded-product` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@get_most_demanded_product` |
| 100 | `GET` | `/api/v1/products/shop-again-product` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getShopAgainProduct` |
| 101 | `GET` | `/api/v1/products/just-for-you` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@just_for_you` |
| 102 | `GET` | `/api/v1/products/most-searching` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getMostSearchingProductsList` |
| 103 | `GET` | `/api/v1/products/digital-author-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getDigitalProductsAuthorList` |
| 104 | `GET` | `/api/v1/products/digital-publishing-house-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getDigitalPublishingHouseList` |
| 105 | `GET` | `/api/v1/products/clearance-sale` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@getClearanceSale` |
| 106 | `GET` | `/api/v1/products/feed/google-merchant.xml` | `unnamed` | `App\Http\Controllers\ProductFeedExportController@googleMerchantXml` |
| 107 | `GET` | `/api/v1/products/feed/facebook-catalog.csv` | `unnamed` | `App\Http\Controllers\ProductFeedExportController@facebookCatalogCsv` |
| 108 | `GET` | `/api/v1/products/feed/tiktok-catalog.csv` | `unnamed` | `App\Http\Controllers\ProductFeedExportController@tiktokCatalogCsv` |
| 109 | `PUT` | `/api/v1/customer/language-change` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@language_change` |
| 110 | `GET` | `/api/v1/seller/{slug}/products` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@getVendorProducts` |
| 111 | `GET` | `/api/v1/seller/{slug}/seller-best-selling-products` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@get_seller_best_selling_products` |
| 112 | `GET` | `/api/v1/seller/{slug}/seller-featured-product` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@get_sellers_featured_product` |
| 113 | `GET` | `/api/v1/seller/{slug}/seller-recommended-products` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@get_sellers_recommended_products` |
| 114 | `GET` | `/api/v1/categories` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CategoryController@get_categories` |
| 115 | `GET` | `/api/v1/categories/products/{category_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CategoryController@get_products` |
| 116 | `GET` | `/api/v1/categories/find-what-you-need` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CategoryController@find_what_you_need` |
| 117 | `GET` | `/api/v1/brands` | `unnamed` | `App\Http\Controllers\RestAPI\v1\BrandController@get_brands` |
| 118 | `GET` | `/api/v1/brands/products/{brand_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\BrandController@get_products` |
| 119 | `GET` | `/api/v1/delivery-hubs/states` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@getStates` |
| 120 | `GET` | `/api/v1/delivery-hubs/cities/{state_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@getCities` |
| 121 | `GET` | `/api/v1/delivery-hubs/hubs/{city_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@getHubs` |
| 122 | `POST` | `/api/v1/delivery-hubs/calculate-shipping` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@calculateHubShipping` |
| 123 | `PUT` | `/api/v1/customer/cm-firebase-token` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@update_cm_firebase_token` |
| 124 | `GET` | `/api/v1/customer/get-restricted-country-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_restricted_country_list` |
| 125 | `GET` | `/api/v1/customer/get-restricted-zip-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_restricted_zip_list` |
| 126 | `POST` | `/api/v1/customer/address/add` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@add_new_address` |
| 127 | `GET` | `/api/v1/customer/address/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@address_list` |
| 128 | `DELETE` | `/api/v1/customer/address` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@delete_address` |
| 129 | `POST` | `/api/v1/customer/address/update` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@update_address` |
| 130 | `GET` | `/api/v1/customer/order/place` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@place_order` |
| 131 | `GET` | `/api/v1/customer/order/offline-payment-method-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@offline_payment_method_list` |
| 132 | `POST` | `/api/v1/customer/order/place-by-offline-payment` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@placeOrderByOfflinePayment` |
| 133 | `GET` | `/api/v1/customer/order/details` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_order_details` |
| 134 | `GET` | `/api/v1/customer/order/generate-invoice` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@getOrderInvoice` |
| 135 | `GET` | `/api/v1/customer/order/deliveryman-review` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ReviewController@getReview` |
| 136 | `POST` | `/api/v1/customer/order/deliveryman-review/update` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ReviewController@updateDeliveryManReview` |
| 137 | `GET` | `/api/v1/customer/info` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@info` |
| 138 | `PUT` | `/api/v1/customer/update-profile` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@update_profile` |
| 139 | `GET` | `/api/v1/customer/account-delete/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@account_delete` |
| 140 | `GET` | `/api/v1/customer/address/get/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_address` |
| 141 | `POST` | `/api/v1/customer/support-ticket/create` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@create_support_ticket` |
| 142 | `GET` | `/api/v1/customer/support-ticket/get` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_support_tickets` |
| 143 | `GET` | `/api/v1/customer/support-ticket/conv/{ticket_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_support_ticket_conv` |
| 144 | `POST` | `/api/v1/customer/support-ticket/reply/{ticket_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@reply_support_ticket` |
| 145 | `GET` | `/api/v1/customer/support-ticket/close/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@support_ticket_close` |
| 146 | `GET` | `/api/v1/customer/compare/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CompareController@list` |
| 147 | `POST` | `/api/v1/customer/compare/product-store` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CompareController@compare_product_store` |
| 148 | `DELETE` | `/api/v1/customer/compare/clear-all` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CompareController@clear_all` |
| 149 | `GET` | `/api/v1/customer/compare/product-replace` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CompareController@compare_product_replace` |
| 150 | `GET` | `/api/v1/customer/wish-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@wish_list` |
| 151 | `POST` | `/api/v1/customer/wish-list/add` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@add_to_wishlist` |
| 152 | `DELETE` | `/api/v1/customer/wish-list/remove` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@remove_from_wishlist` |
| 153 | `GET` | `/api/v1/customer/restock-requests/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerRestockRequestController@restockRequestsList` |
| 154 | `POST` | `/api/v1/customer/restock-requests/delete` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerRestockRequestController@deleteRestockRequests` |
| 155 | `GET` | `/api/v1/customer/order/place-by-wallet` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@placeOrderByWallet` |
| 156 | `GET` | `/api/v1/customer/order/refund` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@refund_request` |
| 157 | `POST` | `/api/v1/customer/order/refund-store` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@store_refund` |
| 158 | `GET` | `/api/v1/customer/order/refund-details` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@refund_details` |
| 159 | `POST` | `/api/v1/customer/order/again` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@order_again` |
| 160 | `POST` | `/api/v1/customer/order/confirm-driver-transit-code` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@confirm_driver_transit_code` |
| 161 | `GET` | `/api/v1/customer/order/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CustomerController@get_order_list` |
| 162 | `POST` | `/api/v1/customer/order/deliveryman-reviews/submit` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ProductController@submit_deliveryman_review` |
| 163 | `GET` | `/api/v1/customer/chat/list/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ChatController@list` |
| 164 | `GET` | `/api/v1/customer/chat/get-messages/{type}/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ChatController@get_message` |
| 165 | `POST` | `/api/v1/customer/chat/send-message/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ChatController@send_message` |
| 166 | `POST` | `/api/v1/customer/chat/seen-message/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ChatController@seen_message` |
| 167 | `GET` | `/api/v1/customer/chat/search/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\ChatController@search` |
| 168 | `GET` | `/api/v1/customer/wallet/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\UserWalletController@list` |
| 169 | `GET` | `/api/v1/customer/wallet/bonus-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\UserWalletController@bonus_list` |
| 170 | `GET` | `/api/v1/customer/loyalty/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\UserLoyaltyController@list` |
| 171 | `POST` | `/api/v1/customer/loyalty/loyalty-exchange-currency` | `unnamed` | `App\Http\Controllers\RestAPI\v1\UserLoyaltyController@loyalty_exchange_currency` |
| 172 | `GET` | `/api/v1/customer/order/digital-product-download/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@digital_product_download` |
| 173 | `GET` | `/api/v1/customer/order/digital-product-download-otp-verify` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@digital_product_download_otp_verify` |
| 174 | `POST` | `/api/v1/customer/order/digital-product-download-otp-resend` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@digital_product_download_otp_resend` |
| 175 | `POST` | `/api/v1/digital-payment` | `unnamed` | `App\Http\Controllers\Customer\PaymentController@payment` |
| 176 | `POST` | `/api/v1/add-to-fund` | `unnamed` | `App\Http\Controllers\Customer\PaymentController@customer_add_to_fund_request` |
| 177 | `GET` | `/api/v1/order/track` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@track_by_order_id` |
| 178 | `GET` | `/api/v1/order/track-order-details` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@track_order_details_history` |
| 179 | `GET` | `/api/v1/order/cancel-order` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@order_cancel` |
| 180 | `POST` | `/api/v1/order/track-order` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderController@track_order` |
| 181 | `POST` | `/api/v1/edit-order/due-payment-by-offline-payment` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderEditController@duePaymentByOfflinePayment` |
| 182 | `POST` | `/api/v1/edit-order/due-payment-by-wallet` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderEditController@duePaymentByWallet` |
| 183 | `POST` | `/api/v1/edit-order/due-payment-by-cod` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderEditController@duePaymentByCod` |
| 184 | `POST` | `/api/v1/edit-order/due-payment-by-digital-payment` | `unnamed` | `App\Http\Controllers\RestAPI\v1\OrderEditController@duePaymentByDigitalPayment` |
| 185 | `GET` | `/api/v1/banners` | `unnamed` | `App\Http\Controllers\RestAPI\v1\BannerController@getBannerList` |
| 186 | `GET` | `/api/v1/seller` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@get_seller_info` |
| 187 | `GET` | `/api/v1/seller/list/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@getSellerList` |
| 188 | `GET` | `/api/v1/seller/more` | `unnamed` | `App\Http\Controllers\RestAPI\v1\SellerController@more_sellers` |
| 189 | `GET` | `/api/v1/coupon/apply` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CouponController@apply` |
| 190 | `GET` | `/api/v1/coupon/list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CouponController@list` |
| 191 | `GET` | `/api/v1/coupon/applicable-list` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CouponController@applicable_list` |
| 192 | `GET` | `/api/v1/coupons/{slug}/seller-wise-coupons` | `unnamed` | `App\Http\Controllers\RestAPI\v1\CouponController@getSellerWiseCoupon` |
| 193 | `GET` | `/api/v1/mapapi/place-api-autocomplete` | `unnamed` | `App\Http\Controllers\RestAPI\v1\MapApiController@placeApiAutocomplete` |
| 194 | `GET` | `/api/v1/mapapi/distance-api` | `unnamed` | `App\Http\Controllers\RestAPI\v1\MapApiController@distanceApi` |
| 195 | `GET` | `/api/v1/mapapi/place-api-details` | `unnamed` | `App\Http\Controllers\RestAPI\v1\MapApiController@placeApiDetails` |
| 196 | `GET` | `/api/v1/mapapi/geocode-api` | `unnamed` | `App\Http\Controllers\RestAPI\v1\MapApiController@geocode_api` |
| 197 | `GET` | `/api/v1/faq` | `unnamed` | `App\Http\Controllers\RestAPI\v1\GeneralController@faq` |
| 198 | `GET` | `/api/v1/get-guest-id` | `unnamed` | `App\Http\Controllers\RestAPI\v1\GeneralController@get_guest_id` |
| 199 | `POST` | `/api/v1/contact-us` | `unnamed` | `App\Http\Controllers\RestAPI\v1\GeneralController@contact_store` |
| 200 | `GET` | `/api/v2/seller/seller-info` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@seller_info` |
| 201 | `GET` | `/api/v2/seller/account-delete` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@account_delete` |
| 202 | `GET` | `/api/v2/seller/seller-delivery-man` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@seller_delivery_man` |
| 203 | `GET` | `/api/v2/seller/shop-product-reviews` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@shop_product_reviews` |
| 204 | `GET` | `/api/v2/seller/shop-product-reviews-status` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@shop_product_reviews_status` |
| 205 | `PUT` | `/api/v2/seller/seller-update` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@seller_info_update` |
| 206 | `GET` | `/api/v2/seller/monthly-earning` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@monthly_earning` |
| 207 | `GET` | `/api/v2/seller/monthly-commission-given` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@monthly_commission_given` |
| 208 | `PUT` | `/api/v2/seller/cm-firebase-token` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@update_cm_firebase_token` |
| 209 | `GET` | `/api/v2/seller/shop-info` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@shop_info` |
| 210 | `GET` | `/api/v2/seller/transactions` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@transaction` |
| 211 | `PUT` | `/api/v2/seller/shop-update` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@shop_info_update` |
| 212 | `POST` | `/api/v2/seller/balance-withdraw` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@withdraw_request` |
| 213 | `DELETE` | `/api/v2/seller/close-withdraw-request` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\SellerController@close_withdraw_request` |
| 214 | `GET` | `/api/v2/seller/brands` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\BrandController@getBrands` |
| 215 | `POST` | `/api/v2/seller/products/upload-images` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@upload_images` |
| 216 | `POST` | `/api/v2/seller/products/upload-digital-product` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@upload_digital_product` |
| 217 | `POST` | `/api/v2/seller/products/add` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@add_new` |
| 218 | `GET` | `/api/v2/seller/products/list` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@list` |
| 219 | `GET` | `/api/v2/seller/products/stock-out-list` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@stock_out_list` |
| 220 | `GET` | `/api/v2/seller/products/status-update` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@status_update` |
| 221 | `GET` | `/api/v2/seller/products/edit/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@edit` |
| 222 | `PUT` | `/api/v2/seller/products/update/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@update` |
| 223 | `DELETE` | `/api/v2/seller/products/delete/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@delete` |
| 224 | `GET` | `/api/v2/seller/products/barcode/generate` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ProductController@barcode_generate` |
| 225 | `GET` | `/api/v2/seller/orders/list` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\OrderController@list` |
| 226 | `GET` | `/api/v2/seller/orders/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\OrderController@details` |
| 227 | `PUT` | `/api/v2/seller/orders/order-detail-status/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\OrderController@order_detail_status` |
| 228 | `PUT` | `/api/v2/seller/orders/assign-delivery-man` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\OrderController@assign_delivery_man` |
| 229 | `PUT` | `/api/v2/seller/orders/order-wise-product-upload` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\OrderController@digital_file_upload_after_sell` |
| 230 | `PUT` | `/api/v2/seller/orders/delivery-charge-date-update` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\OrderController@amount_date_update` |
| 231 | `POST` | `/api/v2/seller/orders/assign-third-party-delivery` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\OrderController@assign_third_party_delivery` |
| 232 | `POST` | `/api/v2/seller/orders/update-payment-status` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\OrderController@update_payment_status` |
| 233 | `GET` | `/api/v2/seller/refund/list` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\RefundController@list` |
| 234 | `GET` | `/api/v2/seller/refund/refund-details` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\RefundController@refund_details` |
| 235 | `POST` | `/api/v2/seller/refund/refund-status-update` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\RefundController@refund_status_update` |
| 236 | `GET` | `/api/v2/seller/shipping/get-shipping-method` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\shippingController@get_shipping_type` |
| 237 | `GET` | `/api/v2/seller/shipping/selected-shipping-method` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\shippingController@selected_shipping_type` |
| 238 | `GET` | `/api/v2/seller/shipping/all-category-cost` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\shippingController@all_category_cost` |
| 239 | `POST` | `/api/v2/seller/shipping/set-category-cost` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\shippingController@set_category_cost` |
| 240 | `GET` | `/api/v2/seller/shipping-method/list` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ShippingMethodController@list` |
| 241 | `POST` | `/api/v2/seller/shipping-method/add` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ShippingMethodController@store` |
| 242 | `GET` | `/api/v2/seller/shipping-method/edit/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ShippingMethodController@edit` |
| 243 | `PUT` | `/api/v2/seller/shipping-method/status` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ShippingMethodController@status_update` |
| 244 | `PUT` | `/api/v2/seller/shipping-method/update/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ShippingMethodController@update` |
| 245 | `DELETE` | `/api/v2/seller/shipping-method/delete/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ShippingMethodController@delete` |
| 246 | `GET` | `/api/v2/seller/messages/list/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ChatController@list` |
| 247 | `GET` | `/api/v2/seller/messages/get-message/{type}/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ChatController@get_message` |
| 248 | `POST` | `/api/v2/seller/messages/send/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ChatController@send_message` |
| 249 | `GET` | `/api/v2/seller/messages/search/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\ChatController@search` |
| 250 | `POST` | `/api/v2/seller/auth/login` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\auth\LoginController@login` |
| 251 | `POST` | `/api/v2/seller/auth/forgot-password` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\auth\ForgotPasswordController@reset_password_request` |
| 252 | `POST` | `/api/v2/seller/auth/verify-otp` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\auth\ForgotPasswordController@otp_verification_submit` |
| 253 | `PUT` | `/api/v2/seller/auth/reset-password` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\auth\ForgotPasswordController@reset_password_submit` |
| 254 | `POST` | `/api/v2/seller/registration` | `unnamed` | `App\Http\Controllers\RestAPI\v2\seller\auth\RegisterController@store` |
| 255 | `GET` | `/search` | `unnamed` | `Closure` |
| 256 | `POST` | `/change-language` | `change-language` | `App\Http\Controllers\SharedController@changeLanguage` |
| 257 | `POST` | `/get-session-recaptcha-code` | `get-session-recaptcha-code` | `App\Http\Controllers\SharedController@getSessionRecaptchaCode` |
| 258 | `POST` | `/g-recaptcha-response-store` | `g-recaptcha-response-store` | `App\Http\Controllers\SharedController@storeRecaptchaResponse` |
| 259 | `GET` | `/g-recaptcha-session-store` | `g-recaptcha-session-store` | `App\Http\Controllers\SharedController@storeRecaptchaSession` |
| 260 | `GET` | `/activation-check` | `system.activation-check` | `App\Http\Controllers\SharedController@getActivationCheckView` |
| 261 | `POST` | `/activation-check` | `unnamed` | `App\Http\Controllers\SharedController@activationCheck` |
| 262 | `POST` | `/system/subscribe-to-topic` | `system.subscribeToTopic` | `App\Http\Controllers\FirebaseController@subscribeToTopic` |
| 263 | `GET` | `/login/{loginUrl}` | `unnamed` | `App\Http\Controllers\Admin\Auth\LoginController@index` |
| 264 | `GET` | `/login/recaptcha/{tmp}` | `recaptcha` | `App\Http\Controllers\Admin\Auth\LoginController@generateReCaptcha` |
| 265 | `POST` | `/login` | `login` | `App\Http\Controllers\Admin\Auth\LoginController@login` |
| 266 | `GET` | `/image-proxy` | `unnamed` | `Closure` |
| 267 | `GET` | `/maintenance-mode` | `maintenance-mode` | `App\Http\Controllers\Web\WebController@maintenance_mode` |
| 268 | `GET` | `/product-compare/index` | `product-compare.index` | `App\Http\Controllers\Web\ProductCompareController@index` |
| 269 | `POST` | `/product-compare/index` | `product-compare.` | `App\Http\Controllers\Web\ProductCompareController@add` |
| 270 | `GET` | `/product-compare/delete` | `product-compare.delete` | `App\Http\Controllers\Web\ProductCompareController@delete` |
| 271 | `GET` | `/product-compare/delete-all` | `product-compare.delete-all` | `App\Http\Controllers\Web\ProductCompareController@deleteAllCompareProduct` |
| 272 | `POST` | `/shop-follow` | `shop-follow` | `App\Http\Controllers\Web\Shop\ShopFollowerController@followOrUnfollowShop` |
| 273 | `GET` | `/` | `home` | `App\Http\Controllers\Web\HomeController@index` |
| 274 | `GET` | `/quick-view` | `quick-view` | `App\Http\Controllers\Web\WebController@getQuickView` |
| 275 | `GET` | `/searched-products` | `searched-products` | `App\Http\Controllers\Web\WebController@getSearchedProducts` |
| 276 | `POST` | `/review` | `review.store` | `App\Http\Controllers\Web\ReviewController@add` |
| 277 | `POST` | `/submit-deliveryman-review` | `submit-deliveryman-review` | `App\Http\Controllers\Web\ReviewController@addDeliveryManReview` |
| 278 | `POST` | `/review-delete-image` | `delete-review-image` | `App\Http\Controllers\Web\ReviewController@deleteReviewImage` |
| 279 | `GET` | `/checkout-details` | `checkout-details` | `App\Http\Controllers\Web\WebController@checkout_details` |
| 280 | `GET` | `/checkout-shipping` | `checkout-shipping` | `App\Http\Controllers\Web\WebController@checkout_details` |
| 281 | `GET` | `/checkout-payment` | `checkout-payment` | `App\Http\Controllers\Web\WebController@checkout_payment` |
| 282 | `GET` | `/checkout-review` | `checkout-review` | `App\Http\Controllers\Web\WebController@checkout_payment` |
| 283 | `GET` | `/checkout-complete` | `checkout-complete` | `App\Http\Controllers\Web\WebController@getCashOnDeliveryCheckoutComplete` |
| 284 | `POST` | `/offline-payment-checkout-complete` | `offline-payment-checkout-complete` | `App\Http\Controllers\Web\WebController@getOfflinePaymentCheckoutComplete` |
| 285 | `GET` | `/order-placed` | `order-placed` | `App\Http\Controllers\Web\WebController@order_placed` |
| 286 | `GET` | `/order-placed-success` | `order-placed-success` | `App\Http\Controllers\Web\WebController@getOrderPlaceView` |
| 287 | `GET` | `/shop-cart` | `shop-cart` | `App\Http\Controllers\Web\WebController@shop_cart` |
| 288 | `POST` | `/order_note` | `order_note` | `App\Http\Controllers\Web\WebController@order_note` |
| 289 | `GET` | `/digital-product-download/{id}` | `digital-product-download` | `App\Http\Controllers\Web\WebController@getDigitalProductDownload` |
| 290 | `POST` | `/digital-product-download-otp-verify` | `digital-product-download-otp-verify` | `App\Http\Controllers\Web\WebController@getDigitalProductDownloadOtpVerify` |
| 291 | `POST` | `/digital-product-download-otp-reset` | `digital-product-download-otp-reset` | `App\Http\Controllers\Web\WebController@getDigitalProductDownloadOtpReset` |
| 292 | `GET` | `/pay-offline-method-list` | `pay-offline-method-list` | `App\Http\Controllers\Web\WebController@pay_offline_method_list` |
| 293 | `GET` | `/checkout-complete-wallet` | `checkout-complete-wallet` | `App\Http\Controllers\Web\WebController@checkout_complete_wallet` |
| 294 | `POST` | `/subscription` | `subscription` | `App\Http\Controllers\Web\WebController@subscription` |
| 295 | `GET` | `/search-shop` | `search-shop` | `App\Http\Controllers\Web\WebController@search_shop` |
| 296 | `GET` | `/categories` | `categories` | `App\Http\Controllers\Web\WebController@getAllCategoriesView` |
| 297 | `GET` | `/category-ajax/{id}` | `category-ajax` | `App\Http\Controllers\Web\WebController@categories_by_category` |
| 298 | `GET` | `/brands` | `brands` | `App\Http\Controllers\Web\WebController@getAllBrandsView` |
| 299 | `GET` | `/seller-profile/{id}` | `seller-profile` | `App\Http\Controllers\Web\WebController@seller_profile` |
| 300 | `GET` | `/business-page/{slug}` | `business-page.view` | `App\Http\Controllers\Web\PageController@getPageView` |
| 301 | `GET` | `/contacts` | `contacts` | `App\Http\Controllers\Web\PageController@getContactView` |
| 302 | `GET` | `/helpTopic` | `helpTopic` | `App\Http\Controllers\Web\PageController@getHelpTopicView` |
| 303 | `GET` | `/product/{slug}` | `product` | `App\Http\Controllers\Web\ProductDetailsController@index` |
| 304 | `GET` | `/products` | `products` | `App\Http\Controllers\Web\ProductListController@products` |
| 305 | `GET` | `/flash-deals/{id}` | `flash-deals` | `App\Http\Controllers\Web\ProductListController@getFlashDealsView` |
| 306 | `POST` | `/flash-deals/{id}` | `unnamed` | `App\Http\Controllers\Web\ProductListController@getFlashDealsProducts` |
| 307 | `GET` | `/brand/{slug}` | `brand-products` | `App\Http\Controllers\Web\ProductListController@getBrandProductsView` |
| 308 | `GET` | `/category/{slug}` | `category-products` | `App\Http\Controllers\Web\ProductListController@getCategoryProductsView` |
| 309 | `GET` | `/featured-products` | `featured-products` | `App\Http\Controllers\Web\ProductListController@getFeaturedProductsView` |
| 310 | `GET` | `/featured-deal-products` | `featured-deal-products` | `App\Http\Controllers\Web\ProductListController@getFeaturedDealProductsView` |
| 311 | `GET` | `/latest-products` | `latest-products` | `App\Http\Controllers\Web\ProductListController@getLatestProductsView` |
| 312 | `GET` | `/best-selling-products` | `best-selling-products` | `App\Http\Controllers\Web\ProductListController@getBestSellingProductsView` |
| 313 | `GET` | `/top-rated-products` | `top-rated-products` | `App\Http\Controllers\Web\ProductListController@getTopRatedProductsView` |
| 314 | `GET` | `/most-favorite-products` | `most-favorite-products` | `App\Http\Controllers\Web\ProductListController@getMostFavoriteProductsView` |
| 315 | `GET` | `/discounted-products` | `discounted-products` | `App\Http\Controllers\Web\ProductListController@getDiscountedProductsView` |
| 316 | `GET` | `/clearance-sale-products` | `clearance-sale-products` | `App\Http\Controllers\Web\ProductListController@getClearanceSaleProductsView` |
| 317 | `POST` | `/ajax-filter-products` | `ajax-filter-products` | `App\Http\Controllers\Web\ShopViewController@filterProductsAjaxResponse` |
| 318 | `POST` | `/products-view-style` | `product_view_style` | `App\Http\Controllers\Web\WebController@product_view_style` |
| 319 | `POST` | `/review-list-product` | `review-list-product` | `App\Http\Controllers\Web\WebController@review_list_product` |
| 320 | `POST` | `/review-list-shop` | `review-list-shop` | `App\Http\Controllers\Web\WebController@getShopReviewList` |
| 321 | `GET` | `/wishlists` | `wishlists` | `App\Http\Controllers\Web\WebController@viewWishlist` |
| 322 | `POST` | `/store-wishlist` | `store-wishlist` | `App\Http\Controllers\Web\WebController@storeWishlist` |
| 323 | `POST` | `/delete-wishlist` | `delete-wishlist` | `App\Http\Controllers\Web\WebController@deleteWishlist` |
| 324 | `GET` | `/delete-wishlist-all` | `delete-wishlist-all` | `App\Http\Controllers\Web\WebController@deleteAllWishListItems` |
| 325 | `GET` | `/searched-products-for-compare` | `searched-products-compare` | `App\Http\Controllers\Web\WebController@getSearchedProductsForCompareList` |
| 326 | `POST` | `/currency` | `currency.change` | `App\Http\Controllers\Web\CurrencyController@changeCurrency` |
| 327 | `GET` | `/support-ticket/{id}` | `support-ticket.index` | `App\Http\Controllers\Web\UserProfileController@single_ticket` |
| 328 | `POST` | `/support-ticket/{id}` | `support-ticket.comment` | `App\Http\Controllers\Web\UserProfileController@comment_submit` |
| 329 | `GET` | `/support-ticket/delete/{id}` | `support-ticket.delete` | `App\Http\Controllers\Web\UserProfileController@support_ticket_delete` |
| 330 | `GET` | `/support-ticket/close/{id}` | `support-ticket.close` | `App\Http\Controllers\Web\UserProfileController@support_ticket_close` |
| 331 | `GET` | `/track-order` | `track-order.index` | `App\Http\Controllers\Web\UserProfileController@track_order` |
| 332 | `GET` | `/track-order/result-view` | `track-order.result-view` | `App\Http\Controllers\Web\UserProfileController@track_order_result` |
| 333 | `GET` | `/track-order/last` | `track-order.last` | `App\Http\Controllers\Web\UserProfileController@track_last_order` |
| 334 | `GET` | `/track-order/result` | `track-order.result` | `App\Http\Controllers\Web\UserProfileController@track_order_result` |
| 335 | `GET` | `/track-order/order-wise-result-view` | `track-order.order-wise-result-view` | `App\Http\Controllers\Web\UserProfileController@track_order_wise_result` |
| 336 | `GET` | `/user-profile` | `user-profile` | `App\Http\Controllers\Web\UserProfileController@user_profile` |
| 337 | `GET` | `/user-account` | `user-account` | `App\Http\Controllers\Web\UserProfileController@user_account` |
| 338 | `POST` | `/user-account-update` | `user-update` | `App\Http\Controllers\Web\UserProfileController@getUserProfileUpdate` |
| 339 | `POST` | `/user-account-picture` | `user-picture` | `App\Http\Controllers\Web\UserProfileController@getUserProfileUpdate` |
| 340 | `GET` | `/account-address-add` | `account-address-add` | `App\Http\Controllers\Web\UserProfileController@account_address_add` |
| 341 | `GET` | `/account-address` | `account-address` | `App\Http\Controllers\Web\UserProfileController@account_address` |
| 342 | `POST` | `/account-address-store` | `address-store` | `App\Http\Controllers\Web\UserProfileController@address_store` |
| 343 | `GET` | `/account-address-delete` | `address-delete` | `App\Http\Controllers\Web\UserProfileController@address_delete` |
| 344 | `GET` | `/account-address-edit/{id}` | `address-edit` | `App\Http\Controllers\Web\UserProfileController@address_edit` |
| 345 | `POST` | `/account-address-update` | `address-update` | `App\Http\Controllers\Web\UserProfileController@address_update` |
| 346 | `GET` | `/account-payment` | `account-payment` | `App\Http\Controllers\Web\UserProfileController@account_payment` |
| 347 | `GET` | `/account-oder` | `account-oder` | `App\Http\Controllers\Web\UserProfileController@account_order` |
| 348 | `GET` | `/account-order-details` | `account-order-details` | `App\Http\Controllers\Web\UserProfileController@account_order_details` |
| 349 | `GET` | `/account-order-details-vendor-info` | `account-order-details-vendor-info` | `App\Http\Controllers\Web\UserProfileController@account_order_details_seller_info` |
| 350 | `GET` | `/account-order-details-delivery-man-info` | `account-order-details-delivery-man-info` | `App\Http\Controllers\Web\UserProfileController@account_order_details_delivery_man_info` |
| 351 | `GET` | `/account-order-details-reviews` | `account-order-details-reviews` | `App\Http\Controllers\Web\UserProfileController@getAccountOrderDetailsReviewsView` |
| 352 | `GET` | `/generate-invoice/{id}` | `generate-invoice` | `App\Http\Controllers\Web\UserProfileController@generate_invoice` |
| 353 | `GET` | `/account-wishlist` | `account-wishlist` | `App\Http\Controllers\Web\UserProfileController@account_wishlist` |
| 354 | `GET` | `/refund-request/{id}` | `refund-request` | `App\Http\Controllers\Web\UserProfileController@refund_request` |
| 355 | `GET` | `/refund-details/{id}` | `refund-details` | `App\Http\Controllers\Web\UserProfileController@refund_details` |
| 356 | `POST` | `/refund-store` | `refund-store` | `App\Http\Controllers\Web\UserProfileController@store_refund` |
| 357 | `GET` | `/account-tickets` | `account-tickets` | `App\Http\Controllers\Web\UserProfileController@account_tickets` |
| 358 | `GET` | `/order-cancel/{id}` | `order-cancel` | `App\Http\Controllers\Web\UserProfileController@order_cancel` |
| 359 | `POST` | `/ticket-submit` | `ticket-submit` | `App\Http\Controllers\Web\UserProfileController@submitSupportTicket` |
| 360 | `GET` | `/account-delete/{id}` | `account-delete` | `App\Http\Controllers\Web\UserProfileController@account_delete` |
| 361 | `GET` | `/refer-earn` | `refer-earn` | `App\Http\Controllers\Web\UserProfileController@refer_earn` |
| 362 | `GET` | `/user-coupons` | `user-coupons` | `App\Http\Controllers\Web\UserProfileController@user_coupons` |
| 363 | `GET` | `/user-restock-requests` | `user-restock-requests` | `App\Http\Controllers\Web\UserProfileController@restockRequestsView` |
| 364 | `GET` | `/user-restock-request-delete` | `user-restock-request-delete` | `App\Http\Controllers\Web\UserProfileController@deleteRestockRequest` |
| 365 | `GET` | `/user-all-restock-request-delete/{ids}` | `user-all-restock-request-delete` | `App\Http\Controllers\Web\UserProfileController@deleteRestockRequest` |
| 366 | `GET` | `/chat/{type}` | `chat` | `App\Http\Controllers\Web\ChattingController@index` |
| 367 | `GET` | `/message` | `messages` | `App\Http\Controllers\Web\ChattingController@getMessageByUser` |
| 368 | `POST` | `/message` | `unnamed` | `App\Http\Controllers\Web\ChattingController@addMessage` |
| 369 | `GET` | `/wallet-account` | `wallet-account` | `App\Http\Controllers\Web\UserWalletController@myWalletAccount` |
| 370 | `GET` | `/wallet` | `wallet` | `App\Http\Controllers\Web\UserWalletController@index` |
| 371 | `GET` | `/loyalty` | `loyalty` | `App\Http\Controllers\Web\UserLoyaltyController@index` |
| 372 | `POST` | `/loyalty-exchange-currency` | `loyalty-exchange-currency` | `App\Http\Controllers\Web\UserLoyaltyController@getLoyaltyExchangeCurrency` |
| 373 | `GET` | `/ajax-loyalty-currency-amount` | `ajax-loyalty-currency-amount` | `App\Http\Controllers\Web\UserLoyaltyController@getLoyaltyCurrencyAmount` |
| 374 | `GET` | `/digital-product-download-pos` | `digital-product-download-pos.index` | `App\Http\Controllers\Web\DigitalProductDownloadController@index` |
| 375 | `GET` | `/ajax-shop-vacation-check` | `ajax-shop-vacation-check` | `App\Http\Controllers\Web\ShopViewController@ajax_shop_vacation_check` |
| 376 | `GET` | `/top-rated` | `topRated` | `App\Http\Controllers\Web\ProductListController@getTopRatedProductsView` |
| 377 | `GET` | `/best-sell` | `bestSell` | `App\Http\Controllers\Web\ProductListController@getBestSellingProductsView` |
| 378 | `GET` | `/new-product` | `newProduct` | `App\Http\Controllers\Web\ProductListController@getLatestProductsView` |
| 379 | `POST` | `/contact/store` | `contact.store` | `App\Http\Controllers\Web\WebController@contact_store` |
| 380 | `GET` | `/contact/code/captcha/{tmp}` | `contact.default-captcha` | `App\Http\Controllers\Web\WebController@captcha` |
| 381 | `POST` | `/cart/variant_price` | `cart.variant_price` | `App\Http\Controllers\Web\CartController@getVariantPrice` |
| 382 | `POST` | `/cart/add` | `cart.add` | `App\Http\Controllers\Web\CartController@addToCart` |
| 383 | `POST` | `/cart/add-all-to-cart` | `cart.add-all-to-cart` | `App\Http\Controllers\Web\CartController@addAllToCartFromWishtList` |
| 384 | `POST` | `/cart/update-variation` | `cart.update-variation` | `App\Http\Controllers\Web\CartController@update_variation` |
| 385 | `POST` | `/cart/remove` | `cart.remove` | `App\Http\Controllers\Web\CartController@removeFromCart` |
| 386 | `GET` | `/cart/remove-all` | `cart.remove-all` | `App\Http\Controllers\Web\CartController@remove_all_cart` |
| 387 | `POST` | `/cart/nav-cart-items` | `cart.nav-cart` | `App\Http\Controllers\Web\CartController@updateNavCart` |
| 388 | `POST` | `/cart/floating-nav-cart-items` | `cart.floating-nav-cart-items` | `App\Http\Controllers\Web\CartController@update_floating_nav` |
| 389 | `POST` | `/cart/updateQuantity` | `cart.updateQuantity` | `App\Http\Controllers\Web\CartController@updateQuantity` |
| 390 | `POST` | `/cart/updateQuantity-guest` | `cart.updateQuantity.guest` | `App\Http\Controllers\Web\CartController@updateQuantity_guest` |
| 391 | `POST` | `/cart/order-again` | `cart.order-again` | `App\Http\Controllers\Web\CartController@orderAgain` |
| 392 | `POST` | `/cart/select-cart-items` | `cart.select-cart-items` | `App\Http\Controllers\Web\CartController@updateCheckedCartItems` |
| 393 | `POST` | `/cart/product-restock-request` | `cart.product-restock-request` | `App\Http\Controllers\Web\CartController@addProductRestockRequest` |
| 394 | `POST` | `/coupon/apply` | `coupon.apply` | `App\Http\Controllers\Web\CouponController@apply` |
| 395 | `GET` | `/coupon/remove` | `coupon.remove` | `App\Http\Controllers\Web\CouponController@removeCoupon` |
| 396 | `GET` | `/authentication-failed` | `authentication-failed` | `Closure` |
| 397 | `GET` | `/customer/auth/login` | `customer.auth.login` | `App\Http\Controllers\Customer\Auth\CustomerAuthController@loginView` |
| 398 | `POST` | `/customer/auth/login` | `customer.auth.` | `App\Http\Controllers\Customer\Auth\CustomerAuthController@loginSubmit` |
| 399 | `GET` | `/customer/auth/login/verify-account` | `customer.auth.login.verify-account` | `App\Http\Controllers\Customer\Auth\CustomerAuthController@loginVerifyPhone` |
| 400 | `POST` | `/customer/auth/login/verify-account/submit` | `customer.auth.login.verify-account.submit` | `App\Http\Controllers\Customer\Auth\CustomerAuthController@verifyAccount` |
| 401 | `GET` | `/customer/auth/login/update-info` | `customer.auth.login.update-info` | `App\Http\Controllers\Customer\Auth\CustomerAuthController@updateInfo` |
| 402 | `POST` | `/customer/auth/login/update-info` | `customer.auth.` | `App\Http\Controllers\Customer\Auth\CustomerAuthController@updateInfoSubmit` |
| 403 | `POST` | `/customer/auth/login/resend-otp-code` | `customer.auth.resend-otp-code` | `App\Http\Controllers\Customer\Auth\CustomerAuthController@resendOTPCode` |
| 404 | `GET` | `/customer/auth/logout` | `customer.auth.logout` | `App\Http\Controllers\Customer\Auth\LoginController@logout` |
| 405 | `GET` | `/customer/auth/get-login-modal-data` | `customer.auth.get-login-modal-data` | `App\Http\Controllers\Customer\Auth\LoginController@getLoginModalView` |
| 406 | `GET` | `/customer/auth/sign-up` | `customer.auth.sign-up` | `App\Http\Controllers\Customer\Auth\RegisterController@getRegisterView` |
| 407 | `POST` | `/customer/auth/sign-up` | `customer.auth.` | `App\Http\Controllers\Customer\Auth\RegisterController@submitRegisterData` |
| 408 | `GET` | `/customer/auth/check-verification` | `customer.auth.check-verification` | `App\Http\Controllers\Customer\Auth\RegisterController@verificationCheckView` |
| 409 | `POST` | `/customer/auth/verify` | `customer.auth.verify` | `App\Http\Controllers\Customer\Auth\RegisterController@verifyRegistration` |
| 410 | `POST` | `/customer/auth/ajax-verify` | `customer.auth.ajax_verify` | `App\Http\Controllers\Customer\Auth\RegisterController@ajax_verify` |
| 411 | `POST` | `/customer/auth/resend-otp` | `customer.auth.resend_otp` | `App\Http\Controllers\Customer\Auth\RegisterController@resendOTPToCustomer` |
| 412 | `GET` | `/customer/auth/login/{service}` | `customer.auth.service-login` | `App\Http\Controllers\Customer\Auth\SocialAuthController@redirectToProvider` |
| 413 | `GET` | `/customer/auth/login/{service}/callback` | `customer.auth.service-callback` | `App\Http\Controllers\Customer\Auth\SocialAuthController@handleProviderCallback` |
| 414 | `GET` | `/customer/auth/login/social/confirmation` | `customer.auth.social-login-confirmation` | `App\Http\Controllers\Customer\Auth\SocialAuthController@socialLoginConfirmation` |
| 415 | `POST` | `/customer/auth/login/social/confirmation/update` | `customer.auth.social-login-confirmation.update` | `App\Http\Controllers\Customer\Auth\SocialAuthController@updateSocialLoginConfirmation` |
| 416 | `POST` | `/customer/auth/login/social/verify-account` | `customer.auth.login.social.verify-account` | `App\Http\Controllers\Customer\Auth\SocialAuthController@verifyAccount` |
| 417 | `GET` | `/customer/auth/recover-password` | `customer.auth.recover-password` | `App\Http\Controllers\Customer\Auth\ForgotPasswordController@reset_password` |
| 418 | `POST` | `/customer/auth/forgot-password` | `customer.auth.forgot-password` | `App\Http\Controllers\Customer\Auth\ForgotPasswordController@resetPasswordRequest` |
| 419 | `POST` | `/customer/auth/verify-recover-password` | `customer.auth.verify-recover-password` | `App\Http\Controllers\Customer\Auth\ForgotPasswordController@verifyRecoverPassword` |
| 420 | `GET` | `/customer/auth/otp-verification` | `customer.auth.otp-verification` | `App\Http\Controllers\Customer\Auth\ForgotPasswordController@otp_verification` |
| 421 | `POST` | `/customer/auth/otp-verification` | `customer.auth.` | `App\Http\Controllers\Customer\Auth\ForgotPasswordController@otp_verification_submit` |
| 422 | `GET` | `/customer/auth/reset-password` | `customer.auth.reset-password` | `App\Http\Controllers\Customer\Auth\ForgotPasswordController@resetPasswordView` |
| 423 | `POST` | `/customer/auth/reset-password` | `customer.auth.password-recovery` | `App\Http\Controllers\Customer\Auth\ForgotPasswordController@resetPasswordSubmit` |
| 424 | `POST` | `/customer/auth/resend-otp-reset-password` | `customer.auth.resend-otp-reset-password` | `App\Http\Controllers\Customer\Auth\ForgotPasswordController@resendPhoneOTPRequest` |
| 425 | `GET` | `/customer/set-payment-method/{name}` | `customer.set-payment-method` | `App\Http\Controllers\Customer\SystemController@setPaymentMethod` |
| 426 | `GET` | `/customer/set-shipping-method` | `customer.set-shipping-method` | `App\Http\Controllers\Customer\SystemController@setShippingMethod` |
| 427 | `POST` | `/customer/choose-shipping-address` | `customer.choose-shipping-address` | `App\Http\Controllers\Customer\SystemController@getChooseShippingAddress` |
| 428 | `POST` | `/customer/choose-shipping-address-other` | `customer.choose-shipping-address-other` | `App\Http\Controllers\Customer\SystemController@getChooseShippingAddressOther` |
| 429 | `POST` | `/customer/choose-billing-address` | `customer.choose-billing-address` | `App\Http\Controllers\Customer\SystemController@getChooseShippingAddress` |
| 430 | `GET` | `/customer/reward-points/convert` | `customer.reward-points.convert` | `App\Http\Controllers\Customer\RewardPointController@convert` |
| 431 | `POST` | `/customer/web-payment-request` | `customer.web-payment-request` | `App\Http\Controllers\Customer\PaymentController@payment` |
| 432 | `POST` | `/customer/customer-add-fund-request` | `customer.add-fund-request` | `App\Http\Controllers\Customer\PaymentController@customer_add_to_fund_request` |
| 433 | `POST` | `/customer/customer-order-edit-pay-amount` | `customer.customer-order-edit-pay-amount` | `App\Http\Controllers\Customer\PaymentController@customerOrderEditPayDueAmount` |
| 434 | `GET` | `/web-payment` | `web-payment-success` | `App\Http\Controllers\Customer\PaymentController@web_payment_success` |
| 435 | `GET` | `/payment-success` | `payment-success` | `App\Http\Controllers\Customer\PaymentController@success` |
| 436 | `GET` | `/payment-fail` | `payment-fail` | `App\Http\Controllers\Customer\PaymentController@fail` |
| 437 | `GET` | `/payment/paystack/pay` | `paystack.pay` | `App\Http\Controllers\Payment_Methods\PaystackController@index` |
| 438 | `GET` | `/payment/paystack/callback` | `paystack.callback` | `App\Http\Controllers\Payment_Methods\PaystackController@handleGatewayCallback` |
| 439 | `GET` | `/payment/paystack/cancel` | `paystack.cancel` | `App\Http\Controllers\Payment_Methods\PaystackController@cancel` |
| 440 | `POST` | `/payment/paystack/webhook` | `paystack.webhook` | `App\Http\Controllers\Payment_Methods\PaystackController@webhook` |
| 441 | `GET` | `/payment/paystack-delivery/callback` | `paystack-delivery.callback` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@paystack_delivery_callback` |
| 442 | `GET` | `/payment/paystack-remittance/callback` | `paystack-remittance.callback` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@paystack_remittance_callback` |
| 443 | `GET` | `/ai` | `ai.index` | `Modules\AI\app\Http\Controllers\AIController@index` |
| 444 | `GET` | `/ai/create` | `ai.create` | `Modules\AI\app\Http\Controllers\AIController@create` |
| 445 | `POST` | `/ai` | `ai.store` | `Modules\AI\app\Http\Controllers\AIController@store` |
| 446 | `GET` | `/ai/{ai}` | `ai.show` | `Modules\AI\app\Http\Controllers\AIController@show` |
| 447 | `GET` | `/ai/{ai}/edit` | `ai.edit` | `Modules\AI\app\Http\Controllers\AIController@edit` |
| 448 | `PUT` | `/ai/{ai}` | `ai.update` | `Modules\AI\app\Http\Controllers\AIController@update` |
| 449 | `DELETE` | `/ai/{ai}` | `ai.destroy` | `Modules\AI\app\Http\Controllers\AIController@destroy` |
| 450 | `GET` | `/blog` | `frontend.blog.index` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@index` |
| 451 | `GET` | `/popular-blog` | `frontend.blog.popular-blog` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@getPopularBlogs` |
| 452 | `GET` | `/blog/{slug}` | `frontend.blog.details` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@getDetailsView` |
| 453 | `GET` | `/app/blog` | `app.blog.index` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@index` |
| 454 | `GET` | `/app/popular-blog` | `app.blog.popular-blog` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@getPopularBlogs` |
| 455 | `GET` | `/app/blog/{slug}` | `app.blog.details` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@getDetailsView` |
| 456 | `GET` | `/api/v1/taxmodule` | `api.taxmodule` | `Closure` |
| 457 | `GET` | `/api/v1/vat-tax/get-taxVat-list` | `v1.vat-tax.` | `Modules\TaxModule\app\Http\Controllers\Api\V1\TaxController@getTaxVatList` |
| 458 | `POST` | `/api/v1/vat-tax/get-calculated-tax` | `v1.vat-tax.` | `Modules\TaxModule\app\Http\Controllers\Api\V1\TaxController@getCalculateTax` |

---

## 3. Disallowed & Gated Endpoints Summary (1125 Endpoints Blocked)

Attempting to access any of the 1125 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

### Sample Gated Endpoints for this Role:

| # | Method | Gated URI | Guard Interceptor | Reason for Gating |
|:---:|:---:|---|---|---|
| 1 | `POST` | `/api/v2/delivery-man/auth/login` | `auth / rbac` | Strictly isolated outside role boundary |
| 2 | `POST` | `/api/v2/delivery-man/auth/forgot-password` | `auth / rbac` | Strictly isolated outside role boundary |
| 3 | `POST` | `/api/v2/delivery-man/auth/verify-otp` | `auth / rbac` | Strictly isolated outside role boundary |
| 4 | `POST` | `/api/v2/delivery-man/auth/reset-password` | `auth / rbac` | Strictly isolated outside role boundary |
| 5 | `PUT` | `/api/v2/delivery-man/language-change` | `auth / rbac` | Strictly isolated outside role boundary |
| 6 | `PUT` | `/api/v2/delivery-man/is-online` | `auth / rbac` | Strictly isolated outside role boundary |
| 7 | `GET` | `/api/v2/delivery-man/info` | `auth / rbac` | Strictly isolated outside role boundary |
| 8 | `POST` | `/api/v2/delivery-man/distance-api` | `auth / rbac` | Strictly isolated outside role boundary |
| 9 | `GET` | `/api/v2/delivery-man/current-orders` | `auth / rbac` | Strictly isolated outside role boundary |
| 10 | `GET` | `/api/v2/delivery-man/all-orders` | `auth / rbac` | Strictly isolated outside role boundary |
| 11 | `POST` | `/api/v2/delivery-man/record-location-data` | `auth / rbac` | Strictly isolated outside role boundary |
| 12 | `GET` | `/api/v2/delivery-man/order-delivery-history` | `auth / rbac` | Strictly isolated outside role boundary |
| 13 | `PUT` | `/api/v2/delivery-man/update-order-status` | `auth / rbac` | Strictly isolated outside role boundary |
| 14 | `PUT` | `/api/v2/delivery-man/update-expected-delivery` | `auth / rbac` | Strictly isolated outside role boundary |
| 15 | `PUT` | `/api/v2/delivery-man/update-payment-status` | `auth / rbac` | Strictly isolated outside role boundary |
| 16 | `PUT` | `/api/v2/delivery-man/order-update-is-pause` | `auth / rbac` | Strictly isolated outside role boundary |
| 17 | `GET` | `/api/v2/delivery-man/order-item` | `auth / rbac` | Strictly isolated outside role boundary |
| 18 | `GET` | `/api/v2/delivery-man/order-details` | `auth / rbac` | Strictly isolated outside role boundary |
| 19 | `GET` | `/api/v2/delivery-man/last-location` | `auth / rbac` | Strictly isolated outside role boundary |
| 20 | `PUT` | `/api/v2/delivery-man/update-fcm-token` | `auth / rbac` | Strictly isolated outside role boundary |
| 21 | `GET` | `/api/v2/delivery-man/delivery-wise-earned` | `auth / rbac` | Strictly isolated outside role boundary |
| 22 | `GET` | `/api/v2/delivery-man/order-list-by-date` | `auth / rbac` | Strictly isolated outside role boundary |
| 23 | `GET` | `/api/v2/delivery-man/search` | `auth / rbac` | Strictly isolated outside role boundary |
| 24 | `GET` | `/api/v2/delivery-man/profile-dashboard-counts` | `auth / rbac` | Strictly isolated outside role boundary |
| 25 | `PUT` | `/api/v2/delivery-man/update-info` | `auth / rbac` | Strictly isolated outside role boundary |


