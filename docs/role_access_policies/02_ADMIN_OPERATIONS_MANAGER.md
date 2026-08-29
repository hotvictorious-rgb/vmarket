# Role Security & Endpoint Access Policy: Admin Staff: Operations & Store Manager

> **Role Scope:** `Admin Staff: Operations & Store Manager`  
> **Total Allowed Endpoints:** `131`  
> **Total Disallowed / Blocked Endpoints:** `1452`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

Super Admin employee assigned to daily operations, catalog management, POS oversight, and logistics dispatch.

### Core Authorized Capabilities:
- ✅ **Order Status Management & Shipping Route Allocations**
- ✅ **Product Review, Categorization & Catalog Curation**
- ✅ **POS Terminal and Store Operations Monitoring**
- ✅ **Logistics Fleet Dispatch & Interstate Delivery Hubs Management**

### Strict Architectural Restrictions:
- ⛔ **Strictly blocked from SaaS Master Control, Admin Employee Setup, and Payment Gateway API Keys**
- ⛔ **Strictly blocked from Platform Financial Withdrawals and Bank Configuration**

---

## 2. Authorized Endpoints Access Matrix (131 Endpoints)

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
| 43 | `GET` | `/admin/orders/list/{status}` | `admin.orders.list` | `App\Http\Controllers\Admin\Order\OrderController@index` |
| 44 | `GET` | `/admin/orders/export-excel/{status}` | `admin.orders.export-excel` | `App\Http\Controllers\Admin\Order\OrderController@exportList` |
| 45 | `GET` | `/admin/orders/generate-invoice/{id}` | `admin.orders.generate-invoice` | `App\Http\Controllers\Admin\Order\OrderController@generateInvoice` |
| 46 | `GET` | `/admin/orders/details/{id}` | `admin.orders.details` | `App\Http\Controllers\Admin\Order\OrderController@getView` |
| 47 | `POST` | `/admin/orders/address-update` | `admin.orders.address-update` | `App\Http\Controllers\Admin\Order\OrderController@updateAddress` |
| 48 | `POST` | `/admin/orders/update-deliver-info` | `admin.orders.update-deliver-info` | `App\Http\Controllers\Admin\Order\OrderController@updateDeliverInfo` |
| 49 | `GET` | `/admin/orders/add-delivery-man/{order_id}/{d_man_id}` | `admin.orders.add-delivery-man` | `App\Http\Controllers\Admin\Order\OrderController@addDeliveryMan` |
| 50 | `POST` | `/admin/orders/amount-date-update` | `admin.orders.amount-date-update` | `App\Http\Controllers\Admin\Order\OrderController@updateAmountDate` |
| 51 | `GET` | `/admin/orders/customers` | `admin.orders.customers` | `App\Http\Controllers\Admin\Order\OrderController@getCustomers` |
| 52 | `POST` | `/admin/orders/payment-status` | `admin.orders.payment-status` | `App\Http\Controllers\Admin\Order\OrderController@updatePaymentStatus` |
| 53 | `GET` | `/admin/orders/inhouse-order-filter` | `admin.orders.inhouse-order-filter` | `App\Http\Controllers\Admin\Order\OrderController@filterInHouseOrder` |
| 54 | `POST` | `/admin/orders/digital-file-upload-after-sell` | `admin.orders.digital-file-upload-after-sell` | `App\Http\Controllers\Admin\Order\OrderController@uploadDigitalFileAfterSell` |
| 55 | `POST` | `/admin/orders/status` | `admin.orders.status` | `App\Http\Controllers\Admin\Order\OrderController@updateStatus` |
| 56 | `POST` | `/admin/orders/customer-return-amount` | `admin.orders.customer-return-amount` | `App\Http\Controllers\Admin\Order\OrderController@orderReturnAmountToCustomer` |
| 57 | `POST` | `/admin/orders/customer-due-amount` | `admin.orders.customer-due-amount` | `App\Http\Controllers\Admin\Order\OrderController@orderDueAmountSwitchToCOD` |
| 58 | `POST` | `/admin/orders/customer-due-amount-mark-as-paid` | `admin.orders.customer-due-amount-mark-as-paid` | `App\Http\Controllers\Admin\Order\OrderController@orderDueAmountMarkAsPaid` |
| 59 | `GET` | `/admin/orders/search-for-edit-order-product` | `admin.orders.search-for-edit-order-product` | `App\Http\Controllers\Admin\Order\OrderEditController@getSearchEditOrderProductsView` |
| 60 | `POST` | `/admin/orders/edit-order-product-modal-view` | `admin.orders.edit-order-product-modal-view` | `App\Http\Controllers\Admin\Order\OrderEditController@getEditOrderProductModalView` |
| 61 | `POST` | `/admin/orders/edit-order-product-add` | `admin.orders.edit-order-product-add` | `App\Http\Controllers\Admin\Order\OrderEditController@addEditOrderProduct` |
| 62 | `POST` | `/admin/orders/edit-order-product-variant-price` | `admin.orders.edit-order-product-variant-price` | `App\Http\Controllers\Admin\Order\OrderEditController@checkProductVariantPrice` |
| 63 | `POST` | `/admin/orders/edit-order-product-list-update` | `admin.orders.edit-order-product-list-update` | `App\Http\Controllers\Admin\Order\OrderEditController@updateEditOrderProductList` |
| 64 | `POST` | `/admin/orders/edit-order-product-remove` | `admin.orders.edit-order-product-remove` | `App\Http\Controllers\Admin\Order\OrderEditController@removeEditOrderProduct` |
| 65 | `POST` | `/admin/orders/edit-order-generate` | `admin.orders.edit-order-generate` | `App\Http\Controllers\Admin\Order\OrderEditController@generateEditOrderByProductList` |
| 66 | `GET` | `/admin/brand/list` | `admin.brand.list` | `App\Http\Controllers\Admin\Product\BrandController@index` |
| 67 | `GET` | `/admin/brand/add-new` | `admin.brand.add-new` | `App\Http\Controllers\Admin\Product\BrandController@getAddView` |
| 68 | `POST` | `/admin/brand/add-new` | `admin.brand.` | `App\Http\Controllers\Admin\Product\BrandController@add` |
| 69 | `GET` | `/admin/brand/update/{id}` | `admin.brand.update` | `App\Http\Controllers\Admin\Product\BrandController@getUpdateView` |
| 70 | `POST` | `/admin/brand/update/{id}` | `admin.brand.` | `App\Http\Controllers\Admin\Product\BrandController@update` |
| 71 | `POST` | `/admin/brand/delete` | `admin.brand.delete` | `App\Http\Controllers\Admin\Product\BrandController@delete` |
| 72 | `GET` | `/admin/brand/export` | `admin.brand.export` | `App\Http\Controllers\Admin\Product\BrandController@exportList` |
| 73 | `POST` | `/admin/brand/status-update` | `admin.brand.status-update` | `App\Http\Controllers\Admin\Product\BrandController@updateStatus` |
| 74 | `POST` | `/admin/brand/load-more-brands` | `admin.brand.load-more-brands` | `App\Http\Controllers\Admin\Product\BrandController@loadMoreBrands` |
| 75 | `GET` | `/admin/category/view` | `admin.category.view` | `App\Http\Controllers\Admin\Product\CategoryController@index` |
| 76 | `POST` | `/admin/category/add-new` | `admin.category.store` | `App\Http\Controllers\Admin\Product\CategoryController@add` |
| 77 | `GET` | `/admin/category/update` | `admin.category.update` | `App\Http\Controllers\Admin\Product\CategoryController@getUpdateView` |
| 78 | `POST` | `/admin/category/update` | `admin.category.` | `App\Http\Controllers\Admin\Product\CategoryController@update` |
| 79 | `POST` | `/admin/category/delete` | `admin.category.delete` | `App\Http\Controllers\Admin\Product\CategoryController@delete` |
| 80 | `POST` | `/admin/category/status` | `admin.category.status` | `App\Http\Controllers\Admin\Product\CategoryController@updateStatus` |
| 81 | `GET` | `/admin/category/export` | `admin.category.export` | `App\Http\Controllers\Admin\Product\CategoryController@getExportList` |
| 82 | `GET` | `/admin/category-specifications` | `admin.category-specifications.index` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@index` |
| 83 | `POST` | `/admin/category-specifications/store` | `admin.category-specifications.store` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@store` |
| 84 | `POST` | `/admin/category-specifications/update/{id}` | `admin.category-specifications.update` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@update` |
| 85 | `DELETE` | `/admin/category-specifications/delete/{id}` | `admin.category-specifications.delete` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@delete` |
| 86 | `POST` | `/admin/category-specifications/status` | `admin.category-specifications.status` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@status` |
| 87 | `GET` | `/admin/category-specifications/get-by-category/{category_id}` | `admin.category-specifications.get-by-category` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@getByCategoryAjax` |
| 88 | `POST` | `/admin/category-specifications/ai-suggest-specs` | `admin.category-specifications.ai-suggest-specs` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@aiSuggestSpecs` |
| 89 | `GET` | `/admin/delivery-man/list` | `admin.delivery-man.list` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@index` |
| 90 | `GET` | `/admin/delivery-man/add` | `admin.delivery-man.add` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@getAddView` |
| 91 | `POST` | `/admin/delivery-man/add` | `admin.delivery-man.` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@add` |
| 92 | `POST` | `/admin/delivery-man/status-update` | `admin.delivery-man.status-update` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@updateStatus` |
| 93 | `GET` | `/admin/delivery-man/export` | `admin.delivery-man.export` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@exportList` |
| 94 | `GET` | `/admin/delivery-man/update/{id}` | `admin.delivery-man.edit` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@getUpdateView` |
| 95 | `POST` | `/admin/delivery-man/update/{id}` | `admin.delivery-man.update` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@update` |
| 96 | `DELETE` | `/admin/delivery-man/delete/{id}` | `admin.delivery-man.delete` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@delete` |
| 97 | `GET` | `/admin/delivery-man/earning-statement-overview/{id}` | `admin.delivery-man.earning-statement-overview` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@getEarningOverview` |
| 98 | `GET` | `/admin/delivery-man/order-wise-earning/{id}` | `admin.delivery-man.order-wise-earning` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@getOrderWiseEarningView` |
| 99 | `GET` | `/admin/delivery-man/order-list-by-filer/{id}` | `admin.delivery-man.order-wise-earning-list-by-filter` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@getOrderWiseEarningListByFilter` |
| 100 | `GET` | `/admin/delivery-man/order-history-log/{id}` | `admin.delivery-man.order-history-log` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@getOrderHistoryList` |
| 101 | `GET` | `/admin/delivery-man/order-history-log-export/{id}` | `admin.delivery-man.order-history-log-export` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@getOrderHistoryListExport` |
| 102 | `GET` | `/admin/delivery-man/rating/{id}` | `admin.delivery-man.rating` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@getRatingView` |
| 103 | `GET` | `/admin/delivery-man/ajax-order-status-history/{order}` | `admin.delivery-man.ajax-order-status-history` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManController@getOrderStatusHistory` |
| 104 | `GET` | `/admin/delivery-man/collect-cash/{id}` | `admin.delivery-man.collect-cash` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManCashCollectController@index` |
| 105 | `POST` | `/admin/delivery-man/cash-receive/{id}` | `admin.delivery-man.cash-receive` | `App\Http\Controllers\Admin\Deliveryman\DeliveryManCashCollectController@getCashReceive` |
| 106 | `GET` | `/admin/delivery-man/withdraw-list` | `admin.delivery-man.withdraw-list` | `App\Http\Controllers\Admin\Deliveryman\DeliverymanWithdrawController@index` |
| 107 | `POST` | `/admin/delivery-man/withdraw-list` | `admin.delivery-man.` | `App\Http\Controllers\Admin\Deliveryman\DeliverymanWithdrawController@getFiltered` |
| 108 | `GET` | `/admin/delivery-man/withdraw-list-export` | `admin.delivery-man.withdraw-list-export` | `App\Http\Controllers\Admin\Deliveryman\DeliverymanWithdrawController@exportList` |
| 109 | `GET` | `/admin/delivery-man/withdraw-view/{withdraw_id}` | `admin.delivery-man.withdraw-view` | `App\Http\Controllers\Admin\Deliveryman\DeliverymanWithdrawController@getView` |
| 110 | `POST` | `/admin/delivery-man/withdraw-update-status/{id}` | `admin.delivery-man.withdraw-update-status` | `App\Http\Controllers\Admin\Deliveryman\DeliverymanWithdrawController@updateStatus` |
| 111 | `GET` | `/admin/delivery-man/emergency-contact` | `admin.delivery-man.emergency-contact.index` | `App\Http\Controllers\Admin\Deliveryman\EmergencyContactController@index` |
| 112 | `POST` | `/admin/delivery-man/emergency-contact/add` | `admin.delivery-man.emergency-contact.add` | `App\Http\Controllers\Admin\Deliveryman\EmergencyContactController@add` |
| 113 | `GET` | `/admin/delivery-man/emergency-contact/update/{id}` | `admin.delivery-man.emergency-contact.update` | `App\Http\Controllers\Admin\Deliveryman\EmergencyContactController@getUpdateView` |
| 114 | `POST` | `/admin/delivery-man/emergency-contact/update/{id}` | `admin.delivery-man.emergency-contact.` | `App\Http\Controllers\Admin\Deliveryman\EmergencyContactController@update` |
| 115 | `POST` | `/admin/delivery-man/emergency-contact/ajax-status-change` | `admin.delivery-man.emergency-contact.ajax-status-change` | `App\Http\Controllers\Admin\Deliveryman\EmergencyContactController@updateStatus` |
| 116 | `DELETE` | `/admin/delivery-man/emergency-contact/destroy` | `admin.delivery-man.emergency-contact.destroy` | `App\Http\Controllers\Admin\Deliveryman\EmergencyContactController@delete` |
| 117 | `GET` | `/admin/delivery-hubs` | `admin.delivery-hubs.index` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@index` |
| 118 | `POST` | `/admin/delivery-hubs/store-state` | `admin.delivery-hubs.store-state` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@storeState` |
| 119 | `POST` | `/admin/delivery-hubs/update-state/{id}` | `admin.delivery-hubs.update-state` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@updateState` |
| 120 | `DELETE` | `/admin/delivery-hubs/delete-state/{id}` | `admin.delivery-hubs.delete-state` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@deleteState` |
| 121 | `POST` | `/admin/delivery-hubs/status-state` | `admin.delivery-hubs.status-state` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@statusState` |
| 122 | `POST` | `/admin/delivery-hubs/store-city` | `admin.delivery-hubs.store-city` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@storeCity` |
| 123 | `POST` | `/admin/delivery-hubs/update-city/{id}` | `admin.delivery-hubs.update-city` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@updateCity` |
| 124 | `DELETE` | `/admin/delivery-hubs/delete-city/{id}` | `admin.delivery-hubs.delete-city` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@deleteCity` |
| 125 | `POST` | `/admin/delivery-hubs/status-city` | `admin.delivery-hubs.status-city` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@statusCity` |
| 126 | `POST` | `/admin/delivery-hubs/store-hub` | `admin.delivery-hubs.store-hub` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@storeHub` |
| 127 | `POST` | `/admin/delivery-hubs/update-hub/{id}` | `admin.delivery-hubs.update-hub` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@updateHub` |
| 128 | `DELETE` | `/admin/delivery-hubs/delete-hub/{id}` | `admin.delivery-hubs.delete-hub` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@deleteHub` |
| 129 | `POST` | `/admin/delivery-hubs/status-hub` | `admin.delivery-hubs.status-hub` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@statusHub` |
| 130 | `GET` | `/admin/delivery-hubs/get-cities-ajax/{state_id}` | `admin.delivery-hubs.get-cities-ajax` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@getCitiesAjax` |
| 131 | `GET` | `/admin/delivery-hubs/get-hubs-ajax/{city_id}` | `admin.delivery-hubs.get-hubs-ajax` | `App\Http\Controllers\Admin\Delivery\DeliveryHubController@getHubsAjax` |

---

## 3. Disallowed & Gated Endpoints Summary (1452 Endpoints Blocked)

Attempting to access any of the 1452 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

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


