# Victorious MARKET: Logistics & Delivery Policy (V1)

**Effective Date:** 2026-09-21  
**Region:** Uyo, Akwa Ibom State  
**Version:** V1 Fresh-System Architecture

This document outlines the official logistics policies governing Deliveries, Pickups, Returns, and Vendor Fulfillment on the Victorious MARKET platform. All logistics are centrally managed by Victorious MARKET to ensure unified customer experience, security, and financial accountability.

---

## 1. For Customers: Delivery & Pickup Terms

### 1.1 Delivery Orders — Pay First, Then Deliver
- **Payment Timing:** All delivery orders require **payment before fulfillment**. Customers pay VMarket digitally (via Paystack card/bank transfer) at checkout.
- **Zero Cash on Delivery (COD):** V1 does not support cash-on-delivery for delivery orders. Payment must be verified before vendor preparation begins.
- **Delivery OTP Verification:** Deliveries will **not** be handed over without the customer providing the 6-digit Delivery OTP (`verification_code`) sent to their app/phone. This OTP proves customer receipt and starts the 24-hour return protection window.
- **No Doorstep Inspection:** Dispatch riders are strictly logistics agents. They are **not** permitted to wait for customers to unbox, test, or try on items at the door. Once the OTP is provided and the package is handed over, the delivery is considered complete.

### 1.2 Pickup Orders — Inspect First, Then Pay
- **Reservation:** Customers create a pickup reservation (not an order, not payment, not inventory hold). A human-friendly reservation code (e.g., `RES-AB12CD34`) is generated.
- **Physical Inspection:** Customers visit the vendor's approved pickup point and physically inspect the goods.
- **Payment at Pickup:** After inspection and acceptance, customers pay VMarket digitally at the pickup point (via USSD, bank transfer, card, or in-app Wallet).
- **Release Authorization:** After payment verification, VMarket sends a 6-digit Handover OTP to the vendor. The vendor enters this OTP to release the goods.
- **Zero Pickup COD to Vendor:** Customers **never** pay cash or private transfer directly to the vendor. All payments flow through VMarket.

### 1.3 Post-Receipt Refunds & Return Logistics
- **24-Hour Return Window:** Customers have 24 hours from actual receipt (`received_at`) to request a return for objective problems (wrong item, damaged, materially defective, materially different from description, missing essential component).
- **Change-of-Mind Returns:** Not a universal right in V1. Category-specific rules may be introduced later.
- **Refund Eligibility:** Returns must be reviewed and approved by VMarket. Refunds are not automatic.
- **Delivery Fee Refund Policy:**
  - **If delivery occurred (`received_at != null`):** Delivery fee is **strictly non-refundable**. VMarket performed the delivery service. Approved merchandise returns refund product price only.
  - **If delivery did NOT occur (`received_at == null`):** Delivery fee is **fully refundable**. Customer receives full payment refund (merchandise + delivery fee) for that undelivered child order.
- **Return Drop-offs:** Return logistics are coordinated by VMarket Support. Customers may be directed to return items to approved locations. VMarket may dispatch riders to retrieve items for approved returns at VMarket's discretion.

---

## 2. For Dispatch Riders: Compensation & Conduct

### 2.1 Performance-Based Compensation
- Riders earn their `deliveryman_charge` **only** upon successful delivery of an order (when the status is updated to `delivered` in the app via customer Delivery OTP verification).
- Failed delivery attempts (e.g., customer unreachable, absent, or rejecting the package at the door) do not generate a delivery fee.
- **Mandatory Pre-Call:** To avoid unpaid failed deliveries, riders should call the customer to confirm availability before departure.

### 2.2 Zero Cash Collection Authority (V1)
- **No COD, No Cash Collection:** V1 riders handle **zero customer merchandise cash**. All delivery orders are prepaid.
- **Zero Cash-in-Hand Liability:** The `cash_in_hand` remittance subsystem is decommissioned in V1. Riders do not collect cash on behalf of VMarket for delivered goods.
- **Rider Earnings:** Delivery earnings are credited directly to the rider's `current_balance` in `DeliverymanWallet` upon successful delivery OTP verification.
- **Payment Authority Boundary:** Riders have **zero** authority to mark orders as paid, verify payments, or collect funds. Payment verification is strictly a VMarket/Admin/Paystack function.

### 2.3 Two-Factor OTP Custody Chain
- **Vendor Pickup OTP (`pickup_verification_code`):** Rider arrives at vendor shop and enters the 4-digit Vendor Pickup OTP to collect the package. This transitions the order to `out_for_delivery` and records vendor-to-rider custody transfer.
- **Customer Delivery OTP (`verification_code`):** Rider delivers to customer doorstep. Customer provides the 6-digit Delivery OTP to complete handover. This records `received_at`, transitions order to `delivered`, and starts the 24-hour return window.
- **Custody Invariant:** `rider pickup ≠ customer delivery`. Rider collection from vendor does NOT complete delivery or earn the delivery fee. Only customer receipt (`received_at != null`) completes delivery.

### 2.4 Failed Order Handover
- In the event of a canceled or failed delivery, the rider must return the package to the Vendor or the VMarket Hub as directed by Dispatch. Failure to return merchandise per VMarket instructions may result in penalties.

---

## 3. For Vendors: Handover & Fulfillment

### 3.1 Order Readiness & Vendor Pickup OTP
- When an order is marked as "Ready for Pickup/Delivery" by a Vendor, the item must be fully packaged and available.
- **Vendor Pickup OTP:** When the assigned rider arrives, the vendor provides the 4-digit Vendor Pickup OTP (`pickup_verification_code`). The rider enters this OTP in the Delivery App to unlock custody transfer. This records the handover timestamp and transitions the order to `out_for_delivery`.
- **Wait-Time Discipline:** VMarket manages dispatch riders centrally. Excessive vendor delays waste logistics resources. If a rider is forced to wait excessively because an item is out of stock, unpackaged, or the vendor is unavailable, VMarket reserves the right to cancel the order and apply operational penalties.

### 3.2 Pickup Orders — Customer Handover OTP
- For pickup orders, after the customer pays VMarket digitally at the pickup point, VMarket generates a 6-digit Handover OTP.
- The vendor enters this Handover OTP in the Vendor App/Portal to release the goods to the customer. This records `received_at` (handed_over_at) and starts the 24-hour return window.
- **Zero Direct Vendor Payment:** Vendors must **never** accept cash or private transfers from customers for marketplace orders. All pickup payments flow through VMarket. Vendor release is gated by VMarket's digital payment verification and OTP authorization.

### 3.3 Refund Cost Absorption & Settlement Impact
- If a customer successfully files a return within the 24-hour window and the return is approved by VMarket Admin, the refund liability flows through the settlement system.
- **Vendor Settlement Rule:** Vendor settlement is calculated after the 24-hour return window expires with no unresolved disputes. If a refund is approved, that order never reaches `settlement_eligible` and the vendor does not receive payout for that transaction.
- **Delivery Fee Retention (Delivered Orders):** For delivered orders where the customer later returns the merchandise, the delivery fee is non-refundable. VMarket retains the delivery fee as the delivery service was successfully rendered. The vendor's settlement is reduced by the refunded merchandise amount only (not delivery fee, as that was never part of vendor settlement).
- **Quality Control Responsibility:** Ensuring product quality, accuracy, and proper packaging prior to handover is strictly the Vendor's responsibility.

---

## 4. V1 Architecture Boundaries & Exclusions

To maintain clarity and operational focus in V1:

- ❌ **No Mixed Fulfillment Checkout:** A checkout must be either delivery-only or pickup-only. Do not mix delivery and pickup items in one V1 checkout.
- ❌ **No Rider Payment Authority:** Riders cannot verify payments, mark orders as paid, or override payment status.
- ❌ **No Interstate Bus-Driver Transit Flow:** V1 operates hyper-local (Uyo urban) and controlled park routes. Complex interstate driver handover codes are not active in V1.
- ❌ **No Customer-to-Vendor Direct Chat:** All customer inquiries, complaints, and support flow through VMarket Customer Support to prevent transaction circumvention and maintain platform authority.
- ❌ **No Partial Pickup Acceptance:** Pickup reservations are accepted or rejected as a whole in V1. Partial item acceptance creates additional complexity deferred to later iterations.

---

## 5. Summary Principles

- **Payment Authority:** VMarket controls all payment verification (Paystack, Admin OPay reference verification, Pay-at-Pickup digital gate).
- **Custody Authority:** Three distinct OTP-based custody events enforce accountability (Vendor → Rider, Rider → Customer, Vendor → Customer).
- **Financial Separation:** Delivery fees are 100% separate from merchandise economics (90% vendor / 5% cashback / 5% VMarket retained).
- **Return Window:** 24 hours from actual receipt (`received_at`), not from payment.
- **Operational Discipline:** Every role (Customer, Vendor, Rider, Admin) operates within strict, software-enforced boundaries to ensure zero leakage and unified experience.
