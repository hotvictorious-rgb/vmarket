# Ticket Template (Appendix A)

Ticket ID:            VM-STORE-005
Title:                Auth modal logo rings 404 (storage-dependent web_logo with missing file)
Type:                 BUG
Status:               RELEASED
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-28 (user asked "can we have logo" ΓÇö broken image confirmed)
Size estimate:        xs (2-line src swap)

Business requirement:
Problem:            Login/register modal logo rings use `getStorageImages(web_logo)`, which falls back to theme `placeholder/*.png` files that DO NOT EXIST in the theme (`img/` ships only `favicon.jpg`, `vic_logo.webp`, `vm_icon.jpg`). Result: broken-image alt text in both auth modals in every env without an uploaded logo. The theme ships its own on-brand mark (`vic_logo.webp`, verified visually: purple/gold Victorious Market lockup).
Expected behavior:  Modal rings render the theme brand mark deterministically (zero DB/storage dependency). Admin-uploaded logos continue everywhere else untouched.
Forbidden behavior: No other markup changes. No backend PHP, routes, Flutter, assets (no new files), Control Zone, results/reviews/changelog.
Affected systems:     Storefront auth modals (`_login`, `_register`)
Tier / area:          B (auth trust UX)
Legacy debt IDs:      none
Affected APIs:        none
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (image src swap)
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (report lives in review file)
Assigned AI:          FRONTEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: frontend/VM-STORE-005 (from current `v1`; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
Work order:
- **Goal:** Point both modal logo rings at the theme brand mark.
- **Branch:** `frontend/VM-STORE-005` from current `v1`.
- **Allowed files (exact):** `theme-views/layouts/partials/modal/_login.blade.php` (`:47` src only), `theme-views/layouts/partials/modal/_register.blade.php` (`:30` src only); own ticket notes.
- **FORBIDDEN:** everything else.
- **Fix:** `src="{{ theme_asset('assets/img/vic_logo.webp') }}"` in both rings (44px max constraint already in CSS).
- **Acceptance:** (1) modal JSON/HTML carries vic_logo URLs (evidence: rendered grep); (2) file exists in repo (evidence: path); (3) suites green.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-STORE-005` full HEAD (JSON uncommitted, report counts); push; FRONTEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; sandbox only.
Dependencies (tickets/features): VM-STORE-002 (same area)
Tests required:       rendered-HTML proof; run-all JSON at full SHA
Security requirements: no new inputs (image swap only)
Acceptance criteria:
- [ ] Both rings reference the theme mark (evidence: rendered HTML)
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human ΓåÆ REVIEWER AI ΓåÆ FRONTEND AI ΓåÆ REVIEWER AI (APPROVED) ΓåÆ REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          modal rings (notes)

Implementation notes:
Review notes: .ai/reviews/REV-VM-STORE-005-71390a67d48cf5a73ecf32d16ae56b8a3acd0849.md (Decision APPROVED; rendered-mark proof + 17/17 at exact SHA)
Final decision: APPROVED → RELEASED as RELEASE-2026-09-29-001
Release commit: b040c09a (merge v1; feature 71390a67d48cf5a73ecf32d16ae56b8a3acd0849)

History (append-only):
- 2026-09-28  Reviewer AI  BACKLOG (filed)  Missing placeholder dir proven (`img/` has 3 files, no `placeholder/`); vic_logo verified visually on-brand.
- 2026-09-29  Frontend AI  BACKLOG -> BACKEND_DONE (single-coordinator session)  2 src swaps to theme vic_logo (verified on-brand visually); modal endpoint renders both marks (2 hits). branch=frontend/VM-STORE-005.
- 2026-09-29  Frontend AI  run-all 17/17 PASS at fix commit 0fad7f7 (7 executed + 10 justified).
- 2026-09-29  Reviewer AI  BACKEND_DONE -> REVIEW_APPROVED -> RELEASED (RELEASE-2026-09-29-001, single-coordinator session)  Rendered-mark proof, run-all 17/17 at 71390a67, gate 18/18 PASS. Merged --no-ff (b040c09a); ticket released; branch deleted after merge.
