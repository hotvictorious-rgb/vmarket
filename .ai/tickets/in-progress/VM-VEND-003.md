# Ticket Template (Appendix A)

Ticket ID:            VM-VEND-003
Title:                Vendor banner retirement ΓÇö drop banner/bottom_banner from registration (form + validation)
Type:                 FEATURE
Status:               BACKEND_DONE
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-29 (human ruling: store banners retired for vendors)
Size estimate:        xs (2 validation rules + 2 Blade blocks; backend ships first)

Business requirement:
Problem:            Human retired store banners for vendors, but registration still demands them: `VendorAddRequest:43` requires `banner`, and the form renders `banner` (:122) + `bottom_banner` (:155) upload blocks. Any vendor submitting without banners fails validation. `ShopService::getAddShopDataForRegistration:86-89` + `FileManagerTrait::upload:42-44` (`def.png` fallback) + `$request->has()` storage guards are null-safe ΓÇö verified ΓÇö so removal is behavior-clean.
Expected behavior:  Registration validates and creates shops without banner fields; form shows no banner uploads; `image` (store) + `logo` stay required and untouched.
Forbidden behavior: No touching `image`/`logo`/shop fields. No other validation changes. No backend beyond the request file. No Flutter/assets, Control Zone, results/reviews/changelog.
Affected systems:     Vendor registration (web form + VendorAddRequest)
Tier / area:          A (vendor onboarding)
Legacy debt IDs:      LEGACY-BANNER-001 (proposed: vendor banner surfaces)
Affected APIs:        `POST vendor/auth/registration/add` (validation only)
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (retirement)
Affected database tables:     shops (banner columns fall back to def.png; untouched)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (report lives in review file)
Assigned AI:          BACKEND AI first (validation rules), then FRONTEND AI (form blocks) ΓÇö sequenced backend-first so validation never requires what the form lacks
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-VEND-003 then frontend/VM-VEND-003 (from current `v1`; push only own branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes after merge)
Work order:
- **Goal:** Remove banner/bottom_banner from vendor registration validation first, then from the form.
- **Stage 1 branch:** `backend/VM-VEND-003` ΓÇö delete `banner` + `bottom_banner` lines in `VendorAddRequest.php` ONLY; `php -l`; run-all; push; BACKEND_DONE. Reviewer reviews + releases backend stage alone (validation must ship before the form change).
- **Stage 2 branch:** `frontend/VM-VEND-003` (from post-stage-1 `v1`) ΓÇö delete banner block (`vendor-information-form:119-151`) + bottom_banner block (`:152-~185`, verify exact end) ONLY; push; FRONTEND_DONE. Reviewer reviews + releases.
- **FORBIDDEN:** everything else per stage boundaries.
- **Acceptance:** (1) registration validates without banner fields (evidence: live submit log); (2) form renders no banner uploads; (3) image/logo still required + working; (4) suites green per stage.
- **Tests + DONE per stage:** `run-all.ps1 -Ticket VM-VEND-003` full HEAD (JSON uncommitted, report counts); push; DONE as branch+SHA.
- **Rules:** exact-path `git add` only; testing sqlite + sandbox only.
Dependencies (tickets/features): VM-VEND-002 (journey proof consumes this)
Tests required:       live registration submit without banners; suites green per stage; run-all JSON at full SHA per stage
Security requirements: validation stays strict (image/logo/password rules untouched)
Acceptance criteria:
- [ ] Backend: banner rules gone, registration validates (evidence: live log)
- [ ] Frontend: banner blocks gone from form (evidence: rendered HTML)
- [ ] Suites green per stage, run-all JSONs at full SHAs

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human ΓåÆ REVIEWER AI ΓåÆ BACKEND AI ΓåÆ REVIEWER AI ΓåÆ FRONTEND AI ΓåÆ REVIEWER AI (APPROVED) ΓåÆ REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          registration form (notes)

Implementation notes:
- Null-safety verified 2026-09-29: `upload(null)` ΓåÆ `def.png`; storage-type guards use `$request->has()`; no migration needed.
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-29  Reviewer AI  BACKLOG (filed)  Human banner-retirement ruling. Staged backend-first.
- 2026-09-29  Backend AI  BACKLOG -> BACKEND_DONE (stage 1 validation only; single-coordinator session)  Removed banner+bottom_banner rules (+2 orphan messages). Live: full registration minus banners -> status 1, redirect login; seller id 10 pending + shop id 12 with def.png fallbacks. branch=backend/VM-VEND-003.
- 2026-09-29  Backend AI  run-all 17/17 PASS at stage-1 commit 5dcb2c9f (7 executed + 10 justified).
