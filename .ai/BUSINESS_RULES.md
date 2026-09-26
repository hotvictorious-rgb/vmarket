# Victorious MARKET — Authoritative Business Rules

> **CONTROL ZONE FILE — HUMAN OWNER ONLY**  
> AI agents are STRICTLY FORBIDDEN from editing this file. Any proposed modifications must be submitted via a ticket accompanied by an approved Decision Record (`.ai/decisions/DECISION-XXXX.md`).

---

## 1. Core Principles & Authority

1. **Single Source of Truth (SSOT)**: Business requirements and logic are authoritative. No client application (Customer App, Vendor App, Delivery App, Storefront, Admin Panel) may invent client-side business math or alternate workflows.
2. **Backend Authority**: All pricing, fees, tax, shipping, commissions, discounts, coupons, and wallet mutations are calculated and enforced exclusively by the backend service layer.
3. **Immutability of Audit & Financial History**: Financial ledger entries, order snapshot transactions, wallet journals, and audit logs are append-only and immutable. Historical records must not be mutated or deleted.

---

## 2. Currency, Money & Precision Rules

1. **Zero-Drift Invariant ($\Delta = 0.00$)**:
   $$\text{Customer Paid} = \text{Subtotal} + \text{Tax} + \text{Shipping Fee} - \text{Discount} - \text{Coupon} = \text{Vendor Net} + \text{Admin Commission} + \text{Delivery Share}$$
   Every split, ledger mutation, and settlement must balance with exact precision down to the lowest minor unit ($\Delta = 0.00$).
2. **Fixed-Scale Representation**:
   - Store amounts as fixed-scale decimals with 2 decimal places (or minor units / kobo in payment integrations).
   - **Floating point math (`float`/`double`) is strictly forbidden** for monetary calculations.
   - Use `bcadd()`, `bcsub()`, `bcmul()`, `bcdiv()` in PHP or fixed-precision integer math.
3. **Universal Currency**:
   - The primary currency of Victorious MARKET is Nigerian Naira (`NGN`).
   - Every financial transaction, quote, and balance record must explicitly store the 3-letter ISO-4217 currency code.
4. **Rounding Rules**:
   - Rounding is applied only at the final settlement boundary, never during intermediate multiplication.
   - Round half-up (`PHP_ROUND_HALF_UP`) to 2 decimal places.

---

## 3. Order Lifecycle & State Machine

1. **Canonical States**:
   - `pending`: Order placed, awaiting payment confirmation or cash-on-delivery validation.
   - `confirmed`: Payment verified or COD accepted; order confirmed by merchant/system.
   - `processing`: Merchant is picking, packing, or preparing items.
   - `out_for_delivery`: Order handed over to assigned delivery personnel / rider.
   - `delivered`: Order successfully handed over to customer with OTP / signature proof.
   - `canceled`: Order terminated prior to fulfillment; pre-authorized holds released.
   - `returned`: Goods returned by customer following an approved return request.
   - `failed`: Payment or delivery attempt failed permanently.
2. **Allowed Transitions**:
   - `pending` $\rightarrow$ `confirmed` | `canceled` | `failed`
   - `confirmed` $\rightarrow$ `processing` | `canceled`
   - `processing` $\rightarrow$ `out_for_delivery` | `canceled`
   - `out_for_delivery` $\rightarrow$ `delivered` | `returned` | `failed`
   - `delivered` $\rightarrow$ `returned` (subject to return policy window)
   - `canceled`, `returned`, and `failed` are terminal states.
3. **Atomic Stock Locks**:
   - Inventory is reserved atomically when order enters `pending` (or confirmed).
   - If payment fails or order is canceled, inventory reservations are automatically released.

---

## 4. Multi-Vendor Isolation & Commission Rules

1. **Vendor Scoping**:
   - Vendors can only view, manage, and process items, orders, reviews, and customers associated with their own `seller_id`.
   - Admin panel has marketplace-wide oversight; vendors have strict single-tenant views.
2. **Split Orders**:
   - Orders containing items from multiple distinct merchants must generate discrete sub-orders (`order_details` grouped by `seller_id`) with isolated settlement and delivery status tracks.
3. **Platform Commission**:
   - Commission is deducted automatically from vendor gross sale upon order reaching `delivered` status:
     $$\text{Vendor Payable} = \text{Product Price} - \text{Vendor Discount} - (\text{Commission Rate} \times \text{Product Net}) + \text{Tax (if vendor-managed)}$$
   - Commission rates are configured server-side per vendor or per category in Admin settings.

---

## 5. Fulfillment & Geography Rules

1. **Canonical Hierarchy**:
   `Country (Nigeria)` $\longrightarrow$ `State` $\longrightarrow$ `LGA (Local Government Area)`
2. **Directional Delivery Lanes**:
   - Shipping fees and delivery feasibility are calculated exclusively via `DeliveryLane` records:
     `Origin LGA` $\longrightarrow$ `Destination LGA` $\longrightarrow$ `DeliveryLane` (Fee, Lead Time, Status).
   - Free shipping or custom promotional shipping overrides must be validated server-side.
3. **In-Shop Pickup**:
   - Available only if the seller explicitly enables `in_shop_pickup` in shop settings and the product is physically in stock at that branch/shop.
   - Pickup orders require 6-digit confirmation OTP presented by the customer at the counter.

---

## 6. Payment, Refunds & Balance Integrity

1. **Atomic Payment Locks**:
   - Any payment verification callback (Paystack, Flutterwave, Stripe, COD) must enforce row-level lock on `payment_requests` (`where('is_paid', 0)->update(['is_paid' => 1])`).
   - Only if affected rows $> 0$ may the fulfillment hook (`digital_payment_success`) execute.
2. **Pessimistic Balance Concurrency**:
   - Customer wallet deductions, merchant wallet disbursements, and delivery rider cash collections must execute inside `DB::transaction()` with `->lockForUpdate()`.
   - Balances can never drop below $0.00$ unless an explicit credit facility is enabled by Admin.
3. **Refund Constraints**:
   - Refunds require human/vendor approval and can only be processed on orders in `canceled` or `returned` state.
   - Maximum refundable amount cannot exceed the net customer payment for that specific order.

---

## 7. Time & Localization Standards

1. **Time Storage**:
   - All server timestamps are stored in UTC (`Y-m-d H:i:s`).
   - Conversion to West Africa Time (WAT / UTC+1) occurs solely at the presentation layer.
2. **Localization**:
   - Strings must be localized via language files. No hardcoded display strings in application code.
