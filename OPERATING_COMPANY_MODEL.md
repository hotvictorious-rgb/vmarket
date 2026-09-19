# 🏛️ Victorious MARKET (VMarket) — Operating Company Model & Institutional Architecture

> **Authoritative Specification: VMarket as an Operating Company**  
> *Permanent Institutional Blueprint for Strategic, Operational, Commercial, and Technical Execution.*

---

## 1. Executive Definition: What VMarket Actually Is

**Victorious MARKET (VMarket)** is not merely an e-commerce website, not simply an online shop, and not a generic marketplace plugin.

> **Victorious MARKET is a controlled marketplace + in-house merchant + delivery operator + settlement platform, launching locally in Uyo Local Government Area (LGA) and scaling systematically LGA by LGA.**

VMarket is an end-to-end commercial operating company that controls:
1. **The Customer Relationship & Experience:** Single trusted storefront, verified merchant standard, dispute resolution, and post-purchase customer loyalty.
2. **The Commercial Transaction & Escrow:** 100% of customer payments flow into VMarket; funds are held, partitioned, and settled by the platform.
3. **The Physical Delivery & Logistics Network:** Centralized dispatch, route optimization, motor parks, and delivery fleet operations.
4. **The Merchant Verification & Standards:** Pre-vetted vendor onboarding, quality assurance, fixed pricing discipline, and local pickup point accreditation.

VMarket begins with a hyper-focused geographical footprint:
* **Phase 1 Launch Ground:** **Uyo LGA first.**
* **Subsequent Expansion:** Activating additional LGAs and motor park logistics routes sequentially across Akwa Ibom State, Niger Delta, and West Africa.

```
                    VICTORIOUS MARKET (VMarket)
                               │
             ┌─────────────────┴─────────────────┐
             │                                   │
      VMarket Itself                     Verified Merchants
      (In-House Merchant)                        │
                                                LGA
                                                 │
                                       1 or More Approved
                                          Pickup Points
```

---

## 2. Five Distinct Businesses Under One Ecosystem

VMarket unifies five distinct business models into a single, tightly controlled ecosystem:

| # | Business Domain | Strategic Function | Operational Authority |
|---|---|---|---|
| **1** | **Marketplace Operator** | Connects retail shoppers with vetted local merchants with fixed, transparent pricing. | VMarket Super Admin & Merchant Governance |
| **2** | **In-House Merchant** | VMarket procures, warehouses, and sells its own proprietary retail inventory. | VMarket Merchandising & Inventory Operations |
| **3** | **Logistics & Delivery Operator** | Operates a centralized delivery fleet, route network, and transit park connections. | VMarket Dispatch & Rider Fleet Command |
| **4** | **Payment & Settlement Platform** | Collects funds via Paystack, enforces balance reconciliation, and manages vendor payouts. | VMarket Finance & Treasury |
| **5** | **Customer Loyalty System** | Allocates 5% merchandise cashback from VMarket's own commission, maturing after 24h. | VMarket Customer Loyalty & Ledger Engine |

---

## 3. The Vendor Operating Model

In VMarket, a vendor is **not** primarily modeled as a complex corporation with dozens of independent accounting branches. That keeps V1 lean, resilient, and audit-friendly.

### The Canonical Vendor Entity
> **A verified local merchant operating in a specific LGA with one or more approved physical pickup points.**

```
Vendor: ABC Electronics
Operating LGA: Uyo

├── Approved Pickup Point 1: 14 Ikot Ekpene Road, Uyo
├── Approved Pickup Point 2: 88 Oron Road, Uyo
└── Approved Pickup Point 3: Plaza Mall, Shop 12, Uyo
```

- **Approved Pickup Points:** Pre-inspected physical retail locations where shoppers can inspect and collect orders. Pickup points are physical collection locations, not separate legal entities or independent accounting books in V1.
- **Vendor Privacy:** The vendor's private home or non-retail premises are strictly protected. Only accredited, customer-accessible pickup points are displayed.

### Vendor Organizational Hierarchy & Role-Based Access Control (RBAC)
Vendors can employ staff who operate under granular role permissions:
```
ABC Electronics
├── Owner (Full financial control, bank account management, staff delegation)
├── Manager (Catalog updates, inspection handling, order fulfillment)
├── Sales Staff (Inventory stock counts, in-shop inspection assistance)
└── Pickup Staff (Verification code validation, inspection handover)
```

---

## 4. VMarket as an In-House Merchant

VMarket is both the ringmaster and a merchant in the ring:
* **Seller Type A (In-House VMarket):** Products owned, procured, and fulfilled directly by Victorious MARKET (`seller_is = 'admin'`).
* **Seller Type B (Third-Party Verified Merchants):** Products supplied by vetted independent vendors (`seller_is = 'seller'`).

The customer experiences one unified, world-class storefront. Whether buying from VMarket directly or from a certified merchant, transaction security, warranty rules, and return protections remain identical.

---

## 5. The Dual-Fulfillment Customer Journey

The customer experience is intentionally simple and frictionless, hiding immense backend orchestration behind a single decisive choice at checkout:

```
                      Browse Catalog
                            ↓
                    Select Product(s)
                            ↓
                       Add to Cart
                            ↓
                        Checkout
                            ↓
              ┌─────────────┴─────────────┐
              ▼                           ▼
        DOORSTEP DELIVERY           IN-SHOP PICKUP
     (Pay First, Ship Later)   (Inspect First, Pay Later)
```

---

## 6. The Logistics & Delivery Operational Model

### Vendors Do Not Control Delivery
On VMarket, independent merchants do **not** manage customer delivery. Allowing individual vendors to handle their own shipping creates uneven pricing, fragmented customer service, and untracked packages.

**VMarket operates delivery centrally:**
1. Customer pays merchandise + delivery fee at checkout.
2. Order is confirmed and transmitted to vendor.
3. Vendor packages the goods.
4. VMarket motorized dispatch rider collects the package from the vendor's accredited shop.
5. VMarket routes the package through its local network or motor park transit hubs.
6. Customer inspects and receives doorstep delivery.

### LGA & Park Transit Progression
VMarket delivery expands geographically without rewriting software:
* **Stage 1 (Hyper-Local):** Intra-Uyo urban dispatch (rider to doorstep).
* **Stage 2 (Intra-State Hubs):** Uyo $\rightarrow$ Akwa Ibom motor park hubs (Ikot Ekpene, Eket, Oron).
* **Stage 3 (Inter-State Hubs):** Uyo $\rightarrow$ Regional inter-state parks (Calabar, Port Harcourt, Aba).
* **Stage 4 (New LGA Activation):** Activating new LGA merchant clusters (e.g., Abak LGA $\rightarrow$ Active) plug directly into the existing routing engine.

---

## 7. Strict Financial Separation: Delivery Money vs. Merchandise Money

One of VMarket's inviolable accounting invariants is the **complete decoupling of delivery fees from merchandise revenue**:

$$\text{Customer Payment} = \text{Merchandise Amount} + \text{Delivery Fee}$$

The platform **never** includes delivery fees in the merchant commission calculation.

### Financial Partitioning Example
- **Product Price:** ₦100,000
- **Delivery Fee:** ₦2,000
- **Total Paid by Customer:** ₦102,000

#### Merchandise Partition (₦100,000)
* **Merchant Share (90%):** ₦90,000
* **Customer Cashback Allocation (5%):** ₦5,000
* **VMarket Retained Margin (5%):** ₦5,000

#### Delivery Partition (₦2,000)
* **VMarket Delivery & Rider Operations:** ₦2,000 (100% reserved for dispatch, fuel, rider payout, and logistics overhead).

*Result:* Zero accounting drift ($\Delta = \text{₦}0.00$). The merchant is never underpaid or overcharged for logistics.

### Delivery Fee Refund Policy (Delivered vs. Undelivered)
1. **Actual Customer Delivery Occurred (`received_at != null`):**
   - The delivery service was completed by VMarket logistics.
   - **Delivery fee is NON-REFUNDABLE.**
   - Approved return within the 24-hour window refunds merchandise only (₦100,000 refund, ₦2,000 delivery fee retained by VMarket).
   - Zero delivery fee reversal is executed.
2. **Actual Customer Delivery Did NOT Occur (`received_at == null`):**
   - Delivery was never completed (order cancelled before handover, delivery failure, damaged parcel in transit).
   - **Delivery fee is REFUNDABLE.**
   - If the order is cancelled/failed and a customer payment refund is approved, the refund includes the delivery fee.
   - Customer receives the applicable full payment refund of all amounts paid for that undelivered child Vendor Order (₦102,000 full refund).
3. **Dispatch Boundary:** `out_for_delivery` alone does NOT earn the delivery fee. Only confirmed customer receipt (`received_at != null`) makes the delivery fee earned and non-refundable.
4. **No Post-Delivery Reversals:** No separate delivery-fee reversal mechanism for normal post-delivery merchandise returns. Delivery-fee reversal and deduction from `AdminWallet.delivery_charge_earned` is strictly reserved for cancellations or failed orders where actual delivery never occurred.
5. **Separation from 90/5/5 Economics:** Delivery fee is completely separate from merchandise economics (90% vendor, 5% cashback, 5% VMarket retained).


---

## 8. The Core Commercial Invariant: 90 / 5 / 5 Split

VMarket operates on a transparent, equitable commercial foundation:

```
                      100% Merchandise Total
                                │
         ┌──────────────────────┴──────────────────────┐
         ▼                                             ▼
    90% to Vendor                                10% to VMarket
   (Guaranteed Net)                                    │
                                        ┌──────────────┴──────────────┐
                                        ▼                             ▼
                                5% Customer Cashback          5% VMarket Retained
                                  (Reward Ledger)               (Net Platform)
```

### Invariant Breakdown (₦100,000 Sale)

| Allocation Beneficiary | Percentage | Exact Amount | Financial Source |
|---|:---:|---:|---|
| **Verified Merchant** | **90%** | **₦90,000** | Merchandise Principal |
| **Customer Cashback** | **5%** | **₦5,000** | VMarket 10% Commission |
| **VMarket Retained** | **5%** | **₦5,000** | VMarket 10% Commission |
| **Total Merchandise** | **100%** | **₦100,000** | $\Delta = \text{₦}0.00$ |

> **Critical Commercial Rule:** Customer cashback is funded **entirely out of VMarket's 10% commission**, never deducted from the vendor's 90% share. Vendors always receive their full 90%.

---

## 9. The In-Shop Pay-After-Inspection Pickup Engine

Victorious MARKET's pickup proposition revolutionizes African retail commerce by solving trust:

> **Customers physically inspect products BEFORE making any payment.**

### Lifecycle Flow
```
Customer browses catalog
       ↓
Creates Pickup Reservation (Selects Vendor + Approved Pickup Point)
       ↓
System generates human-friendly Reservation Code (e.g. RES-AB12CD34)
       ↓
Customer visits Vendor Pickup Point & presents Reservation Code
       ↓
Vendor staff verifies code on Vendor Portal / App
       ↓
Customer physically examines the product
       ↓
┌──────────────────────┴──────────────────────┐
▼                                             ▼
Customer REJECTS                              Customer ACCEPTS
• Vendor logs reason                          • Vendor marks "Inspected & Accepted"
• Reservation canceled                        • Online payment unlocks for Customer
• Zero money debited                          • Customer pays ₦100,000 to VMarket via Paystack
                                              • Order created atomically
                                              • Handover OTP generated
                                              • Vendor enters OTP to release item
```

---

## 10. The Verification Secrets Topology Across Delivery & Pickup

VMarket enforces a strict separation of cryptographic secrets across both fulfillment modes:

### A. Delivery Orders (Two Separate Codes)
1. **Vendor Pickup Code (`pickup_verification_code`):**
   - Given to the assigned deliveryman/rider upon arriving at the vendor's physical location.
   - Authorizes transfer of parcel custody from vendor to rider (`OUT_FOR_DELIVERY`).
   - **Zero-Trust Guard:** Knowing the code alone is insufficient; the rider must be an authorized deliveryman assigned to that order (`delivery_man_id == rider.id`).
2. **Customer Delivery Code (`verification_code`):**
   - Given by the customer to the rider at the doorstep.
   - Authorizes final delivery completion (`DELIVERED`).
   - Records the authoritative `received_at` timestamp and starts the **24-hour return protection window**.

### B. Pickup Orders (Two Separate Secrets)
1. **Reservation Code (`RES-XXXXXXXX`):**
   - Identifies customer intent upon arriving at the pickup point and unlocks physical goods inspection.
   - Zero financial authority; does not release inventory.
2. **In-Shop Handover OTP (6-Digit Cryptographic):**
   - Generated only after verified digital payment to VMarket.
   - Entered by merchant staff to authorize final physical release of goods to customer.

### Inviolable Secret Inequality:
$$\text{Vendor Pickup Code} \neq \text{Customer Delivery Code} \neq \text{In-Shop Handover OTP} \neq \text{Reservation Code}$$

| Secret Token | Fulfillment Domain | Creation Moment | Lifecycle Purpose | Security Authority |
|---|:---:|---|---|---|
| **Vendor Pickup Code** | Delivery | Upon Order Creation | Authorizes transfer of custody from vendor to assigned rider | Assigned Rider $\leftrightarrow$ Vendor Staff |
| **Customer Delivery Code** | Delivery | Upon Order Creation | Authorizes doorstep delivery completion & starts 24h window (`received_at`) | Customer $\rightarrow$ Delivery Rider |
| **Reservation Code** | Pickup | Upon Cart Reservation | Identifies customer intent & authorizes in-shop inspection | Customer $\rightarrow$ Vendor Staff |
| **In-Shop Handover OTP** | Pickup | Upon Verified Payment | Authorizes physical release of paid goods & starts 24h window (`received_at`) | Customer $\rightarrow$ Vendor Staff |

### The 3 Distinct Fulfillment Codes & Events

| Fulfillment Path | Verification Code | What It Proves | Custody Direction | Financial & Operational Authority |
|---|---|---|---|---|
| **Rider Delivery Pickup** | **Vendor Pickup Code** (`pickup_verification_code`) | Rider collected package from vendor | Vendor $\rightarrow$ Rider | Custody transfer (`OUT_FOR_DELIVERY`). NOT delivered to customer. Delivery fee NOT earned. |
| **Rider Delivery Completion** | **Customer Delivery Code** (`verification_code`) | Customer received package from rider | Rider $\rightarrow$ Customer | Doorstep receipt (`received_at`). Delivery service completed. Delivery fee becomes NON-REFUNDABLE. 24h return window begins. |
| **Customer Pickup Handover** | **Customer Handover OTP** (`verification_code`) | Customer received package directly from vendor | Vendor $\rightarrow$ Customer | In-shop direct receipt (`received_at`). 24h return window begins. Zero rider involved. |

#### Inviolable Fulfillment Invariants:
1. **Rider pickup $\neq$ customer pickup:** Rider pickup is custody handover to logistics; customer pickup is direct final handover from vendor to customer.
2. **Rider pickup $\neq$ delivery completion:** Rider collection does not complete the delivery service.
3. **Customer pickup $\neq$ delivery:** Customer pickup involves no riders and no delivery fees.
4. Both **customer delivery completion** and **customer pickup handover** set `received_at` and start the 24-hour return clock, but through their own separate fulfillment flows.


---

## 11. Zero Inventory Holds on Pickup Reservations

A pickup reservation is an **inspection commitment**, NOT an inventory freeze:
- Making a reservation leaves `Product.current_stock` completely untouched.
- Merchant physical store inventory remains the single source of truth.
- When the customer inspects, accepts, and pays, physical stock is decremented atomically under pessimistic database locks (`WHERE current_stock >= qty`).
- This prevents malicious users or botnets from locking up retail shelves without paying.

---

## 12. The 24-Hour Return Window & Cashback Maturation

Cashback is not paid out instantaneously at the moment of checkout. It matures through a controlled financial lifecycle:

```
Order Completed (Delivery Received OR Pickup Handover)
       ↓
24-Hour Return / Refund Window Begins
       ↓
Are there active refund requests or dispute claims?
  ├── YES: Cashback revoked/quarantined during dispute resolution.
  └── NO: Window closes after exactly 24 hours.
       ↓
5% Cashback transitions to "Available" in Customer Reward Ledger.
```

### Key Timestamp Attributes on Every Order:
* `paid_at`: Gateway capture confirmation.
* `received_at`: Timestamp of physical delivery or pickup handover.
* `refund_window_expires_at`: Exactly `received_at + 24 hours`.
* `cashback_eligible_at`: Timestamp when funds become redeemable.

---

## 13. Controlled Settlement Architecture: VMarket Retains Financial Custody

In Victorious MARKET, vendors **never** collect funds directly from shoppers and remit commission later. 

$$\text{Customer} \longrightarrow \text{Victorious MARKET (Paystack Escrow)} \longrightarrow \text{Vendor Settlement}$$

### V1 Manual Vendor Payout Discipline
To ensure absolute accounting integrity and prevent runaway automated disbursement errors during the initial phase:
1. Every settled order updates the vendor's internal ledger balance (`total_earning = 90%`).
2. Super Admin reviews eligible vendor payable balances inside the Command Center.
3. VMarket treasury initiates bank transfers via corporate banking or Paystack Transfer.
4. Transaction reference numbers and payout receipts are recorded on the system.
5. Settlement status transitions atomically to `PAID`.

---

## 14. Two Distinct Employee Populations & Security Scoping

VMarket strictly segregates institutional personnel:

### 1. Vendor Employees
- Hired by and affiliated with a specific `vendor_id`.
- Permitted actions: view assigned shop inventory, verify reservation codes, inspect goods, validate handover OTPs.
- **Strict Boundary:** Cannot view VMarket platform finances, delivery rider locations, or other vendors' data.

### 2. VMarket Platform Employees
- Hired directly by Victorious MARKET (`admin_id`).
- Permitted actions: platform auditing, merchant KYC verification, fleet logistics dispatch, dispute arbitration, treasury settlement.
- **Strict Boundary:** Subject to Super Admin Role-Based Access Control (RBAC).

---

## 15. The Controlled Marketplace Standard: Zero Haggling, 100% Vetted

Victorious MARKET upholds a premium commercial reputation:
* **No Unverified Sellers:** Anyone wanting to sell must pass business registration, physical premises verification, and NUBAN bank account resolution.
* **Fixed, Non-Negotiable Pricing:** Products have clearly displayed prices. No "DM for price," no WhatsApp haggling, no opaque surcharges.
* **30-Day Price Freshness:** Automated schedulers require vendors to confirm prices every 30 days to prevent inflation-driven cancellations.

---

## 16. Multi-Vendor Cart Handling

Shoppers can add items from multiple vendors to a single basket:
* **For Delivery Orders:** VMarket handles multi-vendor logistics consolidation behind the scenes.
* **For Pickup Orders:** The platform splits the cart strictly by `seller_id + shop_id`, generating one distinct `PickupReservation` per physical pickup location.

---

## 17. The Four-Phase Geographic Scaling Trajectory

VMarket is designed to scale across Africa without structural software re-engineering:

```
  PHASE 1: Hyper-Local Foundation
  • Launch Uyo LGA
  • Onboard Uyo verified merchants & physical pickup points
  • Establish VMarket intra-Uyo doorstep delivery fleet
  • Settle 90/5/5 split with manual treasury controls

  PHASE 2: Transit Park Network
  • Connect Uyo logistics to Akwa Ibom intra-state motor parks
  • Open hub routes to Eket, Ikot Ekpene, Oron
  • Enable park-to-park delivery with OTP transfer

  PHASE 3: Inter-State Park Routes
  • Connect Uyo hub to commercial regional parks (Calabar, Port Harcourt, Aba)
  • Syndicate regional verified merchant catalogs

  PHASE 4: LGA Expansion Rollout
  • Activate contiguous LGAs sequentially (Abak, Ikot Ekpene, Eket)
  • Each new LGA inherits the same verified merchant, pickup, and delivery pipeline
```

---

## 18. The Core VMarket Operating Thesis

> **Victorious MARKET is a controlled online marketplace starting in Uyo LGA, allowing VMarket and verified local merchants to sell quality goods, empowering shoppers to choose between VMarket-managed delivery or physical in-shop inspection pickup, holding all funds securely in escrow, distributing 90% to vendors, allocating 5% to customer cashback and 5% to platform operations, with a 24-hour return protection window.**

*This document serves as the immutable operating doctrine for all Victorious MARKET engineers, product managers, logistics operators, and executive leadership.*

---

## 19. Authoritative Rulebook Reference

For the comprehensive 42-rule operational standard governing fulfillment isolation, per-vendor delivery fees, 24-hour return boundaries, and zero-trust security invariants, consult:
* **[V1_BUSINESS_RULEBOOK.md](file:///c:/Users/SOOQ%20ELASER/Downloads/vmarket/V1_BUSINESS_RULEBOOK.md)** — The complete, binding operational specification.

