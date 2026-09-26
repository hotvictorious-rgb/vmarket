# Victorious MARKET AI Development Rules

Welcome to the Victorious MARKET (Vmarket) ecosystem! This file enforces strict rules and patterns that **ALL AIs** must adhere to when working on this repository. **All AIs must follow all the rules in AGENTS.md strictly; no AI is allowed to bypass them under any circumstances.**

## 0. Prime Directive: Read Governance Documents First
Before taking ANY action, every AI **MUST** read:
1. `AI_ENGINEERING_RULES.md` — The foundational engineering principles of Vmarket as ONE unified platform.
2. `CHANGE_IMPACT_PROTOCOL.md` — The mandatory 6-point pre-change impact analysis checklist.
3. `ARCHITECTURE.md` — The system topology, service layers, and state boundaries.
4. `AI_CHANGELOG.md` — The chronological log of recent AI modifications.
5. `.agents/rules/VMARKET_CUSTOMER_APP_SPEC.md` — The 77-section canonical production contract for Customer App ↔ Backend.
6. `.agents/rules/CUSTOMER_APP_ALIGNMENT.md` — The 20 mandatory Customer App enforcing rules.
7. `.agents/rules/CUSTOMER_APP_ALIGNMENT_PLAN.md` — The 34-phase Customer App alignment plan.
8. `.agents/rules/CUSTOMER_APP_SCENARIO_AUDIT_PROTOCOL.md` — The 26-scenario, 9-phase audit protocol and parity matrix.
9. `.agents/rules/VMARKET_ADMIN_PANEL_SPEC.md` — The 70-section canonical production contract for Admin Panel ↔ Backend.
10. `.agents/rules/ADMIN_PANEL_ALIGNMENT.md` — The mandatory Admin Panel enforcing rules.
11. `.agents/rules/ADMIN_PANEL_ALIGNMENT_PLAN.md` — The 10-phase Admin Panel alignment plan.
12. `.agents/rules/VMARKET_VENDOR_SPEC.md` — The 31-section canonical production contract for Vendor Web & Vendor Mobile App ↔ Backend.
13. `.agents/rules/VMARKET_DELIVERY_APP_SPEC.md` — The 31-section canonical production contract for Delivery Rider Mobile App ↔ Backend.
14. `.agents/rules/VMARKET_STOREFRONT_SPEC.md` — The canonical production specification for the VMarket Public Storefront (SEO/discovery layer). Governs Core Web Vitals, structured data, sitemap, Google Merchant Center sync, and backend-data authority.
15. `.agents/rules/VMARKET_BACKEND_SPEC.md` — The master backend reference blueprint (Full Production Architecture: 54 sections). Backend is the Single Source of Truth (SSOT) and central operating system.
16. `.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md` — The multi-agent communication protocol and request lifecycle across all 6 platform actors.
17. `.agents/sync/API_CONTRACT_REGISTRY.md` — The authoritative living dictionary of frozen API schemas and endpoint contracts.

**MANDATORY RULE FOR ALL AIs**: All AIs working on Victorious MARKET must strictly follow all 6 canonical production specifications (`VMARKET_BACKEND_SPEC.md`, `VMARKET_CUSTOMER_APP_SPEC.md`, `VMARKET_ADMIN_PANEL_SPEC.md`, `VMARKET_VENDOR_SPEC.md`, `VMARKET_DELIVERY_APP_SPEC.md`, `VMARKET_STOREFRONT_SPEC.md`) and the multi-agent communication protocol (`CROSS_AGENT_COMMUNICATION_PROTOCOL.md`) without exception. No AI is permitted to bypass, override, or alter these specification rules under any circumstances.

## 1. Golden Rule: Read Before Writing
Before making ANY changes to this codebase, you MUST:
- Analyze the existing structure.
- Understand how your requested change integrates with the existing architecture.
- Do NOT introduce new architectural patterns (e.g., do not install Redux if the app uses Provider, do not use raw SQL if the backend uses Eloquent Repositories).
- You must always read `AI_CHANGELOG.md` in the root directory to understand recent modifications made by other AIs.

## 2. Mandatory Change Logging
Any time you make a functional change, fix a bug, or complete a feature, you **MUST** document it in `AI_CHANGELOG.md` located in the root of the workspace. Always include the exact timestamp in the header: `### [YYYY-MM-DD HH:MM UTC] <Title> [<Scope>]`. This ensures all AIs remain synchronized on the project's state.

## 3. Strict Architectural Patterns

### A. The Laravel Backend (`backend/vmarket-web`)
- **Queries:** Avoid N+1 queries at all costs. You MUST use Eager Loading (`->with()`) inside the `app/Repositories` classes. Note that the Repository pattern and eager loading rules apply to newly written or refactored features. Legacy direct queries inside controllers must be preserved to minimize regression risk, unless that specific endpoint is being overhauled.
- **Caching & Invalidation:** The storefront relies heavily on caching. If you add a new configuration or storefront setting, you must cache it using `Cache::remember()` in the `app/Utils/settings.php` file or equivalent utility. Crucially, any settings creation/update logic in controllers or repositories must explicitly invalidate the corresponding cache key using `clearWebConfigCacheKeys()` or `cacheRemoveByType()`.
- **Data Integrity:** All Eloquent Models must explicitly define a `$fillable` or `$guarded` array to prevent Mass Assignment.
- **Cross-App & Multi-Platform Verification:** Whenever any AI wants to make functional modifications to the backend, they MUST first verify and prove feature-by-feature, one-by-one, that the changes do not break operations across:
  1. Admin Web Panel
  2. Customer Web Storefront
  3. Seller Web Panel
  4. Customer Mobile App
  5. Seller Mobile App
  6. Delivery Man Mobile App
  This verification and proof is mandatory before making any change to the backend.

### B. User App & Vendor App (Flutter)
- **State Management:** These apps use **Provider**. Do NOT introduce GetX, BLoC, or Riverpod.
- **Dependency Injection:** All services and providers must be registered using **GetIt** in `lib/di_container.dart`.
- **Security:** API tokens must ONLY be stored using `flutter_secure_storage`. Do not use `shared_preferences` for sensitive keys.
- **Architecture:** Follow the Feature-First directory structure (`lib/features/{feature_name}`).
- **Customer App Canonical Contract:** The Customer App (`User app`) must strictly adhere to the 77-section specification in `.agents/rules/VMARKET_CUSTOMER_APP_SPEC.md` and the 20 enforcing rules in `.agents/rules/CUSTOMER_APP_ALIGNMENT.md`. No client-side pricing, tax, commission, or delivery fee calculation is permitted. The backend is the sole authority.

### C. Delivery Man App (Flutter)
- **State Management:** This specific app uses **GetX** for state and routing. Do NOT use Provider here.
- **Security Notice:** API tokens and credentials must ONLY be stored using `flutter_secure_storage`. Any legacy fallback in `shared_preferences` must be migrated.
- **Performance:** When dealing with maps and geolocation, ensure UI repaints are minimized via GetX reactive variables (`.obs`).

### D. Payment Gateways & Hook Security (Laravel Backend)
- **Atomic Payment Row Lock Directive:** Every payment gateway controller (Paystack, Flutterwave, Stripe, PayPal, Razorpay, bKash, Paytm, etc.) MUST enforce an **Atomic Row-Level Lock** (`where('is_paid', 0)->update(...)`) on `payment_requests`.
- **Double Execution Guard:** Before invoking `$data->success_hook` (`digital_payment_success`), the code MUST check `$affected > 0`. Never call `success_hook` without checking affected rows, to prevent concurrent browser callbacks and background IPN/webhooks from generating duplicate orders or duplicate wallet credits.

### E. Admin Web Panel (Control Tower & Governance)
- **Control Tower, Not a Second Engine:** The Admin Panel (`backend/vmarket-web/resources/views/admin-views/`) is strictly a presentation and command interface for the backend. All business logic, fee calculations, and state machines reside in domain services.
- **Zero-Trust Server-Side Authorization:** Every admin action must be authorized server-side using Laravel Policies or Gates. Never trust frontend role checks or hidden UI elements.
- **Admin Canonical Contract:** All modifications to Admin controllers, routes, and views must strictly adhere to `.agents/rules/VMARKET_ADMIN_PANEL_SPEC.md` and `.agents/rules/ADMIN_PANEL_ALIGNMENT.md`.
- **Canonical Geography & Lanes:** Admin strictly manages `Country → State → LGA` and directional `DeliveryLane` records. No wards or hubs in public geography. Historical order snapshots must remain immutable when lanes or fees change.
- **Immutable Audit Logging:** Every sensitive admin mutation (lane toggling, fee modification, merchant suspension, refund approval) must write an immutable audit log record. Audit logs cannot be edited or deleted.

## 4. UI / UX Standards
- The platform uses a specific color scheme (Purple & Gold). Use the predefined theme colors.
- Maintain smooth 60fps performance on mobile apps. Use `cached_network_image` for all network images.
- **Multi-Theme Home Headers:** Any modification to the Customer App home screen header (app bar, brand logo, wordmark, call-to-order pill, or notifications badge) MUST be implemented identically across all 3 theme screens: `lib/features/home/screens/home_screens.dart` (Default), `lib/features/home/screens/aster_theme_home_screen.dart` (Aster), and `lib/features/home/screens/fashion_theme_home_screen.dart` (Fashion) to prevent visual discrepancies when the active theme is toggled from the admin panel.

## 5. Mandatory Git Commit Rule ⚠️
**This is non-negotiable.** Every AI MUST commit all changes to Git upon completing any task, feature, fix, or audit. Leaving changes uncommitted is STRICTLY FORBIDDEN.

### Commit Format
Use descriptive, atomic commits grouped by component. Follow this convention:

```
<type>(<scope>): <short description> [AI]

- Bullet point of what changed
- Another bullet point
```

**Types:** `feat`, `fix`, `security`, `perf`, `refactor`, `chore`
**Scopes:** `user-app`, `vendor-app`, `delivery-man`, `backend`, `ai-governance`

### Commit Procedure & Multi-Agent Strict Isolation ⚠️
In this repository, multiple AI agents work concurrently across different platforms (`User app/`, `Vendor app/`, `Delivery Man App/`, `backend/vmarket-web/`, and `storefront`).
**Every AI MUST strictly stage and commit ONLY its own changes. NEVER commit all files.**

1. **Strict File Staging:** `git add <specific-file-1> <specific-file-2>` — You MUST explicitly name only the specific files you created or modified.
2. **STRICTLY PROHIBITED:** NEVER run `git add .`, `git add -A`, `git commit -a`, or `git add *`. Using bulk staging is an immediate violation because it accidentally absorbs or breaks work in progress from concurrent AIs.
3. **Leave Other Actors' Files Dirty:** If `git status` shows uncommitted files in directories or components you did not touch (e.g. you are Backend AI and see modified files in `Vendor app/` or `User app/`), **LEAVE THEM UNTOUCHED AND UNSTAGED**. Do NOT commit them, and NEVER run `git restore`, `git checkout -- .`, or `git clean` to wipe them.
4. `git commit -m "<message> [AI]"` — Include the `[AI]` tag and proper scope.
5. Log your changes in `AI_CHANGELOG.md` **before** committing (so the changelog entry is part of your commit).
6. Verify with `git status` that ONLY your own files were committed and that you did not disturb other actors' working files.

### Grouping Strategy
- Group commits by **component** (one commit per app, one for backend, one for ai-governance).
- Do NOT mix Flutter app changes with Laravel backend changes in a single commit.
- New untracked files (widgets, screens) must be explicitly staged by exact path with `git add <exact_path>`.

## 6. Code Commenting Standards
- **AI Prefix:** All comments introduced by an AI must be prefixed with `[AI]` so human developers can easily identify AI-authored notes.
- **Client Context:** When writing or modifying API/controller methods, add comments detailing which client applications (e.g., Customer Web, Vendor App) consume it.
- **Preservation:** Never delete, strip, or replace existing developer comments or docstrings unless the corresponding code is completely removed.

## 7. Production Deployment & Server Sync SOP (Safe Overlay Protocol)
- **GitHub is the Authoritative Single Source of Truth (SSOT):** All business logic, custom controllers, security patches, and features originate in this repository and are pushed to GitHub `master`. No manual code edits should exist on production.
- **Monorepo Destination Mapping:** The web application deployed on cPanel (`shop.victoriousmarket.com.ng`) maps **EXCLUSIVELY** to `backend/vmarket-web/`. The 3 Flutter mobile apps (`User app`, `Vendor app`, `Delivery Man App`) are built separately via Flutter/Dart pipelines and MUST NEVER be copied into the web root.
- **Strict Prohibition of Destructive Deletion:** NEVER run `rsync --delete` or `git clean -fd` on the live cPanel server.
- **The 4 Immutable Runtime Server Assets:** The following runtime paths on the live cPanel server MUST NEVER be deleted, overwritten, or wiped during deployment:
  1. `.env` (Live database credentials & secret keys)
  2. `storage/` (Customer uploads, order receipts, and framework cache)
  3. `vendor/` (Composer dependency packages)
  4. `public/assets/` (Storefront UI icons, SVGs, fonts, and stylesheets)
- **Safe Overlay Execution:** When deploying to production, overlay code directly from `backend/vmarket-web/` (or run `git pull origin master`), preserving the 4 immutable runtime assets above, and execute `php artisan optimize:clear`.

## 8. Reference Baseline Guidelines (`reference/`)
- The `reference/` directory contains extracted clean stock reference baselines for all 4 platforms:
  1. `reference/6valley_v16.1_web/` — Stock 6valley V16.1 Laravel Web Backend & Web Dashboards
  2. `reference/6valley_user_app_v16.1/` — Stock 6valley V16.1 Customer Mobile App (Flutter)
  3. `reference/6valley_vendor_app_v16.1/` — Stock 6valley V16.1 Vendor Mobile App (Flutter)
  4. `reference/6valley_delivery_v4.2/` — Stock 6valley Delivery Rider Mobile App V4.2 (Flutter)
- **Read-Only Status:** The `reference/` directory is strictly READ-ONLY. No AI is permitted to modify files inside `reference/` or automatically overwrite active project code (`backend/vmarket-web/`, `User app/`, `Vendor app/`, `Delivery Man App/`) with stock reference code without explicit verification.

## 9. Enterprise Security & Financial Invariants (Non-Negotiable) 🛡️

### A. Zero-Trust IDOR Authorization Scoping
Every controller and repository action that views, modifies, or deletes a private resource (Orders, Products, Coupons, Reviews, Addresses, Profile, Wallet, Withdrawals) MUST explicitly scope the query to the authenticated principal:
- **Customer Context:** Must enforce `where('customer_id', auth('customer')->id())` or verified `guest_id`.
- **Vendor Context:** Must enforce `where('user_id', auth('seller')->id())->where('added_by', 'seller')` or `where('seller_id', auth('seller')->id())`.
- **Admin Context:** Must enforce `where('id', auth('admin')->id())` for profile, credential, and password operations. Route parameters (`$id`) must NEVER be trusted alone for ownership.

### B. Pessimistic Balance Concurrency Locks
Any read-modify-write operation involving financial balances (Customer Wallet, Vendor Balance, Delivery Man Cash-in-Hand, Platform Commissions) MUST execute inside an atomic database transaction (`DB::transaction()`) with a pessimistic row-level lock (`->lockForUpdate()`). Optimistic/unlocked wallet deductions are strictly prohibited.

### C. Universal 6-Digit OTP & Exact Identity Matching Standards
- **Length Standard:** All OTP generators MUST use the **6-digit cryptographic format** (`rand(100000, 999999)`). Legacy 4-digit codes (`rand(1000, 9999)`) are forbidden.
- **Exact Identity Matching:** Identity lookups for authentication, password resets, and phone/email verification MUST strictly use exact equality (`where('identity', $identity)`), NEVER fuzzy SQL search (`where('identity', 'like', "%{$identity}%")`).
- **Expiration & Attempt Bounds:** Every OTP verification endpoint MUST enforce a **15-minute expiration bound** (`addMinutes(15)->isPast()`) and a **5-attempt brute-force lockout** (`max_otp_hit = 5`).

### D. Anti-Mass-Assignment Filtering
Never pass raw `$request->all()` directly into Eloquent `create()`, `update()`, or repository update methods. All model mutations must strictly use `$request->only(...)` or dedicated Service data mappers to prevent parameter injection into sensitive database columns (`is_paid`, `order_status`, `role_id`, `seller_id`, `wallet_balance`).

## 10. Mandatory Systemic & Mathematical Proof Directive (Zero-Drift & Cross-Module Verification) 🧮
**This is an inviolable prime directive.** Any AI performing modifications, optimizations, bug fixes, or feature additions across Victorious MARKET MUST rigorously prove that the entire system operates with 100% error-free integrity before concluding any task:
1. **Mathematical Invariant Proofs ($\Delta = 0.00$):** All financial calculations, order totals, split-tender payments, commission splits, debtor installment bounds, blind drawer shift reconciliations, and waybill shortage variances must be mathematically formulated, executed, and proven with zero drift ($\Delta = 0.00$).
2. **Cross-Actor & Multi-Platform Verification:** The AI must explicitly verify and prove feature-by-feature that modifications preserve operational parity across all 4 primary system actors:
   - Super Admin Command Center
   - Omnichannel Merchants (Web Dashboard & Vendor Mobile App)
   - Online Shoppers (Web Storefront & Customer Mobile App)
   - Delivery Logistics Riders (Rider Mobile App)
3. **Reproducible Test Execution:** Every code edit must pass syntax validation (`php -l` for PHP / `flutter analyze` for Dart) and automated regression execution.
4. **Mandatory Documentation of Proof:** All mathematical proofs, balance tables, and verification logs must be permanently updated in `VICTORIOUS_MARKET_MATHEMATICAL_AND_SYSTEMIC_PROOF.md` and documented in `AI_CHANGELOG.md` before committing. No task is complete without reproducible proof.

## 11. The Controlled Completion Path & Decommissioning Directive 🛑

### A. The Controlled Completion Roadmap
To prevent architectural sprawl and converge VMarket toward production readiness, all AI agents must strictly follow the sequential completion roadmap:
1. **Finish Backend Geography & Fulfillment:** Canonical LGA routing (`Country → State → LGA`), directional lanes (`Origin LGA → Destination LGA → DeliveryLane`), and in-shop pickup (`Shop → Pickup Settings → Availability`), cleanly integrated into existing checkout/settlement engines without duplicate pipelines.
2. **Backend Integration & Hardening:** Comprehensive audit and deterministic test scenarios across API contracts, database, auth, branch isolation, transactions, stock locks, payment verification, idempotency, fulfillment, cashback, and legacy dependencies.
3. **Freeze Backend API Contracts:** Lock the authoritative API schemas so client applications consume backend contracts rather than inventing marketplace logic.
4. **Finish Customer App (`User app`):** Complete end-to-end customer journey strictly consuming backend decisions.
5. **Finish Vendor App (`Vendor app`):** Complete merchant operations, inventory, orders, returns, and strict branch/employee security isolation.
6. **Finish Delivery App (`Delivery Man App`):** Complete rider dispatch, merchant pickup, transit, and proof of delivery. Hubs and riders are operational logistics infrastructure, not marketplace geography.
7. **Admin Command Center:** Complete unified governance, geography/lane controls, merchant/order management, and audit logs.
8. **End-to-End Simulation:** Run realistic multi-actor scenarios (Uyo→Uyo, Uyo→Eket, In-Shop Pickup, Split Fulfillment) and adversarial edge cases (race conditions, invalid LGA, tamper attacks).
9. **Controlled V1 Launch:** Small merchant cohort (~10 merchants) + limited geography + controlled logistics.
10. **Progressive Post-V1 Scaling:** Advanced analytics, loyalty tiers, ads, broader geographic coverage.

### B. Single Authoritative Implementation Rule (One Concept → One Implementation)
The repository must maintain **exactly one authoritative implementation** for every marketplace business capability. Legacy implementations must not remain as ambiguous alternatives for future AIs to guess between. Clean architecture is AI-readable architecture.

### C. Architecture Status Taxonomy
Every major capability, service, model, and route must have an unambiguous classification:
- **`AUTHORITATIVE`**: The current, single source of truth for the capability. All new and existing active flows must use this.
- **`DEPRECATED`**: Obsolete implementation slated for removal. No new code may consume it; existing callers must be actively migrated.
- **`LEGACY / MIGRATION`**: Historical schema or operational structure in active transition (e.g., `DeliveryHub` repurposed as internal logistics infrastructure, not public geography).
- **`REMOVED`**: Fully eradicated code once 100% of production callers, migrations, and dependencies have been decoupled.

### D. The 10-Step Capability Migration & Cleanup Protocol
Never delete code aggressively based on filenames or assumptions. Follow this disciplined protocol:
1. **Identify:** Inspect the capability, callers, routes, and data dependencies.
2. **Search:** Conduct exhaustive repository-wide search for all callers (Controllers, Services, Models, Blade views, Flutter apps, tests).
3. **Zero Duplicate Engines:** Never build a parallel duplicate engine alongside an unmigrated legacy engine without explicit deprecation linkage.
4. **Migrate Callers:** Re-point all production callers to the authoritative implementation one by one.
5. **Mark Deprecated:** Add explicit `@deprecated` annotations and log deprecations.
6. **Remove Dead Code:** Delete obsolete controllers, services, models, routes, and migrations once all callers are migrated.
7. **Clean Clients & Docs:** Remove dead endpoints, unused client DTOs, obsolete tests, and stale configuration.
8. **Universal Reference Audit:** Perform repository-wide search to confirm zero lingering references.
9. **Regression Proof:** Validate syntax (`php -l`, `flutter analyze`) and execute deterministic tests.
10. **Document & Commit:** Record retained legacy exceptions in `AI_CHANGELOG.md` and commit.

### E. Continuous Per-Phase Cleanup
Cleanup is mandatory in **every** phase, not deferred to the end:
$$\text{Build} \longrightarrow \text{Integrate} \longrightarrow \text{Test} \longrightarrow \text{Migrate} \longrightarrow \text{Remove Obsolete Code} \longrightarrow \text{Document} \longrightarrow \text{Git Commit}$$

## 12. 3-AI Control System (Human → Reviewer → Backend → Reviewer → Frontend → Reviewer pushes) 📡

Exactly 3 independent AIs. No other AI roles exist.

1. **REVIEWER AI** (coordinator, gatekeeper, SOLE push authority): the ONLY AI the human talks to. Receives every requirement, decomposes it into tickets, dispatches copy-paste-ready exact-prompt work orders (`.ai/templates/work-order-template.md`) to Backend AI then Frontend AI, reviews ALL code, and ONLY Reviewer merges to `main` and pushes — after its own `APPROVED` + gate PASS, via `scripts/release/merge-release`. Reviewer never writes implementation code. Charters: `.opencode/agents/vmarket-reviewer.md`, `.ai/agents/REVIEWER_AI.md`.
2. **BACKEND AI** (worker, PHP logic only): sole owner of Laravel PHP logic — `backend/vmarket-web/app/**`, `routes/**`, `config/**`, `database/**`. Works ONLY from Reviewer work orders. Never touches Flutter, Blade, or theme assets. Never merges, never pushes to `main` (pushes only its own `backend/` branches for Reviewer inspection). SSOT for money, fees, inventory, OTP, state machines. Charters: `.opencode/agents/vmarket-backend.md`, `.ai/agents/BACKEND_AI.md`.
3. **FRONTEND AI** (worker, all UI): sole owner of ALL UI — `User app/**`, `Vendor app/**`, `Delivery Man App/**`, `backend/vmarket-web/resources/views/**` (all Blade), `backend/vmarket-web/public/assets/**`. Works ONLY from Reviewer work orders, starting only after Reviewer confirms `BACKEND_DONE`. Never touches backend PHP logic. Never merges, never pushes to `main` (pushes only its own `frontend/` branches). Consumes backend contracts only, never calculates business rules. Charters: `.opencode/agents/vmarket-frontend.md`, `.ai/agents/FRONTEND_AI.md`.

Mandatory pipeline for every feature: `Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes`. Skipping a stage is forbidden. Backend and Frontend never communicate directly — all handoffs flow through Reviewer tickets. No human approval sits in the release path: Reviewer `APPROVED` + gate PASS is the push authority.

4. **Strict Prohibition of Client-Side Business Math**: Frontend AI must never implement fee calculations, pricing formulas, discount logic, commission cuts, or cryptographic OTP generation in client code.
5. **Dedicated Actor Inboxes**: Frontend AI posts per-surface requests through the designated mailbox in `.agents/sync/`:
   - `INBOX_USER_APP.md` (Customer App)
   - `INBOX_STOREFRONT.md` (Web Storefront)
   - `INBOX_VENDOR.md` (Vendor Web & Mobile App)
   - `INBOX_DELIVERY.md` (Delivery Rider App)
   - `INBOX_ADMIN.md` (Admin Control Center)
6. **Authoritative Contract Registry**: All live, verified backend routes and JSON schemas are maintained in `.agents/sync/API_CONTRACT_REGISTRY.md`. Frontend AI must consume these exact contracts and must never hallucinate unverified endpoints or JSON keys.
7. **The RFC Flow**: When Frontend AI needs an endpoint or field, it posts a structured Request Ticket in its inbox, awaits Backend AI fulfillment and schema registration, and only then binds the client UI. Full details are governed by `.agents/sync/CROSS_AGENT_COMMUNICATION_PROTOCOL.md`.
