# Ticket: VM-ASSETS-001

Ticket ID:            VM-ASSETS-001
Title:                Permanent first-serve unstyled fix — public/themes via storage:link
Type:                 BUG
Status:               IN_PROGRESS
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-26 (from human report: unstyled on every first serve + Backend local commit 6a8fb3b3 on wrong ticket)
Size estimate:        small (3 files, ~25 lines: filesystems.php links + 2 controllers)

Business requirement:
Problem:            Every fresh clone / first `php artisan serve` renders unstyled: `public/themes` link missing on Windows. `v1` `InstallController::step5()` runs Unix-only `shell_exec('ln -s ../resources/themes themes')` from project root (wrong cwd) guarded by `DOMAIN_POINTED_DIRECTORY == 'public'`, and `storage:link` only creates `public/storage` (no `themes` entry in `config/filesystems.php` `links`). Windows has no `ln -s`, junction never created, theme CSS/JS 404. Same gap in `UpdateController::updatePurchaseCode()`.
Expected behavior:  Fresh clone + `php artisan storage:link` (called from install step5 + update path) creates both `public/storage` and `public/themes` on Windows (junction, no admin) and Linux/macOS (symlink), with zero manual `mklink` / `ln -s`. First serve is styled.
Forbidden behavior: No Unix-only `shell_exec('ln -s ...')` anywhere in install/update path. No duplicate `Artisan::call('storage:link')`. No hardcoded absolute paths. No manual steps required on fresh clone. No changes to test harness or unrelated product logic.
Affected systems:     backend install/update bootstrap, filesystem links
Tier / area:          B
Legacy debt IDs:      none (new; supersedes Unix-only ln -s pattern)
Affected APIs:        none
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - core bootstrap path, exception-swallowed by design
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (bootstrap fix, no API/docs change)
Assigned AI:          BACKEND AI   (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-ASSETS-001 (create fresh from current `v1` at 3976e8aa; NEVER reuse backend/VM-TEST-001; Reviewer deletes it after merge)
Work order:
  ## WORK ORDER from REVIEWER AI — VM-ASSETS-001 — BACKEND
  - **Goal (one sentence):** Make first-serve styled on Windows + Unix by declaring `public/themes` in `filesystems.php` `links` and calling `storage:link` once from install/update, deleting Unix-only `ln -s` shell_exec.
  - **Branch:** `backend/VM-ASSETS-001` (create from `v1` at 3976e8aa; push only this branch; NEVER merge; NEVER push to `v1`/`main`; NEVER commit on `backend/VM-TEST-001`; Reviewer deletes it after merge)
  - **Allowed files (exact paths/scopes):** `backend/vmarket-web/config/filesystems.php` (`links` array only), `backend/vmarket-web/app/Http/Controllers/InstallController.php` (`step5()` link block only), `backend/vmarket-web/app/Http/Controllers/UpdateController.php` (`updatePurchaseCode()` link block only); own notes in `.ai/tickets/in-progress/VM-ASSETS-001.md` copy if needed
  - **FORBIDDEN paths (do not touch):** `backend/vmarket-web/tests/**`, `backend/vmarket-web/database/**`, `backend/vmarket-web/phpunit.xml`, all Flutter (`User app/`, `Vendor app/`, `Delivery Man App/`), all Blade (`backend/vmarket-web/resources/views/**`), theme assets (`backend/vmarket-web/public/assets/**`), Control Zone (`.ai/*.md` rules, `.ai/agents/*`, `.ai/templates/*`, `.ai/schemas/*`, `scripts/**`), `.ai/status/results/**`, `.ai/reviews/**`, `AI_CHANGELOG.md`
  - **Contract / inputs you consume:** This ticket Business requirement + Acceptance criteria; `v1` evidence: `InstallController::step5()` Unix-only `shell_exec('ln -s ../resources/themes themes')` + bare `storage:link` (themes missing), `UpdateController::updatePurchaseCode()` same pattern, `config/filesystems.php` has no `links` key; reference commit 6a8fb3b3 (on origin/backend/VM-TEST-001, verified — correct direction, wrong ticket — re-apply its 3-file content cleanly on the new branch, single `storage:link` call, no duplicate)
  - **Acceptance criteria (each needs evidence):**
    1. [ ] `config/filesystems.php` declares `links` with `public_path('storage') => storage_path('app/public')` + `public_path('themes') => base_path('resources/themes')` (evidence: `git diff` + `php -l`)
    2. [ ] `InstallController::step5()` has zero `shell_exec`/`ln -s`, single `Artisan::call('storage:link')` in try/catch (evidence: `grep -rn "shell_exec\|ln -s" app/Http/Controllers/InstallController.php` empty + `git diff`)
    3. [ ] `UpdateController::updatePurchaseCode()` same: zero `shell_exec`, single `storage:link` (evidence: same grep on UpdateController + `git diff`)
    4. [ ] Fresh-link proof: delete `public/storage` + `public/themes` in a temp copy, run `php artisan storage:link`, both links exist and resolve to targets on this Windows host (evidence: `dir` / `Get-Item` output + `php artisan storage:link` log)
    5. [ ] No regressions: `php -l` on all 3 touched files + `scripts/tests/run-backend-tests.ps1` result recorded (failures must match VM-TEST-001 baseline only, no new failures) (evidence: full runner log tail)
  - **Tests to run + evidence to attach:** REQUIRED: `php -l` on every touched file; `grep` proof of zero `shell_exec` in both controllers; fresh-link recreation proof; `scripts/tests/run-backend-tests.ps1` from repo root (Herd php84 on PATH — do NOT use XAMPP php 8.1); `git diff --stat` showing only the 3 allowed files
  - **DONE definition:** commits pushed to `backend/VM-ASSETS-001` + ticket notes filled (contract none, schemas none, migration notes none — bootstrap only) + status word `BACKEND_DONE` reported in ticket History.
  - **Rules:** exact-path `git add` only (never bulk / `-A` / `-a`); leave other AIs' files dirty; change notes in ticket notes (NEVER edit `AI_CHANGELOG.md` — Reviewer writes the one entry at release); blocked → set `Blocked: yes (<reason>)` + History entry to REVIEWER AI, never to the human, never sideways.
  - **Push rule:** NEVER merge, NEVER push to `v1`/`main`. Only Reviewer AI merges + pushes after APPROVED + gate PASS via `scripts/release/merge-release`.
Dependencies (tickets/features): VM-TEST-001 (must stay test-only; its branch must be cleaned first — see that ticket History)
Tests required:       `php -l` x3 + fresh `storage:link` recreation proof + `scripts/tests/run-backend-tests.ps1` (no new failures vs baseline)
Security requirements: no shell_exec in web-request path (command-injection surface removed); links confined to `resources/themes` + `storage/app/public`; no secrets in logs
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] `links` array present with storage + themes (evidence: diff + php -l)
- [ ] Zero `shell_exec`/`ln -s` in both controllers, single `storage:link` each (evidence: grep + diff)
- [ ] Fresh-link recreation works on Windows host (evidence: dir/Get-Item + artisan log)
- [ ] Backend runner shows no new failures (evidence: runner log)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes (no frontend stage: bootstrap-only, no UI change)
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (bootstrap link fix; styled proof via link-exists + HTTP 200 on theme asset if available)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-26  Reviewer AI  BACKLOG -> IN_PROGRESS  Filed from human first-serve unstyled report + Backend 6a8fb3b3 (correct 3-file direction, wrong ticket). ID VM-ASSETS-001 chosen because VM-THEME-001 is taken on v1 (Theme_Aster removal, READY). Dispatched to BACKEND AI on fresh branch backend/VM-ASSETS-001 from v1@3976e8aa.
- 2026-09-26  Reviewer AI  IN_PROGRESS (dispatched)  Fix order (2): Backend creates fresh backend/VM-ASSETS-001 from v1@3976e8aa carrying only the 3-file styling fix (filesystems.php links + 2 controllers, single storage:link, zero shell_exec), attaches php -l + grep-empty + fresh-link + runner proof, pushes branch, declares BACKEND_DONE here. NEVER merge, NEVER touch v1/main.
