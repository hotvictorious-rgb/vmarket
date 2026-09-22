# VMarket Production Alignment Plan
## Customer App ↔ Backend — Full 34-Phase Reference

> **This is the detailed reference document.** The enforcing rule file is `.agents/rules/CUSTOMER_APP_ALIGNMENT.md`.
> All AIs working on the Customer App must follow both files.

---

## The Production Architecture

```
                         VMARKET BACKEND
                       SOURCE OF TRUTH
                              |
        +---------------------+---------------------+
        |                     |                     |
    CUSTOMER API          CUSTOMER DATA        BUSINESS RULES
        |                     |                     |
        +---------------------+---------------------+
                              |
                         +----+----+
                         |         |
                    User App   Other Apps
```

The Customer App is a **client** of the backend — not a second implementation of VMarket.

---

## PHASE 0 — Freeze the Target Architecture

### Geography

```
Country -> State -> LGA
```

No customer-facing: Hub, Route, Corridor, Ward, Area, Zone (for marketplace fulfillment).

### Delivery

```
Shop Origin LGA -> Destination LGA -> Directional Delivery Lane -> Available/Unavailable -> Fee + ETA
```

`Uyo -> Eket` and `Eket -> Uyo` are **separate capabilities**.

### Pickup

```
Shop -> Pickup enabled? -> Reservation allowed? -> Inspection -> Payment -> Order -> OTP -> Collection
```

Pickup does **not** use delivery lanes.

---

## PHASE 1 — Repository-Wide Customer/Backend Inventory

Before modifying anything, create an impact map. For every customer domain, identify:

- Frontend screen
- Frontend controller
- Frontend model
- Frontend repository/service
- API endpoint
- Backend route
- Backend controller
- Backend request validation
- Backend service
- Backend model
- Database tables
- Authorization
- Tests
- Legacy implementation

### Customer Domains

Authentication, Profile, Addresses, Geography, Home, Categories, Search, Products,
Wishlist, Cart, Checkout, Payment, Delivery, Pickup, Orders, Order details, Tracking,
Returns, Refunds, Reviews, Cashback, Notifications, Support.

### Impact Map Format

```
CHECKOUT

Customer App
 +-- CheckoutScreen
 +-- CheckoutController
 +-- CheckoutModel
 +-- CheckoutService
          |
          v
API
 +-- POST /checkout/intent
          |
          v
Backend
 +-- Controller
 +-- Request
 +-- CheckoutIntent
 +-- FulfillmentAvailabilityService
 +-- PaymentRequest
 +-- Settlement
```

---

## PHASE 2 — API Contract Audit

For every endpoint, compare Frontend expects vs Backend actually provides:

| Dimension | Frontend | Backend |
|---|---|---|
| URL | expected path | actual route |
| HTTP Method | expected verb | actual verb |
| Headers | expected | actual |
| Authentication | expected | actual |
| Request Body | field names, types, nullable | validation rules |
| Response Structure | model shape | serializer output |
| Error Structure | expected error format | actual exceptions |
| Pagination | expected format | actual format |

**Example mismatch to eliminate:**
- Flutter expects `delivery_fee` -> Backend returns `shipping_cost`
- Flutter calls `POST /digital-payment` -> Backend's authoritative route is `POST /checkout/intent/{orderGroupId}/pay`

---

## PHASE 3 — Authentication

Test the full lifecycle: Register, Login, Logout, Token refresh, Expired token, Invalid token, Account disabled, Unauthorized API, Password/OTP recovery.

Backend must derive customer from authentication — client must NOT supply `customer_id` for sensitive operations.

```
authenticated user -> customer account -> authorized resources
```

---

## PHASE 4 — Customer Profile

Align `GET profile` and `UPDATE profile`. Fields must be identical.

Test: valid update, invalid data, unauthorized request, duplicate email/phone, account status, returned profile after update.

---

## PHASE 5 — Address System

Canonical structure: `country_id -> state_id -> lga_id -> address -> (optional coordinates)`

Backend verifies: state belongs to country, LGA belongs to state, country/state/LGA active, customer owns address.

| Input | Result |
|---|---|
| Nigeria -> Akwa Ibom -> Uyo | Valid |
| Nigeria -> Akwa Ibom -> Eket | Valid |
| Nigeria -> Lagos -> Uyo | Reject |
| Inactive LGA | Reject |
| Another customer's address ID | Reject |

---

## PHASE 6 — Product Browsing

Marketplace must be browseable without forcing a delivery location selection first.

```
Home -> Categories -> Search -> Product
```

Backend distinguishes `product exists` from `product currently fulfillable to this customer`. Do not simply hide unavailable products.

---

## PHASE 7 — Product Detail

Backend owns: name, description, price, images, seller, shop, stock, attributes, variants, pickup eligibility, delivery eligibility.

Frontend must never be authoritative for: price, stock, seller ownership, delivery fee, commission, discount, cashback calculation.

---

## PHASE 8 — Cart

Test: Add item, Remove item, Increase/decrease quantity, Clear cart, Refresh cart, Out-of-stock item, Price changed, Product disabled, Shop disabled.

Backend recalculates authoritatively. Frontend says `quantity = 2`, backend has `quantity available = 1` -> checkout must NOT proceed with 2.

Cart must preserve: product, shop, seller, quantity, price, variant.

---

## PHASE 9 — Multi-Vendor Cart

```
Cart

Shop A
 +-- Product 1
 +-- Product 2

Shop B
 +-- Product 3
 +-- Product 4
```

Customer must be able to select fulfillment independently per shop (Shop A -> Delivery, Shop B -> Pickup). Do not force entire cart = same fulfillment.

---

## PHASE 10 — Fulfillment Availability

Backend check flow:
```
customer address -> destination LGA -> shop LGA -> directional lane
-> shop active -> merchant eligible -> product purchasable -> stock
```

Success response:
```json
{ "available": true, "fee": "1500.00", "estimated_delivery_time": "24-48 hours" }
```

Failure response:
```json
{ "available": false, "reason": "origin_destination_lane_not_served" }
```

Flutter only renders this — it does not calculate it.

---

## PHASE 11 — Delivery Scenario Matrix

| Origin | Destination | Expected |
|---|---|---|
| Uyo | Uyo | According to lane |
| Uyo | Eket | According to lane |
| Eket | Uyo | **Independent of Uyo->Eket** |
| Uyo | Unsupported LGA | Unavailable |
| Inactive origin | Any | Unavailable |
| Any | Inactive destination | Unavailable |
| Lane disabled | Any | Unavailable |
| Lane enabled | Destination | Available |

Critical: `Uyo -> Eket = enabled` does NOT mean `Eket -> Uyo = enabled`.

---

## PHASE 12 — Pickup Flow

```
Product -> Pickup option -> Reserve -> Reservation pending -> Visit shop -> Inspection -> Accepted/Rejected
```

If rejected: `pending_inspection -> inspected_rejected` (no payment settlement).

If accepted: `pending_inspection -> inspected_accepted -> Pay Now -> Paystack -> server verification -> pickup order -> stock deduction -> OTP -> collection`

---

## PHASE 13 — Pickup Security

Customer isolation: A cannot access B's reservation, A cannot pay B's reservation, customer cannot modify shop/seller/price, customer cannot fake inspection acceptance.

Vendor isolation: Branch 1 cannot inspect Branch 2 reservations; cannot inspect another merchant's reservations.

---

## PHASE 14 — Checkout Intent

```
Cart -> Address -> Fulfillment selection -> POST /checkout/intent
```

Backend flow:
```
authenticate customer -> load actual cart -> validate products/shops/stock
-> validate destination -> validate fulfillment -> calculate authoritative prices
-> calculate delivery fee -> calculate cashback eligibility -> create checkout snapshot
```

The frontend's prior availability response is NOT trusted. Backend re-checks.

---

## PHASE 15 — Checkout Snapshot

Once created, snapshot preserves (immutably):
- Shop, seller, fulfillment type
- Origin: country, state, LGA
- Destination: country, state, LGA, address
- Delivery lane, delivery fee, ETA
- Pickup information
- Product prices and quantities
- Cashback information, total

Admin changing lane fee AFTER snapshot creation does not affect the customer's snapshot.

---

## PHASE 16 — Payment

```
Checkout -> Payment initialization -> Paystack -> payment result -> backend verification
```

NEVER: Flutter says payment succeeded -> order immediately considered paid.

Authoritative flow: `Paystack -> Backend verification/webhook -> PaymentRequest -> Settlement -> Order`

---

## PHASE 17 — Payment Failure Scenarios

Test all: User cancels, Payment fails, Network disconnects, App closes, Callback delayed, Callback duplicated, User presses Pay twice, Webhook duplicated, Amount mismatch, Wrong currency, Expired checkout, Already-paid checkout.

Expected behavior must come from backend state.

---

## PHASE 18 — Stock

| Fulfillment | Deduction Trigger |
|---|---|
| Delivery | Successful payment settlement |
| Pickup | Successful pickup payment settlement |

Reservation alone must NOT deduct stock.

Concurrency: Stock = 1, two customers pay simultaneously -> only one succeeds (pessimistic lock: `lockForUpdate()`).

---

## PHASE 19 — Order Creation

Backend returns the authoritative order. Flutter does NOT reconstruct order from cart data after payment.

Order response must include: order ID, order group, status, shop, items, prices, fulfillment details, delivery/pickup information, payment state, timestamps.

---

## PHASE 20 — Order State Machine

Delivery:
```
PENDING PAYMENT -> PAID -> PROCESSING -> READY FOR DELIVERY -> OUT FOR DELIVERY -> DELIVERED
```

Pickup:
```
PENDING INSPECTION -> INSPECTION ACCEPTED -> AWAITING PAYMENT -> PAID -> READY FOR COLLECTION -> COLLECTED
```

Rejection:
```
PENDING INSPECTION -> INSPECTION REJECTED
```

Frontend renders backend states — it does NOT invent its own state machine.

---

## PHASE 21 — Order Details

Customer must see: Items, Quantity, Price, Shop, Fulfillment, Delivery fee, Total, Payment status, Order status, Pickup details (if applicable), Delivery tracking (if applicable), Cashback earned.

---

## PHASE 22 — Delivery Tracking

Customer-visible only:
```
Order confirmed -> Preparing -> Picked up -> On the way -> Delivered
```

Do NOT expose: hub IDs, internal routing, dispatcher structures, private rider data.

---

## PHASE 23 — Pickup Collection (OTP)

- OTP must be backend-generated and backend-verified.
- Customer App displays the OTP.
- Backend verifies it.
- Flutter may NOT unilaterally mark a pickup as collected.

---

## PHASE 24 — Cashback

Frontend displays: cashback earned, Victorious Points balance.

Backend determines: eligibility, amount, award timing, redemption, reversal.

Customer cannot submit `cashback_amount = 5000` and have backend accept it.

---

## PHASE 25 — Returns / Refunds / Exchanges

**Return:** `Order -> Eligible? -> Return request -> Backend validation -> Review/approval -> Return processing`

**Refund:** Backend determines original payment method and records financial event.

**Exchange:** Must clearly define original item, replacement item, stock, price difference, payment/refund, status.

Do not let frontend improvise these workflows.

---

## PHASE 26 — Notifications

Backend events that must map to customer notifications:
Order confirmed, Payment confirmed, Payment failed, Pickup inspection ready, Pickup accepted, Pickup rejected, Ready for collection, Rider assigned, Out for delivery, Delivered, Return approved, Refund processed, Cashback awarded.

Frontend consumes the notification API — it does not infer events locally.

---

## PHASE 27 — Error Contract

Consistent backend error format:
```json
{
  "success": false,
  "code": "origin_destination_lane_not_served",
  "message": "Delivery is not available for this destination."
}
```

Flutter maps known error codes to appropriate UI. Do NOT build logic around fragile human-readable strings.

---

## PHASE 28 — Offline/Network Behavior

Test: Poor network, Request timeout, App killed during checkout, App reopened after payment, Duplicate tap, Slow API, Paystack succeeds while app is offline.

Recovery pattern:
```
Customer pays -> internet disappears -> app closes -> app opens later
-> GET orders -> backend says PAID -> app displays correct state
```

NEVER assume: "The callback reached Flutter, therefore payment succeeded."

---

## PHASE 29 — Security Testing

Attempt all — every one should fail safely:
- Customer A -> Customer B address/order/reservation
- Customer -> fake shop ID / seller ID
- Customer -> fake delivery fee / origin LGA / destination
- Customer -> fake cashback amount / fake price
- Customer -> another customer's payment

---

## PHASE 30 — Legacy Cleanup

Search the Customer App for: `digital-payment`, `CartShipping`, `shipping_cost`, `delivery_city`, `delivery_state`, `delivery_hub`, legacy shipping models.

Classify each: KEEP | MIGRATE | DEPRECATE | REMOVE.

Do NOT leave two simultaneously valid checkout or shipping implementations.

---

## PHASE 31 — Frontend Model Cleanup

Every Dart model must correspond to an actual backend contract. Remove models that represent dead APIs (e.g., `DeliveryCityModel` if backend has migrated to LGA).

---

## PHASE 32 — Production Test Matrix

| Domain | Scenarios |
|---|---|
| Customer | Register, Login, Logout, Profile, Address |
| Discovery | Home, Category, Search, Product |
| Cart | Add, Remove, Quantity, Multi-vendor, Out-of-stock |
| Delivery | Same LGA, Cross LGA, Unsupported LGA, Directionality, Fee, ETA |
| Pickup | Reserve, Inspect, Accept, Reject, Pay, OTP, Collect |
| Checkout | Single vendor, Multi-vendor, Delivery, Pickup, Mixed fulfillment |
| Payment | Success, Failure, Cancel, Timeout, Duplicate, Webhook, App crash |
| Orders | Created, Processing, Delivery, Pickup, Completed |
| Post-order | Return, Refund, Exchange, Cashback |
| Security | IDOR, Fake price, Fake fee, Fake shop, Fake customer, Fake address, Fake fulfillment, Fake payment |

---

## PHASE 33 — Golden End-to-End Tests (Mandatory)

| # | Scenario | Required Result |
|---|---|---|
| 1 | Uyo customer + Uyo shop + Uyo->Uyo lane enabled | Delivery available |
| 2 | Uyo shop + Eket customer + Uyo->Eket enabled | Delivery available |
| 3 | Eket shop + Uyo customer + Eket->Uyo **disabled** | Delivery **unavailable** |
| 4 | Reserve -> Inspection accepted -> Paystack -> Verify -> Order -> Stock deduction -> OTP -> Collect | Full pickup flow |
| 5 | Shop A (Delivery) + Shop B (Pickup) — one checkout, two order groups | Mixed fulfillment works |
| 6 | Two payment attempts on same checkout | Only one valid settlement |
| 7 | Stock = 1, two simultaneous customers paying | Only one succeeds |
| 8 | Availability = YES -> admin disables lane -> checkout intent | Checkout rejected |
| 9 | Customer A attempts Customer B order/address/reservation | 403/404 |
| 10 | Paystack succeeds -> app crashes -> reopen -> fetch backend state | Correct order/payment shown |

---

## PHASE 34 — Definition of "Aligned"

We do not say "the frontend works" until all layers agree:

```
CUSTOMER APP
     | (exact contract)
     v
API ROUTES -> CONTROLLERS -> VALIDATION -> SERVICES -> DATABASE
```

And in reverse:

```
DATABASE -> BUSINESS RESULT -> API RESPONSE -> FRONTEND MODEL -> CONTROLLER -> SCREEN -> CUSTOMER
```

**Every layer must agree.**

---

## Mandatory Phase Completion Checklist

After **every** change, the AI must produce a report:

1. Files changed
2. APIs changed
3. Database changes
4. Frontend/backend contract changes
5. Tests added
6. Tests executed
7. Legacy code removed
8. Legacy code intentionally retained (with reason)
9. Security issues found
10. Remaining mismatches
11. Git diff reviewed
12. No unrelated changes confirmed

**A change is not complete without this report.**

---

## The Final Gate

> *For every customer journey supported by VMarket, the Customer App and backend have been traced from **screen -> API -> controller -> service -> database -> response -> screen**, tested for success/failure/security/concurrency, and there is **exactly one authoritative implementation**.*

No AI may declare the Customer App production-ready without satisfying this gate.
