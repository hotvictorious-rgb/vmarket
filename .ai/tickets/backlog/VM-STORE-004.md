# Ticket Template (Appendix A)

Ticket ID:            VM-STORE-004
Title:                Country-picker stylesheet missing from main layout (+NG default audit)
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-28 (user-reported raw country list in register popup)
Size estimate:        xs (1 CSS link + audit notes; init wiring explicitly deferred)

Business requirement:
Problem:            User reports the register-popup country list rendering as a raw flat list. Traced: `intlTelInput.css` loads only in `blog-layouts` + seller-register page — never in the main storefront layout (`layouts/app.blade.php`) — so any initialized picker dropdown renders unstyled. Separately, `initializePhoneInput()` (`country-picker-init.js:3`) is defined but never called anywhere and has no script include, so v1 currently renders a clean plain tel input; the raw-list symptom matches production-old-code (stock default country BD/+880 observed) or an unseen surface — exact surface requested from user.
Expected behavior:  Stylesheet present so any picker renders styled; picker-init wiring left untouched pending browser proof (init rewrites phone values — unsafe blind). Country default audited (config `country_code` vs hardcoded NG).
Forbidden behavior: No JS init wiring without browser proof. No phone-submit behavior change. No other files. No backend PHP, routes, Flutter, assets (CSS link only), Control Zone, results/reviews/changelog.
Affected systems:     Storefront main layout head
Tier / area:          B (auth UX polish)
Legacy debt IDs:      none
Affected APIs:        none
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (stylesheet link only)
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (report lives in review file)
Assigned AI:          FRONTEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: frontend/VM-STORE-004 (from current `v1`; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
Work order:
- **Goal:** Add the picker stylesheet to the main layout and record the default-country + init findings.
- **Branch:** `frontend/VM-STORE-004` from current `v1`.
- **Allowed files (exact):** `theme-views/layouts/app.blade.php` (one `<link>` only); own ticket notes.
- **FORBIDDEN:** everything else.
- **Acceptance:** (1) rendered home HTML contains the iti.css link (evidence: HTML grep); (2) full-page render smoke unchanged (home 200); (3) suites green.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-STORE-004` full HEAD (JSON uncommitted, report counts); push; FRONTEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; sandbox only.
Dependencies (tickets/features): VM-STORE-002 (same area)
Tests required:       render smoke; run-all JSON at full SHA
Security requirements: no new inputs (stylesheet only)
Acceptance criteria:
- [ ] iti.css link present in main layout head (evidence: rendered HTML)
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (stylesheet link; browser proof of picker pending user surface)

Implementation notes:
- Default-country audit: `initializePhoneInput` falls back to `.system-default-country-code[data-value]` (rendered only in `blog-layouts`) else `'us'`; observed user default `+880` (BD) matches stock config, not v1 code. Correct default for NG marketplace (config `country_code` → NG, or explicit `initialCountry`) to be set when init is wired with browser proof — NOT in this ticket.
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-28  Reviewer AI  BACKLOG (filed)  Full picker trace (plugin present, init dead, CSS absent on main layout). Stylesheet-only fix; init wiring deferred pending browser proof + exact user surface.
