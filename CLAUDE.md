# VMarket Development & Architecture Rules

## Core Rule: One Business Concept → One Authoritative Implementation
The repository must maintain **one authoritative implementation** for every marketplace business capability.
Legacy implementations must not remain as ambiguous alternatives.

### Before Adding or Modifying a Capability:
1. **Identify** the current authoritative implementation.
2. **Search** for legacy implementations and callers.
3. **Do not create duplicate engines.**
4. **Migrate** required callers to the authoritative implementation.
5. **Mark obsolete implementations deprecated** during migration.
6. **Remove obsolete code** once all dependencies are migrated.
7. **Remove obsolete routes, frontend clients, tests, and documentation.**
8. **Verify** with repository-wide search that no unintended references remain.
9. **Run regression tests** after removal.
10. **Document** any intentionally retained legacy code and why.

---

## Authoritative vs Legacy Mapping

| Subsystem | Authoritative Implementation | Legacy / Replaced Subsystem |
|---|---|---|
| **Geography** | `Country`, `State`, `Lga` models (`canonical_geography`) | `DeliveryState`, `DeliveryCity`, `DeliveryZipCode` |
| **Delivery Routing & Fees** | `DeliveryLane` model + `FulfillmentAvailabilityService` | `ShippingMethod`, `ShippingType`, `CartShipping` |
| **Fulfillment Checks** | `FulfillmentAvailabilityService` (`POST /api/v1/fulfillment/availability`) | Client-side calculations, legacy shipping controllers |
| **Pickup Operations** | `Shop` pickup settings (`pickup_enabled`, hours, prep time) + OTP verification | Ad-hoc / unmanaged pickup flags |
| **Checkout & Intent** | `DeliveryCheckoutIntentService` + `CheckoutIntent` model | Legacy direct order placement |
| **Order Settlement & Stock** | `DeliveryOrderSettlementService` + `PaymentRequest` + `PaymentReconciliation` | Direct order creation on webhook |
| **Discounts / Loyalty** | Victorious Points (`CustomerCashback`, `CashbackRedemption`) | Coupons, referral promo codes |
| **Logistics Infrastructure** | `DeliveryHub` (Internal physical logistics hub only) | Hubs as customer-facing geography |

---

## Backend as Single Source of Truth
Frontend applications (**User App**, **Vendor App**, **Delivery App**) are **consumers** of backend decisions:
- **Never calculate business rules or fees in Flutter/Client apps.**
- Flutter displays what the backend returns (e.g. `is_available: true`, `fee: 1500.00`, `estimated_time: "24-48 hours"`).
- Backend validates all constraints (inventory, pricing, geography, branch isolation, permissions).

---

## Canonical Production Alignment References
- **Customer App ↔ Backend Contract:** `.agents/rules/VMARKET_CUSTOMER_APP_SPEC.md` (77-section canonical production contract)
- **Customer App Rules:** `.agents/rules/CUSTOMER_APP_ALIGNMENT.md` (20 mandatory rules)
- **Customer App Alignment Plan:** `.agents/rules/CUSTOMER_APP_ALIGNMENT_PLAN.md` (34-phase execution plan)
- **Customer App Scenario Audit Protocol:** `.agents/rules/CUSTOMER_APP_SCENARIO_AUDIT_PROTOCOL.md` (26 scenarios)
- **Admin Panel ↔ Backend Contract:** `.agents/rules/VMARKET_ADMIN_PANEL_SPEC.md` (70-section canonical production contract)
- **Admin Panel Rules:** `.agents/rules/ADMIN_PANEL_ALIGNMENT.md` (Mandatory Admin enforcing rules)
- **Admin Panel Alignment Plan:** `.agents/rules/ADMIN_PANEL_ALIGNMENT_PLAN.md` (10-phase execution plan)

---

## Controlled VMarket Roadmap Sequence

```text
1. Finish Geography + Fulfillment Architecture (Country -> State -> LGA, Delivery Lanes, Pickup Settings)
   ↓
2. Backend Integration + Hardening & Auditing (Auth, stock lock, idempotency, payments, Uyo/Eket scenarios)
   ↓
3. Lock Core Backend API Contracts (Freeze contracts so User, Vendor, Delivery apps consume, not invent)
   ↓
4. Finish User App (Customer journey: Browse -> Cart -> Address -> Fulfillment -> Checkout -> Paystack -> Order -> Tracking)
   ↓
5. Finish Vendor App (Merchant operations, branch isolation, inventory, order prep, handoff)
   ↓
6. Finish Delivery App (Dispatch, hub operations, rider assignment, transit, proof of delivery)
   ↓
7. Admin Control Center (Central operational control tower)
   ↓
8. End-to-End Simulation & Adversarial Testing (Real commerce flows + break tests)
   ↓
9. Controlled V1 Launch (~10 merchants, Akwa Ibom focus, controlled logistics)
   ↓
10. Progressive Scale & Expansion
```

### Feature Scope Check
Before adding any new feature, always ask:
> **"Does VMarket need this for the current transaction lifecycle?"**
- If **Yes** → Implement cleanly and authoritatively.
- If **No** → Put it on the backlog.
