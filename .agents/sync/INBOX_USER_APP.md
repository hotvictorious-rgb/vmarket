# Sync Channel: Customer Mobile App (User App) ↔ Backend

> **Actor Focus**: Flutter Customer Mobile App (`User app/`)  
> **Client Framework**: Flutter / Provider / GetIt (`lib/features/`)  
> **Protocol Rules**: Strictly follow [`.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md) and [`.agents/sync/API_CONTRACT_REGISTRY.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/API_CONTRACT_REGISTRY.md).

---

## 1. Client Invariants (Mandatory for User App AI)
1. **Zero Client-Side Math**: Never calculate shipping fees, cart subtotals, tax, cashback redemption amounts, or OTP codes inside Dart controllers or widgets.
2. **Authoritative Two-Phase Checkout**: Always execute `POST /api/v1/checkout/intent` to freeze intent and get `order_group_id`, then proceed to Paystack.
3. **Two-Code Pickup Architecture**:
   - `reservation_code` (₦0.00 pre-payment store pass) for counter inspection.
   - `pickup_verification_code` (6-digit PIN) is generated **only after** Paystack settlement and presented at counter to take custody.
4. **Geography Hierarchy**: Only display canonical `Country → State → LGA` via `/api/v1/geography/*`.

---

## 2. Active Communication & RFC Tickets

### [TICKET-USERAPP-001] Two-Phase Delivery Checkout Intent Contract
- **Status**: `FULFILLED`
- **Request**: Customer App needs to initiate marketplace delivery checkout without guessing total delivery fees.
- **Backend Fulfillment**:
  - Phase 1: `POST /api/v1/checkout/intent` (takes `address_id`, `idempotency_key`, `use_cashback`).
  - Phase 2: `POST /api/v1/checkout/intent/{orderGroupId}/pay` (returns `authorization_url`).
  - Shim: `POST /api/v1/digital-payment` is preserved and wired to the same intent service.
- **Schema**: See `API_CONTRACT_REGISTRY.md` Section 3.

### [TICKET-USERAPP-002] In-Shop Pickup Inspection Status Check
- **Status**: `FULFILLED`
- **Request**: User App needs to poll/check if merchant has accepted or rejected the customer's in-store inspection before showing the "Pay at Store" button.
- **Backend Fulfillment**:
  - Endpoint: `GET /api/v1/pickup-reservations/{code}`
  - Returns `can_pay: true` and `status: "inspected_accepted"` once merchant taps "Accept" in Vendor app.
- **Schema**: See `API_CONTRACT_REGISTRY.md` Section 4.

---

## 3. Template for New Request Tickets (Copy & Paste to Append Below)
```markdown
### [REQ-USERAPP-YYYYMMDD-###] <Feature / Endpoint Title>
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `LOW` | `MEDIUM` | `HIGH` | `BLOCKER`
- **Context**: <Explain the UI flow or user problem in the Flutter Customer App>
- **Proposed Endpoint**: `METHOD /api/v1/...`
- **Required Request Payload**:
  ```json
  { ... }
  ```
- **Desired Response Fields**:
  ```json
  { ... }
  ```
```
