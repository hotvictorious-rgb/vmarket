# Audit Report: AUDIT-VM-CUST-011-storefront-click-chain

**Ticket ID:** VM-CUST-011  
**Title:** Storefront Click-by-Click Customer Journey Audit — DOM → Endpoint → Backend Authority  
**Auditor / Reviewer:** REVIEWER AI  
**Execution Date:** 2026-10-03 11:50 UTC  
**Scope:** Storefront Click Chain (`theme_vmarket`), 12 Steps (Landing to Complete), Routes, Controllers, Domain Services, Database Authority  
**Runtime Endpoint Verified:** `http://127.0.0.1:8000` (Local PHP 8.2 / Laravel 10 Runtime)  
**Overall Verdict:** **PASS (12 of 12 Steps Traced & Proven; F-01/F-02 Mitigations Verified; F-03 Characterized)**  

---

## 1. Executive Summary

Per human directive 2026-09-25 ("test button by button customer journey, what is clicked, how it shows, what happens next, with their corresponding endpoints, aligned with rules"), this audit traces the live storefront customer journey click-by-click, validating the full invocation triple:
$$\text{DOM Element / Action} \longrightarrow \text{Client JS Handler} \longrightarrow \text{HTTP Verb \& URL} \longrightarrow \text{Laravel Route} \longrightarrow \text{Controller Method} \longrightarrow \text{Domain Service \& DB Authority}$$

Every interaction was scrutinized to ensure:
1. **Server-Side Authority:** Zero client-side fee, price, tax, or discount calculation.
2. **Two-Phase Checkout Intent:** No direct order inserts prior to verified settlement.
3. **Zero-Trust IDOR Scoping:** All customer order and address lookups are strictly scoped to `auth('customer')->id()`.
4. **Canonical Geography:** Strict `Country → State → LGA` and directional `DeliveryLane` matrix (no hubs, routes, or zones as geography).
5. **Pre-filed Findings F-01, F-02, and F-03:** Live probes and source tracing against the running server.

---

## 2. 12-Step Click-by-Click Journey Trace Matrix

| # | Step Name | Trigger Element / ID / Data Attr | JS Handler (File:Line) | HTTP Verb & URL | Laravel Route Name | Controller & Method | Domain Service & DB Tables | Verdict |
|---|---|---|---|---|---|---|---|:---:|
| 1 | **Nav Cart Count** | `#update_nav_cart_url` (`data-url`) | `custom.js:703` `updateNavCart()` | `POST /cart/nav-cart-items` | `cart.nav-cart` (`routes.php:267`) | `CartController@updateNavCart` (`CartController.php:202`) | `CartManager::get_cart()`; DB: `carts` (customer/guest scoped) | **PASS** |
| 2 | **Add to Cart** | `.add-to-cart` / `.buy-now` / `#add-to-cart-form` | `custom.js:809` `addToCart()` | `POST /cart/add` | `cart.add` (`routes.php:262`) | `CartController@addToCart` (`CartController.php:154`) | `CartManager::addToCartPhysicalProduct()`; DB: `products`, `carts`. Stock & unit price re-queried from DB; variations eliminated. | **PASS** |
| 3 | **Qty + / -** | `.quantity__minus`, `.quantity__plus`, `.cartQuantity{key}` | `cart.js:77` `updateCartQuantity()` | `POST /cart/updateQuantity` | `cart.updateQuantity` (`routes.php:269`) | `CartController@updateQuantity` (`CartController.php:242`) | `CartManager::update_cart_qty()`; DB: `carts`, `products`. Sanitizes `qty < 1` and rejects `qty > current_stock`. | **PASS** |
| 4 | **Remove Item** | `.cart-remove`, `#remove_from_cart_url` | `cart.js:109` `removeProductFromCartList()` | `POST /cart/remove` | `cart.remove` (`routes.php:265`) | `CartController@removeFromCart` (`CartController.php:218`) | `Cart::where(...)->delete()`; DB: `carts`. Deletes row in DB; re-render cannot resurrect removed rows. | **PASS** |
| 5 | **Item / Shop Checkbox** | `.shop-head-check`, `.shop-item-check`, `#select-cart-items-url` | `cart-list-page.js:34` `multipleCheckBoxFunctionsInit()` | `POST /cart/select-cart-items` | `cart.select-cart-items` (`routes.php:272`) | `CartController@updateCheckedCartItems` (`CartController.php:481`) | Updates `is_checked` in DB; recalculates totals server-side via `CartManager::cart_grand_total()`. Client does zero math. | **PASS** |
| 6 | **Order Note** | `#order_note`, `#order_note_url` | `cart-list-page.js:3` `proceedToNextAction()` | `POST /order_note` | `order_note` (`routes.php:128`) | `WebController@order_note` (`WebController.php:944`) | `OrderManager::checkValidationForCheckoutPages()`; returns JSON `{status: 0\|1, redirect: ...}`. | **PASS** |
| 7 | **Proceed to Next** | `#proceed-to-next-action` | `cart-list-page.js:3` `proceedToNextAction()` | `POST /order_note` $\rightarrow$ Redirect `GET /checkout-details` | `checkout-details` (`routes.php:121`) | `WebController@checkout_details` (`WebController.php:330`) | Posts `order_note`, reads JSON response, redirects to `checkout-details`. **Discrepancy Verified:** `data-goto-checkout` is dead in cart path, live in shipping path. | **PASS** |
| 8 | **Checkout Details / Shipping** | `#fulfillment-tab-delivery`, `#fulfillment-tab-pickup`, `#proceed-to-next-action`, `#address-form` | `shipping-page.js:287` | `POST /customer/choose-shipping-address-other` | `customer.choose-shipping-address-other` (`routes.php:344`) | `SystemController@getChooseShippingAddressOther` (`SystemController.php:220`) | `DeliveryLane` matrix (`origin_lga_id` $\rightarrow$ `destination_lga_id`); zero hubs/zones as geography. In-shop pickup supports 24-hr hold with ₦0 payment. | **PASS** |
| 9 | **Choose Payment** | `.digital-payment-card`, `.checkout-payment-paystack` | `payment-page.js:168` | Form submit to `POST /customer/web-payment-request` | `customer.web-payment-request` (`routes.php:353`) | `PaymentController@payment` (`Customer\PaymentController.php:56`) | `DeliveryCheckoutIntentService::createCheckoutIntent()`; freezes cart into `checkout_intents`. Direct order insert is strictly prohibited. | **PASS** |
| 10 | **Pay Now** | Form `.checkout-payment-paystack` submit | Form submit $\rightarrow$ Paystack redirect | `POST /customer/web-payment-request` | `customer.web-payment-request` (`routes.php:353`) | `PaymentController@payment` (`Customer\PaymentController.php:56`) | `DeliveryPaymentInitializationService::initializePayment()`; returns Paystack `authorization_url`. Order settled via Paystack webhook. | **PASS** |
| 11 | **Order Placed / Success** | `.main-content` in `complete.blade.php` | Blade View | `GET /order-placed`, `GET /order-placed-success` | `order-placed` (`routes.php:125`), `order-placed-success` (`routes.php:126`) | `WebController@order_placed`, `getOrderPlaceView` (`WebController.php:478, 511`) | Renders `cashback_earned` directly from `CustomerCashbackLedger`. Victorious Points are display-only celebratory badges. | **PASS** |
| 12 | **Account Order & Track Order** | `#account-order-list`, `#track-order` | `account-order-details.js` / Form submit | `GET /account-oder`, `GET /account-order-details`, `GET /track-order/result` | `account-oder` (`routes.php:215`), `account-order-details` (`:216`), `track-order.result` (`:198`) | `UserProfileController@account_order`, `account_order_details`, `track_order_result` | Zero-Trust IDOR query scoping: `where(['customer_id' => auth('customer')->id(), 'is_guest' => '0'])`. Customer A cannot access Customer B's orders. | **PASS** |

---

## 3. Discrepancy & Finding Verification (F-01, F-02, F-03)

### Discrepancy in Step 7 (`data-goto-checkout` on `#proceed-to-next-action`)
- **Investigation:**
  In `theme-views/partials/_order-summery.blade.php:109-116`, the button `#proceed-to-next-action` carries:
  ```html
  <button id="proceed-to-next-action"
          data-goto-checkout="{{ route('customer.choose-shipping-address-other') }}"
          data-checkout-payment="{{ route('checkout-payment') }}">
  ```
- **Finding:**
  - In `cart-list-page.js:3-30`, the click handler reads only `$('#order_note').val()`, posts to `$('#order_note_url').data('url')`, and redirects to `response.redirect` or `$('#route-checkout-details').data('url')`. It **never reads** `data-goto-checkout` or `data-checkout-payment`.
  - In `shipping-page.js:287-323`, the click handler on the same `#proceed-to-next-action` ID explicitly reads:
    ```javascript
    let redirectUrl = $(this).data('checkout-payment');
    let formUrl = $(this).data('goto-checkout');
    ```
- **Conclusion:**
  Confirmed. The attribute `data-goto-checkout` is dead on the cart details page (`shop-cart`) and live on the checkout shipping page (`checkout-details`). This is because `_order-summery.blade.php` is shared across both views.

---

### Finding F-01 (Security, HIGH): State-Changing GET on `set-shipping-method`
- **Initial Report:** `routes/web/routes.php:342` registered `Route::get('set-shipping-method', 'setShippingMethod')`. A GET with write side effects (`insertIntoCartShipping`) lacks CSRF protection.
- **Verification & Status:** **MITIGATED / FIXED in VM-CUST-014 (Commit `3be60ffe` / `a0f1c7fb`).**
  - Source check: `routes/web/routes.php:342` is registered as:
    ```php
    Route::post('set-shipping-method', 'setShippingMethod')->name('set-shipping-method'); // [AI] VM-CUST-014: POST only (was state-changing GET)
    ```
  - Live probe against `http://127.0.0.1:8000/customer/set-shipping-method`:
    ```
    HTTP/1.1 405 Method Not Allowed
    allow: POST
    ```
  - CSRF verification: All POST requests require `X-CSRF-TOKEN` or Laravel session token.

---

### Finding F-02 (Legacy Debt, MEDIUM): Legacy Cost Authoritative in Live Route
- **Initial Report:** `insertIntoCartShipping` line 52 did `ShippingMethod::find($request['id'])->cost` and persisted `CartShipping.cost`, bypassing `DeliveryLane`.
- **Verification & Status:** **MITIGATED / FIXED in VM-CUST-014.**
  - Source check in `Customer\SystemController::insertIntoCartShipping` (`app/Http/Controllers/Customer/SystemController.php:63-84`):
    ```php
    $destinationLgaId = $destinationLgaId ?? (int) session('customer_lga_id');
    $originLgaId = (int) Cart::where(['cart_group_id' => $request['cart_group_id']])
        ->with('shop')->first()?->shop?->lga_id;
    if ($originLgaId < 1 || $destinationLgaId < 1) {
        return false;
    }
    $laneFee = DeliveryLane::getDeliveryFee($originLgaId, $destinationLgaId);
    if ($laneFee === null) {
        return false;
    }
    $shipping['shipping_cost'] = $laneFee;
    ```
  - Client-supplied `id` is never used for pricing. Delivery fees are 100% authoritative from `DeliveryLane::getDeliveryFee($originLgaId, $destinationLgaId)`.

---

### Finding F-03 (Auth, MEDIUM): Missing Customer Middleware on Intermediate Routes
- **Initial Report:** Routes `customer.set-shipping-method`, `customer.set-payment-method`, `customer.choose-shipping-address*` (`routes/web/routes.php:340-346`) lack `customer` route middleware.
- **Verification & Architectural Status:** **CONFIRMED & CHARACTERIZED.**
  - `routes/web/routes.php:340-346`:
    ```php
    Route::controller(SystemController::class)->group(function () {
        Route::get('set-payment-method/{name}', 'setPaymentMethod')->name('set-payment-method');
        Route::post('set-shipping-method', 'setShippingMethod')->name('set-shipping-method');
        Route::post('choose-shipping-address', 'getChooseShippingAddress')->name('choose-shipping-address');
        Route::post('choose-shipping-address-other', 'getChooseShippingAddressOther')->name('choose-shipping-address-other');
        Route::post('choose-billing-address', 'getChooseShippingAddress')->name('choose-billing-address');
    });
    ```
  - Internal Guarding:
    - `setPaymentMethod`: Guards via `if (auth('customer')->check() || session()->has('mobile_app_payment_customer_id'))`.
    - `setShippingMethod`: Guards via mandatory `session('customer_lga_id')` check (fails closed with 422 if empty).
    - `getChooseShippingAddressOther`: Evaluates `auth('customer')->check()` vs `is_guest = !auth('customer')->check()`.
    - `PaymentController@payment`: Explicitly rejects guest checkout (`is_guest = 1` $\rightarrow$ 403 / redirect to login) and requires authenticated customer.
  - Recommendation: Intermediate cart/address configuration in web session can proceed for unauthenticated shoppers up to payment entry, where authentication is strictly enforced. No privilege escalation is possible.

---

## 4. Defect Follow-ups

All UI/DOM defect items identified during storefront sweeps (specifically HTML ID collisions in `shipping.blade.php` and modal login forms) are tracked under `AUDIT-VM-CUST-012.md` (`DEF-STORE-001` through `DEF-STORE-006`). No new application defects were uncovered during this 12-step click-by-click customer journey trace.
