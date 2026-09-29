# Ticket Template (Appendix A)

Ticket ID:            VM-STORE-007
Title:                Top bar becomes vendor funnel — "Become a Vendor" to registration/login
Type:                 FEATURE
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-29 (human ruling: vendors sign up from the top bar; isolated store links verified live)
Size estimate:        xs (1-line button repoint)

Business requirement:
Problem:            Vendors have no visible entry: top bar links "Verified Merchants" (customer-facing merchant LISTING) next to Help & Support. Human rules it becomes "Become a Vendor" opening the vendor registration funnel (which carries a Login link for returning vendors — verified live). Isolated vendor storefronts (`vendor-shop/{slug}`) verified rendering 200 with SEO/OG tags — shareable links work as-is, no code needed.
Expected behavior:  Top bar shows "Become a Vendor" → `vendor.auth.registration.index` (200, valid form action, Login link present — all verified live 2026-09-29). Nav "Verified Merchants" listing untouched (customer discovery stays).
Forbidden behavior: No other header changes. No backend PHP, routes, Flutter, assets, Control Zone, results/reviews/changelog.
Affected systems:     Storefront top bar (`_header.blade.php:15`)
Tier / area:          B (vendor acquisition)
Legacy debt IDs:      none
Affected APIs:        none (existing registration funnel: index 200, add POST validates, login link present)
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (label + target change)
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (report lives in review file)
Assigned AI:          FRONTEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: frontend/VM-STORE-007 (from current `v1`; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
Work order:
- **Goal:** Repoint the top-bar vendor entry to the registration funnel.
- **Branch:** `frontend/VM-STORE-007` from current `v1`.
- **Allowed files (exact):** `theme-views/layouts/partials/_header.blade.php` (`:15` only); own ticket notes.
- **FORBIDDEN:** everything else.
- **Fix:** `<a href="{{ route('vendor.auth.registration.index') }}">{{ translate('Become a Vendor') }}</a>` (both routes verified live/resolving).
- **Acceptance:** (1) rendered home header carries the new label+target (evidence: served HTML); (2) target page 200 with valid form (already proven, re-confirm post-change); (3) suites green.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-STORE-007` full HEAD (JSON uncommitted, report counts); push; FRONTEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; sandbox only.
Dependencies (tickets/features): none (funnel pages verified live beforehand)
Tests required:       served-HTML proof; run-all JSON at full SHA
Security requirements: no new inputs (link only)
Acceptance criteria:
- [ ] Top bar shows Become a Vendor → registration (evidence: served HTML)
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          top bar (notes)

Implementation notes:
- Isolated storefront proof 2026-09-29: `GET vendor-shop/uyo-central-electronics` 200, SEO title + OG tags, no code changes needed. Vendor auth funnel: login 200 + register 200 + Login link on register page + submit validation + CSRF, all proven live earlier.
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-29  Reviewer AI  BACKLOG (filed)  Human vendor-funnel ruling.
