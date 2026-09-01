# Victorious MARKET AI Development Rules

Welcome to the Victorious MARKET (Vmarket) ecosystem! This file enforces strict rules and patterns that **ALL AIs** must adhere to when working on this repository. **All AIs must follow all the rules in AGENTS.md strictly; no AI is allowed to bypass them under any circumstances.**

## 0. Prime Directive: Read Governance Documents First
Before taking ANY action, every AI **MUST** read:
1. `AI_ENGINEERING_RULES.md` — The foundational engineering principles of Vmarket as ONE unified platform.
2. `CHANGE_IMPACT_PROTOCOL.md` — The mandatory 6-point pre-change impact analysis checklist.
3. `ARCHITECTURE.md` — The system topology, service layers, and state boundaries.
4. `AI_CHANGELOG.md` — The chronological log of recent AI modifications.

## 1. Golden Rule: Read Before Writing
Before making ANY changes to this codebase, you MUST:
- Analyze the existing structure.
- Understand how your requested change integrates with the existing architecture.
- Do NOT introduce new architectural patterns (e.g., do not install Redux if the app uses Provider, do not use raw SQL if the backend uses Eloquent Repositories).
- You must always read `AI_CHANGELOG.md` in the root directory to understand recent modifications made by other AIs.

## 2. Mandatory Change Logging
Any time you make a functional change, fix a bug, or complete a feature, you **MUST** document it in `AI_CHANGELOG.md` located in the root of the workspace. Always include the exact timestamp in the header: `### [YYYY-MM-DD HH:MM UTC] <Title> [<Scope>]`. This ensures all AIs remain synchronized on the project's state.

## 3. Strict Architectural Patterns

### A. The Laravel Backend (`backend/Admin and web new install V16.1`)
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

### C. Delivery Man App (Flutter)
- **State Management:** This specific app uses **GetX** for state and routing. Do NOT use Provider here.
- **Security Notice:** API tokens and credentials must ONLY be stored using `flutter_secure_storage`. Any legacy fallback in `shared_preferences` must be migrated.
- **Performance:** When dealing with maps and geolocation, ensure UI repaints are minimized via GetX reactive variables (`.obs`).

### D. Payment Gateways & Hook Security (Laravel Backend)
- **Atomic Payment Row Lock Directive:** Every payment gateway controller (Paystack, Flutterwave, Stripe, PayPal, Razorpay, bKash, Paytm, etc.) MUST enforce an **Atomic Row-Level Lock** (`where('is_paid', 0)->update(...)`) on `payment_requests`.
- **Double Execution Guard:** Before invoking `$data->success_hook` (`digital_payment_success`), the code MUST check `$affected > 0`. Never call `success_hook` without checking affected rows, to prevent concurrent browser callbacks and background IPN/webhooks from generating duplicate orders or duplicate wallet credits.

## 4. UI / UX Standards & Official Brand Color System 🎨
- **Official Brand Palette (Strict Invariant for ALL AIs):**
  - **Primary Brand Purple:** `#5E17EB` (Vibrant Royal Purple — Used for main navigation, primary action buttons, active tab indicators, brand headers, and primary checkout buttons).
  - **Secondary Brand Gold:** `#FFD700` (Electric Gold — Used for promotional badges, star ratings, VIP/Verified badges, discount pills, and high-impact highlight accents).
  - **Base Clean White:** `#FFFFFF` (Pure White — Used for clean card surfaces, readable high-contrast typography, inverted icons, and modal container backgrounds).
- **Strict Invariant for All AI Agents:**
  - Every AI modifying Blade templates, CSS/SCSS stylesheets, POS UI registers, or Flutter Dart theme files MUST strictly use these exact hex codes: `#5E17EB`, `#FFD700`, and `#FFFFFF`.
  - **Prohibition of Generic Colors:** Never substitute with arbitrary generic purples (e.g. `#800080`, `#9333ea`) or generic yellows (e.g. `#ffff00`, `#eab308`). Always apply `#5E17EB` and `#FFD700` with high visual polish, balanced contrast ratios, and elegant micro-interactions.
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

### Commit Procedure
After completing any change:
1. `git add <specific files>` — Stage only the files you changed (do NOT use `git add .` blindly).
2. `git commit -m "<message> [AI]"` — Include `[AI]` tag so human developers know it was AI-authored.
3. Log the changes in `AI_CHANGELOG.md` **before** committing (so the changelog itself is part of the commit).
4. Verify with `git status` that the working tree is clean before ending your session.

### Grouping Strategy
- Group commits by **component** (one commit per app, one for backend).
- Do NOT mix Flutter app changes with Laravel backend changes in a single commit.
- New untracked files (widgets, screens) must be explicitly staged with `git add <path>`.

## 6. Universal Explanatory Code Commenting & Cross-Layer Linking Standard 📝

**This is a mandatory prime directive for ALL AIs:**
Every file, class, method, function, Blade view, JavaScript block, CSS module, and Flutter widget created or modified MUST include clear, plain-English explanatory comments detailing **what it does, why it exists, which user role uses it, and how the frontend links to the backend**.

### A. Backend Commenting Standard (PHP / Laravel)
Every Controller method, Repository action, Service, Migration, and Middleware MUST have a structured docblock and inline explanation:

```php
/**
 * [AI] Brief Plain-English Summary of what this method accomplishes.
 *
 * Business Context: Explains why this exists (e.g. Nigerian retail walk-in customer checkout).
 *
 * @role_access       Role 3 (Verified Merchant), Role 5 (Store Cashier)
 * @frontend_view     resources/views/pos/index.blade.php (POS Counter Register)
 * @route_name        pos.checkout (POST /pos/checkout)
 * @security_checks   Zero-Trust IDOR ($sellerId scope), Pessimistic Row Lock (lockForUpdate)
 * @financial_math    Total = Subtotal + Tax - Discount; Debt = Total - Paid (Delta = 0.00)
 */
```

### B. Frontend Commenting Standard (Blade Views, JavaScript, Flutter)
Every Blade template, JS function, modal component, and Flutter widget MUST declare its purpose and link to its corresponding backend controller:

```blade
{{-- 
  [AI] Component: Cross-Branch Stock Lookup Modal
  Purpose: Displays physical inventory across all merchant branches without allowing unauthorized remote dispatches.
  Backend Controller: Modules/Pos/app/Http/Controllers/PosController.php :: getBranchStocks()
  API Route: GET /pos/product/{id}/branch-stocks (Route: pos.product.branch_stocks)
  Target Roles: Cashiers (Role 5), Storekeepers, Verified Merchants (Role 3)
--}}
```

```javascript
// [AI] Handles real-time barcode scanner enter-key trigger.
// Matches exact product SKU and increments cart quantity without refreshing the page.
```

### C. The 4 Non-Negotiable Invariants for Comments:
1. **Plain-English Empathy:** Explain the *intent* and *business reason*, not just restating obvious code syntax.
2. **Mandatory Cross-Linking:** Always show which Frontend view connects to which Backend Controller action and Route name.
3. **Mandatory `[AI]` Prefix:** All AI-authored comments must begin with `[AI]` so human engineers can immediately trace AI contributions.
4. **Preservation:** NEVER delete, strip, or truncate existing comments or docstrings.

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

## 11. Mandatory 9-Role Visibility Breakdown & Multi-Actor Proof Protocol 👥
**Every feature, modification, endpoint, or navigation change MUST explicitly break down and prove what each of the 9 standardized ecosystem roles will see, what they can do, and why.** No task is considered complete without this multi-role proof.

### The 9 Standardized Ecosystem Roles:
1. **Super Admin:** The single platform owner and supreme commander. Exclusive access to SaaS Master Control (`/saas/*`), global configuration, platform auditing, and the `🔙 Back to Vmarket Admin` topbar hub action.
2. **Super Admin Employee:** Platform staff (Customer Support, Product Moderators, Finance Auditors). Scoped strictly to permitted admin sub-modules with role-tailored navigation.
3. **Verified Merchant:** Approved store owner. Enjoys full multi-branch In-Store POS, waybills, debt ledgers, live online marketplace selling on Victorious MARKET, omnichannel inventory sync, and `🔙 Back to Merchant Panel`.
4. **Unverified Merchant:** Newly registered store owner pending KYC verification. Granted **Free-Tier In-Store POS (1 Store)** access for offline counter sales and barcode scanning, but **strictly blocked from online marketplace selling** and has the header return button **masked/hidden** (`Free In-Store POS (Pending KYC)` badge).
5. **Verified Merchant Employee:** Staff (Cashiers, Storekeepers) assigned to an approved store. Access to POS counter registers (`/store/{slug}/login`), shift drawers, and offline receipt printing.
6. **Unverified Merchant Employee:** Staff (Cashiers) assigned to a physical free-tier store. Isolated strictly to local offline sales with zero marketplace access.
7. **Active Deliveryman:** KYC-approved logistics rider with live delivery assignment, GPS tracking, and cash-in-hand collection privileges.
8. **Inactive Deliveryman:** Logistics rider pending verification, suspended, or offline. Blocked from picking up orders or collecting cash until KYC verified.
9. **Customer:** End-user shopper on web storefront and mobile apps. Access to product browsing, order tracking, digital wallet, and in-store QR checkout.

### Mandatory Role Breakdown Checklist for Every Feature:
Before concluding any implementation or architectural change, the AI MUST document:
1. **Role Visibility Table:** A clear table mapping what each of the 9 roles sees on the screen (buttons, menus, badges, headers).
2. **Authorization Boundary Proof:** Proof that unauthorized roles (e.g. Unverified Merchants or Employees) cannot access gated routes (e.g. SaaS Master Control or live online selling) via direct URL manipulation.
3. **Automated Multi-Role Test Suite:** Execution of automated tests (e.g. `test_9_tier_role_taxonomy_and_access_matrix.php`) asserting correct visibility, HTTP response codes, and session isolation.

## 12. Universal Zero-Penetration Isolation & Absolute Personalization Invariant 🔒
**Every single point in the system must be completely isolated and personalized down to the exact individual person based on their role.** Cross-user penetration, cross-shop data leaks, or unpersonalized UI states are strictly prohibited across all layers:

1. **Total Shop & Merchant Isolation (Zero Cross-Tenant Bleed):**
   - No merchant or merchant's employee can EVER view, modify, list, or penetrate another merchant's store, products, orders, customers, debts, cash drawers, waybills, or settings.
   - Every single backend query and repository call MUST strictly enforce `where('seller_id', $authSellerId)` or `where('shop_id', $authShopId)`.
2. **Employee & Cashier Micro-Isolation:**
   - Cashiers and staff are strictly locked to their assigned physical store/register (`shop_id`).
   - Staff cannot access unauthorized modules, cannot view administrative SaaS settings, and cannot access other employees' shift drawers or personal sales histories without elevated manager permissions.
3. **Rider & Logistics Micro-Isolation:**
   - Every delivery rider's active orders, delivery earnings, cash-in-hand collections, customer contact masking, and GPS breadcrumbs are isolated strictly to their authenticated `delivery_man_id`.
   - Zero visibility into other riders' orders, wallets, or routes.
4. **Customer Micro-Isolation (Zero-Trust IDOR):**
   - Customer carts, addresses, order receipts, payment methods, and digital wallet balances are strictly bound to `customer_id` / verified `guest_id`.
5. **Absolute UI / UX Down-to-the-Person Personalization:**
   - Every header, topbar, sidebar, button, badge, greeting, and notification must be dynamically personalized to that exact user:
     - Only **Super Admin** sees platform command controls and `Back to Vmarket Admin`.
     - Only **Verified Merchants** see `Back to Merchant Panel` and live online sync.
     - **Unverified Merchants** see local Free POS only with `Marketplace Pending` status (never marketplace return buttons).
     - **Employees** see their active assigned shop register and cashier tools only.
     - **Riders** see active delivery routes and cash collection prompts only.
     - **Customers** see their personalized cart, wishlist, and orders only.

## 13. Vmarket Master Engineering Rules & Execution SOP
All AIs working on this monorepo must strictly adhere to the following master execution rules:

### A. Omnichannel Codebase Parity Invariant
The repository contains two Laravel backends: the central marketplace (`backend/vmarket-web`) and In-Store POS (`hysam`).
- Any database scoping rule (e.g., `company_id`, `seller_id`, or `shop_id` isolation) or query modification applied to central Vmarket must be audited and identically mirrored in POS (`hysam`) to prevent cross-tenant inventory or data leaks.

### B. Pre-Change & Post-Change Verification Checklist
Before writing any code, evaluate the pre-change impact report checklist:
1. Explain where the feature currently lives and what will change.
2. Outline existing security boundaries and client impact (across all 6 client apps/panels).
After writing code, verify:
1. Legitimate users can perform the operation (authorized tests).
2. Unauthorized tenants/branches/users are denied (unauthorized tests).
3. Run the Two-Minute Security Smoke Test and report execution metrics.

### C. Eloquent-Backed In-Process Request Testing
To prevent silent query or middleware bypasses, security smoke tests must boot the Laravel kernels in-process, bind mock route resolvers (for parameter routing mapping), authenticate the mock session guard (e.g., `Auth::login()`), and dispatch request simulations. Do not rely solely on static analysis or simple database queries.

### D. Final Response Format
Upon completing any task, format the final response as follows:
- **CHANGE:** What was changed.
- **FILES:** Clickable file links of modified/created files.
- **SECURITY:** Authorization checks and row locks verified.
- **DATABASE:** Schema changes or migrations performed.
- **TESTS:** Tests executed and results.
- **REGRESSION:** Functionality checked.
- **RISKS:** Remaining tech debt or warnings.
- **RESULT:** PASS / FAIL / PASS WITH WARNINGS

## 14. Mandatory Endpoint Pre-Analysis & Synchronized Catalogue Maintenance 🗺️
**This is a strict invariant for ALL AI agents:**
1. **Analyze Master Registry First:** Before creating, modifying, or refactoring ANY endpoint, controller method, or route, every AI **MUST** read and analyze `ALL_ECOSYSTEM_ENDPOINTS_AND_SECURITY_TAXONOMY.md`.
2. **Synchronized Master Catalogue Appending:** Whenever an AI introduces, updates, or deletes any route in Web, Admin, Vendor, Customer, Delivery Rider, POS, or REST API routes (`routes/web/`, `routes/admin/`, `routes/vendor/`, `routes/rest_api/`, or `Modules/Pos/routes/`), the AI **MUST explicitly append and update** `ALL_ECOSYSTEM_ENDPOINTS_AND_SECURITY_TAXONOMY.md` with its exact HTTP methods, URI, route name, controller action, guard, and security middleware.
3. **Continuous 100+ Test Suite Synchronization:** New endpoints must also be registered in `test_100_plus_ecosystem_views_and_apis_suite.php` to ensure 100% continuous test coverage.

## 15. Universal 5-Pillar Security Standard for Every Endpoint (Zero-Loopholes) 🛡️
**Every endpoint across all platforms (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD) must strictly enforce the following 5 security pillars without exception:**

1. **Zero-Trust Authentication:** Explicit guard definition (`auth:admin`, `auth:seller`, `auth:customer`, `auth:api`, `auth:delivery_man`). Unauthenticated fallback must safely redirect or return HTTP 401/403 (never unhandled 500s).
2. **Tenant Scoping & Micro-Isolation (Zero Cross-Tenant Bleed):** Every database query within the handler MUST enforce `where('seller_id', $authSellerId)`, `where('shop_id', $authShopId)`, `where('customer_id', $authCustomerId)`, or `where('delivery_man_id', $authRiderId)`. Route IDs (`$request->id`) must NEVER be trusted alone for ownership.
3. **Anti-Mass-Assignment & Input Validation:** Handlers must never pass `$request->all()` into Eloquent model mutations; only validated data via `$request->only(...)` or dedicated FormRequest data mappers.
4. **Pessimistic Balance & Row Locks:** Any read-modify-write on wallet balances, debt ledgers, order payments, commission splits, or cash drawers must execute inside `DB::transaction()` with pessimistic row-level locking (`->lockForUpdate()`).
5. **Audit Logging & Atomic Execution Closure:** State mutations, order status transitions, refunds, and financial updates must emit structured audit entries and prevent concurrent double-execution via atomic row updates (`where('is_paid', 0)->update(...)`).

## 16. Mandatory 1,583-Endpoint Automated Security Proof After Every Modification 🛡️
**This is an inviolable prime directive for ALL AI agents.**
Every AI completing ANY task, feature addition, bug fix, route refactoring, or database modification across Victorious MARKET MUST execute the automated 1,583-endpoint security verification harness:
```bash
php test_all_1572_endpoints_security_and_role_proof.php
```
### Mandatory Verification Invariants:
1. **100% Zero-Defect Operational Parity:** All 1,583 ecosystem endpoints across Central Marketplace (`backend/vmarket-web`), POS (`Modules/Pos`), and Delivery Hub (`Modules/Delivery`) must be evaluated with **0 Fatal Unhandled 500 Exceptions**.
2. **9-Role Multi-Actor Security Proof:** The execution must verify that Super Admin, Super Admin Employee, Verified Merchant, Unverified Merchant, Verified Merchant Employee, Unverified Merchant Employee, Active Deliveryman, Inactive Deliveryman, Customer, and Guest are strictly bounded to their authorized privileges.
3. **Universal 5-Pillar Security Standard:**
   - Zero-Trust Authentication intercepts unauthenticated requests cleanly (302/401/403/404).
   - Zero Cross-Tenant Bleed (queries scoped to `seller_id`, `shop_id`, `customer_id`, or `delivery_man_id`).
   - Anti-Mass-Assignment Protection via `$fillable` / `$guarded`.
   - Pessimistic Row Locks (`lockForUpdate()`) on financial balances.
   - Mathematical Invariant Proof with zero drift ($\Delta = 0.00$).
4. **Mandatory AI Changelog Logging & Clean Commit:** The AI must document the test results in `AI_CHANGELOG.md` before committing changes to Git with `[AI]`. No change may be merged or reported as complete without this passing proof.

## 17. Mandatory 10-Suite PHP Multi-User Security Testing Harness 🧪
**This is an inviolable rule for ALL AIs:**
Whenever any AI modifies backend controllers, repositories, middleware, or routes across Victorious MARKET, the AI **MUST execute the 10 dedicated PHP security test suites** located in `tests/Security/`.

### The 10 Standalone Security Suites:
| Suite File | Target Actor / Standard | Primary Invariants Verified |
|---|---|---|
| `tests/Security/Suite01_SuperAdminAccessTest.php` | Role 1: Super Admin | Unrestricted access across all modules, MRR dashboards, SaaS POS, payment config |
| `tests/Security/Suite02_AdminEmployeeModuleGateTest.php` | Role 2: Admin Employee | Strict `module:*` middleware gates (e.g. `module:pos_management`, `module:3rd_party_setup`) |
| `tests/Security/Suite03_VerifiedMerchantIsolationTest.php` | Role 3: Verified Merchant | Zero cross-tenant data bleed (`where('seller_id', $authSellerId)` on products, orders, shops) |
| `tests/Security/Suite04_UnverifiedMerchantBlockTest.php` | Role 4: Unverified Merchant | Status=0 / pending KYC merchants blocked from live marketplace selling & withdrawals |
| `tests/Security/Suite05_DeliveryManIsolationTest.php` | Roles 7 & 8: Delivery Riders | Active rider route/cash isolation (`delivery_man_id`); inactive rider zero operational access |
| `tests/Security/Suite06_CustomerIDORTest.php` | Role 9: Customer | Zero-trust IDOR bounds on orders, shipping addresses, wallets, and account delete |
| `tests/Security/Suite07_PaymentGatewaySecurityTest.php` | Payment Gateways (All Roles) | Atomic locks (`is_paid`=0), Paystack reference entropy, live/test key isolation, `UpdateStatus()` whitelist |
| `tests/Security/Suite08_OTPBruteForceTest.php` | Universal Auth / OTP | 6-digit standard, 5-attempt brute-force lockout, 15-minute expiry, exact equality matching |
| `tests/Security/Suite09_AntiMassAssignmentAndInputValidationTest.php` | Data Integrity (Pillars 3 & 4) | Anti-mass-assignment parameter injection guards, pessimistic balance locks (`lockForUpdate()`) |
| `tests/Security/Suite10_NineRoleCrossAccessMatrixTest.php` | Master 9-Role Cross Matrix | Full cross-actor privilege boundary matrix & mutual exclusion across all 9 roles |

### Mandatory Execution Command:
```bash
# Run all suites together:
php artisan test tests/Security/

# Or run individual suites independently:
php artisan test tests/Security/Suite01_SuperAdminAccessTest.php
php artisan test tests/Security/Suite02_AdminEmployeeModuleGateTest.php
php artisan test tests/Security/Suite03_VerifiedMerchantIsolationTest.php
php artisan test tests/Security/Suite04_UnverifiedMerchantBlockTest.php
php artisan test tests/Security/Suite05_DeliveryManIsolationTest.php
php artisan test tests/Security/Suite06_CustomerIDORTest.php
php artisan test tests/Security/Suite07_PaymentGatewaySecurityTest.php
php artisan test tests/Security/Suite08_OTPBruteForceTest.php
php artisan test tests/Security/Suite09_AntiMassAssignmentAndInputValidationTest.php
php artisan test tests/Security/Suite10_NineRoleCrossAccessMatrixTest.php
```
All AIs must verify 100% PASS across all suites, update `ALL_ECOSYSTEM_ENDPOINTS_AND_SECURITY_TAXONOMY.md` and `AI_CHANGELOG.md`, and make an atomic Git commit with `[AI]`.

## 18. Mandatory Full-Stack Dual Delivery, Role-Awareness, Top-Notch Security & Premium UX Invariant (Strict Non-Negotiable) 👑
**This is an absolute, inviolable prime directive for ALL AIs operating on this repository:**

### Pillar I: Mandatory Dual Frontend & Backend Delivery (Zero Half-Baked Features)
1. **Never Deliver One-Sided Code:** Under NO circumstance is an AI permitted to implement a backend endpoint without the corresponding frontend UI, or create a frontend UI without the complete backend controller, validation, database persistence, and API routes.
2. **Complete Vertical Slice:** Every feature, enhancement, or bug fix must be implemented end-to-end as a complete vertical slice:
   - **Database / Schema:** Migrations, model fillables, foreign keys, indexes, seeds.
   - **Backend Logic:** Controllers, FormRequests, Repositories, Services, Atomic DB transactions, pessimistic locks.
   - **Frontend UI & Interactivity:** Blade views / Flutter screens, CSS/styling, responsive layouts, modals, client-side validation, Ajax handshakes, error states, and live DOM updates.
   - **Route Taxonomy:** Registration in `routes/` and synchronized update in `ALL_ECOSYSTEM_ENDPOINTS_AND_SECURITY_TAXONOMY.md`.

### Pillar II: Deep 9-Tier Role Awareness & Micro-Isolation
1. **Explicit Role-Tailored Behavior:** Every feature MUST be explicitly aware of the user's standardized role and adapt both its frontend presentation and backend authorization:
   - **Super Admin:** Platform-wide oversight, SaaS master controls, and global configurations.
   - **Super Admin Employee:** Scoped strictly to permitted admin sub-modules with role-tailored navigation.
   - **Verified Merchant:** Full omnichannel access (In-Store POS + live online marketplace selling + waybill transfers).
   - **Unverified Merchant:** Free single-store POS counter access with locked marketplace publishing and upgrade prompts.
   - **Verified Merchant Employee (Cashier / Storekeeper):** Scoped strictly to their physically assigned branch register (`assigned_branch_id`); blocked from unassigned branches.
   - **Unverified Merchant Employee:** Scoped strictly to the physical free-tier store register.
   - **Active Deliveryman:** Live GPS routes, order pickups, and cash-in-hand collections.
   - **Inactive Deliveryman:** KYC pending / suspended; blocked from order pickups.
   - **Customer:** Scoped strictly to personal cart, wishlist, address book, and orders.
2. **Zero Unauthorized Exposure:** Unauthorized buttons, links, or controls must NEVER render on the frontend for unpermitted roles, and the backend must enforce strict `abort(403)` gates against URL tampering.

### Pillar III: Top-Notch Enterprise Security (Zero-Loopholes)
1. **Physical Responsibility & Branch Custody:** Staff can only manipulate data and dispatch stock for their physically assigned branch. If an item is needed from another branch, the system must facilitate direct communication (phone/WhatsApp/transfer request), requiring the physical origin custodian to dispatch.
2. **Zero Cross-Tenant Bleed (IDOR Elimination):** Every database query MUST strictly scope by `seller_id`, `shop_id`, `customer_id`, or `delivery_man_id`.
3. **Pessimistic Concurrency & Transactions:** Read-modify-write on inventory and financial balances must always execute inside `DB::transaction()` with `->lockForUpdate()`.
4. **Anti-Mass-Assignment & Input Filtering:** Never pass `$request->all()` into model mutations; only use `$request->only(...)` or FormRequest data mappers.

### Pillar IV: World-Class Premium User Experience & Official Brand System
1. **Official Brand Aesthetics (Strict Invariant):**
   - **Primary Royal Purple:** `#5E17EB` (Vibrant Royal Purple)
   - **Secondary Electric Gold:** `#FFD700` (Electric Gold)
   - **Base Clean White:** `#FFFFFF` (Pure White)
2. **Operational Empathy & Frictionless UX:**
   - Visual clarity, intuitive micro-interactions, responsive button states, loading spinners, thermal printer alignment, and barcode scanner shortcuts (Enter key triggers).
   - Friendly, actionable error messages guiding the user on how to resolve issues (e.g. direct storekeeper phone call).




