# Admin Panel API Request Tracker (Admin-Owned)

> **Scope:** Admin Control Tower (`backend/vmarket-web/resources/views/admin-views/`, admin routes/controllers/policies).
> **Control tower, not a second engine:** backend owns all domain logic, fees, state machines, and audit.
> **Canonical refs:** `.agents/rules/VMARKET_ADMIN_PANEL_SPEC.md` (70 sections) · `.agents/sync/INBOX_ADMIN.md` · `.agents/rules/ADMIN_PANEL_ALIGNMENT.md`.

---

## 1. Process (mandatory for all AIs working on Admin panel)

1. **Need a backend capability?** Add an `AAPI-00X` row below, then file the `REQ-ADMIN-YYYYMMDD-###` ticket in `.agents/sync/INBOX_ADMIN.md` — do NOT implement business rules in Blade/controllers step-wise.
2. **Fill:** date, needed-by, request shape/policies, why backend must own it.
3. **Notify backend AI** with the ticket ID. Do NOT duplicate an existing engine.
4. **Adapt, don't revert:** on `git pull`, re-check against new contracts; never restore backend files.
5. **Tick the boxes:** `[ ] R` Requested · `[ ] B` Backend done (link commit) · `[ ] I` Integrated + verified.
6. **Commit rule:** stage ONLY your files (`git add <exact-path> AI_CHANGELOG.md`). Never `git add .`; leave foreign actors' files dirty and untouched. `[AI]` tag.

### Status legend — `R` Requested · `B` Backend done · `I` Integrated + verified

---

## 2. Open requests (admin panel)

### AAPI-001 — Payment exception triage queue (paid order recovery)
- [x] R · [ ] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** paid-order recovery center — orphan `payment_requests` (initialized_no_order, callback_failed, webhook_mismatch, amount/reference mismatch)
- **Ticket:** `REQ-ADMIN-20260924-001` (HIGH) · **Routes:** `GET/POST /admin/payment-exceptions` + `{id}/retry|create-order|mark-resolved|escalate`
- **Backend commit:** _pending_ · **Integrated in:** _pending_

### AAPI-002 — Delivery exception queue
- [x] R · [ ] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** dispatcher exception handling — customer unavailable, wrong address, damaged, lane interruption, failed attempt
- **Ticket:** `REQ-ADMIN-20260924-002` (HIGH) · **Routes:** `GET/POST /admin/delivery-exceptions` + `{id}/assign|reattempt|return|resolve`
- **Backend commit:** _pending_ · **Integrated in:** _pending_

### AAPI-003 — Stock adjustment workflow with mandatory audit
- [x] R · [ ] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** inventory oversight — `ProductStock` adjust with before/after audit + required reason
- **Ticket:** `REQ-ADMIN-20260924-003` (HIGH) · **Routes:** `GET /admin/stock/adjustments` + `POST /admin/stock/adjustments/store`
- **Backend commit:** _pending_ · **Integrated in:** _pending_

### AAPI-004 — Command center dashboard metrics
- [x] R · [ ] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** operational home — 13 server-computed clickable metrics (currently only 3)
- **Ticket:** `REQ-ADMIN-20260924-004` (MEDIUM) · **Routes:** extend `DashboardController::index()` + `dashboard.blade.php`
- **Backend commit:** _pending_ · **Integrated in:** _pending_

### AAPI-005 — Pickup inspection accept/reject + OTP verification oversight
- [x] R · [ ] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** pickup oversight — act on `pending_inspection` reservations, view collection OTP verification
- **Ticket:** `REQ-ADMIN-20260924-005` (MEDIUM) · **Routes:** `POST /admin/orders/pickup/{id}/inspect` + `GET /admin/orders/pickup/{id}/otp-verification`
- **Backend commit:** _pending_ · **Integrated in:** _pending_

### AAPI-006 — Admin auth hardening
- [x] R · [ ] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** defended admin auth — login audit, brute-force lockout (5/min, 15-min lock), optional MFA for Super Admin
- **Ticket:** `REQ-ADMIN-20260924-006` (MEDIUM) · **Routes:** `LoginController` + rate limiter + `Admin` lockout fields
- **Backend commit:** _pending_ · **Integrated in:** _pending**

---

## 3. Residual open questions (need product decision, not backend tickets)

- **Cashback manual adjustment:** spec §34 allows adjust-with-audit; current `CustomerCashbackAdminController` is deliberately read-only (Δ = 0.00 invariant). Decide: keep invariant or enable controlled `POST /admin/cashback/adjust`.
- **Merchant suspension scope:** full suspension (block new purchases, preserve orders, show obligations) vs current `marketplace_status` toggle.
- **Refund receipt-first evidence:** optional upload on approval vs current auto-initiate Paystack refund.
- **Global admin search (§58)** and **bulk-operation safety (§59)**: planned, defer past controlled V1.