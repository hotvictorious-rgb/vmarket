# 🗄️ Vmarket Database Architecture

**MySQL 8 Authoritative Schema & Relationship Documentation**

---

## 1. Key Database Entities

```mermaid
erDiagram
    USERS ||--o{ ORDERS : places
    SELLERS ||--o{ SHOPS : owns
    SELLERS ||--o{ PRODUCTS : lists
    SELLERS ||--o{ WITHDRAW_REQUESTS : requests
    ORDERS ||--|{ ORDER_DETAILS : contains
    DELIVERY_MEN ||--o{ ORDERS : fulfills
    DELIVERY_MEN ||--o{ WITHDRAW_REQUESTS : requests
```

---

## 2. Core Tables & Specialized Fields

### A. `sellers` Table
* `id` (BIGINT, Primary Key)
* `f_name`, `l_name` (VARCHAR)
* `phone`, `email` (VARCHAR, Unique)
* `status` (ENUM: `pending`, `approved`, `rejected`, `suspended`)
* `bank_name`, `branch`, `account_no`, `holder_name` (VARCHAR)
* `bank_updated_at` (TIMESTAMP) — Used to calculate the 48-hour withdrawal cooldown.
* `bank_otp`, `bank_otp_expires_at` (VARCHAR, TIMESTAMP) — Email OTP authorization.
* `nin`, `nin_document` (VARCHAR) — National Identity Slip data.
* `cac_number`, `cac_document` (VARCHAR) — Corporate Affairs Commission data.
* `kyc_status` (ENUM: `unverified`, `submitted`, `verified`, `rejected`) — Verification state.
* `kyc_reviewed_at`, `kyc_notes` (TIMESTAMP, TEXT)

### B. `withdraw_requests` Table
* `id` (BIGINT, Primary Key)
* `seller_id`, `delivery_man_id`, `admin_id` (BIGINT, Foreign Keys)
* `amount` (DECIMAL 24, 2)
* `request_updated_at` (TIMESTAMP)
* `status` (ENUM: `pending`, `approved`, `denied`)
* `transaction_note` (TEXT)
* `proof_of_payment` (VARCHAR) — Path to the mandatory payment transfer screenshot.

### C. `orders` Table
* `id` (BIGINT, Primary Key)
* `customer_id`, `seller_id`, `delivery_man_id` (BIGINT)
* `order_status` (ENUM: `pending`, `confirmed`, `processing`, `out_for_delivery`, `delivered`, `returned`, `failed`, `canceled`)
* `payment_status` (ENUM: `paid`, `unpaid`)
* `pickup_verification_code` (VARCHAR) — Secret 4-digit Pickup OTP for store handoffs.
* `verification_code` (VARCHAR) — Customer delivery OTP.
* `paystack_payment_url` (TEXT) — Dynamic payment link for rider cash-on-delivery collection.
