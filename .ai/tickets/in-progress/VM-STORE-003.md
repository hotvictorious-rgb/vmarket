# Ticket Template (Appendix A)

Ticket ID:            VM-STORE-003
Title:                Contact-us form posts to GET-only view route (405 on every submit)
Type:                 BUG
Status:               BACKEND_DONE
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-28 (found during storefront button audit: live 405 proof)
Size estimate:        xs (1-line form action)

Business requirement:
Problem:            `theme-views/pages/contact-us.blade.php:44` posts the contact form to `route('contacts')`, which is GET-only (`routes.php:143`); the submit handler lives at `POST contact/store` (`contact.store`, `WebController@contact_store:883`). Every contact submission dies with 405. Proven live on server :8002.
Expected behavior:  Form posts to `contact.store`; handler validates (name/email/mobile/subject/message match) and processes as coded.
Forbidden behavior: No handler changes. No other blades. No backend PHP, routes, Flutter, assets, Control Zone, results/reviews/changelog.
Affected systems:     Storefront contact page
Tier / area:          B (contact; trust surface)
Legacy debt IDs:      none
Affected APIs:        `POST contact/store` (existing, unchanged)
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (1-line fix)
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (report lives in review file)
Assigned AI:          FRONTEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: frontend/VM-STORE-003 (from current `v1`; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
Work order:
- **Goal:** Repoint the contact form to the existing submit route and prove live submit past 405.
- **Branch:** `frontend/VM-STORE-003` from current `v1`.
- **Allowed files (exact):** `resources/themes/theme_vmarket/theme-views/pages/contact-us.blade.php` (`:44` action only); own ticket notes.
- **FORBIDDEN:** everything else.
- **Acceptance:** (1) live POST returns validation/Toastr path (no 405); (2) grep confirms no other form posts to GET-only `contacts`; (3) suites green.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-STORE-003` full HEAD (JSON uncommitted, report counts); push; FRONTEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; sandbox only.
Dependencies (tickets/features): VM-STORE-001 (same area, released)
Tests required:       live submit proof; run-all JSON at full SHA
Security requirements: CSRF kept; no new inputs
Acceptance criteria:
- [ ] Contact submit no longer 405 (evidence: live log)
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human ΓåÆ REVIEWER AI ΓåÆ FRONTEND AI ΓåÆ REVIEWER AI (APPROVED) ΓåÆ REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (1-line fix; evidence is live log)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-28  Reviewer AI  BACKLOG (filed)  Live 405 proof on :8002 during button audit. Handler field match verified.
- 2026-09-28  Frontend AI  BACKLOG -> BACKEND_DONE (single-coordinator session)  1-line action repoint contacts->contact.store (handler field match verified). Live: POST contact/store 302 back (was 405). branch=frontend/VM-STORE-003.
- 2026-09-28  Frontend AI  run-all 17/17 PASS at fix commit 83080fdb (7 executed + 10 justified).
