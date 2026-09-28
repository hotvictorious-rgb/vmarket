# Ticket Template (Appendix A)

Ticket ID:            VM-CATL-001
Title:                Catalog discovery timeout — api/v1/products/latest hangs over HTTP while SQLite fast natively
Type:                 BUG
Status:               RELEASED
Blocked:              no (scope expanded by Reviewer 2026-09-28 on reviewer/VM-CATL-001-scope@1b09673b; blocker cleared)
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
Branch / base commit: backend/VM-CATL-001 (from current `v1@cb0779e6`; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
Work order:
  ## WORK ORDER from REVIEWER AI — VM-CATL-001 — BACKEND
  - **Goal (one sentence):** Root-cause and fix the `GET api/v1/products/latest` HTTP timeout (DB fast natively) with zero contract change, proving the fix with timed probes + query counts.
  - **Branch:** `backend/VM-CATL-001` (create from `v1@cb0779e6`; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
  - **Allowed files (exact paths/scopes):** `backend/vmarket-web/app/Http/Controllers/RestAPI/v1/ProductController.php` (`get_latest_products()` only), `backend/vmarket-web/app/Utils/ProductManager.php` (`get_latest_products()` only); read-only inspection of `backend/vmarket-web/app/Http/Controllers/RestAPI/v1/FeedSyncController.php` (:26 in-process caller) + `apiGuestCheck` middleware (no edits there without a new Reviewer ruling); own notes in `.ai/tickets/in-progress/VM-CATL-001.md` copy if needed
  - **FORBIDDEN paths (do not touch):** `backend/vmarket-web/routes/**`, all other controllers/services, all Flutter (`User app/`, `Vendor app/`, `Delivery Man App/`), all Blade (`backend/vmarket-web/resources/views/**`), theme assets (`backend/vmarket-web/public/assets/**`), Control Zone (`.ai/*.md` rules, `.ai/agents/*`, `.ai/templates/*`, `.ai/schemas/*`, `scripts/**`), `.ai/status/results/**`, `.ai/reviews/**`, `AI_CHANGELOG.md`
  - **Contract / inputs you consume:** This ticket Business requirement + Acceptance criteria; chain evidence: route `GET api/v1/products/latest` (routes/rest_api/v1/api.php:175, `apiGuestCheck` group) → `RestAPI\v1\ProductController@get_latest_products` (:55) → `ProductManager::get_latest_products()` (app/Utils/ProductManager.php:43); in-process caller `FeedSyncController:26` (->getData(), not HTTP); testing sqlite only, Herd php84 via `scripts/tests/run-backend-tests.ps1`
  - **Acceptance criteria (each needs evidence):**
    1. [ ] Root cause named with file:line — self-HTTP loopback vs N+1 vs missing index vs middleware (evidence: timed probe + query log + code refs; `api/v1/config` is the known-good control)
    2. [ ] `GET api/v1/products/latest` responds within staging budget after fix (evidence: before/after timed probe logs on testing sqlite, sandbox only)
    3. [ ] Queries-per-request bounded, no N+1 in `ProductManager::get_latest_products()` (evidence: query-count log)
    4. [ ] Zero self-HTTP fetch of internal URLs in the path (evidence: grep `Http::|curl_|file_get_contents('http` in the two allowed scopes = empty, or SSRF surface removed with file:line)
    5. [ ] No regressions: invariant + security suites green, contract byte-identical (evidence: full `scripts/tests/run-all.ps1` JSON at full HEAD SHA, uncommitted per hook)
  - **Tests to run + evidence to attach:** REQUIRED: `php -l` on every touched file; timed HTTP probes (before/after); query-count log; `scripts/tests/run-all.ps1 -Ticket VM-CATL-001` with NO `-CommitSha` (full HEAD binding); `git diff --stat` showing only allowed scopes
  - **DONE definition:** commits pushed to `backend/VM-CATL-001` + ticket notes filled (contract: none — byte-identical; schemas: none unless an index migration proves necessary, then pretend + status proof) + status word `BACKEND_DONE` with full SHA + run-all counts reported in ticket History.
  - **Rules:** exact-path `git add` only (never bulk / `-A` / `-a`); testing sqlite + sandbox only (no prod traffic, no prod data, no live charges); blocked → set `Blocked: yes (<reason>)` + History entry to REVIEWER AI, never to the human, never sideways.
  - **Push rule:** NEVER merge, NEVER push to `v1`/`main`. Only Reviewer AI merges + pushes after APPROVED + gate PASS via `scripts/release/merge-release`.
Dependencies (tickets/features): TEST-001 + ASSETS-001 reroutes land first (single-front rule, currency); blocks VM-PERF-001 (load ceiling meaningless while browse times out) and the PAY-001 audit walk (browse -> cart -> intent)
Tests required:       repro on clean `v1` via runner + HTTP probe with timings; query-count per request (N+1 check); existing invariant + security suites stay green; full `scripts/tests/run-all.ps1` JSON at full SHA before review
Security requirements: no secrets in logs; no prod data; no self-HTTP fetch of internal URLs from the request path (SSRF surface removed if present)
Acceptance criteria:  (checklist; each item gets an evidence link)
- [x] Root cause named with file:line: `backend/vmarket-web/app/Http/Controllers/RestAPI/v1/ProductController.php:45-46` (pre-fix) — dead constructor deps on purged `App\Models\Author`/`App\Models\PublishingHouse` → `BindingResolutionException` at controller instantiation (evidence: exception log, grep output, constructor cleanup diff).
- [x] `GET api/v1/products/latest` within budget on clean v1: **51.09 ms** after fix (control `api/v1/config` = 2635.48 ms cold-boot cost) (evidence: in-process timed probe log).
- [x] Queries-per-request bounded, no N+1: **10 queries / 2.88 ms DB time** (evidence: query-count log).
- [x] Zero self-HTTP in path: grep `Http::|curl_|file_get_contents` in allowed scopes = **0 matches** (evidence: grep output).
- [x] No regressions: full `scripts/tests/run-all.ps1` JSON at `f6bafddf` reports 17/17 suites PASS (evidence: Schema-v2 result JSON at `.ai/status/results/VM-CATL-001/f6bafddfc47575ae7865235336bc5ac20076333f.json`).

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI (only if Reviewer confirms needed) → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (API defect; evidence is timed probe + query logs)

Implementation notes:
- **ROOT CAUSE (named, file:line):** `backend/vmarket-web/app/Http/Controllers/RestAPI/v1/ProductController.php:45-46` (pre-fix) — `__construct` promoted `private readonly AuthorRepositoryInterface $authorRepo` and `private readonly PublishingHouseRepositoryInterface $publishingHouseRepo`. `App\Repositories\AuthorRepository::__construct` requires `App\Models\Author` and `App\Repositories\PublishingHouseRepository::__construct` requires `App\Models\PublishingHouse`; both models were purged during legacy-debt cleanup. `App\Providers\InterfaceServiceProvider.php:bindInterfaceWithRepository()` still binds the two interfaces (it auto-discovers `app/Contracts/Repositories/*Interface.php` → `app/Repositories/*.php`), so the container resolved the dead repositories and threw `BindingResolutionException: Target class [App\Models\Author] does not exist.` Laravel resolves the controller and its whole constructor graph during `Route::getController()` in the middleware-gathering phase — before `get_latest_products()` is ever dispatched. Result: every HTTP request to `GET api/v1/products/latest` returned HTTP 500 while `api/v1/config` (a different controller, clean constructor) returned 200. **Not** a self-HTTP loopback, **not** an N+1, **not** a missing index.
- **Pre-edititized grep proof (Reviewer-required):** `authorRepo|publishingHouseRepo|AuthorRepositoryInterface|PublishingHouseRepositoryInterface` in `ProductController.php` matched exactly 4 lines, all declaration-only — 2 `use` imports (lines 5, 7) and 2 promoted constructor params (lines 45, 46). **Zero use sites.** Removal is behavior-preserving for every other method in the file.
- **Fix applied (scope: constructor + unused imports only):** removed the two promoted properties and their two `use` imports. `git diff --stat` = `.../ProductController.php | 4 ----` (1 file changed, 4 deletions). No method bodies touched. `Author::`/`PublishingHouse::` references at lines 116, 139, 202, 224 are untouched dead legacy debt → separate ticket, not freelanced.
- **Timed probes (testing sqlite, sandbox; Herd php84 8.4.25):**
  - BEFORE (`php -S localhost:8088`): `[500] GET /api/v1/products/latest?limit=10&offset=1` (fatal `BindingResolutionException`); control `[200] GET /api/v1/config` in ~1.2 s incl. cold boot.
  - AFTER (in-process kernel handle, same DB, post-fix HEAD): `STATUS: 200` on `/api/v1/products/latest?limit=10&offset=1` in **51.09 ms**; control `/api/v1/config` `STATUS: 200` in 2635.48 ms (cold first-request boot cost, 66 products / 58 categories present). The 500 is gone; the route now dispatches.
- **Query count:** `ProductManager::get_latest_products()` = **10 bounded queries / 2.88 ms DB time / 30.46 ms native wall clock**. Zero N+1. No index migration needed → no schema, no migration, no pretend run.
- **Zero self-HTTP proof:** grep `Http::|curl_|file_get_contents` across the two allowed scopes (`ProductController.php`, `ProductManager.php`) = **0 matches**. No SSRF surface in the request path.
- **`php -l`:** `No syntax errors detected` on `ProductController.php` and `ProductManager.php` (Herd php84).
- **Contract:** unchanged / byte-identical. Response shape of `get_latest_products()` untouched.
- **Schema/migrations:** none. **Docs:** none (report lives in review file).

Review notes: .ai/reviews/REV-VM-CATL-001-06e029100ab5e4737bf0c856f69b848509aa02a9.md (Decision APPROVED; independent re-run 17/17 PASS at exact SHA)
Final decision: APPROVED → RELEASED as RELEASE-2026-09-28-001
Release commit: 02bb0708307907a8227faab3db8be56301c8e681 (merge v1; feature 06e029100ab5e4737bf0c856f69b848509aa02a9)

History (append-only):
- 2026-09-27  Reviewer AI  BACKLOG (filed)  Filed from :8001 observation (DB fast natively, latest-products timeout over HTTP). Queued behind TEST-001/ASSETS-001 reroutes per single-front + currency rule. No dispatch yet — work order pasted at dispatch after reroutes land.
- 2026-09-27  Reviewer AI  BACKLOG -> IN_PROGRESS (dispatched)  Both reroutes RELEASED (v1@cb0779e6). Chain mapped by Reviewer: route api.php:175 → ProductController:55 → ProductManager:43 (+ in-process FeedSyncController:26). Work order pasted above; branch backend/VM-CATL-001 from v1@cb0779e6.
- 2026-09-28  Backend AI   IN_PROGRESS -> BLOCKED (scope blocker reported)  Investigated catalog discovery timeout and HTTP failure. Native SQLite executes in ~30ms (10 queries, zero self-HTTP loopback). Direct HTTP probe on :8088 revealed fatal BindingResolutionException: Target class [App\Models\Author] does not exist triggered by ProductController::__construct injecting dead AuthorRepositoryInterface. Scope restricted to get_latest_products() only. Escalate to Reviewer AI for constructor cleanup scope authorization.
- 2026-09-28  Backend AI   BLOCKED -> BACKEND_DONE  branch=backend/VM-CATL-001 sha=f6bafddfc47575ae7865235336bc5ac20076333f  Scope amendment `reviewer/VM-CATL-001-scope@1b09673b` applied. Removed dead `AuthorRepositoryInterface`/`PublishingHouseRepositoryInterface` promoted props + 2 unused `use` imports from `ProductController::__construct` (1 file, 4 deletions only). Pre-edititized grep confirmed 0 use sites for removed deps. `php -l` PASS on `ProductController.php` & `ProductManager.php`. Before: `[500] GET /api/v1/products/latest` (fatal BindingResolutionException). After: STATUS 200 / 51.09 ms (control api/v1/config = 200). Queries: 10 bounded, 2.88 ms DB time, zero N+1, zero self-HTTP. run-all.ps1 -Ticket VM-CATL-001 Schema-v2 JSON at commit f6bafddf: 17/17 PASS (secret_scan, static_analysis, backend, security, contract, database, dependency_scan + 10 baseline-justified). git diff --stat: `ProductController.php | 4 ----`. Contract byte-identical. Awaiting REVIEWER AI review at exact SHA.
- 2026-09-28  Backend AI   BACKEND_DONE docs  sha=06e029100ab5e4737bf0c856f69b848509aa02a9  Ticket evidence recorded; runner JSON regenerated at full HEAD 06e02910: 17/17 PASS uncommitted per hook.
- 2026-09-28  Reviewer AI  BACKEND_DONE -> REVIEW_APPROVED (single-coordinator session; multi-terminal relay retired)  Independent verification at detached 06e02910: diff 4 deletions only, php -l PASS x2, zero self-HTTP grep empty, run-all.ps1 17/17 PASS bound to 06e02910. Gate 18/18 PASS. Review .ai/reviews/REV-VM-CATL-001-06e029100ab5e4737bf0c856f69b848509aa02a9.md Decision APPROVED. No Frontend dispatch (no client change required).
- 2026-09-28  Reviewer AI  REVIEW_APPROVED -> RELEASED (RELEASE-2026-09-28-001)  Merged origin/backend/VM-CATL-001 into v1 --no-ff (02bb0708); tag on merge commit; manifest + review + released ticket recorded. Backend branch deleted after merge.
