# VMARKET — VENDOR WEB + VENDOR APP PRODUCTION ALIGNMENT SPECIFICATION

## 0. The Fundamental Rule

The Vendor Web and Vendor App are two interfaces to the same merchant capabilities.

They must not become two different implementations of merchant business logic.

```
                    VMARKET BACKEND
                 SOURCE OF TRUTH
                         │
              ┌──────────┴──────────┐
              │                     │
        VENDOR WEB              VENDOR APP
              │                     │
          Browser              Mobile device
              │                     │
              └──────────┬──────────┘
                         │
                    SAME APIs
                    SAME RULES
                    SAME DATA
                    SAME AUTHORIZATION
```

The backend decides:
- whether the vendor is allowed to perform an action
- which shop/branch they can access
- which products they can modify
- which orders they can see
- whether pickup is available
- whether an order can be accepted
- whether an order can be prepared
- whether an order can be handed to delivery
- whether an order can be released to a customer
- whether a refund/return action is allowed
- whether a user is a vendor owner, manager, worker, etc.

The web/app only requests the action and displays the result.

This follows the general Laravel authorization model: authentication establishes who the caller is, but authorization must determine whether that authenticated user can access or modify the specific resource.

---

## 1. Vendor Architecture

The vendor system should conceptually be:

```
Vendor Account
      │
      ├── Business identity
      │
      ├── Verification
      │
      ├── Shops / Branches
      │       │
      │       ├── Location
      │       ├── Products
      │       ├── Inventory
      │       ├── Orders
      │       ├── Pickup
      │       └── Employees
      │
      ├── Financial records
      │
      └── Marketplace activity
```

Authorization must operate at the actual shop/branch scope.

For example:
```
Vendor A
 ├── Shop Uyo
 │    ├── Employee X
 │    └── Employee Y
 │
 └── Shop Eket
      ├── Employee Z
      └── Employee W
```

Employee X must not automatically gain access to Eket merely because Employee X belongs to Vendor A.

---

## 2. Vendor Identity and Account Lifecycle

### Registration
Vendor registration should be handled by the backend.

Possible lifecycle:
```
registered ──► application_submitted ──► under_review ──► approved ──► active
```
Or:
```
under_review ──► rejected ──► resubmission
```

The client must never decide: `"verification_status": "approved"`. The server determines that.

---

## 3. Vendor Authentication

Both Vendor Web and Vendor App should authenticate against the same backend identity system:

```
Vendor Web ──────┐
                 ├── Backend authentication
Vendor App ──────┘
```

Authentication must support: login, logout, token/session expiration, password reset, account status, disabled account, suspended account, verification state, role/permission loading.

The client must handle 401 Unauthorized and 403 Forbidden differently:
- **401 (Unauthorized)**: Authentication is missing/expired. Clear invalid authentication state ➔ redirect to login.
- **403 (Forbidden)**: User is authenticated but isn't allowed to perform the action. Do NOT log them out. Show an authorization error message.

---

## 4. Vendor Roles & RBAC

The backend should remain authoritative for vendor roles.

Target hierarchy:
```
Vendor Owner
   │
   ├── Branch Manager
   ├── Executive
   ├── Support
   ├── Dispatcher
   └── Content Manager
```

Exact roles follow backend RBAC.

**Critical Principle**: Frontend permissions are presentation controls. Backend authorization is security.

Hiding a button is NOT security. The backend must reject `DELETE /products/123` if the authenticated employee isn't authorized.

---

## 5. Branch/Shop Context

The current branch must be explicit:
- **Vendor App**: `Current Shop: Uyo Branch`
- **Vendor Web**: `Current Shop: Uyo Branch`

Every branch-scoped request must be validated server-side. Never trust `{"shop_id": 999}` merely because the user submitted it. The backend verifies whether the authenticated vendor/employee is authorized for shop 999; if not, return HTTP 403 Forbidden.

---

## 6. Branch Switching

```
Current branch = Uyo ──► Switch to Eket ──► Backend validates authorization ──► Eket becomes active context
```
The client must not simply change a local variable and assume authorization. The backend remains authoritative.

---

## 7. Vendor Dashboard

Dashboard is a read-only aggregation of backend facts (Today's orders, Pending, Preparing, Ready for pickup/delivery, Completed, Cancelled, Returns, Sales, Stock alerts).

The dashboard must NOT independently calculate authoritative financial totals from partial client lists. Display exact values returned by the backend.

---

## 8. Product Management

Vendor product lifecycle:
```
Draft ──► Submitted ──► Approved ──► Published
```
Vendor manages only products they are authorized to manage.

When creating a product, backend validates name, description, category, brand, images, price, SKU, attributes, and shop assignment. Vendor cannot determine marketplace-wide availability.

---

## 9. Product Pricing

Vendor can submit/update their permitted selling price, but client price must NEVER be trusted during checkout. Checkout strictly retrieves authoritative backend prices.

---

## 10. Product Stock & Inventory

Authoritative stock values come from backend inventory:
- Physical stock
- Reserved (pickup)
- Available

The client must not mutate local stock counts (`stock = stock - 1`). Inventory changes are backend atomic operations.

---

## 11. Stock Movement & Events

Supported events: Stock In, Stock Out, Sale, Return, Adjustment, Transfer.

VMarket should not depend on external POS internals without an explicit integration contract (`VMarket ↔ POS`).

---

## 12. Inventory Adjustment Workflows

Employee inventory adjustments require permission checks, manager approvals (where configured by RBAC), and immutable audit logs.

---

## 13. Orders — Central Vendor Workflow

Backend order lifecycle:
```
Paid / Confirmed ──► Vendor receives order ──► Vendor accepts/prepares ──► Ready ──► Fulfillment handoff
```

Delivery and pickup workflows diverge.

---

## 14. Delivery Order Scenario

Vendor sees:
- Order `#VM-1001`
- Fulfillment: `DELIVERY`
- Destination: `Eket LGA`
- Status: `Paid`

Vendor does NOT calculate lane pricing (`Uyo → Eket`). Backend has already determined it.

---

## 15. Delivery Order Preparation

```
Accept ──► Prepare products ──► Mark Ready for Pickup
```
State transitions are backend-defined. Vendor cannot mark an order as customer-delivered if VMarket owns logistics dispatch.

---

## 16. Delivery Handoff

```
Ready for pickup ──► VMarket dispatch ──► Rider assignment ──► Rider collects
```
Vendor cannot assign arbitrary riders, alter delivery fees, create delivery lanes, or change customer destinations.

---

## 17. Pickup Order Scenario

```
Customer ──► Pickup reservation ──► Vendor inspection ──► Accepted ──► Customer pays ──► Paystack server verification ──► Pickup order created ──► Stock deducted ──► OTP ──► Customer collects
```
Vendor UI clearly distinguishes `DELIVERY` from `IN-SHOP PICKUP`.

---

## 18. Pickup Inspection

Actions available to Vendor: `Accept Inspection` or `Reject Inspection`.

Client sends action request to backend. Client cannot mutate `status = inspected_accepted` locally.

---

## 19. Pickup Rejection

Rejection transitions `pending_inspection ──► inspected_rejected`. Vendor cannot mark a rejected reservation as `collected`.

---

## 20. Pickup Acceptance & Settlement

After `inspected_accepted`, customer pays via Paystack. Vendor does not manually mark payment successful; status updates via Paystack server verification/webhook settlement.

---

## 21. Pickup Collection & OTP Verification

Customer presents 6-digit OTP ──► Vendor submits OTP to backend ──► Backend validates OTP ──► Collection completed.

The server validates OTP format and correctness.

---

## 22. Stock Behavior on Pickup

Pickup reservation does NOT deduct stock permanently until successful Paystack digital settlement.

```
Reservation ──► Inspection ──► Accepted ──► Payment ──► Server verification ──► Settlement ──► Stock deduction
```

---

## 23. Cancellation & Refunds

Vendor cannot cancel arbitrary orders or fake refunds by editing order amounts. Cancellations, refunds, and returns are controlled backend financial operations with audit logs.

---

## 24. Delivery Lanes & Delivery Fees

Vendor does NOT configure marketplace delivery lanes (`Uyo → Eket`) or delivery fees. Logistics infrastructure belongs exclusively to VMarket Admin administration.

---

## 25. Vendor Location & Shop Pickup Config

Vendor manages physical shop location (`Country`, `State`, `LGA`, `Address`, `Lat`, `Lng`) with backend validation. Vendor manages permitted shop pickup operating hours and instructions.

---

## 26. Employee Management & Deactivation

Branch managers and staff accounts are scoped strictly to assigned branches. Deactivated employee accounts fail authentication server-side immediately.

---

## 27. Vendor Web vs Vendor App Functional Split

- **Vendor Web**: Best for product management, bulk editing, inventory tables, financial reports, employee management, branch administration.
- **Vendor App**: Best for real-time order notifications, order acceptance, preparation, pickup inspection, 6-digit OTP verification, quick inventory actions.

Both consume identical backend REST APIs.

---

## 28. Network Failures & Concurrency Handling

- **Network Failure**: App displays "Processing..." and handles retries without assuming state mutation until confirmed by backend.
- **Idempotency & Double Tapping**: Backend handles duplicate requests gracefully (`Accept`, `Reject`, `Inspect`, `Collect`).
- **Stale Screen Handling**: If order status changes while screen is open, backend rejects invalid transitions and returns updated state.

---

## 29. Financial Dashboard & Historical Facts

Financial statistics (Gross Sales, Commission, Net Payable, Pending Settlement) come strictly from authoritative backend queries. Historical order snapshots (fees, origin/destination LGAs, prices) remain immutable.

---

## 30. Security & Authorization Test Matrix

The coding AI must test and verify that unauthorized attempts fail:
1. Vendor A ➔ Vendor B product modification (Blocked HTTP 403)
2. Vendor A ➔ Vendor B order viewing/mutation (Blocked HTTP 403)
3. Employee A ➔ Branch B data access (Blocked HTTP 403)
4. Vendor ➔ Delivery lane manipulation (Blocked HTTP 403)
5. Vendor ➔ Fake payment status override (Blocked HTTP 403)
6. Vendor ➔ Fake stock deduction (Blocked HTTP 403)

---

## 31. Direct Instruction for Coding AI

> **Directive for Coding AI**: Do not redesign the Vendor architecture while implementing this plan. First inspect the existing v1 backend, Vendor Web, and Vendor App. Build an implementation/dependency map. Identify which existing code is authoritative, which is legacy, and which is missing. Reuse existing canonical services and APIs. Do not create duplicate order, checkout, payment, pickup, fulfillment, inventory, or authorization engines. Backend remains the source of truth. Every vendor action must be authorized against the authenticated vendor/employee and the specific shop/branch/resource. Frontends must consume backend state rather than calculate authoritative business decisions. Implement the smallest compatible changes required to achieve this specification, then run frontend/backend contract tests, authorization tests, concurrency/idempotency tests, and end-to-end vendor scenarios. Remove obsolete code only after its callers have been migrated and verified.
