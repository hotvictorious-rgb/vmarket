# Ticket Template (Appendix A)

Ticket ID:            VM-CATL-001
Title:                Catalog discovery timeout — api/v1/products/latest hangs over HTTP while SQLite fast natively
Type:                 BUG
Status:               IN_PROGRESS
Blocked:              yes (Fatal HTTP 500 on Controller Instantiation due to purged App\Models\Author in constructor; out of scope for get_latest_products())
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
- **Investigation Summary:**
  1. **HTTP Routing & Instantiation Failure (Fatal 500)**:
     - `GET /api/v1/products/latest` over HTTP throws `Illuminate\Contracts\Container\BindingResolutionException`: `Target class [App\Models\Author] does not exist.`
     - Root cause: `App\Http\Controllers\RestAPI\v1\ProductController::__construct` (lines 45-46) auto-wires `AuthorRepositoryInterface` and `PublishingHouseRepositoryInterface`.
     - `App\Repositories\AuthorRepository::__construct` injects `App\Models\Author`, which was purged from the codebase during legacy cleanup.
     - Because Laravel's routing pipeline resolves the controller and its full constructor dependencies before invoking any method, the HTTP route cannot execute `get_latest_products()` at all.
  2. **Native Performance & Query Bounding**:
     - `ProductManager::get_latest_products()` executes in **30.46 ms** (2.88 ms DB time) across **10 bounded queries** on SQLite testing database.
     - `Helpers::product_data_formatting()` processes 10 items in **4.11 ms**.
     - There is no runaway query loop or query-level hang in SQLite.
  3. **Zero Self-HTTP Calls**:
     - Comprehensive grep for `Http::`, `curl_`, `file_get_contents('http')`, or Guzzle clients across `ProductController.php` and `ProductManager.php` returned **0 matches**.
     - Zero loopback SSRF or self-calls in the allowed scopes.
  4. **Scope Constraint & Blocker**:
     - Work order permits modifying ONLY `ProductController.php::get_latest_products()` and `ProductManager.php::get_latest_products()`.
     - Fixing the constructor of `ProductController.php` or `AuthorRepository.php` requires out-of-scope modifications.
     - Per Reviewer AI instruction: `"Blocked → set Blocked: yes (<reason>) + History entry to REVIEWER AI, never to the human, never sideways."`
     - Status updated to `Blocked: yes`.

Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-27  Reviewer AI  BACKLOG (filed)  Filed from :8001 observation (DB fast natively, latest-products timeout over HTTP). Queued behind TEST-001/ASSETS-001 reroutes per single-front + currency rule. No dispatch yet — work order pasted at dispatch after reroutes land.
- 2026-09-27  Reviewer AI  BACKLOG -> IN_PROGRESS (dispatched)  Both reroutes RELEASED (v1@cb0779e6). Chain mapped by Reviewer: route api.php:175 → ProductController:55 → ProductManager:43 (+ in-process FeedSyncController:26). Work order pasted above; branch backend/VM-CATL-001 from v1@cb0779e6.
- 2026-09-28  Backend AI   IN_PROGRESS -> BLOCKED (scope blocker reported)  Investigated catalog discovery timeout and HTTP failure. Native SQLite executes in ~30ms (10 queries, zero self-HTTP loopback). Direct HTTP probe on :8088 revealed fatal BindingResolutionException: Target class [App\Models\Author] does not exist triggered by ProductController::__construct injecting dead AuthorRepositoryInterface. Scope restricted to get_latest_products() only. Escalate to Reviewer AI for constructor cleanup scope authorization.
