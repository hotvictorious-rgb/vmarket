# Victorious MARKET — Phase 0 Engineering Baseline Audit (BASELINE.md)

> **CONTROL ZONE FILE — HUMAN OWNERSHIP ONLY**  
> **Status:** Phase 0 Baseline Audit under Multi-AI Control System Specification (v3) §21.3  
> **Audited Date:** 2026-09-24  
> **Audited By:** Central Engineering AI & Human Owner  
> **Rule:** The hard release gate compares all future candidate runs against this baseline. **No new failures relative to baseline are permitted.** Pre-existing defects are cataloged as tickets and legacy debt.

---

## 1. System Inventory Summary (§21.3.1)

| Area | Monorepo Path | Subsystems | Technologies |
| :--- | :--- | :--- | :--- |
| **Backend Core** | `backend/vmarket-web/` | Core REST APIs (v1, v2, v3), Eloquent models, domain services, repositories | Laravel 11 / PHP 8.4 / SQLite & MySQL |
| **Customer Web** | `backend/vmarket-web/resources/themes/theme_vmarket/` | Public storefront UI, category navigation, LGA-scoped product feed | Laravel Blade / Vanilla JS / CSS |
| **Customer App** | `User app/` | Customer Flutter mobile application | Flutter 3.x / Dart / Provider / GetIt |
| **Vendor Web** | `backend/vmarket-web/resources/views/vendor-views/` | Merchant web portal, in-shop pickup verification, product catalog | Laravel Blade / jQuery / CSS |
| **Vendor App** | `Vendor app/` | Merchant Flutter mobile application | Flutter 3.x / Dart / Provider / GetIt |
| **Admin Web** | `backend/vmarket-web/resources/views/admin-views/` | Super Admin command center, directional delivery lanes, dispatch portal | Laravel Blade / jQuery / CSS |
| **Delivery App** | `Delivery Man App/` | Delivery logistics courier mobile application | Flutter 3.x / Dart / GetX |

---

## 2. Test Execution Baseline (§21.3.2)

### A. Backend Unit & Invariant Test Suites
* **`MarketplaceListingFreshnessTest`**: **23 PASSED**, 0 FAILED ($\Delta = 0.00$)
  - Verified default schema values, freshness expiry boundaries, 6-point eligibility invariant, vendor tenancy isolation, stock privacy, and anti-mass-assignment.
* **`PaymentFulfillmentBoundarySecurityTest`**: **21 PASSED**, 0 FAILED ($\Delta = 0.00$)
  - Verified payment protection (vendor cannot self-mark orders paid), in-shop pickup handover OTP hash comparison, constant-time `hash_equals`, rate limiting (5 attempts), and Paystack HMAC-SHA512 webhook signature verification.
* **`ProductFeedExportIsolationTest`**: **31 PASSED**, 0 FAILED ($\Delta = 0.00$)
  - Verified feed token hashing, vendor scoping lock (`added_by='seller'`), admin multi-product feed, and unapproved merchant exclusion.
* **`ExampleTest`**: **1 PASSED**

### B. Baseline Test Exceptions & Pre-Existing Failures
* **`DeliveryFlowLifecycleTest`**: **1 FAILED** (`Tests\Feature\DeliveryFlowLifecycleTest`)
  - **Failure Reason**: `QueryException: SQLSTATE[HY000]: General error: 1 no such table: orders (Connection: sqlite, Database: :memory:)`.
  - **Root Cause**: The test executes `RefreshDatabase` against an in-memory SQLite database (`:memory:`). The legacy 6valley migrations contain incremental `ALTER TABLE` statements before a base Laravel orders migration exists.
  - **Action**: Cataloged under `LEGACY_DEBT.md` as **`LD-006`** (needs SQLite memory base schema migration fixture).

---

## 3. Supply-Chain & Security Audit Baseline (§21.3.4 & §24.1)

Executed via `composer audit --format=plain` on `backend/vmarket-web`:
* **Pre-Existing Advisories Recorded**:
  - `phpseclib/phpseclib` (PKSA-3qpq-r242-jqj7, PKSA-zh4j-by9m-7mz8, PKSA-km2b-zc3b-mjm3)
  - `setasign/fpdi` (PKSA-37cw-b473-k9np)
  - `symfony/*` (PKSA-dw7n-x7f5-zf63, PKSA-2n2k-66v2-bwg3, PKSA-bf7t-jnpz-492k, PKSA-yc7t-91v9-99xs, PKSA-y6py-qpv1-h52p, PKSA-28rh-rzzn-djk4, PKSA-wtxr-p26d-nn42, PKSA-v5yj-8nmz-sk2q, PKSA-ft77-7h5f-p3r6, PKSA-b14r-zh1d-vdrc)
  - `paypal/rest-api-sdk-php` (Abandoned package notice)
* **Release Gate Rule**: These pre-existing baseline advisories are grandfathered into Phase 0. **Any newly introduced critical or high advisory in future pull requests will block the release gate.**

---

## 4. Known Untested Critical Paths & Risk Ratings (§21.3.5 & §21.3.6)

1. **In-Shop Inspection Mobile Handshake (`User app` $\leftrightarrow$ `Vendor app`)**:
   - Status: API and backend logic fully covered (21 security invariants verified); Flutter client widgets require automated widget integration tests.
   - Proposed Tier: **Tier A (Critical)**.
2. **Delivery Man App Offline Caching & GPS Ping**:
   - Status: Single location reporting wired; GPS tracking streaming requires simulated rider mock tests.
   - Proposed Tier: **Tier B (Gated)**.
3. **Legacy POS Subsystem (`backend/vmarket-web/app/Http/Controllers/Admin/POS/`)**:
   - Status: Decoupled legacy stock module.
   - Proposed Tier: **Tier C (Legacy Debt `LD-001`)**.

---

## 5. Phase 0 Exit Recommendation

Phase 0 Baseline is established and verified. All existing test outputs, structural boundaries, and pre-existing exceptions are transparently recorded with zero concealment.
