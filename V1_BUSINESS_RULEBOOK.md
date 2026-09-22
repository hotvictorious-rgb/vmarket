# VICTORIOUS MARKET — V1 BUSINESS RULEBOOK

## 1. Order structure

### Rule
A customer checkout can represent multiple vendors, but **each vendor/fulfillment point becomes its own child Order**.

One checkout:
```
Customer
  ↓
Order Group
  ↓
┌───────────────────┬───────────────────┬───────────────────┐
│  Vendor Order A   │  Vendor Order B   │  Vendor Order C   │
└───────────────────┴───────────────────┴───────────────────┘
```

An Order must never contain products belonging to different vendors.

### Recommended V1 rule for multiple pickup points
A vendor Order is tied to **one fulfillment/pickup point**.

If products from the same vendor come from different pickup points, they become separate vendor Orders/reservations.

This keeps the physical source of every Order unambiguous.

---

## 2. Fulfillment mode & Mixed Cart Partitioning

### Rule
Every child vendor Order/OrderGroup must have **exactly one authoritative fulfillment mode**:
* **Delivery:** pay first via Paystack → vendor prepares → VMarket rider delivers
* **Pickup:** reserve now (₦0.00) → visit shop → inspect items → pay at store → OTP handover

### Multi-Vendor Mixed Cart Handling (Spec Section 34 Alignment)
A customer's cart MAY contain items intended for delivery alongside items intended for pickup:
```text
Vendor A -> Delivery (Doorstep via Directional Lane)
Vendor B -> In-Shop Pickup (24-hr Stock Reservation at Store)
```

The system partitions the checkout into two decoupled execution channels:
1. **Delivery Channel:** Items for Vendor A are submitted to `POST /api/v1/checkout/intent` (two-phase pre-paid checkout).
2. **Pickup Channel:** Items for Vendor B are submitted to `POST /api/v1/customer/pickup-reservations` (zero-payment stock hold).

Neither channel blocks or corrupts the other. The customer is never forced to empty their cart or make entire carts 100% delivery or 100% pickup. Each order group is independently evaluated and settled according to its authoritative lifecycle.

---

## 3. Delivery model

Delivery is a **VMarket-controlled service**.

### Vendor responsibility:
* Product accuracy
* Stock availability
* Product preparation
* Handover to VMarket delivery

### VMarket responsibility:
* Delivery task creation
* Dispatch & fleet management
* Rider/logistics assignment
* Routing & transit tracking
* Customer delivery communication
* Delivery verification
* Delivery status updates

A vendor does not independently arrange marketplace delivery.

---

## 4. Delivery fee

### Rule
**Delivery is charged per child vendor Order.**

The delivery fee is not one single fee for the entire multi-vendor checkout.

### Example:
* **Vendor A:** ₦50,000 merchandise + ₦1,000 delivery
* **Vendor B:** ₦30,000 merchandise + ₦1,500 delivery
* **Vendor C:** ₦20,000 merchandise + ₦2,000 delivery

Customer pays:
* Merchandise = ₦100,000
* Delivery = ₦4,500
* **Total = ₦104,500**

> **The critical distinction:** The customer may make ONE Paystack payment for the delivery checkout, but each child Order stores and owns its own delivery fee.

---

## 5. Delivery fee pricing

### V1 recommendation
Use a simple **admin-managed delivery rate table**.

Rates can be based on:
$$\text{Origin / fulfillment point} + \text{Destination / service zone}$$

Examples:
* Uyo local zone → ₦X
* Uyo → Akwa Ibom park → ₦X
* Uyo → outside-Akwa-Ibom park → ₦X

Do not build sophisticated dynamic route pricing in V1.

Once an Order is confirmed, its delivery fee becomes part of that Order's immutable commercial snapshot. Changing the rate later must not alter existing Orders.

---

## 6. Delivery codes & verification secrets

For delivery, there are **two separate verification codes** for every child Vendor Order:

### 1. Vendor Pickup Code (`pickup_verification_code`)
* Used by the **deliveryman/rider to collect the order from the vendor/pickup point**.
* Confirms that the correct order is being released to the assigned rider.
* Must be strictly tied to the specific child Vendor Order.
* **Security Guard:** Knowing the code alone is NOT sufficient; the rider must be an authorized deliveryman assigned to that order/task (`delivery_man_id == rider.id`).
* Successful vendor handover is recorded with a timestamp and rider identity.

### 2. Customer Delivery Code (`verification_code`)
* Used by the **customer to confirm receipt from the deliveryman at the doorstep**.
* Confirms actual customer handover.
* This timestamp records `received_at` and starts the **24-hour return/refund window**.
* Each child Vendor Order receives its own distinct customer delivery confirmation code.

### The Complete Delivery Verification Lifecycle:
$$\begin{aligned}
\text{Vendor prepares Order} &\longrightarrow \text{Rider assigned} \\
&\longrightarrow \text{Rider verifies Vendor Pickup Code} \\
&\longrightarrow \text{Rider collects Order (Status: OUT\_FOR\_DELIVERY)} \\
&\longrightarrow \text{Rider delivers to doorstep} \\
&\longrightarrow \text{Customer verifies Delivery Code} \\
&\longrightarrow \text{received\_at recorded (Status: DELIVERED)} \\
&\longrightarrow \text{24-hour return protection window begins.}
\end{aligned}$$

### Verification Secrets Inequality:
$$\text{Vendor Pickup Code} \neq \text{Customer Delivery Code} \neq \text{In-Shop Pickup Handover OTP} \neq \text{Reservation Code}$$

* Code A can verify only Order A.
* Code B can verify only Order B.
* Codes must never be shared across an order group.
* For pickup orders, the separate **customer-to-vendor handover OTP** is retained exclusively for when the customer physically collects in-shop.

### Fulfillment Disambiguation: The 3 Distinct Verification Codes & Events

VMarket operates two completely different fulfillment paths with three distinct codes/events:

| Fulfillment Path | Verification Code | What It Proves | Custody Direction | Financial & Operational Authority |
|---|---|---|---|---|
| **Rider Delivery Pickup** | **Vendor Pickup Code** (`pickup_verification_code`) | Rider collected package from vendor | Vendor $\rightarrow$ Rider | Custody transfer (`OUT_FOR_DELIVERY`). Package in transit. NOT delivered to customer. Delivery fee NOT earned. |
| **Rider Delivery Completion** | **Customer Delivery Code** (`verification_code`) | Customer received package from rider | Rider $\rightarrow$ Customer | Doorstep receipt (`received_at`). Delivery service completed. Delivery fee becomes NON-REFUNDABLE. 24h return window begins. |
| **Customer Pickup Handover** | **Customer Handover OTP** (`verification_code`) | Customer received package directly from vendor | Vendor $\rightarrow$ Customer | In-shop physical receipt (`received_at`). 24h return window begins. Zero rider involved. |

#### Inviolable Fulfillment Invariants:
* **Rider pickup $\neq$ customer pickup:** Rider pickup transfers parcel custody to a delivery rider; customer pickup is a direct in-shop handover from vendor to customer.
* **Rider pickup $\neq$ delivery completion:** Rider collection (`out_for_delivery`) does not complete delivery service and does not start the 24-hour return window.
* **Customer pickup $\neq$ delivery:** Customer pickup involves zero riders, zero transit logistics, and zero delivery fees.
* Both **customer delivery completion** and **customer pickup handover** record `received_at` and start the 24-hour post-receipt return protection clock, but strictly through their own independent, non-interchangeable fulfillment lifecycles.


---

## 7. Independent delivery status

Vendor Orders remain independent even when transported together.

Example:
* **Order A** → `DELIVERED`
* **Order B** → `OUT_FOR_DELIVERY`
* **Order C** → `PROCESSING`

One Order becoming delivered must never automatically make the whole Order Group delivered.

One rider may carry A, B, and C together, but the Orders remain separate operational records.

---

## 8. Pickup model

Pickup is a separate fulfillment workflow:
```
Reservation
  ↓
Customer visits approved pickup point
  ↓
Reservation code verified
  ↓
Physical inspection
  ↓
Accept / Reject
  ↓
Payment to VMarket
  ↓
One Order created
  ↓
Separate Handover OTP
  ↓
Customer receives goods
```

* A pickup reservation is **not** an Order.
* A pickup reservation is **not** an inventory hold.
* A pickup reservation is **not** payment.

---

## 9. Pickup inspection

### V1 rule
Inspection is **whole-reservation acceptance or rejection**.

A reservation containing three items is accepted as a whole or rejected as a whole. Do not support partial item acceptance in V1.

### Why
Partial acceptance creates additional requirements for:
* Partial payment
* Partial stock mutations
* Partial Order creation
* Partial refunds
* Partial cashback
* Partial cart cleanup

That complexity can be introduced later.

---

## 10. Pickup payment

### V1 rule
Pickup payment is made **to VMarket**, not directly to the vendor.

Customer physically inspects the goods first. After acceptance:
1. Customer pays VMarket digitally at the pickup point.
2. The vendor does not collect the marketplace payment as their own money.
3. V1 should not support vendor cash collection for marketplace pickup.

Payment may be made from the customer's phone at the pickup point through the VMarket online payment flow.

---

## 11. Pickup verification secrets

There are two completely different secrets:

### 1. Reservation Code (`RES-XXXXXXXX`)
Used for:
* Finding the reservation
* Customer check-in
* Physical inspection authorization

### 2. Handover OTP (6-Digit Cryptographic)
Used for:
* Final release of paid goods from physical merchant custody

$$\text{Reservation Code} \neq \text{Handover OTP}$$

The Order-level handover OTP is generated only after verified payment and Order creation.

---

## 12. Merchandise economics — 90 / 5 / 5

For third-party vendor merchandise:
$$100\% \text{ Merchandise Value} \longrightarrow 90\% \text{ Vendor} + 10\% \text{ VMarket}$$

The VMarket 10% is internally divided:
$$10\% \text{ VMarket Commission} \longrightarrow 5\% \text{ Customer Cashback} + 5\% \text{ VMarket Retained Margin}$$

### Example (₦100,000 Merchandise):
* **Vendor** = ₦90,000
* **Customer Cashback Allocation** = ₦5,000
* **VMarket Retained Margin** = ₦5,000

> **Important:** The 5% cashback comes from VMarket's 10%. It does not reduce the vendor's 90%.

---

## 13. Delivery money is separate & delivery fee refund policy

Delivery fees are completely separate from the merchandise 90/5/5 split.

Example:
* Merchandise = ₦100,000
* Delivery = ₦2,000

The ₦2,000 delivery fee does **not** become:
* Part of Vendor 90%
* Part of Customer cashback
* Part of VMarket's 5% merchandise margin

It belongs exclusively to the separate VMarket delivery operation/financial domain.

### Authoritative V1 Delivery Fee Refund Policy:

#### 1. If Actual Customer Delivery Successfully Occurred:
* `received_at` was recorded through successful Customer Delivery Code verification at the doorstep.
* The delivery service was completed by VMarket logistics.
* **Delivery fee is NON-REFUNDABLE.**
* If the customer later requests and receives an approved merchandise return/refund within the 24-hour protection window, refund the applicable **merchandise amount only**.
* **Zero delivery-fee reversal:** VMarket retains the ₦2,000 delivery fee in `AdminWallet.delivery_charge_earned`. Do not reverse or refund the delivery fee because of a merchandise return.
* *Example (₦100,000 item + ₦2,000 delivery = ₦102,000 paid):*
  $$\text{Approved post-delivery return} \longrightarrow \text{Refund: } ₦100,000 \quad \vert \quad \text{Delivery fee retained: } ₦2,000$$

#### 2. If Actual Customer Delivery Did NOT Occur:
* `received_at` remains `NULL` (e.g. rider never successfully delivered, parcel damaged before receipt, unreachable recipient, or order cancelled prior to handover).
* **Delivery fee is REFUNDABLE.**
* If the order is cancelled/failed and a customer payment refund is approved, the refund includes the delivery fee.
* The customer receives the applicable **FULL payment refund** for that undelivered child Vendor Order, including the delivery fee.
* *Example (₦100,000 item + ₦2,000 delivery = ₦102,000 paid):*
  $$\text{No delivery occurred} \longrightarrow \text{Refund: } ₦102,000 \text{ (Full Refund)}$$

#### 3. Operational Determining Event:
* `out_for_delivery` alone does **NOT** make the delivery fee non-refundable. Rider dispatch is not delivery completion.
* **Only successful customer receipt (`received_at != null`) makes the delivery fee earned and non-refundable.**

#### 4. No Over-Complicated Post-Delivery Reversals:
* There is no delivery-fee reversal for normal post-delivery merchandise returns.
* Delivery-fee reversal and deduction from `AdminWallet.delivery_charge_earned` is strictly reserved for cancellations or failed orders where actual customer delivery never took place.

#### 5. Strict Separation from 90 / 5 / 5 Merchandise Economics:
* Merchandise: 90% vendor
* Merchandise: 5% customer cashback
* Merchandise: 5% VMarket retained
* Delivery fee is 100% separate from merchandise economics and retained or refunded strictly according to actual delivery occurrence.


---

## 14. VMarket in-house products

VMarket can sell its own products.

For VMarket-owned merchandise:
* There is no third-party vendor 90% entitlement;
* VMarket controls the merchandise margin;
* Delivery rules remain exactly the same;
* Pickup rules remain exactly the same.

### Recommended V1 cashback rule
Give customers the same 5% cashback experience on eligible VMarket-owned merchandise, but record it as a **VMarket-funded cashback expense**, because there is no vendor 10% commission on an in-house sale.

Therefore:
* **Third-party vendor sale:** 90 / 5 / 5
* **VMarket-owned sale:** VMarket margin + 5% cashback expense where eligible

Do not force VMarket-owned products into a fake vendor 90% split.

---

## 15. Return/refund window

### Rule
The standard V1 refund/return window is:
**24 hours from actual customer receipt.**

* **Delivery:** Delivery verified → `received_at` → 24-hour window begins
* **Pickup:** Handover verified → `received_at` → 24-hour window begins

Payment time does **not** start the 24-hour clock.

---

## 16. V1 return reasons

To keep V1 manageable, standard returns should be limited to objective problems such as:
* Wrong item
* Damaged item
* Materially defective item
* Materially different from what was advertised
* Missing essential component/accessory

### V1 recommendation
Do not make ordinary change-of-mind returns a universal marketplace right.

For categories where size/fit or personal preference is important, category-specific rules can be introduced later.

Pickup is even stricter because the customer physically inspected the goods before payment.

---

## 17. Refund approval

Refunds should be **reviewed through VMarket**, not automatically issued by the customer.

V1 flow:
$$\text{Customer submits return/refund request} \longrightarrow \text{VMarket reviews} \longrightarrow \text{Vendor notified} \longrightarrow \text{Evidence checked} \longrightarrow \text{Approve/Reject} \longrightarrow \text{Refund processed}$$

This keeps fraud and financial leakage lower while the company is still small.

### Delivery fee refund rule
* **Order Delivered (`received_at != null`):** Delivery fee is strictly non-refundable. VMarket performed the delivery service. Approved return refunds merchandise only.
* **Order NOT Delivered (`received_at == null`):** Delivery fee is fully refundable. The customer receives a full payment refund (merchandise + delivery fee) for that undelivered child Order.


---

## 18. Refund money

Refunds should be tied to the **specific child Vendor Order**, not the entire Order Group.

Example:
* **Vendor A Order** → Approved refund
* **Vendor B Order** → Unaffected
* **Vendor C Order** → Unaffected

Refunds should normally go back through the customer's original payment path where supported.

---

## 19. Cashback timing

Cashback is **NOT** available immediately after payment.

Flow:
$$\text{Customer receives Order} \longrightarrow \text{24-hour window} \longrightarrow \text{Window expires without dispute} \longrightarrow \text{Cashback becomes eligible}$$

If a return/refund case is still open at the end of the 24 hours:
**Cashback remains pending until the issue is resolved.**

### Recommended V1 cashback behavior
Cashback is a **VMarket account credit for future purchases**, not a cash withdrawal product. Do not build cashback withdrawal in V1.

---

## 20. Cashback calculation

Cashback is calculated on the **eligible merchandise value**, not on delivery fees.

For:
* Merchandise = ₦100,000
* Delivery = ₦2,000
* **Cashback = ₦5,000** (5% of ₦100,000, NOT 5% of ₦102,000)

For multi-vendor orders, cashback is calculated independently per child Order.

---

## 21. Vendor settlement

### V1 rule
Vendor settlement is **manual**.

The system calculates the vendor's payable amount. VMarket staff then manually pays the vendor and records:
* Amount
* Payment method
* Bank / reference details
* Date
* Staff member who authorized and executed the settlement
* Audit notes / transaction reference

### Settlement eligibility sequence:
$$\text{Customer receives Order} \longrightarrow \text{24-hour window} \longrightarrow \text{No unresolved refund} \longrightarrow \text{Vendor Order settlement-eligible} \longrightarrow \text{Admin manually pays} \longrightarrow \text{Marked settled}$$

* Vendor A can be paid while Vendor B remains unsettled.
* Vendor settlement is strictly per child Vendor Order.
* **Third-Party Scope:** Vendor settlement applies exclusively to third-party vendor orders (`seller_is == 'seller'`). VMarket in-house orders (`seller_is == 'admin'`) have no third-party entitlement and do not undergo vendor settlement or `SellerWallet` disbursement.

### Vendor Settlement Status Lifecycle:
* **`held`:** Initial state upon delivery or pickup handover while within 24-hour return window.
* **`eligible`:** Reached when 24 hours expire with `received_at != null` and zero unresolved refund requests.
* **`disputed`:** Reached when a customer submits a return/refund request within the 24-hour window.
* **`refunded` (Terminal):** Reached when a refund request is approved. **Inviolable Terminal Rule:** An order marked `refunded` is permanently disqualified from vendor payout and can never transition to `eligible` or `settled`.
* **`settled` (Terminal):** Reached when Super Admin manually disburses the vendor's 90% share with verified bank reference.

Transitions:
```text
held ────────────► eligible ────────────► settled
  │                   ▲
  ▼ (refund filed)    │ (refund rejected)
disputed ─────────────┘
  │
  ▼ (refund approved)
refunded (TERMINAL)
```

---

## 22. Order completion vs vendor payment

These are separate concepts:

$$\text{Order DELIVERED} \longrightarrow \text{REFUND\_WINDOW\_ACTIVE (HELD)} \longrightarrow \text{SETTLEMENT\_ELIGIBLE} \longrightarrow \text{SETTLED}$$

An Order being delivered does not mean the vendor has already been paid.

---

## 23. Inventory

`Product.current_stock` remains the physical quantity authority.

* **Reservation:** No stock change.
* **Successful paid Order:** Stock is decremented atomically.

A stock shortage discovered during post-payment settlement becomes a reconciliation quarantine case rather than silently creating a phantom Order.

A paid Order is not invalidated simply because someone later changes the displayed stock quantity.

---

## 24. Vendor lifecycle

### Vendor active
Can sell normally.

### Vendor suspended/inactive
* New purchases are blocked.
* Existing paid Orders remain in the system and must be handled by VMarket operations.
* VMarket staff decide whether an existing Order can continue, be transferred, or be refunded.
* Suspending a vendor must never delete historical Orders.

---

## 25. Pickup-point lifecycle

A vendor can have one or more approved pickup points within its operating LGA.

If a pickup point becomes inactive:
* New reservations cannot use it;
* Existing reservations remain preserved;
* Existing paid Orders remain preserved.
* If the pickup point cannot fulfill an existing reservation/order, VMarket operations may relocate the pickup to another approved point with customer consent, or initiate the appropriate cancellation/refund process.
* Never delete historical pickup-point information from completed Orders.

---

## 26. Geography

VMarket is activated geographically.

### V1
* **Uyo LGA = ACTIVE**
* **Other LGAs = NOT ACTIVE** until deliberately enabled.

Products and pickup points are displayed strictly according to serviceability:
* Delivery availability depends on an active delivery route/service zone.
* Pickup availability depends on an active approved pickup point.

Do not expose unavailable/out-of-service locations as if VMarket currently serves them.

---

## 27. LGA operating model

The core structure is:
$$\text{VMarket} \longrightarrow \text{Vendor} \longrightarrow \text{Operating LGA} \longrightarrow \text{Approved Pickup Point(s)}$$

Not:
$$\text{Vendor} \longrightarrow \text{Traditional corporate branch hierarchy}$$

V1 avoids unnecessary multi-tiered branch accounting complexity. More LGAs can be activated sequentially using the same platform foundation.

---

## 28. Vendor employees

V1 keeps vendor employee roles clear and concise:

### 1. Vendor Owner
* Business settings, product management, stock, orders, staff management, financial visibility.

### 2. Vendor Manager
* Products, inventory management, orders, pickup operations, fulfillment tracking.

### 3. Vendor Fulfillment / Pickup Staff
* Prepare orders, verify pickup reservations, inspect/accept/reject pickup, hand goods to VMarket delivery where authorized.

Vendor staff never have access to VMarket platform financial controls.

---

## 29. VMarket employees

VMarket staff are institutionally segregated from vendor staff:

### 1. Platform Admin
* Full management and operational oversight authority.

### 2. Customer Support
* Customer assistance, orders, returns/refunds, communications.

### 3. Dispatch
* Delivery tasks, riders, routes, delivery exceptions.

### 4. Finance / Treasury
* Payments, reconciliation, cashback liabilities, vendor settlement.

### 5. Content / Catalogue
* Products, categories, merchandising quality control.

Never expose financial or security permissions to operations staff unless required by role.

---

## 30. Customer relationship

The customer relationship belongs exclusively to VMarket:
* VMarket owns the customer account, order history, payment records, returns/refunds, cashback, and customer support.
* Vendors receive only the information strictly necessary to fulfill their specific Orders.
* Vendors do not directly chat with customers through VMarket in V1.

---

## 31. Pricing

* Prices are fixed and non-negotiable.
* No customer/vendor haggling system in V1.
* The customer sees the authoritative marketplace price.
* Once the customer creates a checkout snapshot or pickup reservation, that commercial snapshot is immutable.
* A later vendor price change must not alter an existing reservation or paid Order.

---

## 32. Product changes after purchase

If a product is hidden, deactivated, repriced, or edited:
* Existing paid Orders remain based on their original immutable snapshot.
* The product catalogue can evolve without corrupting historical transactions.

---

## 33. Order cancellation

### Before payment:
Customer can cancel normally.

### Delivery after payment but before fulfillment:
Cancellation becomes a managed cancellation/refund process.

### After dispatch:
Do not treat it as a simple cancellation. It becomes a delivery refusal/return workflow.

### Pickup before payment:
Customer can simply decline/reject the reservation. No payment means no refund needed.

### Pickup after payment:
Use the standard 24-hour return/refund process.

---

## 34. Delivery failure

V1 distinguishes actual operational reasons:
* Vendor not ready
* Wrong address
* Unreachable customer / customer unavailable
* Route/access problem
* Damaged parcel
* Delivery attempt failed

The system records the actual reason. The first failed delivery is handled operationally through VMarket support/dispatch rather than building an automated reattempt engine. Additional reattempt fees can be introduced later with explicit customer disclosure.

---

## 35. Vendor responsibility boundary

### Vendor:
* Product quality & authenticity
* Product accuracy
* Product preparation & packaging
* Stock availability
* Correct package handover

### VMarket:
* Marketplace platform & storefront
* Payment processing & escrow custody
* Delivery & rider dispatch
* Customer support
* Returns/refunds administration
* Customer loyalty & cashback
* Vendor settlement & treasury

This boundary must be transparent in policies and enforced in software permissions.

---

## 36. Payment methods for V1

### Delivery:
Pay online digitally before fulfillment/delivery.

### Pickup:
Pay VMarket digitally after physical inspection and acceptance.

### V1 Exclusions:
Do not initially allow:
* Vendor personal account payment
* Vendor cash collection
* Vendor direct bank transfer
* Multiple unrelated gateways
* Complex split-tender methods

Keep all marketplace transactions under VMarket central custody.

---

## 37. Multi-vendor checkout payment

### Delivery checkout:
One customer payment can cover:
* All merchandise in the delivery Order Group
* All per-vendor delivery fees

The payment is one commercial checkout, but the system creates independent Vendor Orders.

### Pickup checkout:
Each Pickup Reservation has its own independent payment:
$$\text{Three Pickup Reservations} \longrightarrow \text{Three Payments} \longrightarrow \text{Three Orders}$$
No combined multi-pickup payment in V1.

---

## 38. Delivery operational grouping

One rider may carry:
$$\text{Order A} + \text{Order B} + \text{Order C}$$
on one physical delivery trip.

That does **NOT** merge them financially or operationally. Each remains a separate Vendor Order with:
* Its own delivery fee
* Its own fulfillment status
* Its own delivery code
* Its own receipt timestamp (`received_at`)
* Its own 24-hour window
* Its own cashback eligibility
* Its own vendor settlement

---

## 39. Return/refund and settlement independence

One problematic Vendor Order must not freeze every order in the customer's checkout.

Example:
* **Order A:** Returned
* **Order B:** Delivered successfully
* **Order C:** Delivered successfully

Order A remains under refund processing while B and C move toward cashback eligibility and vendor settlement normally.

---

## 40. Manual settlement protection

V1 vendor settlement must be recorded as a separate financial event:
* Settlement eligible
* Settlement pending / manual review
* Settlement paid
* Settlement on hold

A staff member marking a settlement paid must provide a verified payment reference. Silent "paid" status changes without an audit trail are strictly prohibited.

---

## 41. Security principle

V1 operates on **Zero-Trust**:
Every sensitive action must verify:
* Authenticated user identity
* Role & permissions
* Customer/vendor ownership scoping
* Seller identity
* Pickup point affiliation
* Order ownership
* Reservation ownership
* Payment state

Knowing an Order number or reservation code alone must not grant unrestricted access.

---

## 42. V1 feature boundary

### Build Now:
* Authenticated customers
* Verified vendors
* VMarket in-house selling
* Vendor pickup points
* Products & stock management
* Cart
* Delivery checkout
* Pickup reservations (zero-hold inspection)
* Online payment via Paystack
* Multi-vendor child Orders
* VMarket delivery fleet management
* In-shop pickup handover with OTP
* 24-hour post-receipt return/refund workflow
* Cashback eligibility ledger (5% merchandise)
* Manual vendor settlement recording (90% merchandise)
* Admin, support, dispatch, finance roles
* Comprehensive RBAC, security, and audit logging

### Defer to Later Iterations:
* Automated bank payouts to vendors
* Cashback cash withdrawal
* Complex coupon matrices
* Dynamic AI route optimization
* Nationwide park automation
* Multi-tier loyalty programs
* Customer/vendor in-app direct chat
* Complex partial pickup acceptance
* Mixed delivery + pickup single checkouts
* Multi-tiered corporate branch accounting

---

# V1 MASTER CUSTOMER FLOW

## Delivery Flow
```
Customer
  ↓
Browse Catalog
  ↓
Add to Cart
  ↓
Delivery Checkout
  ↓
Pay Total (Merchandise + Per-Vendor Delivery Fees)
  ↓
Vendors Prepare Packages
  ↓
VMarket Central Dispatch
  ↓
Delivery Rider Assigned
  ↓
Rider Arrives at Vendor & Verifies Vendor Pickup Code
  ↓
Vendor Custody Handed Over to Rider (Status: OUT_FOR_DELIVERY)
  ↓
Rider Transits to Customer Doorstep
  ↓
Package Handover at Doorstep with Customer Delivery Code
  ↓
Customer Receives Goods (Status: DELIVERED)
  ↓
Timestamp: received_at recorded
  ↓
24-Hour Return / Refund Protection Window

  ↓
Cashback Eligible (if no unresolved disputes)
  ↓
Vendor Settlement Eligible (90% Merchandise)
  ↓
VMarket Treasury Manually Pays Vendor
```

## Pickup Flow
```
Customer
  ↓
Browse Catalog
  ↓
Pickup Checkout
  ↓
Pickup Reservation(s) Created (Split by Seller + Shop)
  ↓
Unique Human-Friendly Reservation Code (RES-XXXXXXXX)
  ↓
Customer Visits Approved Vendor Pickup Point
  ↓
Physical Inspection of Product
  ↓
Customer Accepts Goods
  ↓
Customer Pays VMarket Digitally at Pickup Point
  ↓
One Order Created Atomically
  ↓
6-Digit Handover OTP Generated
  ↓
Vendor Enters Handover OTP to Release Goods
  ↓
Timestamp: received_at (handed_over_at)
  ↓
24-Hour Return / Refund Protection Window
  ↓
Cashback Eligible (if no unresolved disputes)
  ↓
Vendor Settlement Eligible (90% Merchandise)
  ↓
VMarket Treasury Manually Pays Vendor
```

---

# MASTER FINANCIAL MODEL

## Third-Party Vendor Merchandise (100%)
$$\begin{aligned}
&\longrightarrow \mathbf{90\% \text{ Vendor Payout}} \\
&\longrightarrow \mathbf{10\% \text{ VMarket Commission}} \quad \begin{cases} 5\% \text{ Customer Cashback} \\ 5\% \text{ VMarket Retained Margin} \end{cases}
\end{aligned}$$

## Delivery Fees
* Segregated entirely from merchandise.
* Calculated **per Vendor Order**.
* Belongs 100% to VMarket delivery operations and logistics overhead.

## Pickup Orders
* **₦0.00** delivery fee.

---

# MASTER V1 OPERATING PRINCIPLE

> **Build Uyo first. Keep fulfillment, payment, inventory, returns, cashback, and settlement understandable. Make each Vendor Order independently accountable. Let VMarket control the customer transaction and delivery, while vendors remain responsible for their merchandise and pickup operations.**
