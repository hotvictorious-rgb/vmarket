# Sync Channel: Delivery Rider Mobile App ↔ Backend

> **Actor Focus**: Delivery Logistics Riders & Couriers  
> **Client Framework**: Flutter / GetX (`Delivery Man App/`)  
> **Protocol Rules**: Strictly follow [`.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md) and [`.agents/sync/API_CONTRACT_REGISTRY.md`](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/.agents/sync/API_CONTRACT_REGISTRY.md).

> [!CAUTION]
> **MANDATORY MULTI-AGENT GIT COMMIT DIRECTIVE (NON-NEGOTIABLE)**:
> **EVERY AI MUST STAGE AND COMMIT ONLY ITS OWN SPECIFIC CHANGES — NEVER COMMIT ALL FILES!**  
> 1. You MUST explicitly name only your own modified files: `git add <exact-file-path-1> <exact-file-path-2> AI_CHANGELOG.md`.
> 2. Running `git add .`, `git add -A`, or `git commit -a` is **STRICTLY FORBIDDEN**.
> 3. Concurrent AIs are actively modifying files in other directories (`User app/`, `Vendor app/`, `Delivery Man App/`, `backend/`, `storefront/`). When you see foreign files in `git status`, **LEAVE THEM DIRTY AND UNTOUCHED**. Do NOT stage them, do NOT commit them, and NEVER run `git restore .` or `git checkout -- .`.

---

## 1. Client Invariants (Mandatory for Delivery App AI)
1. **Cash-on-Delivery (COD) Prohibition**: Riders are strictly prohibited from collecting cash payments in V1. All orders must be prepaid digitally via Paystack before dispatch.
2. **Authoritative State Transitions**:
   - `assigned`: Rider assigned by dispatcher.
   - `picked_up`: Rider scans merchant waybill / collection code at shop.
   - `in_transit`: Rider moving along designated corridor / hub.
   - `out_for_delivery`: Rider en route in customer destination LGA.
   - `delivered`: **Rider must enter customer's 6-digit delivery verification OTP.**
3. **Proof of Delivery (POD) Handshake**: An order cannot transition to `delivered` without entering the customer's 6-digit OTP into the Rider app. This protects both riders and merchants against disputes.
4. **Logistics Infrastructure Separation**: Hubs (`DeliveryHub`) are internal aggregation parks/landmarks managed by Super Admin and dispatchers—never selectable by public shoppers.

---

## 2. Active Communication & RFC Tickets

### [TICKET-DELIVERY-001] Proof of Delivery (POD) 6-Digit OTP Verification
- **Status**: `FULFILLED`
- **Request**: Rider arrives at customer destination address; needs to complete delivery and prove handover.
- **Backend Fulfillment**:
  - Endpoint: `POST /api/v2/delivery-man/order/complete`
  - Body:
    ```json
    {
      "order_id": 100234,
      "verification_code": "849201"
    }
    ```
  - Backend verifies cryptographic OTP against `orders.verification_code`. Transitions order status to `delivered`.

### [TICKET-DELIVERY-002] Real-time Delivery GPS Tracking Stream
- **Status**: `FULFILLED`
- **Request**: Rider app streams current latitude/longitude to backend while on `in_transit` or `out_for_delivery`.
- **Backend Fulfillment**:
  - Endpoint: `POST /api/v2/delivery-man/record-location-data`
  - Appends to immutable `tracking_events` table for customer tracking view.

---

## 3. Template for New Request Tickets (Copy & Paste to Append Below)
```markdown
### [REQ-DELIVERY-YYYYMMDD-###] <Feature / Endpoint Title>
- **Status**: `PENDING_BACKEND_REVIEW`
- **Urgency**: `LOW` | `MEDIUM` | `HIGH` | `BLOCKER`
- **Context**: <Explain rider operational flow or mobile app requirement>
- **Proposed Route**: `POST /api/v2/delivery-man/...`
- **Payload / Schema**:
  ```json
  { ... }
  ```
```

