# 🔌 Vmarket Core API Contract

**Authoritative REST API Contracts for Mobile Apps & Third-Party Integrations**

---

## 1. Authentication & Headers

All requests to `/api/v1/...` must include:
* `Accept: application/json`
* `Authorization: Bearer <token>` (for authenticated routes)
* `zoneId: [1]` (for localized multi-vendor inventory)
* `X-localization: en` (for multi-language strings)

---

## 2. Core Endpoints & Payloads

### A. Nigerian Banking & Resolution

#### 1. Fetch Nigerian Banks
* **Route:** `GET /api/v3/seller/banks`
* **Response:**
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

#### 2. Resolve NUBAN Account (Paystack)
* **Route:** `POST /api/v3/seller/resolve-account`
* **Request:** `{ "account_number": "0123456789", "bank_code": "044" }`
* **Response:**
```json
{
  "status": true,
  "account_number": "0123456789",
  "account_name": "JOHN VICTOR DOE",
  "bank_code": "044"
}
```

#### 3. Send Bank Change OTP
* **Route:** `POST /api/v3/seller/bank-info/send-otp`
* **Request:** `{ "bank_name": "Access Bank", "account_no": "0123456789", "holder_name": "JOHN VICTOR DOE" }`
* **Response:** `{ "status": true, "message": "OTP sent to your email." }`

#### 4. Update Bank Info (with 48-hr Cooldown)
* **Route:** `PUT /api/v3/seller/seller-update`
* **Request (Multipart):**
  - `bank_name`: String
  - `branch`: String
  - `account_no`: String (10 digits)
  - `holder_name`: String
  - `otp`: String (6 digits, required if bank already set)
* **Response:** `{ "status": true, "message": "Bank info updated successfully." }`

---

### B. Vendor Identity Verification (KYC)

#### 1. Submit KYC Documents
* **Route:** `POST /api/v3/seller/kyc/submit`
* **Request (Multipart):**
  - `nin`: String (11 digits, optional)
  - `nin_document`: File (Image/PDF, optional)
  - `cac_number`: String (RC/BN format, optional)
  - `cac_document`: File (Image/PDF, optional)
* **Response:**
```json
{
  "status": true,
  "message": "KYC submitted for review.",
  "kyc_status": "submitted"
}
```

---

### C. Withdrawal & Payouts

#### 1. Request Payout (Vendor / Delivery Man)
* **Route:** `POST /api/v3/seller/balance-withdraw`
* **Request:** `{ "amount": 50000, "withdraw_method_id": 1, "field_1": "value" }`
* **Response:** `{ "status": true, "message": "Withdrawal request submitted." }`

#### 2. Admin Approve Payout (Mandatory Screenshot)
* **Route:** `POST /admin/vendor/withdraw-status/{id}` or `POST /admin/delivery-man/withdraw/status-filter`
* **Request:**
  - `status`: "approved"
  - `proof_of_payment`: File (Image, required)
  - `note`: String (optional)

---

### D. Logistics & Orders

#### 1. Verify Pickup OTP
* **Route:** `POST /api/v1/delivery-man/order/verify-pickup-otp`
* **Request:** `{ "order_id": 100045, "pickup_otp": "4920" }`
* **Response:** `{ "status": true, "order_status": "out_for_delivery" }`

#### 2. Generate Paystack Dynamic Payment Link
* **Route:** `POST /api/v1/delivery-man/order/generate-paystack-link`
* **Request:** `{ "order_id": 100045 }`
* **Response:** `{ "status": true, "payment_url": "https://checkout.paystack.com/..." }`
