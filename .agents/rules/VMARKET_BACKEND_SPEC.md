# VMarket Backend — Full Production Architecture

> **MANDATORY MASTER REFERENCE DOCUMENT FOR ALL AIs WORKING ON THE VMARKET BACKEND.**
> The backend is the central operating system and Single Source of Truth (SSOT) of Victorious MARKET. All client applications (Customer App, Customer Storefront, Vendor Mobile App, Vendor Web Panel, Delivery Rider App, and Admin Panel) are strictly presentation and command clients of this backend engine—never separate sources of business logic.
> All AIs must adhere strictly to these 54 architectural sections without exception.

---

## 1. Core principle

The backend must be the single source of truth for:

- customers
- merchants
- shops/branches
- employees
- products
- categories
- inventory
- geography
- delivery capability
- pickup
- carts
- checkout
- payments
- orders
- delivery
- returns
- refunds
- cashback
- notifications
- commissions
- financial records
- permissions
- audit logs

Clients may request actions.

The backend decides whether those actions are valid.

```
Customer App ───────┐
Storefront ─────────┤
Vendor App ─────────┤
Vendor Web ─────────┤
Delivery App ───────┤
Admin Panel ────────┤
                    ↓
             VMARKET BACKEND
          SINGLE SOURCE OF TRUTH
                    ↓
              DATABASE
```

---

## 2. Backend architectural layers

Do not allow every controller to contain business logic.

Use a structure conceptually like:

```
HTTP/API
   ↓
Authentication
   ↓
Authorization
   ↓
Validation
   ↓
Application Service
   ↓
Domain/business rules
   ↓
Repositories/Models
   ↓
Database
```

For example:

```
POST /checkout/intent
        ↓
CheckoutController
        ↓
CheckoutIntentService
        ↓
FulfillmentAvailabilityService
        ↓
PricingService
        ↓
InventoryService
        ↓
PaymentRequestService
        ↓
Database
```

The controller should orchestrate the request—not become a 1,000-line business engine.

---

## 3. Identity and authentication

Backend should support distinct actors.

### Customer
- customer

### Vendor
- vendor owner

### Vendor employees
Examples:
- branch_manager
- sales_staff
- inventory_staff
- pickup_staff

### Delivery personnel
- delivery_company
- delivery_manager
- dispatcher
- rider

### Admin
Examples:
- super_admin
- admin_employee
- support
- finance
- content_manager
- dispatcher

Every authenticated request should establish:
- who
- what role
- which tenant
- which shop
- which branch
- which permissions

---

## 4. Tenant and branch isolation

This is extremely important.

If Vendor A owns:
- Shop A
- Shop B
- Shop C

an employee assigned to Shop A must not automatically see:
- Shop B
- Shop C

Every sensitive query should be scoped.

Conceptually:

```
authenticated user
      ↓
vendor
      ↓
authorized shop/branch
      ↓
requested resource
```

Never trust:

```json
{
  "shop_id": 99
}
```

just because the client sent it.

Backend must verify ownership/authorization.

---

## 5. Geography

Canonical marketplace geography:

```
Country
   ↓
State
   ↓
LGA
```

Models:
- countries
- states
- lgas

Validation:
- State belongs to Country
- LGA belongs to State

No client is allowed to create arbitrary geography.

---

## 6. Shop location

Every marketplace shop should have canonical:
- country_id
- state_id
- lga_id
- address
- latitude
- longitude

The backend determines the shop's actual origin.

The customer cannot tell the backend:

"I am buying from Uyo."

if the product actually belongs to a shop in Eket.

The backend derives:

```
Cart
 ↓
Product
 ↓
Shop
 ↓
Shop LGA
```

---

## 7. Customer addresses

Customer addresses should reference:
- country
- state
- LGA

plus:
- address
- latitude
- longitude
- delivery instructions

When the customer uses an address:
- customer_id
- address_id

must be verified.

Customer A cannot submit Customer B's address ID.

---

## 8. Delivery capability

This is one of the most important backend concepts.

Use:
- `delivery_lanes`

A lane means:

VMarket can deliver from Origin LGA to Destination LGA.

Example:
- Uyo → Uyo
- Uyo → Eket
- Eket → Uyo

Direction matters.

Therefore:

Uyo → Eket

does NOT automatically mean:

Eket → Uyo

The backend checks the lane.

---

## 9. Delivery availability

Create one authoritative service:
- `FulfillmentAvailabilityService`

It evaluates:
- customer
- cart
- address
- shops
- products
- inventory
- delivery lane
- pickup settings

For delivery:

```
Shop LGA
      ↓
Delivery Lane
      ↓
Customer LGA
```

Backend returns:

```json
{
  "available": true,
  "fee": "1500.00",
  "estimated_delivery_time": "24-48 hours"
}
```

The frontend only displays this.

---

## 10. Pickup availability

Pickup is separate.

It depends on:
- shop active
- seller approved
- pickup enabled
- reservation enabled
- product eligible
- stock available

It does NOT require a delivery lane.

Therefore:

**Delivery:**
Origin LGA → Destination LGA

**Pickup:**
Shop → Customer

---

## 11. Products

Product creation must be backend-controlled.

Product contains things such as:
- id
- shop_id
- category_id
- brand_id
- name
- description
- SKU
- price
- images
- status
- approval_status

Backend validates:
- seller owns shop
- category exists
- price valid
- SKU rules
- images valid
- product status
- merchant permissions

---

## 12. Product visibility

A product can exist without being purchasable.

Separate:
- exists
- published
- approved
- active
- in stock
- fulfillable

For example:

Product published = YES
Stock = 0

The product can still appear in the marketplace but cannot be purchased.

Similarly:

Product active = YES
Delivery unavailable
Pickup unavailable

It can remain discoverable while backend explains the fulfillment limitation.

---

## 13. Inventory

Inventory must be authoritative in the backend.

Do not let the User App calculate stock.

Important concepts:
- available stock
- reserved stock
- sold stock
- returned stock
- damaged stock
- stock adjustment

A simplified model:

```
physical stock
      -
reserved stock
      =
available stock
```

---

## 14. Stock reservation

For ordinary delivery orders, checkout/payment must prevent overselling.

Concurrency must be handled using:
- transactions
- row locks
- atomic updates
- idempotency

Example:
- Customer A sees 1 item
- Customer B sees 1 item

Both attempt payment.

Only one transaction should successfully claim the final stock.

---

## 15. Cart

Cart belongs to the authenticated customer.

Backend should derive:

```
cart
 ↓
cart items
 ↓
products
 ↓
shops
 ↓
inventory
```

Never trust the client for:
- product price
- shop
- seller
- stock
- delivery fee
- discount

---

## 16. Multi-vendor cart

A cart can contain:
- Vendor A
- Vendor B
- Vendor C

Backend groups items by shop/order group.

Example:

```
Cart
 ├── Shop A
 │    ├── Product 1
 │    └── Product 2
 │
 ├── Shop B
 │    └── Product 3
 │
 └── Shop C
      └── Product 4
```

Each group independently evaluates fulfillment.

---

## 17. Mixed fulfillment

This must work.

Example:
- Shop A → Delivery
- Shop B → Pickup

The backend should allow:

**Order Group A:**
fulfillment = delivery

**Order Group B:**
fulfillment = pickup

Do not force the entire cart into one fulfillment method.

---

## 18. Checkout Intent

Checkout should begin with:

`POST /checkout/intent`

The backend recalculates everything.

Even if the app previously asked:

`/fulfillment/availability`

the backend must recheck during checkout.

Why?

Because between availability and checkout:
- stock could change
- lane could be disabled
- price could change
- shop could become inactive

Therefore:

**Availability is advisory. Checkout is authoritative.**

---

## 19. Checkout snapshot

Once checkout is created, preserve the commercial facts.

Snapshot:
- product
- quantity
- unit price
- subtotal
- delivery fee
- fulfillment type
- shop
- seller
- origin LGA
- destination LGA
- lane
- ETA
- cashback
- total

This is important because configuration can change later.

If today:
Uyo → Eket = ₦1,500

and tomorrow the admin changes it to:
₦2,000

yesterday's paid order must not suddenly become ₦2,000.

---

## 20. Payment

Current provider:
- Paystack

Backend flow:

```
Checkout Intent
      ↓
Payment Request
      ↓
Paystack Initialization
      ↓
Customer Payment
      ↓
Paystack Callback/Webhook
      ↓
Server Verification
      ↓
Settlement
```

The callback from the frontend is not sufficient proof of payment.

Backend verifies with Paystack/server-side mechanisms.

---

## 21. Payment idempotency

Payment callbacks can arrive more than once.

Therefore:

same transaction
+
same payment reference

must not create:
- two orders
- two stock deductions
- two cashback awards

Everything that changes money or inventory needs idempotent handling.

---

## 22. Delivery order settlement

Once payment is verified:

```
Payment verified
      ↓
DeliveryOrderSettlementService
      ↓
Create order
      ↓
Create order group
      ↓
Create delivery record
      ↓
Deduct/reserve stock
      ↓
Financial records
      ↓
Vendor notification
      ↓
Delivery workflow
```

All critical changes should happen transactionally.

---

## 23. Pickup settlement

Pickup is different.

Current intended lifecycle:

```
Pending inspection
       ↓
Inspected accepted
       ↓
Customer pays
       ↓
Payment verified
       ↓
Pickup order created
       ↓
Stock deducted
       ↓
OTP generated
       ↓
Customer collects
```

Or:

```
Pending inspection
       ↓
Inspected rejected
```

No payment.
No completed order.

---

## 24. Pickup reservation does not own stock prematurely

The reservation should not behave like a completed sale.

The current business rule is:

**Reservation ≠ Sale**

Stock deduction occurs at successful settlement.

This needs careful concurrency protection so another transaction cannot consume the same inventory incorrectly.

---

## 25. Delivery lifecycle

Backend should own the state machine.

For example:

```
pending_payment
      ↓
paid
      ↓
processing
      ↓
ready_for_pickup
      ↓
assigned
      ↓
picked_up
      ↓
in_transit
      ↓
out_for_delivery
      ↓
delivered
      ↓
completed
```

Exact states should be standardized rather than allowing every app to invent states.

---

## 26. Delivery failure

Possible states:
- delivery_failed
- customer_unavailable
- wrong_address
- customer_cancelled
- merchant_issue
- rider_issue

Backend determines what each means financially.

Do not let the Delivery App simply mark:
refund = true

The backend must determine the consequence.

---

## 27. Manual refunds

Since you decided to start with manual refunds, make this explicit in the backend.

Customer requests:
- refund request

Backend creates:
- `refund_request`

with:
- order_id
- customer_id
- reason
- amount_requested
- status

Admin reviews.

Possible:
- pending
- approved
- rejected
- processing
- completed

When approved:

```
Admin approves
      ↓
Backend records approved amount
      ↓
Admin sends money manually
      ↓
Admin records transaction/reference
      ↓
Refund marked completed
```

The backend should not pretend the money was automatically sent.

---

## 28. Delivery fee refund

Your specific operational rule can be supported.

Example:

Customer paid:
- Product = ₦20,000
- Delivery = ₦1,500
- Total = ₦21,500

If the delivery man genuinely reached the customer's location but delivery failed because of the customer's absence, the business may decide:
- Product refund = ₦0
- Delivery refund = ₦0

or another policy.

If VMarket decides the delivery charge should be refunded:
- refund amount = ₦1,500

The backend records exactly what was approved.

The app should never calculate that policy itself.

---

## 29. Returns

Return lifecycle should be explicit.

```
delivered
   ↓
return requested
   ↓
return review
   ↓
approved/rejected
   ↓
item returned
   ↓
inspection
   ↓
refund/exchange/replacement
```

Backend determines eligibility based on business rules.

---

## 30. Exchange

Exchange should be a separate transaction concept.

Example:

```
Original order
      ↓
Exchange request
      ↓
Approval
      ↓
Returned item
      ↓
Inspection
      ↓
Replacement item
```

Inventory must account for both:
- returned original
+
- replacement product

---

## 31. Cashback / Victorious Points

Cashback should be ledger-based.

Never simply mutate balances without an auditable record.

Use a transaction/ledger concept:
- `cashback_ledger`

Events:
- earned
- redeemed
- reversed
- expired
- adjusted

Every balance should be explainable.

---

## 32. Cashback redemption

Backend verifies:
- customer owns points
- points available
- order eligible
- maximum redemption rules

Then atomically:

```
redeem
 ↓
ledger entry
 ↓
order snapshot
```

If the order is later refunded, cashback consequences must be handled explicitly.

---

## 33. Notifications

Backend should generate business events such as:
- OrderPaid
- OrderReady
- DeliveryAssigned
- OutForDelivery
- Delivered
- PickupReady
- PickupRejected
- RefundApproved
- RefundCompleted

Clients consume notifications.

Don't make the frontend responsible for deciding when a notification should exist.

---

## 34. Search and storefront

The storefront can be optimized heavily for SEO and speed, but backend remains authoritative.

Search results should come from backend/product data.

Important distinction:

```
SEO content
      ↓
storefront

Commerce truth
      ↓
backend
```

Do not duplicate pricing/business rules into the storefront.

---

## 35. Admin

Admin is a control surface over backend functionality.

Admin can manage:
- countries
- states
- LGAs
- delivery lanes
- merchants
- shops
- products
- orders
- payments
- refunds
- pickup
- delivery
- riders
- hubs
- customers
- permissions

But even Admin must use backend authorization.

Being an Admin UI does not mean:
“Trust whatever the browser sends.”

---

## 36. Audit logging

Critical actions must be auditable.

Examples:
- Admin disabled delivery lane
- Vendor changed product price
- Admin approved refund
- Employee changed inventory
- Rider marked delivery failed
- Customer cancelled order
- Admin changed shop status

Audit record:
- actor
- actor_type
- action
- resource
- resource_id
- before
- after
- timestamp
- IP/device context where appropriate

This will become extremely valuable when something goes wrong.

---

## 37. Financial ledger

Don't rely solely on order totals.

You eventually need a financial model capable of explaining:
- customer payment
- platform revenue
- vendor amount
- delivery fee
- delivery expense
- cashback
- refund
- commission
- adjustment

Every financial movement should be traceable to a source event.

---

## 38. Vendor settlement

Vendor settlement should be separate from customer payment.

Conceptually:

```
Customer pays VMarket
        ↓
VMarket financial hold
        ↓
Order successfully fulfilled
        ↓
Return/dispute period/business rules
        ↓
Vendor becomes eligible
        ↓
Settlement
```

The exact settlement timing can be configured later, but it must be represented explicitly.

---

## 39. Delivery operations

Delivery backend should maintain:
- delivery order
- delivery assignment
- rider
- hub
- dispatch batch
- waybill
- tracking events
- proof of delivery

Internal logistics can use hubs.

But customer-facing geography remains:

**Country → State → LGA**

---

## 40. Backend event architecture

As the system grows, important business actions should produce events.

For example:

```
PaymentVerified
      ↓
OrderPaid
      ↓
StockDeducted
      ↓
VendorNotified
      ↓
DeliveryWorkflowStarted
```

This reduces hard coupling.

But don't introduce unnecessary distributed microservices yet.

A well-structured Laravel backend can handle a great deal before you need that complexity.

---

## 41. Queues

Use queues for work that doesn't need to block the customer's HTTP request.

Examples:
- emails
- push notifications
- SMS
- image processing
- search indexing
- analytics
- non-critical integrations

Do NOT put critical financial decisions into an unreliable asynchronous flow without proper transactional design.

---

## 42. Database transactions

Use transactions around critical operations.

Especially:
- checkout settlement
- stock deduction
- payment settlement
- pickup settlement
- refund recording
- cashback redemption
- inventory adjustment
- vendor settlement

Example:

```
BEGIN TRANSACTION

verify order
lock stock
verify payment
create order
deduct stock
create financial records
create fulfillment record

COMMIT

If something critical fails:
ROLLBACK
```

---

## 43. Concurrency

The backend must assume multiple people act simultaneously.

Examples:
- two customers buying final item
- admin disabling lane while checkout occurs
- two employees editing inventory
- duplicate payment webhook
- rider updating delivery while customer cancels

Use:
- row locks
- unique constraints
- idempotency keys
- transactions
- optimistic/pessimistic locking where appropriate

---

## 44. API design

The backend should expose clear contracts.

For example:
- `/api/v1/auth`
- `/api/v1/products`
- `/api/v1/categories`
- `/api/v1/cart`
- `/api/v1/addresses`
- `/api/v1/fulfillment`
- `/api/v1/checkout`
- `/api/v1/orders`
- `/api/v1/pickup`
- `/api/v1/delivery`
- `/api/v1/payments`
- `/api/v1/refunds`
- `/api/v1/cashback`
- `/api/v1/notifications`

Don't create multiple endpoints that perform the same business operation differently.

---

## 45. API response consistency

Use consistent structures.

For example:

```json
{
  "success": true,
  "message": "...",
  "data": {}
}
```

Errors:

```json
{
  "success": false,
  "message": "...",
  "errors": {}
}
```

HTTP status codes should also be meaningful.

---

## 46. Backend must reject client manipulation

These should never be trusted:
- price
- subtotal
- total
- delivery fee
- shop ownership
- seller ownership
- stock
- payment status
- refund amount
- cashback balance
- order ownership
- fulfillment availability
- origin LGA

The client may display them.

The backend recalculates/verifies them.

---

## 47. Production security

Before production:
- authentication
- authorization
- RBAC
- tenant isolation
- branch isolation
- CSRF where applicable
- rate limiting
- input validation
- SQL injection protection
- file upload validation
- secure secrets
- webhook verification
- audit logging
- session security
- password hashing
- token expiration

Also ensure sensitive information isn't unnecessarily returned by APIs.

---

## 48. Data integrity

Database constraints should reinforce business rules.

Examples:
- unique SKU within required scope
- unique delivery lane
- unique payment reference
- unique idempotency key
- valid state → country
- valid LGA → state
- valid shop → seller
- valid employee → shop

Don't rely only on application-level checks.

---

## 49. Historical data

Never casually delete records that are needed to understand historical commerce.

Orders should preserve snapshots.

For example:
- Order created in 2026
- Shop LGA = Uyo
- Delivery fee = ₦1,500
- Lane = Uyo → Eket

If the shop moves later, the old order remains historically accurate.

---

## 50. Legacy code cleanup

This is especially important for your current repository.

The old 6Valley mechanisms should not remain as competing authorities.

For example:
- old shipping calculation
- old CartShipping
- old delivery-city assumptions
- old seller shipping logic

must be:

**audit → migrate callers → deprecate → remove**

But do not blindly delete historical database structures until dependencies and old orders have been checked.

---

## 51. Backend testing strategy

The backend needs tests at several levels.

### Unit
Test:
- delivery lane decision
- pickup decision
- pricing
- cashback
- refund rules
- stock calculations

### Feature/API
Test:
- customer checkout
- vendor order management
- admin refund
- delivery assignment
- pickup reservation

### Authorization
Test:
- Customer A → Customer B data = DENY
- Vendor A → Vendor B shop = DENY
- Branch A employee → Branch B = DENY
- Vendor → delivery lane administration = DENY
- Customer → admin endpoint = DENY

### Concurrency
Test:
- two buyers, one final stock
- duplicate payment webhook

---

## 52. End-to-end backend scenarios

The backend isn't production-ready until these work.

### Scenario 1 — Same-LGA delivery
- Shop: Uyo
- Customer: Uyo
- Uyo → Uyo lane exists
- Flow: Checkout → Pay → Verify → Order → Stock → Delivery → Complete

### Scenario 2 — Cross-LGA
- Shop: Uyo
- Customer: Eket
- Uyo → Eket exists
- ALLOW

### Scenario 3 — Reverse direction unavailable
- Shop: Eket
- Customer: Uyo
- Eket → Uyo disabled
- DENY DELIVERY

### Scenario 4 — Pickup
- Shop → Pickup enabled → Reservation → Inspection → Accept → Payment → Settlement → OTP → Collection

### Scenario 5 — Mixed cart
- Vendor A → Delivery
- Vendor B → Pickup
- Both order groups proceed correctly.

### Scenario 6 — Stock race
- Stock = 1
- Customer A attempts purchase; Customer B attempts purchase
- Only one succeeds.

### Scenario 7 — Payment duplication
- Paystack webhook arrives twice
- One payment, One settlement, One stock deduction, One order

### Scenario 8 — Manual refund
- Customer requests refund → Admin reviews → Approves ₦X → Money sent manually → Admin records reference → Refund completed

---

## 53. What the backend should NOT do

Do not allow the backend to become a giant uncontrolled monolith.

- **Avoid:** one controller doing everything
- **Avoid:** duplicate checkout engines
- **Avoid:** duplicate payment flows
- **Avoid:** different fulfillment logic in every app
- **Avoid:** vendor-specific delivery engines
- **Avoid:** frontend-specific business rules

---

## 54. The final mental model

Your entire ecosystem should eventually look like this:

```
                         VMARKET BACKEND
                         SOURCE OF TRUTH
                                │
       ┌────────────────────────┼────────────────────────┐
       │                        │                        │
   COMMERCE                  FULFILLMENT             FINANCE
       │                        │                        │
 Products                   Delivery                 Payments
 Cart                       Pickup                   Refunds
 Checkout                   Hubs                     Cashback
 Orders                     Riders                   Settlement
 Inventory                  Dispatch                 Ledger
       │                        │                        │
       └────────────────────────┼────────────────────────┘
                                │
                         AUTHORIZATION
                                │
                         AUDIT / EVENTS
                                │
                         DATABASE
```

And around it:

```
             ┌─────────────┐
             │  CUSTOMER   │
             │    APP      │
             └──────┬──────┘
                    │
             ┌──────▼──────┐
             │  STOREFRONT │
             └──────┬──────┘
                    │
      ┌─────────────▼─────────────┐
      │       VMARKET API         │
      │      BACKEND CORE         │
      └─────────────┬─────────────┘
                    │
       ┌────────────┼─────────────┐
       ↓            ↓             ↓
   VENDOR APP   DELIVERY APP   ADMIN
```

### The most important rule

**Every application should be replaceable without changing the business truth.**

- If tomorrow you replace the Flutter Customer App with another technology, the backend should still work.
- If you replace the Vendor Web, the backend should still work.
- If you replace the Delivery App, the backend should still work.
- If you redesign the storefront, orders, payments, inventory and fulfillment should remain intact.

That is what I would consider the production-grade backend foundation for VMarket.

And because your current repository already contains substantial checkout, pickup, payment and legacy 6Valley functionality, I would not give an AI a prompt saying “build this backend from scratch.” The correct next step is to use this as the target architecture and have the AI perform a repository-wide gap analysis against v1, then implement/migrate one bounded subsystem at a time while preserving the working pieces.
