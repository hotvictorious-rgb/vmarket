# Audit Report: AUDIT-VM-CUST-012-storefront-deep-sweep

**Ticket ID:** VM-CUST-012  
**Title:** Storefront Button-by-Button Deep Checklist & Redundancy Audit  
**Reviewer:** REVIEWER AI / BACKEND AI  
**Execution Date:** 2026-09-29 13:55 UTC  
**Scope:** Whole Storefront (`theme_vmarket`), 104 Blade Files, 338 Interactive Controls  
**Runtime Endpoint Verified:** `http://127.0.0.1:8000` (Local Herd / PHP 8.4 Server)  
**Overall Verdict:** **FAIL (Defects Detected: 4 Redundancy/Collision Files + 3 Dead Route Files)**  
*(Per ticket instructions: All defects are documented here and logged to INBOX_COORDINATOR for dedicated repair tickets; no application code mutated under this audit ticket).*

---

## 1. Executive Summary

This audit performs an exhaustive button-by-button, link-by-link, and form-by-form inventory of the entire `theme_vmarket` storefront across all 104 template and partial files. 

Every interactive control was scanned for:
1. **HTML ID Uniqueness & jQuery Selector Integrity:** Detecting DOM collisions that break client-side event listeners.
2. **Backend Route & Controller Tracing:** Mapping every form submission, button trigger, and AJAX action to its registered Laravel route, controller method, and domain service.
3. **Dead Route Invocations:** Identifying templates calling unregistered or obsolete named routes.
4. **Live Server Page Reachability:** Verifying HTTP status codes and rendered payload integrity from the live `127.0.0.1:8000` runtime instance.

### Summary Statistics
- **Total Blade Files Audited:** 104 files
- **Total Registered Named Routes in System:** 904 routes
- **Total Interactive Controls Scanned:** 338 controls (Forms: 42, Buttons: 148, Action Links: 148)
- **ID Collision Files Found:** 4 files (Redundancy / Ambiguity Failures)
- **Dead Route Invocations Found:** 3 files (Legacy / Unregistered Routes)
- **Live Canonical Public Routes:** 100% reachable (Home, Sign-Up, Contacts, Categories, Brands, Track Order: all HTTP 200)

---

## 2. Redundancy & DOM Collision Failures (FAIL Conditions)

The following files contain duplicate HTML IDs. In modern browsers, `document.getElementById()` and jQuery `$('#id')` query selectors bind only to the first DOM occurrence, rendering subsequent controls broken or hijacked:

### Failure 1: `theme-views/checkout/shipping.blade.php`
- **Duplicate ID `customer_password` (2 occurrences):** Used simultaneously in account creation block and guest registration block.
- **Duplicate ID `customer_confirm_password` (2 occurrences):** Breaks password confirmation matching validation.
- **Duplicate ID `is_check_create_account` (2 occurrences):** Checkbox binding for optional account registration.
- **Duplicate ID `zip` (2 occurrences):** Delivery postal code input.
- **Duplicate ID `billing-zip` (2 occurrences):** Billing address postal code input.
- **Duplicate ID `contact_sellerModalLabel` (2 occurrences):** Modal header ID collision.
- **Impact:** Critical checkout defect — attempting to create an account during guest checkout binds to duplicate inputs and submits blank credentials.

### Failure 2: `theme-views/layouts/partials/modal/_login.blade.php`
- **Duplicate ID `customer-login-form` (6 occurrences):** Repeated across multiple auth modal variants and responsive viewports.
- **Duplicate ID `customerLoginBtn` (4 occurrences):** Form submit button defined multiple times. Click handlers attach multiple listeners, resulting in duplicate AJAX POST requests.
- **Duplicate ID `customerOtpLogin` (2 occurrences):** OTP tab switch button.
- **Impact:** High defect — causes multiple simultaneous login requests and race conditions on authentication tokens.

### Failure 3: `theme-views/layouts/partials/modal/_review.blade.php`
- **Duplicate ID `rating` (2 occurrences):** Star rating container.
- **Impact:** Moderate defect — user review star selection fails or applies to the wrong element.

### Failure 4: `theme-views/layouts/partials/_app-bar.blade.php`
- **Duplicate ID `clip0_8487_6242` (2 occurrences):** SVG clipping path ID duplicated within the mobile app bar navigation.
- **Impact:** Low visual defect — potential clipping distortion on Safari/WebKit.

---

## 3. Dead & Unresolved Route Invocations (FAIL Conditions)

The following Blade files invoke `route(...)` helpers with names that do NOT exist in Laravel's route collection (`routes/web/routes.php` or `routes/rest_api/`):

| File | Unregistered Route Called | Problem Description |
| :--- | :--- | :--- |
| `theme-views/order/partials/_choose-payment-method-modal.blade.php` | `route('customer.customer-order-edit-pay-amount')` | Route was removed or never registered in web namespace; clicking triggers `RouteNotFoundException` (500). |
| `theme-views/order/partials/_choose-payment-method-order-details.blade.php` | `route('customer.customer-order-edit-pay-amount')` | Same missing payment edit route called from order details partial. |
| `theme-views/payment/marcedo-pogo.blade.php` | `route('mercadopago.make_payment')` | Obsolete third-party gateway route (`MercadoPago`) not registered in V1 stack. |
| `theme-views/blogs/` (all partials) | `route('blogs')`, `route('blog-details')` | Storefront blog templates exist in theme, but no blog routes are declared in `routes/web/routes.php`. |

---

## 4. Live Server URL Reachability (http://127.0.0.1:8000)

| Page / Feature | URL | HTTP Status | Response Payload | Status |
| :--- | :--- | :---: | :---: | :---: |
| **Home Storefront** | `http://127.0.0.1:8000/` | **200 OK** | 219,307 bytes | **PASS** |
| **Customer Sign-Up** | `http://127.0.0.1:8000/customer/auth/sign-up` | **200 OK** | 118,909 bytes | **PASS** |
| **Customer Login** | `http://127.0.0.1:8000/customer/auth/login` | **302 Found** | Redirects to home/modal | **PASS** |
| **Customer Recover Password**| `http://127.0.0.1:8000/customer/auth/recover-password`| **200 OK** | 115,564 bytes | **PASS** |
| **Vendor Registration** | `http://127.0.0.1:8000/vendor/auth/registration/index` | **200 OK** | 138,299 bytes | **PASS** |
| **Contacts Page** | `http://127.0.0.1:8000/contacts` | **200 OK** | 116,726 bytes | **PASS** |
| **Categories Directory** | `http://127.0.0.1:8000/categories` | **200 OK** | 120,609 bytes | **PASS** |
| **Brands Directory** | `http://127.0.0.1:8000/brands` | **200 OK** | 129,536 bytes | **PASS** |
| **Track Order Page** | `http://127.0.0.1:8000/track-order` | **200 OK** | 114,210 bytes | **PASS** |
| **Cart Details Page** | `http://127.0.0.1:8000/shop-cart` | **302 Found** | Redirects to guest/login | **PASS** |
| **Checkout Details Page** | `http://127.0.0.1:8000/checkout-details` | **302 Found** | Redirects on empty cart | **PASS** |

---

## 5. Domain Control Mapping & Traceability

Below is the representative tracing matrix for core storefront interaction groups:

| Control Domain | Element & ID | Client Trigger | Verb & URL | Backend Controller & Method | Domain Service / Model | Verdict |
| :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| **Header Nav** | `trackOrderBtn` | Click Link | `GET /track-order` | `WebController@trackOrder` | `OrderManager::track_order()` | **PASS** |
| **Header Cart** | `cartToggleBtn` | Click Cart | `GET /shop-cart` | `CartController@cartList` | `CartManager::get_cart()` | **PASS** |
| **Search Bar** | `form#searchForm` | Form Submit | `GET /products` | `WebController@all_products` | `Product::marketplaceEligible()` | **PASS** |
| **Product Detail** | `btn.add-to-cart` | Click Button | `POST /cart/add` | `CartController@addToCart` | `CartManager::add_to_cart()` | **PASS** |
| **Product Detail** | `btn.buy-now` | Click Button | `POST /cart/add` | `CartController@addToCart` | `CartManager::add_to_cart()` | **PASS** |
| **Cart Table** | `btn.qty-increment` | Click Button | `POST /cart/updateQuantity`| `CartController@updateQuantity` | `CartManager::update_cart_qty()`| **PASS** |
| **Cart Table** | `btn.cart-remove` | Click Link | `POST /cart/remove` | `CartController@removeFromCart` | `CartManager::cart_clean()` | **PASS** |
| **Checkout** | `form#shippingForm`| Form Submit | `POST /customer/choose-shipping-address` | `CheckoutController@choose_shipping_address` | `OrderManager::choose_shipping_address()` | **FAIL** (Collisions) |
| **Auth Modal** | `btn#customerLoginBtn`| Form Submit| `POST /customer/auth/login` | `LoginController@submit` | `CustomerAuthService` | **FAIL** (Collisions) |
| **Vendor Reg** | `form#vendorRegForm`| Form Submit| `POST /vendor/auth/registration/index` | `RegisterController@store` | `VendorService` | **PASS** |

---

## 6. Required Defect Distribution (To INBOX_COORDINATOR)

As required by ticket rules, application code is untouched in this ticket. The following defects must be scheduled for immediate resolution:

1. **`DEF-STORE-001` (Critical):** Deduplicate HTML IDs in `theme-views/checkout/shipping.blade.php` (`zip`, `billing-zip`, `customer_password`, `customer_confirm_password`, `is_check_create_account`).
2. **`DEF-STORE-002` (High):** Deduplicate form and button IDs in `theme-views/layouts/partials/modal/_login.blade.php` (`customer-login-form`, `customerLoginBtn`, `customerOtpLogin`).
3. **`DEF-STORE-003` (High):** Remove or re-point dead route `route('customer.customer-order-edit-pay-amount')` in `_choose-payment-method-modal.blade.php` and `_choose-payment-method-order-details.blade.php`.
4. **`DEF-STORE-004` (Medium):** Decommission orphan MercadoPago view `theme-views/payment/marcedo-pogo.blade.php` containing dead route `mercadopago.make_payment`.
5. **`DEF-STORE-005` (Medium):** Decommission or route-bind orphaned blog templates in `theme-views/blogs/`.
6. **`DEF-STORE-006` (Low):** Fix duplicate `rating` ID in `theme-views/layouts/partials/modal/_review.blade.php`.
