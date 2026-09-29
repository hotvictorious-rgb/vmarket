# Ticket Template (Appendix A)

Ticket ID:            VM-STORE-005
Title:                Auth modal logo rings 404 (storage-dependent web_logo with missing file)
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-28 (user asked "can we have logo" — broken image confirmed)
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
Pipeline:             Human → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          modal rings (notes)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-28  Reviewer AI  BACKLOG (filed)  Missing placeholder dir proven (`img/` has 3 files, no `placeholder/`); vic_logo verified visually on-brand.
