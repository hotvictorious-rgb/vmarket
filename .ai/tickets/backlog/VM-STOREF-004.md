# Ticket Template (Appendix A)

Ticket ID:            VM-STOREF-004
Title:                Payment dead-ends: empty state, CSRF meta mismatch, missing due-bill/offline routes, legacy gateways
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-27 (triaged from scratch/storefront-ticket-drafts/VM-STOREF-004-DRAFT.md deep-scan batch; filed to BACKLOG, no dispatch — queued behind reroutes, triage position 4/10)
Size estimate:        medium (blade + payment.js + route decision)

Business requirement:
Problem:            Paystack-only V1 checkout must always offer a path. payment.blade:34 dead-end when gateways list empty (offline partial exists file_names:65 but not included); payment.js:94 uses meta[csrf-token] vs _token everywhere else → undefined header; due-bill Pay Now targets missing customer.customer-order-edit-pay-amount; pay-offline-method-list missing (guarded inert); legacy paytm/marcedo-pogo blades remain vs Paystack-only routes:375-383.
Expected behavior:  Empty state names Paystack fallback + back nav; CSRF meta unified to _token; due-bill route confirmed or button removed with decision; offline route kept or span removed; legacy gateway blades removed if unauthorized (one-implementation rule).
Forbidden behavior: No new gateways; no COD reintroduction; no client amount math.
Affected systems:     customer/storefront-web, backend/core-api
Tier / area:          A (payment path)
Legacy debt IDs:      none
Affected APIs:        POST customer.web-payment-request, GET web-payment/success/fail, paystack pay/cancel/callback/webhook (existing)
Contract impact:      no (unless offline route added → then yes, versioned under its own decision)
Client compatibility impact:  no
Feature flag / kill switch:   none - justify
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (or decision record if gateways removed)
Assigned AI:          BACKEND AI then FRONTEND AI
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-STOREF-004 then frontend/VM-STOREF-004 (from v1 after reroutes land)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md` at dispatch)
Dependencies (tickets/features): reroutes merged; coordinate with VM-PAY-001 audit findings (no duplicate money fixes — PAY-001 informs, this ticket fixes storefront dead-ends only)
Tests required:       gateways-empty render; header value defined; due-bill path 200 or button absent with decision; legacy files absent or justified
Security requirements: CSRF header valid; amounts server-rendered only; webhook HMAC unchanged
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] no dead-end payment page (evidence: render with gateways off)
- [ ] CSRF header sends token (evidence: request headers)
- [ ] missing-route buttons resolved (evidence: grep zero + click 200)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          payment empty + headers

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-27  Reviewer AI  BACKLOG (filed)  Triaged from scratch draft (storefront §6 + payment.js audit). PAY-001 coordination noted to avoid duplicate money fixes. Queued behind reroutes; triage position 4/10.
