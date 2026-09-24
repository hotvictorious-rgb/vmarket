# Sync Channel: Vendor Web & Vendor Mobile App ↔ Backend

> **Actor Focus**: Omnichannel Merchants & Branch Staff  
> **Client Framework**: Laravel Blade (`resources/views/vendor-views/`) & Flutter (`Vendor app/`)  
> **Protocol Rules**: Strictly follow [`.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md) and [`.agents/sync/API_CONTRACT_REGISTRY.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/API_CONTRACT_REGISTRY.md).

---

## 1. Client Invariants (Mandatory for Vendor AI)
1. **Tenant & Branch Security Scoping**: Every query must verify `seller_id = auth('seller')->id()` or `user_id = auth('seller')->id()`. Never trust a client-provided `shop_id` or `seller_id` parameter.
2. **In-Shop Inspection Protocol**:
   - Customer presents **Code #1** (`reservation_code`, e.g., `RES-17B81659`).
   - Merchant views reservation, retrieves physical items, and presents them for customer examination.
   - Merchant clicks **"Accept Inspection"** or **"Reject Inspection"**.
   - Inspection acceptance unlocks customer in-app Paystack checkout. **Reservation does NOT decrement stock.**
3. **Counter Handover & Release**:
   - Packaged goods must **NEVER** be handed over without verifying the customer's **Code #2 (6-digit Handover OTP)**.
   - Counter release endpoint: `POST /seller/orders/verify-pickup-otp` (Web) or `POST /api/v3/seller/orders/verify-pickup-otp` (Mobile).
4. **Logistics Handover**:
   - Rider collection requires verified collection barcode / OTP before releasing items marked `ready_for_pickup`.

---

## 2. Active Communication & RFC Tickets

### [TICKET-VENDOR-001] In-Shop Inspection Accept/Reject Handshake
- **Status**: `FULFILLED`
- **Request**: Merchant needs an endpoint to record the outcome of customer physical inspection for a pickup reservation.
- **Backend Fulfillment**:
  - Web: `POST /seller/orders/pickup-reservations/{id}/action` (actions: `accept`, `reject`).
  - Rest API: `POST /api/v3/seller/pickup-reservations/{code}/inspect` (status: `inspected_accepted` | `inspected_rejected`).
  - Invariant: Accepting updates reservation state and triggers real-time status update so Customer App displays the Pay button.

### [TICKET-VENDOR-002] Counter Release 6-Digit Handover OTP Verification
- **Status**: `FULFILLED`
- **Request**: Merchant needs counter release verification using the customer's 6-digit payment PIN.
- **Backend Fulfillment**:
  - Handover Controller: `App\Http\Controllers\Vendor\Order\InShopHandoverController@verifyAndRelease`
  - Validates cryptographic 6-digit OTP against `orders.pickup_verification_code` with pessimistic row locking.
  - Transitions order status from `processing`/`ready_for_pickup` to `delivered` $\rightarrow$ `completed`.

---

## 3. Template for New Request Tickets (Copy & Paste to Append Below)
```markdown
### [REQ-VENDOR-YYYYMMDD-###] <Feature / Endpoint Title>
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `LOW` | `MEDIUM` | `HIGH` | `BLOCKER`
- **Context**: <Explain the merchant operations or branch employee use case>
- **Client**: `Vendor Web` | `Vendor Mobile App`
- **Proposed Route**: `METHOD /...`
- **Payload / Schema**:
  ```json
  { ... }
  ```
```
