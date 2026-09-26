# Ticket Template (Appendix A)

Ticket ID:            VM-TEST-001
Title:                Seed migrations for sqlite :memory: test DB (fix DeliveryFlowLifecycleTest + ExampleTest)
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Human operator / 2026-09-26 (filed from live test run: 2 failed, 9 passed)
Size estimate:        small (test-harness only, zero product-code change expected)

Business requirement:
Problem:            `php artisan test` in `backend/vmarket-web` fails 2 feature suites with `SQLSTATE[HY000]: no such table: guest_users` on sqlite `:memory:`. Domain suites are green (invariants 23/23, security 21/21).
Expected behavior:  Full backend suite green; feature tests boot against a migrated test database.
Forbidden behavior: No product-code changes to make tests pass. No weakening of assertions. No production DB touched.
Affected systems:     backend test harness only
Tier / area:          B
Legacy debt IDs:      none
Affected APIs:        none
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (test-only ticket)
Affected database tables:     guest_users (+ any other tables missing under :memory:)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (test-only ticket)
Assigned AI:          BACKEND AI   (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-TEST-001 (from current `v1`)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md`)
Dependencies (tickets/features): none
Tests required:       `php artisan test` full suite green (target: 0 failed); `php -l` on touched files
Security requirements: tests run against isolated test DB only; no secrets in fixtures or logs
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] `DeliveryFlowLifecycleTest > complete delivery flow lifecycle` passes (evidence: artisan test log)
- [ ] `ExampleTest > basic test` passes (evidence: artisan test log)
- [ ] Invariant (23/23) + security (21/21) suites still green, no regressions (evidence: artisan test log)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes (no frontend stage: test-only ticket)
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (test-only ticket)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-26 06:35  Human  BACKLOG (created)  Filed from live run evidence: invariants 23/23 PASS, security 21/21 PASS, 2 feature suites FAIL on missing :memory: tables.
