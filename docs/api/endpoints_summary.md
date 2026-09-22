# 🔌 Vmarket Endpoints Summary (Authoritative SSOT)

## REST API Version 1 (`/api/v1/`)

### Canonical Geography & Routing
- `GET /api/v1/geography/countries` — Active countries
- `GET /api/v1/geography/states/{country_id}` — Active states
- `GET /api/v1/geography/lgas/{state_id}` — Active LGAs (Local Government Areas)

### Customer Address Book (IDOR-Scoped)
- `GET /api/v1/customer/address/list` — List authenticated customer addresses
- `POST /api/v1/customer/address/add` — Create address with validated `country_id`, `state_id`, `lga_id`
- `POST /api/v1/customer/address/update` — Update customer address
- `DELETE /api/v1/customer/address/` — Delete customer address

### Fulfillment Availability Engine
- `POST /api/v1/fulfillment/availability` — Check delivery lane & in-shop pickup availability
- `POST /api/v1/fulfillment/delivery-fee` — Query authoritative directional delivery lane fee

### Two-Phase Delivery Checkout & Paystack Payment
- `POST /api/v1/checkout/intent` — Phase 1: Create or replay frozen CheckoutIntent
- `POST /api/v1/checkout/intent/{orderGroupId}/pay` — Phase 2: Initialize Paystack gateway attempt (returns `authorization_url`)

### In-Shop Pickup Reservations Channel
- `POST /api/v1/customer/pickup-reservations` — Reserve items for 24hr shop inspection (₦0.00 upfront)
- `GET /api/v1/customer/pickup-reservations` — List customer reservations
- `GET /api/v1/customer/pickup-reservations/{code}` — Get reservation details and inspection state
- `POST /api/v1/customer/pickup-reservations/{code}/pay` — Initialize Paystack payment for accepted reservation

### Victorious Cashback 5% Reward Ledger
- `GET /api/v1/cashback/summary` — Cashback balance, lifetime earned, pending settlement
- `GET /api/v1/cashback/list` — Detailed credit/debit/redemption transaction ledger

### Customer Core Endpoints
- `POST /api/v1/auth/login` — Login with email/phone & password
- `POST /api/v1/auth/register` — Register customer
- `GET /api/v1/auth/logout` — Invalidate session token
- `GET /api/v1/products/latest` — Browse catalog
- `GET /api/v1/categories` — Browse categories
- `POST /api/v1/cart/add` — Add item to cart
- `GET /api/v1/cart` — Retrieve cart items
- `GET /api/v1/customer/order/cancel-order` — Request cancellation within allowed pending window

### Vendor Endpoints
- `GET /api/v3/seller/banks` — Nigerian banks list (Paystack NUBAN)
- `POST /api/v3/seller/resolve-account` — Resolve NUBAN account name
- `POST /api/v3/seller/bank-info/send-otp` — Bank change OTP verification
- `PUT /api/v3/seller/seller-update` — Update bank details (with 48-hr withdrawal cooldown)
- `POST /api/v3/seller/kyc/submit` — Submit NIN / CAC verification
- `POST /api/v3/seller/balance-withdraw` — Request balance payout
- `POST /api/v3/seller/pickup-reservations/verify` — Verify customer pickup reservation code
- `POST /api/v3/seller/pickup-reservations/accept` — Accept store inspection condition
- `POST /api/v3/seller/pickup-reservations/reject` — Reject store inspection (releases stock)

### Delivery Man Endpoints
- `POST /api/v1/delivery-man/order/verify-pickup-otp` — Vendor pickup OTP (rider collects from vendor)
- `POST /api/v2/delivery-man/order/verify-order-delivery-otp` — Customer delivery OTP (completes doorstep delivery)

---

## Decommissioned & Legacy Compatibility Endpoints

| Endpoint | Status | Replacement / Migration Path |
|---|---|---|
| `POST /api/v1/digital-payment` | **LEGACY SHIM** | Callers migrate to `POST /api/v1/checkout/intent` |
| `/api/v1/shipping-method/*` | **DEPRECATED** | Use `POST /api/v1/fulfillment/availability` & `POST /api/v1/fulfillment/delivery-fee` |
| `POST /api/v1/customer/order/place` | **REMOVED** | Offline COD removed. Use two-phase Checkout Intent + Paystack. |
| `/api/v1/customer/wallet/*` | **DEPRECATED** | Replaced by Victorious Cashback (`/api/v1/cashback/*`). |
| `/api/v1/delivery-hubs/*` (as geography) | **REMOVED** | Hubs are internal logistics only. Geography uses `/api/v1/geography/*`. |
| `/api/v1/coupon/*` | **DEPRECATED** | Decommissioned in V1 in favor of Victorious Cashback. |
