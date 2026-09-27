# Ticket Template (Appendix A)

Ticket ID:            VM-TEST-001
Title:                Seed migrations for sqlite :memory: test DB (fix DeliveryFlowLifecycleTest + ExampleTest)
Type:                 BUG
Status:               REVIEW_APPROVED
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
Work order:
  ## WORK ORDER from REVIEWER AI — VM-TEST-001 — BACKEND
  - **Goal (one sentence):** Make `php artisan test` green in `backend/vmarket-web` by seeding/migrating sqlite `:memory:` test DB so `guest_users` (+ any other missing tables) exist, with zero product-code changes.
  - **Branch:** `backend/VM-TEST-001` (create from `v1` at `c55a74ef9ac4a5617bd1df57681cdb4eb8ab8b6a`; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
  - **Allowed files (exact paths/scopes):** `backend/vmarket-web/tests/**/*.php`, `backend/vmarket-web/phpunit.xml`, `backend/vmarket-web/database/**/*.php` (new migration/seed files only — NEVER edit old migrations), `backend/vmarket-web/config/database.php` (test DB connection only); own notes in `.ai/tickets/in-progress/VM-TEST-001.md` copy if needed
  - **FORBIDDEN paths (do not touch):** `backend/vmarket-web/app/**` product logic, all Flutter (`User app/`, `Vendor app/`, `Delivery Man App/`), all Blade (`backend/vmarket-web/resources/views/**`), theme assets (`backend/vmarket-web/public/assets/**`), Control Zone (`.ai/*.md` rules, `.ai/agents/*`, `.ai/templates/*`, `.ai/schemas/*`, `scripts/**`), `.ai/status/results/**`, `.ai/reviews/**`, `AI_CHANGELOG.md`
  - **Contract / inputs you consume:** Ticket VM-TEST-001 Business requirement + Acceptance criteria; live-run evidence (invariants 23/23 PASS, security 21/21 PASS, 2 feature suites FAIL on `no such table: guest_users`); `.ai/DATABASE_RULES.md`, `.ai/TESTING_RULES.md`
  - **Acceptance criteria (each needs evidence):**
    1. [ ] `DeliveryFlowLifecycleTest > complete delivery flow lifecycle` passes (evidence: full `php artisan test` log pasted in ticket notes)
    2. [ ] `ExampleTest > basic test` passes (evidence: same log)
    3. [ ] Invariant (23/23) + security (21/21) suites still green, full suite 0 failed (evidence: same log)
    4. [ ] Zero product-code changes; no weakened assertions; no production DB touched (evidence: `git diff --stat` + `git status -sb`)
  - **Tests to run + evidence to attach:** REQUIRED invocation: `scripts/tests/run-backend-tests.ps1` from repo root (it puts Herd `php84` 8.4.25 on PATH; satisfies `^8.2` — do NOT hand-roll env detection, do NOT use XAMPP php 8.1). `php -l` on every touched file; full suite via the runner (attach complete log); `git diff --stat` showing only test-harness files
  - **DONE definition:** commits pushed to `backend/VM-TEST-001` + ticket notes filled with contract (none — test-only), schemas (none), migration notes + status word `BACKEND_DONE` reported in ticket History.
  - **Rules:** exact-path `git add` only (never bulk / `-A` / `-a`); leave other AIs' files dirty; change notes in ticket notes (NEVER edit `AI_CHANGELOG.md` — Reviewer writes the one entry at release); blocked → set `Blocked: yes (<reason>)` + History entry to REVIEWER AI, never to the human, never sideways.
  - **Push rule:** NEVER merge, NEVER push to `v1`/`main`. Only Reviewer AI merges + pushes after APPROVED + gate PASS via `scripts/release/merge-release`.
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
- 2026-09-26  Reviewer AI  BACKLOG -> IN_PROGRESS  Dispatched to BACKEND AI with exact-prompt work order; branch backend/VM-TEST-001 from v1@c55a74ef; no frontend stage (test-only).
- 2026-09-26  Backend AI  IN_PROGRESS (blocked)  Reported hard env blocker: host PHP 8.1.25 < required ^8.2, Composer platform check fails, tests cannot run.
- 2026-09-26  Reviewer AI  IN_PROGRESS -> BLOCKED  Verified: composer.json requires ^8.2; only PHP on this host is C:\xampp\php\php.exe 8.1.25; php not on PATH. Drafted DECISION-VM-TEST-001-PHP82 for human env resolution. Work halted on backend/VM-TEST-001; no code changes accepted until env fixed.
- 2026-09-26  Human  BLOCKED (env resolved, no escalation)  PHP 8.4.25 at C:\Users\SOOQEL~1\.config\herd\bin\php84 satisfies ^8.2; runner scripts/tests/run-backend-tests.ps1 puts it on PATH. Reviewer verified binary + runner. DECISION draft discarded unfiled.
- 2026-09-26  Reviewer AI  BLOCKED -> IN_PROGRESS  Set Blocked: no; work order amended to require scripts/tests/run-backend-tests.ps1; resumed from top on backend/VM-TEST-001. Process notes logged: (1) name runner script in work orders, (2) verify with repo tooling before escalation.
- 2026-09-26  Reviewer AI  IN_PROGRESS (scope guard)  Local backend/VM-TEST-001 contains 6a8fb3b3 (InstallController + UpdateController + config/filesystems.php themes link). Correct direction for unstyled, WRONG ticket: VM-TEST-001 work order forbids app/** + filesystems.php and requires zero product-code changes. Verdict: 6a8fb3b3 cannot land under VM-TEST-001. Backend must reset backend/VM-TEST-001 to test-only files (9419203a) and re-apply themes fix cleanly on new branch backend/VM-ASSETS-001 per VM-ASSETS-001 work order (renamed; VM-THEME-001 taken on v1).
- 2026-09-26  Reviewer AI  IN_PROGRESS (collected + ruled)  Collected via fetch + `git show backend/VM-TEST-001:<path>` (no pasted content). backend/VM-TEST-001 = 9419203a (harness, in scope) + 6a8fb3b3 (themes product fix, out of scope). RULING: SPLIT — harness stays in VM-TEST-001; themes fix moves to VM-ASSETS-001 (VM-THEME-001 ID taken on v1 by Aster removal READY). Reviews filed: `.ai/reviews/operations/REV-VM-TEST-001-9419203a382d7ac633191a9068efbff756fd7cf4-cycle1.md` (CHANGES_REQUIRED: runner log + BACKEND_DONE + strip product files + rebase onto v1@3976e8aa) and `.ai/reviews/operations/REV-VM-ASSETS-001-6a8fb3b3784570cc055ce32bbdf69ed5e4f07642-cycle1.md` (CHANGES_REQUIRED: re-apply on fresh backend/VM-ASSETS-001 + php -l/grep/fresh-link/runner proof). STATUS.md untouched (human regenerates).
- 2026-09-26  Reviewer AI  IN_PROGRESS (amended)  Human reports origin/backend/VM-TEST-001 now at 6a8fb3b3 (pushed after cycle-1 fetch). Verified via fetch + rev-parse: origin and local both 6a8fb3b3784570cc055ce32bbdf69ed5e4f07642. Cycle-1 notes corrected (were: local-only/unpushed). Fix orders stand, now as revert-then-split: (1) strip 6a8fb3b3 from backend/VM-TEST-001 back to harness-only + rebase onto v1@3976e8aa; (2) carry the styling fix on fresh backend/VM-ASSETS-001 from v1.
- 2026-09-26  Reviewer AI  IN_PROGRESS (dispatched)  Fix order (1): Backend reverts/strips 6a8fb3b3 product files from backend/VM-TEST-001 (InstallController, UpdateController, filesystems.php) back to harness-only 9419203a, rebases onto current v1@3976e8aa, attaches runner log + php -l, pushes backend/VM-TEST-001, declares BACKEND_DONE here. Exact-path add only; NEVER merge, NEVER touch v1/main.
- 2026-09-27  Reviewer AI  BACKEND_DONE -> APPROVED (cycle-2)  Collected origin/backend/VM-TEST-001@c0b68002 (fetch + rev-parse verified; human-pushed with --force-with-lease). Harness-only, rebased onto v1@3976e8aa, 11/11 green / 58 assertions / 0 failures, invariants 23/23 + security 21/21, Δ = 0.00; warnings pre-existing on v1, left untouched per scope. Independent php -l clean + diff-scope check by Reviewer. Review: `.ai/reviews/operations/REV-VM-TEST-001-c0b680026a432709ef72d870ac6316a498146894-cycle2.md`. Next: gate + merge via release script (folder/state parity reconciled at merge).
- 2026-09-27  Reviewer AI  APPROVED (gate PASS, merge HALTED on currency)  Gate 18/18 PASS at c0b68002 after Check-5 fix sync (v1@6c01b111) + schema-v2 JSON generated via run-all.ps1 at exact SHA in detached worktree (7/7 live PASS, nothing new). Evidence carried at `.ai/status/results/VM-TEST-001/c0b68002...json` (+ logs). merge-release currency check fails: v1@6c01b111 is not an ancestor of c0b68002 (branch forked at 3976e8aa). NO merge executed. Reroute to Backend: rebase backend/VM-TEST-001 onto current v1, regenerate evidence, re-push, re-declare DONE. New SHA will need cycle-3 review + fresh JSON + gate before ritual.
- 2026-09-27  Reviewer AI  IN_PROGRESS (reroute dispatched)  REROUTE ORDER (1) — BACKEND AI, copy-paste ready, zero follow-ups: fetch Reviewer's branch for orders, then: rebase `backend/VM-TEST-001` onto current `v1`, run `run-all.ps1 -Ticket VM-TEST-001 -CommitSha <new>` in-branch, push `backend/VM-TEST-001`, declare BACKEND_DONE here with the new SHA. Result JSONs must be produced on the worker branch — Reviewer cannot commit them (hook refusal logged). Exact-path add only; NEVER merge, NEVER touch v1/main. Lesson logged: currency halt is ordinary churn (v1 moved after fork), not fault; refusal log stands alongside approvals as audit value.
- 2026-09-27  Reviewer AI  IN_PROGRESS (v1 moved — fix order)  VM-ASSETS-001 RELEASED onto v1@5259ed03 (RELEASE-2026-09-27-001). origin/backend/VM-TEST-001@4872f788 now stale on two counts: (1) base ccb5599e/4f2c18c3-era, not current v1; (2) carries stale `.ai/status/results/VM-TEST-001/a5805de0.json` + duplicate `.ai/tickets/backlog/VM-TEST-001.md` (authoritative ticket is this in-progress copy). Fix order dispatched below.
- 2026-09-27  Reviewer AI  IN_PROGRESS (human-authorized reroute)  Human AUTHORIZED: (1) `git -c vmarket.role=RUNNER commit` of `.ai/status/results/` JSONs on worker branches, (2) `git push --force-with-lease` of rebased `backend/VM-TEST-001` + `backend/VM-ASSETS-001`. Finding: origin/backend/VM-TEST-001@418bf34a sits on stale ccb5599e, current v1 is 4f2c18c3 — TEST-001 must re-rebase onto 4f2c18c3 and regenerate evidence. Corrected order dispatched below.
- 2026-09-27  Reviewer AI  IN_PROGRESS (collected fix round)  Human-pushed origin/backend/VM-TEST-001@fb3a4fb10e01f8a92dc42e0668e26ec6c2905e3a (fetch verified). Base = origin/v1@5259ed03, currency PASS. Scope: harness files only (DumpSchemaTestCase 513 new + 2x8-line bootstrap edits), zero product code. BUT: stale `.ai/status/results/VM-TEST-001/a5805de0.json` retained (not removed) + `.ai/tickets/backlog/VM-TEST-001.md` gutted-not-removed (gate duplicate risk) → CHANGES_REQUIRED, no rebase.
- 2026-09-27  Reviewer AI  IN_PROGRESS (transcription path approved)  Human approved same transcription release path as ASSETS-001 for TEST-001 (v1 lacks in-progress ticket; worker currency forbids v1 pre-push). This file text is the transcription source: Backend copies this exact blob onto the branch, removes both stale files in the same commit, regenerates evidence (uncommitted), regular-pushes, reports SHA. New SHA needs cycle-4 review + fresh JSON + gate before ritual.
