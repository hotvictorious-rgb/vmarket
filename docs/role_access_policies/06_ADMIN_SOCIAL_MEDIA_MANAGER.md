# Role Security & Endpoint Access Policy: Admin Staff: Social Media Manager

> **Role Scope:** `Admin Staff: Social Media Manager`  
> **Total Allowed Endpoints:** `87`  
> **Total Disallowed / Blocked Endpoints:** `1496`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

Super Admin employee assigned to digital marketing, promotional announcements, push notifications, and blog content.

### Core Authorized Capabilities:
- ✅ **Storefront Promotional Banners & Hero Sliders**
- ✅ **Push Notification Campaigns & Flash Deal Scheduling**
- ✅ **Blog Post Publishing, News & Editorial Articles**
- ✅ **Social Media Links & Platform Announcement Banners**

### Strict Architectural Restrictions:
- ⛔ **Blocked from financial ledgers, order dispatches, and admin role assignments**
- ⛔ **Blocked from merchant store settings or payment gateway configuration**

---

## 2. Authorized Endpoints Access Matrix (87 Endpoints)

| # | Method | URI | Route Name | Action / Controller |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/admin/dashboard` | `admin.dashboard.index` | `App\Http\Controllers\Admin\DashboardController@index` |
| 2 | `POST` | `/admin/dashboard/order-status` | `admin.dashboard.order-status` | `App\Http\Controllers\Admin\DashboardController@getOrderStatus` |
| 3 | `GET` | `/admin/dashboard/earning-statistics` | `admin.dashboard.earning-statistics` | `App\Http\Controllers\Admin\DashboardController@getEarningStatistics` |
| 4 | `GET` | `/admin/dashboard/order-statistics` | `admin.dashboard.order-statistics` | `App\Http\Controllers\Admin\DashboardController@getOrderStatistics` |
| 5 | `GET` | `/admin/dashboard/real-time-activities` | `admin.dashboard.real-time-activities` | `App\Http\Controllers\Admin\DashboardController@getRealTimeActivities` |
| 6 | `GET` | `/admin/banner/list` | `admin.banner.list` | `App\Http\Controllers\Admin\Promotion\BannerController@index` |
| 7 | `POST` | `/admin/banner/add` | `admin.banner.store` | `App\Http\Controllers\Admin\Promotion\BannerController@add` |
| 8 | `POST` | `/admin/banner/delete` | `admin.banner.delete` | `App\Http\Controllers\Admin\Promotion\BannerController@delete` |
| 9 | `POST` | `/admin/banner/status` | `admin.banner.status` | `App\Http\Controllers\Admin\Promotion\BannerController@updateStatus` |
| 10 | `GET` | `/admin/banner/update/{id}` | `admin.banner.update` | `App\Http\Controllers\Admin\Promotion\BannerController@getUpdateView` |
| 11 | `POST` | `/admin/banner/update/{id}` | `admin.banner.` | `App\Http\Controllers\Admin\Promotion\BannerController@update` |
| 12 | `GET` | `/admin/deal/flash` | `admin.deal.flash` | `App\Http\Controllers\Admin\Promotion\FlashDealController@index` |
| 13 | `GET` | `/admin/deal/flash/add` | `admin.deal.flash-add` | `App\Http\Controllers\Admin\Promotion\FlashDealController@getAddView` |
| 14 | `POST` | `/admin/deal/flash` | `admin.deal.` | `App\Http\Controllers\Admin\Promotion\FlashDealController@add` |
| 15 | `GET` | `/admin/deal/update/{id}` | `admin.deal.update` | `App\Http\Controllers\Admin\Promotion\FlashDealController@getUpdateView` |
| 16 | `POST` | `/admin/deal/update/{id}` | `admin.deal.update-data` | `App\Http\Controllers\Admin\Promotion\FlashDealController@update` |
| 17 | `POST` | `/admin/deal/status-update` | `admin.deal.status-update` | `App\Http\Controllers\Admin\Promotion\FlashDealController@updateStatus` |
| 18 | `POST` | `/admin/deal/delete-product` | `admin.deal.delete-product` | `App\Http\Controllers\Admin\Promotion\FlashDealController@delete` |
| 19 | `GET` | `/admin/deal/add-product/{deal_id}` | `admin.deal.add-product` | `App\Http\Controllers\Admin\Promotion\FlashDealController@getAddProductView` |
| 20 | `POST` | `/admin/deal/add-product/{deal_id}` | `admin.deal.` | `App\Http\Controllers\Admin\Promotion\FlashDealController@addProduct` |
| 21 | `GET` | `/admin/deal/search-product` | `admin.deal.search-product` | `App\Http\Controllers\Admin\Promotion\FlashDealController@search` |
| 22 | `GET` | `/admin/deal/day` | `admin.deal.day` | `App\Http\Controllers\Admin\Promotion\DealOfTheDayController@index` |
| 23 | `POST` | `/admin/deal/day` | `admin.deal.` | `App\Http\Controllers\Admin\Promotion\DealOfTheDayController@add` |
| 24 | `POST` | `/admin/deal/day-status-update` | `admin.deal.day-status-update` | `App\Http\Controllers\Admin\Promotion\DealOfTheDayController@updateStatus` |
| 25 | `GET` | `/admin/deal/day-update/{id}` | `admin.deal.day-update` | `App\Http\Controllers\Admin\Promotion\DealOfTheDayController@getUpdateView` |
| 26 | `POST` | `/admin/deal/day-update/{id}` | `admin.deal.` | `App\Http\Controllers\Admin\Promotion\DealOfTheDayController@update` |
| 27 | `POST` | `/admin/deal/day-delete` | `admin.deal.day-delete` | `App\Http\Controllers\Admin\Promotion\DealOfTheDayController@delete` |
| 28 | `GET` | `/admin/deal/feature` | `admin.deal.feature` | `App\Http\Controllers\Admin\Promotion\FeaturedDealController@index` |
| 29 | `GET` | `/admin/deal/feature/new` | `admin.deal.feature-add` | `App\Http\Controllers\Admin\Promotion\FeaturedDealController@getAddView` |
| 30 | `GET` | `/admin/deal/feature-update/{id}` | `admin.deal.edit` | `App\Http\Controllers\Admin\Promotion\FeaturedDealController@getUpdateView` |
| 31 | `POST` | `/admin/deal/feature-update` | `admin.deal.featured-update` | `App\Http\Controllers\Admin\Promotion\FeaturedDealController@update` |
| 32 | `POST` | `/admin/deal/feature-status` | `admin.deal.feature-status` | `App\Http\Controllers\Admin\Promotion\FeaturedDealController@updateStatus` |
| 33 | `GET` | `/admin/deal/clearance-sale` | `admin.deal.clearance-sale.index` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleController@index` |
| 34 | `POST` | `/admin/deal/clearance-sale/status-update` | `admin.deal.clearance-sale.status-update` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleController@updateStatus` |
| 35 | `POST` | `/admin/deal/clearance-sale/update-config` | `admin.deal.clearance-sale.update-config` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleController@updateClearanceConfig` |
| 36 | `POST` | `/admin/deal/clearance-sale/update-seo-meta` | `admin.deal.clearance-sale.update-seo-meta` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleController@updateClearanceSeoConfig` |
| 37 | `GET` | `/admin/deal/clearance-sale/search` | `admin.deal.clearance-sale.search-product-for-clearance` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleController@getSearchedProductsView` |
| 38 | `GET` | `/admin/deal/clearance-sale/multiple-product-details` | `admin.deal.clearance-sale.multiple-clearance-product-details` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleController@getMultipleProductDetailsView` |
| 39 | `POST` | `/admin/deal/clearance-sale/add-clearance-product` | `admin.deal.clearance-sale.add-product` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleController@addClearanceProduct` |
| 40 | `POST` | `/admin/deal/clearance-sale/clearance-product-status-update` | `admin.deal.clearance-sale.product-status-update` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleController@updateProductStatus` |
| 41 | `DELETE` | `/admin/deal/clearance-sale/clearance-delete/{product_id}` | `admin.deal.clearance-sale.clearance-delete` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleController@deleteClearanceProduct` |
| 42 | `DELETE` | `/admin/deal/clearance-sale/clearance-products-delete` | `admin.deal.clearance-sale.clearance-delete-all-product` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleController@deleteClearanceAllProduct` |
| 43 | `POST` | `/admin/deal/clearance-sale/update-discount` | `admin.deal.clearance-sale.update-discount` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleController@updateDiscountAmount` |
| 44 | `GET` | `/admin/deal/clearance-sale/vendor-offers` | `admin.deal.clearance-sale.vendor-offers` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleVendorOfferController@index` |
| 45 | `GET` | `/admin/deal/clearance-sale/vendor-search` | `admin.deal.clearance-sale.search-vendor-for-clearance` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleVendorOfferController@getSearchedVendorsView` |
| 46 | `POST` | `/admin/deal/clearance-sale/vendor-add` | `admin.deal.clearance-sale.vendor-add` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleVendorOfferController@addClearanceVendorProduct` |
| 47 | `POST` | `/admin/deal/clearance-sale/update-status` | `admin.deal.clearance-sale.update-vendor-status` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleVendorOfferController@updateVendorStatus` |
| 48 | `POST` | `/admin/deal/clearance-sale/update-offer-status` | `admin.deal.clearance-sale.update-vendor-offer-status` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleVendorOfferController@updateVendorOfferStatus` |
| 49 | `DELETE` | `/admin/deal/clearance-sale/delete-vendor/{id}` | `admin.deal.clearance-sale.vendor-delete` | `App\Http\Controllers\Admin\Promotion\ClearanceSaleVendorOfferController@deleteVendorOffer` |
| 50 | `GET` | `/admin/deal/clearance-sale/priority-setup` | `admin.deal.clearance-sale.priority-setup` | `App\Http\Controllers\Admin\Promotion\ClearanceSalePrioritySetupController@index` |
| 51 | `POST` | `/admin/deal/clearance-sale/priority-setup-config` | `admin.deal.clearance-sale.priority-setup-config` | `App\Http\Controllers\Admin\Promotion\ClearanceSalePrioritySetupController@updateConfig` |
| 52 | `GET` | `/admin/notification/index` | `admin.notification.index` | `App\Http\Controllers\Admin\Notification\NotificationController@index` |
| 53 | `POST` | `/admin/notification/index` | `admin.notification.` | `App\Http\Controllers\Admin\Notification\NotificationController@add` |
| 54 | `GET` | `/admin/notification/update/{id}` | `admin.notification.update` | `App\Http\Controllers\Admin\Notification\NotificationController@getUpdateView` |
| 55 | `POST` | `/admin/notification/update/{id}` | `admin.notification.` | `App\Http\Controllers\Admin\Notification\NotificationController@update` |
| 56 | `POST` | `/admin/notification/delete` | `admin.notification.delete` | `App\Http\Controllers\Admin\Notification\NotificationController@delete` |
| 57 | `POST` | `/admin/notification/update-status` | `admin.notification.update-status` | `App\Http\Controllers\Admin\Notification\NotificationController@updateStatus` |
| 58 | `POST` | `/admin/notification/resend-notification` | `admin.notification.resend-notification` | `App\Http\Controllers\Admin\Notification\NotificationController@resendNotification` |
| 59 | `GET` | `/admin/blog/title-auto-fill` | `admin.blog.title-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\Blog\AIBlogController@titleAutoFill` |
| 60 | `GET` | `/admin/blog/description-auto-fill` | `admin.blog.description-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\Blog\AIBlogController@descriptionAutoFill` |
| 61 | `POST` | `/admin/blog/seo-section-auto-fill` | `admin.blog.seo-section-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\Blog\AIBlogController@seoSectionAutoFill` |
| 62 | `POST` | `/admin/blog/generate-title-suggestions` | `admin.blog.generate-title-suggestions` | `Modules\AI\app\Http\Controllers\Admin\Blog\AIBlogController@generateBlogTitleSuggestion` |
| 63 | `POST` | `/admin/blog/analyze-image-auto-fill` | `admin.blog.analyze-image-auto-fill` | `Modules\AI\app\Http\Controllers\Admin\Blog\AIBlogController@generateBlogTitleFromImages` |
| 64 | `POST` | `/admin/blog/category/add` | `admin.blog.category.add` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@add` |
| 65 | `POST` | `/admin/blog/category/category-info` | `admin.blog.category.info` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@getCategoryInfo` |
| 66 | `POST` | `/admin/blog/category/update` | `admin.blog.category.update` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@update` |
| 67 | `GET` | `/admin/blog/category/status` | `admin.blog.category.status-update` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@updateStatus` |
| 68 | `DELETE` | `/admin/blog/category/delete` | `admin.blog.category.delete` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@deleteCategory` |
| 69 | `POST` | `/admin/blog/category/search` | `admin.blog.category.search` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@search` |
| 70 | `GET` | `/admin/blog/category/get-list` | `admin.blog.category.get-list` | `Modules\Blog\app\Http\Controllers\Admin\BlogCategoryController@getList` |
| 71 | `GET` | `/admin/blog/view` | `admin.blog.view` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@index` |
| 72 | `POST` | `/admin/blog/intro` | `admin.blog.intro` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@updateIntro` |
| 73 | `GET` | `/admin/blog/add` | `admin.blog.add` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@getAddView` |
| 74 | `POST` | `/admin/blog/add` | `admin.blog.store` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@addBlog` |
| 75 | `GET` | `/admin/blog/edit` | `admin.blog.edit` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@getUpdateView` |
| 76 | `POST` | `/admin/blog/update` | `admin.blog.update` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@update` |
| 77 | `POST` | `/admin/blog/status-update` | `admin.blog.status-update` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@updateStatus` |
| 78 | `POST` | `/admin/blog/blog-status-update/{id}` | `admin.blog.blog-status-update` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@updateBlogStatus` |
| 79 | `GET` | `/admin/blog/draft-edit/{id}` | `admin.blog.draft-edit` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@draftEdit` |
| 80 | `POST` | `/admin/blog/delete` | `admin.blog.delete` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@delete` |
| 81 | `POST` | `/admin/blog/section-view` | `admin.blog.section-view` | `Modules\Blog\app\Http\Controllers\Admin\BlogController@sectionView` |
| 82 | `GET` | `/admin/blog/app-download-setup` | `admin.blog.app-download-setup` | `Modules\Blog\app\Http\Controllers\Admin\BlogDownloadAppController@appDownloadSetup` |
| 83 | `POST` | `/admin/blog/app-download-setup` | `admin.blog.` | `Modules\Blog\app\Http\Controllers\Admin\BlogDownloadAppController@updateDownloadAppButton` |
| 84 | `POST` | `/admin/blog/app-download-setup-status` | `admin.blog.app-download-setup-status` | `Modules\Blog\app\Http\Controllers\Admin\BlogDownloadAppController@updateStatus` |
| 85 | `POST` | `/admin/blog/delete-image` | `admin.blog.delete-image` | `Modules\Blog\app\Http\Controllers\Admin\BlogDownloadAppController@deleteImage` |
| 86 | `GET` | `/admin/blog/priority-setup` | `admin.blog.priority-setup.index` | `Modules\Blog\app\Http\Controllers\Admin\BlogPrioritySetupController@index` |
| 87 | `POST` | `/admin/blog/priority-setup` | `admin.blog.priority-setup.` | `Modules\Blog\app\Http\Controllers\Admin\BlogPrioritySetupController@update` |

---

## 3. Disallowed & Gated Endpoints Summary (1496 Endpoints Blocked)

Attempting to access any of the 1496 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

### Sample Gated Endpoints for this Role:

| # | Method | Gated URI | Guard Interceptor | Reason for Gating |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/_debugbar/open` | `auth / rbac` | Strictly isolated outside role boundary |
| 2 | `GET` | `/_debugbar/clockwork/{id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 3 | `GET` | `/_debugbar/assets/stylesheets` | `auth / rbac` | Strictly isolated outside role boundary |
| 4 | `GET` | `/_debugbar/assets/javascript` | `auth / rbac` | Strictly isolated outside role boundary |
| 5 | `DELETE` | `/_debugbar/cache/{key}/{tags?}` | `auth / rbac` | Strictly isolated outside role boundary |
| 6 | `POST` | `/_debugbar/queries/explain` | `auth / rbac` | Strictly isolated outside role boundary |
| 7 | `POST` | `/oauth/token` | `auth / rbac` | Strictly isolated outside role boundary |
| 8 | `GET` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 9 | `POST` | `/oauth/token/refresh` | `auth / rbac` | Strictly isolated outside role boundary |
| 10 | `POST` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 11 | `DELETE` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 12 | `GET` | `/oauth/tokens` | `auth / rbac` | Strictly isolated outside role boundary |
| 13 | `DELETE` | `/oauth/tokens/{token_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 14 | `GET` | `/oauth/clients` | `auth / rbac` | Strictly isolated outside role boundary |
| 15 | `POST` | `/oauth/clients` | `auth / rbac` | Strictly isolated outside role boundary |
| 16 | `PUT` | `/oauth/clients/{client_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 17 | `DELETE` | `/oauth/clients/{client_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 18 | `GET` | `/oauth/scopes` | `auth / rbac` | Strictly isolated outside role boundary |
| 19 | `GET` | `/oauth/personal-access-tokens` | `auth / rbac` | Strictly isolated outside role boundary |
| 20 | `POST` | `/oauth/personal-access-tokens` | `auth / rbac` | Strictly isolated outside role boundary |
| 21 | `DELETE` | `/oauth/personal-access-tokens/{token_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 22 | `GET` | `/sanctum/csrf-cookie` | `auth / rbac` | Strictly isolated outside role boundary |
| 23 | `GET` | `/_ignition/health-check` | `auth / rbac` | Strictly isolated outside role boundary |
| 24 | `POST` | `/_ignition/execute-solution` | `auth / rbac` | Strictly isolated outside role boundary |
| 25 | `POST` | `/_ignition/update-config` | `auth / rbac` | Strictly isolated outside role boundary |


