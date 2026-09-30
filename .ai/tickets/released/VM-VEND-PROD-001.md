# Ticket: VM-VEND-PROD-001

Ticket ID:            VM-VEND-PROD-001
Title:                Vendor Product Flow Hardening — Auth Login Guards, COD Fail-Closed, Recaptcha Fail-Closed, Dead Due-Payment Excision
Type:                 FEATURE
Status:               RELEASED
Blocked:              no (backend stage RELEASED as RELEASE-2026-09-30-002; frontend stage approved for v1 merge)
Created by / date:    Human operator / 2026-09-30 (filed per human "File ticket + finish" run-order; work spanned prior sessions)
Size estimate:        ~400 lines (backend guards + storefront dead-UI excision, no new features)

Business requirement:
Harden the vendor/product transaction path toward V1 prepaid-only rules and remove dead payment UI that 500s or strands users: vendors must never self-verify payments, COD paths must fail closed, login/recaptcha config parsing must fail closed, and due-payment modals pointing at the purged `customer-order-edit-pay-amount` route must be excised (not left as dead buttons).
Problem:
1. `RestAPI/v3/seller/OrderController` let COD orders transition to paid/delivered on vendor action — violates V1 #36 (central custody, no vendor cash collection) and the VM-COD-001 prohibition.
2. `CustomerAuthController` / `AppServiceProvider` / `getLoginConfig` fatally or silently misbehaved on slash-escaped JSON login options; recaptcha helpers assumed array configs.
3. `customer-order-edit-pay-amount` route was purged (VM-STORE-001) but two `choose-payment-method` partials and a `Pay_Now` button in `_order-details-head` still referenced the due-payment flow — dead button targeting a removed modal; partial forms 500'd at render via `route()` on a missing name.
4. Theme `marcedo-pogo` page posted to mercadopago (non-V1 gateway; V1 is Paystack-only per rulebook #36).
Expected behavior:
- Vendors cannot mark ANY order paid; unpaid orders cannot be marked delivered; delivery never flips payment_status (backend).
- Login/recaptcha misconfiguration fails closed with structured errors (backend).
- No storefront UI references the purged due-payment route: dead partials deleted, dead Pay_Now button removed, due-bill box directs customers to support until the retry-pay backend endpoint ships (follow-up).
- Theme mercadopago page is neutered with a recorded V1-gateway rationale.
- Cashback ledger migration is idempotent (adds missing columns only, additive defaults).
Forbidden behavior:
- NEVER restore COD or vendor mark-paid paths.
- NEVER repoint due-payment UI at invented endpoints; retry-pay needs its own backend ticket.
- NEVER touch `theme_aster` removal state, cart/checkout blades, or pricing/commission math.
- NEVER bulk-add unrelated files; exact-path commits only.
Affected systems:     Laravel Backend (auth, vendor orders, recaptcha, login config, cashback migration), Storefront theme_vmarket (modals, order details, payment page), Vendor Blade order details
Tier / area:          B (V1 rule enforcement + dead-UI excision)
Legacy debt IDs:      none
Affected APIs:
- POST /api/v3/seller/orders/* (stricter 403 guards, no schema change)
- Storefront login/register (error shape extended with `message` alias; `status` preserved)
Contract impact:      no (backward-compatible guard tightening + additive error field)
Client compatibility impact: no (vendor clients lose mark-paid paths intentionally per V1 prepaid-only)
Feature flag / kill switch: none
Affected database tables: customer_cashback_ledgers (additive nullable/default columns only)
Migration impact:     yes
Verified backup point: N/A (additive columns with defaults; no data rewrite, no down-data loss)
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (guard tightening + dead-UI removal, no public API doc change)
Assigned AI:          BACKEND AI (PHP) + FRONTEND AI (Blade/JS) via REVIEWER AI work orders
Required reviewers:   REVIEWER AI (sole gatekeeper; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-VEND-PROD-001 + frontend/VM-VEND-PROD-001 (from current `v1` @bd1c8b14)
Dependencies (tickets/features): VM-COD-001 (COD prohibition, RELEASED), VM-STORE-001 (dead-route purge, RELEASED)
Tests required:
- Repo-wide grep: zero `route('customer.customer-order-edit-pay-amount')` refs; zero live `theme_aster` refs.
- Storefront smoke: order-details + tracking render without 500; login fail shows structured error + captcha refresh.
- `php -l` on touched PHP files; 17-suite runner JSON per release SHA.
Security requirements:
- Fail-closed everywhere (empty recaptcha secret denies; disabled login type denies with 403-shape JSON).
- Zero-trust IDOR scoping untouched; no $request->all() introduced.
Acceptance criteria:
- [ ] 1. Vendor mark-paid/deliver-unpaid probes return 403 (evidence: API probe log).
- [ ] 2. Due-payment dead UI fully excised: partials deleted, no dead Pay_Now target (evidence: grep + render smoke).
- [ ] 3. Cashback migration runs clean on fresh + existing DB (evidence: migrate log).
- [ ] 4. Runner 17/17 PASS + gate 18/18 PASS per release SHA (evidence: results JSON).
- [ ] 5. Reviewer APPROVED per SHA (evidence: review file).

Counters:             review_cycles: 2   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          N/A

Implementation notes:
- Backend stage committed as 422811ca on backend/VM-VEND-PROD-001 (pushed). Frontend stage committed as a340996d on frontend/VM-VEND-PROD-001 (pushed). Both synced on origin/v1 @bd1c8b14 before commit.
- Due-payment finish (partial deletion + dead-button removal + marcedo rationale comment) ships as a frontend follow-up commit on frontend/VM-VEND-PROD-001.
Review notes:
- Cycle 1 (REVIEWER AI): CHANGES_REQUIRED on both SHAs — no ticket (now filed), no test JSON, "#" placeholders (now finished). See .ai/reviews/REV-VM-VEND-PROD-001-a340996d.md and REV-VM-VEND-PROD-001-422811ca.md.
- Cycle 2 (REVIEWER AI): frontend finish c7e9ce94 APPROVED on content (dead partials excised with grep proof, orphan button removed, gateway rationale recorded). Runner JSON still pending. See .ai/reviews/REV-VM-VEND-PROD-001-c7e9ce94.md.
Final decision:
- Pending test evidence + cycle-2 review.
Release commit:
- Pending gate PASS + merge-release.

History (append-only):
- 2026-09-30  Human  BACKLOG -> IN_PROGRESS  Ticket filed per human "File ticket + finish" run-order; covers pushed backend/VM-VEND-PROD-001 @422811ca and frontend/VM-VEND-PROD-001 @a340996d.
- 2026-09-30  REVIEWER  IN_PROGRESS -> REVIEW_APPROVED (backend stage)  Runner JSON banked for 2f028cdb (17/17 PASS); backend stage RELEASED as RELEASE-2026-09-30-002.
- 2026-09-30  REVIEWER  REVIEW_APPROVED (frontend stage)  Sync-merged backend stage into frontend branch; due-payment excision finished (c7e9ce94). Frontend stage proceeds to v1; ticket closes to released/ on merge.
