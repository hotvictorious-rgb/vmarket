# Ticket Template (Appendix A)

Ticket ID:            VM-CATL-001
Title:                Catalog discovery timeout — api/v1/products/latest hangs over HTTP while SQLite fast natively
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-27 (filed from :8001 observation: native queries fast — 66 products / 58 categories — but api/v1/products/latest times out over HTTP; suspected single-threaded serve self-call or N+1)
Size estimate:        small (diagnose + root-cause fix + proof; split if product-code + harness both change)

Business requirement:
Problem:            Catalog discovery is dead over HTTP: `GET api/v1/products/latest` times out on the :8001 serve path while the same SQLite database answers natively in milliseconds. If this code reaches prod, customers cannot browse → zero orders. `api/v1/config` responds, so boot + DB are intact; the hang is inside the latest-products path (suspected loopback self-HTTP against the single-threaded PHP server, or N+1 / missing index).
Expected behavior:  `GET api/v1/products/latest` responds within the staging budget on a clean `v1` checkout via `scripts/tests/run-backend-tests.ps1` + direct HTTP probe, with root cause named (self-call vs query plan) and eliminated — no self-HTTP in the request path.
Forbidden behavior: No self-HTTP loopback in the request path. No N+1 left in the path. No prod traffic for repro. No live charges. No weakening of assertions. No unrelated product changes. No manual-server evidence instead of harness logs.
Affected systems:     backend/core-api, customer/storefront-web, customer/mobile-app
Tier / area:          A (launch-blocking: Browse step of Browse -> Cart -> Checkout -> Paystack -> Order -> Tracking)
Legacy debt IDs:      none
Affected APIs:        GET api/v1/products/latest (repro), api/v1/config (control, known-good)
Contract impact:      no (fix must preserve v1 product-feed contract; any contract change needs its own ticket)
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (defect fix on the read path)
Affected database tables:     products, categories (read-only verification unless an index migration proves necessary — migration then ships inside this ticket with pretend + status proof)
Migration impact:     only if an index proves necessary (else no)
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (defect fix; report lives in review file)
Assigned AI:          BACKEND AI first, then FRONTEND AI only if Reviewer confirms a client-side handling change is required (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-CATL-001 (from current `v1` at dispatch time — after TEST-001/ASSETS-001 reroutes land)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md` at dispatch)
Dependencies (tickets/features): TEST-001 + ASSETS-001 reroutes land first (single-front rule, currency); blocks VM-PERF-001 (load ceiling meaningless while browse times out) and the PAY-001 audit walk (browse -> cart -> intent)
Tests required:       repro on clean `v1` via runner + HTTP probe with timings; query-count per request (N+1 check); existing invariant + security suites stay green; full `scripts/tests/run-all.ps1` JSON at full SHA before review
Security requirements: no secrets in logs; no prod data; no self-HTTP fetch of internal URLs from the request path (SSRF surface removed if present)
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] Root cause named with file:line (self-call vs N+1 vs index) (evidence: probe + query log + code refs)
- [ ] `GET api/v1/products/latest` within budget on clean v1 (evidence: timed probe log before/after)
- [ ] Queries-per-request bounded, no N+1 (evidence: query-count log)
- [ ] Zero self-HTTP in path (evidence: grep for Http::/curl/file_get_contents to local URLs in the path = empty)
- [ ] No regressions: invariants + security green, full run-all JSON at full SHA (evidence: runner + result JSON path)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI (only if Reviewer confirms needed) → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (API defect; evidence is timed probe + query logs)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-27  Reviewer AI  BACKLOG (filed)  Filed from :8001 observation (DB fast natively, latest-products timeout over HTTP). Queued behind TEST-001/ASSETS-001 reroutes per single-front + currency rule. No dispatch yet — work order pasted at dispatch after reroutes land.
