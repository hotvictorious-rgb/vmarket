# Ticket Template (Appendix A)

Ticket ID:            VM-PERF-001
Title:                Baseline Load Ceiling — staging checkout/payment/order under concurrent users
Type:                 FEATURE
Status:               BACKLOG
Blocked:              no
Created by / date:    Human operator / 2026-09-26
Size estimate:        medium (harness + run + report; no product-code changes unless the run exposes a defect, which gets its own ticket)

Business requirement:
Problem:            Nobody knows what load the system carries. Production is a cPanel shared host; the pilot may fit, but "any number of vendors/users" is currently hope, not a number. The gate already requires load evidence before releases touching checkout/payment/order — none exists.
Expected behavior:  A measured ceiling on staging: N concurrent users walking browse → cart → checkout-intent (sandbox payment, no live charges) while recording p50/p95/p99 response times, error rate, DB query counts per request (N+1 check), and server resource headroom. One number per endpoint: "carries X concurrent users before p95 exceeds Y ms or errors exceed 1%."
Forbidden behavior: No load against production. No live charges (sandbox only). No tuning or product-code fixes under this ticket — measure first; defects get own tickets.
Affected systems:     Checkout intent, Paystack sandbox callback path, order creation, cart/list endpoints
Tier / area:          A (gate-required evidence for money flows)
Legacy debt IDs:      none
Affected APIs:        POST /api/v1/checkout/intent, cart list/update, order create, Paystack sandbox webhook
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (staging measurement only)
Affected database tables:     carts, checkout_intents, orders, payment_requests (staging data only)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none (if a load tool needs installing, e.g. k6, Backend proposes and Reviewer approves first)
Documents updated:    none - justify (report lives in review file)
Assigned AI:          BACKEND AI   (dispatched by REVIEWER AI with an exact-prompt work order; owns staging server + DB observability)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-PERF-001 (from current `v1`)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md`)
Dependencies (tickets/features): VM-PAY-001 (run after the money audit, against the audited flow)
Tests required:       staged load runs at 3 levels (e.g. 10 / 50 / 200 concurrent users, or host-appropriate steps); per-level p50/p95/p99 + errors + queries-per-request; existing suites stay green
Security requirements: staging + sandbox only; no production data; no credentials in scripts, logs, or tickets
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] Ceiling number recorded per endpoint (concurrency at p95 breach or 1% errors) (evidence: run logs + summary table)
- [ ] N+1/query-count findings listed, each as its own defect ticket if over budget (evidence: ticket IDs)
- [ ] Bottleneck named (DB, PHP workers, bandwidth, host limits) with the next scaling step priced (evidence: resource graphs + recommendation)
- [ ] No product-code changes in this ticket (evidence: `git diff --stat` shows harness/scripts only)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes (no frontend stage unless defects need UI)
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (evidence is run logs + tables)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-26  Human  BACKLOG (created)  Measure the ceiling; runs after VM-PAY-001 against the audited money flow.
- 2026-09-27  Reviewer AI  BACKLOG (triaged, synced v1@4f2c18c3)  Sequence confirmed: VM-PAY-001 (Tier A money-path audit, no deps) first, VM-PERF-001 second — load runs against the audited flow (intent freeze → atomic pay lock → settlement → cashback Δ=0.00), never against unaudited money. PERF-001 starts only after PAY-001 RELEASED, and after the in-flight TEST-001/ASSETS-001 reroutes land (no second front on Backend). Both tickets are measure/audit-only; any defect found ships under its own ticket.
