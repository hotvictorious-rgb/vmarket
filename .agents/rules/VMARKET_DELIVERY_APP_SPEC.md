# VMARKET DELIVERYMAN APP ↔ BACKEND PRODUCTION ALIGNMENT SPECIFICATION

## 1. Core Principle

The Deliveryman App is a field-operations application.

It must never become the source of truth for:
- delivery availability
- delivery fees
- order ownership
- customer ownership
- shop ownership
- payment status
- stock
- delivery eligibility
- rider authorization
- delivery assignment
- order totals
- fulfillment rules
- cashback
- refunds
- settlement
- geography
- hub configuration

The backend is authoritative.

The Deliveryman App should primarily: Display backend-authorized work, collect operational input, submit actions, and display the resulting server state.

Architecture:
```
                    VMARKET BACKEND
                  SOURCE OF TRUTH
                         │
        ┌────────────────┼────────────────┐
        │                │                │
        ↓                ↓                ↓
   CUSTOMER APP      VENDOR APP      DELIVERYMAN APP
                                      │
                                      ↓
                               Field Operations
```

The Deliveryman App must not independently recreate backend business logic.

---

## 2. Deliveryman Identity

The rider must authenticate through the VMarket backend.

The app should obtain: user, rider profile, delivery company / logistics company, authorized hub(s), role, permissions, status.

The backend determines whether the user is actually a deliveryman. Never trust `role = deliveryman`, `rider_id`, `company_id`, or `hub_id` supplied by the app as authoritative identity. The authenticated session/token determines identity.

---

## 3. Rider Lifecycle

The rider account should have server-controlled states (`pending`, `active`, `suspended`, `inactive`, `blocked`).

- **Active**: Rider can receive/execute assigned delivery work.
- **Suspended**: App allows login to display state, but operational actions are rejected by backend.
- **Inactive**: No new operational work.
- **Blocked**: Access denied according to backend policy.

The Flutter app must not simply hide buttons and assume that constitutes security. Backend enforces authorization.

---

## 4. Rider Profile

The rider views relevant profile information (Name, Phone, Profile photo, Rider ID, Logistics company, Assigned hub, Status, Vehicle info).

Only backend-approved fields are displayed. Rider cannot edit company, status, assigned hub, commission rules, or delivery permissions.

---

## 5. Home / Dashboard

Dashboard figures (Active deliveries, Pending pickup, Ready for delivery, Completed today) must come directly from backend APIs. Do not calculate totals by combining locally cached records.

---

## 6. Delivery Work Queue

The rider sees work matching canonical backend delivery states (`assigned`, `accepted`, `awaiting_pickup`, `picked_up`, `in_transit`, `arriving`, `delivered`, `failed`, `returned`). Flutter must not invent a separate state machine.

---

## 7. Assignment Lifecycle

```
Order ready ──► Dispatch ──► Rider assignment ──► Rider receives ──► Rider accepts ──► Pickup ──► Transit ──► Delivery
```

The app cannot assign itself a delivery. Backend verifies authenticated rider, delivery ID, assignment ownership, rider authorization, and delivery state before allowing acceptance.

---

## 8. New Assignment Notification

Push notifications deep-link to the backend record. App displays reference number, pickup shop/location, destination LGA, package info, delivery instructions, and status. Sensitive information is exposed only when operationally necessary.

---

## 9. Accepting an Assignment

```
assigned ──► rider accepts ──► accepted
```
Backend verifies rider owns assignment, assignment is active, delivery hasn't been reassigned/cancelled, and rider isn't suspended. Backend changes state; app refreshes.

---

## 10. Concurrency & Race Conditions

If two riders receive an assignment simultaneously and tap Accept, backend ensures only the authorized assignment succeeds. The second request receives `Assignment no longer available` and app refreshes. Flutter local state is NOT concurrency control.

---

## 11. Merchant Pickup Handoff

```
Accepted ──► Arrived at pickup ──► Pickup verification ──► Package handed over ──► Picked up
```
Backend validates correct delivery, rider, shop, state, merchant readiness, package eligibility, OTP/verification, and zero prior pickup or cancellation.

---

## 12. Pickup Verification

Pickup verification uses backend-required mechanisms (Order reference, OTP, QR/barcode, Vendor confirmation). Flutter displays what backend requires.

---

## 13. Package Information & Multi-Package Handling

Rider receives package count and details for transit. Backend remains authoritative for package count and items. Rider cannot change quantities, prices, order totals, or stock.

---

## 14. Pickup Failure Scenarios

- **Vendor Not Ready**: Rider reports issue ➔ Backend records event.
- **Wrong Package / Discrepancy**: Rider reports ➔ Operational exception created.
- **Shop Closed**: Rider reports failed pickup ➔ Backend records reason for admin rescheduling.

Rider cannot mark an order as "cancelled". Cancellation authority belongs to backend workflows.

---

## 15. After Pickup & Navigation

```
picked_up ──► in_transit
```
App displays navigation assistance for transit. Backend remains responsible for delivery state.

---

## 16. Customer Destination Control

App receives destination determined by backend (`Origin LGA ──► Destination LGA`). Rider cannot edit delivery address. Address changes must go through backend/admin/customer validation workflows to prevent fraud.

---

## 17. Logistics Hubs vs Marketplace Geography

- **Marketplace Geography**: `Country ──► State ──► LGA` (Customer & Order Destination).
- **Internal Logistics Infrastructure**: `Hubs ──► Dispatch ──► Riders` (Rider Operations).

Riders do not configure delivery lanes or create hubs.

---

## 18. Delivery Tracking

Location data submission is server-controlled (when tracking starts, stops, retention, and visibility). App does not continuously transmit location unless tied to active operational rules.

---

## 19. Customer Delivery Handover & 6-Digit OTP

```
Customer receives OTP ──► Rider asks customer ──► Rider enters OTP ──► Backend verifies ──► Delivery completed
```
The app MUST NOT verify OTP locally (`if (otp == localOtp)` is FORBIDDEN). Server verifies OTP format and correctness.

---

## 20. Successful Delivery Settlement

```
in_transit ──► delivery verification ──► delivered
```
Only backend finalizes delivery, updating order, financial state, cashback eligibility, notifications, and audit trails.

---

## 21. Failed Delivery Triage

Allowed reason codes are server-controlled (`Customer unavailable`, `Incorrect address`, `Customer refused`, `Phone unreachable`, `Access issue`, `Shop closed`).

- **Customer Unavailable**: Rider reports failed attempt ➔ Backend records attempt for rescheduling.
- **Customer Refusal**: Rider reports refusal ➔ Backend triggers `return_to_shop` workflow.

---

## 22. Return-to-Vendor Workflow

```
Delivery failed ──► Return authorized ──► Rider collects package back ──► Return transit ──► Vendor receives ──► Backend records return
```
Rider app executes return assignment. Rider cannot issue refunds. Return $\neq$ Refund.

---

## 23. Damaged Package & Cancellations

Rider reports damage/evidence ➔ Backend records incident ➔ Order enters exception workflow. Platform decides financial consequences. Rider cannot cancel orders or issue refunds.

---

## 24. Payment Visibility & Cash Handling

Rider views operational status only (`Payment: Paid`). In VMarket prepaid digital model, rider does NOT collect cash on delivery unless an official backend COD workflow is introduced.

---

## 25. Delivery vs In-Shop Pickup Isolation

`In-Shop Pickup` orders are handled by customer and merchant in-store. Deliveryman App only processes orders with `fulfillment_type = delivery`.

---

## 26. Push Notifications & Deep Links

Push notifications deep-link into backend records. Upon opening, app fetches authoritative current backend state to prevent acting on stale notifications.

---

## 27. Offline Behavior & Idempotency

App caches loaded assignment info for display. Dangerous actions (`Mark delivered`, `Accept assignment`, `Confirm pickup`, `Verify OTP`) require backend confirmation and idempotent retry handling.

---

## 28. Stale State & Reassignment Handling

If an order is reassigned by admin/dispatch while rider views screen, rider action is rejected by backend (`Assignment no longer available`). App refreshes UI to server truth.

---

## 29. Security & API Authorization Matrix

Every Deliveryman API enforces:
```
authenticated user + deliveryman role + active account + authorized company/hub + authorized delivery + valid state transition
```
Rider A cannot access Rider B's delivery, unauthorized hubs, or arbitrary customer data.

---

## 30. Forbidden Actions Matrix for Deliveryman App

The AI MUST NOT implement:
- ❌ Delivery fee calculation
- ❌ Delivery availability/eligibility calculation
- ❌ LGA lane creation or route creation
- ❌ Hub creation
- ❌ Rider self-assignment logic
- ❌ Order pricing or stock deduction
- ❌ Local OTP verification
- ❌ Payment status override or cash collection
- ❌ Cashback calculation or refund authorization
- ❌ Customer address mutation or arbitrary cancellation

---

## 31. Definition of "Aligned"

The Deliveryman App is aligned ONLY when:
```
Backend business rule
        ↓
Backend API contract
        ↓
Flutter model & service
        ↓
Flutter UI action
        ↓
Backend authorization & state transition
        ↓
Flutter receives authoritative result & updates UI
```
