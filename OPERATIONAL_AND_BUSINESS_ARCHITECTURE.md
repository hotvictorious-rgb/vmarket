# 🌐 Victorious MARKET: Operational & Business Architecture

> **Prime Principle:**  
> **Victorious MARKET is not just an app or code repository.**  
> It is a **commerce business + software platform + payment operation + logistics operation + customer-support operation + vendor network**.  
> **Software enforces the business rules, while operations follows the exact same rules.**

---

## 1. The 10 Connected Systems

Victorious MARKET functions as 10 deeply unified, interdependent systems:

| # | System | Primary Focus | Operational Responsibility |
|---|---|---|---|
| **1** | **VM (Customer Marketplace)** | Customer Commerce | Product discovery, search, cart, checkout, payment rails, live order tracking, customer OTP, reviews, and support inbox. |
| **2** | **VV (Vendor System)** | Merchant Operations | Storefront profile, SKU catalog, stock counts, pricing, 1-click order preparation, ready marking, and in-shop OTP handover. |
| **3** | **VD (Delivery Logistics)** | Custody & Transport | Rider dispatch, route navigation, vendor pickup OTP verification, transit custody, active order phone/chat, and customer delivery OTP verification. |
| **4** | **Admin Control Tower** | Platform Governance | Vendor vetting & KYC, catalog moderation, OPay reference verification, Paystack reconciliation, dispute resolution, and audit oversight. |
| **5** | **Payments Authority** | Payment Rails | Paystack (automated digital rail), OPay (manual bank transfer with Admin reference verification), Pay-at-Pickup (in-store Vmarket payment gate). |
| **6** | **Settlements & Escrow** | Financial Balance | Order escrow buffers, vendor payables, platform commissions, and withdrawal payouts with bank receipts ($\Delta = 0.00$). |
| **7** | **Returns & Refunds** | Reverse Logistics | Structured return workflows, admin inspection, refund balance debiting, and pro-rata commission reversals. |
| **8** | **Customer Support** | Communication Hub | Vmarket Support Inbox, mediating customer inquiries without exposing vendors or allowing off-platform disintermediation. |
| **9** | **Security & Integrity** | Zero-Trust Defense | IDOR scoping, universal 6-digit cryptographic OTPs, customer PII masking, brute-force lockouts, and anti-mass assignment. |
| **10** | **Physical Operations** | Real-World Assets | Rider identification/bags, vendor onboarding visits, packaging standards, and physical pickup location approvals. |

---

## 2. High-Level Macro Topology

```text
                             VICTORIOUS MARKET
                                    │
                  ┌─────────────────┴─────────────────┐
                  │                                   │
         SOFTWARE PLATFORM                   PHYSICAL OPERATIONS
                  │                                   │
          ┌───────┼───────┐                   ┌───────┼───────┐
          │       │       │                   │       │       │
       VM App  VV App  VD App              Vendors  Riders Support
      (Shopper) (Store) (Courier)             │       │       │
          │       │       │                   │       │       │
          └───────┼───────┘                   └───────┼───────┘
                  │                                   │
                  └─────────── ADMIN TOWER ───────────┘
                              (Central SSOT)
                                    │
                        ┌───────────┼───────────┐
                        │           │           │
                    PAYMENTS     RETURNS   SETTLEMENTS
                    (Paystack/   (Reverse   (Escrow &
                     OPay)      Logistics)  Payables)
```

---

## 3. The Prime Invariant: Software-Operations Symmetry

Every operational rule practiced on the ground must be hard-coded as a server-side invariant in the software:

| Business & Operational Rule | Software Enforcement (Laravel & Mobile) | Operations Enforcement (On Ground) |
|---|---|---|
| **Zero Customer $\leftrightarrow$ Vendor Contact** | API returns `403 Forbidden` for direct chat; phone/email stripped from vendor endpoints. | Vendor support never discloses customer contact information to vendors under any circumstance. |
| **Payment Authority Invariant** | Unverified OPay / offline payment blocks fulfillment (`403 Forbidden`). | Orders are never marked verified from paper slips or customer screenshots alone; Admin reconciles bank/OPay session IDs. |
| **Pay-at-Pickup Release Gate** | `InShopHandoverController` blocks OTP verification if `payment_status !== 'paid'`. | Vendors are trained never to release goods without the app unlocking the `[ VERIFY PICKUP OTP ]` screen. |
| **Custody Handshake Separation** | 3 distinct cryptographic OTPs (Vendor $\rightarrow$ Rider, Rider $\rightarrow$ Customer, Vendor $\rightarrow$ Customer). | Riders cannot depart vendor shops without receiving the pickup OTP; customers cannot claim delivery without giving the delivery OTP. |
| **No Delivery-on-Credit / Zero COD** | COD is disabled for delivery orders. | Riders never carry cash for delivered goods; goods in transit are already paid and held in Vmarket Escrow. |

---

## 4. Financial Architecture: Escrow, Settlements & Commission

Victorious MARKET enforces a strict financial lifecycle with zero mathematical drift ($\Delta = 0.00$):

```text
Order Placed
     │
Payment Verified (Paystack Webhook OR Admin OPay Reference Verification)
     │
Order Inflow ──> Held in Vmarket Escrow (Vendor Wallet unaffected)
     │
Fulfillment Complete (Delivery OTP verified OR Customer Pickup OTP verified)
     │
Escrow Released:
     ├── Platform Commission (Credited to Admin Account)
     └── Vendor Payable Net Balance (Credited to Vendor Wallet)
     │
Vendor Withdrawal Request ──> Pessimistic DB Lock ──> Admin Approval ──> NUBAN Payout
```

> **Axiom:** Vendor settlement balance is an **earned payable balance** held in escrow until fulfillment verification. It is fundamentally distinct from a stored-value customer wallet.

---

## 5. Standard Operating Procedures (SOPs)

### A. Customer Delivery Issue Workflow
```text
Customer reports problem via VM Inbox
        ↓
Support team verifies order status in Admin Tower
        ↓
Support checks active rider location & transit history
        ↓
Support contacts rider via internal operations channel
        ↓
Determine failure reason:
  - Customer unreachable (Phone off)
  - Wrong address / landmark not found
  - Reschedule requested
  - Package rejected
        ↓
Authorize reschedule OR initiate Return-to-Merchant (RTM)
        ↓
Case closed and logged in audit history
```

### B. OPay Payment Verification Workflow
```text
Customer places order and selects OPay Transfer
        ↓
Customer submits OPay Transaction Reference / Session ID
        ↓
Order remains in 'pending' / 'unpaid' status (Fulfillment locked)
        ↓
Vmarket Finance Officer checks Vmarket OPay Merchant Account
        ↓
Transaction reference and amount matched exactly
        ↓
Admin clicks [ Verify OPay Payment ] in Admin Command Center
        ↓
Order transitions to 'confirmed' / 'paid' ──> Vendor notified to prepare
```

### C. Return & Refund Workflow
```text
Customer requests return within return window
        ↓
Vmarket Support reviews product category and eligibility
        ↓
Approved for return ──> Dispatch rider retrieves package
        ↓
Quality control inspection at Vmarket Hub / Approved Vendor Shop
        ↓
Condition verified intact
        ↓
Admin approves refund ──> Paystack/Bank refund initiated
        ↓
Vendor ledger debited + Pro-rata platform commission reversed (Δ = 0.00)
```

---

## 6. Phased Rollout Blueprint: The Uyo Pilot

To ensure maximum operational focus and eliminate overhead, Victorious MARKET launches with a disciplined pilot:

```text
PHASE 1: UYO PILOT
├── Geography: Uyo Urban (Ibom Plaza, Aka Road, Abak Road, Oron Road, Ikot Ekpene Road)
├── Merchant Density: 10 Vetted High-Quality Vendors
├── Catalog Size: 50 – 100 Fast-Moving SKUs
├── Logistics: 3 – 5 Dedicated, Vetted Victorious Delivery Riders
├── Payment Rails: Paystack (Cards/Bank), OPay (Direct Transfer), Pay-at-Pickup
└── Operations Team: 1 Admin/Dispatcher + 1 Finance/Customer Support Lead
```

```text
EXPANSION TRAJECTORY:
Phase 1: Uyo Pilot (Operational Proof & Cashflow Parity)
    ↓
Phase 2: Akwa Ibom Expansion (Eket, Ikot Ekpene, Oron)
    ↓
Phase 3: South-South Regional Scale (Calabar, Port Harcourt)
    ↓
Phase 4: Nationwide Marketplace (All Nigerian Commercial Centers)
```

---

## 7. Scope Discipline: What NOT to Build Prematurely

To prevent bloat and maintain absolute focus on customer satisfaction and cashflow:

- ❌ **No Unmonitored Customer $\leftrightarrow$ Vendor Chat** (Preserves platform authority and prevents disintermediation).
- ❌ **No Uncontrolled Cash on Delivery (COD)** (Eliminates rider robbery, fake orders, and cash shortages).
- ❌ **No Complex Multi-Tier MLM / Loyalty Tokens** (Focus on pricing and delivery speed).
- ❌ **No Nationwide Logistics on Day One** (Master the Uyo urban radius first).
- ❌ **No Premature AI Overhead** (Deterministic state machines and human support deliver higher trust).

---

## 8. Summary Commitment

Every line of code written in `backend/vmarket-web/`, `User app/`, `Vendor app/`, and `Delivery Man App/` directly serves this operational machine. When software and operations move in locked step, Victorious MARKET operates as an unbeatable commerce powerhouse.
