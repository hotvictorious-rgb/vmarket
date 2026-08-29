# 🌐 Victorious MARKET: Master Ecosystem Endpoint & Security Taxonomy Catalogue

**Authoritative Master Registry of ALL 1552 HTTP Endpoints Across the Victorious MARKET Ecosystem**

---

## 📜 MANDATORY AI GOVERNANCE RULES FOR ENDPOINTS & APIS

> ### 🛡️ RULE 14: Mandatory Endpoint Pre-Analysis & Synchronized Catalogue Maintenance
> 1. Before creating, modifying, or refactoring ANY route or controller method, every AI **MUST** first read and analyze this document (`ALL_ECOSYSTEM_ENDPOINTS_AND_SECURITY_TAXONOMY.md`).
> 2. Whenever a new route is introduced or modified in Blade, Web routes, or API routes (`routes/web.php`, `routes/admin/`, `routes/vendor/`, `routes/rest_api/`, or `Modules/Pos/routes/`), the AI **MUST append and document** the endpoint in this file under the appropriate domain table with its exact HTTP methods, URI, controller action, guard, and security middleware.

> ### 🔒 RULE 15: Universal 5-Pillar Security Standard for Every Endpoint
> Every endpoint in this ecosystem (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD) must strictly enforce all 5 security pillars without exception:
> 1. **Zero-Trust Authentication:** Explicit guard definition (`auth:admin`, `auth:seller`, `auth:customer`, `auth:api`, `auth:delivery_man`). Unauthenticated fallback must safely redirect or return 401/403 (never 500).
> 2. **Tenant Scoping & Micro-Isolation (Zero Cross-Tenant Bleed):** All database queries within the handler MUST enforce `where('seller_id', $authSellerId)`, `where('shop_id', $authShopId)`, `where('customer_id', $authCustomerId)`, or `where('delivery_man_id', $authRiderId)`. Route IDs (`$request->id`) must NEVER be trusted alone for ownership.
> 3. **Anti-Mass-Assignment & Input Validation:** Handlers must never pass `$request->all()` into model mutations; only validated data via `$request->only(...)` or dedicated FormRequest data mappers.
> 4. **Pessimistic Balance & Row Locks:** Any read-modify-write on wallet balances, debt ledgers, order payments, or cash drawers must execute inside `DB::transaction()` with `lockForUpdate()`.
> 5. **Audit Logging & Loophole Closure:** State mutations, order status transitions, refunds, and financial updates must emit structured audit entries and prevent concurrent double-execution via atomic row updates (`where('is_paid', 0)->update(...)`).

---

## 📊 Ecosystem Summary

| Platform Application | Domain Category | Total Registered Endpoints |
| :--- | :--- | :---: |
| **Vmarket Marketplace** | Public Storefront | **190** |
| **Vmarket Marketplace** | Customer REST API (v1) | **177** |
| **Vmarket Marketplace** | Delivery Rider REST API (v2) | **100** |
| **Vmarket Marketplace** | Vendor Mobile REST API (v3) | **162** |
| **Vmarket Marketplace** | Super Admin Panel (Cleaned) | **638** |
| **Vmarket Marketplace** | Merchant / Vendor Panel | **205** |
| **Vmarket Marketplace** | Customer Web Portal | **37** |
| **Vmarket Marketplace** | Native POS Module | **42** |
| **Vmarket Marketplace** | Delivery & Logistics Hub Module | **21** |
| **ECOSYSTEM TOTAL** | **ALL DOMAINS COMBINED** | **1,572 ENDPOINTS** |

---

### 🏷️ Domain: Public Storefront (190 Endpoints)

| # | Method(s) | URI / Route | Route Name | Controller Action | Security Middleware |
| :-: | :--- | :--- | :--- | :--- | :--- |
| 1 | `GET` | `/_debugbar/open` | `debugbar.openhandler` | `Barryvdh\Debugbar\Controllers\OpenHandlerController@handle` | Barryvdh\Debugbar\Middleware\DebugbarEnabled |
| 2 | `GET` | `/_debugbar/clockwork/{id}` | `debugbar.clockwork` | `Barryvdh\Debugbar\Controllers\OpenHandlerController@clockwork` | Barryvdh\Debugbar\Middleware\DebugbarEnabled |
| 3 | `GET` | `/_debugbar/assets/stylesheets` | `debugbar.assets.css` | `Barryvdh\Debugbar\Controllers\AssetController@css` | Barryvdh\Debugbar\Middleware\DebugbarEnabled |
| 4 | `GET` | `/_debugbar/assets/javascript` | `debugbar.assets.js` | `Barryvdh\Debugbar\Controllers\AssetController@js` | Barryvdh\Debugbar\Middleware\DebugbarEnabled |
| 5 | `DELETE` | `/_debugbar/cache/{key}/{tags?}` | `debugbar.cache.delete` | `Barryvdh\Debugbar\Controllers\CacheController@delete` | Barryvdh\Debugbar\Middleware\DebugbarEnabled |
| 6 | `POST` | `/_debugbar/queries/explain` | `debugbar.queries.explain` | `Barryvdh\Debugbar\Controllers\QueriesController@explain` | Barryvdh\Debugbar\Middleware\DebugbarEnabled |
| 7 | `POST` | `/oauth/token` | `passport.token` | `Laravel\Passport\Http\Controllers\AccessTokenController@issueToken` | throttle |
| 8 | `GET` | `/oauth/authorize` | `passport.authorizations.authorize` | `Laravel\Passport\Http\Controllers\AuthorizationController@authorize` | web |
| 9 | `POST` | `/oauth/token/refresh` | `passport.token.refresh` | `Laravel\Passport\Http\Controllers\TransientTokenController@refresh` | web, auth:web |
| 10 | `POST` | `/oauth/authorize` | `passport.authorizations.approve` | `Laravel\Passport\Http\Controllers\ApproveAuthorizationController@approve` | web, auth:web |
| 11 | `DELETE` | `/oauth/authorize` | `passport.authorizations.deny` | `Laravel\Passport\Http\Controllers\DenyAuthorizationController@deny` | web, auth:web |
| 12 | `GET` | `/oauth/tokens` | `passport.tokens.index` | `Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController@forUser` | web, auth:web |
| 13 | `DELETE` | `/oauth/tokens/{token_id}` | `passport.tokens.destroy` | `Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController@destroy` | web, auth:web |
| 14 | `GET` | `/oauth/clients` | `passport.clients.index` | `Laravel\Passport\Http\Controllers\ClientController@forUser` | web, auth:web |
| 15 | `POST` | `/oauth/clients` | `passport.clients.store` | `Laravel\Passport\Http\Controllers\ClientController@store` | web, auth:web |
| 16 | `PUT` | `/oauth/clients/{client_id}` | `passport.clients.update` | `Laravel\Passport\Http\Controllers\ClientController@update` | web, auth:web |
| 17 | `DELETE` | `/oauth/clients/{client_id}` | `passport.clients.destroy` | `Laravel\Passport\Http\Controllers\ClientController@destroy` | web, auth:web |
| 18 | `GET` | `/oauth/scopes` | `passport.scopes.index` | `Laravel\Passport\Http\Controllers\ScopeController@all` | web, auth:web |
| 19 | `GET` | `/oauth/personal-access-tokens` | `passport.personal.tokens.index` | `Laravel\Passport\Http\Controllers\PersonalAccessTokenController@forUser` | web, auth:web |
| 20 | `POST` | `/oauth/personal-access-tokens` | `passport.personal.tokens.store` | `Laravel\Passport\Http\Controllers\PersonalAccessTokenController@store` | web, auth:web |
| 21 | `DELETE` | `/oauth/personal-access-tokens/{token_id}` | `passport.personal.tokens.destroy` | `Laravel\Passport\Http\Controllers\PersonalAccessTokenController@destroy` | web, auth:web |
| 22 | `GET` | `/sanctum/csrf-cookie` | `sanctum.csrf-cookie` | `Laravel\Sanctum\Http\Controllers\CsrfCookieController@show` | web |
| 23 | `GET` | `/_ignition/health-check` | `ignition.healthCheck` | `Spatie\LaravelIgnition\Http\Controllers\HealthCheckController` | Spatie\LaravelIgnition\Http\Middleware\RunnableSolutionsEnabled |
| 24 | `POST` | `/_ignition/execute-solution` | `ignition.executeSolution` | `Spatie\LaravelIgnition\Http\Controllers\ExecuteSolutionController` | Spatie\LaravelIgnition\Http\Middleware\RunnableSolutionsEnabled |
| 25 | `POST` | `/_ignition/update-config` | `ignition.updateConfig` | `Spatie\LaravelIgnition\Http\Controllers\UpdateConfigController` | Spatie\LaravelIgnition\Http\Middleware\RunnableSolutionsEnabled |
| 26 | `GET` | `/search` | `—` | `Closure` | web |
| 27 | `POST` | `/change-language` | `change-language` | `SharedController@changeLanguage` | web |
| 28 | `POST` | `/get-session-recaptcha-code` | `get-session-recaptcha-code` | `SharedController@getSessionRecaptchaCode` | web |
| 29 | `POST` | `/g-recaptcha-response-store` | `g-recaptcha-response-store` | `SharedController@storeRecaptchaResponse` | web |
| 30 | `GET` | `/g-recaptcha-session-store` | `g-recaptcha-session-store` | `SharedController@storeRecaptchaSession` | web |
| 31 | `GET` | `/activation-check` | `system.activation-check` | `SharedController@getActivationCheckView` | web |
| 32 | `POST` | `/activation-check` | `—` | `SharedController@activationCheck` | web |
| 33 | `POST` | `/system/subscribe-to-topic` | `system.subscribeToTopic` | `FirebaseController@subscribeToTopic` | web |
| 34 | `GET` | `/login/{loginUrl}` | `—` | `Admin\Auth\LoginController@index` | web |
| 35 | `GET` | `/login/recaptcha/{tmp}` | `recaptcha` | `Admin\Auth\LoginController@generateReCaptcha` | web |
| 36 | `POST` | `/login` | `login` | `Admin\Auth\LoginController@login` | web, throttle:10,1 |
| 37 | `GET` | `/image-proxy` | `—` | `Closure` | web, logUserBrowsingNavigation, throttle:60,1 |
| 38 | `GET` | `/maintenance-mode` | `maintenance-mode` | `Web\WebController@maintenance_mode` | web, logUserBrowsingNavigation |
| 39 | `GET` | `/product-compare/index` | `product-compare.index` | `Web\ProductCompareController@index` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 40 | `POST` | `/product-compare/index` | `product-compare.` | `Web\ProductCompareController@add` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 41 | `GET` | `/product-compare/delete` | `product-compare.delete` | `Web\ProductCompareController@delete` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 42 | `GET` | `/product-compare/delete-all` | `product-compare.delete-all` | `Web\ProductCompareController@deleteAllCompareProduct` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 43 | `POST` | `/shop-follow` | `shop-follow` | `Web\Shop\ShopFollowerController@followOrUnfollowShop` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 44 | `GET` | `/` | `home` | `Web\HomeController@index` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 45 | `GET` | `/quick-view` | `quick-view` | `Web\WebController@getQuickView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 46 | `GET` | `/searched-products` | `searched-products` | `Web\WebController@getSearchedProducts` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 47 | `POST` | `/review` | `review.store` | `Web\ReviewController@add` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 48 | `POST` | `/submit-deliveryman-review` | `submit-deliveryman-review` | `Web\ReviewController@addDeliveryManReview` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 49 | `POST` | `/review-delete-image` | `delete-review-image` | `Web\ReviewController@deleteReviewImage` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 50 | `GET` | `/checkout-details` | `checkout-details` | `Web\WebController@checkout_details` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 51 | `GET` | `/checkout-shipping` | `checkout-shipping` | `Web\WebController@checkout_details` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 52 | `GET` | `/checkout-payment` | `checkout-payment` | `Web\WebController@checkout_payment` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 53 | `GET` | `/checkout-review` | `checkout-review` | `Web\WebController@checkout_payment` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 54 | `GET` | `/checkout-complete` | `checkout-complete` | `Web\WebController@getCashOnDeliveryCheckoutComplete` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 55 | `POST` | `/offline-payment-checkout-complete` | `offline-payment-checkout-complete` | `Web\WebController@getOfflinePaymentCheckoutComplete` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 56 | `GET` | `/order-placed` | `order-placed` | `Web\WebController@order_placed` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 57 | `GET` | `/order-placed-success` | `order-placed-success` | `Web\WebController@getOrderPlaceView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 58 | `GET` | `/shop-cart` | `shop-cart` | `Web\WebController@shop_cart` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 59 | `POST` | `/order_note` | `order_note` | `Web\WebController@order_note` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 60 | `GET` | `/digital-product-download/{id}` | `digital-product-download` | `Web\WebController@getDigitalProductDownload` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 61 | `POST` | `/digital-product-download-otp-verify` | `digital-product-download-otp-verify` | `Web\WebController@getDigitalProductDownloadOtpVerify` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, throttle:5,1 |
| 62 | `POST` | `/digital-product-download-otp-reset` | `digital-product-download-otp-reset` | `Web\WebController@getDigitalProductDownloadOtpReset` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, throttle:5,1 |
| 63 | `GET` | `/pay-offline-method-list` | `pay-offline-method-list` | `Web\WebController@pay_offline_method_list` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, guestCheck |
| 64 | `GET` | `/checkout-complete-wallet` | `checkout-complete-wallet` | `Web\WebController@checkout_complete_wallet` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 65 | `POST` | `/subscription` | `subscription` | `Web\WebController@subscription` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 66 | `GET` | `/search-shop` | `search-shop` | `Web\WebController@search_shop` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 67 | `GET` | `/categories` | `categories` | `Web\WebController@getAllCategoriesView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 68 | `GET` | `/category-ajax/{id}` | `category-ajax` | `Web\WebController@categories_by_category` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 69 | `GET` | `/brands` | `brands` | `Web\WebController@getAllBrandsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 70 | `GET` | `/seller-profile/{id}` | `seller-profile` | `Web\WebController@seller_profile` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 71 | `GET` | `/business-page/{slug}` | `business-page.view` | `Web\PageController@getPageView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 72 | `GET` | `/contacts` | `contacts` | `Web\PageController@getContactView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 73 | `GET` | `/helpTopic` | `helpTopic` | `Web\PageController@getHelpTopicView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 74 | `GET` | `/product/{slug}` | `product` | `Web\ProductDetailsController@index` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 75 | `GET` | `/products` | `products` | `Web\ProductListController@products` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 76 | `GET` | `/flash-deals/{id}` | `flash-deals` | `Web\ProductListController@getFlashDealsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 77 | `POST` | `/flash-deals/{id}` | `—` | `Web\ProductListController@getFlashDealsProducts` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 78 | `GET` | `/brand/{slug}` | `brand-products` | `Web\ProductListController@getBrandProductsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 79 | `GET` | `/category/{slug}` | `category-products` | `Web\ProductListController@getCategoryProductsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 80 | `GET` | `/featured-products` | `featured-products` | `Web\ProductListController@getFeaturedProductsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 81 | `GET` | `/featured-deal-products` | `featured-deal-products` | `Web\ProductListController@getFeaturedDealProductsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 82 | `GET` | `/latest-products` | `latest-products` | `Web\ProductListController@getLatestProductsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 83 | `GET` | `/best-selling-products` | `best-selling-products` | `Web\ProductListController@getBestSellingProductsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 84 | `GET` | `/top-rated-products` | `top-rated-products` | `Web\ProductListController@getTopRatedProductsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 85 | `GET` | `/most-favorite-products` | `most-favorite-products` | `Web\ProductListController@getMostFavoriteProductsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 86 | `GET` | `/discounted-products` | `discounted-products` | `Web\ProductListController@getDiscountedProductsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 87 | `GET` | `/clearance-sale-products` | `clearance-sale-products` | `Web\ProductListController@getClearanceSaleProductsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 88 | `POST` | `/ajax-filter-products` | `ajax-filter-products` | `Web\ShopViewController@filterProductsAjaxResponse` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 89 | `POST` | `/products-view-style` | `product_view_style` | `Web\WebController@product_view_style` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 90 | `POST` | `/review-list-product` | `review-list-product` | `Web\WebController@review_list_product` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 91 | `POST` | `/review-list-shop` | `review-list-shop` | `Web\WebController@getShopReviewList` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 92 | `GET` | `/wishlists` | `wishlists` | `Web\WebController@viewWishlist` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 93 | `POST` | `/store-wishlist` | `store-wishlist` | `Web\WebController@storeWishlist` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 94 | `POST` | `/delete-wishlist` | `delete-wishlist` | `Web\WebController@deleteWishlist` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 95 | `GET` | `/delete-wishlist-all` | `delete-wishlist-all` | `Web\WebController@deleteAllWishListItems` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 96 | `GET` | `/searched-products-for-compare` | `searched-products-compare` | `Web\WebController@getSearchedProductsForCompareList` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 97 | `POST` | `/currency` | `currency.change` | `Web\CurrencyController@changeCurrency` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 98 | `GET` | `/support-ticket/{id}` | `support-ticket.index` | `Web\UserProfileController@single_ticket` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 99 | `POST` | `/support-ticket/{id}` | `support-ticket.comment` | `Web\UserProfileController@comment_submit` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 100 | `GET` | `/support-ticket/delete/{id}` | `support-ticket.delete` | `Web\UserProfileController@support_ticket_delete` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 101 | `GET` | `/support-ticket/close/{id}` | `support-ticket.close` | `Web\UserProfileController@support_ticket_close` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 102 | `GET` | `/track-order` | `track-order.index` | `Web\UserProfileController@track_order` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 103 | `GET` | `/track-order/result-view` | `track-order.result-view` | `Web\UserProfileController@track_order_result` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 104 | `GET` | `/track-order/last` | `track-order.last` | `Web\UserProfileController@track_last_order` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 105 | `GET|POST|PUT|PATCH|DELETE|OPTIONS` | `/track-order/result` | `track-order.result` | `Web\UserProfileController@track_order_result` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 106 | `GET` | `/track-order/order-wise-result-view` | `track-order.order-wise-result-view` | `Web\UserProfileController@track_order_wise_result` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 107 | `GET` | `/user-profile` | `user-profile` | `Web\UserProfileController@user_profile` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 108 | `GET` | `/user-account` | `user-account` | `Web\UserProfileController@user_account` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 109 | `POST` | `/user-account-update` | `user-update` | `Web\UserProfileController@getUserProfileUpdate` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 110 | `POST` | `/user-account-picture` | `user-picture` | `Web\UserProfileController@getUserProfileUpdate` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 111 | `GET` | `/account-address-add` | `account-address-add` | `Web\UserProfileController@account_address_add` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 112 | `GET` | `/account-address` | `account-address` | `Web\UserProfileController@account_address` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 113 | `POST` | `/account-address-store` | `address-store` | `Web\UserProfileController@address_store` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 114 | `GET` | `/account-address-delete` | `address-delete` | `Web\UserProfileController@address_delete` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 115 | `GET` | `/account-address-edit/{id}` | `address-edit` | `Web\UserProfileController@address_edit` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 116 | `POST` | `/account-address-update` | `address-update` | `Web\UserProfileController@address_update` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 117 | `GET` | `/account-payment` | `account-payment` | `Web\UserProfileController@account_payment` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 118 | `GET` | `/account-oder` | `account-oder` | `Web\UserProfileController@account_order` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 119 | `GET` | `/account-order-details` | `account-order-details` | `Web\UserProfileController@account_order_details` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 120 | `GET` | `/account-order-details-vendor-info` | `account-order-details-vendor-info` | `Web\UserProfileController@account_order_details_seller_info` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 121 | `GET` | `/account-order-details-delivery-man-info` | `account-order-details-delivery-man-info` | `Web\UserProfileController@account_order_details_delivery_man_info` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 122 | `GET` | `/account-order-details-reviews` | `account-order-details-reviews` | `Web\UserProfileController@getAccountOrderDetailsReviewsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 123 | `GET` | `/generate-invoice/{id}` | `generate-invoice` | `Web\UserProfileController@generate_invoice` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 124 | `GET` | `/account-wishlist` | `account-wishlist` | `Web\UserProfileController@account_wishlist` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 125 | `GET` | `/refund-request/{id}` | `refund-request` | `Web\UserProfileController@refund_request` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 126 | `GET` | `/refund-details/{id}` | `refund-details` | `Web\UserProfileController@refund_details` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 127 | `POST` | `/refund-store` | `refund-store` | `Web\UserProfileController@store_refund` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 128 | `GET` | `/account-tickets` | `account-tickets` | `Web\UserProfileController@account_tickets` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 129 | `GET` | `/order-cancel/{id}` | `order-cancel` | `Web\UserProfileController@order_cancel` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 130 | `POST` | `/ticket-submit` | `ticket-submit` | `Web\UserProfileController@submitSupportTicket` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 131 | `GET` | `/account-delete/{id}` | `account-delete` | `Web\UserProfileController@account_delete` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 132 | `GET` | `/refer-earn` | `refer-earn` | `Web\UserProfileController@refer_earn` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 133 | `GET` | `/user-coupons` | `user-coupons` | `Web\UserProfileController@user_coupons` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 134 | `GET` | `/user-restock-requests` | `user-restock-requests` | `Web\UserProfileController@restockRequestsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 135 | `GET` | `/user-restock-request-delete` | `user-restock-request-delete` | `Web\UserProfileController@deleteRestockRequest` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 136 | `GET` | `/user-all-restock-request-delete/{ids}` | `user-all-restock-request-delete` | `Web\UserProfileController@deleteRestockRequest` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 137 | `GET` | `/chat/{type}` | `chat` | `Web\ChattingController@index` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 138 | `GET` | `/message` | `messages` | `Web\ChattingController@getMessageByUser` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 139 | `POST` | `/message` | `—` | `Web\ChattingController@addMessage` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 140 | `GET` | `/wallet-account` | `wallet-account` | `Web\UserWalletController@myWalletAccount` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 141 | `GET` | `/wallet` | `wallet` | `Web\UserWalletController@index` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 142 | `GET` | `/loyalty` | `loyalty` | `Web\UserLoyaltyController@index` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck, customer |
| 143 | `POST` | `/loyalty-exchange-currency` | `loyalty-exchange-currency` | `Web\UserLoyaltyController@getLoyaltyExchangeCurrency` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 144 | `GET` | `/ajax-loyalty-currency-amount` | `ajax-loyalty-currency-amount` | `Web\UserLoyaltyController@getLoyaltyCurrencyAmount` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 145 | `GET` | `/digital-product-download-pos` | `digital-product-download-pos.index` | `Web\DigitalProductDownloadController@index` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 146 | `GET` | `/ajax-shop-vacation-check` | `ajax-shop-vacation-check` | `Web\ShopViewController@ajax_shop_vacation_check` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 147 | `GET` | `/top-rated` | `topRated` | `Web\ProductListController@getTopRatedProductsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 148 | `GET` | `/best-sell` | `bestSell` | `Web\ProductListController@getBestSellingProductsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 149 | `GET` | `/new-product` | `newProduct` | `Web\ProductListController@getLatestProductsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 150 | `POST` | `/contact/store` | `contact.store` | `Web\WebController@contact_store` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 151 | `GET` | `/contact/code/captcha/{tmp}` | `contact.default-captcha` | `Web\WebController@captcha` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 152 | `POST` | `/cart/variant_price` | `cart.variant_price` | `Web\CartController@getVariantPrice` | web, logUserBrowsingNavigation |
| 153 | `POST` | `/cart/add` | `cart.add` | `Web\CartController@addToCart` | web, logUserBrowsingNavigation |
| 154 | `POST` | `/cart/add-all-to-cart` | `cart.add-all-to-cart` | `Web\CartController@addAllToCartFromWishtList` | web, logUserBrowsingNavigation |
| 155 | `POST` | `/cart/update-variation` | `cart.update-variation` | `Web\CartController@update_variation` | web, logUserBrowsingNavigation |
| 156 | `POST` | `/cart/remove` | `cart.remove` | `Web\CartController@removeFromCart` | web, logUserBrowsingNavigation |
| 157 | `GET` | `/cart/remove-all` | `cart.remove-all` | `Web\CartController@remove_all_cart` | web, logUserBrowsingNavigation |
| 158 | `POST` | `/cart/nav-cart-items` | `cart.nav-cart` | `Web\CartController@updateNavCart` | web, logUserBrowsingNavigation |
| 159 | `POST` | `/cart/floating-nav-cart-items` | `cart.floating-nav-cart-items` | `Web\CartController@update_floating_nav` | web, logUserBrowsingNavigation |
| 160 | `POST` | `/cart/updateQuantity` | `cart.updateQuantity` | `Web\CartController@updateQuantity` | web, logUserBrowsingNavigation |
| 161 | `POST` | `/cart/updateQuantity-guest` | `cart.updateQuantity.guest` | `Web\CartController@updateQuantity_guest` | web, logUserBrowsingNavigation |
| 162 | `POST` | `/cart/order-again` | `cart.order-again` | `Web\CartController@orderAgain` | web, logUserBrowsingNavigation, customer |
| 163 | `POST` | `/cart/select-cart-items` | `cart.select-cart-items` | `Web\CartController@updateCheckedCartItems` | web, logUserBrowsingNavigation |
| 164 | `POST` | `/cart/product-restock-request` | `cart.product-restock-request` | `Web\CartController@addProductRestockRequest` | web, logUserBrowsingNavigation |
| 165 | `POST` | `/coupon/apply` | `coupon.apply` | `Web\CouponController@apply` | web, logUserBrowsingNavigation |
| 166 | `GET` | `/coupon/remove` | `coupon.remove` | `Web\CouponController@removeCoupon` | web, logUserBrowsingNavigation |
| 167 | `GET` | `/authentication-failed` | `authentication-failed` | `Closure` | web, logUserBrowsingNavigation |
| 168 | `GET` | `/web-payment` | `web-payment-success` | `Customer\PaymentController@web_payment_success` | web, logUserBrowsingNavigation |
| 169 | `GET` | `/payment-success` | `payment-success` | `Customer\PaymentController@success` | web, logUserBrowsingNavigation |
| 170 | `GET` | `/payment-fail` | `payment-fail` | `Customer\PaymentController@fail` | web, logUserBrowsingNavigation |
| 171 | `GET` | `/payment/paystack/pay` | `paystack.pay` | `Payment_Methods\PaystackController@index` | web, logUserBrowsingNavigation |
| 172 | `GET` | `/payment/paystack/callback` | `paystack.callback` | `Payment_Methods\PaystackController@handleGatewayCallback` | web, logUserBrowsingNavigation |
| 173 | `GET` | `/payment/paystack/cancel` | `paystack.cancel` | `Payment_Methods\PaystackController@cancel` | web, logUserBrowsingNavigation |
| 174 | `POST` | `/payment/paystack/webhook` | `paystack.webhook` | `Payment_Methods\PaystackController@webhook` | web, logUserBrowsingNavigation |
| 175 | `GET` | `/payment/paystack-delivery/callback` | `paystack-delivery.callback` | `RestAPI\v2\delivery_man\DeliveryManController@paystack_delivery_callback` | web, logUserBrowsingNavigation |
| 176 | `GET` | `/payment/paystack-remittance/callback` | `paystack-remittance.callback` | `RestAPI\v2\delivery_man\DeliveryManController@paystack_remittance_callback` | web, logUserBrowsingNavigation |
| 177 | `GET` | `/sso-return` | `sso.return` | `Closure` | web, logUserBrowsingNavigation |
| 178 | `GET` | `/ai` | `ai.index` | `Modules\AI\app\Http\Controllers\AIController@index` | web |
| 179 | `GET` | `/ai/create` | `ai.create` | `Modules\AI\app\Http\Controllers\AIController@create` | web |
| 180 | `POST` | `/ai` | `ai.store` | `Modules\AI\app\Http\Controllers\AIController@store` | web |
| 181 | `GET` | `/ai/{ai}` | `ai.show` | `Modules\AI\app\Http\Controllers\AIController@show` | web |
| 182 | `GET` | `/ai/{ai}/edit` | `ai.edit` | `Modules\AI\app\Http\Controllers\AIController@edit` | web |
| 183 | `PUT|PATCH` | `/ai/{ai}` | `ai.update` | `Modules\AI\app\Http\Controllers\AIController@update` | web |
| 184 | `DELETE` | `/ai/{ai}` | `ai.destroy` | `Modules\AI\app\Http\Controllers\AIController@destroy` | web |
| 185 | `GET` | `/blog` | `frontend.blog.index` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@index` | web, Modules\Blog\app\Http\Middleware\BlogActiveStatusMiddleware |
| 186 | `GET` | `/popular-blog` | `frontend.blog.popular-blog` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@getPopularBlogs` | web, Modules\Blog\app\Http\Middleware\BlogActiveStatusMiddleware |
| 187 | `GET` | `/blog/{slug}` | `frontend.blog.details` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@getDetailsView` | web, Modules\Blog\app\Http\Middleware\BlogActiveStatusMiddleware |
| 188 | `GET` | `/app/blog` | `app.blog.index` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@index` | web, Modules\Blog\app\Http\Middleware\BlogActiveStatusMiddleware |
| 189 | `GET` | `/app/popular-blog` | `app.blog.popular-blog` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@getPopularBlogs` | web, Modules\Blog\app\Http\Middleware\BlogActiveStatusMiddleware |
| 190 | `GET` | `/app/blog/{slug}` | `app.blog.details` | `Modules\Blog\app\Http\Controllers\Web\FrontendBlogController@getDetailsView` | web, Modules\Blog\app\Http\Middleware\BlogActiveStatusMiddleware |

---

### 🏷️ Domain: Customer REST API (v1) (177 Endpoints)

| # | Method(s) | URI / Route | Route Name | Controller Action | Security Middleware |
| :-: | :--- | :--- | :--- | :--- | :--- |
| 191 | `GET` | `/api/v1/pos/products` | `—` | `RestAPI\v1\PosSyncApiController@getProducts` | api, api_lang |
| 192 | `POST` | `/api/v1/pos/sync-stock` | `—` | `RestAPI\v1\PosSyncApiController@syncStock` | api, api_lang |
| 193 | `POST` | `/api/v1/pos/order-dispatch/{orderId}` | `—` | `RestAPI\v1\PosSyncApiController@confirmDispatch` | api, api_lang |
| 194 | `GET` | `/api/v1/webhooks/whatsapp` | `—` | `RestAPI\v1\WhatsAppWebhookController@verify` | api, api_lang |
| 195 | `POST` | `/api/v1/webhooks/whatsapp` | `—` | `RestAPI\v1\WhatsAppWebhookController@handle` | api, api_lang |
| 196 | `GET` | `/api/v1/feed/sync` | `—` | `RestAPI\v1\FeedSyncController@getInitialFeed` | api, api_lang |
| 197 | `GET` | `/api/v1/config` | `—` | `RestAPI\v1\ConfigController@configuration` | api, api_lang |
| 198 | `GET` | `/api/v1/business-pages` | `—` | `RestAPI\v1\ConfigController@getBusinessPagesList` | api, api_lang |
| 199 | `GET` | `/api/v1/auth/logout` | `—` | `RestAPI\v1\auth\PassportAuthController@logout` | api, api_lang, auth:api |
| 200 | `POST` | `/api/v1/auth/register` | `—` | `RestAPI\v1\auth\CustomerAPIAuthController@register` | api, api_lang, throttle:10,1 |
| 201 | `POST` | `/api/v1/auth/login` | `—` | `RestAPI\v1\auth\CustomerAPIAuthController@login` | api, api_lang, throttle:10,1 |
| 202 | `POST` | `/api/v1/auth/check-email` | `—` | `RestAPI\v1\auth\CustomerAPIAuthController@checkEmail` | api, api_lang, throttle:10,1 |
| 203 | `POST` | `/api/v1/auth/check-phone` | `—` | `RestAPI\v1\auth\CustomerAPIAuthController@checkPhone` | api, api_lang, throttle:10,1 |
| 204 | `POST` | `/api/v1/auth/firebase-auth-verify` | `—` | `RestAPI\v1\auth\CustomerAPIAuthController@firebaseAuthVerify` | api, api_lang, throttle:10,1 |
| 205 | `POST` | `/api/v1/auth/firebase-auth-token-store` | `—` | `RestAPI\v1\auth\CustomerAPIAuthController@firebaseAuthTokenStore` | api, api_lang, throttle:10,1 |
| 206 | `POST` | `/api/v1/auth/verify-otp` | `—` | `RestAPI\v1\auth\CustomerAPIAuthController@verifyOTP` | api, api_lang, throttle:10,1 |
| 207 | `POST` | `/api/v1/auth/verify-email` | `—` | `RestAPI\v1\auth\CustomerAPIAuthController@verifyEmail` | api, api_lang, throttle:10,1 |
| 208 | `POST` | `/api/v1/auth/verify-phone` | `—` | `RestAPI\v1\auth\CustomerAPIAuthController@verifyPhone` | api, api_lang, throttle:10,1 |
| 209 | `POST` | `/api/v1/auth/registration-with-otp` | `—` | `RestAPI\v1\auth\CustomerAPIAuthController@registrationWithOTP` | api, api_lang, throttle:10,1 |
| 210 | `POST` | `/api/v1/auth/existing-account-check` | `—` | `RestAPI\v1\auth\SocialAuthController@existingAccountCheck` | api, api_lang |
| 211 | `POST` | `/api/v1/auth/registration-with-social-media` | `—` | `RestAPI\v1\auth\SocialAuthController@registrationWithSocialMedia` | api, api_lang |
| 212 | `POST` | `/api/v1/auth/forgot-password` | `—` | `RestAPI\v1\auth\CustomerAPIAuthController@passwordResetRequest` | api, api_lang, throttle:5,1 |
| 213 | `POST` | `/api/v1/auth/verify-profile-info` | `—` | `RestAPI\v1\auth\CustomerAPIAuthController@verifyProfileInfo` | api, api_lang, apiGuestCheck |
| 214 | `POST` | `/api/v1/auth/resend-otp-check-phone` | `—` | `RestAPI\v1\auth\PhoneVerificationController@resend_otp_check_phone` | api, api_lang |
| 215 | `POST` | `/api/v1/auth/resend-otp-check-email` | `—` | `RestAPI\v1\auth\EmailVerificationController@resend_otp_check_email` | api, api_lang |
| 216 | `POST` | `/api/v1/auth/verify-token` | `—` | `RestAPI\v1\auth\ForgotPasswordController@tokenVerificationSubmit` | api, api_lang |
| 217 | `PUT` | `/api/v1/auth/reset-password` | `—` | `RestAPI\v1\auth\ForgotPasswordController@reset_password_submit` | api, api_lang |
| 218 | `POST` | `/api/v1/auth/social-login` | `—` | `RestAPI\v1\auth\SocialAuthController@social_login` | api, api_lang |
| 219 | `POST` | `/api/v1/auth/update-phone` | `—` | `RestAPI\v1\auth\SocialAuthController@update_phone` | api, api_lang |
| 220 | `POST` | `/api/v1/auth/social-customer-login` | `—` | `RestAPI\v1\auth\SocialAuthController@customerSocialLogin` | api, api_lang |
| 221 | `GET` | `/api/v1/shipping-method/detail/{id}` | `—` | `RestAPI\v1\ShippingMethodController@get_shipping_method_info` | api, api_lang, apiGuestCheck |
| 222 | `GET` | `/api/v1/shipping-method/by-seller/{id}/{seller_is}` | `—` | `RestAPI\v1\ShippingMethodController@shipping_methods_by_seller` | api, api_lang, apiGuestCheck |
| 223 | `POST` | `/api/v1/shipping-method/choose-for-order` | `—` | `RestAPI\v1\ShippingMethodController@choose_for_order` | api, api_lang, apiGuestCheck |
| 224 | `GET` | `/api/v1/shipping-method/chosen` | `—` | `RestAPI\v1\ShippingMethodController@chosen_shipping_methods` | api, api_lang, apiGuestCheck |
| 225 | `GET` | `/api/v1/shipping-method/check-shipping-type` | `—` | `RestAPI\v1\ShippingMethodController@check_shipping_type` | api, api_lang, apiGuestCheck |
| 226 | `GET` | `/api/v1/cart` | `—` | `RestAPI\v1\CartController@getCartList` | api, api_lang, apiGuestCheck |
| 227 | `POST` | `/api/v1/cart/add` | `—` | `RestAPI\v1\CartController@addToCart` | api, api_lang, apiGuestCheck |
| 228 | `PUT` | `/api/v1/cart/update` | `—` | `RestAPI\v1\CartController@update_cart` | api, api_lang, apiGuestCheck |
| 229 | `DELETE` | `/api/v1/cart/remove` | `—` | `RestAPI\v1\CartController@remove_from_cart` | api, api_lang, apiGuestCheck |
| 230 | `DELETE` | `/api/v1/cart/remove-all` | `—` | `RestAPI\v1\CartController@remove_all_from_cart` | api, api_lang, apiGuestCheck |
| 231 | `POST` | `/api/v1/cart/select-cart-items` | `—` | `RestAPI\v1\CartController@updateCheckedCartItems` | api, api_lang, apiGuestCheck |
| 232 | `POST` | `/api/v1/cart/product-restock-request` | `—` | `RestAPI\v1\CartController@addProductRestockRequest` | api, api_lang, apiGuestCheck |
| 233 | `POST` | `/api/v1/cart/get-referral-discount-redeem` | `—` | `RestAPI\v1\CartController@getReferralDiscountRedeem` | api, api_lang, apiGuestCheck |
| 234 | `POST` | `/api/v1/cart/get-merge-guest-cart` | `—` | `RestAPI\v1\CartController@getMergeGuestCart` | api, api_lang, apiGuestCheck |
| 235 | `GET` | `/api/v1/customer/order/get-order-by-id` | `—` | `RestAPI\v1\CustomerController@getOrderById` | api, api_lang, apiGuestCheck |
| 236 | `GET` | `/api/v1/notifications` | `—` | `RestAPI\v1\NotificationController@list` | api, api_lang |
| 237 | `GET` | `/api/v1/notifications/seen` | `—` | `RestAPI\v1\NotificationController@notification_seen` | api, api_lang, auth:api |
| 238 | `GET` | `/api/v1/attributes` | `—` | `RestAPI\v1\AttributeController@get_attributes` | api, api_lang |
| 239 | `GET` | `/api/v1/flash-deals` | `—` | `RestAPI\v1\FlashDealController@getFlashDeal` | api, api_lang |
| 240 | `GET` | `/api/v1/flash-deals/products/{deal_id}` | `—` | `RestAPI\v1\FlashDealController@getFlashDealProducts` | api, api_lang |
| 241 | `GET` | `/api/v1/deals/featured` | `—` | `RestAPI\v1\DealController@getFeaturedDealProducts` | api, api_lang |
| 242 | `GET` | `/api/v1/dealsoftheday/deal-of-the-day` | `—` | `RestAPI\v1\DealOfTheDayController@getDealOfTheDayProduct` | api, api_lang |
| 243 | `GET` | `/api/v1/products/reviews/{slug}` | `—` | `RestAPI\v1\ProductController@get_product_reviews` | api, api_lang |
| 244 | `GET` | `/api/v1/products/rating/{product_id}` | `—` | `RestAPI\v1\ProductController@get_product_rating` | api, api_lang |
| 245 | `GET` | `/api/v1/products/counter/{slug}` | `—` | `RestAPI\v1\ProductController@counter` | api, api_lang |
| 246 | `GET` | `/api/v1/products/shipping-methods` | `—` | `RestAPI\v1\ProductController@get_shipping_methods` | api, api_lang |
| 247 | `GET` | `/api/v1/products/social-share-link/{product_id}` | `—` | `RestAPI\v1\ProductController@socialShareLink` | api, api_lang |
| 248 | `POST` | `/api/v1/products/reviews/submit` | `—` | `RestAPI\v1\ProductController@submit_product_review` | api, api_lang, auth:api |
| 249 | `PUT` | `/api/v1/products/review/update` | `—` | `RestAPI\v1\ProductController@updateProductReview` | api, api_lang, auth:api |
| 250 | `GET` | `/api/v1/products/review/{product_id}/{order_id}` | `—` | `RestAPI\v1\ProductController@getProductReviewByOrder` | api, api_lang, auth:api |
| 251 | `DELETE` | `/api/v1/products/review/delete-image` | `—` | `RestAPI\v1\ProductController@deleteReviewImage` | api, api_lang, auth:api |
| 252 | `GET` | `/api/v1/products/latest` | `—` | `RestAPI\v1\ProductController@get_latest_products` | api, api_lang, apiGuestCheck |
| 253 | `GET` | `/api/v1/products/new-arrival` | `—` | `RestAPI\v1\ProductController@getNewArrivalProducts` | api, api_lang, apiGuestCheck |
| 254 | `GET` | `/api/v1/products/featured` | `—` | `RestAPI\v1\ProductController@getFeaturedProductsList` | api, api_lang, apiGuestCheck |
| 255 | `GET` | `/api/v1/products/top-rated` | `—` | `RestAPI\v1\ProductController@getTopRatedProducts` | api, api_lang, apiGuestCheck |
| 256 | `GET|POST|PUT|PATCH|DELETE|OPTIONS` | `/api/v1/products/search` | `—` | `RestAPI\v1\ProductController@get_searched_products` | api, api_lang, apiGuestCheck |
| 257 | `POST` | `/api/v1/products/filter` | `—` | `RestAPI\v1\ProductController@getProductsFilter` | api, api_lang, apiGuestCheck |
| 258 | `GET|POST|PUT|PATCH|DELETE|OPTIONS` | `/api/v1/products/suggestion-product` | `—` | `RestAPI\v1\ProductController@get_suggestion_product` | api, api_lang, apiGuestCheck |
| 259 | `GET` | `/api/v1/products/details/{slug}` | `—` | `RestAPI\v1\ProductController@getProductDetails` | api, api_lang, apiGuestCheck |
| 260 | `GET` | `/api/v1/products/related-products/{slug}` | `—` | `RestAPI\v1\ProductController@get_related_products` | api, api_lang, apiGuestCheck |
| 261 | `GET` | `/api/v1/products/best-sellings` | `—` | `RestAPI\v1\ProductController@getBestSellingProducts` | api, api_lang, apiGuestCheck |
| 262 | `GET` | `/api/v1/products/home-categories` | `—` | `RestAPI\v1\ProductController@get_home_categories` | api, api_lang, apiGuestCheck |
| 263 | `GET` | `/api/v1/products/discounted-product` | `—` | `RestAPI\v1\ProductController@get_discounted_product` | api, api_lang, apiGuestCheck |
| 264 | `GET` | `/api/v1/products/most-demanded-product` | `—` | `RestAPI\v1\ProductController@get_most_demanded_product` | api, api_lang, apiGuestCheck |
| 265 | `GET` | `/api/v1/products/shop-again-product` | `—` | `RestAPI\v1\ProductController@getShopAgainProduct` | api, api_lang, apiGuestCheck, auth:api |
| 266 | `GET` | `/api/v1/products/just-for-you` | `—` | `RestAPI\v1\ProductController@just_for_you` | api, api_lang, apiGuestCheck |
| 267 | `GET` | `/api/v1/products/most-searching` | `—` | `RestAPI\v1\ProductController@getMostSearchingProductsList` | api, api_lang, apiGuestCheck |
| 268 | `GET` | `/api/v1/products/digital-author-list` | `—` | `RestAPI\v1\ProductController@getDigitalProductsAuthorList` | api, api_lang, apiGuestCheck |
| 269 | `GET` | `/api/v1/products/digital-publishing-house-list` | `—` | `RestAPI\v1\ProductController@getDigitalPublishingHouseList` | api, api_lang, apiGuestCheck |
| 270 | `GET` | `/api/v1/products/clearance-sale` | `—` | `RestAPI\v1\ProductController@getClearanceSale` | api, api_lang, apiGuestCheck |
| 271 | `GET` | `/api/v1/products/feed/google-merchant.xml` | `—` | `ProductFeedExportController@googleMerchantXml` | api, api_lang, apiGuestCheck |
| 272 | `GET` | `/api/v1/products/feed/facebook-catalog.csv` | `—` | `ProductFeedExportController@facebookCatalogCsv` | api, api_lang, apiGuestCheck |
| 273 | `GET` | `/api/v1/products/feed/tiktok-catalog.csv` | `—` | `ProductFeedExportController@tiktokCatalogCsv` | api, api_lang, apiGuestCheck |
| 274 | `PUT` | `/api/v1/customer/language-change` | `—` | `RestAPI\v1\CustomerController@language_change` | api, api_lang, apiGuestCheck |
| 275 | `GET` | `/api/v1/seller/{slug}/products` | `—` | `RestAPI\v1\SellerController@getVendorProducts` | api, api_lang, apiGuestCheck |
| 276 | `GET` | `/api/v1/seller/{slug}/seller-best-selling-products` | `—` | `RestAPI\v1\SellerController@get_seller_best_selling_products` | api, api_lang, apiGuestCheck |
| 277 | `GET` | `/api/v1/seller/{slug}/seller-featured-product` | `—` | `RestAPI\v1\SellerController@get_sellers_featured_product` | api, api_lang, apiGuestCheck |
| 278 | `GET` | `/api/v1/seller/{slug}/seller-recommended-products` | `—` | `RestAPI\v1\SellerController@get_sellers_recommended_products` | api, api_lang, apiGuestCheck |
| 279 | `GET` | `/api/v1/categories` | `—` | `RestAPI\v1\CategoryController@get_categories` | api, api_lang, apiGuestCheck |
| 280 | `GET` | `/api/v1/categories/products/{category_id}` | `—` | `RestAPI\v1\CategoryController@get_products` | api, api_lang, apiGuestCheck |
| 281 | `GET` | `/api/v1/categories/find-what-you-need` | `—` | `RestAPI\v1\CategoryController@find_what_you_need` | api, api_lang, apiGuestCheck |
| 282 | `GET` | `/api/v1/brands` | `—` | `RestAPI\v1\BrandController@get_brands` | api, api_lang, apiGuestCheck |
| 283 | `GET` | `/api/v1/brands/products/{brand_id}` | `—` | `RestAPI\v1\BrandController@get_products` | api, api_lang, apiGuestCheck |
| 284 | `GET` | `/api/v1/delivery-hubs/states` | `—` | `RestAPI\v1\DeliveryHubApiController@getStates` | api, api_lang, apiGuestCheck |
| 285 | `GET` | `/api/v1/delivery-hubs/cities/{state_id}` | `—` | `RestAPI\v1\DeliveryHubApiController@getCities` | api, api_lang, apiGuestCheck |
| 286 | `GET` | `/api/v1/delivery-hubs/hubs/{city_id}` | `—` | `RestAPI\v1\DeliveryHubApiController@getHubs` | api, api_lang, apiGuestCheck |
| 287 | `POST` | `/api/v1/delivery-hubs/calculate-shipping` | `—` | `RestAPI\v1\DeliveryHubApiController@calculateHubShipping` | api, api_lang, apiGuestCheck |
| 288 | `PUT` | `/api/v1/customer/cm-firebase-token` | `—` | `RestAPI\v1\CustomerController@update_cm_firebase_token` | api, api_lang, apiGuestCheck |
| 289 | `GET` | `/api/v1/customer/get-restricted-country-list` | `—` | `RestAPI\v1\CustomerController@get_restricted_country_list` | api, api_lang, apiGuestCheck |
| 290 | `GET` | `/api/v1/customer/get-restricted-zip-list` | `—` | `RestAPI\v1\CustomerController@get_restricted_zip_list` | api, api_lang, apiGuestCheck |
| 291 | `POST` | `/api/v1/customer/address/add` | `—` | `RestAPI\v1\CustomerController@add_new_address` | api, api_lang, apiGuestCheck |
| 292 | `GET` | `/api/v1/customer/address/list` | `—` | `RestAPI\v1\CustomerController@address_list` | api, api_lang, apiGuestCheck |
| 293 | `DELETE` | `/api/v1/customer/address` | `—` | `RestAPI\v1\CustomerController@delete_address` | api, api_lang, apiGuestCheck |
| 294 | `POST` | `/api/v1/customer/address/update` | `—` | `RestAPI\v1\CustomerController@update_address` | api, api_lang, apiGuestCheck |
| 295 | `GET` | `/api/v1/customer/order/place` | `—` | `RestAPI\v1\OrderController@place_order` | api, api_lang, apiGuestCheck |
| 296 | `GET` | `/api/v1/customer/order/offline-payment-method-list` | `—` | `RestAPI\v1\OrderController@offline_payment_method_list` | api, api_lang, apiGuestCheck |
| 297 | `POST` | `/api/v1/customer/order/place-by-offline-payment` | `—` | `RestAPI\v1\OrderController@placeOrderByOfflinePayment` | api, api_lang, apiGuestCheck |
| 298 | `GET` | `/api/v1/customer/order/details` | `—` | `RestAPI\v1\CustomerController@get_order_details` | api, api_lang, apiGuestCheck |
| 299 | `GET` | `/api/v1/customer/order/generate-invoice` | `—` | `RestAPI\v1\CustomerController@getOrderInvoice` | api, api_lang, apiGuestCheck |
| 300 | `GET` | `/api/v1/customer/order/deliveryman-review` | `—` | `RestAPI\v1\ReviewController@getReview` | api, api_lang, apiGuestCheck |
| 301 | `POST` | `/api/v1/customer/order/deliveryman-review/update` | `—` | `RestAPI\v1\ReviewController@updateDeliveryManReview` | api, api_lang, apiGuestCheck |
| 302 | `GET` | `/api/v1/customer/info` | `—` | `RestAPI\v1\CustomerController@info` | api, api_lang, auth:api |
| 303 | `PUT` | `/api/v1/customer/update-profile` | `—` | `RestAPI\v1\CustomerController@update_profile` | api, api_lang, auth:api |
| 304 | `GET` | `/api/v1/customer/account-delete/{id}` | `—` | `RestAPI\v1\CustomerController@account_delete` | api, api_lang, auth:api |
| 305 | `GET` | `/api/v1/customer/address/get/{id}` | `—` | `RestAPI\v1\CustomerController@get_address` | api, api_lang, auth:api |
| 306 | `POST` | `/api/v1/customer/support-ticket/create` | `—` | `RestAPI\v1\CustomerController@create_support_ticket` | api, api_lang, auth:api |
| 307 | `GET` | `/api/v1/customer/support-ticket/get` | `—` | `RestAPI\v1\CustomerController@get_support_tickets` | api, api_lang, auth:api |
| 308 | `GET` | `/api/v1/customer/support-ticket/conv/{ticket_id}` | `—` | `RestAPI\v1\CustomerController@get_support_ticket_conv` | api, api_lang, auth:api |
| 309 | `POST` | `/api/v1/customer/support-ticket/reply/{ticket_id}` | `—` | `RestAPI\v1\CustomerController@reply_support_ticket` | api, api_lang, auth:api |
| 310 | `GET` | `/api/v1/customer/support-ticket/close/{id}` | `—` | `RestAPI\v1\CustomerController@support_ticket_close` | api, api_lang, auth:api |
| 311 | `GET` | `/api/v1/customer/compare/list` | `—` | `RestAPI\v1\CompareController@list` | api, api_lang, auth:api |
| 312 | `POST` | `/api/v1/customer/compare/product-store` | `—` | `RestAPI\v1\CompareController@compare_product_store` | api, api_lang, auth:api |
| 313 | `DELETE` | `/api/v1/customer/compare/clear-all` | `—` | `RestAPI\v1\CompareController@clear_all` | api, api_lang, auth:api |
| 314 | `GET` | `/api/v1/customer/compare/product-replace` | `—` | `RestAPI\v1\CompareController@compare_product_replace` | api, api_lang, auth:api |
| 315 | `GET` | `/api/v1/customer/wish-list` | `—` | `RestAPI\v1\CustomerController@wish_list` | api, api_lang, auth:api |
| 316 | `POST` | `/api/v1/customer/wish-list/add` | `—` | `RestAPI\v1\CustomerController@add_to_wishlist` | api, api_lang, auth:api |
| 317 | `DELETE` | `/api/v1/customer/wish-list/remove` | `—` | `RestAPI\v1\CustomerController@remove_from_wishlist` | api, api_lang, auth:api |
| 318 | `GET` | `/api/v1/customer/restock-requests/list` | `—` | `RestAPI\v1\CustomerRestockRequestController@restockRequestsList` | api, api_lang, auth:api |
| 319 | `POST` | `/api/v1/customer/restock-requests/delete` | `—` | `RestAPI\v1\CustomerRestockRequestController@deleteRestockRequests` | api, api_lang, auth:api |
| 320 | `GET` | `/api/v1/customer/order/place-by-wallet` | `—` | `RestAPI\v1\OrderController@placeOrderByWallet` | api, api_lang, auth:api |
| 321 | `GET` | `/api/v1/customer/order/refund` | `—` | `RestAPI\v1\OrderController@refund_request` | api, api_lang, auth:api |
| 322 | `POST` | `/api/v1/customer/order/refund-store` | `—` | `RestAPI\v1\OrderController@store_refund` | api, api_lang, auth:api |
| 323 | `GET` | `/api/v1/customer/order/refund-details` | `—` | `RestAPI\v1\OrderController@refund_details` | api, api_lang, auth:api |
| 324 | `POST` | `/api/v1/customer/order/again` | `—` | `RestAPI\v1\OrderController@order_again` | api, api_lang, auth:api |
| 325 | `POST` | `/api/v1/customer/order/confirm-driver-transit-code` | `—` | `RestAPI\v1\OrderController@confirm_driver_transit_code` | api, api_lang, auth:api |
| 326 | `GET` | `/api/v1/customer/order/list` | `—` | `RestAPI\v1\CustomerController@get_order_list` | api, api_lang, auth:api |
| 327 | `POST` | `/api/v1/customer/order/deliveryman-reviews/submit` | `—` | `RestAPI\v1\ProductController@submit_deliveryman_review` | api, api_lang, auth:api, auth:api |
| 328 | `GET` | `/api/v1/customer/chat/list/{type}` | `—` | `RestAPI\v1\ChatController@list` | api, api_lang, auth:api |
| 329 | `GET` | `/api/v1/customer/chat/get-messages/{type}/{id}` | `—` | `RestAPI\v1\ChatController@get_message` | api, api_lang, auth:api |
| 330 | `POST` | `/api/v1/customer/chat/send-message/{type}` | `—` | `RestAPI\v1\ChatController@send_message` | api, api_lang, auth:api |
| 331 | `POST` | `/api/v1/customer/chat/seen-message/{type}` | `—` | `RestAPI\v1\ChatController@seen_message` | api, api_lang, auth:api |
| 332 | `GET` | `/api/v1/customer/chat/search/{type}` | `—` | `RestAPI\v1\ChatController@search` | api, api_lang, auth:api |
| 333 | `GET` | `/api/v1/customer/wallet/list` | `—` | `RestAPI\v1\UserWalletController@list` | api, api_lang, auth:api |
| 334 | `GET` | `/api/v1/customer/wallet/bonus-list` | `—` | `RestAPI\v1\UserWalletController@bonus_list` | api, api_lang, auth:api |
| 335 | `GET` | `/api/v1/customer/loyalty/list` | `—` | `RestAPI\v1\UserLoyaltyController@list` | api, api_lang, auth:api |
| 336 | `POST` | `/api/v1/customer/loyalty/loyalty-exchange-currency` | `—` | `RestAPI\v1\UserLoyaltyController@loyalty_exchange_currency` | api, api_lang, auth:api |
| 337 | `GET` | `/api/v1/customer/order/digital-product-download/{id}` | `—` | `RestAPI\v1\OrderController@digital_product_download` | api, api_lang, apiGuestCheck |
| 338 | `GET` | `/api/v1/customer/order/digital-product-download-otp-verify` | `—` | `RestAPI\v1\OrderController@digital_product_download_otp_verify` | api, api_lang, apiGuestCheck, throttle:5,1 |
| 339 | `POST` | `/api/v1/customer/order/digital-product-download-otp-resend` | `—` | `RestAPI\v1\OrderController@digital_product_download_otp_resend` | api, api_lang, apiGuestCheck, throttle:5,1 |
| 340 | `POST` | `/api/v1/digital-payment` | `—` | `Customer\PaymentController@payment` | api, api_lang, apiGuestCheck |
| 341 | `POST` | `/api/v1/add-to-fund` | `—` | `Customer\PaymentController@customer_add_to_fund_request` | api, api_lang, auth:api |
| 342 | `GET` | `/api/v1/order/track` | `—` | `RestAPI\v1\OrderController@track_by_order_id` | api, api_lang, apiGuestCheck |
| 343 | `GET` | `/api/v1/order/track-order-details` | `—` | `RestAPI\v1\OrderController@track_order_details_history` | api, api_lang, apiGuestCheck |
| 344 | `GET` | `/api/v1/order/cancel-order` | `—` | `RestAPI\v1\OrderController@order_cancel` | api, api_lang, apiGuestCheck |
| 345 | `POST` | `/api/v1/order/track-order` | `—` | `RestAPI\v1\OrderController@track_order` | api, api_lang, apiGuestCheck |
| 346 | `POST` | `/api/v1/edit-order/due-payment-by-offline-payment` | `—` | `RestAPI\v1\OrderEditController@duePaymentByOfflinePayment` | api, api_lang, apiGuestCheck |
| 347 | `POST` | `/api/v1/edit-order/due-payment-by-wallet` | `—` | `RestAPI\v1\OrderEditController@duePaymentByWallet` | api, api_lang, apiGuestCheck |
| 348 | `POST` | `/api/v1/edit-order/due-payment-by-cod` | `—` | `RestAPI\v1\OrderEditController@duePaymentByCod` | api, api_lang, apiGuestCheck |
| 349 | `POST` | `/api/v1/edit-order/due-payment-by-digital-payment` | `—` | `RestAPI\v1\OrderEditController@duePaymentByDigitalPayment` | api, api_lang, apiGuestCheck |
| 350 | `GET` | `/api/v1/banners` | `—` | `RestAPI\v1\BannerController@getBannerList` | api, api_lang |
| 351 | `GET` | `/api/v1/seller` | `—` | `RestAPI\v1\SellerController@get_seller_info` | api, api_lang |
| 352 | `GET` | `/api/v1/seller/list/{type}` | `—` | `RestAPI\v1\SellerController@getSellerList` | api, api_lang |
| 353 | `GET` | `/api/v1/seller/more` | `—` | `RestAPI\v1\SellerController@more_sellers` | api, api_lang |
| 354 | `GET` | `/api/v1/coupon/apply` | `—` | `RestAPI\v1\CouponController@apply` | api, api_lang, auth:api |
| 355 | `GET` | `/api/v1/coupon/list` | `—` | `RestAPI\v1\CouponController@list` | api, api_lang, auth:api |
| 356 | `GET` | `/api/v1/coupon/applicable-list` | `—` | `RestAPI\v1\CouponController@applicable_list` | api, api_lang, auth:api |
| 357 | `GET` | `/api/v1/coupons/{slug}/seller-wise-coupons` | `—` | `RestAPI\v1\CouponController@getSellerWiseCoupon` | api, api_lang |
| 358 | `GET` | `/api/v1/mapapi/place-api-autocomplete` | `—` | `RestAPI\v1\MapApiController@placeApiAutocomplete` | api, api_lang |
| 359 | `GET` | `/api/v1/mapapi/distance-api` | `—` | `RestAPI\v1\MapApiController@distanceApi` | api, api_lang |
| 360 | `GET` | `/api/v1/mapapi/place-api-details` | `—` | `RestAPI\v1\MapApiController@placeApiDetails` | api, api_lang |
| 361 | `GET` | `/api/v1/mapapi/geocode-api` | `—` | `RestAPI\v1\MapApiController@geocode_api` | api, api_lang |
| 362 | `GET` | `/api/v1/faq` | `—` | `RestAPI\v1\GeneralController@faq` | api, api_lang |
| 363 | `GET` | `/api/v1/get-guest-id` | `—` | `RestAPI\v1\GeneralController@get_guest_id` | api, api_lang |
| 364 | `POST` | `/api/v1/contact-us` | `—` | `RestAPI\v1\GeneralController@contact_store` | api, api_lang |
| 365 | `GET` | `/api/v1/taxmodule` | `api.taxmodule` | `Closure` | api, auth:sanctum |
| 366 | `GET` | `/api/v1/vat-tax/get-taxVat-list` | `v1.vat-tax.` | `Modules\TaxModule\app\Http\Controllers\Api\V1\TaxController@getTaxVatList` | api |
| 367 | `POST` | `/api/v1/vat-tax/get-calculated-tax` | `v1.vat-tax.` | `Modules\TaxModule\app\Http\Controllers\Api\V1\TaxController@getCalculateTax` | api |

---

### 🏷️ Domain: Delivery Rider REST API (v2) (100 Endpoints)

| # | Method(s) | URI / Route | Route Name | Controller Action | Security Middleware |
| :-: | :--- | :--- | :--- | :--- | :--- |
| 368 | `GET` | `/api/v2/seller/seller-info` | `—` | `RestAPI\v2\seller\SellerController@seller_info` | api, api_lang |
| 369 | `GET` | `/api/v2/seller/account-delete` | `—` | `RestAPI\v2\seller\SellerController@account_delete` | api, api_lang |
| 370 | `GET` | `/api/v2/seller/seller-delivery-man` | `—` | `RestAPI\v2\seller\SellerController@seller_delivery_man` | api, api_lang |
| 371 | `GET` | `/api/v2/seller/shop-product-reviews` | `—` | `RestAPI\v2\seller\SellerController@shop_product_reviews` | api, api_lang |
| 372 | `GET` | `/api/v2/seller/shop-product-reviews-status` | `—` | `RestAPI\v2\seller\SellerController@shop_product_reviews_status` | api, api_lang |
| 373 | `PUT` | `/api/v2/seller/seller-update` | `—` | `RestAPI\v2\seller\SellerController@seller_info_update` | api, api_lang |
| 374 | `GET` | `/api/v2/seller/monthly-earning` | `—` | `RestAPI\v2\seller\SellerController@monthly_earning` | api, api_lang |
| 375 | `GET` | `/api/v2/seller/monthly-commission-given` | `—` | `RestAPI\v2\seller\SellerController@monthly_commission_given` | api, api_lang |
| 376 | `PUT` | `/api/v2/seller/cm-firebase-token` | `—` | `RestAPI\v2\seller\SellerController@update_cm_firebase_token` | api, api_lang |
| 377 | `GET` | `/api/v2/seller/shop-info` | `—` | `RestAPI\v2\seller\SellerController@shop_info` | api, api_lang |
| 378 | `GET` | `/api/v2/seller/transactions` | `—` | `RestAPI\v2\seller\SellerController@transaction` | api, api_lang |
| 379 | `PUT` | `/api/v2/seller/shop-update` | `—` | `RestAPI\v2\seller\SellerController@shop_info_update` | api, api_lang |
| 380 | `POST` | `/api/v2/seller/balance-withdraw` | `—` | `RestAPI\v2\seller\SellerController@withdraw_request` | api, api_lang |
| 381 | `DELETE` | `/api/v2/seller/close-withdraw-request` | `—` | `RestAPI\v2\seller\SellerController@close_withdraw_request` | api, api_lang |
| 382 | `GET` | `/api/v2/seller/brands` | `—` | `RestAPI\v2\seller\BrandController@getBrands` | api, api_lang |
| 383 | `POST` | `/api/v2/seller/products/upload-images` | `—` | `RestAPI\v2\seller\ProductController@upload_images` | api, api_lang |
| 384 | `POST` | `/api/v2/seller/products/upload-digital-product` | `—` | `RestAPI\v2\seller\ProductController@upload_digital_product` | api, api_lang |
| 385 | `POST` | `/api/v2/seller/products/add` | `—` | `RestAPI\v2\seller\ProductController@add_new` | api, api_lang |
| 386 | `GET` | `/api/v2/seller/products/list` | `—` | `RestAPI\v2\seller\ProductController@list` | api, api_lang |
| 387 | `GET` | `/api/v2/seller/products/stock-out-list` | `—` | `RestAPI\v2\seller\ProductController@stock_out_list` | api, api_lang |
| 388 | `GET` | `/api/v2/seller/products/status-update` | `—` | `RestAPI\v2\seller\ProductController@status_update` | api, api_lang |
| 389 | `GET` | `/api/v2/seller/products/edit/{id}` | `—` | `RestAPI\v2\seller\ProductController@edit` | api, api_lang |
| 390 | `PUT` | `/api/v2/seller/products/update/{id}` | `—` | `RestAPI\v2\seller\ProductController@update` | api, api_lang |
| 391 | `DELETE` | `/api/v2/seller/products/delete/{id}` | `—` | `RestAPI\v2\seller\ProductController@delete` | api, api_lang |
| 392 | `GET` | `/api/v2/seller/products/barcode/generate` | `—` | `RestAPI\v2\seller\ProductController@barcode_generate` | api, api_lang |
| 393 | `GET` | `/api/v2/seller/orders/list` | `—` | `RestAPI\v2\seller\OrderController@list` | api, api_lang |
| 394 | `GET` | `/api/v2/seller/orders/{id}` | `—` | `RestAPI\v2\seller\OrderController@details` | api, api_lang |
| 395 | `PUT` | `/api/v2/seller/orders/order-detail-status/{id}` | `—` | `RestAPI\v2\seller\OrderController@order_detail_status` | api, api_lang |
| 396 | `PUT` | `/api/v2/seller/orders/assign-delivery-man` | `—` | `RestAPI\v2\seller\OrderController@assign_delivery_man` | api, api_lang |
| 397 | `PUT` | `/api/v2/seller/orders/order-wise-product-upload` | `—` | `RestAPI\v2\seller\OrderController@digital_file_upload_after_sell` | api, api_lang |
| 398 | `PUT` | `/api/v2/seller/orders/delivery-charge-date-update` | `—` | `RestAPI\v2\seller\OrderController@amount_date_update` | api, api_lang |
| 399 | `POST` | `/api/v2/seller/orders/assign-third-party-delivery` | `—` | `RestAPI\v2\seller\OrderController@assign_third_party_delivery` | api, api_lang |
| 400 | `POST` | `/api/v2/seller/orders/update-payment-status` | `—` | `RestAPI\v2\seller\OrderController@update_payment_status` | api, api_lang |
| 401 | `GET` | `/api/v2/seller/refund/list` | `—` | `RestAPI\v2\seller\RefundController@list` | api, api_lang |
| 402 | `GET` | `/api/v2/seller/refund/refund-details` | `—` | `RestAPI\v2\seller\RefundController@refund_details` | api, api_lang |
| 403 | `POST` | `/api/v2/seller/refund/refund-status-update` | `—` | `RestAPI\v2\seller\RefundController@refund_status_update` | api, api_lang |
| 404 | `GET` | `/api/v2/seller/shipping/get-shipping-method` | `—` | `RestAPI\v2\seller\shippingController@get_shipping_type` | api, api_lang |
| 405 | `GET` | `/api/v2/seller/shipping/selected-shipping-method` | `—` | `RestAPI\v2\seller\shippingController@selected_shipping_type` | api, api_lang |
| 406 | `GET` | `/api/v2/seller/shipping/all-category-cost` | `—` | `RestAPI\v2\seller\shippingController@all_category_cost` | api, api_lang |
| 407 | `POST` | `/api/v2/seller/shipping/set-category-cost` | `—` | `RestAPI\v2\seller\shippingController@set_category_cost` | api, api_lang |
| 408 | `GET` | `/api/v2/seller/shipping-method/list` | `—` | `RestAPI\v2\seller\ShippingMethodController@list` | api, api_lang |
| 409 | `POST` | `/api/v2/seller/shipping-method/add` | `—` | `RestAPI\v2\seller\ShippingMethodController@store` | api, api_lang |
| 410 | `GET` | `/api/v2/seller/shipping-method/edit/{id}` | `—` | `RestAPI\v2\seller\ShippingMethodController@edit` | api, api_lang |
| 411 | `PUT` | `/api/v2/seller/shipping-method/status` | `—` | `RestAPI\v2\seller\ShippingMethodController@status_update` | api, api_lang |
| 412 | `PUT` | `/api/v2/seller/shipping-method/update/{id}` | `—` | `RestAPI\v2\seller\ShippingMethodController@update` | api, api_lang |
| 413 | `DELETE` | `/api/v2/seller/shipping-method/delete/{id}` | `—` | `RestAPI\v2\seller\ShippingMethodController@delete` | api, api_lang |
| 414 | `GET` | `/api/v2/seller/messages/list/{type}` | `—` | `RestAPI\v2\seller\ChatController@list` | api, api_lang |
| 415 | `GET` | `/api/v2/seller/messages/get-message/{type}/{id}` | `—` | `RestAPI\v2\seller\ChatController@get_message` | api, api_lang |
| 416 | `POST` | `/api/v2/seller/messages/send/{type}` | `—` | `RestAPI\v2\seller\ChatController@send_message` | api, api_lang |
| 417 | `GET` | `/api/v2/seller/messages/search/{type}` | `—` | `RestAPI\v2\seller\ChatController@search` | api, api_lang |
| 418 | `POST` | `/api/v2/seller/auth/login` | `—` | `RestAPI\v2\seller\auth\LoginController@login` | api, api_lang |
| 419 | `POST` | `/api/v2/seller/auth/forgot-password` | `—` | `RestAPI\v2\seller\auth\ForgotPasswordController@reset_password_request` | api, api_lang |
| 420 | `POST` | `/api/v2/seller/auth/verify-otp` | `—` | `RestAPI\v2\seller\auth\ForgotPasswordController@otp_verification_submit` | api, api_lang |
| 421 | `PUT` | `/api/v2/seller/auth/reset-password` | `—` | `RestAPI\v2\seller\auth\ForgotPasswordController@reset_password_submit` | api, api_lang |
| 422 | `POST` | `/api/v2/seller/registration` | `—` | `RestAPI\v2\seller\auth\RegisterController@store` | api, api_lang |
| 423 | `POST` | `/api/v2/delivery-man/auth/login` | `—` | `RestAPI\v2\delivery_man\auth\LoginController@login` | api, api_lang, throttle:10,1 |
| 424 | `POST` | `/api/v2/delivery-man/auth/forgot-password` | `—` | `RestAPI\v2\delivery_man\auth\LoginController@reset_password_request` | api, api_lang, throttle:10,1 |
| 425 | `POST` | `/api/v2/delivery-man/auth/verify-otp` | `—` | `RestAPI\v2\delivery_man\auth\LoginController@otp_verification_submit` | api, api_lang, throttle:10,1 |
| 426 | `POST` | `/api/v2/delivery-man/auth/reset-password` | `—` | `RestAPI\v2\delivery_man\auth\LoginController@reset_password_submit` | api, api_lang, throttle:10,1 |
| 427 | `PUT` | `/api/v2/delivery-man/language-change` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@language_change` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 428 | `PUT` | `/api/v2/delivery-man/is-online` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@is_online` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 429 | `GET` | `/api/v2/delivery-man/info` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@info` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 430 | `POST` | `/api/v2/delivery-man/distance-api` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@distance_api` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 431 | `GET` | `/api/v2/delivery-man/current-orders` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@get_current_orders` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 432 | `GET` | `/api/v2/delivery-man/all-orders` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@get_all_orders` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 433 | `POST` | `/api/v2/delivery-man/record-location-data` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@record_location_data` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 434 | `GET` | `/api/v2/delivery-man/order-delivery-history` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@get_order_history` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 435 | `PUT` | `/api/v2/delivery-man/update-order-status` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@update_order_status` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 436 | `PUT` | `/api/v2/delivery-man/update-expected-delivery` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@update_expected_delivery` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 437 | `PUT` | `/api/v2/delivery-man/update-payment-status` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@order_payment_status_update` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 438 | `PUT` | `/api/v2/delivery-man/order-update-is-pause` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@order_update_is_pause` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 439 | `GET` | `/api/v2/delivery-man/order-item` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@getOrderItem` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 440 | `GET` | `/api/v2/delivery-man/order-details` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@get_order_details` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 441 | `GET` | `/api/v2/delivery-man/last-location` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@get_last_location` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 442 | `PUT` | `/api/v2/delivery-man/update-fcm-token` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@update_fcm_token` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 443 | `GET` | `/api/v2/delivery-man/delivery-wise-earned` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@delivery_wise_earned` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 444 | `GET` | `/api/v2/delivery-man/order-list-by-date` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@order_list_date_filter` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 445 | `GET` | `/api/v2/delivery-man/search` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@search` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 446 | `GET` | `/api/v2/delivery-man/profile-dashboard-counts` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@profile_dashboard_counts` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 447 | `PUT` | `/api/v2/delivery-man/update-info` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@update_info` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 448 | `PUT` | `/api/v2/delivery-man/bank-info` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@bank_info` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 449 | `GET` | `/api/v2/delivery-man/review-list` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@review_list` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 450 | `PUT` | `/api/v2/delivery-man/save-review` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@is_saved` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 451 | `GET` | `/api/v2/delivery-man/collected_cash_history` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@collected_cash_history` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 452 | `GET` | `/api/v2/delivery-man/emergency-contact-list` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@emergency_contact_list` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 453 | `GET` | `/api/v2/delivery-man/notifications` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@get_all_notification` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 454 | `POST` | `/api/v2/delivery-man/resend-verification-code` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@resend_verification_code` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 455 | `POST` | `/api/v2/delivery-man/order-delivery-verification` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@order_delivery_verification` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 456 | `POST` | `/api/v2/delivery-man/interstate-driver-handover` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@interstate_driver_handover` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 457 | `POST` | `/api/v2/delivery-man/generate-paystack-link` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@generate_paystack_link` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 458 | `POST` | `/api/v2/delivery-man/remit-cash-paystack-init` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@remit_cash_paystack_init` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 459 | `GET` | `/api/v2/delivery-man/get-waybill-label` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@get_waybill_label` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 460 | `POST` | `/api/v2/delivery-man/change-status` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@change_status` | api, api_lang, delivery_man_auth, actch:deliveryman_app, throttle:5,1 |
| 461 | `POST` | `/api/v2/delivery-man/verify-order-delivery-otp` | `—` | `RestAPI\v2\delivery_man\DeliveryManController@verify_order_delivery_otp` | api, api_lang, delivery_man_auth, actch:deliveryman_app, throttle:5,1 |
| 462 | `POST` | `/api/v2/delivery-man/withdraw-request` | `—` | `RestAPI\v2\delivery_man\WithdrawController@sendWithdrawRequest` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 463 | `GET` | `/api/v2/delivery-man/withdraw-list-by-approved` | `—` | `RestAPI\v2\delivery_man\WithdrawController@getWithdrawListByApproved` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 464 | `GET` | `/api/v2/delivery-man/messages/list/{type}` | `—` | `RestAPI\v2\delivery_man\ChatController@list` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 465 | `GET` | `/api/v2/delivery-man/messages/get-message/{type}/{id}` | `—` | `RestAPI\v2\delivery_man\ChatController@get_message` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 466 | `POST` | `/api/v2/delivery-man/messages/send-message/{type}` | `—` | `RestAPI\v2\delivery_man\ChatController@send_message` | api, api_lang, delivery_man_auth, actch:deliveryman_app |
| 467 | `GET` | `/api/v2/delivery-man/messages/search/{type}` | `—` | `RestAPI\v2\delivery_man\ChatController@search` | api, api_lang, delivery_man_auth, actch:deliveryman_app |

---

### 🏷️ Domain: Vendor Mobile REST API (v3) (162 Endpoints)

| # | Method(s) | URI / Route | Route Name | Controller Action | Security Middleware |
| :-: | :--- | :--- | :--- | :--- | :--- |
| 468 | `POST` | `/api/v3/seller/auth/login` | `—` | `RestAPI\v3\seller\auth\LoginController@login` | api, api_lang, throttle:10,1 |
| 469 | `POST` | `/api/v3/seller/auth/forgot-password` | `—` | `RestAPI\v3\seller\auth\ForgotPasswordController@reset_password_request` | api, api_lang, throttle:10,1 |
| 470 | `POST` | `/api/v3/seller/auth/verify-otp` | `—` | `RestAPI\v3\seller\auth\ForgotPasswordController@otp_verification_submit` | api, api_lang, throttle:10,1 |
| 471 | `PUT` | `/api/v3/seller/auth/reset-password` | `—` | `RestAPI\v3\seller\auth\ForgotPasswordController@reset_password_submit` | api, api_lang, throttle:10,1 |
| 472 | `POST` | `/api/v3/seller/auth/firebase-auth-token-store` | `—` | `RestAPI\v3\seller\auth\ForgotPasswordController@firebaseAuthTokenStore` | api, api_lang, throttle:10,1 |
| 473 | `POST` | `/api/v3/seller/auth/firebase-auth-verify` | `—` | `RestAPI\v3\seller\auth\ForgotPasswordController@firebaseAuthVerify` | api, api_lang, throttle:10,1 |
| 474 | `POST` | `/api/v3/seller/auth/check-vendor-exist-info` | `—` | `RestAPI\v3\seller\auth\ForgotPasswordController@checkVendorExistInfo` | api, api_lang, throttle:10,1 |
| 475 | `POST` | `/api/v3/seller/registration` | `—` | `RestAPI\v3\seller\auth\RegisterController@store` | api, api_lang |
| 476 | `PUT` | `/api/v3/seller/language-change` | `—` | `RestAPI\v3\seller\SellerController@language_change` | api, api_lang, seller_api_auth |
| 477 | `GET` | `/api/v3/seller/seller-info` | `—` | `RestAPI\v3\seller\SellerController@getSellerInfo` | api, api_lang, seller_api_auth |
| 478 | `GET` | `/api/v3/seller/get-earning-statitics` | `—` | `RestAPI\v3\seller\SellerController@getEarningStatics` | api, api_lang, seller_api_auth |
| 479 | `GET` | `/api/v3/seller/order-statistics` | `—` | `RestAPI\v3\seller\SellerController@order_statistics` | api, api_lang, seller_api_auth |
| 480 | `GET` | `/api/v3/seller/account-delete` | `—` | `RestAPI\v3\seller\SellerController@account_delete` | api, api_lang, seller_api_auth |
| 481 | `GET` | `/api/v3/seller/seller-delivery-man` | `—` | `RestAPI\v3\seller\SellerController@seller_delivery_man` | api, api_lang, seller_api_auth |
| 482 | `GET` | `/api/v3/seller/shop-product-reviews` | `—` | `RestAPI\v3\seller\SellerController@shop_product_reviews` | api, api_lang, seller_api_auth |
| 483 | `POST` | `/api/v3/seller/shop-product-reviews-reply` | `—` | `RestAPI\v3\seller\SellerController@shopProductReviewReply` | api, api_lang, seller_api_auth |
| 484 | `GET` | `/api/v3/seller/shop-product-reviews-status` | `—` | `RestAPI\v3\seller\SellerController@shop_product_reviews_status` | api, api_lang, seller_api_auth |
| 485 | `PUT` | `/api/v3/seller/seller-update` | `—` | `RestAPI\v3\seller\SellerController@seller_info_update` | api, api_lang, seller_api_auth |
| 486 | `GET` | `/api/v3/seller/paystack/banks` | `—` | `RestAPI\v3\seller\SellerController@get_nigerian_banks` | api, api_lang, seller_api_auth |
| 487 | `POST` | `/api/v3/seller/paystack/resolve-account` | `—` | `RestAPI\v3\seller\SellerController@resolve_bank_account` | api, api_lang, seller_api_auth |
| 488 | `POST` | `/api/v3/seller/bank-info/send-otp` | `—` | `RestAPI\v3\seller\SellerController@send_bank_update_otp` | api, api_lang, seller_api_auth |
| 489 | `GET` | `/api/v3/seller/kyc/status` | `—` | `RestAPI\v3\seller\SellerController@get_kyc_status` | api, api_lang, seller_api_auth |
| 490 | `POST` | `/api/v3/seller/kyc/submit` | `—` | `RestAPI\v3\seller\SellerController@submit_kyc` | api, api_lang, seller_api_auth |
| 491 | `GET` | `/api/v3/seller/monthly-earning` | `—` | `RestAPI\v3\seller\SellerController@monthly_earning` | api, api_lang, seller_api_auth |
| 492 | `GET` | `/api/v3/seller/monthly-commission-given` | `—` | `RestAPI\v3\seller\SellerController@monthly_commission_given` | api, api_lang, seller_api_auth |
| 493 | `PUT` | `/api/v3/seller/cm-firebase-token` | `—` | `RestAPI\v3\seller\SellerController@update_cm_firebase_token` | api, api_lang, seller_api_auth |
| 494 | `GET` | `/api/v3/seller/shop-info` | `—` | `RestAPI\v3\seller\SellerController@shop_info` | api, api_lang, seller_api_auth |
| 495 | `GET` | `/api/v3/seller/transactions` | `—` | `RestAPI\v3\seller\SellerController@transaction` | api, api_lang, seller_api_auth |
| 496 | `PUT` | `/api/v3/seller/shop-update` | `—` | `RestAPI\v3\seller\SellerController@shop_info_update` | api, api_lang, seller_api_auth |
| 497 | `POST` | `/api/v3/seller/update-setup-guide-app` | `—` | `RestAPI\v3\seller\SellerController@updateSetupGuideApp` | api, api_lang, seller_api_auth |
| 498 | `GET` | `/api/v3/seller/withdraw-method-list` | `—` | `RestAPI\v3\seller\SellerController@withdraw_method_list` | api, api_lang, seller_api_auth |
| 499 | `POST` | `/api/v3/seller/balance-withdraw` | `—` | `RestAPI\v3\seller\SellerController@withdraw_request` | api, api_lang, seller_api_auth |
| 500 | `DELETE` | `/api/v3/seller/close-withdraw-request` | `—` | `RestAPI\v3\seller\SellerController@close_withdraw_request` | api, api_lang, seller_api_auth |
| 501 | `PUT` | `/api/v3/seller/vacation-add` | `—` | `RestAPI\v3\seller\ShopController@vacation_add` | api, api_lang, seller_api_auth |
| 502 | `PUT` | `/api/v3/seller/temporary-close` | `—` | `RestAPI\v3\seller\ShopController@temporary_close` | api, api_lang, seller_api_auth |
| 503 | `GET` | `/api/v3/seller/brands` | `—` | `RestAPI\v3\seller\BrandController@getBrands` | api, api_lang, seller_api_auth |
| 504 | `GET` | `/api/v3/seller/top-delivery-man` | `—` | `RestAPI\v3\seller\ProductController@top_delivery_man` | api, api_lang, seller_api_auth |
| 505 | `GET` | `/api/v3/seller/categories` | `—` | `RestAPI\v3\seller\ProductController@get_categories` | api, api_lang, seller_api_auth |
| 506 | `GET` | `/api/v3/seller/products/list` | `—` | `RestAPI\v3\seller\ProductController@getProductList` | api, api_lang, seller_api_auth |
| 507 | `POST` | `/api/v3/seller/products/upload-images` | `—` | `RestAPI\v3\seller\ProductController@upload_images` | api, api_lang, seller_api_auth |
| 508 | `POST` | `/api/v3/seller/products/upload-digital-product` | `—` | `RestAPI\v3\seller\ProductController@upload_digital_product` | api, api_lang, seller_api_auth |
| 509 | `POST` | `/api/v3/seller/products/delete-digital-product` | `—` | `RestAPI\v3\seller\ProductController@deleteDigitalProduct` | api, api_lang, seller_api_auth |
| 510 | `POST` | `/api/v3/seller/products/add` | `—` | `RestAPI\v3\seller\ProductController@add_new` | api, api_lang, seller_api_auth |
| 511 | `GET` | `/api/v3/seller/products/details/{id}` | `—` | `RestAPI\v3\seller\ProductController@details` | api, api_lang, seller_api_auth |
| 512 | `GET` | `/api/v3/seller/products/stock-out-list` | `—` | `RestAPI\v3\seller\ProductController@stock_out_list` | api, api_lang, seller_api_auth |
| 513 | `PUT` | `/api/v3/seller/products/status-update` | `—` | `RestAPI\v3\seller\ProductController@status_update` | api, api_lang, seller_api_auth |
| 514 | `GET` | `/api/v3/seller/products/edit/{id}` | `—` | `RestAPI\v3\seller\ProductController@edit` | api, api_lang, seller_api_auth |
| 515 | `PUT` | `/api/v3/seller/products/update/{id}` | `—` | `RestAPI\v3\seller\ProductController@updateProduct` | api, api_lang, seller_api_auth |
| 516 | `GET` | `/api/v3/seller/products/review-list/{id}` | `—` | `RestAPI\v3\seller\ProductController@review_list` | api, api_lang, seller_api_auth |
| 517 | `PUT` | `/api/v3/seller/products/quantity-update` | `—` | `RestAPI\v3\seller\ProductController@updateProductQuantity` | api, api_lang, seller_api_auth |
| 518 | `DELETE` | `/api/v3/seller/products/delete/{id}` | `—` | `RestAPI\v3\seller\ProductController@delete` | api, api_lang, seller_api_auth |
| 519 | `GET` | `/api/v3/seller/products/barcode/generate` | `—` | `RestAPI\v3\seller\ProductController@barcode_generate` | api, api_lang, seller_api_auth |
| 520 | `GET` | `/api/v3/seller/products/top-selling-product` | `—` | `RestAPI\v3\seller\ProductController@top_selling_products` | api, api_lang, seller_api_auth |
| 521 | `GET` | `/api/v3/seller/products/most-popular-product` | `—` | `RestAPI\v3\seller\ProductController@most_popular_products` | api, api_lang, seller_api_auth |
| 522 | `GET` | `/api/v3/seller/products/delete-image` | `—` | `RestAPI\v3\seller\ProductController@deleteImage` | api, api_lang, seller_api_auth |
| 523 | `GET` | `/api/v3/seller/products/get-product-images/{id}` | `—` | `RestAPI\v3\seller\ProductController@getProductImages` | api, api_lang, seller_api_auth |
| 524 | `GET` | `/api/v3/seller/products/stock-limit-status` | `—` | `RestAPI\v3\seller\ProductController@getStockLimitStatus` | api, api_lang, seller_api_auth |
| 525 | `GET` | `/api/v3/seller/products/delete-preview-file` | `—` | `RestAPI\v3\seller\ProductController@deletePreviewFile` | api, api_lang, seller_api_auth |
| 526 | `GET` | `/api/v3/seller/products/digital-author-list` | `—` | `RestAPI\v3\seller\ProductController@getDigitalProductsAuthorList` | api, api_lang, seller_api_auth |
| 527 | `GET` | `/api/v3/seller/products/digital-publishing-house-list` | `—` | `RestAPI\v3\seller\ProductController@getDigitalPublishingHouseList` | api, api_lang, seller_api_auth |
| 528 | `POST` | `/api/v3/seller/products/restock-request-list` | `—` | `RestAPI\v3\seller\ProductController@getRestockRequestList` | api, api_lang, seller_api_auth |
| 529 | `GET` | `/api/v3/seller/products/restock-request-delete` | `—` | `RestAPI\v3\seller\ProductController@deleteRestockRequest` | api, api_lang, seller_api_auth |
| 530 | `POST` | `/api/v3/seller/products/restock-request-stock-update` | `—` | `RestAPI\v3\seller\ProductController@updateRestockQuantity` | api, api_lang, seller_api_auth |
| 531 | `GET` | `/api/v3/seller/products/restock-request-brands-list` | `—` | `RestAPI\v3\seller\ProductController@getRestockRequestBrands` | api, api_lang, seller_api_auth |
| 532 | `POST` | `/api/v3/seller/products/update-price-and-reactivate` | `—` | `RestAPI\v3\seller\ProductController@updatePriceAndReactivate` | api, api_lang, seller_api_auth |
| 533 | `POST` | `/api/v3/seller/orders/list` | `—` | `RestAPI\v3\seller\OrderController@list` | api, api_lang, seller_api_auth |
| 534 | `GET` | `/api/v3/seller/orders/{id}` | `—` | `RestAPI\v3\seller\OrderController@details` | api, api_lang, seller_api_auth |
| 535 | `PUT` | `/api/v3/seller/orders/order-detail-status/{id}` | `—` | `RestAPI\v3\seller\OrderController@order_detail_status` | api, api_lang, seller_api_auth |
| 536 | `PUT` | `/api/v3/seller/orders/assign-delivery-man` | `—` | `RestAPI\v3\seller\OrderController@assign_delivery_man` | api, api_lang, seller_api_auth |
| 537 | `PUT` | `/api/v3/seller/orders/order-wise-product-upload` | `—` | `RestAPI\v3\seller\OrderController@digital_file_upload_after_sell` | api, api_lang, seller_api_auth |
| 538 | `PUT` | `/api/v3/seller/orders/delivery-charge-date-update` | `—` | `RestAPI\v3\seller\OrderController@amount_date_update` | api, api_lang, seller_api_auth |
| 539 | `POST` | `/api/v3/seller/orders/assign-third-party-delivery` | `—` | `RestAPI\v3\seller\OrderController@assign_third_party_delivery` | api, api_lang, seller_api_auth |
| 540 | `POST` | `/api/v3/seller/orders/update-payment-status` | `—` | `RestAPI\v3\seller\OrderController@update_payment_status` | api, api_lang, seller_api_auth |
| 541 | `POST` | `/api/v3/seller/orders/address-update` | `—` | `RestAPI\v3\seller\OrderController@address_update` | api, api_lang, seller_api_auth |
| 542 | `POST` | `/api/v3/seller/orders/order-detail-info-update` | `—` | `RestAPI\v3\seller\OrderController@updateOrderDetails` | api, api_lang, seller_api_auth |
| 543 | `POST` | `/api/v3/seller/orders/edit-order-submit` | `—` | `RestAPI\v3\seller\OrderEditController@submitEditOrder` | api, api_lang, seller_api_auth |
| 544 | `POST` | `/api/v3/seller/orders/edit-order-validation` | `—` | `RestAPI\v3\seller\OrderEditController@checkEditOrderValidation` | api, api_lang, seller_api_auth |
| 545 | `POST` | `/api/v3/seller/orders/assign-order-in-cod` | `—` | `RestAPI\v3\seller\OrderEditController@assignOrderInCOD` | api, api_lang, seller_api_auth |
| 546 | `GET` | `/api/v3/seller/clearance-sale/product-list` | `—` | `RestAPI\v3\seller\ClearanceSaleController@list` | api, api_lang, seller_api_auth |
| 547 | `POST` | `/api/v3/seller/clearance-sale/product-add` | `—` | `RestAPI\v3\seller\ClearanceSaleController@addClearanceProduct` | api, api_lang, seller_api_auth |
| 548 | `POST` | `/api/v3/seller/clearance-sale/product-delete` | `—` | `RestAPI\v3\seller\ClearanceSaleController@deleteClearanceProduct` | api, api_lang, seller_api_auth |
| 549 | `POST` | `/api/v3/seller/clearance-sale/all-product-delete` | `—` | `RestAPI\v3\seller\ClearanceSaleController@deleteAllClearanceProduct` | api, api_lang, seller_api_auth |
| 550 | `POST` | `/api/v3/seller/clearance-sale/product-status-update` | `—` | `RestAPI\v3\seller\ClearanceSaleController@updateClearanceProductStatus` | api, api_lang, seller_api_auth |
| 551 | `POST` | `/api/v3/seller/clearance-sale/product-discount-update` | `—` | `RestAPI\v3\seller\ClearanceSaleController@updateClearanceProductDiscount` | api, api_lang, seller_api_auth |
| 552 | `POST` | `/api/v3/seller/clearance-sale/config-status-update` | `—` | `RestAPI\v3\seller\ClearanceSaleController@updateClearanceConfigStatus` | api, api_lang, seller_api_auth |
| 553 | `GET` | `/api/v3/seller/clearance-sale/config-data` | `—` | `RestAPI\v3\seller\ClearanceSaleController@getConfigData` | api, api_lang, seller_api_auth |
| 554 | `POST` | `/api/v3/seller/clearance-sale/config-data-update` | `—` | `RestAPI\v3\seller\ClearanceSaleController@updateConfigData` | api, api_lang, seller_api_auth |
| 555 | `GET` | `/api/v3/seller/refund/list` | `—` | `RestAPI\v3\seller\RefundController@list` | api, api_lang, seller_api_auth |
| 556 | `GET` | `/api/v3/seller/refund/single-item` | `—` | `RestAPI\v3\seller\RefundController@getSingleItem` | api, api_lang, seller_api_auth |
| 557 | `GET` | `/api/v3/seller/refund/refund-details` | `—` | `RestAPI\v3\seller\RefundController@refund_details` | api, api_lang, seller_api_auth |
| 558 | `POST` | `/api/v3/seller/refund/refund-status-update` | `—` | `RestAPI\v3\seller\RefundController@refund_status_update` | api, api_lang, seller_api_auth |
| 559 | `GET` | `/api/v3/seller/coupon/list` | `—` | `RestAPI\v3\seller\CouponController@list` | api, api_lang, seller_api_auth |
| 560 | `POST` | `/api/v3/seller/coupon/store` | `—` | `RestAPI\v3\seller\CouponController@store` | api, api_lang, seller_api_auth |
| 561 | `PUT` | `/api/v3/seller/coupon/update/{id}` | `—` | `RestAPI\v3\seller\CouponController@update` | api, api_lang, seller_api_auth |
| 562 | `PUT` | `/api/v3/seller/coupon/status-update/{id}` | `—` | `RestAPI\v3\seller\CouponController@status_update` | api, api_lang, seller_api_auth |
| 563 | `DELETE` | `/api/v3/seller/coupon/delete/{id}` | `—` | `RestAPI\v3\seller\CouponController@delete` | api, api_lang, seller_api_auth |
| 564 | `POST` | `/api/v3/seller/coupon/check-coupon` | `—` | `RestAPI\v3\seller\CouponController@check_coupon` | api, api_lang, seller_api_auth |
| 565 | `GET` | `/api/v3/seller/coupon/customers` | `—` | `RestAPI\v3\seller\CouponController@customers` | api, api_lang, seller_api_auth |
| 566 | `GET` | `/api/v3/seller/shipping/get-shipping-method` | `—` | `RestAPI\v3\seller\shippingController@get_shipping_type` | api, api_lang, seller_api_auth |
| 567 | `GET` | `/api/v3/seller/shipping/selected-shipping-method` | `—` | `RestAPI\v3\seller\shippingController@selected_shipping_type` | api, api_lang, seller_api_auth |
| 568 | `GET` | `/api/v3/seller/shipping/all-category-cost` | `—` | `RestAPI\v3\seller\shippingController@all_category_cost` | api, api_lang, seller_api_auth |
| 569 | `POST` | `/api/v3/seller/shipping/set-category-cost` | `—` | `RestAPI\v3\seller\shippingController@set_category_cost` | api, api_lang, seller_api_auth |
| 570 | `GET` | `/api/v3/seller/shipping-method/list` | `—` | `RestAPI\v3\seller\ShippingMethodController@list` | api, api_lang, seller_api_auth |
| 571 | `POST` | `/api/v3/seller/shipping-method/add` | `—` | `RestAPI\v3\seller\ShippingMethodController@store` | api, api_lang, seller_api_auth |
| 572 | `GET` | `/api/v3/seller/shipping-method/edit/{id}` | `—` | `RestAPI\v3\seller\ShippingMethodController@edit` | api, api_lang, seller_api_auth |
| 573 | `PUT` | `/api/v3/seller/shipping-method/status` | `—` | `RestAPI\v3\seller\ShippingMethodController@status_update` | api, api_lang, seller_api_auth |
| 574 | `PUT` | `/api/v3/seller/shipping-method/update/{id}` | `—` | `RestAPI\v3\seller\ShippingMethodController@update` | api, api_lang, seller_api_auth |
| 575 | `DELETE` | `/api/v3/seller/shipping-method/delete/{id}` | `—` | `RestAPI\v3\seller\ShippingMethodController@delete` | api, api_lang, seller_api_auth |
| 576 | `GET` | `/api/v3/seller/messages/list/{type}` | `—` | `RestAPI\v3\seller\ChatController@list` | api, api_lang, seller_api_auth |
| 577 | `GET` | `/api/v3/seller/messages/get-message/{type}/{id}` | `—` | `RestAPI\v3\seller\ChatController@get_message` | api, api_lang, seller_api_auth |
| 578 | `POST` | `/api/v3/seller/messages/send/{type}` | `—` | `RestAPI\v3\seller\ChatController@send_message` | api, api_lang, seller_api_auth |
| 579 | `POST` | `/api/v3/seller/messages/seen/{type}` | `—` | `RestAPI\v3\seller\ChatController@seenMessage` | api, api_lang, seller_api_auth |
| 580 | `GET` | `/api/v3/seller/messages/search/{type}` | `—` | `RestAPI\v3\seller\ChatController@search` | api, api_lang, seller_api_auth |
| 581 | `GET` | `/api/v3/seller/pos/get-categories` | `—` | `RestAPI\v3\seller\POSController@get_categories` | api, api_lang, seller_api_auth |
| 582 | `GET` | `/api/v3/seller/pos/customers` | `—` | `RestAPI\v3\seller\POSController@customers` | api, api_lang, seller_api_auth |
| 583 | `POST` | `/api/v3/seller/pos/customer-store` | `—` | `RestAPI\v3\seller\POSController@customer_store` | api, api_lang, seller_api_auth |
| 584 | `GET` | `/api/v3/seller/pos/products` | `—` | `RestAPI\v3\seller\POSController@get_product_by_barcode` | api, api_lang, seller_api_auth |
| 585 | `GET` | `/api/v3/seller/pos/product-list` | `—` | `RestAPI\v3\seller\POSController@product_list` | api, api_lang, seller_api_auth |
| 586 | `POST` | `/api/v3/seller/pos/place-order` | `—` | `RestAPI\v3\seller\POSController@place_order` | api, api_lang, seller_api_auth |
| 587 | `GET` | `/api/v3/seller/pos/get-invoice` | `—` | `RestAPI\v3\seller\POSController@get_invoice` | api, api_lang, seller_api_auth |
| 588 | `POST` | `/api/v3/seller/pos/get-tax-amount` | `—` | `RestAPI\v3\seller\POSCartController@getTaxAmountCart` | api, api_lang, seller_api_auth |
| 589 | `GET` | `/api/v3/seller/delivery-man/list` | `—` | `RestAPI\v3\seller\DeliveryManController@list` | api, api_lang, seller_api_auth |
| 590 | `POST` | `/api/v3/seller/delivery-man/store` | `—` | `RestAPI\v3\seller\DeliveryManController@store` | api, api_lang, seller_api_auth |
| 591 | `PUT` | `/api/v3/seller/delivery-man/update/{id}` | `—` | `RestAPI\v3\seller\DeliveryManController@update` | api, api_lang, seller_api_auth |
| 592 | `GET` | `/api/v3/seller/delivery-man/details/{id}` | `—` | `RestAPI\v3\seller\DeliveryManController@details` | api, api_lang, seller_api_auth |
| 593 | `POST` | `/api/v3/seller/delivery-man/status-update` | `—` | `RestAPI\v3\seller\DeliveryManController@status` | api, api_lang, seller_api_auth |
| 594 | `GET` | `/api/v3/seller/delivery-man/delete/{id}` | `—` | `RestAPI\v3\seller\DeliveryManController@delete` | api, api_lang, seller_api_auth |
| 595 | `GET` | `/api/v3/seller/delivery-man/reviews/{id}` | `—` | `RestAPI\v3\seller\DeliveryManController@reviews` | api, api_lang, seller_api_auth |
| 596 | `GET` | `/api/v3/seller/delivery-man/order-list/{id}` | `—` | `RestAPI\v3\seller\DeliveryManController@order_list` | api, api_lang, seller_api_auth |
| 597 | `GET` | `/api/v3/seller/delivery-man/order-status-history/{id}` | `—` | `RestAPI\v3\seller\DeliveryManController@order_status_history` | api, api_lang, seller_api_auth |
| 598 | `GET` | `/api/v3/seller/delivery-man/earning/{id}` | `—` | `RestAPI\v3\seller\DeliveryManController@earning` | api, api_lang, seller_api_auth |
| 599 | `POST` | `/api/v3/seller/delivery-man/cash-receive` | `—` | `RestAPI\v3\seller\DeliveryManCashCollectController@cash_receive` | api, api_lang, seller_api_auth |
| 600 | `GET` | `/api/v3/seller/delivery-man/collect-cash-list/{id}` | `—` | `RestAPI\v3\seller\DeliveryManCashCollectController@list` | api, api_lang, seller_api_auth |
| 601 | `GET` | `/api/v3/seller/delivery-man/withdraw/list` | `—` | `RestAPI\v3\seller\DeliverymanWithdrawController@list` | api, api_lang, seller_api_auth |
| 602 | `GET` | `/api/v3/seller/delivery-man/withdraw/details/{id}` | `—` | `RestAPI\v3\seller\DeliverymanWithdrawController@details` | api, api_lang, seller_api_auth |
| 603 | `PUT` | `/api/v3/seller/delivery-man/withdraw/status-update` | `—` | `RestAPI\v3\seller\DeliverymanWithdrawController@status_update` | api, api_lang, seller_api_auth |
| 604 | `GET` | `/api/v3/seller/delivery-man/emergency-contact/list` | `—` | `RestAPI\v3\seller\EmergencyContactController@list` | api, api_lang, seller_api_auth |
| 605 | `POST` | `/api/v3/seller/delivery-man/emergency-contact/store` | `—` | `RestAPI\v3\seller\EmergencyContactController@store` | api, api_lang, seller_api_auth |
| 606 | `PUT` | `/api/v3/seller/delivery-man/emergency-contact/update` | `—` | `RestAPI\v3\seller\EmergencyContactController@update` | api, api_lang, seller_api_auth |
| 607 | `PUT` | `/api/v3/seller/delivery-man/emergency-contact/status-update` | `—` | `RestAPI\v3\seller\EmergencyContactController@status_update` | api, api_lang, seller_api_auth |
| 608 | `DELETE` | `/api/v3/seller/delivery-man/emergency-contact/delete` | `—` | `RestAPI\v3\seller\EmergencyContactController@destroy` | api, api_lang, seller_api_auth |
| 609 | `GET` | `/api/v3/seller/notification` | `—` | `RestAPI\v3\seller\ShopController@notification_index` | api, api_lang, seller_api_auth |
| 610 | `GET` | `/api/v3/seller/notification/view` | `—` | `RestAPI\v3\seller\ShopController@seller_notification_view` | api, api_lang, seller_api_auth |
| 611 | `GET` | `/api/v3/seller/payment-information/list` | `payment-information.` | `RestAPI\v3\seller\VendorPaymentInfoController@index` | api, api_lang, seller_api_auth |
| 612 | `GET` | `/api/v3/seller/payment-information/withdrawal-method-list` | `payment-information.` | `RestAPI\v3\seller\VendorPaymentInfoController@getWithdrawalMethods` | api, api_lang, seller_api_auth |
| 613 | `POST` | `/api/v3/seller/payment-information/add` | `payment-information.` | `RestAPI\v3\seller\VendorPaymentInfoController@add` | api, api_lang, seller_api_auth |
| 614 | `POST` | `/api/v3/seller/payment-information/update` | `payment-information.` | `RestAPI\v3\seller\VendorPaymentInfoController@update` | api, api_lang, seller_api_auth |
| 615 | `POST` | `/api/v3/seller/payment-information/default` | `payment-information.` | `RestAPI\v3\seller\VendorPaymentInfoController@updateDefault` | api, api_lang, seller_api_auth |
| 616 | `POST` | `/api/v3/seller/payment-information/status` | `payment-information.` | `RestAPI\v3\seller\VendorPaymentInfoController@updateStatus` | api, api_lang, seller_api_auth |
| 617 | `GET` | `/api/v3/seller/payment-information/delete` | `payment-information.` | `RestAPI\v3\seller\VendorPaymentInfoController@delete` | api, api_lang, seller_api_auth |
| 618 | `GET` | `/api/v3/seller/products/{seller_id}/all-products` | `—` | `RestAPI\v3\seller\ProductController@getVendorAllProducts` | api, api_lang |
| 619 | `GET` | `/api/v3/seller/products/{seller_id}/edit-order-all-products` | `—` | `RestAPI\v3\seller\ProductController@editOrderVendorAllProducts` | api, api_lang |
| 620 | `POST` | `/api/v3/seller/product/title-auto-fill` | `v3/seller.product.title-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@titleAutoFill` | api, api_lang, seller_api_auth |
| 621 | `POST` | `/api/v3/seller/product/description-auto-fill` | `v3/seller.product.description-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@descriptionAutoFill` | api, api_lang, seller_api_auth |
| 622 | `POST` | `/api/v3/seller/product/general-setup-auto-fill` | `v3/seller.product.general-setup-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@generalSetupAutoFill` | api, api_lang, seller_api_auth |
| 623 | `POST` | `/api/v3/seller/product/price-others-auto-fill` | `v3/seller.product.price-others-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@pricingAndOthersAutoFill` | api, api_lang, seller_api_auth |
| 624 | `POST` | `/api/v3/seller/product/seo-section-auto-fill` | `v3/seller.product.seo-section-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@productSeoSectionAutoFill` | api, api_lang, seller_api_auth |
| 625 | `POST` | `/api/v3/seller/product/variation-setup-auto-fill` | `v3/seller.product.variation-setup-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@productVariationSetupAutoFill` | api, api_lang, seller_api_auth |
| 626 | `POST` | `/api/v3/seller/product/analyze-image-auto-fill` | `v3/seller.product.analyze-image-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@generateTitleFromImages` | api, api_lang, seller_api_auth |
| 627 | `POST` | `/api/v3/seller/product/generate-title-suggestions` | `v3/seller.product.generate-title-suggestions` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@generateProductTitleSuggestion` | api, api_lang, seller_api_auth |
| 628 | `GET` | `/api/v3/seller/product/generate-limit-check` | `v3/seller.product.` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@generateLimitCheck` | api, api_lang, seller_api_auth |
| 629 | `GET` | `/api/v3/seller/get-vat-tax-report-list` | `—` | `Modules\TaxModule\app\Http\Controllers\Api\v3\VendorTaxReportController@vendorWiseTaxes` | api, seller_api_auth, api_lang |

---

### 🏷️ Domain: Super Admin Panel (639 Endpoints)

| # | Method(s) | URI / Route | Route Name | Controller Action | Security Middleware |
| :-: | :--- | :--- | :--- | :--- | :--- |
| 630 | `GET` | `/admin/pos-sso` | `admin.pos.sso` | `Admin\DashboardController@posSsoRedirect` | web, admin, actch:admin_panel |
| 631 | `GET` | `/admin/whatsapp-crm` | `admin.whatsapp-crm.index` | `Admin\WhatsWhatsAppCrmController@index` | web, admin, actch:admin_panel |
| 632 | `GET` | `/admin/whatsapp-crm/messages/{id}` | `admin.whatsapp-crm.get-messages` | `Admin\WhatsWhatsAppCrmController@getMessages` | web, admin, actch:admin_panel |
| 633 | `POST` | `/admin/whatsapp-crm/send/{id}` | `admin.whatsapp-crm.send-message` | `Admin\WhatsWhatsAppCrmController@sendMessage` | web, admin, actch:admin_panel |
| 634 | `POST` | `/admin/whatsapp-crm/status/{id}` | `admin.whatsapp-crm.update-status` | `Admin\WhatsWhatsAppCrmController@updateStatus` | web, admin, actch:admin_panel |
| 635 | `POST` | `/admin/whatsapp-crm/reassign/{id}` | `admin.whatsapp-crm.reassign` | `Admin\WhatsWhatsAppCrmController@reassignAgent` | web, admin, actch:admin_panel |
| 636 | `GET` | `/admin/whatsapp-crm/broadcasts` | `admin.whatsapp-crm.broadcasts` | `Admin\WhatsWhatsAppBroadcastController@index` | web, admin, actch:admin_panel |
| 637 | `POST` | `/admin/whatsapp-crm/broadcasts/store` | `admin.whatsapp-crm.broadcasts.store` | `Admin\WhatsWhatsAppBroadcastController@store` | web, admin, actch:admin_panel |
| 638 | `GET` | `/admin/whatsapp-crm/ai-settings` | `admin.whatsapp-crm.ai-settings` | `Admin\WhatsWhatsAppAiSettingsController@index` | web, admin, actch:admin_panel |
| 639 | `POST` | `/admin/whatsapp-crm/ai-settings/faq` | `admin.whatsapp-crm.ai-settings.faq-store` | `Admin\WhatsWhatsAppAiSettingsController@storeFaq` | web, admin, actch:admin_panel |
| 640 | `DELETE` | `/admin/whatsapp-crm/ai-settings/faq/{id}` | `admin.whatsapp-crm.ai-settings.faq-delete` | `Admin\WhatsWhatsAppAiSettingsController@deleteFaq` | web, admin, actch:admin_panel |
| 641 | `POST` | `/admin/whatsapp-crm/ai-settings/update` | `admin.whatsapp-crm.ai-settings.update` | `Admin\WhatsWhatsAppAiSettingsController@updateSettings` | web, admin, actch:admin_panel |
| 642 | `GET` | `/admin/component` | `admin.` | `Closure` | web, admin, actch:admin_panel |
| 643 | `GET` | `/admin/component-snippets` | `admin.` | `Closure` | web, admin, actch:admin_panel |
| 644 | `GET` | `/admin/vat-tax` | `admin.` | `Closure` | web, admin, actch:admin_panel |
| 645 | `GET` | `/admin/youtube-callback` | `admin.youtube.callback` | `Admin\Settings\BusinessSettingsController@youtubeCallback` | web, admin, actch:admin_panel |
| 646 | `GET` | `/admin/advanced-search` | `admin.advanced-search` | `Admin\AdvancedSearchController@getSearch` | web, admin, actch:admin_panel |
| 647 | `POST` | `/admin/advanced-search-recent` | `admin.advanced-search-recent` | `Admin\AdvancedSearchController@recentSearch` | web, admin, actch:admin_panel |
| 648 | `GET` | `/admin/dashboard` | `admin.dashboard.index` | `Admin\DashboardController@index` | web, admin, actch:admin_panel |
| 649 | `POST` | `/admin/dashboard/order-status` | `admin.dashboard.order-status` | `Admin\DashboardController@getOrderStatus` | web, admin, actch:admin_panel |
| 650 | `GET` | `/admin/dashboard/earning-statistics` | `admin.dashboard.earning-statistics` | `Admin\DashboardController@getEarningStatistics` | web, admin, actch:admin_panel |
| 651 | `GET` | `/admin/dashboard/order-statistics` | `admin.dashboard.order-statistics` | `Admin\DashboardController@getOrderStatistics` | web, admin, actch:admin_panel |
| 652 | `GET` | `/admin/dashboard/real-time-activities` | `admin.dashboard.real-time-activities` | `Admin\DashboardController@getRealTimeActivities` | web, admin, actch:admin_panel |
| 653 | `GET` | `/admin/logout` | `admin.logout` | `Admin\Auth\LoginController@logout` | web, admin, actch:admin_panel |
| 654 | `GET` | `/admin/profile/update/{id}` | `admin.profile.update` | `Admin\ProfileController@getUpdateView` | web, admin, actch:admin_panel |
| 655 | `POST` | `/admin/profile/update/{id}` | `admin.profile.` | `Admin\ProfileController@update` | web, admin, actch:admin_panel |
| 656 | `PATCH` | `/admin/profile/update/{id}` | `admin.profile.` | `Admin\ProfileController@updatePassword` | web, admin, actch:admin_panel |
| 657 | `GET` | `/admin/products/list/{type}` | `admin.products.list` | `Admin\Product\ProductController@index` | web, admin, actch:admin_panel, module:product_management |
| 658 | `GET` | `/admin/products/add` | `admin.products.add` | `Admin\Product\ProductController@getAddView` | web, admin, actch:admin_panel, module:product_management |
| 659 | `POST` | `/admin/products/add` | `admin.products.store` | `Admin\Product\ProductController@add` | web, admin, actch:admin_panel, module:product_management |
| 660 | `GET` | `/admin/products/view/{addedBy}/{id}` | `admin.products.view` | `Admin\Product\ProductController@getView` | web, admin, actch:admin_panel, module:product_management |
| 661 | `POST` | `/admin/products/sku-combination` | `admin.products.sku-combination` | `Admin\Product\ProductController@getSkuCombinationView` | web, admin, actch:admin_panel, module:product_management |
| 662 | `POST` | `/admin/products/digital-variation-combination` | `admin.products.digital-variation-combination` | `Admin\Product\ProductController@getDigitalVariationCombinationView` | web, admin, actch:admin_panel, module:product_management |
| 663 | `POST` | `/admin/products/digital-variation-file-delete` | `admin.products.digital-variation-file-delete` | `Admin\Product\ProductController@deleteDigitalVariationFile` | web, admin, actch:admin_panel, module:product_management |
| 664 | `POST` | `/admin/products/featured-status` | `admin.products.featured-status` | `Admin\Product\ProductController@updateFeaturedStatus` | web, admin, actch:admin_panel, module:product_management |
| 665 | `GET` | `/admin/products/get-categories` | `admin.products.get-categories` | `Admin\Product\ProductController@getCategories` | web, admin, actch:admin_panel, module:product_management |
| 666 | `POST` | `/admin/products/status-update` | `admin.products.status-update` | `Admin\Product\ProductController@updateStatus` | web, admin, actch:admin_panel, module:product_management |
| 667 | `GET` | `/admin/products/barcode/{id}` | `admin.products.barcode` | `Admin\Product\ProductController@getBarcodeView` | web, admin, actch:admin_panel, module:product_management |
| 668 | `GET` | `/admin/products/export-excel/{type}` | `admin.products.export-excel` | `Admin\Product\ProductController@exportList` | web, admin, actch:admin_panel, module:product_management |
| 669 | `GET` | `/admin/products/stock-limit-list/{type}` | `admin.products.stock-limit-list` | `Admin\Product\ProductController@getStockLimitListView` | web, admin, actch:admin_panel, module:product_management |
| 670 | `DELETE` | `/admin/products/delete/{id}` | `admin.products.delete` | `Admin\Product\ProductController@delete` | web, admin, actch:admin_panel, module:product_management |
| 671 | `GET` | `/admin/products/update/{id}` | `admin.products.update` | `Admin\Product\ProductController@getUpdateView` | web, admin, actch:admin_panel, module:product_management |
| 672 | `POST` | `/admin/products/update/{id}` | `admin.products.` | `Admin\Product\ProductController@update` | web, admin, actch:admin_panel, module:product_management |
| 673 | `POST` | `/admin/products/update-product-images/{id}` | `admin.products.update-product-images` | `Admin\Product\ProductController@updateProductImages` | web, admin, actch:admin_panel, module:product_management |
| 674 | `GET` | `/admin/products/delete-image` | `admin.products.delete-image` | `Admin\Product\ProductController@deleteImage` | web, admin, actch:admin_panel, module:product_management |
| 675 | `GET` | `/admin/products/get-variations` | `admin.products.get-variations` | `Admin\Product\ProductController@getVariations` | web, admin, actch:admin_panel, module:product_management |
| 676 | `POST` | `/admin/products/update-quantity` | `admin.products.update-quantity` | `Admin\Product\ProductController@updateQuantity` | web, admin, actch:admin_panel, module:product_management |
| 677 | `GET` | `/admin/products/bulk-import` | `admin.products.bulk-import` | `Admin\Product\ProductController@getBulkImportView` | web, admin, actch:admin_panel, module:product_management |
| 678 | `POST` | `/admin/products/bulk-import` | `admin.products.` | `Admin\Product\ProductController@importBulkProduct` | web, admin, actch:admin_panel, module:product_management |
| 679 | `GET` | `/admin/products/updated-product-list` | `admin.products.updated-product-list` | `Admin\Product\ProductController@updatedProductList` | web, admin, actch:admin_panel, module:product_management |
| 680 | `POST` | `/admin/products/updated-shipping` | `admin.products.updated-shipping` | `Admin\Product\ProductController@updatedShipping` | web, admin, actch:admin_panel, module:product_management |
| 681 | `POST` | `/admin/products/deny` | `admin.products.deny` | `Admin\Product\ProductController@deny` | web, admin, actch:admin_panel, module:product_management |
| 682 | `POST` | `/admin/products/approve-status` | `admin.products.approve-status` | `Admin\Product\ProductController@approveStatus` | web, admin, actch:admin_panel, module:product_management |
| 683 | `GET` | `/admin/products/search` | `admin.products.search-product` | `Admin\Product\ProductController@getSearchedProductsView` | web, admin, actch:admin_panel, module:product_management |
| 684 | `GET` | `/admin/products/search-all-product` | `admin.products.search-all-type-product` | `Admin\Product\ProductController@getSearchedAllProductsView` | web, admin, actch:admin_panel, module:product_management |
| 685 | `GET` | `/admin/products/product-gallery` | `admin.products.product-gallery` | `Admin\Product\ProductController@getProductGalleryView` | web, admin, actch:admin_panel, module:product_management |
| 686 | `GET` | `/admin/products/stock-limit-status/{type}` | `admin.products.stock-limit-status` | `Admin\Product\ProductController@getStockLimitStatus` | web, admin, actch:admin_panel, module:product_management |
| 687 | `POST` | `/admin/products/delete-preview-file` | `admin.products.delete-preview-file` | `Admin\Product\ProductController@deletePreviewFile` | web, admin, actch:admin_panel, module:product_management |
| 688 | `GET` | `/admin/products/request-restock-list` | `admin.products.request-restock-list` | `Admin\Product\ProductController@getRequestRestockListView` | web, admin, actch:admin_panel, module:product_management |
| 689 | `GET` | `/admin/products/export-restock` | `admin.products.restock-export` | `Admin\Product\ProductController@exportRestockList` | web, admin, actch:admin_panel, module:product_management |
| 690 | `DELETE` | `/admin/products/restock-delete/{id}` | `admin.products.restock-delete` | `Admin\Product\ProductController@deleteRestock` | web, admin, actch:admin_panel, module:product_management |
| 691 | `GET` | `/admin/products/product-feeds` | `admin.products.product-feeds` | `ProductFeedExportController@index` | web, admin, actch:admin_panel, module:product_management |
| 692 | `POST` | `/admin/products/product-feeds/regenerate-token` | `admin.products.product-feeds.regenerate-token` | `ProductFeedExportController@regenerateToken` | web, admin, actch:admin_panel, module:product_management |
| 693 | `GET` | `/admin/products/multiple-product-details` | `admin.products.multiple-product-details` | `Admin\Product\ProductController@getMultipleProductDetailsView` | web, admin, actch:admin_panel |
| 694 | `GET` | `/admin/orders/list/{status}` | `admin.orders.list` | `Admin\Order\OrderController@index` | web, admin, actch:admin_panel, module:order_management |
| 695 | `GET` | `/admin/orders/export-excel/{status}` | `admin.orders.export-excel` | `Admin\Order\OrderController@exportList` | web, admin, actch:admin_panel, module:order_management |
| 696 | `GET` | `/admin/orders/generate-invoice/{id}` | `admin.orders.generate-invoice` | `Admin\Order\OrderController@generateInvoice` | web, admin, actch:admin_panel, module:order_management |
| 697 | `GET` | `/admin/orders/details/{id}` | `admin.orders.details` | `Admin\Order\OrderController@getView` | web, admin, actch:admin_panel, module:order_management |
| 698 | `POST` | `/admin/orders/address-update` | `admin.orders.address-update` | `Admin\Order\OrderController@updateAddress` | web, admin, actch:admin_panel, module:order_management |
| 699 | `POST` | `/admin/orders/update-deliver-info` | `admin.orders.update-deliver-info` | `Admin\Order\OrderController@updateDeliverInfo` | web, admin, actch:admin_panel, module:order_management |
| 700 | `GET` | `/admin/orders/add-delivery-man/{order_id}/{d_man_id}` | `admin.orders.add-delivery-man` | `Admin\Order\OrderController@addDeliveryMan` | web, admin, actch:admin_panel, module:order_management |
| 701 | `POST` | `/admin/orders/amount-date-update` | `admin.orders.amount-date-update` | `Admin\Order\OrderController@updateAmountDate` | web, admin, actch:admin_panel, module:order_management |
| 702 | `GET` | `/admin/orders/customers` | `admin.orders.customers` | `Admin\Order\OrderController@getCustomers` | web, admin, actch:admin_panel, module:order_management |
| 703 | `POST` | `/admin/orders/payment-status` | `admin.orders.payment-status` | `Admin\Order\OrderController@updatePaymentStatus` | web, admin, actch:admin_panel, module:order_management |
| 704 | `GET` | `/admin/orders/inhouse-order-filter` | `admin.orders.inhouse-order-filter` | `Admin\Order\OrderController@filterInHouseOrder` | web, admin, actch:admin_panel, module:order_management |
| 705 | `POST` | `/admin/orders/digital-file-upload-after-sell` | `admin.orders.digital-file-upload-after-sell` | `Admin\Order\OrderController@uploadDigitalFileAfterSell` | web, admin, actch:admin_panel, module:order_management |
| 706 | `POST` | `/admin/orders/status` | `admin.orders.status` | `Admin\Order\OrderController@updateStatus` | web, admin, actch:admin_panel, module:order_management |
| 707 | `POST` | `/admin/orders/customer-return-amount` | `admin.orders.customer-return-amount` | `Admin\Order\OrderController@orderReturnAmountToCustomer` | web, admin, actch:admin_panel, module:order_management |
| 708 | `POST` | `/admin/orders/customer-due-amount` | `admin.orders.customer-due-amount` | `Admin\Order\OrderController@orderDueAmountSwitchToCOD` | web, admin, actch:admin_panel, module:order_management |
| 709 | `POST` | `/admin/orders/customer-due-amount-mark-as-paid` | `admin.orders.customer-due-amount-mark-as-paid` | `Admin\Order\OrderController@orderDueAmountMarkAsPaid` | web, admin, actch:admin_panel, module:order_management |
| 710 | `GET` | `/admin/orders/search-for-edit-order-product` | `admin.orders.search-for-edit-order-product` | `Admin\Order\OrderEditController@getSearchEditOrderProductsView` | web, admin, actch:admin_panel, module:order_management |
| 711 | `POST` | `/admin/orders/edit-order-product-modal-view` | `admin.orders.edit-order-product-modal-view` | `Admin\Order\OrderEditController@getEditOrderProductModalView` | web, admin, actch:admin_panel, module:order_management |
| 712 | `POST` | `/admin/orders/edit-order-product-add` | `admin.orders.edit-order-product-add` | `Admin\Order\OrderEditController@addEditOrderProduct` | web, admin, actch:admin_panel, module:order_management |
| 713 | `POST` | `/admin/orders/edit-order-product-variant-price` | `admin.orders.edit-order-product-variant-price` | `Admin\Order\OrderEditController@checkProductVariantPrice` | web, admin, actch:admin_panel, module:order_management |
| 714 | `POST` | `/admin/orders/edit-order-product-list-update` | `admin.orders.edit-order-product-list-update` | `Admin\Order\OrderEditController@updateEditOrderProductList` | web, admin, actch:admin_panel, module:order_management |
| 715 | `POST` | `/admin/orders/edit-order-product-remove` | `admin.orders.edit-order-product-remove` | `Admin\Order\OrderEditController@removeEditOrderProduct` | web, admin, actch:admin_panel, module:order_management |
| 716 | `POST` | `/admin/orders/edit-order-generate` | `admin.orders.edit-order-generate` | `Admin\Order\OrderEditController@generateEditOrderByProductList` | web, admin, actch:admin_panel, module:order_management |
| 717 | `GET` | `/admin/attribute/view` | `admin.attribute.view` | `Admin\Product\AttributeController@index` | web, admin, actch:admin_panel, module:product_management |
| 718 | `POST` | `/admin/attribute/store` | `admin.attribute.store` | `Admin\Product\AttributeController@add` | web, admin, actch:admin_panel, module:product_management |
| 719 | `GET` | `/admin/attribute/update/{id}` | `admin.attribute.update` | `Admin\Product\AttributeController@getUpdateView` | web, admin, actch:admin_panel, module:product_management |
| 720 | `GET` | `/admin/attribute/get-translation-data/{id}` | `admin.attribute.translation-data` | `Admin\Product\AttributeController@getTranslationData` | web, admin, actch:admin_panel, module:product_management |
| 721 | `POST` | `/admin/attribute/update/{id}` | `admin.attribute.` | `Admin\Product\AttributeController@update` | web, admin, actch:admin_panel, module:product_management |
| 722 | `POST` | `/admin/attribute/delete` | `admin.attribute.delete` | `Admin\Product\AttributeController@delete` | web, admin, actch:admin_panel, module:product_management |
| 723 | `GET` | `/admin/brand/list` | `admin.brand.list` | `Admin\Product\BrandController@index` | web, admin, actch:admin_panel, module:product_management |
| 724 | `GET` | `/admin/brand/add-new` | `admin.brand.add-new` | `Admin\Product\BrandController@getAddView` | web, admin, actch:admin_panel, module:product_management |
| 725 | `POST` | `/admin/brand/add-new` | `admin.brand.` | `Admin\Product\BrandController@add` | web, admin, actch:admin_panel, module:product_management |
| 726 | `GET` | `/admin/brand/update/{id}` | `admin.brand.update` | `Admin\Product\BrandController@getUpdateView` | web, admin, actch:admin_panel, module:product_management |
| 727 | `POST` | `/admin/brand/update/{id}` | `admin.brand.` | `Admin\Product\BrandController@update` | web, admin, actch:admin_panel, module:product_management |
| 728 | `POST` | `/admin/brand/delete` | `admin.brand.delete` | `Admin\Product\BrandController@delete` | web, admin, actch:admin_panel, module:product_management |
| 729 | `GET` | `/admin/brand/export` | `admin.brand.export` | `Admin\Product\BrandController@exportList` | web, admin, actch:admin_panel, module:product_management |
| 730 | `POST` | `/admin/brand/status-update` | `admin.brand.status-update` | `Admin\Product\BrandController@updateStatus` | web, admin, actch:admin_panel, module:product_management |
| 731 | `POST` | `/admin/brand/load-more-brands` | `admin.brand.load-more-brands` | `Admin\Product\BrandController@loadMoreBrands` | web, admin, actch:admin_panel, module:product_management |
| 732 | `GET` | `/admin/category/view` | `admin.category.view` | `Admin\Product\CategoryController@index` | web, admin, actch:admin_panel, module:product_management |
| 733 | `POST` | `/admin/category/add-new` | `admin.category.store` | `Admin\Product\CategoryController@add` | web, admin, actch:admin_panel, module:product_management |
| 734 | `GET` | `/admin/category/update` | `admin.category.update` | `Admin\Product\CategoryController@getUpdateView` | web, admin, actch:admin_panel, module:product_management |
| 735 | `POST` | `/admin/category/update` | `admin.category.` | `Admin\Product\CategoryController@update` | web, admin, actch:admin_panel, module:product_management |
| 736 | `POST` | `/admin/category/delete` | `admin.category.delete` | `Admin\Product\CategoryController@delete` | web, admin, actch:admin_panel, module:product_management |
| 737 | `POST` | `/admin/category/status` | `admin.category.status` | `Admin\Product\CategoryController@updateStatus` | web, admin, actch:admin_panel, module:product_management |
| 738 | `GET` | `/admin/category/export` | `admin.category.export` | `Admin\Product\CategoryController@getExportList` | web, admin, actch:admin_panel, module:product_management |
| 739 | `GET` | `/admin/sub-category/view` | `admin.sub-category.view` | `Admin\Product\SubCategoryController@index` | web, admin, actch:admin_panel, module:product_management |
| 740 | `POST` | `/admin/sub-category/store` | `admin.sub-category.store` | `Admin\Product\SubCategoryController@add` | web, admin, actch:admin_panel, module:product_management |
| 741 | `POST` | `/admin/sub-category/update/{id}` | `admin.sub-category.update` | `Admin\Product\SubCategoryController@update` | web, admin, actch:admin_panel, module:product_management |
| 742 | `POST` | `/admin/sub-category/delete` | `admin.sub-category.delete` | `Admin\Product\SubCategoryController@delete` | web, admin, actch:admin_panel, module:product_management |
| 743 | `GET` | `/admin/sub-category/export` | `admin.sub-category.export` | `Admin\Product\SubCategoryController@getExportList` | web, admin, actch:admin_panel, module:product_management |
| 744 | `POST` | `/admin/sub-category/load-more-categories` | `admin.sub-category.load-more-categories` | `Admin\Product\SubCategoryController@loadMoreCategories` | web, admin, actch:admin_panel, module:product_management |
| 745 | `GET` | `/admin/sub-sub-category/view` | `admin.sub-sub-category.view` | `Admin\Product\SubSubCategoryController@index` | web, admin, actch:admin_panel, module:product_management |
| 746 | `POST` | `/admin/sub-sub-category/store` | `admin.sub-sub-category.store` | `Admin\Product\SubSubCategoryController@add` | web, admin, actch:admin_panel, module:product_management |
| 747 | `POST` | `/admin/sub-sub-category/update/{id}` | `admin.sub-sub-category.` | `Admin\Product\SubSubCategoryController@update` | web, admin, actch:admin_panel, module:product_management |
| 748 | `POST` | `/admin/sub-sub-category/delete` | `admin.sub-sub-category.delete` | `Admin\Product\SubSubCategoryController@delete` | web, admin, actch:admin_panel, module:product_management |
| 749 | `POST` | `/admin/sub-sub-category/get-sub-category` | `admin.sub-sub-category.getSubCategory` | `Admin\Product\SubSubCategoryController@getSubCategory` | web, admin, actch:admin_panel, module:product_management |
| 750 | `GET` | `/admin/sub-sub-category/export` | `admin.sub-sub-category.export` | `Admin\Product\SubSubCategoryController@getExportList` | web, admin, actch:admin_panel, module:product_management |
| 751 | `GET` | `/admin/category-specifications` | `admin.category-specifications.index` | `Admin\Product\CategorySpecificationController@index` | web, admin, actch:admin_panel, module:product_management |
| 752 | `POST` | `/admin/category-specifications/store` | `admin.category-specifications.store` | `Admin\Product\CategorySpecificationController@store` | web, admin, actch:admin_panel, module:product_management |
| 753 | `POST` | `/admin/category-specifications/update/{id}` | `admin.category-specifications.update` | `Admin\Product\CategorySpecificationController@update` | web, admin, actch:admin_panel, module:product_management |
| 754 | `DELETE` | `/admin/category-specifications/delete/{id}` | `admin.category-specifications.delete` | `Admin\Product\CategorySpecificationController@delete` | web, admin, actch:admin_panel, module:product_management |
| 755 | `POST` | `/admin/category-specifications/status` | `admin.category-specifications.status` | `Admin\Product\CategorySpecificationController@status` | web, admin, actch:admin_panel, module:product_management |
| 756 | `GET` | `/admin/category-specifications/get-by-category/{category_id}` | `admin.category-specifications.get-by-category` | `Admin\Product\CategorySpecificationController@getByCategoryAjax` | web, admin, actch:admin_panel, module:product_management |
| 757 | `POST` | `/admin/category-specifications/ai-suggest-specs` | `admin.category-specifications.ai-suggest-specs` | `Admin\Product\CategorySpecificationController@aiSuggestSpecs` | web, admin, actch:admin_panel, module:product_management |
| 758 | `GET` | `/admin/banner/list` | `admin.banner.list` | `Admin\Promotion\BannerController@index` | web, admin, actch:admin_panel, module:promotion_management |
| 759 | `POST` | `/admin/banner/add` | `admin.banner.store` | `Admin\Promotion\BannerController@add` | web, admin, actch:admin_panel, module:promotion_management |
| 760 | `POST` | `/admin/banner/delete` | `admin.banner.delete` | `Admin\Promotion\BannerController@delete` | web, admin, actch:admin_panel, module:promotion_management |
| 761 | `POST` | `/admin/banner/status` | `admin.banner.status` | `Admin\Promotion\BannerController@updateStatus` | web, admin, actch:admin_panel, module:promotion_management |
| 762 | `GET` | `/admin/banner/update/{id}` | `admin.banner.update` | `Admin\Promotion\BannerController@getUpdateView` | web, admin, actch:admin_panel, module:promotion_management |
| 763 | `POST` | `/admin/banner/update/{id}` | `admin.banner.` | `Admin\Promotion\BannerController@update` | web, admin, actch:admin_panel, module:promotion_management |
| 764 | `GET` | `/admin/customer/list` | `admin.customer.list` | `Admin\Customer\CustomerController@index` | web, admin, actch:admin_panel, module:user_section |
| 765 | `GET` | `/admin/customer/view/{user_id}` | `admin.customer.view` | `Admin\Customer\CustomerController@getView` | web, admin, actch:admin_panel, module:user_section |
| 766 | `GET` | `/admin/customer/order-list-export/{user_id}` | `admin.customer.order-list-export` | `Admin\Customer\CustomerController@exportOrderList` | web, admin, actch:admin_panel, module:user_section |
| 767 | `POST` | `/admin/customer/status-update` | `admin.customer.status-update` | `Admin\Customer\CustomerController@updateStatus` | web, admin, actch:admin_panel, module:user_section |
| 768 | `DELETE` | `/admin/customer/delete/{id}` | `admin.customer.delete` | `Admin\Customer\CustomerController@deleteCustomer` | web, admin, actch:admin_panel, module:user_section |
| 769 | `GET` | `/admin/customer/subscriber-list` | `admin.customer.subscriber-list` | `Admin\Customer\CustomerController@getSubscriberListView` | web, admin, actch:admin_panel, module:user_section |
| 770 | `GET` | `/admin/customer/subscriber-list/export` | `admin.customer.subscriber-list.export` | `Admin\Customer\CustomerController@exportSubscribersList` | web, admin, actch:admin_panel, module:user_section |
| 771 | `GET` | `/admin/customer/export` | `admin.customer.export` | `Admin\Customer\CustomerController@exportList` | web, admin, actch:admin_panel, module:user_section |
| 772 | `GET` | `/admin/customer/customer-list-search` | `admin.customer.customer-list-search` | `Admin\Customer\CustomerController@getCustomerList` | web, admin, actch:admin_panel, module:user_section |
| 773 | `GET` | `/admin/customer/customer-list-without-all-customer` | `admin.customer.customer-list-without-all-customer` | `Admin\Customer\CustomerController@getCustomerListWithoutAllCustomerName` | web, admin, actch:admin_panel, module:user_section |
| 774 | `POST` | `/admin/customer/add` | `admin.customer.add` | `Admin\Customer\CustomerController@add` | web, admin, actch:admin_panel, module:user_section |
| 775 | `POST` | `/admin/customer/profile-update` | `admin.customer.profile-update` | `Admin\Customer\CustomerController@updateProfile` | web, admin, actch:admin_panel, module:user_section |
| 776 | `POST` | `/admin/customer/ban` | `admin.customer.ban` | `Admin\Customer\BlacklistController@banCustomer` | web, admin, actch:admin_panel, module:user_section |
| 777 | `POST` | `/admin/customer/unban` | `admin.customer.unban` | `Admin\Customer\BlacklistController@unbanCustomer` | web, admin, actch:admin_panel, module:user_section |
| 778 | `POST` | `/admin/customer/verify-receipt/{id}` | `admin.customer.verify-receipt` | `Admin\Customer\BlacklistController@verifyReceipt` | web, admin, actch:admin_panel, module:user_section |
| 779 | `POST` | `/admin/customer/reject-receipt/{id}` | `admin.customer.reject-receipt` | `Admin\Customer\BlacklistController@rejectReceipt` | web, admin, actch:admin_panel, module:user_section |
| 780 | `POST` | `/admin/customer/credit-wallet/{id}` | `admin.customer.credit-wallet` | `Admin\Customer\BlacklistController@approveWalletReceipt` | web, admin, actch:admin_panel, module:user_section |
| 781 | `POST` | `/admin/customer/add-memory` | `admin.customer.add-memory` | `Admin\Customer\BlacklistController@addCustomerMemoryPoint` | web, admin, actch:admin_panel, module:user_section |
| 782 | `GET` | `/admin/customer/wallet/report` | `admin.customer.wallet.report` | `Admin\Customer\CustomerWalletController@index` | web, admin, actch:admin_panel, module:user_section |
| 783 | `POST` | `/admin/customer/wallet/add-fund` | `admin.customer.wallet.add-fund` | `Admin\Customer\CustomerWalletController@addFund` | web, admin, actch:admin_panel, module:user_section |
| 784 | `GET` | `/admin/customer/wallet/export` | `admin.customer.wallet.export` | `Admin\Customer\CustomerWalletController@exportList` | web, admin, actch:admin_panel, module:user_section |
| 785 | `GET` | `/admin/customer/wallet/bonus-setup` | `admin.customer.wallet.bonus-setup` | `Admin\Customer\CustomerWalletController@getBonusSetupView` | web, admin, actch:admin_panel, module:user_section |
| 786 | `POST` | `/admin/customer/wallet/bonus-setup` | `admin.customer.wallet.` | `Admin\Customer\CustomerWalletController@addBonusSetup` | web, admin, actch:admin_panel, module:user_section |
| 787 | `POST` | `/admin/customer/wallet/bonus-setup-update` | `admin.customer.wallet.bonus-setup-update` | `Admin\Customer\CustomerWalletController@update` | web, admin, actch:admin_panel, module:user_section |
| 788 | `POST` | `/admin/customer/wallet/bonus-setup-status` | `admin.customer.wallet.bonus-setup-status` | `Admin\Customer\CustomerWalletController@updateStatus` | web, admin, actch:admin_panel, module:user_section |
| 789 | `GET` | `/admin/customer/wallet/bonus-setup/edit/{id}` | `admin.customer.wallet.bonus-setup-edit` | `Admin\Customer\CustomerWalletController@getUpdateView` | web, admin, actch:admin_panel, module:user_section |
| 790 | `DELETE` | `/admin/customer/wallet/bonus-setup-delete` | `admin.customer.wallet.bonus-setup-delete` | `Admin\Customer\CustomerWalletController@deleteBonus` | web, admin, actch:admin_panel, module:user_section |
| 791 | `GET` | `/admin/customer/loyalty/report` | `admin.customer.loyalty.report` | `Admin\Customer\CustomerLoyaltyController@index` | web, admin, actch:admin_panel, module:user_section |
| 792 | `GET` | `/admin/customer/loyalty/export` | `admin.customer.loyalty.export` | `Admin\Customer\CustomerLoyaltyController@exportList` | web, admin, actch:admin_panel, module:user_section |
| 793 | `GET` | `/admin/report/inhouse-product-sale` | `admin.report.inhouse-product-sale` | `Admin\InhouseProductSaleController@index` | web, admin, actch:admin_panel, module:report |
| 794 | `GET` | `/admin/vendors/list` | `admin.vendors.vendor-list` | `Admin\Vendor\VendorController@index` | web, admin, actch:admin_panel, module:user_section |
| 795 | `GET` | `/admin/vendors/add` | `admin.vendors.add` | `Admin\Vendor\VendorController@getAddView` | web, admin, actch:admin_panel, module:user_section |
| 796 | `POST` | `/admin/vendors/add` | `admin.vendors.` | `Admin\Vendor\VendorController@add` | web, admin, actch:admin_panel, module:user_section |
| 797 | `GET` | `/admin/vendors/order-list-export/{vendor_id}` | `admin.vendors.order-list-export` | `Admin\Vendor\VendorController@exportOrderList` | web, admin, actch:admin_panel, module:user_section |
| 798 | `POST` | `/admin/vendors/status` | `admin.vendors.updateStatus` | `Admin\Vendor\VendorController@updateStatus` | web, admin, actch:admin_panel, module:user_section |
| 799 | `GET` | `/admin/vendors/export` | `admin.vendors.export` | `Admin\Vendor\VendorController@exportList` | web, admin, actch:admin_panel, module:user_section |
| 800 | `POST` | `/admin/vendors/sales-commission-update/{id}` | `admin.vendors.sales-commission-update` | `Admin\Vendor\VendorController@updateSalesCommission` | web, admin, actch:admin_panel, module:user_section |
| 801 | `GET` | `/admin/vendors/order-details/{order_id}/{vendor_id}` | `admin.vendors.order-details` | `Admin\Vendor\VendorController@getOrderDetailsView` | web, admin, actch:admin_panel, module:user_section |
| 802 | `GET` | `/admin/vendors/view/{id}/{tab?}` | `admin.vendors.view` | `Admin\Vendor\VendorController@getView` | web, admin, actch:admin_panel, module:user_section |
| 803 | `POST` | `/admin/vendors/update_setting/{id}` | `admin.vendors.update-setting` | `Admin\Vendor\VendorController@updateSetting` | web, admin, actch:admin_panel, module:user_section |
| 804 | `GET` | `/admin/vendors/withdraw-list` | `admin.vendors.withdraw_list` | `Admin\Vendor\VendorController@getWithdrawListView` | web, admin, actch:admin_panel, module:user_section |
| 805 | `GET` | `/admin/vendors/withdraw-list-export-excel` | `admin.vendors.withdraw-list-export-excel` | `Admin\Vendor\VendorController@exportWithdrawList` | web, admin, actch:admin_panel, module:user_section |
| 806 | `GET` | `/admin/vendors/withdraw-view/{withdrawId}/{vendorId}` | `admin.vendors.withdraw_view` | `Admin\Vendor\VendorController@getWithdrawView` | web, admin, actch:admin_panel, module:user_section |
| 807 | `POST` | `/admin/vendors/withdraw-status/{id}` | `admin.vendors.withdraw_status` | `Admin\Vendor\VendorController@withdrawStatus` | web, admin, actch:admin_panel, module:user_section |
| 808 | `POST` | `/admin/vendors/kyc-status/{id}` | `admin.vendors.kyc-status` | `Admin\Vendor\VendorController@updateKycStatus` | web, admin, actch:admin_panel, module:user_section |
| 809 | `POST` | `/admin/vendors/load-more-stores` | `admin.vendors.load-more-stores` | `Admin\Vendor\VendorController@loadMoreStores` | web, admin, actch:admin_panel, module:user_section |
| 810 | `GET` | `/admin/vendors/marketplace-applications` | `admin.vendors.marketplace-applications` | `Admin\Vendor\MarketplaceApprovalController@index` | web, admin, actch:admin_panel, module:user_section |
| 811 | `POST` | `/admin/vendors/marketplace-applications/approve/{id}` | `admin.vendors.marketplace-applications.approve` | `Admin\Vendor\MarketplaceApprovalController@approve` | web, admin, actch:admin_panel, module:user_section |
| 812 | `POST` | `/admin/vendors/marketplace-applications/reject/{id}` | `admin.vendors.marketplace-applications.reject` | `Admin\Vendor\MarketplaceApprovalController@reject` | web, admin, actch:admin_panel, module:user_section |
| 813 | `GET` | `/admin/vendors/withdraw-method/list` | `admin.vendors.withdraw-method.list` | `Admin\Vendor\WithdrawalMethodController@index` | web, admin, actch:admin_panel, module:user_section |
| 814 | `GET` | `/admin/vendors/withdraw-method/add` | `admin.vendors.withdraw-method.add` | `Admin\Vendor\WithdrawalMethodController@getAddView` | web, admin, actch:admin_panel, module:user_section |
| 815 | `POST` | `/admin/vendors/withdraw-method/add` | `admin.vendors.withdraw-method.` | `Admin\Vendor\WithdrawalMethodController@add` | web, admin, actch:admin_panel, module:user_section |
| 816 | `DELETE` | `/admin/vendors/withdraw-method/delete/{id}` | `admin.vendors.withdraw-method.delete` | `Admin\Vendor\WithdrawalMethodController@delete` | web, admin, actch:admin_panel, module:user_section |
| 817 | `POST` | `/admin/vendors/withdraw-method/default-status-update` | `admin.vendors.withdraw-method.default-status` | `Admin\Vendor\WithdrawalMethodController@updateDefaultStatus` | web, admin, actch:admin_panel, module:user_section |
| 818 | `POST` | `/admin/vendors/withdraw-method/status-update` | `admin.vendors.withdraw-method.status-update` | `Admin\Vendor\WithdrawalMethodController@updateStatus` | web, admin, actch:admin_panel, module:user_section |
| 819 | `GET` | `/admin/vendors/withdraw-method/update/{id}` | `admin.vendors.withdraw-method.edit` | `Admin\Vendor\WithdrawalMethodController@getUpdateView` | web, admin, actch:admin_panel, module:user_section |
| 820 | `POST` | `/admin/vendors/withdraw-method/update` | `admin.vendors.withdraw-method.update` | `Admin\Vendor\WithdrawalMethodController@update` | web, admin, actch:admin_panel, module:user_section |
| 821 | `GET` | `/admin/employee/list` | `admin.employee.list` | `Admin\Employee\EmployeeController@index` | web, admin, actch:admin_panel, module:user_section |
| 822 | `GET` | `/admin/employee/add` | `admin.employee.add-new` | `Admin\Employee\EmployeeController@getAddView` | web, admin, actch:admin_panel, module:user_section |
| 823 | `POST` | `/admin/employee/add` | `admin.employee.add-new-post` | `Admin\Employee\EmployeeController@add` | web, admin, actch:admin_panel, module:user_section |
| 824 | `GET` | `/admin/employee/export` | `admin.employee.export` | `Admin\Employee\EmployeeController@exportList` | web, admin, actch:admin_panel, module:user_section |
| 825 | `GET` | `/admin/employee/view/{id}` | `admin.employee.view` | `Admin\Employee\EmployeeController@getView` | web, admin, actch:admin_panel, module:user_section |
| 826 | `GET` | `/admin/employee/update/{id}` | `admin.employee.update` | `Admin\Employee\EmployeeController@getUpdateView` | web, admin, actch:admin_panel, module:user_section |
| 827 | `POST` | `/admin/employee/update/{id}` | `admin.employee.` | `Admin\Employee\EmployeeController@update` | web, admin, actch:admin_panel, module:user_section |
| 828 | `POST` | `/admin/employee/status` | `admin.employee.status` | `Admin\Employee\EmployeeController@updateStatus` | web, admin, actch:admin_panel, module:user_section |
| 829 | `GET` | `/admin/custom-role/add` | `admin.custom-role.create` | `Admin\Employee\CustomRoleController@index` | web, admin, actch:admin_panel, module:user_section |
| 830 | `POST` | `/admin/custom-role/add` | `admin.custom-role.store` | `Admin\Employee\CustomRoleController@add` | web, admin, actch:admin_panel, module:user_section |
| 831 | `GET` | `/admin/custom-role/update/{id}` | `admin.custom-role.update` | `Admin\Employee\CustomRoleController@getUpdateView` | web, admin, actch:admin_panel, module:user_section |
| 832 | `POST` | `/admin/custom-role/update/{id}` | `admin.custom-role.` | `Admin\Employee\CustomRoleController@update` | web, admin, actch:admin_panel, module:user_section |
| 833 | `POST` | `/admin/custom-role/employee-role-status` | `admin.custom-role.employee-role-status` | `Admin\Employee\CustomRoleController@updateStatus` | web, admin, actch:admin_panel, module:user_section |
| 834 | `POST` | `/admin/custom-role/delete` | `admin.custom-role.delete` | `Admin\Employee\CustomRoleController@delete` | web, admin, actch:admin_panel, module:user_section |
| 835 | `GET` | `/admin/custom-role/export` | `admin.custom-role.export` | `Admin\Employee\CustomRoleController@exportList` | web, admin, actch:admin_panel, module:user_section |
| 836 | `GET` | `/admin/report/transaction/refund-transaction-list` | `admin.report.transaction.refund-transaction-list` | `Admin\Report\RefundTransactionController@index` | web, admin, actch:admin_panel, module:report |
| 837 | `GET` | `/admin/report/transaction/refund-transaction-export` | `admin.report.transaction.refund-transaction-export` | `Admin\Report\RefundTransactionController@exportRefundTransaction` | web, admin, actch:admin_panel, module:report |
| 838 | `GET` | `/admin/report/transaction/refund-transaction-summary-pdf` | `admin.report.transaction.refund-transaction-summary-pdf` | `Admin\Report\RefundTransactionController@getRefundTransactionPDF` | web, admin, actch:admin_panel, module:report |
| 839 | `GET` | `/admin/report/earning` | `admin.report.earning` | `Admin\ReportController@admin_earning` | web, admin, actch:admin_panel, module:report |
| 840 | `GET` | `/admin/report/admin-earning` | `admin.report.admin-earning` | `Admin\ReportController@admin_earning` | web, admin, actch:admin_panel, module:report |
| 841 | `GET` | `/admin/report/admin-earning-excel-export` | `admin.report.admin-earning-excel-export` | `Admin\ReportController@exportAdminEarning` | web, admin, actch:admin_panel, module:report |
| 842 | `POST` | `/admin/report/admin-earning-duration-download-pdf` | `admin.report.admin-earning-duration-download-pdf` | `Admin\ReportController@admin_earning_duration_download_pdf` | web, admin, actch:admin_panel, module:report |
| 843 | `GET` | `/admin/report/vendor-earning` | `admin.report.vendor-earning` | `Admin\ReportController@vendorEarning` | web, admin, actch:admin_panel, module:report |
| 844 | `GET` | `/admin/report/vendor-earning-excel-export` | `admin.report.vendor-earning-excel-export` | `Admin\ReportController@exportVendorEarning` | web, admin, actch:admin_panel, module:report |
| 845 | `GET|POST|PUT|PATCH|DELETE|OPTIONS` | `/admin/report/set-date` | `admin.report.set-date` | `Admin\ReportController@set_date` | web, admin, actch:admin_panel, module:report |
| 846 | `GET` | `/admin/report/order` | `admin.report.order` | `Admin\OrderReportController@order_list` | web, admin, actch:admin_panel, module:report |
| 847 | `GET` | `/admin/report/order-report-excel` | `admin.report.order-report-excel` | `Admin\OrderReportController@orderReportExportExcel` | web, admin, actch:admin_panel, module:report |
| 848 | `GET` | `/admin/report/order-report-pdf` | `admin.report.order-report-pdf` | `Admin\OrderReportController@exportOrderReportInPDF` | web, admin, actch:admin_panel, module:report |
| 849 | `GET` | `/admin/report/all-product` | `admin.report.all-product` | `Admin\ProductReportController@all_product` | web, admin, actch:admin_panel, module:report |
| 850 | `GET` | `/admin/report/all-product-excel` | `admin.report.all-product-excel` | `Admin\ProductReportController@allProductExportExcel` | web, admin, actch:admin_panel, module:report |
| 851 | `GET` | `/admin/report/vendor-report` | `admin.report.vendor-report` | `Admin\VendorProductSaleReportController@vendorReport` | web, admin, actch:admin_panel, module:report |
| 852 | `GET` | `/admin/report/vendor-report-export` | `admin.report.vendor-report-export` | `Admin\VendorProductSaleReportController@exportVendorReport` | web, admin, actch:admin_panel, module:report |
| 853 | `GET` | `/admin/transaction/order-transaction-list` | `admin.transaction.order-transaction-list` | `Admin\TransactionReportController@order_transaction_list` | web, admin, actch:admin_panel, module:report |
| 854 | `GET` | `/admin/transaction/pdf-order-wise-transaction` | `admin.transaction.pdf-order-wise-transaction` | `Admin\TransactionReportController@pdf_order_wise_transaction` | web, admin, actch:admin_panel, module:report |
| 855 | `GET` | `/admin/transaction/order-transaction-export-excel` | `admin.transaction.order-transaction-export-excel` | `Admin\TransactionReportController@orderTransactionExportExcel` | web, admin, actch:admin_panel, module:report |
| 856 | `GET` | `/admin/transaction/order-transaction-summary-pdf` | `admin.transaction.order-transaction-summary-pdf` | `Admin\TransactionReportController@order_transaction_summary_pdf` | web, admin, actch:admin_panel, module:report |
| 857 | `GET` | `/admin/transaction/wallet-bonus` | `admin.transaction.wallet-bonus` | `Admin\TransactionReportController@wallet_bonus` | web, admin, actch:admin_panel, module:report |
| 858 | `GET` | `/admin/transaction/expense-transaction-list` | `admin.transaction.expense-transaction-list` | `Admin\ExpenseTransactionReportController@getExpenseTransactionList` | web, admin, actch:admin_panel, module:report |
| 859 | `GET` | `/admin/transaction/pdf-order-wise-expense-transaction` | `admin.transaction.pdf-order-wise-expense-transaction` | `Admin\ExpenseTransactionReportController@generateOrderWiseExpenseTransactionPdf` | web, admin, actch:admin_panel, module:report |
| 860 | `GET` | `/admin/transaction/expense-transaction-export-excel` | `admin.transaction.expense-transaction-export-excel` | `Admin\ExpenseTransactionReportController@expenseTransactionExportExcel` | web, admin, actch:admin_panel, module:report |
| 861 | `GET` | `/admin/transaction/expense-transaction-summary-pdf` | `admin.transaction.expense-transaction-summary-pdf` | `Admin\ExpenseTransactionReportController@generateExpenseTransactionSummaryPDF` | web, admin, actch:admin_panel, module:report |
| 862 | `GET` | `/admin/stock/product-stock` | `admin.stock.product-stock` | `Admin\ProductStockReportController@index` | web, admin, actch:admin_panel, module:report |
| 863 | `GET` | `/admin/stock/product-stock-export` | `admin.stock.product-stock-export` | `Admin\ProductStockReportController@export` | web, admin, actch:admin_panel, module:report |
| 864 | `POST` | `/admin/stock/ps-filter` | `admin.stock.ps-filter` | `Admin\ProductStockReportController@index` | web, admin, actch:admin_panel, module:report |
| 865 | `GET` | `/admin/stock/product-in-wishlist` | `admin.stock.product-in-wishlist` | `Admin\ProductWishlistReportController@index` | web, admin, actch:admin_panel, module:report |
| 866 | `GET` | `/admin/stock/wishlist-product-export` | `admin.stock.wishlist-product-export` | `Admin\ProductWishlistReportController@export` | web, admin, actch:admin_panel, module:report |
| 867 | `GET` | `/admin/reviews/list` | `admin.reviews.list` | `Admin\Product\ReviewController@index` | web, admin, actch:admin_panel, module:user_section |
| 868 | `POST` | `/admin/reviews/status` | `admin.reviews.status` | `Admin\Product\ReviewController@updateStatus` | web, admin, actch:admin_panel, module:user_section |
| 869 | `GET` | `/admin/reviews/export` | `admin.reviews.export` | `Admin\Product\ReviewController@exportList` | web, admin, actch:admin_panel, module:user_section |
| 870 | `GET` | `/admin/reviews/customer-list-search` | `admin.reviews.customer-list-search` | `Admin\Product\ReviewController@getCustomerList` | web, admin, actch:admin_panel, module:user_section |
| 871 | `GET|POST|PUT|PATCH|DELETE|OPTIONS` | `/admin/reviews/search-product` | `admin.reviews.search-product` | `Admin\Product\ReviewController@search` | web, admin, actch:admin_panel, module:user_section |
| 872 | `POST` | `/admin/reviews/add-review-reply` | `admin.reviews.add-review-reply` | `Admin\Product\ReviewController@addReviewReply` | web, admin, actch:admin_panel, module:user_section |
| 873 | `GET|POST|PUT|PATCH|DELETE|OPTIONS` | `/admin/reviews/search-vendor` | `admin.reviews.search-vendor` | `Admin\Product\ReviewController@searchVendor` | web, admin, actch:admin_panel, module:user_section |
| 874 | `GET` | `/admin/coupon/add` | `admin.coupon.add` | `Admin\Promotion\CouponController@getAddListView` | web, admin, actch:admin_panel, module:promotion_management |
| 875 | `POST` | `/admin/coupon/add` | `admin.coupon.` | `Admin\Promotion\CouponController@add` | web, admin, actch:admin_panel, module:promotion_management |
| 876 | `GET` | `/admin/coupon/export` | `admin.coupon.export` | `Admin\Promotion\CouponController@exportList` | web, admin, actch:admin_panel, module:promotion_management |
| 877 | `GET` | `/admin/coupon/quick-view-details` | `admin.coupon.quick-view-details` | `Admin\Promotion\CouponController@quickView` | web, admin, actch:admin_panel, module:promotion_management |
| 878 | `GET` | `/admin/coupon/update/{id}` | `admin.coupon.update` | `Admin\Promotion\CouponController@getUpdateView` | web, admin, actch:admin_panel, module:promotion_management |
| 879 | `POST` | `/admin/coupon/update/{id}` | `admin.coupon.` | `Admin\Promotion\CouponController@update` | web, admin, actch:admin_panel, module:promotion_management |
| 880 | `GET` | `/admin/coupon/status/{id}/{status}` | `admin.coupon.status` | `Admin\Promotion\CouponController@updateStatus` | web, admin, actch:admin_panel, module:promotion_management |
| 881 | `POST` | `/admin/coupon/ajax-get-vendor` | `admin.coupon.ajax-get-vendor` | `Admin\Promotion\CouponController@getVendorList` | web, admin, actch:admin_panel, module:promotion_management |
| 882 | `DELETE` | `/admin/coupon/delete/{id}` | `admin.coupon.delete` | `Admin\Promotion\CouponController@delete` | web, admin, actch:admin_panel, module:promotion_management |
| 883 | `GET` | `/admin/deal/flash` | `admin.deal.flash` | `Admin\Promotion\FlashDealController@index` | web, admin, actch:admin_panel, module:promotion_management |
| 884 | `GET` | `/admin/deal/flash/add` | `admin.deal.flash-add` | `Admin\Promotion\FlashDealController@getAddView` | web, admin, actch:admin_panel, module:promotion_management |
| 885 | `POST` | `/admin/deal/flash` | `admin.deal.` | `Admin\Promotion\FlashDealController@add` | web, admin, actch:admin_panel, module:promotion_management |
| 886 | `GET` | `/admin/deal/update/{id}` | `admin.deal.update` | `Admin\Promotion\FlashDealController@getUpdateView` | web, admin, actch:admin_panel, module:promotion_management |
| 887 | `POST` | `/admin/deal/update/{id}` | `admin.deal.update-data` | `Admin\Promotion\FlashDealController@update` | web, admin, actch:admin_panel, module:promotion_management |
| 888 | `POST` | `/admin/deal/status-update` | `admin.deal.status-update` | `Admin\Promotion\FlashDealController@updateStatus` | web, admin, actch:admin_panel, module:promotion_management |
| 889 | `POST` | `/admin/deal/delete-product` | `admin.deal.delete-product` | `Admin\Promotion\FlashDealController@delete` | web, admin, actch:admin_panel, module:promotion_management |
| 890 | `GET` | `/admin/deal/add-product/{deal_id}` | `admin.deal.add-product` | `Admin\Promotion\FlashDealController@getAddProductView` | web, admin, actch:admin_panel, module:promotion_management |
| 891 | `POST` | `/admin/deal/add-product/{deal_id}` | `admin.deal.` | `Admin\Promotion\FlashDealController@addProduct` | web, admin, actch:admin_panel, module:promotion_management |
| 892 | `GET|POST|PUT|PATCH|DELETE|OPTIONS` | `/admin/deal/search-product` | `admin.deal.search-product` | `Admin\Promotion\FlashDealController@search` | web, admin, actch:admin_panel, module:promotion_management |
| 893 | `GET` | `/admin/deal/day` | `admin.deal.day` | `Admin\Promotion\DealOfTheDayController@index` | web, admin, actch:admin_panel, module:promotion_management |
| 894 | `POST` | `/admin/deal/day` | `admin.deal.` | `Admin\Promotion\DealOfTheDayController@add` | web, admin, actch:admin_panel, module:promotion_management |
| 895 | `POST` | `/admin/deal/day-status-update` | `admin.deal.day-status-update` | `Admin\Promotion\DealOfTheDayController@updateStatus` | web, admin, actch:admin_panel, module:promotion_management |
| 896 | `GET` | `/admin/deal/day-update/{id}` | `admin.deal.day-update` | `Admin\Promotion\DealOfTheDayController@getUpdateView` | web, admin, actch:admin_panel, module:promotion_management |
| 897 | `POST` | `/admin/deal/day-update/{id}` | `admin.deal.` | `Admin\Promotion\DealOfTheDayController@update` | web, admin, actch:admin_panel, module:promotion_management |
| 898 | `POST` | `/admin/deal/day-delete` | `admin.deal.day-delete` | `Admin\Promotion\DealOfTheDayController@delete` | web, admin, actch:admin_panel, module:promotion_management |
| 899 | `GET` | `/admin/deal/feature` | `admin.deal.feature` | `Admin\Promotion\FeaturedDealController@index` | web, admin, actch:admin_panel, module:promotion_management |
| 900 | `GET` | `/admin/deal/feature/new` | `admin.deal.feature-add` | `Admin\Promotion\FeaturedDealController@getAddView` | web, admin, actch:admin_panel, module:promotion_management |
| 901 | `GET` | `/admin/deal/feature-update/{id}` | `admin.deal.edit` | `Admin\Promotion\FeaturedDealController@getUpdateView` | web, admin, actch:admin_panel, module:promotion_management |
| 902 | `POST` | `/admin/deal/feature-update` | `admin.deal.featured-update` | `Admin\Promotion\FeaturedDealController@update` | web, admin, actch:admin_panel, module:promotion_management |
| 903 | `POST` | `/admin/deal/feature-status` | `admin.deal.feature-status` | `Admin\Promotion\FeaturedDealController@updateStatus` | web, admin, actch:admin_panel, module:promotion_management |
| 904 | `GET` | `/admin/deal/clearance-sale` | `admin.deal.clearance-sale.index` | `Admin\Promotion\ClearanceSaleController@index` | web, admin, actch:admin_panel, module:promotion_management |
| 905 | `POST` | `/admin/deal/clearance-sale/status-update` | `admin.deal.clearance-sale.status-update` | `Admin\Promotion\ClearanceSaleController@updateStatus` | web, admin, actch:admin_panel, module:promotion_management |
| 906 | `POST` | `/admin/deal/clearance-sale/update-config` | `admin.deal.clearance-sale.update-config` | `Admin\Promotion\ClearanceSaleController@updateClearanceConfig` | web, admin, actch:admin_panel, module:promotion_management |
| 907 | `POST` | `/admin/deal/clearance-sale/update-seo-meta` | `admin.deal.clearance-sale.update-seo-meta` | `Admin\Promotion\ClearanceSaleController@updateClearanceSeoConfig` | web, admin, actch:admin_panel, module:promotion_management |
| 908 | `GET` | `/admin/deal/clearance-sale/search` | `admin.deal.clearance-sale.search-product-for-clearance` | `Admin\Promotion\ClearanceSaleController@getSearchedProductsView` | web, admin, actch:admin_panel, module:promotion_management |
| 909 | `GET` | `/admin/deal/clearance-sale/multiple-product-details` | `admin.deal.clearance-sale.multiple-clearance-product-details` | `Admin\Promotion\ClearanceSaleController@getMultipleProductDetailsView` | web, admin, actch:admin_panel, module:promotion_management |
| 910 | `POST` | `/admin/deal/clearance-sale/add-clearance-product` | `admin.deal.clearance-sale.add-product` | `Admin\Promotion\ClearanceSaleController@addClearanceProduct` | web, admin, actch:admin_panel, module:promotion_management |
| 911 | `POST` | `/admin/deal/clearance-sale/clearance-product-status-update` | `admin.deal.clearance-sale.product-status-update` | `Admin\Promotion\ClearanceSaleController@updateProductStatus` | web, admin, actch:admin_panel, module:promotion_management |
| 912 | `DELETE` | `/admin/deal/clearance-sale/clearance-delete/{product_id}` | `admin.deal.clearance-sale.clearance-delete` | `Admin\Promotion\ClearanceSaleController@deleteClearanceProduct` | web, admin, actch:admin_panel, module:promotion_management |
| 913 | `DELETE` | `/admin/deal/clearance-sale/clearance-products-delete` | `admin.deal.clearance-sale.clearance-delete-all-product` | `Admin\Promotion\ClearanceSaleController@deleteClearanceAllProduct` | web, admin, actch:admin_panel, module:promotion_management |
| 914 | `POST` | `/admin/deal/clearance-sale/update-discount` | `admin.deal.clearance-sale.update-discount` | `Admin\Promotion\ClearanceSaleController@updateDiscountAmount` | web, admin, actch:admin_panel, module:promotion_management |
| 915 | `GET` | `/admin/deal/clearance-sale/vendor-offers` | `admin.deal.clearance-sale.vendor-offers` | `Admin\Promotion\ClearanceSaleVendorOfferController@index` | web, admin, actch:admin_panel, module:promotion_management |
| 916 | `GET` | `/admin/deal/clearance-sale/vendor-search` | `admin.deal.clearance-sale.search-vendor-for-clearance` | `Admin\Promotion\ClearanceSaleVendorOfferController@getSearchedVendorsView` | web, admin, actch:admin_panel, module:promotion_management |
| 917 | `POST` | `/admin/deal/clearance-sale/vendor-add` | `admin.deal.clearance-sale.vendor-add` | `Admin\Promotion\ClearanceSaleVendorOfferController@addClearanceVendorProduct` | web, admin, actch:admin_panel, module:promotion_management |
| 918 | `POST` | `/admin/deal/clearance-sale/update-status` | `admin.deal.clearance-sale.update-vendor-status` | `Admin\Promotion\ClearanceSaleVendorOfferController@updateVendorStatus` | web, admin, actch:admin_panel, module:promotion_management |
| 919 | `POST` | `/admin/deal/clearance-sale/update-offer-status` | `admin.deal.clearance-sale.update-vendor-offer-status` | `Admin\Promotion\ClearanceSaleVendorOfferController@updateVendorOfferStatus` | web, admin, actch:admin_panel, module:promotion_management |
| 920 | `DELETE` | `/admin/deal/clearance-sale/delete-vendor/{id}` | `admin.deal.clearance-sale.vendor-delete` | `Admin\Promotion\ClearanceSaleVendorOfferController@deleteVendorOffer` | web, admin, actch:admin_panel, module:promotion_management |
| 921 | `GET` | `/admin/deal/clearance-sale/priority-setup` | `admin.deal.clearance-sale.priority-setup` | `Admin\Promotion\ClearanceSalePrioritySetupController@index` | web, admin, actch:admin_panel, module:promotion_management |
| 922 | `POST` | `/admin/deal/clearance-sale/priority-setup-config` | `admin.deal.clearance-sale.priority-setup-config` | `Admin\Promotion\ClearanceSalePrioritySetupController@updateConfig` | web, admin, actch:admin_panel, module:promotion_management |
| 923 | `GET` | `/admin/push-notification/index` | `admin.push-notification.index` | `Admin\Notification\PushNotificationSettingsController@index` | web, admin, actch:admin_panel, module:promotion_management |
| 924 | `POST` | `/admin/push-notification/update` | `admin.push-notification.update` | `Admin\Notification\PushNotificationSettingsController@updatePushNotificationMessage` | web, admin, actch:admin_panel, module:promotion_management |
| 925 | `GET` | `/admin/notification/index` | `admin.notification.index` | `Admin\Notification\NotificationController@index` | web, admin, actch:admin_panel, module:promotion_management |
| 926 | `POST` | `/admin/notification/index` | `admin.notification.` | `Admin\Notification\NotificationController@add` | web, admin, actch:admin_panel, module:promotion_management |
| 927 | `GET` | `/admin/notification/update/{id}` | `admin.notification.update` | `Admin\Notification\NotificationController@getUpdateView` | web, admin, actch:admin_panel, module:promotion_management |
| 928 | `POST` | `/admin/notification/update/{id}` | `admin.notification.` | `Admin\Notification\NotificationController@update` | web, admin, actch:admin_panel, module:promotion_management |
| 929 | `POST` | `/admin/notification/delete` | `admin.notification.delete` | `Admin\Notification\NotificationController@delete` | web, admin, actch:admin_panel, module:promotion_management |
| 930 | `POST` | `/admin/notification/update-status` | `admin.notification.update-status` | `Admin\Notification\NotificationController@updateStatus` | web, admin, actch:admin_panel, module:promotion_management |
| 931 | `POST` | `/admin/notification/resend-notification` | `admin.notification.resend-notification` | `Admin\Notification\NotificationController@resendNotification` | web, admin, actch:admin_panel, module:promotion_management |
| 932 | `GET` | `/admin/support-ticket/view` | `admin.support-ticket.view` | `Admin\HelpAndSupport\SupportTicketController@index` | web, admin, actch:admin_panel, module:support_section |
| 933 | `POST` | `/admin/support-ticket/status` | `admin.support-ticket.status` | `Admin\HelpAndSupport\SupportTicketController@updateStatus` | web, admin, actch:admin_panel, module:support_section |
| 934 | `GET` | `/admin/support-ticket/single-ticket/{id}` | `admin.support-ticket.singleTicket` | `Admin\HelpAndSupport\SupportTicketController@getView` | web, admin, actch:admin_panel, module:support_section |
| 935 | `POST` | `/admin/support-ticket/single-ticket/{id}` | `admin.support-ticket.replay` | `Admin\HelpAndSupport\SupportTicketController@reply` | web, admin, actch:admin_panel, module:support_section |
| 936 | `GET` | `/admin/messages/index/{type}` | `admin.messages.index` | `Admin\ChattingController@index` | web, admin, actch:admin_panel |
| 937 | `GET` | `/admin/messages/message` | `admin.messages.message` | `Admin\ChattingController@getMessageByUser` | web, admin, actch:admin_panel |
| 938 | `POST` | `/admin/messages/message` | `admin.messages.` | `Admin\ChattingController@addAdminMessage` | web, admin, actch:admin_panel |
| 939 | `GET` | `/admin/contact/list` | `admin.contact.list` | `Admin\HelpAndSupport\ContactController@index` | web, admin, actch:admin_panel, module:support_section |
| 940 | `GET` | `/admin/contact/view/{id}` | `admin.contact.view` | `Admin\HelpAndSupport\ContactController@getView` | web, admin, actch:admin_panel, module:support_section |
| 941 | `POST` | `/admin/contact/filer` | `admin.contact.filter` | `Admin\HelpAndSupport\ContactController@getListByFilter` | web, admin, actch:admin_panel, module:support_section |
| 942 | `POST` | `/admin/contact/delete` | `admin.contact.delete` | `Admin\HelpAndSupport\ContactController@delete` | web, admin, actch:admin_panel, module:support_section |
| 943 | `POST` | `/admin/contact/update/{id}` | `admin.contact.update` | `Admin\HelpAndSupport\ContactController@update` | web, admin, actch:admin_panel, module:support_section |
| 944 | `POST` | `/admin/contact/store` | `admin.contact.store` | `Admin\HelpAndSupport\ContactController@add` | web, admin, actch:admin_panel, module:support_section |
| 945 | `POST` | `/admin/contact/send-mail/{id}` | `admin.contact.send-mail` | `Admin\HelpAndSupport\ContactController@sendMail` | web, admin, actch:admin_panel, module:support_section |
| 946 | `GET` | `/admin/delivery-man/list` | `admin.delivery-man.list` | `Admin\Deliveryman\DeliveryManController@index` | web, admin, actch:admin_panel, module:user_section |
| 947 | `GET` | `/admin/delivery-man/add` | `admin.delivery-man.add` | `Admin\Deliveryman\DeliveryManController@getAddView` | web, admin, actch:admin_panel, module:user_section |
| 948 | `POST` | `/admin/delivery-man/add` | `admin.delivery-man.` | `Admin\Deliveryman\DeliveryManController@add` | web, admin, actch:admin_panel, module:user_section |
| 949 | `POST` | `/admin/delivery-man/status-update` | `admin.delivery-man.status-update` | `Admin\Deliveryman\DeliveryManController@updateStatus` | web, admin, actch:admin_panel, module:user_section |
| 950 | `GET` | `/admin/delivery-man/export` | `admin.delivery-man.export` | `Admin\Deliveryman\DeliveryManController@exportList` | web, admin, actch:admin_panel, module:user_section |
| 951 | `GET` | `/admin/delivery-man/update/{id}` | `admin.delivery-man.edit` | `Admin\Deliveryman\DeliveryManController@getUpdateView` | web, admin, actch:admin_panel, module:user_section |
| 952 | `POST` | `/admin/delivery-man/update/{id}` | `admin.delivery-man.update` | `Admin\Deliveryman\DeliveryManController@update` | web, admin, actch:admin_panel, module:user_section |
| 953 | `DELETE` | `/admin/delivery-man/delete/{id}` | `admin.delivery-man.delete` | `Admin\Deliveryman\DeliveryManController@delete` | web, admin, actch:admin_panel, module:user_section |
| 954 | `GET` | `/admin/delivery-man/earning-statement-overview/{id}` | `admin.delivery-man.earning-statement-overview` | `Admin\Deliveryman\DeliveryManController@getEarningOverview` | web, admin, actch:admin_panel, module:user_section |
| 955 | `GET` | `/admin/delivery-man/order-wise-earning/{id}` | `admin.delivery-man.order-wise-earning` | `Admin\Deliveryman\DeliveryManController@getOrderWiseEarningView` | web, admin, actch:admin_panel, module:user_section |
| 956 | `GET` | `/admin/delivery-man/order-list-by-filer/{id}` | `admin.delivery-man.order-wise-earning-list-by-filter` | `Admin\Deliveryman\DeliveryManController@getOrderWiseEarningListByFilter` | web, admin, actch:admin_panel, module:user_section |
| 957 | `GET` | `/admin/delivery-man/order-history-log/{id}` | `admin.delivery-man.order-history-log` | `Admin\Deliveryman\DeliveryManController@getOrderHistoryList` | web, admin, actch:admin_panel, module:user_section |
| 958 | `GET` | `/admin/delivery-man/order-history-log-export/{id}` | `admin.delivery-man.order-history-log-export` | `Admin\Deliveryman\DeliveryManController@getOrderHistoryListExport` | web, admin, actch:admin_panel, module:user_section |
| 959 | `GET` | `/admin/delivery-man/rating/{id}` | `admin.delivery-man.rating` | `Admin\Deliveryman\DeliveryManController@getRatingView` | web, admin, actch:admin_panel, module:user_section |
| 960 | `GET` | `/admin/delivery-man/ajax-order-status-history/{order}` | `admin.delivery-man.ajax-order-status-history` | `Admin\Deliveryman\DeliveryManController@getOrderStatusHistory` | web, admin, actch:admin_panel, module:user_section |
| 961 | `GET` | `/admin/delivery-man/collect-cash/{id}` | `admin.delivery-man.collect-cash` | `Admin\Deliveryman\DeliveryManCashCollectController@index` | web, admin, actch:admin_panel, module:user_section |
| 962 | `POST` | `/admin/delivery-man/cash-receive/{id}` | `admin.delivery-man.cash-receive` | `Admin\Deliveryman\DeliveryManCashCollectController@getCashReceive` | web, admin, actch:admin_panel, module:user_section |
| 963 | `GET` | `/admin/delivery-man/withdraw-list` | `admin.delivery-man.withdraw-list` | `Admin\Deliveryman\DeliverymanWithdrawController@index` | web, admin, actch:admin_panel, module:user_section |
| 964 | `POST` | `/admin/delivery-man/withdraw-list` | `admin.delivery-man.` | `Admin\Deliveryman\DeliverymanWithdrawController@getFiltered` | web, admin, actch:admin_panel, module:user_section |
| 965 | `GET` | `/admin/delivery-man/withdraw-list-export` | `admin.delivery-man.withdraw-list-export` | `Admin\Deliveryman\DeliverymanWithdrawController@exportList` | web, admin, actch:admin_panel, module:user_section |
| 966 | `GET` | `/admin/delivery-man/withdraw-view/{withdraw_id}` | `admin.delivery-man.withdraw-view` | `Admin\Deliveryman\DeliverymanWithdrawController@getView` | web, admin, actch:admin_panel, module:user_section |
| 967 | `POST` | `/admin/delivery-man/withdraw-update-status/{id}` | `admin.delivery-man.withdraw-update-status` | `Admin\Deliveryman\DeliverymanWithdrawController@updateStatus` | web, admin, actch:admin_panel, module:user_section |
| 968 | `GET` | `/admin/delivery-man/emergency-contact` | `admin.delivery-man.emergency-contact.index` | `Admin\Deliveryman\EmergencyContactController@index` | web, admin, actch:admin_panel, module:user_section |
| 969 | `POST` | `/admin/delivery-man/emergency-contact/add` | `admin.delivery-man.emergency-contact.add` | `Admin\Deliveryman\EmergencyContactController@add` | web, admin, actch:admin_panel, module:user_section |
| 970 | `GET` | `/admin/delivery-man/emergency-contact/update/{id}` | `admin.delivery-man.emergency-contact.update` | `Admin\Deliveryman\EmergencyContactController@getUpdateView` | web, admin, actch:admin_panel, module:user_section |
| 971 | `POST` | `/admin/delivery-man/emergency-contact/update/{id}` | `admin.delivery-man.emergency-contact.` | `Admin\Deliveryman\EmergencyContactController@update` | web, admin, actch:admin_panel, module:user_section |
| 972 | `POST` | `/admin/delivery-man/emergency-contact/ajax-status-change` | `admin.delivery-man.emergency-contact.ajax-status-change` | `Admin\Deliveryman\EmergencyContactController@updateStatus` | web, admin, actch:admin_panel, module:user_section |
| 973 | `DELETE` | `/admin/delivery-man/emergency-contact/destroy` | `admin.delivery-man.emergency-contact.destroy` | `Admin\Deliveryman\EmergencyContactController@delete` | web, admin, actch:admin_panel, module:user_section |
| 974 | `GET` | `/admin/delivery-hubs` | `admin.delivery-hubs.index` | `Admin\Delivery\DeliveryHubController@index` | web, admin, actch:admin_panel, module:order_management |
| 975 | `POST` | `/admin/delivery-hubs/store-state` | `admin.delivery-hubs.store-state` | `Admin\Delivery\DeliveryHubController@storeState` | web, admin, actch:admin_panel, module:order_management |
| 976 | `POST` | `/admin/delivery-hubs/update-state/{id}` | `admin.delivery-hubs.update-state` | `Admin\Delivery\DeliveryHubController@updateState` | web, admin, actch:admin_panel, module:order_management |
| 977 | `DELETE` | `/admin/delivery-hubs/delete-state/{id}` | `admin.delivery-hubs.delete-state` | `Admin\Delivery\DeliveryHubController@deleteState` | web, admin, actch:admin_panel, module:order_management |
| 978 | `POST` | `/admin/delivery-hubs/status-state` | `admin.delivery-hubs.status-state` | `Admin\Delivery\DeliveryHubController@statusState` | web, admin, actch:admin_panel, module:order_management |
| 979 | `POST` | `/admin/delivery-hubs/store-city` | `admin.delivery-hubs.store-city` | `Admin\Delivery\DeliveryHubController@storeCity` | web, admin, actch:admin_panel, module:order_management |
| 980 | `POST` | `/admin/delivery-hubs/update-city/{id}` | `admin.delivery-hubs.update-city` | `Admin\Delivery\DeliveryHubController@updateCity` | web, admin, actch:admin_panel, module:order_management |
| 981 | `DELETE` | `/admin/delivery-hubs/delete-city/{id}` | `admin.delivery-hubs.delete-city` | `Admin\Delivery\DeliveryHubController@deleteCity` | web, admin, actch:admin_panel, module:order_management |
| 982 | `POST` | `/admin/delivery-hubs/status-city` | `admin.delivery-hubs.status-city` | `Admin\Delivery\DeliveryHubController@statusCity` | web, admin, actch:admin_panel, module:order_management |
| 983 | `POST` | `/admin/delivery-hubs/store-hub` | `admin.delivery-hubs.store-hub` | `Admin\Delivery\DeliveryHubController@storeHub` | web, admin, actch:admin_panel, module:order_management |
| 984 | `POST` | `/admin/delivery-hubs/update-hub/{id}` | `admin.delivery-hubs.update-hub` | `Admin\Delivery\DeliveryHubController@updateHub` | web, admin, actch:admin_panel, module:order_management |
| 985 | `DELETE` | `/admin/delivery-hubs/delete-hub/{id}` | `admin.delivery-hubs.delete-hub` | `Admin\Delivery\DeliveryHubController@deleteHub` | web, admin, actch:admin_panel, module:order_management |
| 986 | `POST` | `/admin/delivery-hubs/status-hub` | `admin.delivery-hubs.status-hub` | `Admin\Delivery\DeliveryHubController@statusHub` | web, admin, actch:admin_panel, module:order_management |
| 987 | `GET` | `/admin/delivery-hubs/get-cities-ajax/{state_id}` | `admin.delivery-hubs.get-cities-ajax` | `Admin\Delivery\DeliveryHubController@getCitiesAjax` | web, admin, actch:admin_panel, module:order_management |
| 988 | `GET` | `/admin/delivery-hubs/get-hubs-ajax/{city_id}` | `admin.delivery-hubs.get-hubs-ajax` | `Admin\Delivery\DeliveryHubController@getHubsAjax` | web, admin, actch:admin_panel, module:order_management |
| 989 | `GET` | `/admin/dispatch-portal` | `admin.dispatch-portal.index` | `Admin\Delivery\DispatchPortalController@index` | web, admin, actch:admin_panel, module:order_management |
| 990 | `POST` | `/admin/dispatch-portal/assign-batch` | `admin.dispatch-portal.assign-batch` | `Admin\Delivery\DispatchPortalController@assignBatch` | web, admin, actch:admin_panel, module:order_management |
| 991 | `GET` | `/admin/dispatch-portal/print-manifest` | `admin.dispatch-portal.print-manifest` | `Admin\Delivery\DispatchPortalController@printBatchManifest` | web, admin, actch:admin_panel, module:order_management |
| 992 | `GET` | `/admin/dispatch-portal/print-waybill/{id}` | `admin.dispatch-portal.print-waybill` | `Admin\Delivery\DispatchPortalController@printWaybill` | web, admin, actch:admin_panel, module:order_management |
| 993 | `GET` | `/admin/most-demanded` | `admin.most-demanded.index` | `Admin\Promotion\MostDemandedController@getListView` | web, admin, actch:admin_panel, module:promotion_management |
| 994 | `POST` | `/admin/most-demanded/store` | `admin.most-demanded.store` | `Admin\Promotion\MostDemandedController@add` | web, admin, actch:admin_panel, module:promotion_management |
| 995 | `GET` | `/admin/most-demanded/update/{id}` | `admin.most-demanded.edit` | `Admin\Promotion\MostDemandedController@getUpdateView` | web, admin, actch:admin_panel, module:promotion_management |
| 996 | `POST` | `/admin/most-demanded/update/{id}` | `admin.most-demanded.update` | `Admin\Promotion\MostDemandedController@update` | web, admin, actch:admin_panel, module:promotion_management |
| 997 | `POST` | `/admin/most-demanded/delete` | `admin.most-demanded.delete` | `Admin\Promotion\MostDemandedController@delete` | web, admin, actch:admin_panel, module:promotion_management |
| 998 | `POST` | `/admin/most-demanded/status` | `admin.most-demanded.status-update` | `Admin\Promotion\MostDemandedController@updateStatus` | web, admin, actch:admin_panel, module:promotion_management |
| 999 | `GET` | `/admin/addon` | `admin.addon.index` | `Admin\Settings\AddonController@index` | web, admin, actch:admin_panel |
| 1000 | `POST` | `/admin/addon/publish` | `admin.addon.publish` | `Admin\Settings\AddonController@publish` | web, admin, actch:admin_panel |
| 1001 | `POST` | `/admin/addon/activation` | `admin.addon.activation` | `Admin\Settings\AddonController@activation` | web, admin, actch:admin_panel |
| 1002 | `POST` | `/admin/addon/upload` | `admin.addon.upload` | `Admin\Settings\AddonController@upload` | web, admin, actch:admin_panel |
| 1003 | `POST` | `/admin/addon/delete` | `admin.addon.delete` | `Admin\Settings\AddonController@delete` | web, admin, actch:admin_panel |
| 1004 | `GET` | `/admin/system-setup/theme/setup` | `admin.system-setup.theme.setup` | `Admin\Settings\ThemeController@index` | web, admin, actch:admin_panel, module:themes_and_addons |
| 1005 | `POST` | `/admin/system-setup/theme/install` | `admin.system-setup.theme.install` | `Admin\Settings\ThemeController@upload` | web, admin, actch:admin_panel, module:themes_and_addons |
| 1006 | `POST` | `/admin/system-setup/theme/activation` | `admin.system-setup.theme.activation` | `Admin\Settings\ThemeController@activation` | web, admin, actch:admin_panel, module:themes_and_addons |
| 1007 | `POST` | `/admin/system-setup/theme/publish` | `admin.system-setup.theme.publish` | `Admin\Settings\ThemeController@publish` | web, admin, actch:admin_panel, module:themes_and_addons |
| 1008 | `POST` | `/admin/system-setup/theme/delete` | `admin.system-setup.theme.delete` | `Admin\Settings\ThemeController@delete` | web, admin, actch:admin_panel, module:themes_and_addons |
| 1009 | `POST` | `/admin/system-setup/theme/notify-all-the-vendors` | `admin.system-setup.theme.notify-all-the-vendors` | `Admin\Settings\ThemeController@notifyAllTheVendors` | web, admin, actch:admin_panel, module:themes_and_addons |
| 1010 | `GET` | `/admin/system-setup/addon` | `admin.system-setup.addon.index` | `Admin\Settings\AddonController@index` | web, admin, actch:admin_panel, module:themes_and_addons |
| 1011 | `POST` | `/admin/system-setup/addon/publish` | `admin.system-setup.addon.publish` | `Admin\Settings\AddonController@publish` | web, admin, actch:admin_panel, module:themes_and_addons |
| 1012 | `POST` | `/admin/system-setup/addon/activation` | `admin.system-setup.addon.activation` | `Admin\Settings\AddonController@activation` | web, admin, actch:admin_panel, module:themes_and_addons |
| 1013 | `POST` | `/admin/system-setup/addon/upload` | `admin.system-setup.addon.upload` | `Admin\Settings\AddonController@upload` | web, admin, actch:admin_panel, module:themes_and_addons |
| 1014 | `POST` | `/admin/system-setup/addon/delete` | `admin.system-setup.addon.delete` | `Admin\Settings\AddonController@delete` | web, admin, actch:admin_panel, module:themes_and_addons |
| 1015 | `GET` | `/admin/system-setup/addon-activation` | `admin.system-setup.addon-activation.index` | `Admin\Settings\AddonActivationController@index` | web, admin, actch:admin_panel, module:themes_and_addons |
| 1016 | `POST` | `/admin/system-setup/addon-activation/activation` | `admin.system-setup.addon-activation.activation` | `Admin\Settings\AddonActivationController@activation` | web, admin, actch:admin_panel, module:themes_and_addons |
| 1017 | `GET` | `/admin/system-setup/environment-setup` | `admin.system-setup.environment-setup` | `Admin\Settings\EnvironmentSettingsController@index` | web, admin, actch:admin_panel, module:system_settings |
| 1018 | `POST` | `/admin/system-setup/environment-setup` | `admin.system-setup.` | `Admin\Settings\EnvironmentSettingsController@update` | web, admin, actch:admin_panel, module:system_settings |
| 1019 | `POST` | `/admin/system-setup/environment-update-force-https` | `admin.system-setup.environment-https-setup` | `Admin\Settings\EnvironmentSettingsController@updateForceHttps` | web, admin, actch:admin_panel, module:system_settings |
| 1020 | `POST` | `/admin/system-setup/optimize-system` | `admin.system-setup.optimize-system` | `Admin\Settings\EnvironmentSettingsController@optimizeSystem` | web, admin, actch:admin_panel, module:system_settings |
| 1021 | `POST` | `/admin/system-setup/install-passport` | `admin.system-setup.install-passport` | `Admin\Settings\EnvironmentSettingsController@installPassport` | web, admin, actch:admin_panel, module:system_settings |
| 1022 | `GET` | `/admin/system-setup/app-settings` | `admin.system-setup.app-settings` | `Admin\Settings\BusinessSettingsController@getAppSettingsView` | web, admin, actch:admin_panel, module:system_settings |
| 1023 | `POST` | `/admin/system-setup/app-settings` | `admin.system-setup.` | `Admin\Settings\BusinessSettingsController@updateAppSettings` | web, admin, actch:admin_panel, module:system_settings |
| 1024 | `GET` | `/admin/system-setup/app-deep-link` | `admin.system-setup.app-deep-link` | `Admin\Settings\BusinessSettingsController@getAppDeepLinkView` | web, admin, actch:admin_panel, module:system_settings |
| 1025 | `POST` | `/admin/system-setup/app-deep-link` | `admin.system-setup.app-deep-link-store` | `Admin\Settings\BusinessSettingsController@updateAppDeepLink` | web, admin, actch:admin_panel, module:system_settings |
| 1026 | `GET` | `/admin/system-setup/software-update` | `admin.system-setup.software-update` | `Admin\Settings\SoftwareUpdateController@index` | web, admin, actch:admin_panel, module:system_settings |
| 1027 | `POST` | `/admin/system-setup/software-update` | `admin.system-setup.` | `Admin\Settings\SoftwareUpdateController@update` | web, admin, actch:admin_panel, module:system_settings |
| 1028 | `GET` | `/admin/system-setup/language` | `admin.system-setup.language.index` | `Admin\Settings\LanguageController@index` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1029 | `POST` | `/admin/system-setup/language/add` | `admin.system-setup.language.add-new` | `Admin\Settings\LanguageController@add` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1030 | `POST` | `/admin/system-setup/language/update-status` | `admin.system-setup.language.update-status` | `Admin\Settings\LanguageController@updateStatus` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1031 | `POST` | `/admin/system-setup/language/update-default-status` | `admin.system-setup.language.update-default-status` | `Admin\Settings\LanguageController@updateDefaultStatus` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1032 | `POST` | `/admin/system-setup/language/update` | `admin.system-setup.language.update` | `Admin\Settings\LanguageController@update` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1033 | `GET` | `/admin/system-setup/language/delete/{lang}` | `admin.system-setup.language.delete` | `Admin\Settings\LanguageController@delete` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1034 | `GET` | `/admin/system-setup/language/translate/{lang}` | `admin.system-setup.language.translate` | `Admin\Settings\LanguageController@getTranslateView` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1035 | `GET` | `/admin/system-setup/language/translate-list/{lang}` | `admin.system-setup.language.translate.list` | `Admin\Settings\LanguageController@getTranslateList` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1036 | `POST` | `/admin/system-setup/language/translate-submit/{lang}` | `admin.system-setup.language.translate-submit` | `Admin\Settings\LanguageController@updateTranslate` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1037 | `POST` | `/admin/system-setup/language/remove-key/{lang}` | `admin.system-setup.language.remove-key` | `Admin\Settings\LanguageController@deleteTranslateKey` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1038 | `GET|POST|PUT|PATCH|DELETE|OPTIONS` | `/admin/system-setup/language/auto-translate/{lang}` | `admin.system-setup.language.auto-translate` | `Admin\Settings\LanguageController@getAutoTranslate` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1039 | `GET|POST|PUT|PATCH|DELETE|OPTIONS` | `/admin/system-setup/language/auto-translate-all/{lang}` | `admin.system-setup.language.auto-translate-all` | `Admin\Settings\LanguageController@getAutoTranslateAllMessages` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1040 | `GET` | `/admin/system-setup/currency/view` | `admin.system-setup.currency.view` | `Admin\Settings\CurrencyController@index` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1041 | `POST` | `/admin/system-setup/currency/store` | `admin.system-setup.currency.store` | `Admin\Settings\CurrencyController@add` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1042 | `POST` | `/admin/system-setup/currency/update` | `admin.system-setup.currency.update` | `Admin\Settings\CurrencyController@update` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1043 | `POST` | `/admin/system-setup/currency/delete` | `admin.system-setup.currency.delete` | `Admin\Settings\CurrencyController@delete` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1044 | `POST` | `/admin/system-setup/currency/status` | `admin.system-setup.currency.status` | `Admin\Settings\CurrencyController@status` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1045 | `POST` | `/admin/system-setup/currency/check-currency-update` | `admin.system-setup.currency.check-currency-update` | `Admin\Settings\CurrencyController@checkSystemCurrency` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1046 | `POST` | `/admin/system-setup/currency/system-currency-update` | `admin.system-setup.currency.system-currency-update` | `Admin\Settings\CurrencyController@updateSystemCurrency` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1047 | `GET` | `/admin/system-setup/db-index` | `admin.system-setup.db-index` | `Admin\Settings\DatabaseSettingController@index` | web, admin, actch:admin_panel, module:system_settings |
| 1048 | `POST` | `/admin/system-setup/db-clean` | `admin.system-setup.clean-db` | `Admin\Settings\DatabaseSettingController@delete` | web, admin, actch:admin_panel, module:system_settings |
| 1049 | `GET` | `/admin/system-setup/login-settings/customer-login-setup` | `admin.system-setup.login-settings.customer-login-setup` | `Admin\SystemSetup\SystemLoginSetupController@getCustomerLoginSetupView` | web, admin, actch:admin_panel, module:system_settings |
| 1050 | `POST` | `/admin/system-setup/login-settings/customer-login-setup` | `admin.system-setup.login-settings.` | `Admin\SystemSetup\SystemLoginSetupController@updateCustomerLoginSetup` | web, admin, actch:admin_panel, module:system_settings |
| 1051 | `POST` | `/admin/system-setup/login-settings/customer-config-validation` | `admin.system-setup.login-settings.config-status-validation` | `Admin\SystemSetup\SystemLoginSetupController@getConfigValidation` | web, admin, actch:admin_panel, module:system_settings |
| 1052 | `GET` | `/admin/system-setup/login-settings/otp-setup` | `admin.system-setup.login-settings.otp-setup` | `Admin\SystemSetup\SystemLoginSetupController@getOtpSetupView` | web, admin, actch:admin_panel, module:system_settings |
| 1053 | `POST` | `/admin/system-setup/login-settings/otp-setup` | `admin.system-setup.login-settings.` | `Admin\SystemSetup\SystemLoginSetupController@updateOtpSetup` | web, admin, actch:admin_panel, module:system_settings |
| 1054 | `GET` | `/admin/system-setup/login-settings/login-url-setup` | `admin.system-setup.login-settings.login-url-setup` | `Admin\SystemSetup\SystemLoginSetupController@getLoginSetupView` | web, admin, actch:admin_panel, module:system_settings |
| 1055 | `POST` | `/admin/system-setup/login-settings/login-url-setup` | `admin.system-setup.login-settings.` | `Admin\SystemSetup\SystemLoginSetupController@updateLoginSetupView` | web, admin, actch:admin_panel, module:system_settings |
| 1056 | `GET` | `/admin/system-setup/email-templates/index` | `admin.system-setup.email-templates.index` | `Admin\EmailTemplatesController@index` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1057 | `GET` | `/admin/system-setup/email-templates/{type}/{tab}` | `admin.system-setup.email-templates.view` | `Admin\EmailTemplatesController@getView` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1058 | `POST` | `/admin/system-setup/email-templates/update/{type}/{tab}` | `admin.system-setup.email-templates.update` | `Admin\EmailTemplatesController@update` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1059 | `POST` | `/admin/system-setup/email-templates/update-status/{type}/{tab}` | `admin.system-setup.email-templates.update-status` | `Admin\EmailTemplatesController@updateStatus` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1060 | `GET` | `/admin/system-setup/file-manager/index` | `admin.system-setup.file-manager.index` | `Admin\Settings\FileManagerController@index` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1061 | `GET` | `/admin/system-setup/file-manager/download/{file_name}` | `admin.system-setup.file-manager.download` | `Admin\Settings\FileManagerController@download` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1062 | `POST` | `/admin/system-setup/file-manager/image-upload` | `admin.system-setup.file-manager.image-upload` | `Admin\Settings\FileManagerController@upload` | web, admin, actch:admin_panel, module:system_settings, module:system_settings |
| 1063 | `GET` | `/admin/third-party/payment-method` | `admin.third-party.payment-method.index` | `Admin\ThirdParty\PaymentMethodController@index` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1064 | `PUT` | `/admin/third-party/payment-method/addon-payment-set` | `admin.third-party.payment-method.addon-payment-set` | `Admin\ThirdParty\PaymentMethodController@UpdatePaymentConfig` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1065 | `POST` | `/admin/third-party/payment-method/payment-status` | `admin.third-party.payment-method.payment-status` | `Admin\ThirdParty\PaymentMethodController@UpdateStatus` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1066 | `GET` | `/admin/third-party/offline-payment-method/index` | `admin.third-party.offline-payment-method.index` | `Admin\Payment\OfflinePaymentMethodController@index` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1067 | `GET` | `/admin/third-party/offline-payment-method/add` | `admin.third-party.offline-payment-method.add` | `Admin\Payment\OfflinePaymentMethodController@getAddView` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1068 | `POST` | `/admin/third-party/offline-payment-method/add` | `admin.third-party.offline-payment-method.` | `Admin\Payment\OfflinePaymentMethodController@add` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1069 | `GET` | `/admin/third-party/offline-payment-method/update/{id}` | `admin.third-party.offline-payment-method.update` | `Admin\Payment\OfflinePaymentMethodController@getUpdateView` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1070 | `POST` | `/admin/third-party/offline-payment-method/update/{id}` | `admin.third-party.offline-payment-method.` | `Admin\Payment\OfflinePaymentMethodController@update` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1071 | `POST` | `/admin/third-party/offline-payment-method/delete` | `admin.third-party.offline-payment-method.delete` | `Admin\Payment\OfflinePaymentMethodController@delete` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1072 | `POST` | `/admin/third-party/offline-payment-method/update-status` | `admin.third-party.offline-payment-method.update-status` | `Admin\Payment\OfflinePaymentMethodController@updateStatus` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1073 | `GET` | `/admin/third-party/firebase-configuration/setup` | `admin.third-party.firebase-configuration.setup` | `Admin\Notification\PushNotificationSettingsController@getFirebaseConfigurationView` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1074 | `POST` | `/admin/third-party/firebase-configuration/setup` | `admin.third-party.firebase-configuration.` | `Admin\Notification\PushNotificationSettingsController@getFirebaseConfigurationUpdate` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1075 | `GET` | `/admin/third-party/firebase-configuration/authentication` | `admin.third-party.firebase-configuration.authentication` | `Admin\Settings\FirebaseOTPVerificationController@index` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1076 | `POST` | `/admin/third-party/firebase-configuration/update` | `admin.third-party.firebase-configuration.update` | `Admin\Settings\FirebaseOTPVerificationController@updateAuthentication` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1077 | `POST` | `/admin/third-party/firebase-configuration/firebase-config-validation` | `admin.third-party.firebase-configuration.config-status-validation` | `Admin\Settings\FirebaseOTPVerificationController@getConfigValidation` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1078 | `GET` | `/admin/third-party/analytics-index` | `admin.third-party.analytics-index` | `Admin\Settings\BusinessSettingsController@getAnalyticsView` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1079 | `POST` | `/admin/third-party/analytics-update` | `admin.third-party.analytics-update` | `Admin\Settings\BusinessSettingsController@updateAnalytics` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1080 | `GET` | `/admin/third-party/social-login/view` | `admin.third-party.social-login.view` | `Admin\ThirdParty\SocialLoginSettingsController@index` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1081 | `POST` | `/admin/third-party/social-login/update/{service}` | `admin.third-party.social-login.update` | `Admin\ThirdParty\SocialLoginSettingsController@update` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1082 | `POST` | `/admin/third-party/social-login/update-apple/{service}` | `admin.third-party.social-login.update-apple` | `Admin\ThirdParty\SocialLoginSettingsController@updateAppleLogin` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1083 | `GET` | `/admin/third-party/storage-connection-settings/index` | `admin.third-party.storage-connection-settings.index` | `Admin\Settings\StorageConnectionSettingsController@index` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1084 | `POST` | `/admin/third-party/storage-connection-settings/update-storage-type` | `admin.third-party.storage-connection-settings.update-storage-type` | `Admin\Settings\StorageConnectionSettingsController@updateStorageType` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1085 | `POST` | `/admin/third-party/storage-connection-settings/s3-credential` | `admin.third-party.storage-connection-settings.s3-credential` | `Admin\Settings\StorageConnectionSettingsController@updateS3Credential` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1086 | `POST` | `/admin/third-party/social-media-chat/update/{service}` | `admin.third-party.social-media-chat.update` | `Admin\ThirdParty\SocialMediaChatController@update` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1087 | `GET` | `/admin/third-party/mail` | `admin.third-party.mail.index` | `Admin\ThirdParty\MailController@index` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1088 | `POST` | `/admin/third-party/mail/update` | `admin.third-party.mail.update` | `Admin\ThirdParty\MailController@update` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1089 | `POST` | `/admin/third-party/mail/update-sendgrid` | `admin.third-party.mail.update-sendgrid` | `Admin\ThirdParty\MailController@updateSendGrid` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1090 | `POST` | `/admin/third-party/mail/send` | `admin.third-party.mail.send` | `Admin\ThirdParty\MailController@send` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1091 | `GET` | `/admin/third-party/sms-module` | `admin.third-party.sms-module` | `Admin\ThirdParty\SMSModuleController@index` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1092 | `PUT` | `/admin/third-party/addon-sms-set` | `admin.third-party.addon-sms-set` | `Admin\ThirdParty\SMSModuleController@update` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1093 | `POST` | `/admin/third-party/send-test-sms` | `admin.third-party.send-test-sms` | `Admin\ThirdParty\SMSModuleController@sendSMS` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1094 | `GET` | `/admin/third-party/recaptcha` | `admin.third-party.captcha` | `Admin\ThirdParty\RecaptchaController@index` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1095 | `POST` | `/admin/third-party/recaptcha` | `admin.third-party.` | `Admin\ThirdParty\RecaptchaController@update` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1096 | `GET` | `/admin/third-party/map-api` | `admin.third-party.map-api` | `Admin\ThirdParty\GoogleMapAPIController@index` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1097 | `POST` | `/admin/third-party/map-api` | `admin.third-party.` | `Admin\ThirdParty\GoogleMapAPIController@update` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1098 | `GET` | `/admin/third-party/youtube-integration/setup` | `admin.third-party.youtube-integration.setup` | `Admin\Settings\BusinessSettingsController@getYoutubeSettingsView` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1099 | `POST` | `/admin/third-party/youtube-integration/setup` | `admin.third-party.youtube-integration.` | `Admin\Settings\BusinessSettingsController@updateYoutubeSettings` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1100 | `GET` | `/admin/third-party/youtube-integration/connect` | `admin.third-party.youtube-integration.connect` | `Admin\Settings\BusinessSettingsController@youtubeConnect` | web, admin, actch:admin_panel, module:3rd_party_setup |
| 1101 | `GET` | `/admin/business-settings/web-config` | `admin.business-settings.web-config.index` | `Admin\Settings\BusinessSettingsController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1102 | `POST` | `/admin/business-settings/web-config` | `admin.business-settings.web-config.update` | `Admin\Settings\BusinessSettingsController@updateSettings` | web, admin, actch:admin_panel, module:business_settings |
| 1103 | `POST` | `/admin/business-settings/maintenance-mode` | `admin.business-settings.maintenance-mode` | `Admin\Settings\BusinessSettingsController@updateSystemMode` | web, admin, actch:admin_panel, module:business_settings |
| 1104 | `GET` | `/admin/business-settings/website-setup` | `admin.business-settings.website-setup` | `Admin\BusinessSettings\WebsiteSetupController@getView` | web, admin, actch:admin_panel, module:business_settings |
| 1105 | `POST` | `/admin/business-settings/website-setup` | `admin.business-settings.` | `Admin\BusinessSettings\WebsiteSetupController@updateWebsiteSetup` | web, admin, actch:admin_panel, module:business_settings |
| 1106 | `GET` | `/admin/business-settings/vendor-settings` | `admin.business-settings.vendor-settings.index` | `Admin\Settings\VendorSettingsController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1107 | `POST` | `/admin/business-settings/vendor-settings/update-vendor-settings` | `admin.business-settings.vendor-settings.update-vendor-settings` | `Admin\Settings\VendorSettingsController@update` | web, admin, actch:admin_panel, module:business_settings |
| 1108 | `GET` | `/admin/business-settings/product-settings` | `admin.business-settings.product-settings.index` | `Admin\Settings\BusinessSettingsController@getProductSettingsView` | web, admin, actch:admin_panel, module:business_settings |
| 1109 | `POST` | `/admin/business-settings/product-settings` | `admin.business-settings.product-settings.` | `Admin\Settings\BusinessSettingsController@updateProductSettings` | web, admin, actch:admin_panel, module:business_settings |
| 1110 | `GET` | `/admin/business-settings/delivery-man-settings` | `admin.business-settings.delivery-man-settings.index` | `Admin\Settings\DeliverymanSettingsController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1111 | `POST` | `/admin/business-settings/delivery-man-settings/delivery-man-settings/update` | `admin.business-settings.delivery-man-settings.update` | `Admin\Settings\DeliverymanSettingsController@update` | web, admin, actch:admin_panel, module:business_settings |
| 1112 | `POST` | `/admin/business-settings/delivery-man-settings/delivery-man-settings/upload-picture` | `admin.business-settings.delivery-man-settings.upload-picture` | `Admin\Settings\DeliverymanSettingsController@uploadPicture` | web, admin, actch:admin_panel, module:business_settings |
| 1113 | `GET` | `/admin/business-settings/customer-settings` | `admin.business-settings.customer-settings` | `Admin\Customer\CustomerController@getCustomerSettingsView` | web, admin, actch:admin_panel, module:business_settings |
| 1114 | `POST` | `/admin/business-settings/customer-settings` | `admin.business-settings.` | `Admin\Customer\CustomerController@updateCustomer` | web, admin, actch:admin_panel, module:business_settings |
| 1115 | `GET` | `/admin/business-settings/order-settings/index` | `admin.business-settings.order-settings.index` | `Admin\Settings\OrderSettingsController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1116 | `POST` | `/admin/business-settings/order-settings/update-order-settings` | `admin.business-settings.order-settings.update-order-settings` | `Admin\Settings\OrderSettingsController@update` | web, admin, actch:admin_panel, module:business_settings |
| 1117 | `GET` | `/admin/business-settings/refund-setup` | `admin.business-settings.refund-setup` | `Admin\Settings\BusinessSettingsController@getRefundSetupView` | web, admin, actch:admin_panel, module:business_settings |
| 1118 | `POST` | `/admin/business-settings/refund-setup` | `admin.business-settings.refund-setup-update` | `Admin\Settings\BusinessSettingsController@updateRefundSetup` | web, admin, actch:admin_panel, module:business_settings |
| 1119 | `GET` | `/admin/business-settings/shipping-method/index` | `admin.business-settings.shipping-method.index` | `Admin\Shipping\ShippingMethodController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1120 | `POST` | `/admin/business-settings/shipping-method/index` | `admin.business-settings.shipping-method.` | `Admin\Shipping\ShippingMethodController@add` | web, admin, actch:admin_panel, module:business_settings |
| 1121 | `GET` | `/admin/business-settings/shipping-method/update/{id}` | `admin.business-settings.shipping-method.update` | `Admin\Shipping\ShippingMethodController@getUpdateView` | web, admin, actch:admin_panel, module:business_settings |
| 1122 | `POST` | `/admin/business-settings/shipping-method/update/{id}` | `admin.business-settings.shipping-method.` | `Admin\Shipping\ShippingMethodController@update` | web, admin, actch:admin_panel, module:business_settings |
| 1123 | `POST` | `/admin/business-settings/shipping-method/update-status` | `admin.business-settings.shipping-method.update-status` | `Admin\Shipping\ShippingMethodController@updateStatus` | web, admin, actch:admin_panel, module:business_settings |
| 1124 | `POST` | `/admin/business-settings/shipping-method/delete` | `admin.business-settings.shipping-method.delete` | `Admin\Shipping\ShippingMethodController@delete` | web, admin, actch:admin_panel, module:business_settings |
| 1125 | `POST` | `/admin/business-settings/shipping-method/update-shipping-responsibility` | `admin.business-settings.shipping-method.update-shipping-responsibility` | `Admin\Shipping\ShippingMethodController@updateShippingResponsibility` | web, admin, actch:admin_panel, module:business_settings |
| 1126 | `POST` | `/admin/business-settings/shipping-type/index` | `admin.business-settings.shipping-type.index` | `Admin\Shipping\ShippingTypeController@addOrUpdate` | web, admin, actch:admin_panel, module:business_settings |
| 1127 | `POST` | `/admin/business-settings/category-shipping-cost/store` | `admin.business-settings.category-shipping-cost.store` | `Admin\CategoryShippingCostController@add` | web, admin, actch:admin_panel, module:business_settings |
| 1128 | `GET` | `/admin/business-settings/delivery-zone` | `admin.business-settings.delivery-zone.index` | `Admin\Settings\DeliveryRestrictionController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1129 | `POST` | `/admin/business-settings/delivery-zone/add-delivery-country` | `admin.business-settings.delivery-zone.add-delivery-country` | `Admin\Settings\DeliveryRestrictionController@add` | web, admin, actch:admin_panel, module:business_settings |
| 1130 | `DELETE` | `/admin/business-settings/delivery-zone/delivery-country-delete` | `admin.business-settings.delivery-zone.delivery-country-delete` | `Admin\Settings\DeliveryRestrictionController@delete` | web, admin, actch:admin_panel, module:business_settings |
| 1131 | `POST` | `/admin/business-settings/delivery-zone/add-zip-code` | `admin.business-settings.delivery-zone.add-zip-code` | `Admin\Settings\DeliveryRestrictionController@addZipCode` | web, admin, actch:admin_panel, module:business_settings |
| 1132 | `DELETE` | `/admin/business-settings/delivery-zone/zip-code-delete` | `admin.business-settings.delivery-zone.zip-code-delete` | `Admin\Settings\DeliveryRestrictionController@deleteZipCode` | web, admin, actch:admin_panel, module:business_settings |
| 1133 | `POST` | `/admin/business-settings/delivery-zone/country-restriction-status-change` | `admin.business-settings.delivery-zone.country-restriction-status-change` | `Admin\Settings\DeliveryRestrictionController@countryRestrictionStatusChange` | web, admin, actch:admin_panel, module:business_settings |
| 1134 | `POST` | `/admin/business-settings/delivery-zone/zipcode-restriction-status-change` | `admin.business-settings.delivery-zone.zipcode-restriction-status-change` | `Admin\Settings\DeliveryRestrictionController@zipcodeRestrictionStatusChange` | web, admin, actch:admin_panel, module:business_settings |
| 1135 | `GET` | `/admin/business-settings/invoice-settings` | `admin.business-settings.invoice-settings.index` | `Admin\Settings\InvoiceSettingsController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1136 | `POST` | `/admin/business-settings/invoice-settings` | `admin.business-settings.invoice-settings.update` | `Admin\Settings\InvoiceSettingsController@update` | web, admin, actch:admin_panel, module:business_settings |
| 1137 | `GET` | `/admin/business-settings/inhouse-shop` | `admin.business-settings.inhouse-shop` | `Admin\Settings\InhouseShopController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1138 | `POST` | `/admin/business-settings/inhouse-shop` | `admin.business-settings.` | `Admin\Settings\InhouseShopController@update` | web, admin, actch:admin_panel, module:business_settings |
| 1139 | `POST` | `/admin/business-settings/inhouse-shop-temporary-close` | `admin.business-settings.inhouse-shop-temporary-close` | `Admin\Settings\InhouseShopController@getTemporaryClose` | web, admin, actch:admin_panel, module:business_settings |
| 1140 | `POST` | `/admin/business-settings/vacation-update` | `admin.business-settings.inhouse-shop-vacation-update` | `Admin\Settings\InhouseShopController@updateVacation` | web, admin, actch:admin_panel, module:business_settings |
| 1141 | `GET` | `/admin/business-settings/inhouse-shop/setup` | `admin.business-settings.inhouse-shop-setup` | `Admin\Settings\InhouseShopController@getSetupView` | web, admin, actch:admin_panel, module:business_settings |
| 1142 | `POST` | `/admin/business-settings/inhouse-shop/setup` | `admin.business-settings.` | `Admin\Settings\InhouseShopController@updateSetup` | web, admin, actch:admin_panel, module:business_settings |
| 1143 | `GET` | `/admin/business-settings/priority-setup` | `admin.business-settings.priority-setup.index` | `Admin\Settings\PrioritySetupController@index` | web, admin, actch:admin_panel |
| 1144 | `POST` | `/admin/business-settings/priority-setup` | `admin.business-settings.priority-setup.` | `Admin\Settings\PrioritySetupController@update` | web, admin, actch:admin_panel |
| 1145 | `POST` | `/admin/business-settings/priority-setup/update-by-type` | `admin.business-settings.priority-setup.update-by-type` | `Admin\Settings\PrioritySetupController@updateByType` | web, admin, actch:admin_panel |
| 1146 | `GET` | `/admin/seo-settings/web-master-tool` | `admin.seo-settings.web-master-tool` | `Admin\Settings\SEOSettingsController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1147 | `POST` | `/admin/seo-settings/web-master-tool` | `admin.seo-settings.` | `Admin\Settings\SEOSettingsController@updateWebMasterTool` | web, admin, actch:admin_panel, module:business_settings |
| 1148 | `GET` | `/admin/seo-settings/robot-txt` | `admin.seo-settings.robot-txt` | `Admin\Settings\SEOSettingsController@getRobotTxtView` | web, admin, actch:admin_panel, module:business_settings |
| 1149 | `POST` | `/admin/seo-settings/robot-text` | `admin.seo-settings.update-robot-text` | `Admin\Settings\SEOSettingsController@updateRobotText` | web, admin, actch:admin_panel, module:business_settings |
| 1150 | `GET` | `/admin/seo-settings/robots-meta-content` | `admin.seo-settings.robots-meta-content.index` | `Admin\Settings\RobotsMetaContentController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1151 | `POST` | `/admin/seo-settings/robots-meta-content/add-page` | `admin.seo-settings.robots-meta-content.add-page` | `Admin\Settings\RobotsMetaContentController@addPage` | web, admin, actch:admin_panel, module:business_settings |
| 1152 | `GET` | `/admin/seo-settings/robots-meta-content/delete-page` | `admin.seo-settings.robots-meta-content.delete-page` | `Admin\Settings\RobotsMetaContentController@getPageDelete` | web, admin, actch:admin_panel, module:business_settings |
| 1153 | `GET` | `/admin/seo-settings/robots-meta-content/page-content-view` | `admin.seo-settings.robots-meta-content.page-content-view` | `Admin\Settings\RobotsMetaContentController@getPageAddContentView` | web, admin, actch:admin_panel, module:business_settings |
| 1154 | `POST` | `/admin/seo-settings/robots-meta-content/page-content-update` | `admin.seo-settings.robots-meta-content.page-content-update` | `Admin\Settings\RobotsMetaContentController@getPageContentUpdate` | web, admin, actch:admin_panel, module:business_settings |
| 1155 | `GET` | `/admin/seo-settings/sitemap` | `admin.seo-settings.sitemap` | `Admin\Settings\SiteMapController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1156 | `GET` | `/admin/seo-settings/sitemap-generate-download` | `admin.seo-settings.sitemap-generate-download` | `Admin\Settings\SiteMapController@getGenerateAndDownload` | web, admin, actch:admin_panel, module:business_settings |
| 1157 | `GET` | `/admin/seo-settings/sitemap-generate-upload` | `admin.seo-settings.sitemap-generate-upload` | `Admin\Settings\SiteMapController@getGenerateAndUpload` | web, admin, actch:admin_panel, module:business_settings |
| 1158 | `POST` | `/admin/seo-settings/sitemap-manual-upload` | `admin.seo-settings.sitemap-manual-upload` | `Admin\Settings\SiteMapController@getUpload` | web, admin, actch:admin_panel, module:business_settings |
| 1159 | `GET` | `/admin/seo-settings/sitemap-download` | `admin.seo-settings.sitemap-download` | `Admin\Settings\SiteMapController@getDownload` | web, admin, actch:admin_panel, module:business_settings |
| 1160 | `GET` | `/admin/seo-settings/sitemap-delete` | `admin.seo-settings.sitemap-delete` | `Admin\Settings\SiteMapController@getDelete` | web, admin, actch:admin_panel, module:business_settings |
| 1161 | `GET` | `/admin/seo-settings/error-logs/index` | `admin.seo-settings.error-logs.index` | `Admin\Settings\ErrorLogsController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1162 | `POST` | `/admin/seo-settings/error-logs/index` | `admin.seo-settings.error-logs.` | `Admin\Settings\ErrorLogsController@update` | web, admin, actch:admin_panel, module:business_settings |
| 1163 | `DELETE` | `/admin/seo-settings/error-logs/index` | `admin.seo-settings.error-logs.` | `Admin\Settings\ErrorLogsController@delete` | web, admin, actch:admin_panel, module:business_settings |
| 1164 | `DELETE` | `/admin/seo-settings/error-logs/delete-selected-error-logs` | `admin.seo-settings.error-logs.delete-selected-error-logs` | `Admin\Settings\ErrorLogsController@deleteSelectedErrorLogs` | web, admin, actch:admin_panel, module:business_settings |
| 1165 | `GET` | `/admin/pages-and-media/list` | `admin.pages-and-media.list` | `Admin\Settings\PagesController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1166 | `GET` | `/admin/pages-and-media/add` | `admin.pages-and-media.add` | `Admin\Settings\PagesController@getAddView` | web, admin, actch:admin_panel, module:business_settings |
| 1167 | `POST` | `/admin/pages-and-media/add` | `admin.pages-and-media.` | `Admin\Settings\PagesController@getAdd` | web, admin, actch:admin_panel, module:business_settings |
| 1168 | `GET` | `/admin/pages-and-media/update` | `admin.pages-and-media.update` | `Admin\Settings\PagesController@getUpdateView` | web, admin, actch:admin_panel, module:business_settings |
| 1169 | `POST` | `/admin/pages-and-media/update` | `admin.pages-and-media.` | `Admin\Settings\PagesController@getUpdate` | web, admin, actch:admin_panel, module:business_settings |
| 1170 | `POST` | `/admin/pages-and-media/delete-image` | `admin.pages-and-media.delete.image` | `Admin\Settings\PagesController@getDeleteImage` | web, admin, actch:admin_panel, module:business_settings |
| 1171 | `POST` | `/admin/pages-and-media/delete` | `admin.pages-and-media.delete` | `Admin\Settings\PagesController@getDelete` | web, admin, actch:admin_panel, module:business_settings |
| 1172 | `POST` | `/admin/pages-and-media/update-status` | `admin.pages-and-media.update-status` | `Admin\Settings\PagesController@updateStatus` | web, admin, actch:admin_panel, module:business_settings |
| 1173 | `GET` | `/admin/pages-and-media/company-reliability` | `admin.pages-and-media.company-reliability` | `Admin\Settings\FeaturesSectionController@getCompanyReliabilityView` | web, admin, actch:admin_panel, module:business_settings |
| 1174 | `POST` | `/admin/pages-and-media/company-reliability` | `admin.pages-and-media.` | `Admin\Settings\FeaturesSectionController@updateCompanyReliability` | web, admin, actch:admin_panel, module:business_settings |
| 1175 | `GET` | `/admin/pages-and-media/social-media` | `admin.pages-and-media.social-media` | `Admin\Settings\SocialMediaSettingsController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1176 | `GET` | `/admin/pages-and-media/fetch` | `admin.pages-and-media.fetch` | `Admin\Settings\SocialMediaSettingsController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1177 | `POST` | `/admin/pages-and-media/social-media-store` | `admin.pages-and-media.social-media-store` | `Admin\Settings\SocialMediaSettingsController@add` | web, admin, actch:admin_panel, module:business_settings |
| 1178 | `POST` | `/admin/pages-and-media/social-media-edit` | `admin.pages-and-media.social-media-edit` | `Admin\Settings\SocialMediaSettingsController@getUpdate` | web, admin, actch:admin_panel, module:business_settings |
| 1179 | `POST` | `/admin/pages-and-media/social-media-update` | `admin.pages-and-media.social-media-update` | `Admin\Settings\SocialMediaSettingsController@update` | web, admin, actch:admin_panel, module:business_settings |
| 1180 | `POST` | `/admin/pages-and-media/social-media-delete` | `admin.pages-and-media.social-media-delete` | `Admin\Settings\SocialMediaSettingsController@delete` | web, admin, actch:admin_panel, module:business_settings |
| 1181 | `POST` | `/admin/pages-and-media/social-media-status-update` | `admin.pages-and-media.social-media-status-update` | `Admin\Settings\SocialMediaSettingsController@updateStatus` | web, admin, actch:admin_panel, module:business_settings |
| 1182 | `GET` | `/admin/pages-and-media/vendor-registration-settings/index` | `admin.pages-and-media.vendor-registration-settings.index` | `Admin\Settings\VendorRegistrationSettingController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1183 | `POST` | `/admin/pages-and-media/vendor-registration-settings/index` | `admin.pages-and-media.vendor-registration-settings.` | `Admin\Settings\VendorRegistrationSettingController@updateHeaderSection` | web, admin, actch:admin_panel, module:business_settings |
| 1184 | `GET` | `/admin/pages-and-media/vendor-registration-settings/with-us` | `admin.pages-and-media.vendor-registration-settings.with-us` | `Admin\Settings\VendorRegistrationSettingController@getSellWithUsView` | web, admin, actch:admin_panel, module:business_settings |
| 1185 | `POST` | `/admin/pages-and-media/vendor-registration-settings/with-us` | `admin.pages-and-media.vendor-registration-settings.` | `Admin\Settings\VendorRegistrationSettingController@updateSellWithUsSection` | web, admin, actch:admin_panel, module:business_settings |
| 1186 | `GET` | `/admin/pages-and-media/vendor-registration-settings/business-process` | `admin.pages-and-media.vendor-registration-settings.business-process` | `Admin\Settings\VendorRegistrationSettingController@getBusinessProcessView` | web, admin, actch:admin_panel, module:business_settings |
| 1187 | `POST` | `/admin/pages-and-media/vendor-registration-settings/business-process` | `admin.pages-and-media.vendor-registration-settings.` | `Admin\Settings\VendorRegistrationSettingController@updateBusinessProcess` | web, admin, actch:admin_panel, module:business_settings |
| 1188 | `GET` | `/admin/pages-and-media/vendor-registration-settings/download-app` | `admin.pages-and-media.vendor-registration-settings.download-app` | `Admin\Settings\VendorRegistrationSettingController@getDownloadAppView` | web, admin, actch:admin_panel, module:business_settings |
| 1189 | `POST` | `/admin/pages-and-media/vendor-registration-settings/download-app` | `admin.pages-and-media.vendor-registration-settings.` | `Admin\Settings\VendorRegistrationSettingController@updateDownloadAppSection` | web, admin, actch:admin_panel, module:business_settings |
| 1190 | `GET` | `/admin/pages-and-media/vendor-registration-settings/faq` | `admin.pages-and-media.vendor-registration-settings.faq` | `Admin\Settings\VendorRegistrationSettingController@getFAQView` | web, admin, actch:admin_panel, module:business_settings |
| 1191 | `POST` | `/admin/pages-and-media/vendor-registration-reason/add` | `admin.pages-and-media.vendor-registration-reason.add` | `Admin\Settings\VendorRegistrationReasonController@add` | web, admin, actch:admin_panel, module:business_settings |
| 1192 | `GET` | `/admin/pages-and-media/vendor-registration-reason/update` | `admin.pages-and-media.vendor-registration-reason.update` | `Admin\Settings\VendorRegistrationReasonController@getUpdateView` | web, admin, actch:admin_panel, module:business_settings |
| 1193 | `POST` | `/admin/pages-and-media/vendor-registration-reason/update` | `admin.pages-and-media.vendor-registration-reason.` | `Admin\Settings\VendorRegistrationReasonController@update` | web, admin, actch:admin_panel, module:business_settings |
| 1194 | `POST` | `/admin/pages-and-media/vendor-registration-reason/update-status` | `admin.pages-and-media.vendor-registration-reason.update-status` | `Admin\Settings\VendorRegistrationReasonController@updateStatus` | web, admin, actch:admin_panel, module:business_settings |
| 1195 | `POST` | `/admin/pages-and-media/vendor-registration-reason/delete` | `admin.pages-and-media.vendor-registration-reason.delete` | `Admin\Settings\VendorRegistrationReasonController@delete` | web, admin, actch:admin_panel, module:business_settings |
| 1196 | `GET` | `/admin/pages-and-media/features-section` | `admin.pages-and-media.features-section` | `Admin\Settings\FeaturesSectionController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1197 | `POST` | `/admin/pages-and-media/features-section/submit` | `admin.pages-and-media.features-section.submit` | `Admin\Settings\FeaturesSectionController@update` | web, admin, actch:admin_panel, module:business_settings |
| 1198 | `POST` | `/admin/pages-and-media/features-section/icon-remove` | `admin.pages-and-media.features-section.icon-remove` | `Admin\Settings\FeaturesSectionController@delete` | web, admin, actch:admin_panel, module:business_settings |
| 1199 | `GET` | `/admin/business-settings/announcement` | `admin.business-settings.announcement` | `Admin\Settings\BusinessSettingsController@getAnnouncementView` | web, admin, actch:admin_panel, module:promotion_management |
| 1200 | `POST` | `/admin/business-settings/announcement` | `admin.business-settings.` | `Admin\Settings\BusinessSettingsController@updateAnnouncement` | web, admin, actch:admin_panel, module:promotion_management |
| 1201 | `GET` | `/admin/helpTopic/index` | `admin.helpTopic.list` | `Admin\HelpAndSupport\HelpTopicController@index` | web, admin, actch:admin_panel, module:business_settings |
| 1202 | `POST` | `/admin/helpTopic/add-new` | `admin.helpTopic.add-new` | `Admin\HelpAndSupport\HelpTopicController@add` | web, admin, actch:admin_panel, module:business_settings |
| 1203 | `POST` | `/admin/helpTopic/status/{id}` | `admin.helpTopic.status` | `Admin\HelpAndSupport\HelpTopicController@updateStatus` | web, admin, actch:admin_panel, module:business_settings |
| 1204 | `GET` | `/admin/helpTopic/update/{id}` | `admin.helpTopic.update` | `Admin\HelpAndSupport\HelpTopicController@getUpdateResponse` | web, admin, actch:admin_panel, module:business_settings |
| 1205 | `POST` | `/admin/helpTopic/feature-status-update` | `admin.helpTopic.feature-status-update` | `Admin\HelpAndSupport\HelpTopicController@updateFeatureStatus` | web, admin, actch:admin_panel, module:business_settings |
| 1206 | `POST` | `/admin/helpTopic/update/{id}` | `admin.helpTopic.` | `Admin\HelpAndSupport\HelpTopicController@update` | web, admin, actch:admin_panel, module:business_settings |
| 1207 | `POST` | `/admin/helpTopic/delete` | `admin.helpTopic.delete` | `Admin\HelpAndSupport\HelpTopicController@delete` | web, admin, actch:admin_panel, module:business_settings |
| 1208 | `GET` | `/admin/refund-section/refund/list/{status}` | `admin.refund-section.refund.list` | `Admin\Order\RefundController@index` | web, admin, actch:admin_panel, module:order_management |
| 1209 | `GET` | `/admin/refund-section/refund/export/{status}` | `admin.refund-section.refund.export` | `Admin\Order\RefundController@exportList` | web, admin, actch:admin_panel, module:order_management |
| 1210 | `GET` | `/admin/refund-section/refund/details/{id}` | `admin.refund-section.refund.details` | `Admin\Order\RefundController@getDetailsView` | web, admin, actch:admin_panel, module:order_management |
| 1211 | `POST` | `/admin/refund-section/refund/refund-status-update` | `admin.refund-section.refund.refund-status-update` | `Admin\Order\RefundController@updateRefundStatus` | web, admin, actch:admin_panel, module:order_management |
| 1212 | `GET` | `/admin/product/title-auto-fill` | `admin.product.title-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\AIProductController@titleAutoFill` | web, admin, actch:admin_panel |
| 1213 | `GET` | `/admin/product/description-auto-fill` | `admin.product.description-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\AIProductController@descriptionAutoFill` | web, admin, actch:admin_panel |
| 1214 | `GET` | `/admin/product/general-setup-auto-fill` | `admin.product.general-setup-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\AIProductController@generalSetupAutoFill` | web, admin, actch:admin_panel |
| 1215 | `GET` | `/admin/product/price-others-auto-fill` | `admin.product.price-others-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\AIProductController@pricingAndOthersAutoFill` | web, admin, actch:admin_panel |
| 1216 | `GET` | `/admin/product/seo-section-auto-fill` | `admin.product.seo-section-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\AIProductController@productSeoSectionAutoFill` | web, admin, actch:admin_panel |
| 1217 | `GET` | `/admin/product/variation-setup-auto-fill` | `admin.product.variation-setup-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\AIProductController@productVariationSetupAutoFill` | web, admin, actch:admin_panel |
| 1218 | `POST` | `/admin/product/analyze-image-auto-fill` | `admin.product.analyze-image-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\AIProductController@generateTitleFromImages` | web, admin, actch:admin_panel |
| 1219 | `POST` | `/admin/product/generate-title-suggestions` | `admin.product.generate-title-suggestions` | `Modules\AI\app\Http\Controllers\Admin\AIProductController@generateProductTitleSuggestion` | web, admin, actch:admin_panel |
| 1220 | `GET` | `/admin/blog/title-auto-fill` | `admin.blog.title-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\Blog\AIBlogController@titleAutoFill` | web, admin, actch:admin_panel |
| 1221 | `GET` | `/admin/blog/description-auto-fill` | `admin.blog.description-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\Blog\AIBlogController@descriptionAutoFill` | web, admin, actch:admin_panel |
| 1222 | `POST` | `/admin/blog/seo-section-auto-fill` | `admin.blog.seo-section-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\Blog\AIBlogController@seoSectionAutoFill` | web, admin, actch:admin_panel |
| 1223 | `POST` | `/admin/blog/generate-title-suggestions` | `admin.blog.generate-title-suggestions` | `Modules\AI\app\Http\Controllers\Admin\Blog\AIBlogController@generateBlogTitleSuggestion` | web, admin, actch:admin_panel |
| 1224 | `POST` | `/admin/blog/analyze-image-auto-fill` | `admin.blog.analyze-image-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\Blog\AIBlogController@generateBlogTitleFromImages` | web, admin, actch:admin_panel |
| 1225 | `GET` | `/admin/third-party/ai-setting` | `admin.third-party.ai-setting.index` | `Modules\AI\app\Http\Controllers\Admin\AISettingController@index` | web, admin, actch:admin_panel |
| 1226 | `POST` | `/admin/third-party/ai-setting/store` | `admin.third-party.ai-setting.store` | `Modules\AI\app\Http\Controllers\Admin\AISettingController@store` | web, admin, actch:admin_panel |
| 1227 | `GET` | `/admin/third-party/ai-setting/vendors-usage-limits` | `admin.third-party.ai-setting.vendors-usage-limits` | `Modules\AI\app\Http\Controllers\Admin\AISettingController@getVendorUsagesLimitView` | web, admin, actch:admin_panel |
| 1228 | `POST` | `/admin/third-party/ai-setting/vendors-usage-limits/update` | `admin.third-party.ai-setting.vendors-usage-limits-update` | `Modules\AI\app\Http\Controllers\Admin\AISettingController@updateVendorUsagesLimit` | web, admin, actch:admin_panel |
| 1229 | `POST` | `/admin/blog/category/add` | `admin.blog.category.add` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@add` | web, admin |
| 1230 | `POST` | `/admin/blog/category/category-info` | `admin.blog.category.info` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@getCategoryInfo` | web, admin |
| 1231 | `POST` | `/admin/blog/category/update` | `admin.blog.category.update` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@update` | web, admin |
| 1232 | `GET` | `/admin/blog/category/status` | `admin.blog.category.status-update` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@updateStatus` | web, admin |
| 1233 | `DELETE` | `/admin/blog/category/delete` | `admin.blog.category.delete` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@deleteCategory` | web, admin |
| 1234 | `POST` | `/admin/blog/category/search` | `admin.blog.category.search` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@search` | web, admin |
| 1235 | `GET` | `/admin/blog/category/get-list` | `admin.blog.category.get-list` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@getList` | web, admin |
| 1236 | `GET` | `/admin/blog/view` | `admin.blog.view` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@index` | web, admin |
| 1237 | `POST` | `/admin/blog/intro` | `admin.blog.intro` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@updateIntro` | web, admin |
| 1238 | `GET` | `/admin/blog/add` | `admin.blog.add` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@getAddView` | web, admin |
| 1239 | `POST` | `/admin/blog/add` | `admin.blog.store` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@addBlog` | web, admin |
| 1240 | `GET` | `/admin/blog/edit` | `admin.blog.edit` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@getUpdateView` | web, admin |
| 1241 | `POST` | `/admin/blog/update` | `admin.blog.update` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@update` | web, admin |
| 1242 | `POST` | `/admin/blog/status-update` | `admin.blog.status-update` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@updateStatus` | web, admin |
| 1243 | `POST` | `/admin/blog/blog-status-update/{id}` | `admin.blog.blog-status-update` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@updateBlogStatus` | web, admin |
| 1244 | `GET` | `/admin/blog/draft-edit/{id}` | `admin.blog.draft-edit` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@draftEdit` | web, admin |
| 1245 | `POST` | `/admin/blog/delete` | `admin.blog.delete` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@delete` | web, admin |
| 1246 | `POST` | `/admin/blog/section-view` | `admin.blog.section-view` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@sectionView` | web, admin |
| 1247 | `GET` | `/admin/blog/app-download-setup` | `admin.blog.app-download-setup` | `Modules\Blog\app\Http\Controllers\Admin\BlogDownloadAppController@appDownloadSetup` | web, admin |
| 1248 | `POST` | `/admin/blog/app-download-setup` | `admin.blog.` | `Modules\Blog\app\Http\Controllers\Admin\BlogDownloadAppController@updateDownloadAppButton` | web, admin |
| 1249 | `POST` | `/admin/blog/app-download-setup-status` | `admin.blog.app-download-setup-status` | `Modules\Blog\app\Http\Controllers\Admin\BlogDownloadAppController@updateStatus` | web, admin |
| 1250 | `POST` | `/admin/blog/delete-image` | `admin.blog.delete-image` | `Modules\Blog\app\Http\Controllers\Admin\BlogDownloadAppController@deleteImage` | web, admin |
| 1251 | `GET` | `/admin/blog/priority-setup` | `admin.blog.priority-setup.index` | `Modules\Blog\app\Http\Controllers\Admin\BlogPrioritySetupController@index` | web, admin |
| 1252 | `POST` | `/admin/blog/priority-setup` | `admin.blog.priority-setup.` | `Modules\Blog\app\Http\Controllers\Admin\BlogPrioritySetupController@update` | web, admin |
| 1253 | `GET` | `/admin/vat-tax/list` | `admin.vat-tax.index` | `Modules\TaxModule\app\Http\Controllers\TaxVatController@index` | web, admin |
| 1254 | `POST` | `/admin/vat-tax/add-vat-tax-data` | `admin.vat-tax.store` | `Modules\TaxModule\app\Http\Controllers\TaxVatController@store` | web, admin |
| 1255 | `GET|POST|PUT|PATCH|DELETE|OPTIONS` | `/admin/vat-tax/update-vat-tax-data` | `admin.vat-tax.update` | `Modules\TaxModule\app\Http\Controllers\TaxVatController@update` | web, admin |
| 1256 | `POST` | `/admin/vat-tax/update-vat-tax-status` | `admin.vat-tax.status` | `Modules\TaxModule\app\Http\Controllers\TaxVatController@updateStatus` | web, admin |
| 1257 | `GET` | `/admin/vat-tax/export-vat-tax` | `admin.vat-tax.export` | `Modules\TaxModule\app\Http\Controllers\TaxVatController@export` | web, admin |
| 1258 | `GET` | `/admin/vat-tax/system-vat-tax` | `admin.vat-tax.systemVatTax` | `Modules\TaxModule\app\Http\Controllers\SystemTaxVatSetupController@index` | web, admin |
| 1259 | `POST` | `/admin/vat-tax/system-vat-tax` | `admin.vat-tax.systemTaxVatStore` | `Modules\TaxModule\app\Http\Controllers\SystemTaxVatSetupController@systemTaxVatStore` | web, admin |
| 1260 | `POST` | `/admin/vat-tax/system-vat-tax-vendor-status` | `admin.vat-tax.systemTaxVatVendorStatus` | `Modules\TaxModule\app\Http\Controllers\SystemTaxVatSetupController@vendorStatus` | web, admin |
| 1261 | `GET` | `/admin/report/get-tax-report` | `admin.report.get-tax-report` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\AdminTaxReportController@getTaxReport` | web, admin |
| 1262 | `GET` | `/admin/report/get-tax-details` | `admin.report.getTaxDetails` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\AdminTaxReportController@getTaxDetails` | web, admin |
| 1263 | `GET` | `/admin/report/tax-details-report-export` | `admin.report.getTaxDetailsExport` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\AdminTaxReportController@adminTaxDetailsExport` | web, admin |
| 1264 | `GET` | `/admin/report/admin-tax-report-export` | `admin.report.adminTaxReportExport` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\AdminTaxReportController@adminTaxReportExport` | web, admin |
| 1265 | `GET` | `/admin/report/vendor-wise-taxes` | `admin.report.vendor-wise-taxes` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\VendorTaxReportController@vendorWiseTaxes` | web, admin |
| 1266 | `GET` | `/admin/report/vendor-wise-taxes-export` | `admin.report.vendorWiseTaxExport` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\VendorTaxReportController@vendorWiseTaxExport` | web, admin |
| 1267 | `GET` | `/admin/report/vendor-tax-report` | `admin.report.vendorTax` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\VendorTaxReportController@vendorTax` | web, admin |
| 1268 | `GET` | `/admin/report/vendor-tax-export` | `admin.report.vendorTaxExport` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\VendorTaxReportController@vendorTaxExport` | web, admin |

---

### 🏷️ Domain: Merchant / Vendor Panel (205 Endpoints)

| # | Method(s) | URI / Route | Route Name | Controller Action | Security Middleware |
| :-: | :--- | :--- | :--- | :--- | :--- |
| 1269 | `GET` | `/vendor/auth/login` | `vendor.auth.` | `Vendor\Auth\LoginController@getLoginView` | web, maintenance_mode, actch:admin_panel |
| 1270 | `POST` | `/vendor/auth/login` | `vendor.auth.login` | `Vendor\Auth\LoginController@login` | web, maintenance_mode, actch:admin_panel |
| 1271 | `GET` | `/vendor/auth/vendor.auth.login` | `vendor.auth.logout` | `Vendor\Auth\LoginController@logout` | web, maintenance_mode, actch:admin_panel |
| 1272 | `GET` | `/vendor/auth/forgot-password/index` | `vendor.auth.forgot-password.index` | `Vendor\Auth\ForgotPasswordController@index` | web, maintenance_mode, actch:admin_panel |
| 1273 | `POST` | `/vendor/auth/forgot-password/index` | `vendor.auth.forgot-password.` | `Vendor\Auth\ForgotPasswordController@getPasswordResetRequest` | web, maintenance_mode, actch:admin_panel |
| 1274 | `GET` | `/vendor/auth/forgot-password/otp-verification` | `vendor.auth.forgot-password.otp-verification` | `Vendor\Auth\ForgotPasswordController@getOTPVerificationView` | web, maintenance_mode, actch:admin_panel |
| 1275 | `POST` | `/vendor/auth/forgot-password/otp-verification` | `vendor.auth.forgot-password.` | `Vendor\Auth\ForgotPasswordController@submitOTPVerificationCode` | web, maintenance_mode, actch:admin_panel |
| 1276 | `GET` | `/vendor/auth/forgot-password/reset-password` | `vendor.auth.forgot-password.reset-password` | `Vendor\Auth\ForgotPasswordController@getPasswordResetView` | web, maintenance_mode, actch:admin_panel |
| 1277 | `POST` | `/vendor/auth/forgot-password/reset-password` | `vendor.auth.forgot-password.` | `Vendor\Auth\ForgotPasswordController@resetPassword` | web, maintenance_mode, actch:admin_panel |
| 1278 | `GET` | `/vendor/auth/registration/index` | `vendor.auth.registration.index` | `Vendor\Auth\RegisterController@index` | web, maintenance_mode, actch:admin_panel |
| 1279 | `POST` | `/vendor/auth/registration/add` | `vendor.auth.registration.add` | `Vendor\Auth\RegisterController@add` | web, maintenance_mode, actch:admin_panel |
| 1280 | `GET` | `/vendor/pos-sso` | `vendor.pos.sso` | `Vendor\POS\POSController@ssoRedirect` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1281 | `GET` | `/vendor/dashboard` | `vendor.dashboard.index` | `Vendor\DashboardController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1282 | `GET` | `/vendor/dashboard/order-status/{type}` | `vendor.dashboard.order-status` | `Vendor\DashboardController@getOrderStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1283 | `GET` | `/vendor/dashboard/earning-statistics` | `vendor.dashboard.earning-statistics` | `Vendor\DashboardController@getEarningStatistics` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1284 | `POST` | `/vendor/dashboard/withdraw-request` | `vendor.dashboard.withdraw-request` | `Vendor\DashboardController@getWithdrawRequest` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1285 | `GET` | `/vendor/dashboard/withdraw-request` | `vendor.dashboard.method-list` | `Vendor\DashboardController@getMethodList` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1286 | `GET` | `/vendor/dashboard/real-time-activities` | `vendor.dashboard.real-time-activities` | `Vendor\DashboardController@getRealTimeActivities` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1287 | `GET` | `/vendor/refund/index/{status}` | `vendor.refund.index` | `Vendor\RefundController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1288 | `GET` | `/vendor/refund/details/{id}` | `vendor.refund.details` | `Vendor\RefundController@getDetailsView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1289 | `POST` | `/vendor/refund/update-status` | `vendor.refund.update-status` | `Vendor\RefundController@updateStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1290 | `GET` | `/vendor/refund/export/{status}` | `vendor.refund.export` | `Vendor\RefundController@exportList` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1291 | `GET` | `/vendor/products/list/{type}` | `vendor.products.list` | `Vendor\Product\ProductController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1292 | `GET` | `/vendor/products/add` | `vendor.products.add` | `Vendor\Product\ProductController@getAddView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1293 | `POST` | `/vendor/products/add` | `vendor.products.` | `Vendor\Product\ProductController@add` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1294 | `GET` | `/vendor/products/get-categories` | `vendor.products.get-categories` | `Vendor\Product\ProductController@getCategories` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1295 | `POST` | `/vendor/products/sku-combination` | `vendor.products.sku-combination` | `Vendor\Product\ProductController@getSkuCombinationView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1296 | `POST` | `/vendor/products/digital-variation-combination` | `vendor.products.digital-variation-combination` | `Vendor\Product\ProductController@getDigitalVariationCombinationView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1297 | `POST` | `/vendor/products/digital-variation-file-delete` | `vendor.products.digital-variation-file-delete` | `Vendor\Product\ProductController@deleteDigitalVariationFile` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1298 | `POST` | `/vendor/products/status-update` | `vendor.products.status-update` | `Vendor\Product\ProductController@updateStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1299 | `GET` | `/vendor/products/export-excel/{type}` | `vendor.products.export-excel` | `Vendor\Product\ProductController@exportList` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1300 | `GET` | `/vendor/products/view/{id}` | `vendor.products.view` | `Vendor\Product\ProductController@getView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1301 | `GET` | `/vendor/products/barcode/{id}` | `vendor.products.barcode` | `Vendor\Product\ProductController@getBarcodeView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1302 | `DELETE` | `/vendor/products/delete/{id}` | `vendor.products.delete` | `Vendor\Product\ProductController@delete` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1303 | `GET` | `/vendor/products/stock-limit-list` | `vendor.products.stock-limit-list` | `Vendor\Product\ProductController@getStockLimitListView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1304 | `POST` | `/vendor/products/update-quantity` | `vendor.products.update-quantity` | `Vendor\Product\ProductController@updateQuantity` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1305 | `GET` | `/vendor/products/update/{id}` | `vendor.products.update` | `Vendor\Product\ProductController@getUpdateView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1306 | `POST` | `/vendor/products/update/{id}` | `vendor.products.` | `Vendor\Product\ProductController@update` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1307 | `POST` | `/vendor/products/quick-price-stock-update` | `vendor.products.quick-price-stock-update` | `Vendor\Product\ProductController@quickPriceStockUpdate` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1308 | `POST` | `/vendor/products/update-product-images/{id}` | `vendor.products.update-product-images` | `Vendor\Product\ProductController@updateProductImages` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1309 | `GET` | `/vendor/products/delete-image` | `vendor.products.delete-image` | `Vendor\Product\ProductController@deleteImage` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1310 | `GET` | `/vendor/products/get-variations` | `vendor.products.get-variations` | `Vendor\Product\ProductController@getVariations` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1311 | `GET` | `/vendor/products/bulk-import` | `vendor.products.bulk-import` | `Vendor\Product\ProductController@getBulkImportView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1312 | `POST` | `/vendor/products/bulk-import` | `vendor.products.` | `Vendor\Product\ProductController@importBulkProduct` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1313 | `GET` | `/vendor/products/search` | `vendor.products.search-product` | `Vendor\Product\ProductController@getSearchedProductsView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1314 | `GET` | `/vendor/products/product-gallery` | `vendor.products.product-gallery` | `Vendor\Product\ProductController@getProductGalleryView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1315 | `GET` | `/vendor/products/stock-limit-status` | `vendor.products.stock-limit-status` | `Vendor\Product\ProductController@getStockLimitStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1316 | `POST` | `/vendor/products/delete-preview-file` | `vendor.products.delete-preview-file` | `Vendor\Product\ProductController@deletePreviewFile` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1317 | `GET` | `/vendor/products/request-restock-list` | `vendor.products.request-restock-list` | `Vendor\Product\ProductController@getRequestRestockListView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1318 | `GET` | `/vendor/products/export-restock` | `vendor.products.restock-export` | `Vendor\Product\ProductController@exportRestockList` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1319 | `DELETE` | `/vendor/products/delete-restock/{id}` | `vendor.products.restock-delete` | `Vendor\Product\ProductController@deleteRestock` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1320 | `GET` | `/vendor/products/get-category-specifications/{category_id}` | `vendor.products.get-category-specifications` | `Admin\Product\CategorySpecificationController@getByCategoryAjax` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1321 | `POST` | `/vendor/products/ai-suggest-specs` | `vendor.products.ai-suggest-specs` | `Admin\Product\CategorySpecificationController@aiSuggestSpecs` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1322 | `POST` | `/vendor/products/load-more-brands` | `vendor.products.load-more-brands` | `Vendor\Product\ProductController@loadMoreBrands` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1323 | `GET` | `/vendor/orders/list/{status}` | `vendor.orders.list` | `Vendor\Order\OrderController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1324 | `GET` | `/vendor/orders/customers` | `vendor.orders.customers` | `Vendor\Order\OrderController@getCustomers` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1325 | `GET` | `/vendor/orders/export-excel/{status}` | `vendor.orders.export-excel` | `Vendor\Order\OrderController@exportList` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1326 | `GET` | `/vendor/orders/generate-invoice/{id}` | `vendor.orders.generate-invoice` | `Vendor\Order\OrderController@generateInvoice` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1327 | `GET` | `/vendor/orders/generate-packing-slip/{id}` | `vendor.orders.generate-packing-slip` | `Vendor\Order\OrderController@generatePackingSlip` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1328 | `GET` | `/vendor/orders/details/{id}` | `vendor.orders.details` | `Vendor\Order\OrderController@getView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1329 | `POST` | `/vendor/orders/address-update` | `vendor.orders.address-update` | `Vendor\Order\OrderController@updateAddress` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1330 | `POST` | `/vendor/orders/payment-status` | `vendor.orders.payment-status` | `Vendor\Order\OrderController@updatePaymentStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1331 | `POST` | `/vendor/orders/update-deliver-info` | `vendor.orders.update-deliver-info` | `Vendor\Order\OrderController@updateDeliverInfo` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1332 | `GET` | `/vendor/orders/add-delivery-man/{order_id}/{d_man_id}` | `vendor.orders.add-delivery-man` | `Vendor\Order\OrderController@addDeliveryMan` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1333 | `POST` | `/vendor/orders/amount-date-update` | `vendor.orders.amount-date-update` | `Vendor\Order\OrderController@updateAmountDate` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1334 | `POST` | `/vendor/orders/digital-file-upload-after-sell` | `vendor.orders.digital-file-upload-after-sell` | `Vendor\Order\OrderController@uploadDigitalFileAfterSell` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1335 | `POST` | `/vendor/orders/status` | `vendor.orders.status` | `Vendor\Order\OrderController@updateStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1336 | `POST` | `/vendor/orders/customer-return-amount` | `vendor.orders.customer-return-amount` | `Vendor\Order\OrderController@orderReturnAmountToCustomer` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1337 | `POST` | `/vendor/orders/customer-due-amount` | `vendor.orders.customer-due-amount` | `Vendor\Order\OrderController@orderDueAmountSwitchToCOD` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1338 | `POST` | `/vendor/orders/customer-due-amount-mark-as-paid` | `vendor.orders.customer-due-amount-mark-as-paid` | `Vendor\Order\OrderController@orderDueAmountMarkAsPaid` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1339 | `GET` | `/vendor/orders/search-for-edit-order-product` | `vendor.orders.search-for-edit-order-product` | `Vendor\Order\OrderEditController@getSearchEditOrderProductsView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1340 | `POST` | `/vendor/orders/edit-order-product-modal-view` | `vendor.orders.edit-order-product-modal-view` | `Vendor\Order\OrderEditController@getEditOrderProductModalView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1341 | `POST` | `/vendor/orders/edit-order-product-add` | `vendor.orders.edit-order-product-add` | `Vendor\Order\OrderEditController@addEditOrderProduct` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1342 | `POST` | `/vendor/orders/edit-order-product-variant-price` | `vendor.orders.edit-order-product-variant-price` | `Vendor\Order\OrderEditController@checkProductVariantPrice` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1343 | `POST` | `/vendor/orders/edit-order-product-list-update` | `vendor.orders.edit-order-product-list-update` | `Vendor\Order\OrderEditController@updateEditOrderProductList` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1344 | `POST` | `/vendor/orders/edit-order-product-remove` | `vendor.orders.edit-order-product-remove` | `Vendor\Order\OrderEditController@removeEditOrderProduct` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1345 | `POST` | `/vendor/orders/edit-order-generate` | `vendor.orders.edit-order-generate` | `Vendor\Order\OrderEditController@generateEditOrderByProductList` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1346 | `GET` | `/vendor/customer/list` | `vendor.customer.list` | `Vendor\CustomerController@getList` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1347 | `POST` | `/vendor/customer/add` | `vendor.customer.add` | `Vendor\CustomerController@add` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1348 | `GET` | `/vendor/reviews/index` | `vendor.reviews.index` | `Vendor\ReviewController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1349 | `GET` | `/vendor/reviews/update-status/{id}/{status}` | `vendor.reviews.update-status` | `Vendor\ReviewController@updateStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1350 | `GET` | `/vendor/reviews/export` | `vendor.reviews.export` | `Vendor\ReviewController@exportList` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1351 | `POST` | `/vendor/reviews/add-review-reply` | `vendor.reviews.add-review-reply` | `Vendor\ReviewController@addReviewReply` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1352 | `GET` | `/vendor/coupon/index` | `vendor.coupon.index` | `Vendor\Coupon\CouponController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1353 | `POST` | `/vendor/coupon/add` | `vendor.coupon.add` | `Vendor\Coupon\CouponController@add` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1354 | `GET` | `/vendor/coupon/update/{id}` | `vendor.coupon.update` | `Vendor\Coupon\CouponController@getUpdateView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1355 | `POST` | `/vendor/coupon/update/{id}` | `vendor.coupon.` | `Vendor\Coupon\CouponController@update` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1356 | `GET` | `/vendor/coupon/update-status/{id}/{status}` | `vendor.coupon.update-status` | `Vendor\Coupon\CouponController@updateStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1357 | `DELETE` | `/vendor/coupon/delete/{id}` | `vendor.coupon.delete` | `Vendor\Coupon\CouponController@delete` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1358 | `GET` | `/vendor/coupon/quick-view` | `vendor.coupon.quick-view` | `Vendor\Coupon\CouponController@getQuickView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1359 | `GET` | `/vendor/coupon/export` | `vendor.coupon.export` | `Vendor\Coupon\CouponController@exportList` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1360 | `GET` | `/vendor/clearance-sale` | `vendor.clearance-sale.index` | `Vendor\Promotion\ClearanceSaleController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1361 | `POST` | `/vendor/clearance-sale/status-update` | `vendor.clearance-sale.status-update` | `Vendor\Promotion\ClearanceSaleController@updateStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1362 | `POST` | `/vendor/clearance-sale/update-config` | `vendor.clearance-sale.update-config` | `Vendor\Promotion\ClearanceSaleController@updateClearanceConfig` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1363 | `POST` | `/vendor/clearance-sale/update-seo-meta` | `vendor.clearance-sale.update-seo-meta` | `Vendor\Promotion\ClearanceSaleController@updateClearanceSeoConfig` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1364 | `GET` | `/vendor/clearance-sale/search` | `vendor.clearance-sale.search-product-for-clearance` | `Vendor\Promotion\ClearanceSaleController@getSearchedProductsView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1365 | `GET` | `/vendor/clearance-sale/multiple-product-details` | `vendor.clearance-sale.multiple-clearance-product-details` | `Vendor\Promotion\ClearanceSaleController@getMultipleProductDetailsView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1366 | `POST` | `/vendor/clearance-sale/add-clearance-product` | `vendor.clearance-sale.add-product` | `Vendor\Promotion\ClearanceSaleController@addClearanceProduct` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1367 | `POST` | `/vendor/clearance-sale/clearance-product-status-update` | `vendor.clearance-sale.product-status-update` | `Vendor\Promotion\ClearanceSaleController@updateProductStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1368 | `DELETE` | `/vendor/clearance-sale/clearance-delete/{product_id}` | `vendor.clearance-sale.clearance-delete` | `Vendor\Promotion\ClearanceSaleController@deleteClearanceProduct` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1369 | `DELETE` | `/vendor/clearance-sale/clearance-products-delete` | `vendor.clearance-sale.clearance-delete-all-product` | `Vendor\Promotion\ClearanceSaleController@deleteClearanceAllProduct` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1370 | `POST` | `/vendor/clearance-sale/update-discount` | `vendor.clearance-sale.update-discount` | `Vendor\Promotion\ClearanceSaleController@updateDiscountAmount` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1371 | `GET` | `/vendor/messages/index/{type}` | `vendor.messages.index` | `Vendor\ChattingController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1372 | `GET` | `/vendor/messages/message` | `vendor.messages.message` | `Vendor\ChattingController@getMessageByUser` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1373 | `POST` | `/vendor/messages/message` | `vendor.messages.` | `Vendor\ChattingController@addVendorMessage` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1374 | `GET` | `/vendor/messages/new-notification` | `vendor.messages.new-notification` | `Vendor\ChattingController@getNewNotification` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1375 | `POST` | `/vendor/notification/index` | `vendor.notification.index` | `Vendor\NotificationController@getNotificationModalView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1376 | `GET` | `/vendor/delivery-man/index` | `vendor.delivery-man.index` | `Vendor\DeliveryMan\DeliveryManController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1377 | `POST` | `/vendor/delivery-man/index` | `vendor.delivery-man.` | `Vendor\DeliveryMan\DeliveryManController@add` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1378 | `GET` | `/vendor/delivery-man/list` | `vendor.delivery-man.list` | `Vendor\DeliveryMan\DeliveryManController@getListView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1379 | `GET` | `/vendor/delivery-man/export` | `vendor.delivery-man.export` | `Vendor\DeliveryMan\DeliveryManController@exportList` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1380 | `GET` | `/vendor/delivery-man/update/{id}` | `vendor.delivery-man.update` | `Vendor\DeliveryMan\DeliveryManController@getUpdateView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1381 | `POST` | `/vendor/delivery-man/update/{id}` | `vendor.delivery-man.` | `Vendor\DeliveryMan\DeliveryManController@update` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1382 | `POST` | `/vendor/delivery-man/update-status/{id}` | `vendor.delivery-man.update-status` | `Vendor\DeliveryMan\DeliveryManController@updateStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1383 | `DELETE` | `/vendor/delivery-man/delete/{id}` | `vendor.delivery-man.delete` | `Vendor\DeliveryMan\DeliveryManController@delete` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1384 | `GET` | `/vendor/delivery-man/rating/{id}` | `vendor.delivery-man.rating` | `Vendor\DeliveryMan\DeliveryManController@getRatingView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1385 | `GET` | `/vendor/delivery-man/wallet/index/{id}` | `vendor.delivery-man.wallet.index` | `Vendor\DeliveryMan\DeliveryManWalletController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1386 | `GET` | `/vendor/delivery-man/wallet/order-history/{id}` | `vendor.delivery-man.wallet.order-history` | `Vendor\DeliveryMan\DeliveryManWalletController@getOrderHistory` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1387 | `GET` | `/vendor/delivery-man/wallet/order-history-status/{order}` | `vendor.delivery-man.wallet.order-status-history` | `Vendor\DeliveryMan\DeliveryManWalletController@getOrderStatusHistory` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1388 | `GET` | `/vendor/delivery-man/wallet/earning/{id}` | `vendor.delivery-man.wallet.earning` | `Vendor\DeliveryMan\DeliveryManWalletController@getEarningListView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1389 | `GET` | `/vendor/delivery-man/wallet/cash-collect/{id}` | `vendor.delivery-man.wallet.cash-collect` | `Vendor\DeliveryMan\DeliveryManWalletController@getCashCollectView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1390 | `POST` | `/vendor/delivery-man/wallet/cash-collect/{id}` | `vendor.delivery-man.wallet.` | `Vendor\DeliveryMan\DeliveryManWalletController@collectCash` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1391 | `GET` | `/vendor/delivery-man/withdraw/index` | `vendor.delivery-man.withdraw.index` | `Vendor\DeliveryMan\DeliveryManWithdrawController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1392 | `POST` | `/vendor/delivery-man/withdraw/index` | `vendor.delivery-man.withdraw.` | `Vendor\DeliveryMan\DeliveryManWithdrawController@getFiltered` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1393 | `GET` | `/vendor/delivery-man/withdraw/details/{withdrawId}` | `vendor.delivery-man.withdraw.details` | `Vendor\DeliveryMan\DeliveryManWithdrawController@getDetails` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1394 | `POST` | `/vendor/delivery-man/withdraw/update-status/{withdrawId}` | `vendor.delivery-man.withdraw.update-status` | `Vendor\DeliveryMan\DeliveryManWithdrawController@updateStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1395 | `GET|POST|PUT|PATCH|DELETE|OPTIONS` | `/vendor/delivery-man/withdraw/export` | `vendor.delivery-man.withdraw.export` | `Vendor\DeliveryMan\DeliveryManWithdrawController@exportList` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1396 | `GET` | `/vendor/delivery-man/emergency-contact/index` | `vendor.delivery-man.emergency-contact.index` | `Vendor\DeliveryMan\EmergencyContactController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1397 | `POST` | `/vendor/delivery-man/emergency-contact/index` | `vendor.delivery-man.emergency-contact.` | `Vendor\DeliveryMan\EmergencyContactController@add` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1398 | `GET` | `/vendor/delivery-man/emergency-contact/update/{id}` | `vendor.delivery-man.emergency-contact.update` | `Vendor\DeliveryMan\EmergencyContactController@getUpdateView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1399 | `POST` | `/vendor/delivery-man/emergency-contact/update/{id}` | `vendor.delivery-man.emergency-contact.` | `Vendor\DeliveryMan\EmergencyContactController@update` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1400 | `PATCH` | `/vendor/delivery-man/emergency-contact/index` | `vendor.delivery-man.emergency-contact.` | `Vendor\DeliveryMan\EmergencyContactController@updateStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1401 | `DELETE` | `/vendor/delivery-man/emergency-contact/index` | `vendor.delivery-man.emergency-contact.` | `Vendor\DeliveryMan\EmergencyContactController@delete` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1402 | `GET` | `/vendor/profile/index` | `vendor.profile.index` | `Vendor\ProfileController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1403 | `GET` | `/vendor/profile/update/{id}` | `vendor.profile.update` | `Vendor\ProfileController@getUpdateView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1404 | `POST` | `/vendor/profile/update/{id}` | `vendor.profile.` | `Vendor\ProfileController@update` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1405 | `PATCH` | `/vendor/profile/update/{id}` | `vendor.profile.` | `Vendor\ProfileController@updatePassword` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1406 | `GET` | `/vendor/profile/update-bank-info/{id}` | `vendor.profile.update-bank-info` | `Vendor\ProfileController@getBankInfoUpdateView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1407 | `POST` | `/vendor/profile/update-bank-info/{id}` | `vendor.profile.` | `Vendor\ProfileController@updateBankInfo` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1408 | `GET` | `/vendor/shop/index` | `vendor.shop.index` | `Vendor\ShopController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1409 | `GET` | `/vendor/shop/update/{id}` | `vendor.shop.update` | `Vendor\ShopController@getUpdateView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1410 | `POST` | `/vendor/shop/update/{id}` | `vendor.shop.` | `Vendor\ShopController@update` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1411 | `POST` | `/vendor/shop/add-vacation` | `vendor.shop.update-vacation` | `Vendor\ShopController@updateVacation` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1412 | `POST` | `/vendor/shop/close-shop-temporary` | `vendor.shop.close-shop-temporary` | `Vendor\ShopController@closeShopTemporary` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1413 | `POST` | `/vendor/shop/update-other-settings` | `vendor.shop.update-other-settings` | `Vendor\ShopController@updateOtherSettings` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1414 | `GET` | `/vendor/shop/other-setup` | `vendor.shop.other-setup` | `Vendor\ShopController@getOtherSetupView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1415 | `GET` | `/vendor/shop/payment-information` | `vendor.shop.payment-information.index` | `Vendor\VendorPaymentInfoController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1416 | `POST` | `/vendor/shop/payment-information/add` | `vendor.shop.payment-information.add` | `Vendor\VendorPaymentInfoController@add` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1417 | `POST` | `/vendor/shop/payment-information/update` | `vendor.shop.payment-information.update` | `Vendor\VendorPaymentInfoController@update` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1418 | `GET` | `/vendor/shop/payment-information/edit/{id?}` | `vendor.shop.payment-information.update-view` | `Vendor\VendorPaymentInfoController@getUpdateView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1419 | `GET` | `/vendor/shop/payment-information/delete/{id?}` | `vendor.shop.payment-information.delete` | `Vendor\VendorPaymentInfoController@delete` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1420 | `POST` | `/vendor/shop/payment-information/default` | `vendor.shop.payment-information.default` | `Vendor\VendorPaymentInfoController@updateDefault` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1421 | `POST` | `/vendor/shop/payment-information/status` | `vendor.shop.payment-information.update-status` | `Vendor\VendorPaymentInfoController@updateStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1422 | `GET` | `/vendor/shop/payment-information/dynamic-fields` | `vendor.shop.payment-information.dynamic-fields` | `Vendor\VendorPaymentInfoController@getDynamicPaymentInformationView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1423 | `GET` | `/vendor/business-settings/shipping-method/index` | `vendor.business-settings.shipping-method.index` | `Vendor\Shipping\ShippingMethodController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1424 | `POST` | `/vendor/business-settings/shipping-method/index` | `vendor.business-settings.shipping-method.` | `Vendor\Shipping\ShippingMethodController@add` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1425 | `GET` | `/vendor/business-settings/shipping-method/update/{id}` | `vendor.business-settings.shipping-method.update` | `Vendor\Shipping\ShippingMethodController@getUpdateView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1426 | `POST` | `/vendor/business-settings/shipping-method/update/{id}` | `vendor.business-settings.shipping-method.` | `Vendor\Shipping\ShippingMethodController@update` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1427 | `POST` | `/vendor/business-settings/shipping-method/update-status` | `vendor.business-settings.shipping-method.update-status` | `Vendor\Shipping\ShippingMethodController@updateStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1428 | `POST` | `/vendor/business-settings/shipping-method/delete` | `vendor.business-settings.shipping-method.delete` | `Vendor\Shipping\ShippingMethodController@delete` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1429 | `POST` | `/vendor/business-settings/shipping-type/index` | `vendor.business-settings.shipping-type.index` | `Vendor\Shipping\ShippingTypeController@addOrUpdate` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1430 | `POST` | `/vendor/business-settings/category-wise-shipping-cost/index` | `vendor.business-settings.category-wise-shipping-cost.index` | `Vendor\Shipping\CategoryShippingCostController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1431 | `GET` | `/vendor/business-settings/withdraw/index` | `vendor.business-settings.withdraw.index` | `Vendor\WithdrawController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1432 | `POST` | `/vendor/business-settings/withdraw/index` | `vendor.business-settings.withdraw.` | `Vendor\WithdrawController@getListByStatus` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1433 | `GET` | `/vendor/business-settings/withdraw/close/{id}` | `vendor.business-settings.withdraw.close` | `Vendor\WithdrawController@closeWithdrawRequest` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1434 | `GET` | `/vendor/business-settings/withdraw/export` | `vendor.business-settings.withdraw.export-withdraw-list` | `Vendor\WithdrawController@exportList` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1435 | `POST` | `/vendor/business-settings/withdraw/render-withdraw-method-infos` | `vendor.business-settings.withdraw.render-withdraw-method-infos` | `Vendor\WithdrawController@renderInfosView` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1436 | `GET` | `/vendor/get-order-data` | `vendor.get-order-data` | `Vendor\SystemController@getOrderData` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1437 | `GET` | `/vendor/report/all-product` | `vendor.report.all-product` | `Vendor\ProductReportController@all_product` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1438 | `GET` | `/vendor/report/all-product-excel` | `vendor.report.all-product-excel` | `Vendor\ProductReportController@allProductExportExcel` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1439 | `GET` | `/vendor/report/stock-product-report` | `vendor.report.stock-product-report` | `Vendor\ProductReportController@stock_product_report` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1440 | `GET` | `/vendor/report/product-stock-export` | `vendor.report.product-stock-export` | `Vendor\ProductReportController@productStockExport` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1441 | `GET` | `/vendor/report/order-report` | `vendor.report.order-report` | `Vendor\OrderReportController@order_report` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1442 | `GET` | `/vendor/report/order-report-excel` | `vendor.report.order-report-excel` | `Vendor\OrderReportController@orderReportExportExcel` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1443 | `GET` | `/vendor/report/order-report-pdf` | `vendor.report.order-report-pdf` | `Vendor\OrderReportController@exportOrderReportInPDF` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1444 | `GET` | `/vendor/transaction/order-list` | `vendor.transaction.order-list` | `Vendor\TransactionReportController@order_transaction_list` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1445 | `GET` | `/vendor/transaction/pdf-order-wise-transaction` | `vendor.transaction.pdf-order-wise-transaction` | `Vendor\TransactionReportController@pdf_order_wise_transaction` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1446 | `GET` | `/vendor/transaction/order-transaction-export-excel` | `vendor.transaction.order-transaction-export-excel` | `Vendor\TransactionReportController@orderTransactionExportExcel` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1447 | `GET` | `/vendor/transaction/expense-transaction-summary-pdf` | `vendor.transaction.expense-transaction-summary-pdf` | `Vendor\TransactionReportController@expense_transaction_summary_pdf` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1448 | `GET` | `/vendor/transaction/expense-transaction-export-excel` | `vendor.transaction.expense-transaction-export-excel` | `Vendor\TransactionReportController@expenseTransactionExportExcel` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1449 | `GET` | `/vendor/employee-role` | `vendor.employee-role.index` | `Vendor\Employee\VendorRoleController@index` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1450 | `POST` | `/vendor/employee-role/store` | `vendor.employee-role.store` | `Vendor\Employee\VendorRoleController@store` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1451 | `GET` | `/vendor/employee-role/edit/{id}` | `vendor.employee-role.edit` | `Vendor\Employee\VendorRoleController@edit` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1452 | `POST` | `/vendor/employee-role/update/{id}` | `vendor.employee-role.update` | `Vendor\Employee\VendorRoleController@update` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1453 | `POST` | `/vendor/employee-role/status` | `vendor.employee-role.status` | `Vendor\Employee\VendorRoleController@status` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1454 | `GET` | `/vendor/employee/list` | `vendor.employee.list` | `Vendor\Employee\VendorEmployeeController@list` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1455 | `GET` | `/vendor/employee/add-new` | `vendor.employee.add-new` | `Vendor\Employee\VendorEmployeeController@addNew` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1456 | `POST` | `/vendor/employee/store` | `vendor.employee.store` | `Vendor\Employee\VendorEmployeeController@store` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1457 | `GET` | `/vendor/employee/edit/{id}` | `vendor.employee.edit` | `Vendor\Employee\VendorEmployeeController@edit` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1458 | `POST` | `/vendor/employee/update/{id}` | `vendor.employee.update` | `Vendor\Employee\VendorEmployeeController@update` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1459 | `POST` | `/vendor/employee/status` | `vendor.employee.status` | `Vendor\Employee\VendorEmployeeController@status` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1460 | `POST` | `/vendor/orders/verify-pickup-otp` | `vendor.orders.verify-pickup-otp` | `Vendor\Order\InShopHandoverController@verifyPickupOtp` | web, maintenance_mode, actch:admin_panel, seller, vendor_employee |
| 1461 | `GET` | `/vendors` | `vendors` | `Web\WebController@getAllVendorsView` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 1462 | `GET` | `/vendor-shop/{slug}` | `vendor-shop` | `Web\ShopViewController@seller_shop` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 1463 | `POST` | `/vendor-shop/{id}` | `—` | `Web\WebController@seller_shop_product` | web, logUserBrowsingNavigation, maintenance_mode, guestCheck |
| 1464 | `GET` | `/vendor/product/title-auto-fill` | `vendor.product.title-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@titleAutoFill` | web, seller |
| 1465 | `GET` | `/vendor/product/description-auto-fill` | `vendor.product.description-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@descriptionAutoFill` | web, seller |
| 1466 | `GET` | `/vendor/product/general-setup-auto-fill` | `vendor.product.general-setup-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@generalSetupAutoFill` | web, seller |
| 1467 | `GET` | `/vendor/product/price-others-auto-fill` | `vendor.product.price-others-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@pricingAndOthersAutoFill` | web, seller |
| 1468 | `GET` | `/vendor/product/seo-section-auto-fill` | `vendor.product.seo-section-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@productSeoSectionAutoFill` | web, seller |
| 1469 | `GET` | `/vendor/product/variation-setup-auto-fill` | `vendor.product.variation-setup-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@productVariationSetupAutoFill` | web, seller |
| 1470 | `POST` | `/vendor/product/analyze-image-auto-fill` | `vendor.product.analyze-image-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@generateTitleFromImages` | web, seller |
| 1471 | `POST` | `/vendor/product/generate-title-suggestions` | `vendor.product.generate-title-suggestions` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@generateProductTitleSuggestion` | web, seller |
| 1472 | `GET` | `/vendor/report/get-vat-report` | `vendor.report.get-vat-report` | `Modules\TaxModule\app\Http\Controllers\Vendor\Reports\TaxReportController@vendorTaxReportList` | web, seller |
| 1473 | `GET` | `/vendor/report/get-vat-report-export` | `vendor.report.get-vat-report-export` | `Modules\TaxModule\app\Http\Controllers\Vendor\Reports\TaxReportController@vendorTaxExport` | web, seller |

---

### 🏷️ Domain: Customer Web Portal (37 Endpoints)

| # | Method(s) | URI / Route | Route Name | Controller Action | Security Middleware |
| :-: | :--- | :--- | :--- | :--- | :--- |
| 1474 | `GET` | `/customer/auth/login` | `customer.auth.login` | `Customer\Auth\CustomerAuthController@loginView` | web, logUserBrowsingNavigation |
| 1475 | `POST` | `/customer/auth/login` | `customer.auth.` | `Customer\Auth\CustomerAuthController@loginSubmit` | web, logUserBrowsingNavigation |
| 1476 | `GET` | `/customer/auth/login/verify-account` | `customer.auth.login.verify-account` | `Customer\Auth\CustomerAuthController@loginVerifyPhone` | web, logUserBrowsingNavigation |
| 1477 | `POST` | `/customer/auth/login/verify-account/submit` | `customer.auth.login.verify-account.submit` | `Customer\Auth\CustomerAuthController@verifyAccount` | web, logUserBrowsingNavigation |
| 1478 | `GET` | `/customer/auth/login/update-info` | `customer.auth.login.update-info` | `Customer\Auth\CustomerAuthController@updateInfo` | web, logUserBrowsingNavigation |
| 1479 | `POST` | `/customer/auth/login/update-info` | `customer.auth.` | `Customer\Auth\CustomerAuthController@updateInfoSubmit` | web, logUserBrowsingNavigation |
| 1480 | `POST` | `/customer/auth/login/resend-otp-code` | `customer.auth.resend-otp-code` | `Customer\Auth\CustomerAuthController@resendOTPCode` | web, logUserBrowsingNavigation |
| 1481 | `GET` | `/customer/auth/logout` | `customer.auth.logout` | `Customer\Auth\LoginController@logout` | web, logUserBrowsingNavigation |
| 1482 | `GET` | `/customer/auth/get-login-modal-data` | `customer.auth.get-login-modal-data` | `Customer\Auth\LoginController@getLoginModalView` | web, logUserBrowsingNavigation |
| 1483 | `GET` | `/customer/auth/sign-up` | `customer.auth.sign-up` | `Customer\Auth\RegisterController@getRegisterView` | web, logUserBrowsingNavigation |
| 1484 | `POST` | `/customer/auth/sign-up` | `customer.auth.` | `Customer\Auth\RegisterController@submitRegisterData` | web, logUserBrowsingNavigation |
| 1485 | `GET` | `/customer/auth/check-verification` | `customer.auth.check-verification` | `Customer\Auth\RegisterController@verificationCheckView` | web, logUserBrowsingNavigation |
| 1486 | `POST` | `/customer/auth/verify` | `customer.auth.verify` | `Customer\Auth\RegisterController@verifyRegistration` | web, logUserBrowsingNavigation |
| 1487 | `POST` | `/customer/auth/ajax-verify` | `customer.auth.ajax_verify` | `Customer\Auth\RegisterController@ajax_verify` | web, logUserBrowsingNavigation |
| 1488 | `POST` | `/customer/auth/resend-otp` | `customer.auth.resend_otp` | `Customer\Auth\RegisterController@resendOTPToCustomer` | web, logUserBrowsingNavigation |
| 1489 | `GET` | `/customer/auth/login/{service}` | `customer.auth.service-login` | `Customer\Auth\SocialAuthController@redirectToProvider` | web, logUserBrowsingNavigation |
| 1490 | `GET` | `/customer/auth/login/{service}/callback` | `customer.auth.service-callback` | `Customer\Auth\SocialAuthController@handleProviderCallback` | web, logUserBrowsingNavigation |
| 1491 | `GET` | `/customer/auth/login/social/confirmation` | `customer.auth.social-login-confirmation` | `Customer\Auth\SocialAuthController@socialLoginConfirmation` | web, logUserBrowsingNavigation |
| 1492 | `POST` | `/customer/auth/login/social/confirmation/update` | `customer.auth.social-login-confirmation.update` | `Customer\Auth\SocialAuthController@updateSocialLoginConfirmation` | web, logUserBrowsingNavigation |
| 1493 | `POST` | `/customer/auth/login/social/verify-account` | `customer.auth.login.social.verify-account` | `Customer\Auth\SocialAuthController@verifyAccount` | web, logUserBrowsingNavigation |
| 1494 | `GET` | `/customer/auth/recover-password` | `customer.auth.recover-password` | `Customer\Auth\ForgotPasswordController@reset_password` | web, logUserBrowsingNavigation |
| 1495 | `POST` | `/customer/auth/forgot-password` | `customer.auth.forgot-password` | `Customer\Auth\ForgotPasswordController@resetPasswordRequest` | web, logUserBrowsingNavigation |
| 1496 | `POST` | `/customer/auth/verify-recover-password` | `customer.auth.verify-recover-password` | `Customer\Auth\ForgotPasswordController@verifyRecoverPassword` | web, logUserBrowsingNavigation |
| 1497 | `GET` | `/customer/auth/otp-verification` | `customer.auth.otp-verification` | `Customer\Auth\ForgotPasswordController@otp_verification` | web, logUserBrowsingNavigation |
| 1498 | `POST` | `/customer/auth/otp-verification` | `customer.auth.` | `Customer\Auth\ForgotPasswordController@otp_verification_submit` | web, logUserBrowsingNavigation |
| 1499 | `GET` | `/customer/auth/reset-password` | `customer.auth.reset-password` | `Customer\Auth\ForgotPasswordController@resetPasswordView` | web, logUserBrowsingNavigation |
| 1500 | `POST` | `/customer/auth/reset-password` | `customer.auth.password-recovery` | `Customer\Auth\ForgotPasswordController@resetPasswordSubmit` | web, logUserBrowsingNavigation |
| 1501 | `POST` | `/customer/auth/resend-otp-reset-password` | `customer.auth.resend-otp-reset-password` | `Customer\Auth\ForgotPasswordController@resendPhoneOTPRequest` | web, logUserBrowsingNavigation |
| 1502 | `GET` | `/customer/set-payment-method/{name}` | `customer.set-payment-method` | `Customer\SystemController@setPaymentMethod` | web, logUserBrowsingNavigation |
| 1503 | `GET` | `/customer/set-shipping-method` | `customer.set-shipping-method` | `Customer\SystemController@setShippingMethod` | web, logUserBrowsingNavigation |
| 1504 | `POST` | `/customer/choose-shipping-address` | `customer.choose-shipping-address` | `Customer\SystemController@getChooseShippingAddress` | web, logUserBrowsingNavigation |
| 1505 | `POST` | `/customer/choose-shipping-address-other` | `customer.choose-shipping-address-other` | `Customer\SystemController@getChooseShippingAddressOther` | web, logUserBrowsingNavigation |
| 1506 | `POST` | `/customer/choose-billing-address` | `customer.choose-billing-address` | `Customer\SystemController@getChooseShippingAddress` | web, logUserBrowsingNavigation |
| 1507 | `GET` | `/customer/reward-points/convert` | `customer.reward-points.convert` | `Customer\RewardPointController@convert` | web, logUserBrowsingNavigation, auth:customer |
| 1508 | `POST` | `/customer/web-payment-request` | `customer.web-payment-request` | `Customer\PaymentController@payment` | web, logUserBrowsingNavigation |
| 1509 | `POST` | `/customer/customer-add-fund-request` | `customer.add-fund-request` | `Customer\PaymentController@customer_add_to_fund_request` | web, logUserBrowsingNavigation |
| 1510 | `POST` | `/customer/customer-order-edit-pay-amount` | `customer.customer-order-edit-pay-amount` | `Customer\PaymentController@customerOrderEditPayDueAmount` | web, logUserBrowsingNavigation |

---

### 🏷️ Domain: Native POS Module (42 Endpoints)

| # | Method(s) | URI / Route | Route Name | Controller Action | Security Middleware |
| :-: | :--- | :--- | :--- | :--- | :--- |
| 1511 | `GET` | `/pos` | `pos.dashboard` | `Modules\Pos\Http\Controllers\DashboardController@index` | web, seller |
| 1512 | `GET` | `/pos/terminal` | `pos.index` | `Modules\Pos\Http\Controllers\PosController@index` | web, seller |
| 1513 | `POST` | `/pos/checkout` | `pos.checkout` | `Modules\Pos\Http\Controllers\PosController@checkout` | web, seller |
| 1514 | `GET` | `/pos/receipt/{id}` | `pos.receipt` | `Modules\Pos\Http\Controllers\PosController@receipt` | web, seller |
| 1515 | `GET` | `/pos/returns` | `pos.returns` | `Modules\Pos\Http\Controllers\PosController@returns` | web, seller |
| 1516 | `POST` | `/pos/returns/process` | `pos.returns.process` | `Modules\Pos\Http\Controllers\PosController@processReturn` | web, seller |
| 1517 | `POST` | `/pos/customer/quick-register` | `pos.customer.quick_register` | `Modules\Pos\Http\Controllers\PosController@quickRegisterCustomer` | web, seller |
| 1518 | `GET` | `/pos/products` | `pos.products.index` | `Modules\Pos\Http\Controllers\ProductController@index` | web, seller |
| 1519 | `GET` | `/pos/products/template/csv` | `pos.products.template.csv` | `Modules\Pos\Http\Controllers\ProductController@downloadCsvTemplate` | web, seller |
| 1520 | `GET` | `/pos/products/export/csv` | `pos.products.export.csv` | `Modules\Pos\Http\Controllers\ProductController@exportCsv` | web, seller |
| 1521 | `GET` | `/pos/products/export/json` | `pos.products.export.json` | `Modules\Pos\Http\Controllers\ProductController@exportJson` | web, seller |
| 1522 | `POST` | `/pos/products/import/csv` | `pos.products.import.csv` | `Modules\Pos\Http\Controllers\ProductController@importCsv` | web, seller |
| 1523 | `POST` | `/pos/products` | `pos.products.store` | `Modules\Pos\Http\Controllers\ProductController@store` | web, seller |
| 1524 | `POST` | `/pos/products/{id}` | `pos.products.update` | `Modules\Pos\Http\Controllers\ProductController@update` | web, seller |
| 1525 | `DELETE` | `/pos/products/{id}` | `pos.products.destroy` | `Modules\Pos\Http\Controllers\ProductController@destroy` | web, seller |
| 1526 | `GET` | `/pos/warehouses` | `pos.warehouses.index` | `Modules\Pos\Http\Controllers\WarehouseController@index` | web, seller |
| 1527 | `GET` | `/pos/warehouses/create` | `pos.warehouses.create` | `Modules\Pos\Http\Controllers\WarehouseController@create` | web, seller |
| 1528 | `POST` | `/pos/warehouses` | `pos.warehouses.store` | `Modules\Pos\Http\Controllers\WarehouseController@store` | web, seller |
| 1529 | `GET` | `/pos/warehouses/{id}` | `pos.warehouses.show` | `Modules\Pos\Http\Controllers\WarehouseController@show` | web, seller |
| 1530 | `GET` | `/pos/warehouses/{id}/edit` | `pos.warehouses.edit` | `Modules\Pos\Http\Controllers\WarehouseController@edit` | web, seller |
| 1531 | `PUT` | `/pos/warehouses/{id}` | `pos.warehouses.update` | `Modules\Pos\Http\Controllers\WarehouseController@update` | web, seller |
| 1532 | `DELETE` | `/pos/warehouses/{id}` | `pos.warehouses.destroy` | `Modules\Pos\Http\Controllers\WarehouseController@destroy` | web, seller |
| 1533 | `GET` | `/pos/warehouses/ajax/cities/{state_id}` | `pos.warehouses.cities-ajax` | `Modules\Pos\Http\Controllers\WarehouseController@getCitiesAjax` | web, seller |
| 1534 | `GET` | `/pos/warehouses/ajax/hubs/{city_id}` | `pos.warehouses.hubs-ajax` | `Modules\Pos\Http\Controllers\WarehouseController@getHubsAjax` | web, seller |
| 1535 | `GET` | `/pos/stock` | `pos.stock.index` | `Modules\Pos\Http\Controllers\StockController@index` | web, seller |
| 1536 | `POST` | `/pos/stock/in` | `pos.stock.in` | `Modules\Pos\Http\Controllers\StockController@stockIn` | web, seller |
| 1537 | `GET` | `/pos/stock/transfers` | `pos.stock.transfers` | `Modules\Pos\Http\Controllers\StockController@transfers` | web, seller |
| 1538 | `POST` | `/pos/stock/transfers` | `pos.stock.transfers.create` | `Modules\Pos\Http\Controllers\StockController@createTransfer` | web, seller |
| 1539 | `GET` | `/pos/stock/adjustments` | `pos.stock.adjustments` | `Modules\Pos\Http\Controllers\StockController@adjustments` | web, seller |
| 1540 | `POST` | `/pos/stock/adjustments` | `pos.stock.adjustments.create` | `Modules\Pos\Http\Controllers\StockController@createAdjustment` | web, seller |
| 1541 | `GET` | `/pos/transactions` | `pos.transactions.index` | `Modules\Pos\Http\Controllers\TransactionController@index` | web, seller |
| 1542 | `GET` | `/pos/transactions/cashier-shifts` | `pos.transactions.cashier-shifts` | `Modules\Pos\Http\Controllers\TransactionController@cashierShifts` | web, seller |
| 1543 | `GET` | `/pos/transactions/inventory-log` | `pos.transactions.inventory-log` | `Modules\Pos\Http\Controllers\TransactionController@inventoryLog` | web, seller |
| 1544 | `GET` | `/pos/transactions/export` | `pos.transactions.export` | `Modules\Pos\Http\Controllers\TransactionController@export` | web, seller |
| 1545 | `GET` | `/pos/debts` | `pos.debts.index` | `Modules\Pos\Http\Controllers\DebtController@index` | web, seller |
| 1546 | `GET` | `/pos/debts/customer/{id}` | `pos.debts.customer` | `Modules\Pos\Http\Controllers\DebtController@customerLedger` | web, seller |
| 1547 | `POST` | `/pos/debts/payment` | `pos.debts.payment` | `Modules\Pos\Http\Controllers\DebtController@recordPayment` | web, seller |
| 1548 | `GET` | `/pos/debts/export` | `pos.debts.export` | `Modules\Pos\Http\Controllers\DebtController@export` | web, seller |
| 1549 | `GET` | `/pos/reports` | `pos.reports.index` | `Modules\Pos\Http\Controllers\ReportController@index` | web, seller |
| 1550 | `GET` | `/pos/reports/profit-loss` | `pos.reports.profit-loss` | `Modules\Pos\Http\Controllers\ReportController@profitLoss` | web, seller |
| 1551 | `GET` | `/pos/reports/top-products` | `pos.reports.top-products` | `Modules\Pos\Http\Controllers\ReportController@topProducts` | web, seller |
| 1552 | `GET` | `/pos/reports/export` | `pos.reports.export` | `Modules\Pos\Http\Controllers\ReportController@export` | web, seller |

---

### 🏷️ Domain: Delivery & Logistics Hub Module (21 Endpoints)

| # | Method(s) | URI / Route | Route Name | Controller Action | Security Middleware |
| :-: | :--- | :--- | :--- | :--- | :--- |
| 1553 | `GET` | `/delivery` | `delivery.dashboard` | `Modules\Delivery\app\Http\Controllers\DashboardController@index` | web, admin |
| 1554 | `GET` | `/delivery/dashboard` | `delivery.dashboard.index` | `Modules\Delivery\app\Http\Controllers\DashboardController@index` | web, admin |
| 1555 | `GET` | `/delivery/hubs` | `delivery.hubs.index` | `Modules\Delivery\app\Http\Controllers\HubController@index` | web, admin |
| 1556 | `GET` | `/delivery/hubs/create` | `delivery.hubs.create` | `Modules\Delivery\app\Http\Controllers\HubController@create` | web, admin |
| 1557 | `POST` | `/delivery/hubs/store` | `delivery.hubs.store` | `Modules\Delivery\app\Http\Controllers\HubController@store` | web, admin |
| 1558 | `GET` | `/delivery/hubs/show/{id}` | `delivery.hubs.show` | `Modules\Delivery\app\Http\Controllers\HubController@show` | web, admin |
| 1559 | `POST` | `/delivery/hubs/update/{id}` | `delivery.hubs.update` | `Modules\Delivery\app\Http\Controllers\HubController@update` | web, admin |
| 1560 | `POST` | `/delivery/hubs/status-toggle` | `delivery.hubs.status-toggle` | `Modules\Delivery\app\Http\Controllers\HubController@toggleStatus` | web, admin |
| 1561 | `GET` | `/delivery/hubs/ajax/cities/{state_id}` | `delivery.hubs.ajax.cities` | `Modules\Delivery\app\Http\Controllers\HubController@ajaxGetCities` | web, admin |
| 1562 | `GET` | `/delivery/routes` | `delivery.routes.index` | `Modules\Delivery\app\Http\Controllers\RouteController@index` | web, admin |
| 1563 | `POST` | `/delivery/routes/store` | `delivery.routes.store` | `Modules\Delivery\app\Http\Controllers\RouteController@store` | web, admin |
| 1564 | `POST` | `/delivery/routes/update/{id}` | `delivery.routes.update` | `Modules\Delivery\app\Http\Controllers\RouteController@update` | web, admin |
| 1565 | `POST` | `/delivery/routes/status-toggle` | `delivery.routes.status-toggle` | `Modules\Delivery\app\Http\Controllers\RouteController@toggleStatus` | web, admin |
| 1566 | `GET` | `/delivery/fleet` | `delivery.fleet.index` | `Modules\Delivery\app\Http\Controllers\FleetController@index` | web, admin |
| 1567 | `POST` | `/delivery/fleet/company/store` | `delivery.fleet.company.store` | `Modules\Delivery\app\Http\Controllers\FleetController@storeCompany` | web, admin |
| 1568 | `GET` | `/delivery/fleet/rider/{id}` | `delivery.fleet.rider.show` | `Modules\Delivery\app\Http\Controllers\FleetController@showRider` | web, admin |
| 1569 | `GET` | `/delivery/shipments` | `delivery.shipments.index` | `Modules\Delivery\app\Http\Controllers\ShipmentController@index` | web, admin |
| 1570 | `POST` | `/delivery/shipments/batch/create` | `delivery.shipments.batch.create` | `Modules\Delivery\app\Http\Controllers\ShipmentController@createBatch` | web, admin |
| 1571 | `GET` | `/delivery/shipments/waybill/{id}` | `delivery.shipments.waybill` | `Modules\Delivery\app\Http\Controllers\ShipmentController@waybill` | web, admin |
| 1572 | `GET` | `/delivery/finance` | `delivery.finance.index` | `Modules\Delivery\app\Http\Controllers\FinanceController@index` | web, admin |
| 1573 | `POST` | `/delivery/finance/remittance/record` | `delivery.finance.remittance.record` | `Modules\Delivery\app\Http\Controllers\FinanceController@recordRemittance` | web, admin |


