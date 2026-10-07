# Victorious MARKET — Authoritative API Contract Registry

> **AUTHORITATIVE LIVING CONTRACT DICTIONARY FOR ALL CLIENT PLATFORMS.**  
> **Backend is the Single Source of Truth (SSOT).**  
> All client applications (Customer App, Storefront, Vendor Web/App, Delivery Rider App, Admin Command Center) must consume these exact endpoints and response schemas. Client applications must NEVER implement client-side fee calculations, invent unregistered JSON keys, or bypass backend state machines.

---

## Table of Contents
1. [Canonical Marketplace Geography (SSOT)](#1-canonical-marketplace-geography-ssot)
2. [Fulfillment Availability & Fee Engine (SSOT)](#2-fulfillment-availability--fee-engine-ssot)
3. [Two-Phase Delivery Checkout & Paystack Settlement](#3-two-phase-delivery-checkout--paystack-settlement)
4. [In-Shop Pickup Protocol (Two-Code Physical Inspection & Handover)](#4-in-shop-pickup-protocol-two-code-physical-inspection--handover)
5. [Delivery Rider Logistics & Proof of Delivery (V2 API)](#5-delivery-rider-logistics--proof-of-delivery-v2-api)
6. [Omnichannel Vendor Operations & Relisting (V3 API)](#6-omnichannel-vendor-operations--relisting-v3-api)
7. [Victorious Points & Customer Cashback Ledger](#7-victorious-points--customer-cashback-ledger)
8. [Customer Core Services & Catalog (V1 API)](#8-customer-core-services--catalog-v1-api)
9. [Admin Governance, Lanes & Dispatch Portal](#9-admin-governance-lanes--dispatch-portal)
10. [Authoritative Exclusion & Decommission Catalog](#10-authoritative-exclusion--decommission-catalog)

---

## 1. Canonical Marketplace Geography (SSOT)

Marketplace geography is strictly structured as: **Country (`countries`) → State (`states`) → LGA (`lgas`)**.  
All shipping calculations, merchant origin matching, and customer addresses bind to canonical LGA entities.

### `GET /api/v1/geography/countries`
- **Purpose**: List all active marketplace countries.
- **Auth**: Public / `apiGuestCheck` (`?guest_id=...` or `Authorization: Bearer <token>`)
- **Response `200 OK`**:
  ```json
  {
    "status": true,
    "message": "Countries retrieved successfully.",
    "data": [
      {
        "id": 1,
        "name": "Nigeria",
        "iso_code": "NG",
        "phone_code": "+234",
        "currency_code": "NGN"
      }
    ]
  }
  ```

### `GET /api/v1/geography/states/{country_id}`
- **Purpose**: Retrieve active states for a specific country (e.g. `country_id = 1` for Nigeria).
- **Auth**: Public / `apiGuestCheck`
- **Response `200 OK`**:
  ```json
  {
    "status": true,
    "message": "States retrieved successfully.",
    "data": [
      {
        "id": 3,
        "country_id": 1,
        "name": "Akwa Ibom",
        "state_code": "AK"
      }
    ]
  }
  ```

### `GET /api/v1/geography/lgas/{state_id}`
- **Purpose**: Retrieve canonical Local Government Areas (LGAs) for a state (e.g. `state_id = 3` for Akwa Ibom).
- **Auth**: Public / `apiGuestCheck`
- **Response `200 OK`**:
  ```json
  {
    "status": true,
    "message": "LGAs retrieved successfully.",
    "data": [
      {
        "id": 48,
        "state_id": 3,
        "name": "Uyo",
        "code": "UYO"
      },
      {
        "id": 49,
        "state_id": 3,
        "name": "Eket",
        "code": "EKT"
      }
    ]
  }
  ```

### `POST /api/v1/geography/calculate-lane-fee`
- **Purpose**: Calculate authoritative shipping fee, zonal tier, lead time, and cargo surcharge between any origin LGA and destination LGA across Nigeria. Supports bidirectional overrides and 3-tier zonal distance fallbacks.
- **Auth**: Public / `apiGuestCheck`
- **Request Body**:
  ```json
  {
    "origin_lga_id": 48,           // Required: integer (e.g. Uyo)
    "destination_lga_id": 49,      // Required: integer (e.g. Eket)
    "package_tier": "small"        // Optional: "small" (standard) or "large" (bulky cargo)
  }
  ```
- **Response `200 OK`**:
  ```json
  {
    "status": true,
    "message": "Delivery lane fee calculated successfully.",
    "data": {
      "origin_lga_id": 48,
      "destination_lga_id": 49,
      "fee": 2500.0,
      "estimated_days": "24 Hours",
      "is_enabled": true,
      "lane_id": 14,
      "lane_type": "intra_state",
      "zone_tier": 2,
      "is_custom_override": false
    }
  }
  ```

---

## 2. Fulfillment Availability & Fee Engine (SSOT)

Replaces legacy flat shipping methods. The backend dynamically evaluates origin merchant LGA and destination address LGA against directional `DeliveryLane` records, 3-tier zonal rates (Tier 1 municipal, Tier 2 regional, Tier 3 interstate), package cargo tier (`small` vs `large`), and merchant pickup configurations.

### `POST /api/v1/fulfillment/availability`
- **Purpose**: Authoritative check for delivery and in-shop pickup feasibility. If `shop_id` is omitted, the backend automatically resolves the vendor shop from `cart_items`, `product_id`, or active session cart.
- **Auth**: `apiGuestCheck`
- **Request Body**:
  ```json
  {
    "shop_id": 1,                  // Optional: resolved from items if omitted
    "shipping_address_id": 12,     // Optional: evaluates lane feasibility
    "cart_items": [                // Optional
      {
        "product_id": 105,
        "quantity": 2
      }
    ]
  }
  ```
- **Response `200 OK`**:
  ```json
  {
    "success": true,
    "data": {
      "shop": {
        "id": 1,
        "name": "Uyo Central Electronics",
        "lga": "Uyo",
        "state": "Akwa Ibom"
      },
      "address": {
        "id": 12,
        "lga": "Eket",
        "state": "Akwa Ibom"
      },
      "fulfillment_options": {
        "delivery": {
          "available": true,
          "fee": 2500.0,
          "base_delivery_fee": 2500.0,
          "bulky_surcharge_amount": 0.0,
          "package_tier": "small",
          "zone_tier": 2,
          "zone_tier_label": "Zone Tier 2 (Intra-State)",
          "estimated_time": "24 Hours",
          "origin_lga": {
            "id": 48,
            "name": "Uyo",
            "state": "Akwa Ibom"
          },
          "destination_lga": {
            "id": 49,
            "name": "Eket",
            "state": "Akwa Ibom"
          }
        },
        "pickup": {
          "available": true,
          "pickup_type": "in_shop_inspection",
          "cost": "0.00",
          "estimated_ready_time": "Immediate / 24 hours",
          "available_times": []
        }
      }
    }
  }
  ```

### `POST /api/v1/fulfillment/delivery-fee`
- **Purpose**: Calculate authoritative shipping fee for a specific shop/product and address with package tier awareness.
- **Auth**: `apiGuestCheck`
- **Request Body**:
  ```json
  {
    "shop_id": 1,                  // Or "product_id": 105
    "shipping_address_id": 12,
    "package_tier": "small"        // Optional: "small" or "large"
  }
  ```
- **Response `200 OK`**:
  ```json
  {
    "success": true,
    "data": {
      "fee": "2500.00",
      "bulky_surcharge_amount": "0.00",
      "package_tier": "small",
      "zone_tier": 2,
      "available": true,
      "origin_lga": "Uyo",
      "destination_lga": "Eket",
      "estimated_days": "24 Hours"
    }
  }
  ```

---

## 3. Two-Phase Delivery Checkout & Paystack Settlement

Fulfills delivery orders with two-phase commit:
- **Phase 1**: Creates a frozen `CheckoutIntent` with an immutable price snapshot, stock allocation, and idempotency key.
- **Phase 2**: Initializes official Paystack checkout with atomic row-level locking.

### Phase 1: `POST /api/v1/checkout/intent`
- **Purpose**: Create or replay a frozen checkout snapshot server-side.
- **Auth**: `auth:api` (Authenticated customers only).
- **Request Body**:
  ```json
  {
    "address_id": 12,
    "idempotency_key": "chk_uuid_v4_839218392183", // 8-64 alphanumeric chars
    "billing_address_id": 12,                        // Optional
    "use_cashback": true,                           // Optional: redeem Victorious Points
    "cart_item_ids": [14, 15]                       // Optional: defaults to all checked cart items
  }
  ```
- **Response `200 OK`**:
  ```json
  {
    "intent_id": 42,
    "order_group_id": "ORD-GRP-89231849",
    "total_amount": "26500.00",
    "currency": "NGN",
    "status": "pending",
    "expires_at": "2026-09-24T18:00:00Z",
    "fingerprint": "sha256_hash_of_items_and_prices"
  }
  ```

### Phase 2: `POST /api/v1/checkout/intent/{orderGroupId}/pay`
- **Purpose**: Initialize official Paystack transaction for the frozen intent.
- **Auth**: `auth:api`
- **Response `200 OK`**:
  ```json
  {
    "payment_request_id": "9d90184b-0192-411a-8219-...",
    "authorization_url": "https://checkout.paystack.com/00abcdef...",
    "reference": "PAY-VM-8392019482",
    "amount": "26500.00",
    "currency": "NGN",
    "callback_url": "http://shop.victoriousmarket.com.ng/payment/paystack/callback"
  }
  ```

### Backward Compatibility Shim: `POST /api/v1/digital-payment`
- **Purpose**: Backward-compatible bridge consumed by mobile apps. Internally delegates to `DeliveryCheckoutIntentService` and `DeliveryPaymentInitializationService`.
- **Auth**: `auth:api`
- **Request Body**:
  ```json
  {
    "payment_method": "paystack",
    "payment_platform": "app",
    "payment_request_from": "app",
    "address_id": 12,
    "is_guest": 0
  }
  ```
- **Response `200 OK`**:
  ```json
  {
    "redirect_link": "https://checkout.paystack.com/00abcdef..."
  }
  ```

### Status Polling / Crash Recovery: `GET /api/v1/checkout/intent/{orderGroupId}/status`
- **Purpose**: Polled by Customer Mobile App ("Check Again" button) to recover payment state after returning from external Paystack payment without scanning full order history.
- **Auth**: `auth:api` (Scoped to authenticated customer).
- **Response `200 OK`**:
  ```json
  {
    "order_group_id": "ORD-GRP-89231849",
    "intent_status": "pending", // "pending" | "converted_to_orders" | "canceled" | "expired"
    "payment_status": "unpaid",          // "unpaid" | "pending" | "paid" | "reconciliation_required" | "expired" | "failed" | "superseded"
    "authorization_url": "https://checkout.paystack.com/00abcdef...",
    "total_amount": "26500.00",
    "currency": "NGN",
    "orders": [
      {
        "id": 100234,
        "order_status": "confirmed",
        "payment_status": "paid",
        "order_amount": 26500.00,
        "created_at": "2026-09-24T18:05:00Z"
      }
    ]
  }
  ```

---

## 4. In-Shop Pickup Protocol (Two-Code Physical Inspection & Handover)

Governs the zero-risk physical store inspection flow:  
`Reservation ≠ Sale`. Customer pays ₦0 online, visits the store with **Code #1**, inspects physical goods, pays on counter, and receives **Code #2** (6-digit handover OTP) to collect the items.

### Customer Code #1: `POST /api/v1/pickup-reservations`
- **Purpose**: Issue a 24-hour physical store reservation pass.
- **Auth**: `auth:api`
- **Request Body**:
  ```json
  {
    "shop_id": 1,
    "cart_items": [
      { "product_id": 105, "quantity": 1 }
    ]
  }
  ```
- **Response `201 Created`**:
  ```json
  {
    "status": true,
    "message": "Pickup reservation created successfully.",
    "data": {
      "reservation_code": "RES-17B81659",
      "amount_to_pay_at_store": "20000.00",
      "status": "pending_inspection",
      "expires_at": "2026-09-25T14:30:00Z",
      "shop": {
        "name": "Uyo Central Electronics",
        "address": "12 Ikot Ekpene Road, Uyo",
        "phone": "+2348012345678"
      }
    }
  }
  ```

### Customer Code #1 List: `GET /api/v1/pickup-reservations`
- **Purpose**: List all reservations created by the authenticated customer.
- **Auth**: `auth:api`

### Customer Code #1 Status Query: `GET /api/v1/pickup-reservations/{code}`
- **Purpose**: Check live inspection status. When `status == "inspected_accepted"`, the app unlocks the counter payment button.
- **Auth**: `auth:api`
- **Response `200 OK`**:
  ```json
  {
    "status": true,
    "data": {
      "reservation_code": "RES-17B81659",
      "status": "inspected_accepted",
      "can_pay": true,
      "amount": "20000.00",
      "expires_at": "2026-09-25T14:30:00Z"
    }
  }
  ```

### Customer Counter Payment: `POST /api/v1/pickup-reservations/{code}/pay`
- **Purpose**: Initialize on-site digital Paystack payment after customer approves the physical goods.
- **Auth**: `auth:api`
- **Response `200 OK`**:
  ```json
  {
    "status": true,
    "authorization_url": "https://checkout.paystack.com/pickup_counter_...",
    "reference": "RES-PAY-98319842"
  }
  ```

### Merchant Inspection Handshake (Vendor App & Web):
- `POST /api/v3/seller/pickup-reservations/verify` — Merchant looks up customer's Code #1.
- `POST /api/v3/seller/pickup-reservations/accept` — Merchant confirms customer examined and accepted the item.
- `POST /api/v3/seller/pickup-reservations/reject` — Merchant marks inspection rejected (faulty, declined, or mismatch).
- **Auth**: `seller_api_auth`

### Merchant Handover Release: Code #2 (6-Digit Collection OTP)
- **Customer View**: Customer receives 6-digit Code #2 displayed under `orders.pickup_verification_code`.
- **Merchant Verification Endpoint**:
  - API: `POST /api/v3/seller/orders/verify-pickup-otp`
  - Web: `POST /seller/orders/verify-pickup-otp`
- **Request Body**:
  ```json
  {
    "order_id": 100234,
    "otp": "784912"
  }
  ```
- **Behavior**: Verifies 6-digit code inside atomic DB transaction, updates order status to `delivered`, records receipt and starts the 24-hour return window. Rewards remain pending and vendor entitlement release waits for expiry and admin resolution of disputes.

---

## 5. Delivery Rider Logistics & Proof of Delivery (V2 API)

Rider logistics are managed under `routes/rest_api/v2/api.php` for the Delivery Man Mobile App.

### Rider Authentication
- `POST /api/v2/delivery-man/auth/login` — Rider login with email/phone & password.
- `POST /api/v2/delivery-man/auth/forgot-password` — Password reset request (triggers 6-digit OTP).
- `POST /api/v2/delivery-man/auth/verify-otp` — OTP validation (throttled).
- `POST /api/v2/delivery-man/auth/reset-password` — Submit new password.

### Rider Orders & Active Trips
- `GET /api/v2/delivery-man/current-orders` — Active orders assigned to the authenticated rider.
- `GET /api/v2/delivery-man/all-orders` — Historical and pending order list.
- `GET /api/v2/delivery-man/order-details?order_id={id}` — Detailed delivery payload, recipient address, LGA, and phone.
- `GET /api/v2/delivery-man/order-delivery-history` — Completed delivery ledger.

### Real-Time Handshakes & Status
- `POST /api/v2/delivery-man/record-location-data` — Real-time GPS stream (`latitude`, `longitude`, `location`).
- `PUT /api/v2/delivery-man/update-order-status` — Transitions status (`out_for_delivery`, `processing`).
- `PUT /api/v2/delivery-man/update-expected-delivery` — Update ETA.
- `PUT /api/v2/delivery-man/is-online` — Toggle online/offline rider availability.

### Proof of Delivery (Collection OTP Handshake)
- `POST /api/v2/delivery-man/verify-order-delivery-otp`
  - **Auth**: `delivery_man_auth` with strict `throttle:5,1` (Brute-force protection).
  - **Request Body**:
    ```json
    {
      "order_id": 100234,
      "verification_code": "481920"
    }
    ```
  - **Behavior**: Validates customer-provided 6-digit delivery OTP. On match, sets order to `delivered`, registers rider cash-in-hand if COD, and records proof timestamp.

### Rider Earnings & Wallet
- `GET /api/v2/delivery-man/delivery-wise-earned` — Rider delivery fee earnings per trip.
- `GET /api/v2/delivery-man/profile-dashboard-counts` — Daily and monthly summary metrics.
- `POST /api/v2/delivery-man/withdraw-request` — Request disbursement of earned delivery balance.

---

## 6. Omnichannel Vendor Operations & Relisting (V3 API)

Vendor operations are managed under `routes/rest_api/v3/seller.php` for Vendor Web & Vendor Mobile App.

### Vendor Authentication & Settlement
- `POST /api/v3/seller/auth/login` — Vendor login.
- `POST /api/v3/seller/auth/forgot-password` — Forgot password OTP.
- `GET /api/v3/seller/paystack/banks` — Nigerian bank list for direct Paystack payouts.
- `POST /api/v3/seller/paystack/resolve-account` — Resolve NUBAN account name via Paystack.
- `POST /api/v3/seller/bank-info/send-otp` — 6-digit OTP security challenge before bank account modification.
- `GET /api/v3/seller/kyc/status` & `POST /api/v3/seller/kyc/submit` — Tiered KYC verification.

### Omnichannel Relisting & Marketplace Availability
- `POST /api/v3/seller/products/confirm-availability` — Confirm product physically in stock.
- `POST /api/v3/seller/products/confirm-and-relist` — Reactivate expired listing with refreshed stock and price.
- `POST /api/v3/seller/products/update-marketplace-availability` — Toggle between in-shop only, delivery only, or omnichannel.
- `POST /api/v3/seller/products/update-price-and-reactivate` — Update price and reactivate.
- `POST /api/v3/seller/products/bulk-confirm-availability` — Multi-item inventory confirmation.

### Vendor Catalog & Inventory
- `GET /api/v3/seller/products/list` — List merchant products.
- `POST /api/v3/seller/products/add` — Create new SKU.
- `GET /api/v3/seller/products/details/{id}` — Fetch product details.
- `PUT /api/v3/seller/products/update/{id}` — Update SKU details.
- `PUT /api/v3/seller/products/quantity-update` — Fast stock quantity adjustment.
- `DELETE /api/v3/seller/products/delete/{id}` — Delete product SKU.

### Vendor Orders & Handover
- `POST /api/v3/seller/orders/list` — List orders filtered by status.
- `GET /api/v3/seller/orders/{id}` — Order details, item specifications, customer contact.
- `PUT /api/v3/seller/orders/order-detail-status/{id}` — Update item status (`confirmed`, `processing`).
- `POST /api/v3/seller/orders/verify-pickup-otp` — Complete physical counter handover against 6-digit OTP.

---

## 7. Victorious Points & Customer Cashback Ledger

Governs instant pickup rewards, loyalty incentives, and order redemption.

### Customer Points Summary: `GET /api/v1/cashback/summary`
- **Auth**: `auth:api`
- **Response `200 OK`**:
  ```json
  {
    "status": true,
    "data": {
      "total_points": 2450.00,
      "naira_equivalent": "2450.00",
      "lifetime_earned": 5000.00,
      "lifetime_redeemed": 2550.00,
      "conversion_rate": "1 Point = ₦1.00"
    }
  }
  ```

### Customer Cashback History: `GET /api/v1/cashback/list`
- **Auth**: `auth:api`
- **Response `200 OK`**:
  ```json
  {
    "status": true,
    "data": [
      {
        "id": 104,
        "type": "earned",
        "amount": "1000.00",
        "description": "5% Instant Pickup Reward for Order #100234",
        "created_at": "2026-09-24T12:00:00Z"
      }
    ]
  }
  ```

### Admin Financial Oversight: `GET /admin/cashback/ledger`
- **Auth**: `admin_auth` (Super Admin module: Finance / Audit)
- **Purpose**: Immutable ledger tracking all credits, debits, conversion events, and balance liabilities across the platform.

---

## 8. Customer Core Services & Catalog (V1 API)

### Platform Config & SEO Feeds
- `GET /api/v1/config` — Global settings, active currency, Paystack credentials, theme settings.
- `GET /api/v1/business-pages` — Terms, privacy policy, refund policy, about us.
- `GET /api/v1/feed/sync` — Initial feed bootstrap payload for Customer App home screen.
- `GET /api/v1/products/feed/google-merchant.xml` — Google Merchant Center XML feed.
- `GET /api/v1/products/feed/facebook-catalog.csv` — Meta / Facebook catalog CSV feed.
- `GET /api/v1/products/feed/tiktok-catalog.csv` — TikTok Shop catalog CSV feed.

### Customer Authentication
- `POST /api/v1/auth/register` — Register account with phone/email and password.
- `POST /api/v1/auth/login` — Login with credentials.
- `POST /api/v1/auth/verify-otp` — 6-digit OTP verification.
- `POST /api/v1/auth/forgot-password` — Password reset trigger.
- `GET /api/v1/auth/logout` — Revoke Passport access token.

### Active Cart Lifecycle
- `GET /api/v1/cart` — List items in active cart (each item also includes backend-calculated `cart_totals: { subtotal, tax, total, currency }`).
- `GET /api/v1/cart/totals` — Dedicated authoritative endpoint returning server-computed totals `{ item_count, subtotal, tax, total, currency }` for zero-client-math compliance (§12.2).
- `POST /api/v1/cart/add` — Add item to cart with variant specifications.
- `PUT /api/v1/cart/update` — Update quantity.
- `DELETE /api/v1/cart/remove` — Remove single item.
- `DELETE /api/v1/cart/remove-all` — Clear cart.
- `POST /api/v1/cart/select-cart-items` — Check/uncheck items for selective checkout.
- `POST /api/v1/cart/get-merge-guest-cart` — Merge guest cart with customer cart upon login.

### Customer Addresses (Bound to Canonical Geography)
- `GET /api/v1/customer/address/list` — List customer addresses with LGA & state relationships.
- `POST /api/v1/customer/address/add` — Add address with `country_id`, `state_id`, `lga_id`, street address, phone.
- `POST /api/v1/customer/address/update` — Update address.
- `DELETE /api/v1/customer/address` — Delete address.

### Order Tracking & History
- `GET /api/v1/customer/order/list` — List orders placed by authenticated customer.
- `GET /api/v1/customer/order/details?order_id={id}` — Full order breakdown, items, shipping lane, payment status.
- `GET /api/v1/order/track?order_id={id}&phone_number={phone}` — Public order tracking.
- `GET /api/v1/order/track-order-details` — Granular timeline events and live rider location.

---

## 9. Admin Governance, Lanes & Dispatch Portal

Admin routes are defined in `routes/admin/routes.php` and enforce zero-trust server-side policies.

### Directional Delivery Lanes (`/admin/delivery-lanes`)
- `GET /admin/delivery-lanes` — Manage directional shipping lanes (`Origin LGA → Destination LGA`).
- `POST /admin/delivery-lanes/store` — Create directional lane with base fee, per-kg rate, and transit time.
- `POST /admin/delivery-lanes/update/{id}` — Update lane pricing and parameters.
- `DELETE /admin/delivery-lanes/delete/{id}` — Deactivate lane.
- `POST /admin/delivery-lanes/status` — Toggle lane active status.

### Central Dispatch Portal (`/admin/dispatch-portal`)
- `GET /admin/dispatch-portal` — Cross-merchant order consolidation console.
- `POST /admin/dispatch-portal/assign-batch` — Batch assign multiple orders to a designated rider.
- `GET /admin/dispatch-portal/print-manifest` — Generate printable multi-order dispatch manifest.
- `GET /admin/dispatch-portal/print-waybill/{id}` — Generate printable barcode waybill for order package.

### Internal Logistics Hubs (`/admin/delivery-hubs`)
- `GET /admin/delivery-hubs` — Internal logistics hub network overview.
- `POST /admin/delivery-hubs/store-hub` — Create sorting/transit hub.
- `POST /admin/delivery-hubs/update-hub/{id}` — Update hub metadata.
- `DELETE /admin/delivery-hubs/delete-hub/{id}` — Delete hub.
- *Note*: Hubs are internal physical routing infrastructure only; they are NOT public customer geography.

---

## 10. Authoritative Exclusion & Decommission Catalog

The following endpoints and legacy concepts are **STRICTLY EXCLUDED, DEPRECATED, OR DELETED**.  
Frontend developers and AI agents must NEVER call, re-implement, or re-introduce these patterns:

| Excluded / Dead Route | Reason for Exclusion / Status | Authoritative Replacement |
|---|---|---|
| `GET /api/v1/products/shipping-methods` | **DELETED & REMOVED**. Flat stock shipping methods break directional LGA logistics. | `POST /api/v1/fulfillment/availability` & `/delivery-fee` |
| `POST /api/v1/delivery-hubs/calculate-shipping` | **DELETED & REMOVED**. Direct hub calculation without lane or LGA validation is forbidden. | `POST /api/v1/fulfillment/delivery-fee` |
| Any Flutterwave endpoints / webhooks | **DECOMMISSIONED**. Paystack is the sole approved payment gateway. | `POST /api/v1/checkout/intent/{orderGroupId}/pay` |
| Direct pre-paid pickup without Code #1 | **REPLACED**. Direct payment for pickup without inspection creates fraud and disputes. | `POST /api/v1/pickup-reservations` (Two-Code Flow) |
| Admin `store-state` / `store-city` CRUD | **REMOVED**. Public geography is governed by canonical seeders, not admin UI forms. | Seeded `Country → State → LGA` hierarchy |
| Client-side shipping fee math | **PROHIBITED**. Clients must never calculate delivery fees using distance formulas or hardcoded rates. | Server-side `/fulfillment/delivery-fee` |

## V1 money repair contract amendment — 2026-10-04

- `POST /api/v1/checkout/intent` retains existing response fields and adds `quote`: currency, merchandise_subtotal, tax_total, shipping_total, gross_amount, total_amount, cashback_amount, nested cashback snapshot and vendors. Vendor quote rows include merchandise, tax, shipping_cost and allocated_cashback. All amounts are backend decimal strings. Clients confirm this frozen quote before payment initialization.
- Delivery status uses actual intent states `pending`, `converted_to_orders`, `canceled`, `expired`. Fulfillment success requires a successful paid canonical attempt, converted intent, and paid child orders. A captured attempt needing reconciliation is not a completed order. Expiry releases reserved reward allocations under the intent lock.
- Seller Web/API refund decisions accept `approved` or `rejected` as recommendations only. Sellers cannot reset to pending, financially finalize, reopen admin decisions or mark refunded. Seller rejection retains a dispute hold until admin resolution. Admin refund approval and payment confirmation require their financial capabilities.
- Admin settlement uses `payment_method=wallet_release`, an audit reference and optional notes. It releases the vendor merchandise entitlement once; it does not certify a bank transfer. The existing withdrawal approval, with payment proof and immutable beneficiary snapshot, is the external payout.
- Payout approval decisions are terminal integers `1` (approve) or `2` (deny); `0` is pending and cannot be submitted as a financial decision. Invalid or repeated actions cannot change balances. New V1 payout amounts use NGN decimal values; cancellation restores the exact recorded reservation.
- Pickup recovery displays only `pickup_verification_code`; delivery verification_code is not a pickup fallback.
- Reward earning settings permit 0–5%. Normal V1 points conversion cannot revalue existing liabilities; changing configured conversion requires an explicit ledger migration.
- New per-order hold tracking requires migration 2026_10_03_000010. Historical unknown holds require verified reconciliation before release/refund; aggregate balances are never reset or inferred.
## V1 second-pass money contract amendment — 2026-10-04 [AI]

- Pickup quote: authenticated `POST /api/v1/pickup-reservations/{reservationCode}/quote` (also under `/api/v1/customer/pickup-reservations`). Body `use_cashback` boolean. Response `{status:true, quote_token, quote:{currency:'NGN', merchandise_subtotal, tax_total, shipping_total:'0.00', gross_amount, cashback_amount, total_amount, items, expires_at, reservation_code}}`. All monetary values are exact backend decimal strings. Quoting never initializes a gateway payment. Token is bound to customer, reservation and funding selection, expires within five minutes and before reservation expiry.
- Pickup pay now requires `quote_token` (64 characters) and the same `use_cashback` selection. Missing token fails validation; expired, mismatched or changed funding returns409 requiring a new quote. An unresolved existing attempt cannot silently change reward funding.
- Authenticated pickup `GET /api/v1/pickup-reservations/{reservationCode}/status` returns flat fields: `status`, `reservation_status`, `payment_status`, `order_id`, `pickup_verification_code`, `expires_at`, `payment_request_id`, `gateway_reference`, `authorization_url`. Paid requires canonical successful attempt plus the paid reservation order. Inconsistent captures return `reconciliation_required`; refunded outcomes are distinct. The authorization URL is exposed only for an unresolved pending attempt; handover code/order are shown only for proven fulfillment. Clients never treat redirects as payment proof.
- Storefront mirrors quote/pay/status through authenticated customer pickup routes. The reservation index renders a customer page; API/JSON callers retain JSON. App and browser recovery retain owner-bound reservation identity before initialization and poll the server after payment close/cancel/restart.
- Rider withdrawal approval uses multipart `proof_of_payment`, valid JPEG/PNG/PDF, maximum5MB, decision `approved=1`; rejection is `approved=2`. New requests capture frozen server-side NGN bank beneficiary. Historical missing snapshots cannot be approved without reconciliation. Admin/vendor web/API share terminal reservation and balance validation. Vendor employees cannot operate nested rider withdrawal finance routes.
- Generic order amount/date editing accepts only expected_delivery_date (Y-m-d) and authorized admin deliveryman_charge (exact NGN, no greater than authoritative shipping_cost before dispatch). Sellers may update only their own nonterminal scheduling date. Paid funding/settlement/refund columns cannot be changed here. Digital payment status and existing paid status are immutable through generic admin status controls.
- Refund previews preserve legacy response keys while exposing frozen exact cash, reward, merchandise and tax allocations. Nullable order_details.refund_allocation migration freezes all order lines together with deterministic penny residual allocation. Request creation and manual/provider execution reuse these values; valid zero cash/reward amounts are not replaced by fallback values. Historical incompatible completed allocations require reconciliation.
- Delivery intent creation returns409 if a changed cart/address would supersede an unresolved pending payment attempt. The old intent, reward reservation and payment identity remain intact.

## V1 credential and reset boundary amendment — 2026-10-05 [AI]

- Seller, employee and rider bearer credentials are stored as SHA-256 digests. Authentication hashes the submitted raw token once; stored digests and legacy plaintext storage are not valid bearer credentials. Migration 2026_10_04_000002 invalidates previously exposed credentials and unbound reset records; clients must sign in/request a fresh reset after rollout.
- Seller-info returns an explicit profile DTO. Authentication/remember fields are never returned. Image presentation is retained; bank/KYC/commission presentation and wallet are owner-only. Employee requests retain seller tenancy separately from the authenticated principal. Logout revokes that principal, never its owner or a caller-supplied principal field.
- Password reset finalization requires exact identity, a server-issued OTP/reset proof, password and matching confirm_password. Proof is hashed at rest, bound to account ID, actor and password_reset purpose, expires after 15 minutes and is atomically consumed with the password change. Five failed proof attempts impose a 10-minute account cooldown. Customer/seller payload keys remain identity/otp; rider adds identity/otp (phone alias is supported). Generic verification rows, Firebase sessionInfo and invitation metadata cannot authorize password changes.
- Public firebase-auth-token-store endpoints reject caller-written verification records with403. Authoritative Firebase verification must confirm the exact requested phone before issuing reset_token (64 alphanumeric characters) and identity. Reset clients submit that returned proof as otp, rather than the provider session or SMS code.
- Reset revokes rider/seller bearer credentials and customer Passport access/refresh, temporary and remember credentials. Customer/seller web sessions carry credential_version and stale versions are rejected independent of session storage. Only a fresh successful login renews the session version. Password whitespace is preserved across web/API/app paths; identity normalization remains separate.
- OAuth creation uses random local passwords, never public provider IDs or email addresses. The migration selectively rotates historical hashes proven to match recorded provider IDs/Apple emails and revokes those credentials; other customer-chosen passwords and financial balances are preserved. Suspended customers cannot obtain OAuth login or temporary registration challenges.
- Seller order-status history first authorizes the parent order against seller ownership and employee module/known branch access. Search values in raw ranking/LOCATE queries are bound data. The legacy unused ranking helper supplies a parameterized template and validates identifiers.
- Pickup recovery accepts explicit status=true and an authoritative known payment state. Only owner-matching unpaid status with no payment_request_id permits recovery to a fresh quote; pending/unknown/reconciliation identities remain durable, and refunded is terminal. Redirects never prove payment or reveal handover codes.

### Additive migration rollout clarification — 2026-10-05 [AI]

The credential binding migration remains 2026_10_04_000002. Selective historical public-value OAuth password rotation is a separate 2026_10_05_000001_rotate_public_oauth_passwords migration, so already-migrated installations receive it through normal migration discovery. Rotation is idempotent for already-randomized hashes; rollback never restores public passwords. Deploy both discovered migrations with their matching code; no migration or financial balance changes were applied by this reviewer.
