# Role Security & Endpoint Access Policy: Admin Staff: Product Moderator

> **Role Scope:** `Admin Staff: Product Moderator`  
> **Total Allowed Endpoints:** `80`  
> **Total Disallowed / Blocked Endpoints:** `1503`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

Super Admin employee assigned to vetting, approving, and moderating merchant products and brand directories.

### Core Authorized Capabilities:
- ✅ **Merchant Product Submissions Review & Approval/Rejection**
- ✅ **Category and Brand Hierarchy Management**
- ✅ **Promotional Banners and Campaign Tagging**

### Strict Architectural Restrictions:
- ⛔ **Strictly blocked from Order modifications, financial ledgers, and delivery dispatches**
- ⛔ **Strictly blocked from Super Admin settings and employee roles**

---

## 2. Authorized Endpoints Access Matrix (80 Endpoints)

| # | Method | URI | Route Name | Action / Controller |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/admin/dashboard` | `admin.dashboard.index` | `App\Http\Controllers\Admin\DashboardController@index` |
| 2 | `POST` | `/admin/dashboard/order-status` | `admin.dashboard.order-status` | `App\Http\Controllers\Admin\DashboardController@getOrderStatus` |
| 3 | `GET` | `/admin/dashboard/earning-statistics` | `admin.dashboard.earning-statistics` | `App\Http\Controllers\Admin\DashboardController@getEarningStatistics` |
| 4 | `GET` | `/admin/dashboard/order-statistics` | `admin.dashboard.order-statistics` | `App\Http\Controllers\Admin\DashboardController@getOrderStatistics` |
| 5 | `GET` | `/admin/dashboard/real-time-activities` | `admin.dashboard.real-time-activities` | `App\Http\Controllers\Admin\DashboardController@getRealTimeActivities` |
| 6 | `GET` | `/admin/products/list/{type}` | `admin.products.list` | `App\Http\Controllers\Admin\Product\ProductController@index` |
| 7 | `GET` | `/admin/products/add` | `admin.products.add` | `App\Http\Controllers\Admin\Product\ProductController@getAddView` |
| 8 | `POST` | `/admin/products/add` | `admin.products.store` | `App\Http\Controllers\Admin\Product\ProductController@add` |
| 9 | `GET` | `/admin/products/view/{addedBy}/{id}` | `admin.products.view` | `App\Http\Controllers\Admin\Product\ProductController@getView` |
| 10 | `POST` | `/admin/products/sku-combination` | `admin.products.sku-combination` | `App\Http\Controllers\Admin\Product\ProductController@getSkuCombinationView` |
| 11 | `POST` | `/admin/products/digital-variation-combination` | `admin.products.digital-variation-combination` | `App\Http\Controllers\Admin\Product\ProductController@getDigitalVariationCombinationView` |
| 12 | `POST` | `/admin/products/digital-variation-file-delete` | `admin.products.digital-variation-file-delete` | `App\Http\Controllers\Admin\Product\ProductController@deleteDigitalVariationFile` |
| 13 | `POST` | `/admin/products/featured-status` | `admin.products.featured-status` | `App\Http\Controllers\Admin\Product\ProductController@updateFeaturedStatus` |
| 14 | `GET` | `/admin/products/get-categories` | `admin.products.get-categories` | `App\Http\Controllers\Admin\Product\ProductController@getCategories` |
| 15 | `POST` | `/admin/products/status-update` | `admin.products.status-update` | `App\Http\Controllers\Admin\Product\ProductController@updateStatus` |
| 16 | `GET` | `/admin/products/barcode/{id}` | `admin.products.barcode` | `App\Http\Controllers\Admin\Product\ProductController@getBarcodeView` |
| 17 | `GET` | `/admin/products/export-excel/{type}` | `admin.products.export-excel` | `App\Http\Controllers\Admin\Product\ProductController@exportList` |
| 18 | `GET` | `/admin/products/stock-limit-list/{type}` | `admin.products.stock-limit-list` | `App\Http\Controllers\Admin\Product\ProductController@getStockLimitListView` |
| 19 | `DELETE` | `/admin/products/delete/{id}` | `admin.products.delete` | `App\Http\Controllers\Admin\Product\ProductController@delete` |
| 20 | `GET` | `/admin/products/update/{id}` | `admin.products.update` | `App\Http\Controllers\Admin\Product\ProductController@getUpdateView` |
| 21 | `POST` | `/admin/products/update/{id}` | `admin.products.` | `App\Http\Controllers\Admin\Product\ProductController@update` |
| 22 | `POST` | `/admin/products/update-product-images/{id}` | `admin.products.update-product-images` | `App\Http\Controllers\Admin\Product\ProductController@updateProductImages` |
| 23 | `GET` | `/admin/products/delete-image` | `admin.products.delete-image` | `App\Http\Controllers\Admin\Product\ProductController@deleteImage` |
| 24 | `GET` | `/admin/products/get-variations` | `admin.products.get-variations` | `App\Http\Controllers\Admin\Product\ProductController@getVariations` |
| 25 | `POST` | `/admin/products/update-quantity` | `admin.products.update-quantity` | `App\Http\Controllers\Admin\Product\ProductController@updateQuantity` |
| 26 | `GET` | `/admin/products/bulk-import` | `admin.products.bulk-import` | `App\Http\Controllers\Admin\Product\ProductController@getBulkImportView` |
| 27 | `POST` | `/admin/products/bulk-import` | `admin.products.` | `App\Http\Controllers\Admin\Product\ProductController@importBulkProduct` |
| 28 | `GET` | `/admin/products/updated-product-list` | `admin.products.updated-product-list` | `App\Http\Controllers\Admin\Product\ProductController@updatedProductList` |
| 29 | `POST` | `/admin/products/updated-shipping` | `admin.products.updated-shipping` | `App\Http\Controllers\Admin\Product\ProductController@updatedShipping` |
| 30 | `POST` | `/admin/products/deny` | `admin.products.deny` | `App\Http\Controllers\Admin\Product\ProductController@deny` |
| 31 | `POST` | `/admin/products/approve-status` | `admin.products.approve-status` | `App\Http\Controllers\Admin\Product\ProductController@approveStatus` |
| 32 | `GET` | `/admin/products/search` | `admin.products.search-product` | `App\Http\Controllers\Admin\Product\ProductController@getSearchedProductsView` |
| 33 | `GET` | `/admin/products/search-all-product` | `admin.products.search-all-type-product` | `App\Http\Controllers\Admin\Product\ProductController@getSearchedAllProductsView` |
| 34 | `GET` | `/admin/products/product-gallery` | `admin.products.product-gallery` | `App\Http\Controllers\Admin\Product\ProductController@getProductGalleryView` |
| 35 | `GET` | `/admin/products/stock-limit-status/{type}` | `admin.products.stock-limit-status` | `App\Http\Controllers\Admin\Product\ProductController@getStockLimitStatus` |
| 36 | `POST` | `/admin/products/delete-preview-file` | `admin.products.delete-preview-file` | `App\Http\Controllers\Admin\Product\ProductController@deletePreviewFile` |
| 37 | `GET` | `/admin/products/request-restock-list` | `admin.products.request-restock-list` | `App\Http\Controllers\Admin\Product\ProductController@getRequestRestockListView` |
| 38 | `GET` | `/admin/products/export-restock` | `admin.products.restock-export` | `App\Http\Controllers\Admin\Product\ProductController@exportRestockList` |
| 39 | `DELETE` | `/admin/products/restock-delete/{id}` | `admin.products.restock-delete` | `App\Http\Controllers\Admin\Product\ProductController@deleteRestock` |
| 40 | `GET` | `/admin/products/product-feeds` | `admin.products.product-feeds` | `App\Http\Controllers\ProductFeedExportController@index` |
| 41 | `POST` | `/admin/products/product-feeds/regenerate-token` | `admin.products.product-feeds.regenerate-token` | `App\Http\Controllers\ProductFeedExportController@regenerateToken` |
| 42 | `GET` | `/admin/products/multiple-product-details` | `admin.products.multiple-product-details` | `App\Http\Controllers\Admin\Product\ProductController@getMultipleProductDetailsView` |
| 43 | `GET` | `/admin/brand/list` | `admin.brand.list` | `App\Http\Controllers\Admin\Product\BrandController@index` |
| 44 | `GET` | `/admin/brand/add-new` | `admin.brand.add-new` | `App\Http\Controllers\Admin\Product\BrandController@getAddView` |
| 45 | `POST` | `/admin/brand/add-new` | `admin.brand.` | `App\Http\Controllers\Admin\Product\BrandController@add` |
| 46 | `GET` | `/admin/brand/update/{id}` | `admin.brand.update` | `App\Http\Controllers\Admin\Product\BrandController@getUpdateView` |
| 47 | `POST` | `/admin/brand/update/{id}` | `admin.brand.` | `App\Http\Controllers\Admin\Product\BrandController@update` |
| 48 | `POST` | `/admin/brand/delete` | `admin.brand.delete` | `App\Http\Controllers\Admin\Product\BrandController@delete` |
| 49 | `GET` | `/admin/brand/export` | `admin.brand.export` | `App\Http\Controllers\Admin\Product\BrandController@exportList` |
| 50 | `POST` | `/admin/brand/status-update` | `admin.brand.status-update` | `App\Http\Controllers\Admin\Product\BrandController@updateStatus` |
| 51 | `POST` | `/admin/brand/load-more-brands` | `admin.brand.load-more-brands` | `App\Http\Controllers\Admin\Product\BrandController@loadMoreBrands` |
| 52 | `GET` | `/admin/category/view` | `admin.category.view` | `App\Http\Controllers\Admin\Product\CategoryController@index` |
| 53 | `POST` | `/admin/category/add-new` | `admin.category.store` | `App\Http\Controllers\Admin\Product\CategoryController@add` |
| 54 | `GET` | `/admin/category/update` | `admin.category.update` | `App\Http\Controllers\Admin\Product\CategoryController@getUpdateView` |
| 55 | `POST` | `/admin/category/update` | `admin.category.` | `App\Http\Controllers\Admin\Product\CategoryController@update` |
| 56 | `POST` | `/admin/category/delete` | `admin.category.delete` | `App\Http\Controllers\Admin\Product\CategoryController@delete` |
| 57 | `POST` | `/admin/category/status` | `admin.category.status` | `App\Http\Controllers\Admin\Product\CategoryController@updateStatus` |
| 58 | `GET` | `/admin/category/export` | `admin.category.export` | `App\Http\Controllers\Admin\Product\CategoryController@getExportList` |
| 59 | `GET` | `/admin/category-specifications` | `admin.category-specifications.index` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@index` |
| 60 | `POST` | `/admin/category-specifications/store` | `admin.category-specifications.store` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@store` |
| 61 | `POST` | `/admin/category-specifications/update/{id}` | `admin.category-specifications.update` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@update` |
| 62 | `DELETE` | `/admin/category-specifications/delete/{id}` | `admin.category-specifications.delete` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@delete` |
| 63 | `POST` | `/admin/category-specifications/status` | `admin.category-specifications.status` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@status` |
| 64 | `GET` | `/admin/category-specifications/get-by-category/{category_id}` | `admin.category-specifications.get-by-category` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@getByCategoryAjax` |
| 65 | `POST` | `/admin/category-specifications/ai-suggest-specs` | `admin.category-specifications.ai-suggest-specs` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@aiSuggestSpecs` |
| 66 | `GET` | `/admin/banner/list` | `admin.banner.list` | `App\Http\Controllers\Admin\Promotion\BannerController@index` |
| 67 | `POST` | `/admin/banner/add` | `admin.banner.store` | `App\Http\Controllers\Admin\Promotion\BannerController@add` |
| 68 | `POST` | `/admin/banner/delete` | `admin.banner.delete` | `App\Http\Controllers\Admin\Promotion\BannerController@delete` |
| 69 | `POST` | `/admin/banner/status` | `admin.banner.status` | `App\Http\Controllers\Admin\Promotion\BannerController@updateStatus` |
| 70 | `GET` | `/admin/banner/update/{id}` | `admin.banner.update` | `App\Http\Controllers\Admin\Promotion\BannerController@getUpdateView` |
| 71 | `POST` | `/admin/banner/update/{id}` | `admin.banner.` | `App\Http\Controllers\Admin\Promotion\BannerController@update` |
| 72 | `GET` | `/admin/coupon/add` | `admin.coupon.add` | `App\Http\Controllers\Admin\Promotion\CouponController@getAddListView` |
| 73 | `POST` | `/admin/coupon/add` | `admin.coupon.` | `App\Http\Controllers\Admin\Promotion\CouponController@add` |
| 74 | `GET` | `/admin/coupon/export` | `admin.coupon.export` | `App\Http\Controllers\Admin\Promotion\CouponController@exportList` |
| 75 | `GET` | `/admin/coupon/quick-view-details` | `admin.coupon.quick-view-details` | `App\Http\Controllers\Admin\Promotion\CouponController@quickView` |
| 76 | `GET` | `/admin/coupon/update/{id}` | `admin.coupon.update` | `App\Http\Controllers\Admin\Promotion\CouponController@getUpdateView` |
| 77 | `POST` | `/admin/coupon/update/{id}` | `admin.coupon.` | `App\Http\Controllers\Admin\Promotion\CouponController@update` |
| 78 | `GET` | `/admin/coupon/status/{id}/{status}` | `admin.coupon.status` | `App\Http\Controllers\Admin\Promotion\CouponController@updateStatus` |
| 79 | `POST` | `/admin/coupon/ajax-get-vendor` | `admin.coupon.ajax-get-vendor` | `App\Http\Controllers\Admin\Promotion\CouponController@getVendorList` |
| 80 | `DELETE` | `/admin/coupon/delete/{id}` | `admin.coupon.delete` | `App\Http\Controllers\Admin\Promotion\CouponController@delete` |

---

## 3. Disallowed & Gated Endpoints Summary (1503 Endpoints Blocked)

Attempting to access any of the 1503 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

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


