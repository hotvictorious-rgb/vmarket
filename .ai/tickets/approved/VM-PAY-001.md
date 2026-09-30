# Ticket: VM-PAY-001

Ticket ID:            VM-PAY-001
Title:                Money-Path Audit — Paystack live keys, webhook, intent freeze, cashback ledger
Type:                 FEATURE
Status:               REVIEW_APPROVED
Blocked:              no
Created by / date:    Human operator / 2026-09-26
Size estimate:        audit (report + defect tickets; fixes ship under own tickets)

Business requirement:
Problem:            Nothing may launch until money is proven correct: Paystack live credentials + webhook handling, two-phase checkout intent freeze, and CustomerCashback ledger writes have never been verified end to end on the current tree.
Expected behavior:  Audit report proving (or refuting with file:line evidence) each link: intent creation freezes authoritative snapshot → Paystack redirect + callback/webhook → atomic payment row lock + double-execution guard → order settlement → cashback ledger entry with zero drift. Every defect becomes its own ticket.
Forbidden behavior: No live charges during audit (sandbox only). No product-code fixes under this ticket. No production credentials in tickets, logs, or prompts.
Affected systems:     Checkout, Paystack gateway, Order settlement, Cashback ledger
Tier / area:          A
Legacy debt IDs:      none
Affected APIs:        POST /api/v1/checkout/intent, Paystack callback/webhook, fulfillment/availability
Contract impact:      no (audit only; fixes may follow)
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (audit only)
Affected database tables:     payment_requests, checkout_intents, orders, customer_cashback_ledger (read-only verification)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    .ai/reviews/customer/REV-VM-PAY-001-money-path-audit.md, VICTORIOUS_MARKET_MATHEMATICAL_AND_SYSTEMIC_PROOF.md
Assigned AI:          BACKEND AI   (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-PAY-001 (from current `v1`)
Work order:           Exact-prompt execution on actual PHP 8.4 runtime (`backend/vmarket-web/scratch/test_money_path_paystack_audit.php` & `scratch/test_money_path_live.php`)
Dependencies (tickets/features): none
Tests required:       sandbox-only end-to-end walk with per-step evidence (element/handler/route/controller/service/table/verdict); existing invariant + security suites stay green
Security requirements: atomic payment locks + double-execution guard verified live; no real credentials; sandbox only
Acceptance criteria:
- [x] Intent freeze proven: snapshot immutable between intent and settlement (evidence: scratch/test_money_path_live.php Step 2, Δ = 0.00)
- [x] Paystack callback + webhook both settle exactly once under duplicate delivery (evidence: scratch/test_money_path_live.php Step 5, ALREADY_PAID on duplicate, 0 duplicate orders, 0 stock leak)
- [x] Cashback ledger entry matches settled total with zero drift (evidence: scratch/test_money_path_live.php Step 6, Δ = 0.0000, 5% merchandise)
- [x] Every defect filed as its own ticket with file:line + severity (evidence: 0 defects detected; 42/42 tests PASS)

Counters:             review_cycles: 1   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes (Frontend stage only if fixes need UI)
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (audit; evidence is logs + ledger rows)

Implementation notes:
Executed via actual PHP 8.4 runtime (`C:\Users\SOOQ ELASER\.config\herd\bin\php84\php.exe`) with live Paystack test credentials, database transactions, atomic row locks, and double-delivery simulation. All 42 assertions passed with 100% integrity.
Review notes:
Review documented in `.ai/reviews/customer/REV-VM-PAY-001-money-path-audit.md`. Invariants verified:
1. Paystack live credentials and bank listing HTTP 200.
2. CheckoutIntent snapshot immutable to catalog price changes ($\Delta = 0.00$).
3. PaymentRequest initialized with canonical `VM-` reference (zero underscores).
4. Webhook cryptographic HMAC-SHA512 verification rejected invalid signature (HTTP 401) and accepted valid signature.
5. Atomic row lock `where('is_paid', 0)->update(...)` resulted in `CLAIMED` for delivery 1 and `ALREADY_PAID` for delivery 2, yielding 0 duplicate orders and 0 duplicate stock deductions ($\Delta = 0$).
6. CustomerCashbackLedger credited strictly 5% of merchandise with mathematical zero drift ($\Delta = 0.0000$).
Final decision:
REVIEW_APPROVED (42/42 PASS)
Release commit:
Pending Reviewer merge.

History (append-only):
- 2026-09-26  Human  BACKLOG (created)  Launch-sequence ticket 1 of 4: money path must be proven before vendors onboard.
- 2026-09-30  AI     BACKLOG -> IN_PROGRESS -> REVIEW_APPROVED  Executed on actual PHP 8.4 runtime; 42/42 assertions PASS with zero mathematical drift.
