# 🌟 Victorious MARKET (VMarket) Ecosystem Master Guide — V1 Fresh-System Edition

## Authoritative End-to-End Technical, Operational, Logistics & Scenario Manual

---

# 1. Executive Architecture & System Topology

Victorious MARKET (VMarket) is a controlled marketplace, in-house merchant, delivery operator, payment/settlement platform, and customer loyalty system launching first in **Uyo LGA, Akwa Ibom State**.

VMarket is **not** a generic marketplace plugin, POS ERP, digital-download platform, or uncontrolled social-commerce/chat system. The V1 architecture is intentionally focused on physical merchandise, centralized payment custody, VMarket-controlled delivery, approved pickup points, manual treasury settlement, and strict anti-circumvention rules.

```text
VICTORIOUS MARKET
├── Laravel Backend / Web Panels (`backend/vmarket-web/`)
│   ├── Super Admin Command Center
│   ├── Vendor Web Dashboard
│   └── Customer Web Storefront
├── Customer Flutter App (`User app/`) — Provider
├── Vendor Flutter App (`Vendor app/`) — Provider
└── Delivery Rider Flutter App (`Delivery Man App/`) — GetX
```

The Laravel backend is the **single source of truth**. Mobile apps and Blade views are clients only; they do not invent authoritative pricing, settlement, delivery, payment, or order-state logic.

---

# 2. V1 Business Scope

## Build Now

- Authenticated customers
- Verified vendors
- VMarket in-house selling
- Approved vendor pickup points
- Physical merchandise catalog
- Cart
- Delivery checkout
- Pickup reservations
- Online payment via Paystack
- Multi-vendor child orders
- VMarket delivery fleet management
- Vendor pickup OTP, customer delivery OTP, and in-shop handover OTP
- 24-hour post-receipt return/refund workflow
- Cashback eligibility ledger (5% merchandise value)
- Manual vendor settlement recording (90% merchandise value)
- Admin, support, dispatch, finance roles
- Comprehensive RBAC, security, and audit logging

## Explicitly Decommissioned / Deferred

- Customer stored-value wallet funding and wallet withdrawals
- Cash on Delivery (COD) for delivery orders
- Rider cash collection and rider cash remittance
- Vendor cash collection / vendor private bank transfer for marketplace orders
- Customer-to-vendor direct chat
- AI concierge / WhatsApp sales agent / customer AI memory
- Digital products, downloadable files, digital OTP downloads, authors, publishing houses
- Complex POS ERP branch accounting and customer debt ledger
- Mixed delivery + pickup single checkout
- Partial pickup acceptance
- Automated vendor bank payouts
- Cashback cash withdrawal
- Complex route optimization and nationwide logistics on day one

---

# 3. Commercial Model — 90 / 5 / 5

For third-party vendor merchandise:

```text
100% Merchandise Value
├── 90% Vendor Payout
└── 10% VMarket Commission
    ├── 5% Customer Cashback
    └── 5% VMarket Retained Margin
```

Example: ₦100,000 merchandise value

| Allocation | Amount | Rule |
|---|---:|---|
| Vendor payout | ₦90,000 | Vendor receives full 90% merchandise entitlement |
| Customer cashback | ₦5,000 | Funded from VMarket's commission, not vendor share |
| VMarket retained margin | ₦5,000 | Platform retained merchandise revenue |
| Total | ₦100,000 | Δ = ₦0.00 |

Delivery fee is completely separate from merchandise economics:

```text
Customer Payment = Merchandise Amount + Delivery Fee
```

Delivery fee is not part of vendor 90%, customer cashback, or VMarket's 5% merchandise margin. It belongs to VMarket logistics operations.

---

# 4. Fulfillment Paths

## A. Delivery Checkout — Pay First, Deliver Later

```text
Customer browses catalog
↓
Adds products to cart
↓
Delivery checkout
↓
Pays VMarket digitally (Paystack)
↓
Vendor prepares package
↓
VMarket assigns rider
↓
Rider verifies Vendor Pickup OTP at vendor shop
↓
Order moves to out_for_delivery
↓
Customer provides Delivery OTP at doorstep
↓
received_at recorded; order delivered
↓
24-hour return window begins
```

Rules:

- Delivery checkout is prepaid.
- No COD.
- Rider cannot collect customer money.
- Rider cannot mark orders as paid.
- Vendor does not arrange marketplace delivery independently.
- Delivery completion occurs only when customer Delivery OTP records `received_at`.

## B. Pickup Checkout — Inspect First, Pay Later

```text
Customer browses catalog
↓
Pickup checkout
↓
Pickup Reservation(s) created, split by seller + pickup point
↓
Customer visits approved pickup point with Reservation Code
↓
Customer physically inspects goods
↓
Customer accepts reservation
↓
Customer pays VMarket digitally at pickup point
↓
Order created atomically
↓
6-digit Handover OTP generated
↓
Vendor enters Handover OTP and releases goods
↓
received_at recorded; 24-hour return window begins
```

Rules:

- Reservation is not an order.
- Reservation is not payment.
- Reservation is not inventory hold.
- Reservation Code is not Handover OTP.
- Customer never pays vendor cash or private transfer.

---

# 5. OTP & Custody Invariants

VMarket uses distinct verification secrets for distinct custody events:

| Fulfillment Path | Secret | Event Proved | Custody Direction |
|---|---|---|---|
| Rider delivery pickup | `pickup_verification_code` | Rider collected package from vendor | Vendor → Rider |
| Rider delivery completion | `verification_code` | Customer received package at doorstep | Rider → Customer |
| Customer pickup handover | Handover OTP / `verification_code` | Customer received goods from vendor after payment | Vendor → Customer |
| Pickup reservation | `RES-XXXXXXXX` | Reservation lookup and inspection authorization | No custody transfer |

Invariants:

- Rider pickup ≠ customer delivery.
- Rider pickup ≠ customer pickup.
- Customer pickup ≠ delivery.
- Reservation Code ≠ Handover OTP.
- Knowing a code alone is never sufficient; the authenticated actor must also be authorized for that order/reservation.

---

# 6. Product Marketplace Rules

Public product visibility is governed by a canonical marketplace eligibility scope:

- Product active (`status == 1`)
- Admin approved (`request_status == 1`)
- Seller active and marketplace-approved
- Listing status = `listed`
- Listing freshness is valid
- Availability is exposed to customers only as binary `in_stock` / `out_of_stock`

Customers do **not** see raw numeric vendor/POS inventory quantities.

## 7-Day Marketplace Freshness

Vendors must explicitly confirm listing freshness. Default freshness window is 7 days, configurable by Admin.

- Fresh + in stock: discoverable and purchasable
- Fresh + out of stock: discoverable but not purchasable
- Expired/unlisted: hidden from marketplace discovery

Ordinary product edits do not renew freshness. Only explicit confirmation renews freshness.

---

# 7. Returns, Refunds & Cashback

## 24-Hour Return Window

Return/refund window starts from actual customer receipt:

- Delivery: customer Delivery OTP records `received_at`
- Pickup: Handover OTP records `received_at`

Payment time does not start the return window.

V1 return reasons are objective:

- Wrong item
- Damaged item
- Materially defective item
- Materially different from advertised
- Missing essential component/accessory

Change-of-mind returns are not a universal V1 right.

## Cashback Timing

Cashback is not available immediately after payment.

```text
Customer receives goods
↓
24-hour window runs
↓
No unresolved dispute
↓
5% cashback matures in cashback ledger
```

Cashback is non-withdrawable account credit for future purchases in V1.

---

# 8. Vendor Settlement

Vendor settlement is manual in V1 and strictly per child vendor order.

```text
received_at recorded
↓
24-hour return window
↓
No unresolved refund/dispute
↓
Settlement eligible
↓
VMarket Finance manually pays vendor
↓
Payment reference/proof recorded
↓
Order settlement marked settled
```

Vendor settlement applies only to third-party vendor orders (`seller_is == 'seller'`). VMarket-owned products do not create third-party vendor payout entitlement.

Settlement statuses:

- `held`
- `eligible`
- `disputed`
- `refunded` (terminal)
- `settled` (terminal)

A refunded order can never become settlement-eligible or settled.

---

# 9. Communication & Support

Customer relationship belongs to VMarket.

- Customers contact VMarket Support, not vendors directly.
- Vendors receive only the information necessary to fulfill their specific orders.
- Direct customer-to-vendor chat is disabled in V1 to prevent off-platform negotiation, harassment, and payment circumvention.
- Rider/vendor communication is permitted only as needed for active logistics handoff.

---

# 10. Security & Financial Invariants

- Zero-trust IDOR scoping on every customer/vendor/admin action
- Atomic payment webhook locks (`where('is_paid', 0)->update(...)`) before side effects
- Pessimistic row-level locks for balance mutations
- Explicit request whitelisting; never mass-assign raw `$request->all()` into sensitive models
- Universal 6-digit OTP standard for customer/bank/handover secrets unless a dedicated legacy 4-digit vendor pickup code is explicitly documented
- Exact identity matching for authentication and OTP verification
- 15-minute OTP expiry and 5-attempt brute-force lockout
- Financial proofs must balance to Δ = ₦0.00

---

# 11. End-to-End V1 Scenarios

## Scenario 1: Delivery Order

1. Customer finds product listed as `In Stock`.
2. Customer adds item to cart and chooses delivery checkout.
3. Customer pays VMarket digitally via Paystack.
4. Backend verifies payment with atomic webhook lock.
5. Vendor receives paid order and prepares package.
6. Rider arrives and verifies Vendor Pickup OTP.
7. Order moves to `out_for_delivery`.
8. Rider delivers package to customer.
9. Customer provides Delivery OTP.
10. Order records `received_at` and becomes `delivered`.
11. 24-hour return window begins.
12. If no dispute, cashback matures and vendor settlement becomes eligible.

## Scenario 2: Pickup Reservation

1. Customer chooses pickup checkout.
2. System creates reservation split by seller + pickup point.
3. Customer visits vendor's approved pickup point.
4. Customer presents Reservation Code and inspects item.
5. Customer accepts and pays VMarket digitally.
6. Backend verifies payment and creates order.
7. Vendor enters Handover OTP.
8. Goods are released and `received_at` is recorded.
9. 24-hour return window begins.
10. If no dispute, cashback matures and vendor settlement becomes eligible.

## Scenario 3: Approved Post-Delivery Merchandise Return

1. Delivery was completed; `received_at != null`.
2. Customer files return within 24 hours for damaged/wrong/defective item.
3. VMarket reviews evidence and approves refund.
4. Merchandise amount is refunded.
5. Delivery fee remains non-refundable because delivery service occurred.
6. Vendor settlement for that child order becomes `refunded` and terminal.

## Scenario 4: Failed Delivery Before Customer Receipt

1. Rider cannot complete delivery; `received_at == null`.
2. Support verifies failed delivery reason.
3. If cancellation/refund is approved, customer receives full refund for that child order including delivery fee.
4. Delivery fee is not earned because actual customer receipt never occurred.

---

# 12. Deployment & Engineering Governance

- Repository path: `backend/vmarket-web/` for Laravel backend/web panels.
- User App: `User app/`.
- Vendor App: `Vendor app/`.
- Delivery Rider App: `Delivery Man App/`.
- Production web deployment maps only `backend/vmarket-web/` to the live web root.
- Never overwrite production `.env`, `storage/`, `vendor/`, or `public/assets/` destructively.
- Do not use public `/install` setup wizard in production.
- Run changes feature-first across backend, API contract, and affected clients.
- Record changes in `AI_CHANGELOG.md`.
- Keep `BUSINESS_RULES.md`, `V1_BUSINESS_RULEBOOK.md`, and this guide synchronized.
