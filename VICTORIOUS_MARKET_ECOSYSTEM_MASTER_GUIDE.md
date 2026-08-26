# 🌟 VICTORIOUS MARKET (VMARKET) ECOSYSTEM MASTER MANUAL
## *The Definitive End-to-End Architectural, Operational & Scenario Walkthrough*

---

# TABLE OF CONTENTS
1. [EXECUTIVE ARCHITECTURE & SYSTEM TOPOLOGY](#1-executive-architecture--system-topology)
2. [ACTOR 1: SUPER ADMIN & REGIONAL HUB COMMAND CENTER](#2-actor-1-super-admin--regional-hub-command-center)
3. [ACTOR 2: PHYSICAL MERCHANT & OMNICHANNEL VENDOR](#3-actor-2-physical-merchant--omnichannel-vendor)
4. [ACTOR 3: ONLINE SHOPPER (WEB & MOBILE APP)](#4-actor-3-online-shopper-web--mobile-app)
5. [ACTOR 4: DELIVERY RIDER & LOGISTICS DISPATCHER](#5-actor-4-delivery-rider--logistics-dispatcher)
6. [IN-DEPTH STEP-BY-STEP OPERATIONAL SCENARIOS](#6-in-depth-step-by-step-operational-scenarios)
   - [Scenario A: Walk-In Counter Sale (Split Payment & Thermal Receipt)](#scenario-a-walk-in-counter-sale)
   - [Scenario B: Inter-Branch Anti-Theft Waybill & Variance Detection](#scenario-b-inter-branch-anti-theft-waybill)
   - [Scenario C: Online Marketplace Order to Doorstep OTP Delivery](#scenario-c-online-marketplace-order-to-doorstep-delivery)
   - [Scenario D: Merchant Multi-Branch SaaS Subscription Upgrade](#scenario-d-merchant-multi-branch-saas-subscription)
   - [Scenario E: 30-Day Customer Debt Recovery & Partial Repayment](#scenario-e-30-day-customer-debt-recovery)
7. [ENTERPRISE SECURITY, FINANCIAL & GOVERNANCE INVARIANTS](#7-enterprise-security-financial--governance-invariants)

---

# 1. EXECUTIVE ARCHITECTURE & SYSTEM TOPOLOGY

Victorious MARKET is an omnichannel commerce operating system built to unify physical offline retail POS, multi-branch inventory logistics, customer credit debt ledgers, and an e-commerce marketplace into a single synchronized ecosystem.

```
                                    ┌─────────────────────────────────────────┐
                                    │    VICTORIOUS MARKET UNIFIED DATABASE   │
                                    │       (MySQL on SSD Cloud Cluster)      │
                                    └────────────────────┬────────────────────┘
                                                         │
          ┌───────────────────────────┬──────────────────┴───────────────┬───────────────────────────┐
          │                           │                                  │                           │
┌─────────▼─────────────┐   ┌─────────▼─────────────┐          ┌─────────▼─────────────┐   ┌─────────▼─────────────┐
│ SUPER ADMIN DASHBOARD │   │ VENDOR WEB & POS ERP  │          │ CUSTOMER STOREFRONT   │   │ FLUTTER MOBILE SUITE  │
│ (/admin)              │   │ (/vendor & /vendor/pos│          │ (shop.victorious...)  │   │ • User App (Provider) │
├───────────────────────┤   ├───────────────────────┤          ├───────────────────────┤   │ • Vendor App(Provider)│
│ • SaaS MRR & Branches │   │ • Counter Barcode POS │          │ • City Hub Filtering  │   │ • Rider App (GetX)    │
│ • Waybill Theft Radar │   │ • Debt Aging Ledger   │          │ • Paystack / Escrow   │   ├───────────────────────┤
│ • Commission Engine   │   │ • Anti-Theft Waybills │          │ • Zero-Spam Inbound   │   │ • Camera Barcode Scan │
│ • Vendor Approvals    │   │ • Staff Handshake OTP │          │ • Real-Time Tracking  │   │ • Bluetooth Receipts  │
└───────────────────────┘   └───────────────────────┘          └───────────────────────┘   └───────────────────────┘
```

### System Repositories & Destination Mapping
1. **Laravel Web Monorepo Backend (`backend/vmarket-web`):** Powers the central REST APIs, the Super Admin Command Center, the Vendor Dashboard, the Web POS Counter, and the Storefront (`shop.victoriousmarket.com.ng`).
2. **Customer Mobile App (`User app/`):** Built with Flutter (Provider State Management). Connects to REST API v3, allowing customers to browse location-hub products, pay online, track riders, and confirm doorstep OTPs.
3. **Vendor Mobile App (`Vendor app/`):** Built with Flutter (Provider State Management). Gives merchant owners and sales attendants a pocket POS register, camera barcode scanning, Bluetooth 58mm/80mm thermal receipt printing, and order packing verification.
4. **Delivery Rider App (`Delivery Man App/`):** Built with Flutter (GetX State Management). Facilitates instant dispatch acceptance, in-shop staff handshake OTP generation, in-transit navigation, and customer delivery OTP confirmation.

---

# 2. ACTOR 1: SUPER ADMIN & REGIONAL HUB COMMAND CENTER

The Super Admin represents the platform executive team, overseeing compliance, logistics integrity, financial escrow balancing, and platform revenue.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       SUPER ADMIN COMMAND CENTER SUITE                      │
├─────────────────────────────────────────────────────────────────────────────┤
│ 1. POS Command Dashboard (/admin/pos-management/dashboard)                  │
│    • Live In-Transit Waybill Theft Radar (Driver shortages & variances)     │
│    • Real-Time SaaS Monthly Recurring Revenue (MRR) & Active Branches       │
│    • Outstanding Merchant Customer Debt Volume Across All Shops             │
│                                                                             │
│ 2. POS Dynamic SaaS Configuration Deck (/admin/pos-management/settings)     │
│    • Monthly Pro Branch Price (e.g. ₦15,000 / month / branch)               │
│    • Free Branch Allowance (Default: 1 Primary Branch Free Forever)         │
│    • Auto-Grace Period (Default: 7 Days)                                    │
│                                                                             │
│ 3. Marketplace Application Queue (/admin/pos-management/marketplace-apps)   │
│    • Review private POS-only merchants applying to sell online              │
│    • 1-Click Verification & Immediate Storefront Publishing                 │
│                                                                             │
│ 4. Pure Commission Engine (/admin/business-settings/seller-settings)        │
│    • Default Global Platform Commission (e.g. 10% Flat Rate)                │
│    • Category-Wise & Custom Vendor Override Support                         │
│    • Zero Price-Fixing Bottlenecks (Vendors set own retail prices)          │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Key Administrative Workflows:
* **SaaS Revenue Collection:** Automatically aggregates subscription renewals from multi-branch merchants paid via Paystack, bank deposit, or vendor wallet deductions.
* **Waybill Theft Interception:** If a driver departs Branch A with 50 sacks of rice and the receiving manager at Branch B counts 48 sacks, the radar flags a **Theft Variance Alert** on the Admin dashboard with driver details and vehicle number.
* **Escrow Financial Safety:** Every payment made by online customers is held securely in platform escrow until the delivery rider successfully inputs the customer's 6-digit Delivery OTP.

---

# 3. ACTOR 2: PHYSICAL MERCHANT & OMNICHANNEL VENDOR

The Merchant is the heartbeat of Victorious MARKET. They may start as a small physical shop using the free POS, expand to multiple locations, and sell online across Nigeria.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                     VENDOR OMNICHANNEL SUITE & ERP ENGINE                   │
├─────────────────────────────────────────────────────────────────────────────┤
│ 1. High-Speed Counter POS (/vendor/pos)                                     │
│    • 100ms USB / Bluetooth / Camera Barcode Search & SKU Matching           │
│    • Negotiated Unit Pricing (Bulk wholesale vs Single retail)              │
│    • Split Payments (e.g. ₦50k Cash + ₦30k Card + ₦20k Customer Debt)       │
│    • Dual Fulfillment: "Take Away Now" vs "Packed for Later Pickup"         │
│    • Blind-Close Cashier Shifts (Staff counts physical cash before seeing   │
│      system expected total, preventing end-of-day skimming)                 │
│                                                                             │
│ 2. Customer Debt Aging Ledger (/vendor/pos/debt-ledger)                     │
│    • Automated 30-Day Aging Buckets:                                        │
│      - 🟢 Current (0 - 7 Days)                                              │
│      - 🟡 Due (8 - 30 Days)                                                 │
│      - 🔴 Critical Overdue (30+ Days Default Risk)                          │
│    • Partial Repayment Engine (Direct cash credit to ledger)                │
│                                                                             │
│ 3. Inter-Branch Anti-Theft Waybills (/vendor/branch/transfers)              │
│    • Step 1: Dispatch from Origin (Stock leaves active shelf into In-Transit)│
│    • Step 2: Driver Transit Slip (Contains unique transit code & phone)    │
│    • Step 3: Destination Physical Count (Receiving staff counts goods blind;│
│      system automatically records driver shortage liability)                │
│                                                                             │
│ 4. Staff-Attributed Handshake Protocol (/vendor/orders/[id])                │
│    • Rider arrives to pick up online order and presents 6-digit Secret OTP  │
│    • Acting cashier types OTP into POS / Order screen                       │
│    • System verifies OTP in constant-time and permanently stamps:           │
│      "Handed Over by: Blessing Okon (Staff #14) at 02:45 PM"                │
│                                                                             │
│ 5. 1-Click Storefront Link Sharing (/vendor/shop/update)                    │
│    • Prominent Digital Storefront URL Banner                                │
│    • [ 📋 Copy Link ] & [ 📲 Share on WhatsApp ] Buttons                    │
└─────────────────────────────────────────────────────────────────────────────┘
```

### The In-Store Hardware Integration:
* **Thermal Printers (58mm & 80mm):** Compatible with standard ESC/POS USB, Ethernet, and Bluetooth thermal printers.
* **Mandatory Viral Branding:** All receipts automatically carry the footer:
  `Powered by Victorious MARKET - Your Trusted Online Market`
* **Barcode Scanners:** Supports handheld USB laser guns, 2D QR scanners, and the Vendor App camera scanner.

---

# 4. ACTOR 3: ONLINE SHOPPER (WEB & MOBILE APP)

The Customer experiences a fast, transparent, and secure shopping experience whether ordering groceries, fashion, electronics, or household goods.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      CUSTOMER STOREFRONT & APP EXPERIENCE                   │
├─────────────────────────────────────────────────────────────────────────────┤
│ 1. Location-Hub Routing & Storefront Badges                                 │
│    • Storefront cards display: "📍 Ships from: Uyo Central Hub"             │
│    • Keeps vendor physical home addresses private while proving local stock │
│                                                                             │
│ 2. Seamless Checkout & Multi-Channel Payment                                │
│    • Instant Paystack Debit Card, Bank Transfer, USSD & Wallet              │
│    • Cash on Delivery (COD) with Change Request Amount calculation          │
│                                                                             │
│ 3. Zero-Spam Inbound-Only Communication Policy                              │
│    • Walk-in POS customers are NEVER spammed by bots                        │
│    • Online registered customers receive real-time order tracking alerts    │
│                                                                             │
│ 4. 6-Digit Doorstep Security Delivery OTP                                   │
│    • Generated upon order dispatch                                          │
│    • Given to the delivery rider ONLY when goods are in customer hands      │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

# 5. ACTOR 4: DELIVERY RIDER & LOGISTICS DISPATCHER

The Delivery Rider operates through the Flutter Rider App (GetX), executing rapid intra-city dispatches and inter-hub transfers.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      DELIVERY RIDER LOGISTICS APP SUITE                     │
├─────────────────────────────────────────────────────────────────────────────┤
│ 1. Instant Dispatch Alert                                                   │
│    • Audible vibration alert with store pickup location & customer hub      │
│                                                                             │
│ 2. In-Shop Handshake Verification                                           │
│    • Rider displays their dynamic 6-digit Secret Pickup OTP to store cashier│
│    • Store staff validates OTP to release the package                       │
│    • Custody shifts from Merchant ➔ Delivery Rider                          │
│                                                                             │
│ 3. Navigation & Turn-by-Turn Routing                                        │
│    • Integrated Google Maps / Hub landmark navigation                       │
│                                                                             │
│ 4. Doorstep Delivery Completion & Escrow Payout                             │
│    • Rider enters customer's 6-digit Delivery OTP                           │
│    • Instantly releases order from escrow, crediting vendor & rider wallet  │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

# 6. IN-DEPTH STEP-BY-STEP OPERATIONAL SCENARIOS

---

## SCENARIO A: Walk-In Counter Sale
### *(Negotiated Pricing + Split Payment Cash/Transfer/Debt + Thermal Receipt)*

```
[ Customer at Counter ]
         │
         ▼
[ Cashier Scans Barcode / Types Name ] ───➔ [ Item Added to POS Cart ]
         │
         ▼
[ Cashier Applies Negotiated Unit Discount ] (e.g. ₦30,000 ➔ ₦28,000)
         │
         ▼
[ Selects Split Payment Mode ]:
  • ₦10,000 Cash (In Cash Drawer)
  • ₦10,000 POS Card / Bank Transfer
  • ₦8,000 Customer Debt (Assigned to Customer Profile: "Chief Okon")
         │
         ▼
[ Click Complete Sale ]
         ├── 1. Physical Stock Reduced Instantly in Database
         ├── 2. Cashier Shift Ledger Credited: +₦10k Cash, +₦10k Card
         ├── 3. Customer Debt Ledger Created: +₦8,000 (Current 0-7 Days)
         └── 4. Thermal Printer prints 80mm Receipt:
                "Powered by Victorious MARKET - Your Trusted Online Market"
```

---

## SCENARIO B: Inter-Branch Anti-Theft Waybill & Variance Detection
### *(Warehouse Dispatch to Plaza Branch with Driver Liability Tracking)*

```
[ Origin Warehouse Manager (Plaza Main) ]
         │
         ▼
[ Creates Transfer Waybill ]:
  • Source: Plaza Main Warehouse
  • Destination: Itam Market Branch
  • Items: 50x Bags of Royal Rice (50kg)
  • Driver: Sunday Udoh (Phone: 0803XXXXXXX | Van: AKD-492-XA)
         │
         ▼
[ Dispatch Confirmed ] ───➔ [ 50 Bags moved from Active Stock to In-Transit Buffer ]
         │
         ▼
[ Driver Arrives at Itam Branch ]
         │
         ▼
[ Receiving Manager Performs Blind Physical Count ]:
  • Enters Received Count: 48 Bags
         │
         ▼
[ System Evaluates Variance ]:
  • Dispatched: 50 | Received: 48 | VARIANCE: -2 BAGS (SHORTAGE!)
         ├── 1. Itam Active Stock credited with 48 Bags
         ├── 2. In-Transit Buffer cleared
         ├── 3. Status set to: 'received_with_variance'
         └── 4. Waybill Shortage Alert fired to Admin Theft Radar & Store Owner:
                "Driver Sunday Udoh liable for ₦110,000 (2 missing bags)."
```

---

## SCENARIO C: Online Marketplace Order to Doorstep Delivery
### *(Online Buyer ➔ In-Store Staff Handover ➔ Rider Doorstep OTP Completion)*

```
[ 1. Online Shopper on Vmarket App ]
  • Places Order for Designer Shoe (₦45,000)
  • Selects Payment: Online Paystack (Funds held in Platform Escrow)
  • Order Status: 'confirmed'
         │
         ▼
[ 2. Store Web POS & Vendor App Chime Out Loud ] 🔔
  • Web POS audio plays 'notification.mp3'
  • Vendor phone vibrates with FCM Push Notification
  • Cashier clicks "Pack Order" ➔ Status: 'processing'
         │
         ▼
[ 3. Delivery Rider Emeka Arrives at Store ]
  • Emeka opens Rider App and shows Secret Pickup OTP: [ 7 3 9 1 0 4 ]
         │
         ▼
[ 4. In-Shop Staff-Attributed Handshake ] 🛡️
  • Cashier Blessing Okon (Staff #14) types [ 7 3 9 1 0 4 ] into POS Order Details
  • System verifies OTP in constant-time
  • Custody Transferred:
    - Order status: 'out_for_delivery'
    - Permanent Handover Stamp: "Released by Blessing Okon to Rider Emeka at 03:15 PM"
         │
         ▼
[ 5. Rider Emeka Delivers to Customer Doorstep ]
  • Emeka hands package to customer
  • Customer inspects package seal and provides 6-Digit Delivery OTP: [ 8 2 0 4 9 1 ]
  • Emeka types OTP into Rider App
         │
         ▼
[ 6. Escrow Release & Instant Balance Settlement ]
  • Order status: 'delivered'
  • Platform Commission (10% = ₦4,500) credited to Admin Account
  • Vendor Earning (90% = ₦40,500) credited to Vendor Wallet Balance
  • Delivery Fee credited to Rider Wallet Balance
```

---

## SCENARIO D: Merchant Multi-Branch SaaS Subscription Upgrade
### *(Scaling from 1 Free Store to 5 Branch Locations)*

```
[ Vendor Opens /vendor/subscription ]
         │
         ▼
[ Selects Plan ]:
  • Additional Branches: 4 (Total 5 Branches)
  • Monthly Rate: ₦15,000 / branch = ₦60,000 / month
         │
         ▼
[ Selects Payment Gateway ]:
  • Option 1: Paystack Instant Debit Card / Bank Transfer
  • Option 2: Deduct from Vendor Marketplace Wallet Balance
  • Option 3: Direct Bank Deposit (Upload Bank Teller / Receipt Image)
         │
         ▼
[ Payment Processed & Confirmed ]
         ├── 1. Subscription active until 30 days from today
         ├── 2. Branch creation limit unlocked to 5 Locations
         └── 3. Admin POS Dashboard MRR updated automatically
```

---

## SCENARIO E: 30-Day Customer Debt Recovery & Partial Repayment
### *(Debtor Account Management & Ledger Rebalancing)*

```
[ Vendor Opens /vendor/pos/debt-ledger ]
         │
         ▼
[ Selects Customer: "Chief Okon" ]
  • Total Unpaid Debt: ₦50,000 (Overdue: 18 Days - Status: Due 🟡)
         │
         ▼
[ Customer Walks in to Pay ₦20,000 Partial Installment ]
         │
         ▼
[ Cashier Clicks "Record Repayment" ]:
  • Amount Paid: ₦20,000
  • Payment Method: Cash
  • Notes: "Part-payment for wedding supplies"
         │
         ▼
[ System Executes Atomic Transaction ]:
  • Total Debt reduced from ₦50,000 ➔ ₦30,000
  • PosDebtTransaction record created with staff name
  • Cashier Shift cash drawer increased by +₦20,000
  • Printable Debt Receipt issued showing new remaining balance (₦30,000)
```

---

# 7. ENTERPRISE SECURITY, FINANCIAL & GOVERNANCE INVARIANTS

Every AI, developer, and infrastructure node in Victorious MARKET strictly enforces the following **non-negotiable safety rules**:

### 1. Zero-Trust IDOR Scoping
* All private data queries (Orders, Customers, Products, Ledgers, Debts, Waybills) are strictly scoped to the authenticated principal (`auth('seller')->id()`, `auth('customer')->id()`, or `auth('admin')->id()`). Route parameters (`$id`) are NEVER trusted alone.

### 2. Atomic Payment Locks & Double-Execution Guards
* All payment webhook handlers enforce atomic row-level locks (`where('is_paid', 0)->update(...)`) before triggering digital wallet credits or order confirmation hooks.

### 3. Pessimistic Balance Concurrency Locks
* All wallet balance deductions, credit top-ups, and commission sweeps execute inside an atomic database transaction with `->lockForUpdate()`.

### 4. Universal 6-Digit Cryptographic OTPs
* All verification tokens (Customer Doorstep Delivery OTP, Rider Pickup Handshake OTP, Staff Password Reset) use the 6-digit format with 15-minute expiration bounds and a 5-attempt brute-force lockout.

### 5. Mandatory Viral Branding Invariant
* All thermal 58mm/80mm POS receipts permanently print:  
  **`Powered by Victorious MARKET - Your Trusted Online Market`**

---
*© Victorious MARKET Ecosystem — Enterprise Omnichannel Commerce Architecture.*
