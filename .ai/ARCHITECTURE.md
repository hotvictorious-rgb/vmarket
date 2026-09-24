# Victorious MARKET — System & Monorepo Architecture Mapping

> **CONTROL ZONE FILE — HUMAN OWNERSHIP ONLY**  
> **Status:** Stage 1 Mapping under Multi-AI Control System Specification (v3)  
> **Date:** 2026-09-24  
> **Last Updated:** 2026-09-24  

---

## 1. Physical to Logical Path Mapping

The specification defines standard logical subsystems (§3). The table below maps these logical paths directly onto the existing Victorious MARKET monorepo layout without disrupting active production files:

| Logical Subsystem (§3) | Actual Monorepo Path | Technology Stack | Owning Implementation AI | Reviewer AI |
| :--- | :--- | :--- | :--- | :--- |
| **Backend Core & APIs** | `backend/vmarket-web/app/`<br>`backend/vmarket-web/routes/`<br>`backend/vmarket-web/database/` | Laravel 11 / PHP 8.4 / SQLite & MySQL | **AI 1** (Backend) | **AI 5, 6, 7** (by audience) |
| **Customer Web (Storefront)** | `backend/vmarket-web/resources/themes/theme_vmarket/`<br>`backend/vmarket-web/public/themes/theme_vmarket/` | Blade / Vanilla JS / CSS / Bootstrap 5.3 | **AI 2** (Customer) | **AI 5** (Customer Reviewer) |
| **Customer Mobile App** | `User app/` | Flutter 3.x / Dart / Provider / GetIt | **AI 2** (Customer) | **AI 5** (Customer Reviewer) |
| **Vendor Web Panel** | `backend/vmarket-web/resources/views/vendor-views/`<br>`backend/vmarket-web/resources/views/layouts/vendor/` | Laravel Blade / jQuery / CSS | **AI 3** (Vendor) | **AI 6** (Vendor Reviewer) |
| **Vendor Mobile App** | `Vendor app/` | Flutter 3.x / Dart / Provider / GetIt | **AI 3** (Vendor) | **AI 6** (Vendor Reviewer) |
| **Operations (Admin Web)** | `backend/vmarket-web/resources/views/admin-views/`<br>`backend/vmarket-web/resources/views/layouts/admin/` | Laravel Blade / jQuery / CSS | **AI 4** (Operations) | **AI 7** (Operations Reviewer) |
| **Operations (Delivery App)** | `Delivery Man App/` | Flutter 3.x / Dart / GetX | **AI 4** (Operations) | **AI 7** (Operations Reviewer) |
| **Cross-Cutting Tests** | `tests/contract/`<br>`tests/integration/`<br>`tests/e2e/`<br>`tests/security/`<br>`tests/regression/`<br>`tests/performance/` | PHPUnit / Pest / Flutter Test / Playwright | Test Owner Rule (§4.1) | **AI 5, 6, 7** |
| **Control Scripts** | `scripts/ai/`<br>`scripts/tests/`<br>`scripts/release/`<br>`scripts/git/` | Windows PowerShell (`.ps1`) | **Human Only** (AI 8 executes) | **Human Only** |

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
│   [AI 2 / AI 5] │ │   [AI 2 / AI 5] │ │   [AI 3 / AI 6] │ │   [AI 4 / AI 7] │ │   [AI 4 / AI 7] │
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
C:\VictoriousAI\
├── AI-1-Backend/          # Worktree for AI 1 (backend/, database/, tests/)
├── AI-2-Customer/         # Worktree for AI 2 (User app/, storefront theme)
├── AI-3-Vendor/           # Worktree for AI 3 (Vendor app/, vendor-views)
├── AI-4-Operations/       # Worktree for AI 4 (Delivery Man App/, admin-views)
├── AI-5-ReviewCustomer/   # Worktree for AI 5 (Detached HEAD read-only)
├── AI-6-ReviewVendor/     # Worktree for AI 6 (Detached HEAD read-only)
├── AI-7-ReviewOperations/ # Worktree for AI 7 (Detached HEAD read-only)
├── AI-8-Coordinator/      # Worktree for AI 8 (.ai/tickets, releases, merge gate)
└── MAIN/                  # Pristine mirror of origin/main (Human verified)
```

Each worktree has its own dedicated `.env` configuration file with isolated database schemas and dedicated test ports to prevent port or database deadlocks.
