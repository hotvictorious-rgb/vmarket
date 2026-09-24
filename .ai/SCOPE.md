# Victorious MARKET — Systems Register (SCOPE.md)

> **CONTROL ZONE FILE — HUMAN OWNERSHIP ONLY**  
> **Status:** Stage 1 Systems Register under Multi-AI Control System Specification (v3) §28.1 & Appendix H  
> **Rule:** Every active system in scope MUST have a designated implementer and reviewer. Unowned systems are ineligible for AI changes.

---

## 1. Authoritative Systems Matrix

| System | Description | Implementer | Reviewer(s) | Third Parties | Data Handled | Approved By / Date |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `backend/core-api` | Laravel REST APIs (`v1`, `v2`, `v3`), auth middleware, RBAC, domain services | AI 1 (Backend) | AI 5, 6, 7 (by audience) | Paystack, SMS/OTP gateway | User credentials, session tokens, passwords, customer profiles | Human / 2026-09-24 |
| `backend/database-migrations` | Database schemas, Eloquent models, indexing, transactions, pessimistic balance locks | AI 1 (Backend) | AI 5, 6, 7 | None | All database tables and relational records | Human / 2026-09-24 |
| `backend/checkout-intent` | Two-phase delivery checkout intent, payment row locking, Paystack webhook callbacks | AI 1 (Backend) | AI 5 (Customer), AI 7 (Ops) | Paystack | Payment requests, transaction references, order amounts | Human / 2026-09-24 |
| `backend/pickup-reservations` | In-shop pickup two-code reservation lifecycle, counter inspection, release PIN | AI 1 (Backend) | AI 5 (Customer), AI 6 (Vendor) | None | Reservation codes, 6-digit handover OTPs, inspection states | Human / 2026-09-24 |
| `backend/cashback-ledger` | Victorious Points & Cashback ledger, loyalty conversions, wallet transaction records | AI 1 (Backend) | AI 5 (Customer) | None | Cashback percentages, point balances, wallet credits | Human / 2026-09-24 |
| `backend/geography-lanes` | Country $\rightarrow$ State $\rightarrow$ LGA hierarchy and directional `DeliveryLane` rates | AI 1 (Backend) | AI 5 (Customer), AI 7 (Ops) | None | LGA IDs, delivery fees, estimated delivery duration | Human / 2026-09-24 |
| `customer/storefront-web` | Public storefront (`theme_vmarket` Blade templates, custom JS/CSS, discovery grids) | AI 2 (Customer) | AI 5 (Customer Reviewer) | Google Fonts, Paystack | Cart items, customer browsing history, active LGA session | Human / 2026-09-24 |
| `customer/mobile-app` | Flutter customer mobile app (`User app/`, Provider, GetIt, secure storage) | AI 2 (Customer) | AI 5 (Customer Reviewer) | Paystack, Google Maps, Firebase FCM | Customer auth tokens, delivery addresses, order history | Human / 2026-09-24 |
| `vendor/web-panel` | Merchant web portal (`vendor-views` Blade views, inventory, counter releases) | AI 3 (Vendor) | AI 6 (Vendor Reviewer) | None | Merchant credentials, catalog items, shop bank accounts | Human / 2026-09-24 |
| `vendor/mobile-app` | Flutter merchant mobile app (`Vendor app/`, Provider, GetIt, secure storage) | AI 3 (Vendor) | AI 6 (Vendor Reviewer) | Firebase FCM | Vendor auth tokens, store stock, pickup verification OTPs | Human / 2026-09-24 |
| `operations/admin-panel` | Super Admin command center (`admin-views`, lane governance, dispatch, audit logs) | AI 4 (Operations) | AI 7 (Ops Reviewer) | None | Admin credentials, system configurations, audit logs | Human / 2026-09-24 |
| `operations/delivery-app` | Flutter delivery rider mobile app (`Delivery Man App/`, GetX, secure storage) | AI 4 (Operations) | AI 7 (Ops Reviewer) | Google Maps, Firebase FCM | Rider tokens, GPS coordinates, Proof of Delivery OTPs | Human / 2026-09-24 |
| `infrastructure/control-system`| Git worktrees, pre-commit hooks, test runners, release gate scripts, tickets | Human / AI 8 | Human / All Reviewers | Git, Windows PowerShell | Git commits, test runner logs, release candidate manifests | Human / 2026-09-24 |

---

## 2. Reviewer Routing Rules (§11.2)

1. Any ticket touching `backend/checkout-intent`, `backend/pickup-reservations`, or `customer/*` requires **AI 5 (Customer Reviewer)**.
2. Any ticket touching `vendor/*` or merchant inventory/orders requires **AI 6 (Vendor Reviewer)**.
3. Any ticket touching `operations/*`, `Delivery Man App/`, delivery lanes, or admin portals requires **AI 7 (Operations Reviewer)**.
4. Tickets modifying cross-cutting database schemas or API contracts require **all three reviewers (AI 5, AI 6, AI 7)**.
