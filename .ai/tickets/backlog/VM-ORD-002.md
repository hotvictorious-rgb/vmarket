# Ticket Template (Appendix A)

Ticket ID:            VM-ORD-002
Title:                Live Author/PublishingHouse repo usage in 2 controllers — purged models break resolution
Type:                 BUG
Status:               BLOCKED (needs human product decision — see options below; reported by Reviewer AI, all backend investigation complete)
Blocked:              yes (ESCALATED 2026-09-28: repoint target does not exist; creating entities vs dropping attribution is a product call, not an engineering call)
Created by / date:    Reviewer AI / 2026-09-28 (found during VM-ORD-001 verification: fixing the import exposed the next binding failure `App\Models\Author`)
Size estimate:        small (repoint-or-remove at 4 live call sites across 2 controllers)

Business requirement:
Problem:            `AuthorRepository` requires purged `App\Models\Author` and `PublishingHouseRepository` requires purged `App\Models\PublishingHouse`, but two controllers still resolve and CALL them: `Admin\Order\OrderController:775,777` (`authorRepo->getListWhere`, `publishingHouseRepo->getListWhere` in order quick-view) and `RestAPI\v3\seller\ProductController:947,964` (`authorRepo->updateOrCreate`, `publishingHouseRepo->updateOrCreate` in digital publish flow). Unlike VM-CATL-001 (dead deps, safe removal), these are LIVE call sites — removal changes behavior and needs a repoint decision. Until fixed, `php artisan route:list` fatals and admin order quick-view + seller digital publish hit `BindingResolutionException`.
Expected behavior:  Each of the 4 call sites either repoints to the Digital* counterparts (seller controller already injects `DigitalProductAuthorRepositoryInterface` + `DigitalProductPublishingHouseRepository` on adjacent lines — migration looks half-done) or is removed with product sign-off; container resolves; `route:list` exits 0.
Forbidden behavior: No blind deletion of live behavior without noting the product impact per call site. No other files beyond the two controllers (+ imports). No prod traffic.
Affected systems:     admin orders quick-view, seller digital publish, artisan console (route:list)
Tier / area:          A (order + publish paths; blocks VM-CUST-013 route:list evidence with VM-ORD-001)
Legacy debt IDs:      LEGACY-AUTHOR-001 (proposed: retires the Author/PublishingHouse purge debt)
Affected APIs:        admin order quick-view partial; `POST` seller digital product publish; `php artisan route:list`
Contract impact:      no (internal repoint; response shapes preserved)
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (defect fix)
Affected database tables:     none unless repoint changes writes (Digital* tables already in use on adjacent lines)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (defect fix; report lives in review file)
Assigned AI:          BACKEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-ORD-002 (from current `v1` AFTER VM-ORD-001 releases; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
Work order:
- **Goal:** Repoint or remove the 4 live Author/PublishingHouse repo call sites so the container resolves and behavior is preserved via Digital* counterparts.
- **Branch:** `backend/VM-ORD-002` from post-ORD-001 `v1`.
- **Allowed files:** `app/Http/Controllers/Admin/Order/OrderController.php` (lines ~6,72,86,775,777,779 scope), `app/Http/Controllers/RestAPI/v3/seller/ProductController.php` (lines ~5,9,38,65-68,947,953-958,964,970-978 scope); own ticket notes.
- **FORBIDDEN:** everything else; no Flutter/Blade/assets; no Control Zone; no results/reviews/changelog.
- **Acceptance:** (1) per-call-site decision table (repoint vs remove + product impact); (2) `php artisan route:list` exits 0; (3) `php -l` clean; (4) invariant + security suites green; (5) seller digital publish + admin quick-view smoke (server :8000) without BindingResolutionException.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-ORD-002` full HEAD (JSON uncommitted, report counts); push; BACKEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; testing sqlite + sandbox only.
Dependencies (tickets/features): VM-ORD-001 (ships first; this branches after it releases); VM-CUST-013 (needs this + ORD-001 for route:list evidence)
Tests required:       `php -l`; `route:list` exit 0; suites green; run-all JSON at full SHA; live smoke of both call-site paths
Security requirements: no secrets; IDOR scoping on repointed writes must match existing seller/admin scoping
Acceptance criteria:
- [ ] 4 call sites dispositioned with product impact noted
- [ ] `php artisan route:list` completes exit 0
- [ ] No regressions: invariants + security green, full run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (defect fix)

Implementation notes:
- **Class-existence proof** (autoload-only, no boot; `classcheck.php` temp, Herd php84): `App\Models\Author` MISSING, `App\Models\PublishingHouse` MISSING, `DigitalProductAuthorRepositoryInterface` MISSING, `DigitalProductPublishingHouseRepository` MISSING, `DigitalProductPublishingHouse` model MISSING. Only `App\Models\OrderDetail` EXISTS.
- **Blast radius:** seller `v3 ProductController` injects all four missing classes as constructor deps (`:65-68` + `:5,:7,:9,:38`) → the WHOLE controller fails resolution, so every seller product endpoint (not just digital publish) 500s today. Admin `OrderController` quick-view digital section (`:775-779`) 500s on digital orders. (Live-auth probe not run; resolution failure is proven by class-existence + Laravel mandatory constructor injection.)
- **Why not CATL-001-style removal:** call sites are LIVE (not dead). Seller `:947/:964` feed legacy ids into the pivot writes; dropping the lines silently changes stored attribution. Admin `:775/:777` feeds the quick-view partial.
- **DECISION_REQUEST for human (pick one):**
  - A. Create minimal `DigitalProductAuthor` + `DigitalProductPublishingHouse` entities (models + migrations + repos) and repoint all 4 sites. Preserves attribution. Largest scope (new tables).
  - B. Drop author/publishing-house attribution from digital flows (delete legacy lines; pivot writes use name-keyed data or stop). Smallest scope; product accepts attribution loss.
  - C. Restore legacy `Author`/`PublishingHouse` models (undo purge). Reverses legacy direction; not recommended.
- Backend implements the chosen option under this ticket;rope stays 2 controllers + imports.
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-28  Reviewer AI  BACKLOG (filed)  Exposed by VM-ORD-001 fix: next binding failure is live Author usage, not dead deps — needs repoint decision, own ticket.
- 2026-09-28  Reviewer AI  BACKLOG -> BLOCKED (ESCALATED, single-coordinator session)  Class-existence proof banked above: models purged AND Digital replacements never created. Blast radius is the full seller product controller + admin quick-view digital section. Stopped per escalation rule (product decision required); no code touched.
