# Role Security & Endpoint Access Policy: Active Deliveryman (KYC Verified Rider)

> **Role Scope:** `Active Deliveryman (KYC Verified Rider)`  
> **Total Allowed Endpoints:** `49`  
> **Total Disallowed / Blocked Endpoints:** `1534`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

Approved logistics rider equipped with mobile dispatch, GPS breadcrumbs, and cash-in-hand collection privileges.

### Core Authorized Capabilities:
- ✅ **Mobile Dispatch Order Queue & Delivery Route Navigation**
- ✅ **Doorstep OTP Handshake & Package Delivery Confirmation**
- ✅ **Cash-on-Delivery (COD) Collection & Wallet Remittance Tracking**

### Strict Architectural Restrictions:
- ⛔ **Scoped strictly to authenticated delivery_man_id (Zero Cross-Rider Bleed)**
- ⛔ **Customer contact details masked to protect customer privacy**
- ⛔ **Blocked from merchant store panels and admin command centers**

---

## 2. Authorized Endpoints Access Matrix (49 Endpoints)

| # | Method | URI | Route Name | Action / Controller |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/api/v1/delivery-hubs/states` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@getStates` |
| 2 | `GET` | `/api/v1/delivery-hubs/cities/{state_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@getCities` |
| 3 | `GET` | `/api/v1/delivery-hubs/hubs/{city_id}` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@getHubs` |
| 4 | `POST` | `/api/v1/delivery-hubs/calculate-shipping` | `unnamed` | `App\Http\Controllers\RestAPI\v1\DeliveryHubApiController@calculateHubShipping` |
| 5 | `POST` | `/api/v2/delivery-man/auth/login` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\auth\LoginController@login` |
| 6 | `POST` | `/api/v2/delivery-man/auth/forgot-password` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\auth\LoginController@reset_password_request` |
| 7 | `POST` | `/api/v2/delivery-man/auth/verify-otp` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\auth\LoginController@otp_verification_submit` |
| 8 | `POST` | `/api/v2/delivery-man/auth/reset-password` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\auth\LoginController@reset_password_submit` |
| 9 | `PUT` | `/api/v2/delivery-man/language-change` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@language_change` |
| 10 | `PUT` | `/api/v2/delivery-man/is-online` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@is_online` |
| 11 | `GET` | `/api/v2/delivery-man/info` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@info` |
| 12 | `POST` | `/api/v2/delivery-man/distance-api` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@distance_api` |
| 13 | `GET` | `/api/v2/delivery-man/current-orders` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@get_current_orders` |
| 14 | `GET` | `/api/v2/delivery-man/all-orders` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@get_all_orders` |
| 15 | `POST` | `/api/v2/delivery-man/record-location-data` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@record_location_data` |
| 16 | `GET` | `/api/v2/delivery-man/order-delivery-history` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@get_order_history` |
| 17 | `PUT` | `/api/v2/delivery-man/update-order-status` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@update_order_status` |
| 18 | `PUT` | `/api/v2/delivery-man/update-expected-delivery` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@update_expected_delivery` |
| 19 | `PUT` | `/api/v2/delivery-man/update-payment-status` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@order_payment_status_update` |
| 20 | `PUT` | `/api/v2/delivery-man/order-update-is-pause` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@order_update_is_pause` |
| 21 | `GET` | `/api/v2/delivery-man/order-item` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@getOrderItem` |
| 22 | `GET` | `/api/v2/delivery-man/order-details` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@get_order_details` |
| 23 | `GET` | `/api/v2/delivery-man/last-location` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@get_last_location` |
| 24 | `PUT` | `/api/v2/delivery-man/update-fcm-token` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@update_fcm_token` |
| 25 | `GET` | `/api/v2/delivery-man/delivery-wise-earned` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@delivery_wise_earned` |
| 26 | `GET` | `/api/v2/delivery-man/order-list-by-date` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@order_list_date_filter` |
| 27 | `GET` | `/api/v2/delivery-man/search` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@search` |
| 28 | `GET` | `/api/v2/delivery-man/profile-dashboard-counts` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@profile_dashboard_counts` |
| 29 | `PUT` | `/api/v2/delivery-man/update-info` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@update_info` |
| 30 | `PUT` | `/api/v2/delivery-man/bank-info` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@bank_info` |
| 31 | `GET` | `/api/v2/delivery-man/review-list` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@review_list` |
| 32 | `PUT` | `/api/v2/delivery-man/save-review` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@is_saved` |
| 33 | `GET` | `/api/v2/delivery-man/collected_cash_history` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@collected_cash_history` |
| 34 | `GET` | `/api/v2/delivery-man/emergency-contact-list` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@emergency_contact_list` |
| 35 | `GET` | `/api/v2/delivery-man/notifications` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@get_all_notification` |
| 36 | `POST` | `/api/v2/delivery-man/resend-verification-code` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@resend_verification_code` |
| 37 | `POST` | `/api/v2/delivery-man/order-delivery-verification` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@order_delivery_verification` |
| 38 | `POST` | `/api/v2/delivery-man/interstate-driver-handover` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@interstate_driver_handover` |
| 39 | `POST` | `/api/v2/delivery-man/generate-paystack-link` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@generate_paystack_link` |
| 40 | `POST` | `/api/v2/delivery-man/remit-cash-paystack-init` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@remit_cash_paystack_init` |
| 41 | `GET` | `/api/v2/delivery-man/get-waybill-label` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@get_waybill_label` |
| 42 | `POST` | `/api/v2/delivery-man/change-status` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@change_status` |
| 43 | `POST` | `/api/v2/delivery-man/verify-order-delivery-otp` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController@verify_order_delivery_otp` |
| 44 | `POST` | `/api/v2/delivery-man/withdraw-request` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\WithdrawController@sendWithdrawRequest` |
| 45 | `GET` | `/api/v2/delivery-man/withdraw-list-by-approved` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\WithdrawController@getWithdrawListByApproved` |
| 46 | `GET` | `/api/v2/delivery-man/messages/list/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\ChatController@list` |
| 47 | `GET` | `/api/v2/delivery-man/messages/get-message/{type}/{id}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\ChatController@get_message` |
| 48 | `POST` | `/api/v2/delivery-man/messages/send-message/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\ChatController@send_message` |
| 49 | `GET` | `/api/v2/delivery-man/messages/search/{type}` | `unnamed` | `App\Http\Controllers\RestAPI\v2\delivery_man\ChatController@search` |

---

## 3. Disallowed & Gated Endpoints Summary (1534 Endpoints Blocked)

Attempting to access any of the 1534 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

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


