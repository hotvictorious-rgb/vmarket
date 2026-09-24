# Victorious MARKET — Gated Areas & Tier Classifications (GATED_AREAS.md)

> **CONTROL ZONE FILE — HUMAN OWNERSHIP ONLY**  
> **Status:** Stage 1 Tier Classifications under Multi-AI Control System Specification (v3) §21.2 & Appendix H  
> **Rule:** Every module and path belongs to exactly one tier. Tier A requires complete test gates and characterization tests. Tier C allows test exemptions ONLY when citing a valid `LEGACY_DEBT.md` ID.

---

## 1. Gated Areas Registry

| Path / Module | Tier | Reason | Promotion Target | Approved By / Date |
| :--- | :---: | :--- | :--- | :--- |
| `backend/vmarket-web/app/Services/DeliveryCheckoutIntentService.php` | **A** | Two-phase checkout intent, order creation, escrow settlement | Permanent Tier A (Critical) | Human / 2026-09-24 |
| `backend/vmarket-web/app/Http/Controllers/Payment_Methods/PaystackController.php` | **A** | Sole authorized payment gateway, atomic row lock, webhook verification | Permanent Tier A (Critical) | Human / 2026-09-24 |
| `backend/vmarket-web/app/Services/InShopPickupReservationService.php` | **A** | In-shop inspection handshake, ₦0 reservation pass, 6-digit release PIN | Permanent Tier A (Critical) | Human / 2026-09-24 |
| `backend/vmarket-web/app/Services/CashbackService.php` | **A** | Victorious Points & Cashback ledger, financial wallet credits | Permanent Tier A (Critical) | Human / 2026-09-24 |
| `backend/vmarket-web/app/Http/Controllers/RestApi/v2/delivery_man/DeliveryManController.php` | **A** | Delivery dispatch, order state machine, Proof of Delivery (POD) 6-digit OTP | Permanent Tier A (Critical) | Human / 2026-09-24 |
| `backend/vmarket-web/app/Models/Order.php` | **A** | Core order model, hidden verification codes (`Order::$hidden`), financial totals | Permanent Tier A (Critical) | Human / 2026-09-24 |
| `backend/vmarket-web/app/Services/FulfillmentAvailabilityService.php` | **B** | Directional delivery lane resolution, LGA feasibility | Tier A | Human / 2026-09-24 |
| `backend/vmarket-web/app/Http/Controllers/RestApi/v1/GeographyController.php` | **B** | Canonical Country $\rightarrow$ State $\rightarrow$ LGA hierarchy | Tier B (Gated) | Human / 2026-09-24 |
| `backend/vmarket-web/app/Http/Controllers/RestApi/v1/CartController.php` | **B** | Server-calculated cart subtotals, tax, and in-band totals | Tier B (Gated) | Human / 2026-09-24 |
| `User app/lib/features/checkout/` | **B** | Customer two-phase checkout flow, Paystack redirect, intent polling | Tier A | Human / 2026-09-24 |
| `User app/lib/features/pickup_reservation/` | **B** | Customer in-shop pickup reservation screens and 6-digit release PIN sheet | Tier A | Human / 2026-09-24 |
| `Vendor app/lib/features/order_details/` | **B** | Merchant counter handover sheet and inspection accept/reject buttons | Tier A | Human / 2026-09-24 |
| `Delivery Man App/lib/view/screens/order/` | **B** | Rider dispatch, waypoint routing, and POD OTP entry dialog | Tier A | Human / 2026-09-24 |
| `backend/vmarket-web/resources/themes/theme_vmarket/` | **B** | Primary public web storefront theme and responsive checkout views | Tier B (Gated) | Human / 2026-09-24 |
| `backend/vmarket-web/app/Http/Controllers/Admin/POS/` | **C** | Legacy stock 6valley POS modules (decoupled from online checkout) | Decommission / Tier C | Human / 2026-09-24 |
| `backend/vmarket-web/resources/themes/theme_aster/` | **C** | Secondary stock alternative theme (inactive by default) | Deprecated / Tier C | Human / 2026-09-24 |
| `backend/vmarket-web/resources/themes/theme_fashion/` | **C** | Tertiary stock alternative theme (inactive by default) | Deprecated / Tier C | Human / 2026-09-24 |
