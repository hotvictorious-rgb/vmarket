# Ticket Template (Appendix A)

Ticket ID:            VM-TEST-002
Title:                Harden 3 procedural test suites into real PHPUnit TestCases + paired runner update
Type:                 FEATURE
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-28 (coverage-illusion finding verified: procedural suites undiscoverable by PHPUnit)
Size estimate:        medium (3 rewrites + runner invocation update + gate proof; split per-file if over 400 lines)

Business requirement:
Problem:            `tests/Unit/MarketplaceListingFreshnessTest.php`, `PaymentFulfillmentBoundarySecurityTest.php`, and `ProductFeedExportIsolationTest.php` are procedural scripts (`Mock*` classes + `*Suite::runAll()` at file load, zero `TestCase` subclasses). PHPUnit emits "class cannot be found" warnings and tracks 0 cases for them — the repo's "21 Passed / Δ=0.00" output is custom `echo` text, not framework-verified results. Mock simulators duplicate production logic instead of invoking it. (`DeliveryLaneRoutingInvariantTest.php` is already a real TestCase — untouched.)
Expected behavior:  All three become `PHPUnit\Framework\TestCase` (or DB-backed `DumpSchemaTestCase` + testing sqlite where production code needs it) subclasses invoking REAL production code (`Product::isMarketplaceEligible/Purchasable`, `ProductFeedExportController`, lane/fee guards, `OrderController` payment-authority guards), with `assert*()` and zero simulators. `scripts/tests/run-all.ps1` invocations updated in the SAME change (direct `php file.php` → `phpunit` binary), because pure TestCase files no-op under direct execution and would otherwise false-PASS the gate. Gate green proven before AND after.
Forbidden behavior: No mock simulators carried over (real code or justified DB-seeded fixtures only). No runner/file change landed separately (atomic). No weakening of assertions to force green. No Control Zone edits without human sign-off recorded in History.
Affected systems:     test harness, release gate evidence
Tier / area:          A (gate integrity)
Legacy debt IDs:      none
Affected APIs:        none
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (test-only change)
Affected database tables:     testing sqlite only (DB-backed cases)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (report lives in review file)
Assigned AI:          BACKEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-TEST-002 (from current `v1` AFTER VM-ORD-002 and VM-ORD-003 release; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
Work order:
- **Goal:** Convert the 3 procedural suites to real framework-discovered TestCases calling real production code, with the paired runner update, keeping the gate green throughout.
- **Branch:** `backend/VM-TEST-002` from post-ORD-003 `v1`.
- **Allowed files:** the 3 test files + `scripts/tests/run-all.ps1` (invocation lines ONLY — Control Zone change, human sign-off recorded here before merge); own ticket notes.
- **FORBIDDEN:** production code (any red test becomes its own defect ticket, never a weakened assertion); all other Control Zone; Flutter/Blade/assets; results/reviews/changelog.
- **Acceptance:** (1) `vendor/bin/phpunit -c phpunit.xml` discovers all suites with 0 "class cannot be found" warnings; (2) per-file before/after case counts + assertion counts recorded; (3) `run-all.ps1` full HEAD green with the new invocations (JSON uncommitted, report counts); (4) no `Mock*` simulator classes remain in the 3 files.
- **Tests + DONE:** run-all JSON at full HEAD; push; BACKEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; testing sqlite + sandbox only.
Dependencies (tickets/features): VM-ORD-002, VM-ORD-003 (land first so evidence baselines don't shift mid-ticket)
Tests required:       phpunit discovery proof (0 warnings); run-all full HEAD green; before/after counts
Security requirements: no secrets; DB-backed cases use testing sqlite only
Acceptance criteria:
- [ ] Zero PHPUnit discovery warnings across the suite
- [ ] All 3 files invoke real production code with assert*()
- [ ] run-all green with updated invocations at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (test-only change)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-28  Reviewer AI  BACKLOG (filed)  Procedural-suite structure verified in-tree (`*Suite::runAll()` at load, Mock* classes). Runner coupling verified (`run-all.ps1` uses bare `php file.php` for 2 of 3). Sequenced after ORD-002/003. Human sign-off for the Control Zone edit: GRANTED with this run-order (recorded).
