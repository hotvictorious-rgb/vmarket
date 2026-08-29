# Role Security & Endpoint Access Policy: Verified Merchant (Approved Store Owner)

> **Role Scope:** `Verified Merchant (Approved Store Owner)`  
> **Total Allowed Endpoints:** `421`  
> **Total Disallowed / Blocked Endpoints:** `1162`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

Fully verified store owner with active omnichannel selling: multi-branch In-Store POS, online marketplace store, debt ledger, and waybill transfers.

### Core Authorized Capabilities:
- ✅ **Full In-Store POS with Multi-Branch Support and Barcode Scanning**
- ✅ **Live Online Selling on Victorious MARKET with Real-Time Inventory Sync**
- ✅ **Store Staff Assignment from Predetermined Templates (Manager, Cashier, Inventory Clerk)**
- ✅ **Customer Debt Ledger Management and Part-Payment Tracking**
- ✅ **Warehouse Stock Intake, Inter-Branch Transfers, and Shortage Audits**
- ✅ **Merchant Wallet Balance Withdrawal Requests**

### Strict Architectural Restrictions:
- ⛔ **Strictly blocked from accessing any other merchant store (Zero Cross-Tenant Bleed)**
- ⛔ **Strictly blocked from Super Admin Command Center and SaaS Master Controls**
- ⛔ **Cannot create custom roles (predefined templates only)**

---

## 2. Authorized Endpoints Access Matrix (421 Endpoints)

| # | Method | URI | Route Name | Action / Controller |
|:---:|:---:|---|---|---|
| 1 | `POST` | `/api/v3/seller/auth/login` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\auth\LoginController@login` |
| 2 | `POST` | `/api/v3/seller/auth/forgot-password` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\auth\ForgotPasswordController@reset_password_request` |
| 3 | `POST` | `/api/v3/seller/auth/verify-otp` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\auth\ForgotPasswordController@otp_verification_submit` |
| 4 | `PUT` | `/api/v3/seller/auth/reset-password` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\auth\ForgotPasswordController@reset_password_submit` |
| 5 | `POST` | `/api/v3/seller/auth/firebase-auth-token-store` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\auth\ForgotPasswordController@firebaseAuthTokenStore` |
| 6 | `POST` | `/api/v3/seller/auth/firebase-auth-verify` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\auth\ForgotPasswordController@firebaseAuthVerify` |
| 7 | `POST` | `/api/v3/seller/auth/check-vendor-exist-info` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\auth\ForgotPasswordController@checkVendorExistInfo` |
| 8 | `POST` | `/api/v3/seller/registration` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\auth\RegisterController@store` |
| 9 | `PUT` | `/api/v3/seller/language-change` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@language_change` |
| 10 | `GET` | `/api/v3/seller/seller-info` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@getSellerInfo` |
| 11 | `GET` | `/api/v3/seller/get-earning-statitics` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@getEarningStatics` |
| 12 | `GET` | `/api/v3/seller/order-statistics` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@order_statistics` |
| 13 | `GET` | `/api/v3/seller/account-delete` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@account_delete` |
| 14 | `GET` | `/api/v3/seller/seller-delivery-man` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@seller_delivery_man` |
| 15 | `GET` | `/api/v3/seller/shop-product-reviews` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@shop_product_reviews` |
| 16 | `POST` | `/api/v3/seller/shop-product-reviews-reply` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@shopProductReviewReply` |
| 17 | `GET` | `/api/v3/seller/shop-product-reviews-status` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@shop_product_reviews_status` |
| 18 | `PUT` | `/api/v3/seller/seller-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@seller_info_update` |
| 19 | `GET` | `/api/v3/seller/paystack/banks` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@get_nigerian_banks` |
| 20 | `POST` | `/api/v3/seller/paystack/resolve-account` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@resolve_bank_account` |
| 21 | `POST` | `/api/v3/seller/bank-info/send-otp` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@send_bank_update_otp` |
| 22 | `GET` | `/api/v3/seller/kyc/status` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@get_kyc_status` |
| 23 | `POST` | `/api/v3/seller/kyc/submit` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@submit_kyc` |
| 24 | `GET` | `/api/v3/seller/monthly-earning` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@monthly_earning` |
| 25 | `GET` | `/api/v3/seller/monthly-commission-given` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@monthly_commission_given` |
| 26 | `PUT` | `/api/v3/seller/cm-firebase-token` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@update_cm_firebase_token` |
| 27 | `GET` | `/api/v3/seller/shop-info` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@shop_info` |
| 28 | `GET` | `/api/v3/seller/transactions` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@transaction` |
| 29 | `PUT` | `/api/v3/seller/shop-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@shop_info_update` |
| 30 | `POST` | `/api/v3/seller/update-setup-guide-app` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@updateSetupGuideApp` |
| 31 | `GET` | `/api/v3/seller/withdraw-method-list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@withdraw_method_list` |
| 32 | `POST` | `/api/v3/seller/balance-withdraw` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@withdraw_request` |
| 33 | `DELETE` | `/api/v3/seller/close-withdraw-request` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\SellerController@close_withdraw_request` |
| 34 | `PUT` | `/api/v3/seller/vacation-add` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ShopController@vacation_add` |
| 35 | `PUT` | `/api/v3/seller/temporary-close` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ShopController@temporary_close` |
| 36 | `GET` | `/api/v3/seller/brands` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\BrandController@getBrands` |
| 37 | `GET` | `/api/v3/seller/top-delivery-man` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@top_delivery_man` |
| 38 | `GET` | `/api/v3/seller/categories` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@get_categories` |
| 39 | `GET` | `/api/v3/seller/products/list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@getProductList` |
| 40 | `POST` | `/api/v3/seller/products/upload-images` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@upload_images` |
| 41 | `POST` | `/api/v3/seller/products/upload-digital-product` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@upload_digital_product` |
| 42 | `POST` | `/api/v3/seller/products/delete-digital-product` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@deleteDigitalProduct` |
| 43 | `POST` | `/api/v3/seller/products/add` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@add_new` |
| 44 | `GET` | `/api/v3/seller/products/details/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@details` |
| 45 | `GET` | `/api/v3/seller/products/stock-out-list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@stock_out_list` |
| 46 | `PUT` | `/api/v3/seller/products/status-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@status_update` |
| 47 | `GET` | `/api/v3/seller/products/edit/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@edit` |
| 48 | `PUT` | `/api/v3/seller/products/update/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@updateProduct` |
| 49 | `GET` | `/api/v3/seller/products/review-list/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@review_list` |
| 50 | `PUT` | `/api/v3/seller/products/quantity-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@updateProductQuantity` |
| 51 | `DELETE` | `/api/v3/seller/products/delete/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@delete` |
| 52 | `GET` | `/api/v3/seller/products/barcode/generate` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@barcode_generate` |
| 53 | `GET` | `/api/v3/seller/products/top-selling-product` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@top_selling_products` |
| 54 | `GET` | `/api/v3/seller/products/most-popular-product` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@most_popular_products` |
| 55 | `GET` | `/api/v3/seller/products/delete-image` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@deleteImage` |
| 56 | `GET` | `/api/v3/seller/products/get-product-images/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@getProductImages` |
| 57 | `GET` | `/api/v3/seller/products/stock-limit-status` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@getStockLimitStatus` |
| 58 | `GET` | `/api/v3/seller/products/delete-preview-file` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@deletePreviewFile` |
| 59 | `GET` | `/api/v3/seller/products/digital-author-list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@getDigitalProductsAuthorList` |
| 60 | `GET` | `/api/v3/seller/products/digital-publishing-house-list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@getDigitalPublishingHouseList` |
| 61 | `POST` | `/api/v3/seller/products/restock-request-list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@getRestockRequestList` |
| 62 | `GET` | `/api/v3/seller/products/restock-request-delete` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@deleteRestockRequest` |
| 63 | `POST` | `/api/v3/seller/products/restock-request-stock-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@updateRestockQuantity` |
| 64 | `GET` | `/api/v3/seller/products/restock-request-brands-list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@getRestockRequestBrands` |
| 65 | `POST` | `/api/v3/seller/products/update-price-and-reactivate` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@updatePriceAndReactivate` |
| 66 | `POST` | `/api/v3/seller/orders/list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\OrderController@list` |
| 67 | `GET` | `/api/v3/seller/orders/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\OrderController@details` |
| 68 | `PUT` | `/api/v3/seller/orders/order-detail-status/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\OrderController@order_detail_status` |
| 69 | `PUT` | `/api/v3/seller/orders/assign-delivery-man` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\OrderController@assign_delivery_man` |
| 70 | `PUT` | `/api/v3/seller/orders/order-wise-product-upload` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\OrderController@digital_file_upload_after_sell` |
| 71 | `PUT` | `/api/v3/seller/orders/delivery-charge-date-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\OrderController@amount_date_update` |
| 72 | `POST` | `/api/v3/seller/orders/assign-third-party-delivery` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\OrderController@assign_third_party_delivery` |
| 73 | `POST` | `/api/v3/seller/orders/update-payment-status` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\OrderController@update_payment_status` |
| 74 | `POST` | `/api/v3/seller/orders/address-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\OrderController@address_update` |
| 75 | `POST` | `/api/v3/seller/orders/order-detail-info-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\OrderController@updateOrderDetails` |
| 76 | `POST` | `/api/v3/seller/orders/edit-order-submit` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\OrderEditController@submitEditOrder` |
| 77 | `POST` | `/api/v3/seller/orders/edit-order-validation` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\OrderEditController@checkEditOrderValidation` |
| 78 | `POST` | `/api/v3/seller/orders/assign-order-in-cod` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\OrderEditController@assignOrderInCOD` |
| 79 | `GET` | `/api/v3/seller/clearance-sale/product-list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ClearanceSaleController@list` |
| 80 | `POST` | `/api/v3/seller/clearance-sale/product-add` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ClearanceSaleController@addClearanceProduct` |
| 81 | `POST` | `/api/v3/seller/clearance-sale/product-delete` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ClearanceSaleController@deleteClearanceProduct` |
| 82 | `POST` | `/api/v3/seller/clearance-sale/all-product-delete` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ClearanceSaleController@deleteAllClearanceProduct` |
| 83 | `POST` | `/api/v3/seller/clearance-sale/product-status-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ClearanceSaleController@updateClearanceProductStatus` |
| 84 | `POST` | `/api/v3/seller/clearance-sale/product-discount-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ClearanceSaleController@updateClearanceProductDiscount` |
| 85 | `POST` | `/api/v3/seller/clearance-sale/config-status-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ClearanceSaleController@updateClearanceConfigStatus` |
| 86 | `GET` | `/api/v3/seller/clearance-sale/config-data` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ClearanceSaleController@getConfigData` |
| 87 | `POST` | `/api/v3/seller/clearance-sale/config-data-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ClearanceSaleController@updateConfigData` |
| 88 | `GET` | `/api/v3/seller/refund/list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\RefundController@list` |
| 89 | `GET` | `/api/v3/seller/refund/single-item` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\RefundController@getSingleItem` |
| 90 | `GET` | `/api/v3/seller/refund/refund-details` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\RefundController@refund_details` |
| 91 | `POST` | `/api/v3/seller/refund/refund-status-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\RefundController@refund_status_update` |
| 92 | `GET` | `/api/v3/seller/coupon/list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\CouponController@list` |
| 93 | `POST` | `/api/v3/seller/coupon/store` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\CouponController@store` |
| 94 | `PUT` | `/api/v3/seller/coupon/update/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\CouponController@update` |
| 95 | `PUT` | `/api/v3/seller/coupon/status-update/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\CouponController@status_update` |
| 96 | `DELETE` | `/api/v3/seller/coupon/delete/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\CouponController@delete` |
| 97 | `POST` | `/api/v3/seller/coupon/check-coupon` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\CouponController@check_coupon` |
| 98 | `GET` | `/api/v3/seller/coupon/customers` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\CouponController@customers` |
| 99 | `GET` | `/api/v3/seller/shipping/get-shipping-method` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\shippingController@get_shipping_type` |
| 100 | `GET` | `/api/v3/seller/shipping/selected-shipping-method` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\shippingController@selected_shipping_type` |
| 101 | `GET` | `/api/v3/seller/shipping/all-category-cost` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\shippingController@all_category_cost` |
| 102 | `POST` | `/api/v3/seller/shipping/set-category-cost` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\shippingController@set_category_cost` |
| 103 | `GET` | `/api/v3/seller/shipping-method/list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ShippingMethodController@list` |
| 104 | `POST` | `/api/v3/seller/shipping-method/add` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ShippingMethodController@store` |
| 105 | `GET` | `/api/v3/seller/shipping-method/edit/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ShippingMethodController@edit` |
| 106 | `PUT` | `/api/v3/seller/shipping-method/status` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ShippingMethodController@status_update` |
| 107 | `PUT` | `/api/v3/seller/shipping-method/update/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ShippingMethodController@update` |
| 108 | `DELETE` | `/api/v3/seller/shipping-method/delete/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ShippingMethodController@delete` |
| 109 | `GET` | `/api/v3/seller/messages/list/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ChatController@list` |
| 110 | `GET` | `/api/v3/seller/messages/get-message/{type}/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ChatController@get_message` |
| 111 | `POST` | `/api/v3/seller/messages/send/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ChatController@send_message` |
| 112 | `POST` | `/api/v3/seller/messages/seen/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ChatController@seenMessage` |
| 113 | `GET` | `/api/v3/seller/messages/search/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ChatController@search` |
| 114 | `GET` | `/api/v3/seller/pos/get-categories` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\POSController@get_categories` |
| 115 | `GET` | `/api/v3/seller/pos/customers` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\POSController@customers` |
| 116 | `POST` | `/api/v3/seller/pos/customer-store` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\POSController@customer_store` |
| 117 | `GET` | `/api/v3/seller/pos/products` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\POSController@get_product_by_barcode` |
| 118 | `GET` | `/api/v3/seller/pos/product-list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\POSController@product_list` |
| 119 | `POST` | `/api/v3/seller/pos/place-order` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\POSController@place_order` |
| 120 | `GET` | `/api/v3/seller/pos/get-invoice` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\POSController@get_invoice` |
| 121 | `POST` | `/api/v3/seller/pos/get-tax-amount` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\POSCartController@getTaxAmountCart` |
| 122 | `GET` | `/api/v3/seller/delivery-man/list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliveryManController@list` |
| 123 | `POST` | `/api/v3/seller/delivery-man/store` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliveryManController@store` |
| 124 | `PUT` | `/api/v3/seller/delivery-man/update/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliveryManController@update` |
| 125 | `GET` | `/api/v3/seller/delivery-man/details/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliveryManController@details` |
| 126 | `POST` | `/api/v3/seller/delivery-man/status-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliveryManController@status` |
| 127 | `GET` | `/api/v3/seller/delivery-man/delete/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliveryManController@delete` |
| 128 | `GET` | `/api/v3/seller/delivery-man/reviews/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliveryManController@reviews` |
| 129 | `GET` | `/api/v3/seller/delivery-man/order-list/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliveryManController@order_list` |
| 130 | `GET` | `/api/v3/seller/delivery-man/order-status-history/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliveryManController@order_status_history` |
| 131 | `GET` | `/api/v3/seller/delivery-man/earning/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliveryManController@earning` |
| 132 | `POST` | `/api/v3/seller/delivery-man/cash-receive` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliveryManCashCollectController@cash_receive` |
| 133 | `GET` | `/api/v3/seller/delivery-man/collect-cash-list/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliveryManCashCollectController@list` |
| 134 | `GET` | `/api/v3/seller/delivery-man/withdraw/list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliverymanWithdrawController@list` |
| 135 | `GET` | `/api/v3/seller/delivery-man/withdraw/details/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliverymanWithdrawController@details` |
| 136 | `PUT` | `/api/v3/seller/delivery-man/withdraw/status-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\DeliverymanWithdrawController@status_update` |
| 137 | `GET` | `/api/v3/seller/delivery-man/emergency-contact/list` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\EmergencyContactController@list` |
| 138 | `POST` | `/api/v3/seller/delivery-man/emergency-contact/store` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\EmergencyContactController@store` |
| 139 | `PUT` | `/api/v3/seller/delivery-man/emergency-contact/update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\EmergencyContactController@update` |
| 140 | `PUT` | `/api/v3/seller/delivery-man/emergency-contact/status-update` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\EmergencyContactController@status_update` |
| 141 | `DELETE` | `/api/v3/seller/delivery-man/emergency-contact/delete` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\EmergencyContactController@destroy` |
| 142 | `GET` | `/api/v3/seller/notification` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ShopController@notification_index` |
| 143 | `GET` | `/api/v3/seller/notification/view` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ShopController@seller_notification_view` |
| 144 | `GET` | `/api/v3/seller/payment-information/list` | `payment-information.` | `App\Http\Controllers\RestAPI\v3\seller\VendorPaymentInfoController@index` |
| 145 | `GET` | `/api/v3/seller/payment-information/withdrawal-method-list` | `payment-information.` | `App\Http\Controllers\RestAPI\v3\seller\VendorPaymentInfoController@getWithdrawalMethods` |
| 146 | `POST` | `/api/v3/seller/payment-information/add` | `payment-information.` | `App\Http\Controllers\RestAPI\v3\seller\VendorPaymentInfoController@add` |
| 147 | `POST` | `/api/v3/seller/payment-information/update` | `payment-information.` | `App\Http\Controllers\RestAPI\v3\seller\VendorPaymentInfoController@update` |
| 148 | `POST` | `/api/v3/seller/payment-information/default` | `payment-information.` | `App\Http\Controllers\RestAPI\v3\seller\VendorPaymentInfoController@updateDefault` |
| 149 | `POST` | `/api/v3/seller/payment-information/status` | `payment-information.` | `App\Http\Controllers\RestAPI\v3\seller\VendorPaymentInfoController@updateStatus` |
| 150 | `GET` | `/api/v3/seller/payment-information/delete` | `payment-information.` | `App\Http\Controllers\RestAPI\v3\seller\VendorPaymentInfoController@delete` |
| 151 | `GET` | `/api/v3/seller/products/{seller_id}/all-products` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@getVendorAllProducts` |
| 152 | `GET` | `/api/v3/seller/products/{seller_id}/edit-order-all-products` | `unnamed` | `App\Http\Controllers\RestAPI\v3\seller\ProductController@editOrderVendorAllProducts` |
| 153 | `GET` | `/vendor/auth/login` | `vendor.auth.` | `App\Http\Controllers\Vendor\Auth\LoginController@getLoginView` |
| 154 | `POST` | `/vendor/auth/login` | `vendor.auth.login` | `App\Http\Controllers\Vendor\Auth\LoginController@login` |
| 155 | `GET` | `/vendor/auth/vendor.auth.login` | `vendor.auth.logout` | `App\Http\Controllers\Vendor\Auth\LoginController@logout` |
| 156 | `GET` | `/vendor/auth/forgot-password/index` | `vendor.auth.forgot-password.index` | `App\Http\Controllers\Vendor\Auth\ForgotPasswordController@index` |
| 157 | `POST` | `/vendor/auth/forgot-password/index` | `vendor.auth.forgot-password.` | `App\Http\Controllers\Vendor\Auth\ForgotPasswordController@getPasswordResetRequest` |
| 158 | `GET` | `/vendor/auth/forgot-password/otp-verification` | `vendor.auth.forgot-password.otp-verification` | `App\Http\Controllers\Vendor\Auth\ForgotPasswordController@getOTPVerificationView` |
| 159 | `POST` | `/vendor/auth/forgot-password/otp-verification` | `vendor.auth.forgot-password.` | `App\Http\Controllers\Vendor\Auth\ForgotPasswordController@submitOTPVerificationCode` |
| 160 | `GET` | `/vendor/auth/forgot-password/reset-password` | `vendor.auth.forgot-password.reset-password` | `App\Http\Controllers\Vendor\Auth\ForgotPasswordController@getPasswordResetView` |
| 161 | `POST` | `/vendor/auth/forgot-password/reset-password` | `vendor.auth.forgot-password.` | `App\Http\Controllers\Vendor\Auth\ForgotPasswordController@resetPassword` |
| 162 | `GET` | `/vendor/auth/registration/index` | `vendor.auth.registration.index` | `App\Http\Controllers\Vendor\Auth\RegisterController@index` |
| 163 | `POST` | `/vendor/auth/registration/add` | `vendor.auth.registration.add` | `App\Http\Controllers\Vendor\Auth\RegisterController@add` |
| 164 | `GET` | `/vendor/dashboard` | `vendor.dashboard.index` | `App\Http\Controllers\Vendor\DashboardController@index` |
| 165 | `GET` | `/vendor/dashboard/order-status/{type}` | `vendor.dashboard.order-status` | `App\Http\Controllers\Vendor\DashboardController@getOrderStatus` |
| 166 | `GET` | `/vendor/dashboard/earning-statistics` | `vendor.dashboard.earning-statistics` | `App\Http\Controllers\Vendor\DashboardController@getEarningStatistics` |
| 167 | `POST` | `/vendor/dashboard/withdraw-request` | `vendor.dashboard.withdraw-request` | `App\Http\Controllers\Vendor\DashboardController@getWithdrawRequest` |
| 168 | `GET` | `/vendor/dashboard/withdraw-request` | `vendor.dashboard.method-list` | `App\Http\Controllers\Vendor\DashboardController@getMethodList` |
| 169 | `GET` | `/vendor/dashboard/real-time-activities` | `vendor.dashboard.real-time-activities` | `App\Http\Controllers\Vendor\DashboardController@getRealTimeActivities` |
| 170 | `GET` | `/vendor/refund/index/{status}` | `vendor.refund.index` | `App\Http\Controllers\Vendor\RefundController@index` |
| 171 | `GET` | `/vendor/refund/details/{id}` | `vendor.refund.details` | `App\Http\Controllers\Vendor\RefundController@getDetailsView` |
| 172 | `POST` | `/vendor/refund/update-status` | `vendor.refund.update-status` | `App\Http\Controllers\Vendor\RefundController@updateStatus` |
| 173 | `GET` | `/vendor/refund/export/{status}` | `vendor.refund.export` | `App\Http\Controllers\Vendor\RefundController@exportList` |
| 174 | `GET` | `/vendor/products/list/{type}` | `vendor.products.list` | `App\Http\Controllers\Vendor\Product\ProductController@index` |
| 175 | `GET` | `/vendor/products/add` | `vendor.products.add` | `App\Http\Controllers\Vendor\Product\ProductController@getAddView` |
| 176 | `POST` | `/vendor/products/add` | `vendor.products.` | `App\Http\Controllers\Vendor\Product\ProductController@add` |
| 177 | `GET` | `/vendor/products/get-categories` | `vendor.products.get-categories` | `App\Http\Controllers\Vendor\Product\ProductController@getCategories` |
| 178 | `POST` | `/vendor/products/sku-combination` | `vendor.products.sku-combination` | `App\Http\Controllers\Vendor\Product\ProductController@getSkuCombinationView` |
| 179 | `POST` | `/vendor/products/digital-variation-combination` | `vendor.products.digital-variation-combination` | `App\Http\Controllers\Vendor\Product\ProductController@getDigitalVariationCombinationView` |
| 180 | `POST` | `/vendor/products/digital-variation-file-delete` | `vendor.products.digital-variation-file-delete` | `App\Http\Controllers\Vendor\Product\ProductController@deleteDigitalVariationFile` |
| 181 | `POST` | `/vendor/products/status-update` | `vendor.products.status-update` | `App\Http\Controllers\Vendor\Product\ProductController@updateStatus` |
| 182 | `GET` | `/vendor/products/export-excel/{type}` | `vendor.products.export-excel` | `App\Http\Controllers\Vendor\Product\ProductController@exportList` |
| 183 | `GET` | `/vendor/products/view/{id}` | `vendor.products.view` | `App\Http\Controllers\Vendor\Product\ProductController@getView` |
| 184 | `GET` | `/vendor/products/barcode/{id}` | `vendor.products.barcode` | `App\Http\Controllers\Vendor\Product\ProductController@getBarcodeView` |
| 185 | `DELETE` | `/vendor/products/delete/{id}` | `vendor.products.delete` | `App\Http\Controllers\Vendor\Product\ProductController@delete` |
| 186 | `GET` | `/vendor/products/stock-limit-list` | `vendor.products.stock-limit-list` | `App\Http\Controllers\Vendor\Product\ProductController@getStockLimitListView` |
| 187 | `POST` | `/vendor/products/update-quantity` | `vendor.products.update-quantity` | `App\Http\Controllers\Vendor\Product\ProductController@updateQuantity` |
| 188 | `GET` | `/vendor/products/update/{id}` | `vendor.products.update` | `App\Http\Controllers\Vendor\Product\ProductController@getUpdateView` |
| 189 | `POST` | `/vendor/products/update/{id}` | `vendor.products.` | `App\Http\Controllers\Vendor\Product\ProductController@update` |
| 190 | `POST` | `/vendor/products/quick-price-stock-update` | `vendor.products.quick-price-stock-update` | `App\Http\Controllers\Vendor\Product\ProductController@quickPriceStockUpdate` |
| 191 | `POST` | `/vendor/products/update-product-images/{id}` | `vendor.products.update-product-images` | `App\Http\Controllers\Vendor\Product\ProductController@updateProductImages` |
| 192 | `GET` | `/vendor/products/delete-image` | `vendor.products.delete-image` | `App\Http\Controllers\Vendor\Product\ProductController@deleteImage` |
| 193 | `GET` | `/vendor/products/get-variations` | `vendor.products.get-variations` | `App\Http\Controllers\Vendor\Product\ProductController@getVariations` |
| 194 | `GET` | `/vendor/products/bulk-import` | `vendor.products.bulk-import` | `App\Http\Controllers\Vendor\Product\ProductController@getBulkImportView` |
| 195 | `POST` | `/vendor/products/bulk-import` | `vendor.products.` | `App\Http\Controllers\Vendor\Product\ProductController@importBulkProduct` |
| 196 | `GET` | `/vendor/products/search` | `vendor.products.search-product` | `App\Http\Controllers\Vendor\Product\ProductController@getSearchedProductsView` |
| 197 | `GET` | `/vendor/products/product-gallery` | `vendor.products.product-gallery` | `App\Http\Controllers\Vendor\Product\ProductController@getProductGalleryView` |
| 198 | `GET` | `/vendor/products/stock-limit-status` | `vendor.products.stock-limit-status` | `App\Http\Controllers\Vendor\Product\ProductController@getStockLimitStatus` |
| 199 | `POST` | `/vendor/products/delete-preview-file` | `vendor.products.delete-preview-file` | `App\Http\Controllers\Vendor\Product\ProductController@deletePreviewFile` |
| 200 | `GET` | `/vendor/products/request-restock-list` | `vendor.products.request-restock-list` | `App\Http\Controllers\Vendor\Product\ProductController@getRequestRestockListView` |
| 201 | `GET` | `/vendor/products/export-restock` | `vendor.products.restock-export` | `App\Http\Controllers\Vendor\Product\ProductController@exportRestockList` |
| 202 | `DELETE` | `/vendor/products/delete-restock/{id}` | `vendor.products.restock-delete` | `App\Http\Controllers\Vendor\Product\ProductController@deleteRestock` |
| 203 | `GET` | `/vendor/products/get-category-specifications/{category_id}` | `vendor.products.get-category-specifications` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@getByCategoryAjax` |
| 204 | `POST` | `/vendor/products/ai-suggest-specs` | `vendor.products.ai-suggest-specs` | `App\Http\Controllers\Admin\Product\CategorySpecificationController@aiSuggestSpecs` |
| 205 | `POST` | `/vendor/products/load-more-brands` | `vendor.products.load-more-brands` | `App\Http\Controllers\Vendor\Product\ProductController@loadMoreBrands` |
| 206 | `GET` | `/vendor/orders/list/{status}` | `vendor.orders.list` | `App\Http\Controllers\Vendor\Order\OrderController@index` |
| 207 | `GET` | `/vendor/orders/customers` | `vendor.orders.customers` | `App\Http\Controllers\Vendor\Order\OrderController@getCustomers` |
| 208 | `GET` | `/vendor/orders/export-excel/{status}` | `vendor.orders.export-excel` | `App\Http\Controllers\Vendor\Order\OrderController@exportList` |
| 209 | `GET` | `/vendor/orders/generate-invoice/{id}` | `vendor.orders.generate-invoice` | `App\Http\Controllers\Vendor\Order\OrderController@generateInvoice` |
| 210 | `GET` | `/vendor/orders/generate-packing-slip/{id}` | `vendor.orders.generate-packing-slip` | `App\Http\Controllers\Vendor\Order\OrderController@generatePackingSlip` |
| 211 | `GET` | `/vendor/orders/details/{id}` | `vendor.orders.details` | `App\Http\Controllers\Vendor\Order\OrderController@getView` |
| 212 | `POST` | `/vendor/orders/address-update` | `vendor.orders.address-update` | `App\Http\Controllers\Vendor\Order\OrderController@updateAddress` |
| 213 | `POST` | `/vendor/orders/payment-status` | `vendor.orders.payment-status` | `App\Http\Controllers\Vendor\Order\OrderController@updatePaymentStatus` |
| 214 | `POST` | `/vendor/orders/update-deliver-info` | `vendor.orders.update-deliver-info` | `App\Http\Controllers\Vendor\Order\OrderController@updateDeliverInfo` |
| 215 | `GET` | `/vendor/orders/add-delivery-man/{order_id}/{d_man_id}` | `vendor.orders.add-delivery-man` | `App\Http\Controllers\Vendor\Order\OrderController@addDeliveryMan` |
| 216 | `POST` | `/vendor/orders/amount-date-update` | `vendor.orders.amount-date-update` | `App\Http\Controllers\Vendor\Order\OrderController@updateAmountDate` |
| 217 | `POST` | `/vendor/orders/digital-file-upload-after-sell` | `vendor.orders.digital-file-upload-after-sell` | `App\Http\Controllers\Vendor\Order\OrderController@uploadDigitalFileAfterSell` |
| 218 | `POST` | `/vendor/orders/status` | `vendor.orders.status` | `App\Http\Controllers\Vendor\Order\OrderController@updateStatus` |
| 219 | `POST` | `/vendor/orders/customer-return-amount` | `vendor.orders.customer-return-amount` | `App\Http\Controllers\Vendor\Order\OrderController@orderReturnAmountToCustomer` |
| 220 | `POST` | `/vendor/orders/customer-due-amount` | `vendor.orders.customer-due-amount` | `App\Http\Controllers\Vendor\Order\OrderController@orderDueAmountSwitchToCOD` |
| 221 | `POST` | `/vendor/orders/customer-due-amount-mark-as-paid` | `vendor.orders.customer-due-amount-mark-as-paid` | `App\Http\Controllers\Vendor\Order\OrderController@orderDueAmountMarkAsPaid` |
| 222 | `GET` | `/vendor/orders/search-for-edit-order-product` | `vendor.orders.search-for-edit-order-product` | `App\Http\Controllers\Vendor\Order\OrderEditController@getSearchEditOrderProductsView` |
| 223 | `POST` | `/vendor/orders/edit-order-product-modal-view` | `vendor.orders.edit-order-product-modal-view` | `App\Http\Controllers\Vendor\Order\OrderEditController@getEditOrderProductModalView` |
| 224 | `POST` | `/vendor/orders/edit-order-product-add` | `vendor.orders.edit-order-product-add` | `App\Http\Controllers\Vendor\Order\OrderEditController@addEditOrderProduct` |
| 225 | `POST` | `/vendor/orders/edit-order-product-variant-price` | `vendor.orders.edit-order-product-variant-price` | `App\Http\Controllers\Vendor\Order\OrderEditController@checkProductVariantPrice` |
| 226 | `POST` | `/vendor/orders/edit-order-product-list-update` | `vendor.orders.edit-order-product-list-update` | `App\Http\Controllers\Vendor\Order\OrderEditController@updateEditOrderProductList` |
| 227 | `POST` | `/vendor/orders/edit-order-product-remove` | `vendor.orders.edit-order-product-remove` | `App\Http\Controllers\Vendor\Order\OrderEditController@removeEditOrderProduct` |
| 228 | `POST` | `/vendor/orders/edit-order-generate` | `vendor.orders.edit-order-generate` | `App\Http\Controllers\Vendor\Order\OrderEditController@generateEditOrderByProductList` |
| 229 | `GET` | `/vendor/customer/list` | `vendor.customer.list` | `App\Http\Controllers\Vendor\CustomerController@getList` |
| 230 | `POST` | `/vendor/customer/add` | `vendor.customer.add` | `App\Http\Controllers\Vendor\CustomerController@add` |
| 231 | `GET` | `/vendor/reviews/index` | `vendor.reviews.index` | `App\Http\Controllers\Vendor\ReviewController@index` |
| 232 | `GET` | `/vendor/reviews/update-status/{id}/{status}` | `vendor.reviews.update-status` | `App\Http\Controllers\Vendor\ReviewController@updateStatus` |
| 233 | `GET` | `/vendor/reviews/export` | `vendor.reviews.export` | `App\Http\Controllers\Vendor\ReviewController@exportList` |
| 234 | `POST` | `/vendor/reviews/add-review-reply` | `vendor.reviews.add-review-reply` | `App\Http\Controllers\Vendor\ReviewController@addReviewReply` |
| 235 | `GET` | `/vendor/coupon/index` | `vendor.coupon.index` | `App\Http\Controllers\Vendor\Coupon\CouponController@index` |
| 236 | `POST` | `/vendor/coupon/add` | `vendor.coupon.add` | `App\Http\Controllers\Vendor\Coupon\CouponController@add` |
| 237 | `GET` | `/vendor/coupon/update/{id}` | `vendor.coupon.update` | `App\Http\Controllers\Vendor\Coupon\CouponController@getUpdateView` |
| 238 | `POST` | `/vendor/coupon/update/{id}` | `vendor.coupon.` | `App\Http\Controllers\Vendor\Coupon\CouponController@update` |
| 239 | `GET` | `/vendor/coupon/update-status/{id}/{status}` | `vendor.coupon.update-status` | `App\Http\Controllers\Vendor\Coupon\CouponController@updateStatus` |
| 240 | `DELETE` | `/vendor/coupon/delete/{id}` | `vendor.coupon.delete` | `App\Http\Controllers\Vendor\Coupon\CouponController@delete` |
| 241 | `GET` | `/vendor/coupon/quick-view` | `vendor.coupon.quick-view` | `App\Http\Controllers\Vendor\Coupon\CouponController@getQuickView` |
| 242 | `GET` | `/vendor/coupon/export` | `vendor.coupon.export` | `App\Http\Controllers\Vendor\Coupon\CouponController@exportList` |
| 243 | `GET` | `/vendor/clearance-sale` | `vendor.clearance-sale.index` | `App\Http\Controllers\Vendor\Promotion\ClearanceSaleController@index` |
| 244 | `POST` | `/vendor/clearance-sale/status-update` | `vendor.clearance-sale.status-update` | `App\Http\Controllers\Vendor\Promotion\ClearanceSaleController@updateStatus` |
| 245 | `POST` | `/vendor/clearance-sale/update-config` | `vendor.clearance-sale.update-config` | `App\Http\Controllers\Vendor\Promotion\ClearanceSaleController@updateClearanceConfig` |
| 246 | `POST` | `/vendor/clearance-sale/update-seo-meta` | `vendor.clearance-sale.update-seo-meta` | `App\Http\Controllers\Vendor\Promotion\ClearanceSaleController@updateClearanceSeoConfig` |
| 247 | `GET` | `/vendor/clearance-sale/search` | `vendor.clearance-sale.search-product-for-clearance` | `App\Http\Controllers\Vendor\Promotion\ClearanceSaleController@getSearchedProductsView` |
| 248 | `GET` | `/vendor/clearance-sale/multiple-product-details` | `vendor.clearance-sale.multiple-clearance-product-details` | `App\Http\Controllers\Vendor\Promotion\ClearanceSaleController@getMultipleProductDetailsView` |
| 249 | `POST` | `/vendor/clearance-sale/add-clearance-product` | `vendor.clearance-sale.add-product` | `App\Http\Controllers\Vendor\Promotion\ClearanceSaleController@addClearanceProduct` |
| 250 | `POST` | `/vendor/clearance-sale/clearance-product-status-update` | `vendor.clearance-sale.product-status-update` | `App\Http\Controllers\Vendor\Promotion\ClearanceSaleController@updateProductStatus` |
| 251 | `DELETE` | `/vendor/clearance-sale/clearance-delete/{product_id}` | `vendor.clearance-sale.clearance-delete` | `App\Http\Controllers\Vendor\Promotion\ClearanceSaleController@deleteClearanceProduct` |
| 252 | `DELETE` | `/vendor/clearance-sale/clearance-products-delete` | `vendor.clearance-sale.clearance-delete-all-product` | `App\Http\Controllers\Vendor\Promotion\ClearanceSaleController@deleteClearanceAllProduct` |
| 253 | `POST` | `/vendor/clearance-sale/update-discount` | `vendor.clearance-sale.update-discount` | `App\Http\Controllers\Vendor\Promotion\ClearanceSaleController@updateDiscountAmount` |
| 254 | `GET` | `/vendor/messages/index/{type}` | `vendor.messages.index` | `App\Http\Controllers\Vendor\ChattingController@index` |
| 255 | `GET` | `/vendor/messages/message` | `vendor.messages.message` | `App\Http\Controllers\Vendor\ChattingController@getMessageByUser` |
| 256 | `POST` | `/vendor/messages/message` | `vendor.messages.` | `App\Http\Controllers\Vendor\ChattingController@addVendorMessage` |
| 257 | `GET` | `/vendor/messages/new-notification` | `vendor.messages.new-notification` | `App\Http\Controllers\Vendor\ChattingController@getNewNotification` |
| 258 | `POST` | `/vendor/notification/index` | `vendor.notification.index` | `App\Http\Controllers\Vendor\NotificationController@getNotificationModalView` |
| 259 | `GET` | `/vendor/delivery-man/index` | `vendor.delivery-man.index` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManController@index` |
| 260 | `POST` | `/vendor/delivery-man/index` | `vendor.delivery-man.` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManController@add` |
| 261 | `GET` | `/vendor/delivery-man/list` | `vendor.delivery-man.list` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManController@getListView` |
| 262 | `GET` | `/vendor/delivery-man/export` | `vendor.delivery-man.export` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManController@exportList` |
| 263 | `GET` | `/vendor/delivery-man/update/{id}` | `vendor.delivery-man.update` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManController@getUpdateView` |
| 264 | `POST` | `/vendor/delivery-man/update/{id}` | `vendor.delivery-man.` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManController@update` |
| 265 | `POST` | `/vendor/delivery-man/update-status/{id}` | `vendor.delivery-man.update-status` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManController@updateStatus` |
| 266 | `DELETE` | `/vendor/delivery-man/delete/{id}` | `vendor.delivery-man.delete` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManController@delete` |
| 267 | `GET` | `/vendor/delivery-man/rating/{id}` | `vendor.delivery-man.rating` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManController@getRatingView` |
| 268 | `GET` | `/vendor/delivery-man/wallet/index/{id}` | `vendor.delivery-man.wallet.index` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManWalletController@index` |
| 269 | `GET` | `/vendor/delivery-man/wallet/order-history/{id}` | `vendor.delivery-man.wallet.order-history` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManWalletController@getOrderHistory` |
| 270 | `GET` | `/vendor/delivery-man/wallet/order-history-status/{order}` | `vendor.delivery-man.wallet.order-status-history` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManWalletController@getOrderStatusHistory` |
| 271 | `GET` | `/vendor/delivery-man/wallet/earning/{id}` | `vendor.delivery-man.wallet.earning` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManWalletController@getEarningListView` |
| 272 | `GET` | `/vendor/delivery-man/wallet/cash-collect/{id}` | `vendor.delivery-man.wallet.cash-collect` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManWalletController@getCashCollectView` |
| 273 | `POST` | `/vendor/delivery-man/wallet/cash-collect/{id}` | `vendor.delivery-man.wallet.` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManWalletController@collectCash` |
| 274 | `GET` | `/vendor/delivery-man/withdraw/index` | `vendor.delivery-man.withdraw.index` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManWithdrawController@index` |
| 275 | `POST` | `/vendor/delivery-man/withdraw/index` | `vendor.delivery-man.withdraw.` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManWithdrawController@getFiltered` |
| 276 | `GET` | `/vendor/delivery-man/withdraw/details/{withdrawId}` | `vendor.delivery-man.withdraw.details` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManWithdrawController@getDetails` |
| 277 | `POST` | `/vendor/delivery-man/withdraw/update-status/{withdrawId}` | `vendor.delivery-man.withdraw.update-status` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManWithdrawController@updateStatus` |
| 278 | `GET` | `/vendor/delivery-man/withdraw/export` | `vendor.delivery-man.withdraw.export` | `App\Http\Controllers\Vendor\DeliveryMan\DeliveryManWithdrawController@exportList` |
| 279 | `GET` | `/vendor/delivery-man/emergency-contact/index` | `vendor.delivery-man.emergency-contact.index` | `App\Http\Controllers\Vendor\DeliveryMan\EmergencyContactController@index` |
| 280 | `POST` | `/vendor/delivery-man/emergency-contact/index` | `vendor.delivery-man.emergency-contact.` | `App\Http\Controllers\Vendor\DeliveryMan\EmergencyContactController@add` |
| 281 | `GET` | `/vendor/delivery-man/emergency-contact/update/{id}` | `vendor.delivery-man.emergency-contact.update` | `App\Http\Controllers\Vendor\DeliveryMan\EmergencyContactController@getUpdateView` |
| 282 | `POST` | `/vendor/delivery-man/emergency-contact/update/{id}` | `vendor.delivery-man.emergency-contact.` | `App\Http\Controllers\Vendor\DeliveryMan\EmergencyContactController@update` |
| 283 | `PATCH` | `/vendor/delivery-man/emergency-contact/index` | `vendor.delivery-man.emergency-contact.` | `App\Http\Controllers\Vendor\DeliveryMan\EmergencyContactController@updateStatus` |
| 284 | `DELETE` | `/vendor/delivery-man/emergency-contact/index` | `vendor.delivery-man.emergency-contact.` | `App\Http\Controllers\Vendor\DeliveryMan\EmergencyContactController@delete` |
| 285 | `GET` | `/vendor/profile/index` | `vendor.profile.index` | `App\Http\Controllers\Vendor\ProfileController@index` |
| 286 | `GET` | `/vendor/profile/update/{id}` | `vendor.profile.update` | `App\Http\Controllers\Vendor\ProfileController@getUpdateView` |
| 287 | `POST` | `/vendor/profile/update/{id}` | `vendor.profile.` | `App\Http\Controllers\Vendor\ProfileController@update` |
| 288 | `PATCH` | `/vendor/profile/update/{id}` | `vendor.profile.` | `App\Http\Controllers\Vendor\ProfileController@updatePassword` |
| 289 | `GET` | `/vendor/profile/update-bank-info/{id}` | `vendor.profile.update-bank-info` | `App\Http\Controllers\Vendor\ProfileController@getBankInfoUpdateView` |
| 290 | `POST` | `/vendor/profile/update-bank-info/{id}` | `vendor.profile.` | `App\Http\Controllers\Vendor\ProfileController@updateBankInfo` |
| 291 | `GET` | `/vendor/shop/index` | `vendor.shop.index` | `App\Http\Controllers\Vendor\ShopController@index` |
| 292 | `GET` | `/vendor/shop/update/{id}` | `vendor.shop.update` | `App\Http\Controllers\Vendor\ShopController@getUpdateView` |
| 293 | `POST` | `/vendor/shop/update/{id}` | `vendor.shop.` | `App\Http\Controllers\Vendor\ShopController@update` |
| 294 | `POST` | `/vendor/shop/add-vacation` | `vendor.shop.update-vacation` | `App\Http\Controllers\Vendor\ShopController@updateVacation` |
| 295 | `POST` | `/vendor/shop/close-shop-temporary` | `vendor.shop.close-shop-temporary` | `App\Http\Controllers\Vendor\ShopController@closeShopTemporary` |
| 296 | `POST` | `/vendor/shop/update-other-settings` | `vendor.shop.update-other-settings` | `App\Http\Controllers\Vendor\ShopController@updateOtherSettings` |
| 297 | `GET` | `/vendor/shop/other-setup` | `vendor.shop.other-setup` | `App\Http\Controllers\Vendor\ShopController@getOtherSetupView` |
| 298 | `GET` | `/vendor/shop/payment-information` | `vendor.shop.payment-information.index` | `App\Http\Controllers\Vendor\VendorPaymentInfoController@index` |
| 299 | `POST` | `/vendor/shop/payment-information/add` | `vendor.shop.payment-information.add` | `App\Http\Controllers\Vendor\VendorPaymentInfoController@add` |
| 300 | `POST` | `/vendor/shop/payment-information/update` | `vendor.shop.payment-information.update` | `App\Http\Controllers\Vendor\VendorPaymentInfoController@update` |
| 301 | `GET` | `/vendor/shop/payment-information/edit/{id?}` | `vendor.shop.payment-information.update-view` | `App\Http\Controllers\Vendor\VendorPaymentInfoController@getUpdateView` |
| 302 | `GET` | `/vendor/shop/payment-information/delete/{id?}` | `vendor.shop.payment-information.delete` | `App\Http\Controllers\Vendor\VendorPaymentInfoController@delete` |
| 303 | `POST` | `/vendor/shop/payment-information/default` | `vendor.shop.payment-information.default` | `App\Http\Controllers\Vendor\VendorPaymentInfoController@updateDefault` |
| 304 | `POST` | `/vendor/shop/payment-information/status` | `vendor.shop.payment-information.update-status` | `App\Http\Controllers\Vendor\VendorPaymentInfoController@updateStatus` |
| 305 | `GET` | `/vendor/shop/payment-information/dynamic-fields` | `vendor.shop.payment-information.dynamic-fields` | `App\Http\Controllers\Vendor\VendorPaymentInfoController@getDynamicPaymentInformationView` |
| 306 | `GET` | `/vendor/business-settings/shipping-method/index` | `vendor.business-settings.shipping-method.index` | `App\Http\Controllers\Vendor\Shipping\ShippingMethodController@index` |
| 307 | `POST` | `/vendor/business-settings/shipping-method/index` | `vendor.business-settings.shipping-method.` | `App\Http\Controllers\Vendor\Shipping\ShippingMethodController@add` |
| 308 | `GET` | `/vendor/business-settings/shipping-method/update/{id}` | `vendor.business-settings.shipping-method.update` | `App\Http\Controllers\Vendor\Shipping\ShippingMethodController@getUpdateView` |
| 309 | `POST` | `/vendor/business-settings/shipping-method/update/{id}` | `vendor.business-settings.shipping-method.` | `App\Http\Controllers\Vendor\Shipping\ShippingMethodController@update` |
| 310 | `POST` | `/vendor/business-settings/shipping-method/update-status` | `vendor.business-settings.shipping-method.update-status` | `App\Http\Controllers\Vendor\Shipping\ShippingMethodController@updateStatus` |
| 311 | `POST` | `/vendor/business-settings/shipping-method/delete` | `vendor.business-settings.shipping-method.delete` | `App\Http\Controllers\Vendor\Shipping\ShippingMethodController@delete` |
| 312 | `POST` | `/vendor/business-settings/shipping-type/index` | `vendor.business-settings.shipping-type.index` | `App\Http\Controllers\Vendor\Shipping\ShippingTypeController@addOrUpdate` |
| 313 | `POST` | `/vendor/business-settings/category-wise-shipping-cost/index` | `vendor.business-settings.category-wise-shipping-cost.index` | `App\Http\Controllers\Vendor\Shipping\CategoryShippingCostController@index` |
| 314 | `GET` | `/vendor/business-settings/withdraw/index` | `vendor.business-settings.withdraw.index` | `App\Http\Controllers\Vendor\WithdrawController@index` |
| 315 | `POST` | `/vendor/business-settings/withdraw/index` | `vendor.business-settings.withdraw.` | `App\Http\Controllers\Vendor\WithdrawController@getListByStatus` |
| 316 | `GET` | `/vendor/business-settings/withdraw/close/{id}` | `vendor.business-settings.withdraw.close` | `App\Http\Controllers\Vendor\WithdrawController@closeWithdrawRequest` |
| 317 | `GET` | `/vendor/business-settings/withdraw/export` | `vendor.business-settings.withdraw.export-withdraw-list` | `App\Http\Controllers\Vendor\WithdrawController@exportList` |
| 318 | `POST` | `/vendor/business-settings/withdraw/render-withdraw-method-infos` | `vendor.business-settings.withdraw.render-withdraw-method-infos` | `App\Http\Controllers\Vendor\WithdrawController@renderInfosView` |
| 319 | `GET` | `/vendor/get-order-data` | `vendor.get-order-data` | `App\Http\Controllers\Vendor\SystemController@getOrderData` |
| 320 | `GET` | `/vendor/report/all-product` | `vendor.report.all-product` | `App\Http\Controllers\Vendor\ProductReportController@all_product` |
| 321 | `GET` | `/vendor/report/all-product-excel` | `vendor.report.all-product-excel` | `App\Http\Controllers\Vendor\ProductReportController@allProductExportExcel` |
| 322 | `GET` | `/vendor/report/stock-product-report` | `vendor.report.stock-product-report` | `App\Http\Controllers\Vendor\ProductReportController@stock_product_report` |
| 323 | `GET` | `/vendor/report/product-stock-export` | `vendor.report.product-stock-export` | `App\Http\Controllers\Vendor\ProductReportController@productStockExport` |
| 324 | `GET` | `/vendor/report/order-report` | `vendor.report.order-report` | `App\Http\Controllers\Vendor\OrderReportController@order_report` |
| 325 | `GET` | `/vendor/report/order-report-excel` | `vendor.report.order-report-excel` | `App\Http\Controllers\Vendor\OrderReportController@orderReportExportExcel` |
| 326 | `GET` | `/vendor/report/order-report-pdf` | `vendor.report.order-report-pdf` | `App\Http\Controllers\Vendor\OrderReportController@exportOrderReportInPDF` |
| 327 | `GET` | `/vendor/transaction/order-list` | `vendor.transaction.order-list` | `App\Http\Controllers\Vendor\TransactionReportController@order_transaction_list` |
| 328 | `GET` | `/vendor/transaction/pdf-order-wise-transaction` | `vendor.transaction.pdf-order-wise-transaction` | `App\Http\Controllers\Vendor\TransactionReportController@pdf_order_wise_transaction` |
| 329 | `GET` | `/vendor/transaction/order-transaction-export-excel` | `vendor.transaction.order-transaction-export-excel` | `App\Http\Controllers\Vendor\TransactionReportController@orderTransactionExportExcel` |
| 330 | `GET` | `/vendor/transaction/expense-transaction-summary-pdf` | `vendor.transaction.expense-transaction-summary-pdf` | `App\Http\Controllers\Vendor\TransactionReportController@expense_transaction_summary_pdf` |
| 331 | `GET` | `/vendor/transaction/expense-transaction-export-excel` | `vendor.transaction.expense-transaction-export-excel` | `App\Http\Controllers\Vendor\TransactionReportController@expenseTransactionExportExcel` |
| 332 | `GET` | `/vendor/employee-role` | `vendor.employee-role.index` | `App\Http\Controllers\Vendor\Employee\VendorRoleController@index` |
| 333 | `POST` | `/vendor/employee-role/store` | `vendor.employee-role.store` | `App\Http\Controllers\Vendor\Employee\VendorRoleController@store` |
| 334 | `GET` | `/vendor/employee-role/edit/{id}` | `vendor.employee-role.edit` | `App\Http\Controllers\Vendor\Employee\VendorRoleController@edit` |
| 335 | `POST` | `/vendor/employee-role/update/{id}` | `vendor.employee-role.update` | `App\Http\Controllers\Vendor\Employee\VendorRoleController@update` |
| 336 | `POST` | `/vendor/employee-role/status` | `vendor.employee-role.status` | `App\Http\Controllers\Vendor\Employee\VendorRoleController@status` |
| 337 | `GET` | `/vendor/employee/list` | `vendor.employee.list` | `App\Http\Controllers\Vendor\Employee\VendorEmployeeController@list` |
| 338 | `GET` | `/vendor/employee/add-new` | `vendor.employee.add-new` | `App\Http\Controllers\Vendor\Employee\VendorEmployeeController@addNew` |
| 339 | `POST` | `/vendor/employee/store` | `vendor.employee.store` | `App\Http\Controllers\Vendor\Employee\VendorEmployeeController@store` |
| 340 | `GET` | `/vendor/employee/edit/{id}` | `vendor.employee.edit` | `App\Http\Controllers\Vendor\Employee\VendorEmployeeController@edit` |
| 341 | `POST` | `/vendor/employee/update/{id}` | `vendor.employee.update` | `App\Http\Controllers\Vendor\Employee\VendorEmployeeController@update` |
| 342 | `POST` | `/vendor/employee/status` | `vendor.employee.status` | `App\Http\Controllers\Vendor\Employee\VendorEmployeeController@status` |
| 343 | `POST` | `/vendor/orders/verify-pickup-otp` | `vendor.orders.verify-pickup-otp` | `App\Http\Controllers\Vendor\Order\InShopHandoverController@verifyPickupOtp` |
| 344 | `GET` | `/vendors` | `vendors` | `App\Http\Controllers\Web\WebController@getAllVendorsView` |
| 345 | `GET` | `/vendor-shop/{slug}` | `vendor-shop` | `App\Http\Controllers\Web\ShopViewController@seller_shop` |
| 346 | `POST` | `/vendor-shop/{id}` | `unnamed` | `App\Http\Controllers\Web\WebController@seller_shop_product` |
| 347 | `POST` | `/api/v3/seller/product/title-auto-fill` | `v3/seller.product.title-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@titleAutoFill` |
| 348 | `POST` | `/api/v3/seller/product/description-auto-fill` | `v3/seller.product.description-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@descriptionAutoFill` |
| 349 | `POST` | `/api/v3/seller/product/general-setup-auto-fill` | `v3/seller.product.general-setup-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@generalSetupAutoFill` |
| 350 | `POST` | `/api/v3/seller/product/price-others-auto-fill` | `v3/seller.product.price-others-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@pricingAndOthersAutoFill` |
| 351 | `POST` | `/api/v3/seller/product/seo-section-auto-fill` | `v3/seller.product.seo-section-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@productSeoSectionAutoFill` |
| 352 | `POST` | `/api/v3/seller/product/variation-setup-auto-fill` | `v3/seller.product.variation-setup-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@productVariationSetupAutoFill` |
| 353 | `POST` | `/api/v3/seller/product/analyze-image-auto-fill` | `v3/seller.product.analyze-image-auto-fill` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@generateTitleFromImages` |
| 354 | `POST` | `/api/v3/seller/product/generate-title-suggestions` | `v3/seller.product.generate-title-suggestions` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@generateProductTitleSuggestion` |
| 355 | `GET` | `/api/v3/seller/product/generate-limit-check` | `v3/seller.product.` | `Modules\AI\app\Http\Controllers\API\V3\AIProductController@generateLimitCheck` |
| 356 | `GET` | `/vendor/product/title-auto-fill` | `vendor.product.title-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@titleAutoFill` |
| 357 | `GET` | `/vendor/product/description-auto-fill` | `vendor.product.description-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@descriptionAutoFill` |
| 358 | `GET` | `/vendor/product/general-setup-auto-fill` | `vendor.product.general-setup-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@generalSetupAutoFill` |
| 359 | `GET` | `/vendor/product/price-others-auto-fill` | `vendor.product.price-others-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@pricingAndOthersAutoFill` |
| 360 | `GET` | `/vendor/product/seo-section-auto-fill` | `vendor.product.seo-section-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@productSeoSectionAutoFill` |
| 361 | `GET` | `/vendor/product/variation-setup-auto-fill` | `vendor.product.variation-setup-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@productVariationSetupAutoFill` |
| 362 | `POST` | `/vendor/product/analyze-image-auto-fill` | `vendor.product.analyze-image-auto-fill` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@generateTitleFromImages` |
| 363 | `POST` | `/vendor/product/generate-title-suggestions` | `vendor.product.generate-title-suggestions` | `Modules\AI\app\Http\Controllers\Vendor\AIProductController@generateProductTitleSuggestion` |
| 364 | `GET` | `/pos` | `pos.dashboard` | `Modules\Pos\app\Http\Controllers\DashboardController@index` |
| 365 | `GET` | `/pos/dashboard` | `pos.` | `Modules\Pos\app\Http\Controllers\DashboardController@index` |
| 366 | `GET` | `/pos/terminal` | `pos.index` | `Modules\Pos\app\Http\Controllers\PosController@index` |
| 367 | `POST` | `/pos/checkout` | `pos.checkout` | `Modules\Pos\app\Http\Controllers\PosController@checkout` |
| 368 | `GET` | `/pos/receipt/{id}` | `pos.receipt` | `Modules\Pos\app\Http\Controllers\PosController@receipt` |
| 369 | `GET` | `/pos/returns` | `pos.returns` | `Modules\Pos\app\Http\Controllers\PosController@returns` |
| 370 | `POST` | `/pos/returns/process` | `pos.returns.process` | `Modules\Pos\app\Http\Controllers\PosController@processReturn` |
| 371 | `POST` | `/pos/customer/quick-register` | `pos.customer.quick-register` | `Modules\Pos\app\Http\Controllers\PosController@quickRegisterCustomer` |
| 372 | `GET` | `/pos/products/template/csv` | `pos.products.template.csv` | `Modules\Pos\app\Http\Controllers\ProductController@downloadCsvTemplate` |
| 373 | `GET` | `/pos/products/export/csv` | `pos.products.export.csv` | `Modules\Pos\app\Http\Controllers\ProductController@exportCsv` |
| 374 | `GET` | `/pos/products/export/json` | `pos.products.export.json` | `Modules\Pos\app\Http\Controllers\ProductController@exportJson` |
| 375 | `POST` | `/pos/products/import/csv` | `pos.products.import.csv` | `Modules\Pos\app\Http\Controllers\ProductController@importCsv` |
| 376 | `GET` | `/pos/products` | `pos.products.index` | `Modules\Pos\app\Http\Controllers\ProductController@index` |
| 377 | `GET` | `/pos/products/create` | `pos.products.create` | `Modules\Pos\app\Http\Controllers\ProductController@create` |
| 378 | `POST` | `/pos/products` | `pos.products.store` | `Modules\Pos\app\Http\Controllers\ProductController@store` |
| 379 | `GET` | `/pos/products/{product}/edit` | `pos.products.edit` | `Modules\Pos\app\Http\Controllers\ProductController@edit` |
| 380 | `PUT` | `/pos/products/{product}` | `pos.products.update` | `Modules\Pos\app\Http\Controllers\ProductController@update` |
| 381 | `DELETE` | `/pos/products/{product}` | `pos.products.destroy` | `Modules\Pos\app\Http\Controllers\ProductController@destroy` |
| 382 | `GET` | `/pos/warehouses/ajax/cities/{state_id}` | `pos.warehouses.cities-ajax` | `Modules\Pos\app\Http\Controllers\WarehouseController@getCitiesAjax` |
| 383 | `GET` | `/pos/warehouses/ajax/hubs/{city_id}` | `pos.warehouses.hubs-ajax` | `Modules\Pos\app\Http\Controllers\WarehouseController@getHubsAjax` |
| 384 | `GET` | `/pos/warehouses` | `pos.warehouses.index` | `Modules\Pos\app\Http\Controllers\WarehouseController@index` |
| 385 | `GET` | `/pos/warehouses/create` | `pos.warehouses.create` | `Modules\Pos\app\Http\Controllers\WarehouseController@create` |
| 386 | `POST` | `/pos/warehouses` | `pos.warehouses.store` | `Modules\Pos\app\Http\Controllers\WarehouseController@store` |
| 387 | `GET` | `/pos/warehouses/{warehouse}` | `pos.warehouses.show` | `Modules\Pos\app\Http\Controllers\WarehouseController@show` |
| 388 | `GET` | `/pos/warehouses/{warehouse}/edit` | `pos.warehouses.edit` | `Modules\Pos\app\Http\Controllers\WarehouseController@edit` |
| 389 | `PUT` | `/pos/warehouses/{warehouse}` | `pos.warehouses.update` | `Modules\Pos\app\Http\Controllers\WarehouseController@update` |
| 390 | `DELETE` | `/pos/warehouses/{warehouse}` | `pos.warehouses.destroy` | `Modules\Pos\app\Http\Controllers\WarehouseController@destroy` |
| 391 | `GET` | `/pos/stock` | `pos.stock.index` | `Modules\Pos\app\Http\Controllers\StockController@index` |
| 392 | `GET` | `/pos/stock/in` | `pos.stock.in.form` | `Modules\Pos\app\Http\Controllers\StockController@stockInForm` |
| 393 | `POST` | `/pos/stock/in` | `pos.stock.in` | `Modules\Pos\app\Http\Controllers\StockController@stockIn` |
| 394 | `GET` | `/pos/stock/transfers` | `pos.stock.transfers` | `Modules\Pos\app\Http\Controllers\StockController@transfers` |
| 395 | `POST` | `/pos/stock/transfers` | `pos.stock.transfers.create` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 396 | `POST` | `/pos/stock/transfers/out` | `pos.stock.transfer.out` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 397 | `POST` | `/pos/stock/transfers/{id}/recall` | `pos.stock.transfer.recall` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 398 | `POST` | `/pos/stock/transfers/{id}/accept` | `pos.stock.transfer.accept` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 399 | `GET` | `/pos/stock/transfers/{id}/waybill` | `pos.stock.waybill` | `Modules\Pos\app\Http\Controllers\StockController@waybill` |
| 400 | `GET` | `/pos/stock/adjustments` | `pos.stock.adjustments` | `Modules\Pos\app\Http\Controllers\StockController@adjustments` |
| 401 | `POST` | `/pos/stock/adjustments` | `pos.stock.adjustments.create` | `Modules\Pos\app\Http\Controllers\StockController@createAdjustment` |
| 402 | `POST` | `/pos/stock/adjustments/record` | `pos.stock.adjustments.record` | `Modules\Pos\app\Http\Controllers\StockController@createAdjustment` |
| 403 | `GET` | `/pos/stock/unsupplied` | `pos.stock.unsupplied` | `Modules\Pos\app\Http\Controllers\StockController@unsuppliedOrders` |
| 404 | `GET` | `/pos/transactions` | `pos.transactions.index` | `Modules\Pos\app\Http\Controllers\TransactionController@index` |
| 405 | `GET` | `/pos/transactions/cashier-shifts` | `pos.transactions.cashier-shifts` | `Modules\Pos\app\Http\Controllers\TransactionController@cashierShifts` |
| 406 | `GET` | `/pos/transactions/inventory-log` | `pos.transactions.inventory-log` | `Modules\Pos\app\Http\Controllers\TransactionController@inventoryLog` |
| 407 | `GET` | `/pos/transactions/export` | `pos.transactions.export` | `Modules\Pos\app\Http\Controllers\TransactionController@export` |
| 408 | `GET` | `/pos/transactions/export/csv` | `pos.transactions.export.csv` | `Modules\Pos\app\Http\Controllers\TransactionController@export` |
| 409 | `GET` | `/pos/transactions/export/json` | `pos.transactions.export.json` | `Modules\Pos\app\Http\Controllers\TransactionController@export` |
| 410 | `GET` | `/pos/debts` | `pos.debts.index` | `Modules\Pos\app\Http\Controllers\DebtController@index` |
| 411 | `GET` | `/pos/debts/customer/{id}` | `pos.debts.customer` | `Modules\Pos\app\Http\Controllers\DebtController@customerLedger` |
| 412 | `POST` | `/pos/debts/payment` | `pos.debts.payment` | `Modules\Pos\app\Http\Controllers\DebtController@recordPayment` |
| 413 | `GET` | `/pos/debts/export` | `pos.debts.export` | `Modules\Pos\app\Http\Controllers\DebtController@export` |
| 414 | `GET` | `/pos/reports` | `pos.reports.index` | `Modules\Pos\app\Http\Controllers\ReportController@index` |
| 415 | `GET` | `/pos/reports/profit-loss` | `pos.reports.profit-loss` | `Modules\Pos\app\Http\Controllers\ReportController@profitLoss` |
| 416 | `GET` | `/pos/reports/top-products` | `pos.reports.top-products` | `Modules\Pos\app\Http\Controllers\ReportController@topProducts` |
| 417 | `GET` | `/pos/reports/export/{type}` | `pos.reports.export` | `Modules\Pos\app\Http\Controllers\ReportController@exportCsv` |
| 418 | `GET` | `/pos/reports/export-json/{type}` | `pos.reports.export.json` | `Modules\Pos\app\Http\Controllers\ReportController@exportJson` |
| 419 | `GET` | `/api/v3/seller/get-vat-tax-report-list` | `unnamed` | `Modules\TaxModule\app\Http\Controllers\Api\v3\VendorTaxReportController@vendorWiseTaxes` |
| 420 | `GET` | `/vendor/report/get-vat-report` | `vendor.report.get-vat-report` | `Modules\TaxModule\app\Http\Controllers\Vendor\Reports\TaxReportController@vendorTaxReportList` |
| 421 | `GET` | `/vendor/report/get-vat-report-export` | `vendor.report.get-vat-report-export` | `Modules\TaxModule\app\Http\Controllers\Vendor\Reports\TaxReportController@vendorTaxExport` |

---

## 3. Disallowed & Gated Endpoints Summary (1162 Endpoints Blocked)

Attempting to access any of the 1162 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

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


