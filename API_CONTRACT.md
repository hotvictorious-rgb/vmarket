# 🔌 Vmarket Core API Contract (Authoritative SSOT)

**Authoritative REST API Contracts for Mobile Apps & Web Dashboards**  
*Architecture Status:* `AUTHORITATIVE` (Phase 3 Backend API Contract Freeze)

---

## 1. Global Conventions, Authentication & Security Headers

### Request Headers
Every request to `/api/...` must include:
* `Accept: application/json`
* `Content-Type: application/json`
* `X-localization: en` (or user locale)
* `Authorization: Bearer <token>` (for all authenticated routes)

### Multi-Branch Vendor Staff Header
When an action is performed by a vendor or vendor employee, the target shop branch must be supplied via:
* `X-Branch-ID: <shop_id>` OR request parameter `shop_id: <shop_id>`
* **Branch Isolation Barrier:** If a vendor employee is assigned to Branch A (`shop_id: 1`) and submits requests targeting Branch B (`shop_id: 2`), the backend strictly returns **HTTP 403 Forbidden** (`Unauthorized branch access`).

### Financial & Precision Standard
* All amounts in responses are formatted as exact decimal strings (e.g. `"500.00"`, `"10500.00"`) evaluated via BCMath with zero float drift ($\Delta = ₦0.00$).
* Currency is strictly Nigerian Naira (`NGN`).
* All OTPs are 6-digit cryptographic strings (`[0-9]{6}`).

---

## 2. Canonical Geography REST API (`Country → State → LGA`)

Client applications MUST consume canonical geographic units from these endpoints and NEVER hardcode fees or routing logic.

### 2.1 Fetch Active Countries
* **Route:** `GET /api/v1/geography/countries`
* **Response (HTTP 200):**
```json
{
  "status": true,
  "message": "Countries retrieved successfully.",
  "data": [
    {
      "id": 1,
      "name": "Nigeria",
      "iso_code": "NGA",
      "phone_code": "+234",
      "currency_code": "NGN"
    }
  ]
}
```

### 2.2 Fetch Active States
* **Route:** `GET /api/v1/geography/states/{country_id}`
* **Response (HTTP 200):**
```json
{
  "status": true,
  "message": "States retrieved successfully.",
  "country_id": 1,
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

### 2.3 Fetch Active LGAs
* **Route:** `GET /api/v1/geography/lgas/{state_id}`
* **Response (HTTP 200):**
```json
{
  "status": true,
  "message": "LGAs retrieved successfully.",
  "state_id": 3,
  "data": [
    { "id": 69, "state_id": 3, "name": "Uyo" },
    { "id": 70, "state_id": 3, "name": "Eket" },
    { "id": 71, "state_id": 3, "name": "Ikot Ekpene" },
    { "id": 72, "state_id": 3, "name": "Oron" }
  ]
}
```

---

## 3. Customer Address Book (`ShippingAddress`)

Addresses capture canonical `country_id`, `state_id`, and `lga_id` to establish destination routing for delivery lanes.

### 3.1 List Customer Addresses
* **Route:** `GET /api/v1/customer/address/list`
* **Auth:** Required (`auth:api`)
* **Response (HTTP 200):**
```json
[
  {
    "id": 4,
    "customer_id": 5,
    "contact_person_name": "Customer Alpha",
    "address_type": "home",
    "address": "10 Aka Road, Uyo",
    "city": "Uyo",
    "country_id": 1,
    "state_id": 3,
    "lga_id": 69,
    "phone": "+2348011110001",
    "is_billing": false,
    "country": { "id": 1, "name": "Nigeria", "iso_code": "NGA" },
    "state": { "id": 3, "name": "Akwa Ibom", "state_code": "AK" },
    "lga": { "id": 69, "name": "Uyo" }
  }
]
```

### 3.2 Add New Address
* **Route:** `POST /api/v1/customer/address/add`
* **Auth:** Required (`auth:api`)
* **Request:**
```json
{
  "contact_person_name": "Customer Alpha",
  "address_type": "home",
  "address": "10 Aka Road, Uyo",
  "country_id": 1,
  "state_id": 3,
  "lga_id": 69,
  "phone": "+2348011110001",
  "is_billing": 0
}
```
* **Validation Rules:**
  - `contact_person_name`: required, string
  - `address`: required, string
  - `phone`: required, string
  - `country_id`: required, integer, exists in `countries`
  - `state_id`: required, integer, exists in `states`
  - `lga_id`: required, integer, exists in `lgas`, validated against `state_id` via `ValidLgaForState`

---

## 4. Fulfillment Availability & Pricing Engine

### 4.1 Check Multi-Item Fulfillment Availability
* **Route:** `POST /api/v1/fulfillment/availability`
* **Request:**
```json
{
  "address_id": 4,
  "cart_items": [
    { "id": 148, "product_id": 101, "quantity": 1 }
  ]
}
```
* **Response (HTTP 200):**
```json
{
  "status": true,
  "available": true,
  "fulfillment_options": {
    "delivery": {
      "available": true,
      "total_shipping_fee": "500.00",
      "currency": "NGN",
      "destination_lga": { "id": 69, "name": "Uyo" },
      "vendor_lanes": [
        {
          "seller_id": 5,
          "shop_name": "MegaStore Uyo",
          "origin_lga": { "id": 69, "name": "Uyo" },
          "delivery_fee": "500.00",
          "estimated_delivery_time": "2-6 hours"
        }
      ]
    },
    "in_shop_pickup": {
      "available": true,
      "shops": [
        {
          "shop_id": 6,
          "name": "MegaStore Branch A - Uyo",
          "address": "1 Aka Road, Uyo",
          "pickup_time_slots": ["08:00 - 11:00", "11:00 - 14:00", "14:00 - 18:00"],
          "pickup_instructions": "Bring your 6-digit reservation code."
        }
      ]
    }
  }
}
```

### 4.2 Query Single-Lane Delivery Fee
* **Route:** `POST /api/v1/fulfillment/delivery-fee`
* **Request:** `{ "shop_id": 6, "address_id": 4 }`
* **Response (HTTP 200):**
```json
{
  "status": true,
  "origin_lga_id": 69,
  "destination_lga_id": 69,
  "delivery_fee": "500.00",
  "estimated_delivery_time": "2-6 hours",
  "currency": "NGN"
}
```

---

## 5. Delivery Checkout Intent & Digital Payment

### 5.1 Create / Replay Delivery Checkout Intent
* **Route:** `POST /api/v1/checkout/intent`
* **Auth:** Required (`auth:api`)
* **Headers:** `Idempotency-Key: <unique_client_uuid>`
* **Request:**
```json
{
  "shipping_address_id": 4,
  "cart_item_ids": [148],
  "idempotency_key": "CHK-UUID-99124A"
}
```
* **Response (HTTP 200 / 201):**
```json
{
  "status": true,
  "message": "Checkout intent created successfully.",
  "data": {
    "order_group_id": "OG_a2cece9d-da49-40f9-af48-1943bdd40c35",
    "total_amount": "10500.00",
    "currency": "NGN",
    "status": "pending",
    "expires_at": "2026-09-23T16:00:00Z",
    "summary": {
      "item_subtotal": "10000.00",
      "shipping_cost": "500.00",
      "discount_amount": "0.00",
      "tax_amount": "0.00",
      "grand_total": "10500.00"
    }
  }
}
```

### 5.2 Initialize Paystack Payment Attempt
* **Route:** `POST /api/v1/checkout/intent/{orderGroupId}/pay`
* **Auth:** Required (`auth:api`)
* **Request:** `{ "payment_method": "paystack" }`
* **Response (HTTP 200):**
```json
{
  "status": true,
  "payment_method": "paystack",
  "gateway_reference": "PAYSTACK_REF_018A2B9",
  "authorization_url": "https://checkout.paystack.com/access_code_example",
  "amount_kobo": 1050000,
  "currency": "NGN"
}
```

### 5.3 Victorious Cashback 5% Reward Ledger API
* **Summary Route:** `GET /api/v1/cashback/summary`
* **Ledger Route:** `GET /api/v1/cashback/list?limit=20&offset=1`
* **Auth:** Required (`auth:api`)
* **Response (HTTP 200):**
```json
{
  "status": true,
  "summary": {
    "points_balance": 2500,
    "equivalent_naira": "2500.00",
    "currency": "NGN",
    "lifetime_earned_points": 12500,
    "lifetime_redeemed_points": 10000,
    "pending_settlement_points": 500
  }
}
```
* **Invariants:**
  - Cashback is earned at 5% of settled order merchandise total.
  - Redeemed as order discount via `use_cashback: true` in `POST /api/v1/checkout/intent`.
  - Client never transmits arbitrary point deductions; backend strictly validates balance under pessimistic lock.

---

## 6. In-Shop Inspection & Pickup Reservations

Zero-shipping reservation channel. Stock is locked, pre-payment is ₦0.00, and customer inspects items at merchant shop before paying.

### 6.1 Create In-Shop Pickup Reservation
* **Route:** `POST /api/v1/customer/pickup-reservations`
* **Auth:** Required (`auth:api`)
* **Request:**
```json
{
  "idempotency_key": "RES-UUID-88912A",
  "cart_ids": [148]
}
```
* **Response (HTTP 201):**
```json
{
  "status": true,
  "message": "Pickup reservation created successfully.",
  "reservation_code": "RES-2AD90282",
  "shop": {
    "id": 6,
    "name": "MegaStore Branch A - Uyo",
    "address": "1 Aka Road, Uyo"
  },
  "total_amount": "10000.00",
  "shipping_fee": "0.00",
  "status": "pending_inspection",
  "expires_at": "2026-09-23T16:00:00Z"
}
```

### 6.2 Merchant Verify Customer Reservation Code
* **Route:** `POST /api/v3/seller/pickup-reservations/verify`
* **Auth:** Required (`auth:seller` with `X-Branch-ID`)
* **Request:** `{ "reservation_code": "RES-2AD90282" }`
* **Response (HTTP 200):** Returns reservation items, customer details, and inspection readiness.

### 6.3 Merchant Accept Inspection (Customer Approves Items)
* **Route:** `POST /api/v3/seller/pickup-reservations/accept`
* **Request:** `{ "reservation_code": "RES-2AD90282", "notes": "Inspected in good condition." }`
* **Response (HTTP 200):** Status transitions to `inspected_accepted`. Generates Paystack instant settlement link or accepts verified transfer.

### 6.4 Merchant Reject Inspection (Customer Disapproves Items)
* **Route:** `POST /api/v3/seller/pickup-reservations/reject`
* **Request:** `{ "reservation_code": "RES-2AD90282", "rejection_reason": "Customer chose alternative product size." }`
* **Response (HTTP 200):** Status transitions to `inspected_rejected`. Reserved stock is released back into inventory pool immediately.

---

## 7. Delivery Rider Operations & Contactless Settlement

### 7.1 Verify Vendor Pickup OTP (Package Custody Transfer)
* **Route:** `POST /api/v1/delivery-man/order/verify-pickup-otp`
* **Auth:** Required (`auth:delivery_man`)
* **Request:** `{ "order_id": 100045, "pickup_otp": "492018" }`
* **Response (HTTP 200):**
```json
{
  "status": true,
  "order_status": "out_for_delivery",
  "message": "Package picked up from vendor. Handover custody logged."
}
```

### 7.2 Verify Customer Doorstep Delivery OTP
* **Route:** `POST /api/v2/delivery-man/order/verify-order-delivery-otp`
* **Auth:** Required (`auth:delivery_man`)
* **Request:** `{ "order_id": 100045, "verification_code": "940281" }`
* **Response (HTTP 200):**
```json
{
  "status": true,
  "order_status": "delivered",
  "received_at": "2026-09-22T16:30:00Z",
  "return_window_closes_at": "2026-09-23T16:30:00Z"
}
```

### 7.3 Generate Dynamic Paystack Doorstep Payment Link
* **Route:** `GET /api/v2/delivery-man/order/paystack-link/{order_id}`
* **Auth:** Required (`auth:delivery_man`)
* **Response (HTTP 200):** Returns dynamic Paystack URL and QR code for customer on-delivery transfer payments.

---

## 8. Nigerian Banking, Account Resolution & Payouts

### 8.1 Fetch Nigerian Banks List
* **Route:** `GET /api/v3/seller/banks`
* **Response (HTTP 200):**
```json
{
  "status": true,
  "data": [
    { "id": 1, "name": "Access Bank", "code": "044" },
    { "id": 2, "name": "OPay Digital Services", "code": "999992" },
    { "id": 3, "name": "Kuda Bank", "code": "50211" }
  ]
}
```

### 8.2 Resolve NUBAN Account (Paystack)
* **Route:** `POST /api/v3/seller/resolve-account`
* **Request:** `{ "account_number": "0123456789", "bank_code": "044" }`
* **Response (HTTP 200):**
```json
{
  "status": true,
  "account_number": "0123456789",
  "account_name": "JOHN VICTOR DOE",
  "bank_code": "044"
}
```

### 8.3 Request Balance Payout
* **Route:** `POST /api/v3/seller/balance-withdraw`
* **Request:** `{ "amount": 50000, "withdraw_method_id": 1 }`
* **Response (HTTP 200):** `{ "status": true, "message": "Withdrawal request submitted." }`

---

## 9. Non-Negotiable Contract Invariants

1. **No Client-Side Marketplace Logic:** Client apps must NEVER compute shipping fees, invent delivery availability, or calculate tax amounts locally. The backend calculation is the single authoritative source of truth.
2. **Canonical Geographic IDs:** Addresses must provide `country_id`, `state_id`, and `lga_id`. Unrecognized LGAs are rejected.
3. **Pessimistic Balance & Stock Concurrency:** All financial balance updates and inventory decrements run inside `DB::transaction()` under `lockForUpdate()`.
4. **Zero Float Drift:** Every monetary computation is calculated via BCMath with exact decimal precision ($\Delta = ₦0.00$).

---

## 10. Canonical Production Alignment References

All client and backend implementations must strictly comply with:
- **Customer App Specification:** `.agents/rules/VMARKET_CUSTOMER_APP_SPEC.md` (77 canonical sections)
- **Customer App Enforcing Rules:** `.agents/rules/CUSTOMER_APP_ALIGNMENT.md` (20 mandatory rules)
- **Customer App Alignment Plan:** `.agents/rules/CUSTOMER_APP_ALIGNMENT_PLAN.md` (34-phase plan)
- **Systemic Proof Record:** `VICTORIOUS_MARKET_MATHEMATICAL_AND_SYSTEMIC_PROOF.md`
