# Ticket Template (Appendix A)

Ticket ID:            VM-TEST-001
Title:                Seed migrations for sqlite :memory: test DB (fix DeliveryFlowLifecycleTest + ExampleTest)
Type:                 BUG
Status:               IN_PROGRESS
Blocked:              yes (Hard environment blocker: PHP 8.1.25 < required ^8.2; Composer platform_check fails)
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
BLOCKED before implementation. Environment cannot run the required test command.

1. Version floor not met.
   - Project requirement (`backend/vmarket-web/composer.json`): `"php": "^8.2"` and platform pin `8.2.0`.
   - Only PHP on host: `C:\xampp\php\php.exe` -> `PHP 8.1.25 (cli) (built: Oct 25 2023 08:06:57) (ZTS Visual C++ 2019 x64)`.
   - `php artisan test` aborts before any test is collected:
     `Composer detected issues in your platform: Your Composer dependencies require a PHP version ">= 8.2.0". You are running 8.1.25.` (thrown in vendor/composer/platform_check.php).
   - No PHP >= 8.2 present anywhere on the host (searched C:\, D:\, Program Files, LOCALAPPDATA\Programs, laragon, tools). XAMPP is the only install and is 8.1.
   - I will NOT bypass the platform check (that would fake test evidence) and `composer.json` is outside my allowed write paths, so this is not fixable from the worker seat.

2. Root cause of the ticket symptom (confirmed statically, fix ready once PHP 8.2 exists).
   - `guest_users` migration DOES exist: `database/migrations/2023_08_12_215346_create_guest_users_table.php` (321 migrations total). The table is absent at runtime purely because migrations were never applied to the sqlite `:memory:` test DB.
   - Structural defect: `tests/Feature/ExampleTest.php` uses the `RefreshDatabase` trait, but `tests/Feature/DeliveryFlowLifecycleTest.php` does NOT use any refresh/migration trait - it assumes a pre-existing schema and even mutates schema in-test via `Schema::hasColumn('orders', ...)` / `Schema::table('orders', ...)` at lines 37-66. With an empty in-memory DB it dies on the first missing table.
   - Intended fix (test-harness only, zero product-code change): have the feature tests run against a migrated schema (e.g. `RefreshDatabase` on the lifecycle test, or migrate the in-memory DB in `tests/TestCase.php` setUp / `tests/CreatesApplication.php`) so all 321 migrations apply before assertions. `phpunit.xml` already pins `DB_CONNECTION=sqlite` + `DB_DATABASE=:memory:`.
   - Note: `DeliveryFlowLifecycleTest` wraps its body in `DB::beginTransaction()` / `DB::rollBack()`, which is incompatible with `RefreshDatabase`'s transaction wrapping; that interaction must be reconciled in the fix (likely `DatabaseMigrations` instead of `RefreshDatabase`, or migrate once in base TestCase).

3. What is NOT done: no files modified, no commits other than this ticket note, no migration edits, no app/** changes, no weakened assertions. Working tree otherwise clean.

Resolution requested from REVIEWER AI: provision PHP >= 8.2 (or a PHP 8.2 runtime for the test runner) on the host, then re-dispatch; I will apply the harness fix above and return the full `php artisan test` log as evidence.

Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-26 06:35  Human  BACKLOG (created)  Filed from live run evidence: invariants 23/23 PASS, security 21/21 PASS, 2 feature suites FAIL on missing :memory: tables.
- 2026-09-26 12:05  BACKEND AI  IN_PROGRESS + BLOCKED  Created branch `backend/VM-TEST-001` from `v1` @ c55a74ef. Cannot execute: host PHP is 8.1.25, project requires ^8.2, `php artisan test` aborts in vendor/composer/platform_check.php before collecting tests. No PHP >= 8.2 found on host. Did not bypass platform check (would fake evidence); composer.json is outside Backend AI write allowlist. Root cause of original symptom identified statically: `guest_users` migration exists but is never applied to the in-memory test DB; `DeliveryFlowLifecycleTest` uses no refresh/migration trait and mutates schema in-test, `ExampleTest` uses RefreshDatabase. Fix is test-harness-only and ready to apply once PHP >= 8.2 is provisioned. Requesting: REVIEWER AI provision PHP 8.2+ runtime, then re-dispatch. No product code touched.
