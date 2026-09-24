# Victorious MARKET — Canonical API Contract Registry

> **AUTHORITATIVE LIVING CONTRACT DICTIONARY FOR ALL CLIENT PLATFORMS.**  
> All frontend applications (Customer App, Storefront, Vendor Web/App, Delivery App, Admin Panel) must consume these exact endpoints and response schemas. Client applications must never guess JSON keys or invent client-side calculations.

---

## 1. Canonical Marketplace Geography (SSOT)

### `GET /api/v1/geography/countries`
- **Purpose**: List all active countries.
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

---

## 2. Fulfillment Availability & Fees

### `POST /api/v1/fulfillment/availability`
- **Purpose**: Authoritative check for delivery and in-shop pickup feasibility. Evaluates active directional `DeliveryLane` records and merchant pickup availability.
- **Auth**: `apiGuestCheck`
- **Request Body**:
  ```json
  {
    "shop_id": 1,                  // Optional: resolved from cart_items/product_id/active cart if omitted
    "shipping_address_id": 12,     // Optional: required to evaluate delivery feasibility
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
          "fee": "1500.00",
          "currency": "NGN",
          "estimated_delivery_time": "24-48 hours",
          "lane": {
            "origin_lga": "Uyo",
            "destination_lga": "Eket"
          }
        },
        "pickup": {
          "available": true,
          "pickup_type": "in_shop_inspection",
          "cost": "0.00",
          "estimated_ready_time": "Immediate / 24 hours"
        }
      }
    }
  }
  ```

### `POST /api/v1/fulfillment/delivery-fee`
- **Purpose**: Get authoritative delivery fee for a specific shop and address.
- **Request Body**:
  ```json
  {
    "shop_id": 1,              // Or "product_id": 105
    "shipping_address_id": 12
  }
  ```
- **Response `200 OK`**:
  ```json
  {
    "success": true,
    "data": {
      "fee": "1500.00",
      "available": true,
      "origin_lga": "Uyo",
      "destination_lga": "Eket"
    }
  }
  ```

---

## 3. Two-Phase Delivery Checkout & Paystack Settlement

### Phase 1: `POST /api/v1/checkout/intent`
- **Purpose**: Create or replay a frozen checkout snapshot server-side.
- **Auth**: `auth:api` (Authenticated customers only; guest checkout unsupported in V1).
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
    "status": "pending_payment",
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

### Legacy Compatibility Shim: `POST /api/v1/digital-payment`
- **Purpose**: Backward-compatible route consumed by mobile app payment buttons. Internally delegates to `DeliveryCheckoutIntentService` and `DeliveryPaymentInitializationService`.
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

---

## 4. In-Shop Pickup Protocol (Two-Code Lifecycle)

### Code #1 Creation: `POST /api/v1/pickup-reservations`
- **Purpose**: Issue 24-hour physical store pass. Zero money paid, zero inventory locked (`Reservation ≠ Sale`).
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

### Code #1 Status Query: `GET /api/v1/pickup-reservations/{code}`
- **Purpose**: Customer checks if merchant inspected & accepted the goods.
- **Response `200 OK`**:
  ```json
  {
    "status": true,
    "data": {
      "reservation_code": "RES-17B81659",
      "status": "inspected_accepted", // Unlocks "Pay at Store" button in Customer App
      "can_pay": true,
      "amount": "20000.00",
      "expires_at": "2026-09-25T14:30:00Z"
    }
  }
  ```

### Pay at Store: `POST /api/v1/pickup-reservations/{code}/pay`
- **Purpose**: Customer initiates digital Paystack payment on-site after inspecting goods.
- **Response `200 OK`**:
  ```json
  {
    "status": true,
    "authorization_url": "https://checkout.paystack.com/pickup_counter_...",
    "reference": "RES-PAY-98319842"
  }
  ```

### Post-Payment Handover: Code #2 (6-Digit Collection OTP)
- Generated inside atomic DB transaction upon verified payment:
  - **Location**: `orders.pickup_verification_code` (e.g., `784912`)
  - **Customer View**: Displayed prominently in Customer App order details under "Counter Handover Pass".
  - **Merchant Verification**: Merchant enters 6 digits in Vendor App/Web to confirm counter release and transition order status to `delivered`.

---

## 5. Cashback & Victorious Points Ledger

### `GET /api/v1/cashback/summary`
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

### `GET /api/v1/cashback/list`
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
