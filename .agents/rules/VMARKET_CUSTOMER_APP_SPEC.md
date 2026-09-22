# VMarket Production Alignment Specification
## Customer App ↔ Backend
### Canonical Production Reference — V1

> **CANONICAL ALIGNMENT DOCUMENT — ALL AIs MUST READ AND FOLLOW THIS BEFORE MAKING ANY CHANGES TO THE CUSTOMER APP OR ITS BACKEND CONTRACTS.**
>
> This is the **target production contract**, not a claim that every item is already implemented correctly in v1. The AI must audit the current code against this document, identify mismatches, then fix them **without creating duplicate systems**.
>
> Order of work: Customer App first, then Vendor App, Delivery App, Admin Web, and Marketplace Web separately.

---

## 1. PURPOSE

This document defines how the VMarket Customer App must communicate with and behave against the VMarket backend.

**The backend is the authoritative source of truth. The Customer App is a client.**

The Customer App MUST NOT independently implement authoritative marketplace business rules.

### The Customer App MAY:
- Display backend data
- Collect customer input
- Perform UI validation
- Calculate temporary display values for UX
- Request backend operations
- Display backend decisions
- Maintain local UI state
- Cache safe read-only data

### The Customer App MUST NOT decide:
- Whether a product is purchasable
- Whether stock exists
- Whether delivery is available
- Whether pickup is available
- Delivery fee
- Cashback eligibility
- Final product price
- Final cart price
- Payment success
- Order creation success
- Order ownership
- Refund eligibility
- Return eligibility
- Fulfillment eligibility
- Merchant authorization
- Shop authorization
- Customer authorization
- Payment settlement
- Stock deduction

The backend must independently validate every authoritative operation.

---

## 2. SYSTEM BOUNDARY

```
CUSTOMER APP
     |
     | HTTPS / authenticated API
     v
VMARKET BACKEND
     |
     +---- Database
     +---- Payment Provider (Paystack)
     +---- Inventory
     +---- Fulfillment
     +---- Orders
     +---- Cashback
     +---- Notifications
     +---- Delivery
     +---- Pickup
     +---- Audit / financial records
```

The Customer App does NOT communicate directly with: vendor databases, delivery databases, payment databases, internal hub systems, internal rider systems, or merchant POS systems. The backend is the integration boundary.

---

## 3. CANONICAL CUSTOMER JOURNEY

```
Discover -> Product -> Cart -> Address -> Fulfillment availability
-> Checkout intent -> Payment -> Server verification -> Order
-> Fulfillment -> Completion -> Cashback / post-order services
```

The Customer App must not skip authoritative backend stages merely because a previous UI stage appeared successful.

---

## 4. AUTHENTICATION

### Customer Registration

Customer enters: name, email/phone, password or OTP credentials.

Backend: validates input, checks uniqueness, creates customer, establishes session/token, returns authenticated customer information.

Customer App: stores credentials securely, loads authenticated profile, must NOT construct customer IDs manually.

### Login Flow

```
Customer App -> Login API -> Backend authentication -> Token/session -> Customer App
```

The app must handle: invalid credentials, expired credentials, revoked credentials, network failure, rate limiting, account disabled, server errors.

The app must never assume that a locally stored token means the account is still valid.

---

## 5. CUSTOMER PROFILE

Every profile mutation must be authorized by the backend using the authenticated identity.

The client must NOT submit an arbitrary `customer_id` and expect the backend to trust it.

```
authenticated_user = actual customer   (NOT: customer_id supplied by Flutter = customer)
```

---

## 6. PRODUCT DISCOVERY

Customers can browse the marketplace without first selecting a delivery destination.

```
PRODUCT VISIBILITY  ≠  DELIVERY AVAILABILITY
```

A product may be visible even when it cannot currently be delivered to a particular customer. The backend remains authoritative for actual purchasability.

---

## 7. PRODUCT DETAILS

The app must display the backend's current product information. The app must not permanently trust cached price, stock, seller status, or product status. Before a purchase, the backend must revalidate.

---

## 8. PRODUCT AVAILABILITY

A product can be: active, inactive, unavailable, out of stock, restricted, or unavailable for a particular fulfillment method.

The Customer App must distinguish `product exists` from `product is currently purchasable`. The backend makes the final determination.

---

## 9. CART

The backend must validate the cart whenever it becomes relevant to an authoritative transaction.

Cart validation must cover: product existence, product active status, shop relationship, current price, stock, quantity, purchase restrictions, customer ownership, fulfillment eligibility.

The client must not assume `cart quantity × displayed price` is the final payable amount.

---

## 10. CART PRICE CHANGES

If the product price changes after the customer added it to the cart:

```
Old cart price -> Backend validation -> Current authoritative price
```

The backend determines the valid price. Customer App must show the updated amount and require confirmation. Never allow the frontend to force an old price.

---

## 11. STOCK

Stock is backend authoritative. The Customer App may display stock information.

Stock is NOT reserved simply because the customer: opened a product, added to cart, selected pickup, created an address, or started checkout — unless the backend explicitly creates a stock reservation.

---

## 12. CUSTOMER ADDRESS ARCHITECTURE

Canonical geography: `Country -> State -> LGA`

Customer address also contains: actual address, latitude, longitude (where supported).

The Customer App must NOT expose: delivery hubs, internal routes, rider routes, dispatch zones, internal corridors to the customer as marketplace geography.

---

## 13. ADDRESS CREATION

Customer selects: `Country -> State -> LGA -> Free-text address`

Backend validates: State belongs to Country, LGA belongs to State, Country/State/LGA are active.

Frontend validation is not security. The backend must still validate all combinations.

---

## 14. ADDRESS OWNERSHIP

A customer can only create, view, edit, delete, and select their own addresses.

Customer A must never be able to use an `address_id` belonging to Customer B. The backend must reject this. The frontend must not rely on simply hiding another customer's address.

---

## 15. CHECKOUT ADDRESS

At checkout, the backend must revalidate the address. The Customer App must not send `customer address ownership assumptions`, `origin LGA`, `delivery fee`, or `delivery capability` as authoritative values.

---

## 16. SHOP ORIGIN

The Customer App must NOT determine a shop's origin. The backend determines:

```
Cart Item -> Product -> Shop -> Shop canonical country/state/LGA
```

The client must never send `"origin_lga_id": 17` and expect the backend to trust it.

---

## 17. FULFILLMENT

Every order group must determine its fulfillment mode. Supported marketplace fulfillment: **DELIVERY** or **PICKUP**. Pickup is not delivery. Delivery is not pickup.

---

## 18. DELIVERY AVAILABILITY

The backend evaluates:

```
SHOP ORIGIN LGA -> DELIVERY LANE -> CUSTOMER DESTINATION LGA
```

`Uyo -> Eket`, `Eket -> Uyo`, and `Uyo -> Uyo` are **independent directional lanes**.

If `Uyo -> Eket = enabled` that does NOT imply `Eket -> Uyo = enabled`.

---

## 19. DELIVERY AVAILABILITY API

The Customer App may request fulfillment availability using customer-owned information only:

```json
{
  "address_id": 123,
  "cart_item_ids": [10, 11, 12]
}
```

The backend determines: shop, origin LGA, destination LGA, delivery lane, delivery fee, ETA, pickup availability, restrictions. The backend response is authoritative.

---

## 20. DELIVERY AVAILABILITY RESPONSE

Available:
```json
{
  "shop_id": 12,
  "delivery": {
    "available": true,
    "lane_id": 7,
    "fee": "1500.00",
    "estimated_delivery_time": "24-48 hours"
  },
  "pickup": {
    "available": true,
    "reservation_required": true
  }
}
```

Unavailable:
```json
{
  "delivery": {
    "available": false,
    "reason": "origin_destination_lane_not_served"
  }
}
```

The Customer App displays this decision. It must not recreate it.

---

## 21. DELIVERY FEE

The backend determines the authoritative delivery fee. The customer cannot submit `delivery_fee = 500` and make the backend accept it.

---

## 22. DELIVERY ETA

ETA returned by the backend is authoritative for the checkout snapshot. The frontend must not independently calculate an official ETA.

---

## 23. DELIVERY CHECKOUT

```
Cart -> Address -> Fulfillment availability -> Checkout intent -> Payment initialization
-> Paystack -> Server verification -> Order settlement -> Stock deduction -> Vendor preparation -> VMarket delivery
```

---

## 24. CHECKOUT INTENT

The Customer App requests:

```
POST /api/v1/checkout/intent
```

The backend must revalidate: customer, cart, products, prices, quantities, stock, shops, shop status, geography, fulfillment, delivery lane, delivery fee, pickup eligibility, cashback redemption if applicable.

A previous availability response is NOT sufficient authorization for checkout.

---

## 25. AVAILABILITY-TO-CHECKOUT RACE

| Time | Event |
|---|---|
| 10:00 | Customer checks Uyo -> Eket — Available ₦1,500 |
| 10:02 | Admin disables Uyo -> Eket |
| 10:03 | Customer starts checkout |

Expected: Backend rechecks -> Lane disabled -> Checkout rejected. Customer App displays a clear message and returns to fulfillment selection.

---

## 26. CHECKOUT SNAPSHOT

Once a valid checkout intent is created, the backend preserves the authoritative checkout facts (immutably):

- fulfillment_type, shop, seller
- origin country, state, LGA
- destination address, country, state, LGA
- delivery lane, delivery fee, ETA
- pickup information
- prices, quantities
- applicable rewards

This protects historical checkout integrity against subsequent configuration changes.

---

## 27. PAYMENT

Current payment provider: **Paystack**

The Customer App may initiate the payment experience. It must NEVER declare payment successful because: Paystack UI opened, redirect occurred, client callback returned, or payment reference exists locally. The backend must verify payment.

---

## 28. PAYMENT SUCCESS

Authoritative sequence:

```
Customer App -> Payment initialization -> Paystack -> Paystack callback/webhook
-> Backend verification -> Settlement service -> Order creation/confirmation
```

The Customer App then retrieves/refreshes the authoritative order state.

---

## 29. PAYMENT FAILURE

Possible states: `initiated`, `pending`, `failed`, `cancelled`, `verified`, `settled`

The Customer App must not confuse `payment initialized` with `payment successful`.

---

## 30. DUPLICATE PAYMENT CALLBACK

Backend must be idempotent. If Paystack sends the same notification multiple times, the backend must NOT: create a duplicate order, deduct stock twice, award cashback twice, or settle the vendor twice.

---

## 31. ORDER CREATION

An order becomes authoritative only after successful backend settlement. The Customer App must not locally manufacture an order and display it as confirmed.

After payment: `GET/refresh order` and display the backend state.

---

## 32. STOCK DEDUCTION — DELIVERY

For normal delivery orders, stock is deducted according to the authoritative backend settlement process. The Customer App must never perform stock deduction.

---

## 33. MULTI-VENDOR CART

A cart can contain Vendor A, B, C. The backend creates separate order groups/orders as defined by the marketplace architecture. Each shop/order group must be independently evaluated.

---

## 34. MIXED FULFILLMENT

Explicitly supported:
```
Vendor A -> Delivery
Vendor B -> Pickup
```

The Customer App must allow the backend-defined fulfillment choice per eligible order group. Do NOT force entire cart = one fulfillment method if the backend supports mixed fulfillment.

---

## 35. MULTI-VENDOR DELIVERY

One vendor being deliverable does not make another vendor automatically deliverable. Each shop is independently validated.

---

## 36. MULTI-VENDOR PICKUP

Customer App must show actual availability per order group. `Vendor A -> Pickup available` does not imply `Vendor B -> Pickup available`.

---

## 37. VENDOR WITH NO FULFILLMENT OPTION

If delivery unavailable AND pickup unavailable, the product/order group cannot proceed through fulfillment. The Customer App must explain the backend-provided reason. Do not silently invent another delivery option.

---

## 38. PICKUP LIFECYCLE

```
Customer selects pickup -> Pickup reservation -> Customer visits shop -> Vendor inspects
-> Accepted / Rejected -> If accepted: payment -> Backend verifies payment
-> Pickup order settlement -> Stock deduction -> OTP -> Customer collects -> Completed
```

---

## 39. PICKUP RESERVATION

Pickup reservation is NOT a completed sale. The Customer App must represent `pending_inspection` as a reservation/inspection state, not as `paid`, `completed`, or `collected`.

---

## 40. PICKUP INSPECTION ACCEPTED

Backend changes: `pending_inspection -> inspected_accepted`

Customer App then provides the authorized payment action.

---

## 41. PICKUP INSPECTION REJECTED

Backend: `pending_inspection -> inspected_rejected`

Customer App: displays rejection, does not offer payment, does not mark order completed, does not deduct stock locally.

---

## 42. PICKUP PAYMENT

```
Pay Now -> Paystack -> Backend verification -> Pickup settlement
```

Only successful backend settlement creates/activates the pickup order.

---

## 43. PICKUP STOCK

The canonical lifecycle is: `reservation -> inspection -> payment -> settlement -> stock deduction`

Creating a pickup reservation must NOT automatically be treated as final stock deduction unless the backend explicitly implements a stock reservation mechanism.

---

## 44. PICKUP OTP

OTP is generated/validated by the backend. The Customer App displays the OTP or collection instructions according to backend policy. The app cannot generate a valid pickup OTP itself.

---

## 45. PICKUP COMPLETION

Customer collection becomes authoritative only after backend confirmation. The Customer App must refresh the order state after collection.

---

## 46. CASHBACK / VICTORIOUS POINTS

Cashback is backend-controlled. The backend determines: eligibility, amount, redemption, reversal, expiry.

Customer App may display: available points, earned points, redemption information, order cashback. The Customer App must not calculate final cashback as authoritative.

---

## 47. CASHBACK REDEMPTION

```
Customer selects reward -> Backend validates balance -> Backend validates eligibility
-> Backend applies redemption -> Checkout snapshot records it
```

Never trust `points_balance` supplied by the client.

---

## 48. CASHBACK AND REFUNDS

If an order involving cashback is refunded/returned, the backend determines the appropriate points adjustment. The Customer App displays the resulting authoritative balance. Do not independently modify the customer's points balance in Flutter.

---

## 49. ORDER HISTORY

Customer can view only their own orders. Backend authorization: `authenticated customer -> owns requested order`. Customer A must not retrieve Customer B's order by changing `order_id`.

---

## 50. ORDER DETAILS

Order details must be backend-authoritative: order number, products, quantities, prices, fulfillment mode, delivery address snapshot, pickup information, delivery fee, payment status, order status, fulfillment status, cashback, timestamps, tracking information.

---

## 51. ORDER STATUS

The Customer App must not invent status transitions. Exact statuses must follow the backend contract. If the backend doesn't allow a transition, the frontend must not display or trigger it.

---

## 52. DELIVERY TRACKING

Customer sees only customer-appropriate tracking information. Internal information (rider internal assignment IDs, internal hub IDs, dispatch algorithms, private rider data, operational routing details) must not automatically become customer-facing data.

---

## 53. CUSTOMER CANCELLATION

The Customer App may display a cancellation action only when backend rules say the order is cancellable. Backend determines: whether cancellation is allowed, cancellation window, payment implications, stock implications, refund implications, vendor implications.

---

## 54. RETURNS

Customer requests return through the backend. Backend determines: eligibility, allowed reason, time window, product condition, required evidence, vendor involvement, pickup/return process, refund outcome.

---

## 55. REFUNDS

Refund is a backend financial operation. The Customer App must display `refund processing`, `refund completed`, or `refund failed` according to backend state. The app must not mark a refund successful because a request was submitted.

---

## 56. EXCHANGE

Exchange is not automatically equivalent to refund. Backend determines: exchange eligibility, product availability, price difference, delivery/pickup implications, inventory adjustment, financial adjustment.

---

## 57. NOTIFICATIONS

Backend is authoritative for transactional notifications. When opening a notification, the app should fetch current authoritative data rather than relying entirely on the notification payload.

Customer App may receive: order confirmation, payment confirmation, pickup inspection result, pickup payment request, order ready, dispatch, delivery, cancellation, refund, return, cashback.

---

## 58. NETWORK FAILURE

For every important mutation: `request -> timeout -> retry` must not automatically create duplicates. Backend idempotency is required for sensitive operations. The frontend must not blindly repeat payment/order mutations without understanding the operation state.

---

## 59. OFFLINE / STALE DATA

Cached data can become stale: product price, stock, delivery lane, delivery fee, pickup availability, order status, cashback balance. Before an authoritative transaction, refresh/revalidate.

---

## 60. SECURITY

The Customer App must never be treated as trusted. The backend must reject:

- Fake customer ID / shop ID / seller ID
- Fake origin LGA / destination LGA
- Fake delivery fee / ETA / stock
- Fake cashback / order status / payment status / pickup status
- Another customer's address / order / payment / reservation

---

## 61. API ERROR CONTRACT

Backend returns machine-readable errors:

```json
{ "success": false, "code": "origin_destination_lane_not_served", "message": "Delivery is currently unavailable from this shop to your selected location." }
```

The Customer App maps known error codes to user-friendly messages. It must not change the underlying business meaning.

---

## 62. HTTP / AUTHORIZATION EXPECTATIONS

Every protected API must validate: authentication, authorization, resource ownership, tenant/branch scope where applicable, input validity, business state.

A successful HTTP 200 does not automatically mean the business action succeeded. The frontend must inspect the actual response state.

---

## 63. CUSTOMER APP DATA MODELS

Customer App models must correspond to backend contracts:

`Customer`, `Country`, `State`, `LGA`, `Address`, `Product`, `Shop`, `Cart`, `CartItem`, `FulfillmentAvailability`, `DeliveryOption`, `PickupOption`, `CheckoutIntent`, `PaymentRequest`, `Order`, `OrderGroup`, `OrderItem`, `PickupReservation`, `Cashback`, `Return`, `Refund`, `Notification`

Do not maintain competing versions of the same business model unnecessarily.

---

## 64. API CONTRACT RULE

If backend changes field name, field type, enum, endpoint, required parameter, response structure, status, or error code — the Customer App must be updated against the canonical contract. Do not create silent compatibility behavior unless explicitly documented.

---

## 65. LEGACY API RULE

The Customer App must not continue using legacy marketplace endpoints simply because they happen to work.

**Items requiring audit:**
- `digital-payment`
- legacy shipping endpoints
- `CartShipping`-based checkout
- old `delivery-city` assumptions
- legacy seller shipping configuration

If a legacy endpoint is still temporarily required: **MARK AS LEGACY → DOCUMENT WHY → IDENTIFY REPLACEMENT → MIGRATE CALLERS → REMOVE WHEN SAFE**

Never create two authoritative checkout/payment/fulfillment engines.

---

## 66. CHECKOUT AUTHORITY

There must be ONE authoritative checkout process:

```
Customer App -> Checkout Intent -> Backend -> Payment -> Verification -> Settlement
```

The Customer App must not contain a second checkout engine.

---

## 67. FULFILLMENT AUTHORITY

There must be ONE authoritative fulfillment availability engine: `FulfillmentAvailabilityService`

The Customer App consumes it. The backend checkout process independently invokes/revalidates the same business rules. Do not duplicate delivery eligibility logic in Flutter.

---

## 68. PICKUP AUTHORITY

There must be ONE authoritative pickup reservation/payment/settlement lifecycle.

The Customer App is a client of: `PickupReservationService`, `PickupPaymentInitializationService`, `PickupOrderSettlementService` or their final canonical equivalents. Do not create another pickup engine inside Flutter.

---

## 69. CUSTOMER APP TEST MATRIX

| Domain | Test Scenarios |
|---|---|
| **Authentication** | registration, duplicate registration, login, wrong credentials, expired token, logout, disabled account |
| **Geography** | valid country/state/LGA, invalid state/country, invalid LGA/state, inactive LGA, address ownership violation |
| **Products** | active, inactive, out of stock, price change, removed from marketplace |
| **Cart** | add, remove, quantity change, stock conflict, price conflict, multi-vendor |
| **Delivery** | Uyo->Uyo, Uyo->Eket, Eket->Uyo, unsupported destination, disabled lane, fee change, ETA change, shop without valid origin |
| **Pickup** | enabled/disabled, reservation enabled/disabled, inspection pending/accepted/rejected, payment success/fail, collection, OTP |
| **Mixed fulfillment** | Vendor A delivery + Vendor B pickup, Vendor A delivery + Vendor B unavailable, multiple delivery vendors, multiple pickup vendors |
| **Payment** | initialization success/failure, Paystack cancellation, payment failure/pending/success, duplicate callback, webhook after app closes, app reconnects after payment |
| **Orders** | creation, history, detail, status changes, cancellation, return, refund, exchange |
| **Cashback** | eligible order, ineligible order, redemption, insufficient points, refund after redemption, duplicate cashback prevention |
| **Security** | Customer A attempts Customer B address/order, fake shop ID, fake delivery fee, fake origin LGA, fake cashback amount, fake payment success, fake pickup completion |

---

## 70. END-TO-END PRODUCTION TESTS

### Complete Delivery Test
```
Register/Login -> Browse -> Product -> Cart -> Address -> Fulfillment availability
-> Checkout intent -> Paystack -> Backend verification -> Order -> Vendor preparation
-> Delivery dispatch -> Rider collection -> Customer delivery -> Completion -> Cashback -> Order history
```

### Complete Pickup Test
```
Login -> Product -> Cart -> Select pickup -> Reservation -> Inspection -> Accept
-> Pay -> Backend verification -> Order -> Stock deduction -> OTP -> Collection -> Completion -> Cashback
```

### Mixed Cart Test
```
Vendor A -> Delivery
Vendor B -> Pickup
Vendor C -> unavailable
```
Must verify that the backend and Customer App represent each order group correctly.

---

## 71. ALIGNMENT AUDIT PROCESS

Before modifying the Customer App, AI agents must:

1. Inspect current Customer App implementation
2. Inspect corresponding backend endpoints
3. Inspect backend controllers, services, request validation, response resources/serializers, models, database relationships, routes, existing tests
4. Identify legacy implementations
5. Produce an impact map
6. Compare actual implementation with this specification
7. Identify mismatches
8. **Fix the smallest authoritative layer first**
9. Update frontend models/services/controllers
10. Test backend, then Customer App
11. Run end-to-end scenarios
12. Search repository for old endpoint/business-rule references
13. Review git diff
14. Report remaining legacy dependencies

---

## 72. DO NOT PATCH A SYMPTOM IN THE FRONTEND

If the Customer App receives the wrong delivery fee, do NOT simply change Flutter. Trace the full chain:

```
Delivery Lane -> Fulfillment Service -> Checkout Intent -> Payment -> Order -> API response -> Customer App
```

Find the authoritative source of the error. Fix the correct layer.

---

## 73. DO NOT CREATE DUPLICATE BUSINESS LOGIC

Never create:
- `FlutterDeliveryService` that independently decides delivery eligibility
- `FlutterPickupEligibilityEngine` that independently decides pickup
- `FlutterCashbackEngine` that independently decides cashback

Frontend code can contain presentation helpers, but backend remains authoritative.

---

## 74. PRODUCTION ALIGNMENT DEFINITION

The Customer App and backend are considered aligned only when:

```
Every customer-visible decision
        -> has a corresponding backend authority
        -> frontend consumes the authority
        -> frontend does not contradict it
        -> backend revalidates sensitive operations
        -> legacy alternatives are removed/deprecated
        -> tests cover the complete lifecycle
```

---

## 75. FINAL CUSTOMER APP ARCHITECTURE

```
                         VMARKET BACKEND
                      SOURCE OF TRUTH
                              |
          +-------------------+-------------------+
          |                   |                   |
       PRODUCTS            CART             CUSTOMER
          |                   |                   |
          +-------------------+-------------------+
                              |
                         FULFILLMENT
                              |
                    +---------+---------+
                    |                   |
                 DELIVERY             PICKUP
                    |                   |
                 LGA -> LGA            Shop
                    |                   |
                    +---------+---------+
                              |
                           CHECKOUT
                              |
                           PAYMENT
                              |
                          SETTLEMENT
                              |
                            ORDER
                              |
                    +---------+---------+
                    |                   |
                DELIVERY             PICKUP
                    |                   |
                 COMPLETION         COLLECTION
                    |                   |
                    +---------+---------+
                              |
                         CASHBACK
                              |
                       ORDER HISTORY
```

The Customer App is the interface through which the customer interacts with this system. **It is not the system of record.**

---

## 76. PRODUCTION PRINCIPLES

> **Customer App principle:** The Customer App must **represent** VMarket's backend state, not **invent** VMarket's backend state.

> **Backend principle:** Every customer request must be treated as **untrusted input** and independently validated against authoritative marketplace state.

---

## 77. DEFINITION OF DONE

The Customer App phase is not complete merely because screens compile or API calls return 200.

It is complete when:

```
UI -> API -> Controller -> Service -> Database -> Business rule -> Payment / inventory / fulfillment
```

all agree on the same state for every supported customer scenario.

The target is not: *"The app works."*

The target is: **"The Customer App, backend, database, payment system, inventory system, and fulfillment system all agree about what happened."**

That is the production standard for VMarket.

---

## HOW TO USE THIS DOCUMENT WITH AN AI

Give the AI this document before it changes the Customer App and instruct it to work in this order:

```
1. Audit
   -> 2. Compare
   -> 3. Report mismatches
   -> 4. Fix backend authority
   -> 5. Fix Customer App
   -> 6. Test
   -> 7. Remove obsolete code
```

**Do not tell the AI simply to "make the app match the document"** — that encourages patching the Flutter side around a wrong backend implementation.

---

## NEXT ALIGNMENT DOCUMENTS (Sequence)

| # | Document | Scope |
|---|---|---|
| 1 | Customer App ↔ Backend | **This document** |
| 2 | Vendor App ↔ Backend | Merchant operations |
| 3 | Delivery App ↔ Backend | Rider dispatch and proof of delivery |
| 4 | Admin Web ↔ Backend | Governance and configuration |
| 5 | Marketplace/Public Web ↔ Backend | Storefront |
| 6 | POS ↔ VMarket integration boundary | Separately — intentionally detached |
