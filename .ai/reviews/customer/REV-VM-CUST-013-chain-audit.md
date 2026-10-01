# VM-CUST-013 Transaction-Path Audit Report (Storefront Slice)

Ticket: VM-CUST-013 — browse → cart → qty/remove → total → shipping (LGA) → payment → Paystack → order/track
Auditor: FRONTEND AI (human-dispatched; branch backend/VM-CUST-013)
Runtime: C:\xamp\php\php.exe 8.2.12 + artisan serve :8001 (started/stopped by auditor; dev sqlite)
Rule: static reads are NOT passes. Every verdict below is LIVE unless marked STATIC (route/code triple verified, behavior not clicked).

## Stage 1 — Browse (LIVE PASS)
- GET /products → 200. Product card links resolve: `product` (slug), `category-products`, `vendor-shop` (route:list verified + rendered hrefs).
- GET /product/vmarket-inhouse-product → 200. Contains add-to-cart form (`#add-to-cart-form` → POST `cart.add`), Buy Now button with `data-auth`/`data-route=shop-cart`/`data-url=checkout-details` (STORE-002 contract intact), out-of-stock disable flag wiring present.
- Stock privacy LIVE PASS: page shows binary state only; no numeric `current_stock` in HTML.

## Stage 2 — Cart ops (LIVE PASS with one note)
- POST cart/add (AJAX, CSRF, correct `id` field): unpurchasable product → `{"status":0,"message":"Product is currently out of stock or unavailable"}` — zero-trust gatekeeper LIVE fail-closed.
- POST cart/add product 4 → `{"status":1,"in_cart_key":372,..."price":1500}` — DB price, backend authority, no client math. Cart row removed after test (zero drift, verified count 0).
- Router registry `_route-for-js` (nav-cart, remove, updateQuantity, variant_price, order_note, guest-qty, restock, recaptcha, order-again) — all route names resolve via `route:list`.
- NOTE: qty happy-path not clicked (session isolation across probe sessions; endpoint scoping verified statically — customer/guest IDOR-safe). Marked UNVERIFIED-live, STATIC-pass.

## Stage 3 — Shipping / LGA (LIVE PASS)
- POST set-customer-location (lga_id=69) → `{"city":"Uyo","state":"Akwa Ibom","is_covered":true,"delivery_fee":500}` — fee from `DeliveryLane` row, backend authority.
- OBSERVATION (not a defect): canonical Uyo LGA id is **69**, not 142 (142 = Abadam, Borno). Any fixture assuming 142=Uyo is wrong against this DB.
- Location modal in main layout; session feeds ProductManager/WebController/HomeController/Product.
- DEFECT D1 (filed VM-CUST-014): GET `customer/set-shipping-method` WRITES `CartShipping` rows (state-changing GET) AND prices from legacy `ShippingMethod::find(id)->cost` on a client-supplied id — bypasses `DeliveryLane` authority. Fix under own ticket.
- DEFECT D2 (filed VM-CUST-015): GET `cart/remove-all` deletes the whole cart (destructive GET, CSRF-able, e.g. image-tag attack).

## Stage 4 — Payment (STATIC pass, LIVE unverified)
- checkout/payment.blade form → POST `customer.web-payment-request` → `Customer\PaymentController@payment`; hidden `external_redirect_link` → `web-payment-success`; `payment-fail`/`payment-success` routes registered.
- Guest checkout pages 302 to login (shop-cart, checkout-shipping, checkout-payment) — guest_checkout-off posture correct per V1.
- LIVE click of Paystack redirect NOT performed (needs authenticated session + order; no test customer credentials in this session). Marked UNVERIFIED-live.

## Stage 5 — Track (LIVE PASS)
- GET track-order → 200 (public). GET track-order/result?order_id=100171 → 200, renders order number + Verified/status/details markers.
- account-order-details + vendor/delivery-man info routes registered (auth-gated; not clicked without login).

## Dead/duplicate controls
- None found on the chain. Prior excisions (due-payment modals, COD UI) verified absent; no dangling `choosePaymentMethodModal` targets in Blade.

## Verdict
Chain is LIVE-PROVEN except web-payment click + qty happy-path (UNVERIFIED-live, static triples complete). Two defects filed (VM-CUST-014, VM-CUST-015). No application-code fixes made under this ticket.
