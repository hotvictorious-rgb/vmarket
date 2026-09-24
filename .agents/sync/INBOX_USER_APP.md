# Sync Channel: Customer Mobile App (User App) ↔ Backend

> **Actor Focus**: Flutter Customer Mobile App (`User app/`)  
> **Client Framework**: Flutter / Provider / GetIt (`lib/features/`)  
> **Protocol Rules**: Strictly follow [`.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md) and [`.agents/sync/API_CONTRACT_REGISTRY.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/API_CONTRACT_REGISTRY.md).

> [!CAUTION]
> **MANDATORY MULTI-AGENT GIT COMMIT DIRECTIVE (NON-NEGOTIABLE)**:
> **EVERY AI MUST STAGE AND COMMIT ONLY ITS OWN SPECIFIC CHANGES — NEVER COMMIT ALL FILES!**  
> 1. You MUST explicitly name only your own modified files: `git add <exact-file-path-1> <exact-file-path-2> AI_CHANGELOG.md`.
> 2. Running `git add .`, `git add -A`, or `git commit -a` is **STRICTLY FORBIDDEN**.
> 3. Concurrent AIs are actively modifying files in other directories (`User app/`, `Vendor app/`, `Delivery Man App/`, `backend/`, `storefront/`). When you see foreign files in `git status`, **LEAVE THEM DIRTY AND UNTOUCHED**. Do NOT stage them, do NOT commit them, and NEVER run `git restore .` or `git checkout -- .`.

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

---

## 4. Open RFC Tickets from User App AI (2026-09-24)

### [REQ-USERAPP-20260924-001] Fulfillment availability shape: registry vs live service conflict
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `HIGH`
- **Context**: Checkout `shipping_details_widget.dart` renders lane fee + ETA + origin→destination from `POST /api/v1/fulfillment/availability`. Registry §2 documents `delivery: {fee: string, currency, estimated_delivery_time, lane: {origin_lga, destination_lga strings}}`, but live `FulfillmentAvailabilityService.php` returns `fee` float, `estimated_time`, and `origin_lga/destination_lga` OBJECTS `{id,name,state}`. Frontend currently parses the live shape (FF-01, commit `c2ed372b`). One side must change; backend decides.
- **Proposed Endpoint**: No new endpoint — reconcile existing `POST /api/v1/fulfillment/availability`.
- **Required Request Payload**: unchanged (`shop_id?`, `shipping_address_id?`, `cart_items?`).
- **Desired Response Fields**: single locked shape for `fulfillment_options.delivery` and `.pickup` (types for `fee`, ETA key name, LGA object-vs-string, guaranteed `available_times[]`), then mirrored into `API_CONTRACT_REGISTRY.md` §2.

### [REQ-USERAPP-20260924-002] Direct checkout-intent status for crash-recovery polling
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `MEDIUM`
- **Context**: `payment_status_screen.dart` ("Check Again" button) recovers app-killed-during-Paystack by scanning `GET /api/v1/customer/order/list?limit=5` and matching `order_group_id` client-side. Misses when the customer has >5 recent orders.
- **Proposed Endpoint**: `GET /api/v1/checkout/intent/{orderGroupId}/status`
- **Required Request Payload**: path param only, `auth:api` customer scope.
- **Desired Response Fields**:
  ```json
  { "order_group_id": "...", "intent_status": "pending_payment", "payment_status": "...", "authorization_url": null, "orders": [{ "id": 1, "status": "..." }] }
  ```

### [REQ-USERAPP-20260924-003] Cart totals field for §12.2 zero-client-math compliance
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `MEDIUM`
- **Context**: New §12.2 + inbox invariant forbid client-side cart subtotal math, but `GET cart` returns per-item price/discount/tax with no cart-level total, so `cart_screen.dart` sums `amount+tax` for display. Requesting a backend-computed total (or explicit carve-out for display-only line aggregation).
- **Proposed Endpoint**: extend existing cart read (e.g. `GET /api/v1/cart`) — backend decides shape.
- **Required Request Payload**: unchanged.
- **Desired Response Fields**:
  ```json
  { "totals": { "subtotal": "20000.00", "tax": "1500.00", "currency": "NGN" } }
  ```

### [REQ-USERAPP-20260924-004] Registry path corrections: pickup + cashback `/customer/` prefix
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `LOW`
- **Context**: Registry §§4-5 and TICKET-USERAPP-002 document `/api/v1/pickup-reservations/...` and `/api/v1/cashback/...`, but live routes are `/api/v1/customer/pickup-reservations/...` and `/api/v1/customer/cashback/...` (verified in `routes/rest_api/v1/api.php`). App already calls the `/customer/` paths. Requesting registry + ticket text correction only — no code change.
- **Proposed Endpoint**: none (docs-only).
- **Desired Response Fields**: corrected paths in `API_CONTRACT_REGISTRY.md` §§4-5 and `INBOX_USER_APP.md` TICKET-USERAPP-002.

