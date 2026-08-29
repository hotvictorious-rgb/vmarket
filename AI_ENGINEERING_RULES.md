# 🏛️ Vmarket AI Engineering Rules & Governance

**Authoritative Mandate for all AI Coding Agents working on Victorious MARKET (Vmarket)**

---

## 1. The Core Principle: Vmarket is ONE Unified Platform

Vmarket is **NOT** a collection of independent repositories or isolated apps. It is **ONE unified multi-client software platform** powered by a single authoritative backend:

```
                          ┌──────────────────────────┐
                          │     LARAVEL BACKEND      │
                          │ (Single Source of Truth) │
                          └─────────────┬────────────┘
                                        │
         ┌──────────────┬───────────────┼───────────────┬──────────────┐
         │              │               │               │              │
    ┌────┴────┐   ┌─────┴─────┐   ┌─────┴─────┐   ┌─────┴─────┐   ┌────┴────┐
    │ Customer│   │   Admin   │   │  Vendor   │   │ Customer  │   │ Vendor  │ ... Delivery
    │ Website │   │   Panel   │   │   Panel   │   │Flutter App│   │Flutter  │     App
    └─────────┘   └───────────┘   └───────────┘   └───────────┘   └─────────┘
```

The platform consists of:
1. **Laravel 10 Backend & REST API** (`backend/vmarket-web`)
2. **Super Admin Web Portal** (Blade + AJAX)
3. **Vendor Web Dashboard** (Blade + AJAX)
4. **Customer Web Storefront** (Blade Views: Default & Aster Theme)
5. **Customer Mobile Application** (`User app/` - Flutter + Provider)
6. **Vendor Mobile Application** (`Vendor app/` - Flutter + Provider)
7. **Delivery Rider Mobile Application** (`Delivery Man App/` - Flutter + GetX)

Every AI must treat these clients as **views and interfaces of the central backend**, never as separate systems.

---

## 2. Backend is the Single Source of Truth

**All authoritative business logic MUST reside in Laravel.**

Clients (Flutter apps and Web UI) are responsible strictly for:
* UI rendering and animations
* User experience and local navigation
* Device functionality (Camera, Geolocation, Audio Recording)
* Push notification handling (FCM)
* Temporary local presentation state

**Clients MUST NEVER duplicate or invent authoritative business logic**, including:
* ❌ Pricing calculations, taxes, or discounts
* ❌ Commission splits (e.g. 90/10 vendor/platform)
* ❌ Vendor earnings or wallet balance calculations
* ❌ Order lifecycle and state transitions
* ❌ Payment gateway verification or status overrides
* ❌ Delivery fee formulas and rider dispatch rules
* ❌ Identity verification (KYC) match algorithms and approvals
* ❌ Bank account cooldown timers and OTP validation

---

## 3. Mandatory Change Impact Analysis (Hard Rule)

Before making any non-trivial change, every AI **MUST** inspect the repository across all dimensions:
1. **Backend Impact:** Models, Repositories, Controllers, Services, Policies, Middleware, Notifications, Events, Migrations.
2. **API Contract Impact:** Endpoint routes, request payloads, response schemas, authentication headers, backward compatibility.
3. **Database Impact:** Schema changes, table relationships, foreign keys, default values, backward data integrity.
4. **Client Impact:** Which of the 6 clients (Web Storefront, Admin Panel, Vendor Panel, Customer App, Vendor App, Delivery App) consume this logic.

The AI must explicitly classify every subsystem as:
* `[AFFECTED]`
* `[NOT AFFECTED]`
* `[POTENTIALLY AFFECTED]`

---

## 4. Two-Phase Implementation Process

### Phase 1 — Analysis & Plan
1. Search the entire repository for related keywords, models, endpoints, and UI references.
2. Formulate the root cause and impact analysis.
3. Formulate the implementation plan.
4. Obtain approval or confirm scope.

### Phase 2 — Implementation
1. **Backend First:** Implement changes in Laravel when business logic or APIs are involved.
2. **Contract Update:** Update API documentation and data models.
3. **Clients Next:** Update every affected client app/panel systematically.
4. **Verification:** Run static analysis (`flutter analyze`, syntax checks, unit tests).
5. **Documentation & Commit:** Document changes in `AI_CHANGELOG.md` and commit to Git using conventional commit standards.

---

## 5. Feature-First, Not App-First Development

Vmarket must be developed **feature-by-feature across the stack**, not app-by-app in silos.

```
CORRECT WORKFLOW:
Feature Requirement ➔ Laravel Backend ➔ Admin/Vendor Panels ➔ Flutter Apps ➔ Verification ➔ Docs

INCORRECT WORKFLOW:
Finish User App ➔ Finish Vendor App ➔ Finish Delivery App ➔ Finish Admin
```

---

## 6. Root-Cause-First Debugging

When a bug or crash is reported:
1. Trace from Symptom ➔ Client UI ➔ API Payload ➔ Controller/Service ➔ Eloquent Model ➔ Database.
2. Fix the **root cause** in the authoritative layer.
3. **NEVER apply client-side band-aids** that mask backend inconsistencies.

---

## 7. Change Classification Matrix

| Level | Type | Scope | Governance Requirement |
| :--- | :--- | :--- | :--- |
| **Level 1** | Local UI / Cosmetic | Single widget, color, padding, icon | Local testing; component commit. |
| **Level 2** | Client Feature | Single app screen or local navigation | Check API consumption; test affected app. |
| **Level 3** | Shared API Change | Modified endpoint, payload, or response | Mandatory cross-client search & contract update. |
| **Level 4** | Domain / Business Rule | Orders, commissions, KYC, payouts, delivery | Full Impact Analysis across all 6 clients + ADR update. |
| **Level 5** | Architecture / DB | Schema restructure, auth overhaul, security | Comprehensive migration plan, testing, and documentation. |

---

## 8. Git & Changelog Mandate

* **Atomic Commits:** Every AI modification must be committed atomically by component (`feat(user-app)`, `fix(backend)`, etc.).
* **Include Tag:** Include `[AI]` in the commit message.
* **Changelog:** Always record changes in `AI_CHANGELOG.md` before committing so the log is part of the commit.
* **Clean Tree:** Verify `git status` is clean at the conclusion of every turn.

---

## 9. Payment Gateway Atomic Lock Standard (Zero Duplicate Orders)

All payment gateway controllers MUST enforce an **Atomic Row-Level Database Lock** (`where('is_paid', 0)->update(...)`) on `payment_requests`. Before calling `$data->success_hook` (`digital_payment_success`), the code MUST check `$affected > 0`. This prevents concurrent browser callbacks and background IPN/webhooks from generating duplicate orders or duplicate wallet credits.

---

## 10. Production Deployment Protocol (Safe Overlay SOP)

1. **GitHub is the Single Source of Truth:** All code and custom logic originate in Git and deploy downwards to production. No manual code edits should exist on production.
2. **Web Scope:** Only `backend/vmarket-web/` maps to `shop.victoriousmarket.com.ng`. Mobile Flutter apps are built separately.
3. **Non-Destructive Sync:** NEVER run `rsync --delete` or `git clean -fd` on production cPanel.
4. **4 Protected Runtime Assets:** NEVER overwrite or delete `.env`, `storage/`, `vendor/`, or `public/assets/`.
5. **Post-Sync Optimization:** Execute `php artisan optimize:clear` after any deployment.

---

## 11. Enterprise Security & Financial Invariants 🛡️

1. **Zero-Trust IDOR Authorization Scoping:** Never rely solely on incoming route `$id` or `$request['id']` parameters. Every query modifying, viewing, or deleting user-owned assets MUST be scoped to `auth('customer')->id()`, `auth('seller')->id()`, or `auth('admin')->id()`.
2. **Pessimistic Financial Concurrency Locks:** Every read-modify-write on balances (Customer Wallet, Vendor Earnings, Rider Cash-in-Hand) MUST run inside `DB::transaction()` with `->lockForUpdate()`.
3. **Universal 6-Digit OTP Standards:** All OTP generation must use `rand(100000, 999999)` with exact identity lookups (`=`), 15-minute expiration bounds, and a 5-attempt brute-force lockout.
4. **Anti-Mass-Assignment Filtering:** Never pass `$request->all()` into model create or update methods. Use explicit whitelisting or Service transformers.

---

## 12. Mandatory Systemic & Mathematical Proof Directive 🧮

Every AI modifying ANY system component (Backend, Mobile Apps, POS, Ledgers, Logistics, Pricing, Payments) MUST formulate and execute mathematical proofs ($\Delta = 0.00$), verify cross-module parity, execute zero-error linter validation, and record concrete reproducible proofs in `VICTORIOUS_MARKET_MATHEMATICAL_AND_SYSTEMIC_PROOF.md` and `AI_CHANGELOG.md` before concluding any session. No change can be considered complete without verifiable proof.

---

## 13. Mandatory 9-Role Visibility Breakdown & Proof Standard 👥

Every feature, screen, navigation button, or API endpoint across Victorious MARKET and In-Store POS MUST explicitly formulate, document, and test what EACH of the 9 standardized ecosystem roles can see and do:

1. **Super Admin:** Platform Commander (Single Person) $\rightarrow$ Full control, SaaS Master Control (`/saas/*`), `Back to Vmarket Admin`.
2. **Super Admin Employee:** Platform Staff $\rightarrow$ Module-restricted admin panel access.
3. **Verified Merchant:** Approved Store Owner $\rightarrow$ Full POS + Online marketplace sales on Victorious MARKET, `Back to Merchant Panel`.
4. **Unverified Merchant:** Pending Store Owner $\rightarrow$ **Free In-Store POS (1 Store)** active for local counter sales, but online selling gated and header return button masked.
5. **Verified Merchant Employee:** Store Staff $\rightarrow$ Counter POS register (`/store/{slug}/login`), shift balancing, receipt printing.
6. **Unverified Merchant Employee:** Free Store Staff $\rightarrow$ Local counter POS register only; zero marketplace access.
7. **Active Deliveryman:** KYC Approved Rider $\rightarrow$ Real-time order dispatch, GPS tracking, cash-in-hand collections.
8. **Inactive Deliveryman:** Pending / Offline Rider $\rightarrow$ Blocked from order pickups until KYC verified.
9. **Customer:** Shopper / Buyer $\rightarrow$ Storefront catalog, orders, wallet, in-store QR payments.

**Mandatory Rule:** No PR, commit, or task may be concluded without documenting the 9-role visibility breakdown and providing reproducible automated test proof of zero privilege bleed across all 9 roles.

---

## 14. Universal Zero-Penetration Isolation & Absolute Personalization Invariant 🔒

Every user, store, worker, rider, and customer in Victorious MARKET must be isolated from penetrating another principal's resources. Everything is personalized down to the exact individual:

* **Merchant & Shop Isolation:** Strict multi-tenant boundaries (`seller_id`, `shop_id`). Zero cross-vendor data exposure.
* **Employee Micro-Isolation:** Staff are restricted strictly to their assigned register (`shop_id`), shift drawers, and specific POS actions.
* **Rider Micro-Isolation:** Deliveries, cash collections, and GPS tracking are bounded strictly to `delivery_man_id`.
* **Customer Micro-Isolation:** Customer data, addresses, orders, and wallet funds are isolated strictly to `customer_id`.
* **Total UI Personalization:** Every button, header, return action, and badge is personalized strictly to the user's role and verification status.

---

## 15. Official Brand Palette & Design System Invariant 🎨

All AI coding agents must strictly adhere to Victorious MARKET's official brand identity and color tokens across all web views, stylesheets, POS terminals, and Flutter mobile applications:

* **Primary Brand Purple (`#5E17EB`):** Main branding, topbars, primary action buttons, active navigation states, call-to-action buttons, and brand wordmarks.
* **Secondary Brand Gold (`#FFD700`):** Accent highlights, star ratings, promotional badges, verified vendor tags, discount pills, and VIP markers.
* **Base Clean White (`#FFFFFF`):** High-contrast surface cards, typography, modal containers, and crisp dark-mode inverted text.

**Strict Prohibition:** AI agents must never inject arbitrary generic colors (e.g. standard `#800080`, `#9333ea`, `#ffff00`, or `#eab308`). Always use `#5E17EB`, `#FFD700`, and `#FFFFFF` with elegant visual hierarchy, balanced contrast, and modern aesthetics.

---

## 16. Mandatory Master Endpoint Analysis & Synchronized Catalogue Maintenance 🗺️

1. **Pre-Change Analysis:** Before adding, modifying, or refactoring ANY endpoint, controller action, or route, every AI **MUST** analyze `ALL_ECOSYSTEM_ENDPOINTS_AND_SECURITY_TAXONOMY.md`.
2. **Synchronized Documentation:** Whenever a route is created or changed across Web Storefront, Customer APIs (v1), Rider APIs (v2), Vendor APIs (v3), Super Admin, or native POS Module (`Modules/Pos`), the AI **MUST append and update** `ALL_ECOSYSTEM_ENDPOINTS_AND_SECURITY_TAXONOMY.md`.
3. **Continuous Test Harness Integration:** Every new endpoint must be integrated into `test_100_plus_ecosystem_views_and_apis_suite.php` to maintain 100% continuous test coverage.

---

## 17. Universal 5-Pillar Endpoint Security Standard (Zero-Loopholes) 🛡️

Every endpoint in this ecosystem (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD) must strictly enforce:
1. **Zero-Trust Authentication:** Explicit guard binding (`auth:admin`, `auth:seller`, `auth:customer`, `auth:api`, `auth:delivery_man`). Unauthenticated requests must safely redirect or return HTTP 401/403 (never unhandled 500s).
2. **Tenant Scoping & Micro-Isolation (Zero Cross-Tenant Bleed):** All database operations MUST be scoped to authenticated principals (`seller_id`, `shop_id`, `customer_id`, `delivery_man_id`). Route IDs (`$id`) must never be trusted alone without ownership verification.
3. **Anti-Mass-Assignment & Input Validation:** Never pass `$request->all()` into model mutations; only validated data via `$request->only(...)` or dedicated FormRequest data mappers.
4. **Pessimistic Balance & Concurrency Locks:** Any mutation of financial balances, wallet funds, debt records, or cash drawers must execute inside `DB::transaction()` with pessimistic row locks (`->lockForUpdate()`).
---

## 18. Mandatory 1,572-Endpoint Automated Security Proof After Every Modification 🛡️

**This is an inviolable prime directive for ALL AI agents.**
Every AI completing ANY task, feature addition, bug fix, route refactoring, or database modification across Victorious MARKET MUST execute the automated 1,572-endpoint security verification harness:
```bash
php test_all_1572_endpoints_security_and_role_proof.php
```

### Mandatory Verification Invariants:
1. **100% Zero-Defect Operational Parity:** All 1,572 ecosystem endpoints across Central Marketplace (`backend/vmarket-web`), POS (`Modules/Pos`), and Delivery Hub (`Modules/Delivery`) must be evaluated with **0 Fatal Unhandled 500 Exceptions**.
2. **9-Role Multi-Actor Security Proof:** The execution must verify that Super Admin, Super Admin Employee, Verified Merchant, Unverified Merchant, Verified Merchant Employee, Unverified Merchant Employee, Active Deliveryman, Inactive Deliveryman, Customer, and Guest are strictly bounded to their authorized privileges.
3. **Universal 5-Pillar Security Standard:**
   - Zero-Trust Authentication intercepts unauthenticated requests cleanly (302/401/403/404).
   - Zero Cross-Tenant Bleed (queries scoped to `seller_id`, `shop_id`, `customer_id`, or `delivery_man_id`).
   - Anti-Mass-Assignment Protection via `$fillable` / `$guarded`.
   - Pessimistic Row Locks (`lockForUpdate()`) on financial balances.
   - Mathematical Invariant Proof with zero drift ($\Delta = 0.00$).
4. **Mandatory AI Changelog Logging & Clean Commit:** The AI must document the test results in `AI_CHANGELOG.md` before committing changes to Git with `[AI]`. No change may be merged or reported as complete without this passing proof.







