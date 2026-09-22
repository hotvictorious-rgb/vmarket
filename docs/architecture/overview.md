# 🏛️ Vmarket Architecture Overview (Authoritative SSOT)

This document provides detailed subsystem breakdowns for Victorious MARKET (Vmarket).

## 1. Unified Multi-Client Topology
Vmarket is designed as a centralized monolith backend serving multiple client interfaces:
- **Web Storefront:** Consumer-facing shopping interface with Aster & Default themes.
- **Admin Panel:** Platform administration, directional delivery lane configuration, financial auditing, KYC approvals, order management.
- **Vendor Panel:** Merchant catalog management, store branch pickup settings, order processing, wallet withdrawals.
- **Mobile Clients:** 3 Flutter native applications:
  - **Customer App (`User app`):** Strictly governed by `.agents/rules/VMARKET_CUSTOMER_APP_SPEC.md` (77 sections).
  - **Vendor App (`Vendor app`):** Merchant operations and store inspection.
  - **Delivery App (`Delivery Man App`):** Rider transit and contactless OTP verification.

## 2. Authoritative Backend Service Layer
All business rules and financial mutations are executed by centralized backend services:
- **Fulfillment Availability:** `App\Services\FulfillmentAvailabilityService` evaluates directional delivery lanes (`Origin LGA -> Lane -> Destination LGA`) and in-shop pickup availability.
- **Delivery Checkout Intent:** `App\Services\DeliveryCheckoutIntentService` creates immutable two-phase intent snapshots with SHA-256 fingerprinting.
- **Delivery Payment:** `App\Services\DeliveryPaymentInitializationService` manages Paystack payment attempts and verifications.
- **Pickup Lifecycle:** `App\Services\PickupReservationService` and `App\Services\PickupOrderSettlementService` manage 24-hr stock hold, inspection, and payment settlement.
- **Cashback Ledger:** `App\Services\CustomerCashbackService` maintains the 5% Victorious Cashback ledger.

## 3. Shared Domain Models
All models live under `backend/vmarket-web/app/Models` and are replicated as typed Dart models in the Flutter applications:
- `Country`, `State`, `Lga` (Canonical Geography)
- `DeliveryLane` (Directional fulfillment routing & fees)
- `CheckoutIntent` (Frozen pre-order snapshot)
- `PickupReservation` (In-shop inspection hold)
- `CustomerCashback`, `CashbackRedemption` (Victorious Points reward ledger)
- `Order`, `OrderGroup`, `OrderItem` (Post-settlement records)

## 4. Production Alignment References
- **Customer App Specification:** `.agents/rules/VMARKET_CUSTOMER_APP_SPEC.md` (77 canonical sections)
- **Customer App Enforcing Rules:** `.agents/rules/CUSTOMER_APP_ALIGNMENT.md` (20 mandatory rules)
- **Customer App Alignment Plan:** `.agents/rules/CUSTOMER_APP_ALIGNMENT_PLAN.md` (34-phase plan)
- **Mathematical Invariant Proofs:** `VICTORIOUS_MARKET_MATHEMATICAL_AND_SYSTEMIC_PROOF.md`
