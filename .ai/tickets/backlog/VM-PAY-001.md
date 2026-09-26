# Ticket Template (Appendix A)

Ticket ID:            VM-PAY-001
Title:                Money-Path Audit — Paystack live keys, webhook, intent freeze, cashback ledger
Type:                 FEATURE
Status:               BACKLOG
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
Documents updated:    none - justify (audit only; report lives in review file)
Assigned AI:          BACKEND AI   (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-PAY-001 (from current `v1`)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md`)
Dependencies (tickets/features): none
Tests required:       sandbox-only end-to-end walk with per-step evidence (element/handler/route/controller/service/table/verdict); existing invariant + security suites stay green
Security requirements: atomic payment locks + double-execution guard verified live; no real credentials; sandbox only
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] Intent freeze proven: snapshot immutable between intent and settlement (evidence: per-step audit rows)
- [ ] Paystack callback + webhook both settle exactly once under duplicate delivery (evidence: double-delivery test log)
- [ ] Cashback ledger entry matches settled total with zero drift (evidence: ledger row vs order total)
- [ ] Every defect filed as its own ticket with file:line + severity (evidence: ticket IDs)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes (Frontend stage only if fixes need UI)
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (audit; evidence is logs + ledger rows)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-26  Human  BACKLOG (created)  Launch-sequence ticket 1 of 4: money path must be proven before vendors onboard.
