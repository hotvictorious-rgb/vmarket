# Victorious MARKET — System & Monorepo Architecture Mapping

> **CONTROL ZONE FILE — HUMAN OWNERSHIP ONLY**
> **Status:** 3-AI Control System (Backend → Frontend → Reviewer). The 8-role mapping is retired; historical tickets preserve it as audit trail.
> **Date:** 2026-09-24
> **Last Updated:** 2026-09-26  

---

## 1. Physical to Logical Path Mapping

The specification defines standard logical subsystems (§3). The table below maps these logical paths directly onto the existing Victorious MARKET monorepo layout without disrupting active production files:

| Logical Subsystem (§3) | Actual Monorepo Path | Technology Stack | Owning AI | Reviewer |
| :--- | :--- | :--- | :--- | :--- |
| **Backend Core & APIs** | `backend/vmarket-web/app/`<br>`backend/vmarket-web/routes/`<br>`backend/vmarket-web/database/` | Laravel 11 / PHP 8.4 / SQLite & MySQL | **BACKEND AI** (PHP logic only) | **REVIEWER AI** |
| **Customer Web (Storefront)** | `backend/vmarket-web/resources/themes/theme_vmarket/`<br>`backend/vmarket-web/public/themes/theme_vmarket/` | Blade / Vanilla JS / CSS / Bootstrap 5.3 | **FRONTEND AI** (all UI) | **REVIEWER AI** |
| **Customer Mobile App** | `User app/` | Flutter 3.x / Dart / Provider / GetIt | **FRONTEND AI** | **REVIEWER AI** |
| **Vendor Web Panel** | `backend/vmarket-web/resources/views/vendor-views/`<br>`backend/vmarket-web/resources/views/layouts/vendor/` | Laravel Blade / jQuery / CSS | **FRONTEND AI** | **REVIEWER AI** |
| **Vendor Mobile App** | `Vendor app/` | Flutter 3.x / Dart / Provider / GetIt | **FRONTEND AI** | **REVIEWER AI** |
| **Operations (Admin Web)** | `backend/vmarket-web/resources/views/admin-views/`<br>`backend/vmarket-web/resources/views/layouts/admin/` | Laravel Blade / jQuery / CSS | **FRONTEND AI** | **REVIEWER AI** |
| **Operations (Delivery App)** | `Delivery Man App/` | Flutter 3.x / Dart / GetX | **FRONTEND AI** | **REVIEWER AI** |
| **Cross-Cutting Tests** | `tests/contract/`<br>`tests/integration/`<br>`tests/e2e/`<br>`tests/security/`<br>`tests/regression/`<br>`tests/performance/` | PHPUnit / Pest / Flutter Test / Playwright | Owner AI of system under test | **REVIEWER AI** |
| **Control Scripts** | `scripts/ai/`<br>`scripts/tests/`<br>`scripts/release/`<br>`scripts/git/` | Windows PowerShell (`.ps1`) | **Human Only** | **Human Only** |

---

## 2. Service Boundaries & Single Source of Truth (SSOT)

```
                            ┌────────────────────────────────────────┐
                            │      CENTRAL LARAVEL BACKEND (SSOT)    │
                            │        (http://127.0.0.1:8000)         │
                            │  Money • Inventory • Fees • State Machine│
                            └───────────────────┬────────────────────┘
                                                │
         ┌───────────────────┬──────────────────┼───────────────────┬───────────────────┐
         │ (Web/Theme)       │ (REST v1)        │ (Web/Rest v3)     │ (REST v2)         │ (Web Admin)
         ▼                   ▼                  ▼                   ▼                   ▼
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│ STOREFRONT WEB  │ │ CUSTOMER APP    │ │ VENDOR WEB/APP  │ │ DELIVERY RIDER  │ │ ADMIN COMMAND   │
│ (Blade/JS)      │ │ (Flutter/Prov)  │ │ (Blade/Flutter) │ │ (Flutter/GetX)  │ │ (Blade Views)   │
│   [BACKEND AI]  │ │ [FRONTEND AI] │ │ [FRONTEND AI] │ │ [FRONTEND AI] │ │ [FRONTEND AI] │
└─────────────────┘ └─────────────────┘ └─────────────────┘ └─────────────────┘ └─────────────────┘
```

1. **Backend is the Sole Financial & State Authority**:
   - Client applications (`User app/`, `Vendor app/`, `Delivery Man App/`, Storefront) are strictly **presentation and action-dispatch layers**.
   - No client-side price, shipping fee, tax, cashback, commission, or OTP calculation is permitted.
2. **Two-Phase Checkout Protocol**:
   - Client freezes intent via `POST /api/v1/checkout/intent` $\rightarrow$ receives authoritative `order_group_id` $\rightarrow$ proceeds to Paystack.
3. **In-Shop Two-Code Architecture**:
   - `reservation_code` (₦0.00 store pass) enables physical counter inspection.
   - `pickup_verification_code` (6-digit PIN) is generated only upon Paystack settlement and verified at counter release.

---

## 3. Worktree Physical Structure on Windows

To ensure physical isolation and prevent accidental git collisions, worktrees are configured under a dedicated folder (e.g. `C:\VictoriousAI\`):

```
BACKEND-AI/            # Worktree for Backend AI (backend PHP logic only)
├── FRONTEND-AI/       # Worktree for Frontend AI (Flutter + Blade + theme assets)
├── REVIEWER/          # Reviewer worktree (detached HEAD, read-only + .ai/reviews/)
└── MAIN/              # Pristine mirror of origin/main (Human verified)
```

Each worktree has its own dedicated `.env` configuration file with isolated database schemas and dedicated test ports to prevent port or database deadlocks.
