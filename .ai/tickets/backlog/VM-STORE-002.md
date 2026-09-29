# Ticket Template (Appendix A)

Ticket ID:            VM-STORE-002
Title:                Product-page cart defects — first-click misroute, buy-now missing attrs, country picker never initializes
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-28 (user-reported live symptoms reproduced in code)
Size estimate:        xs (JS guard + button attrs + picker wiring)

Business requirement:
Problem:            Live user reports on the storefront: (1) add-to-cart "pops something but doesn't add"; (2) Buy Now does nothing; (3) register popup country list renders raw. Root causes proven in code: (1) `addToCart()` treats missing `.product-exist-in-cart-list` as "exists" (`undefined !== ""`), POSTing first clicks on `details.blade.php` (which lacks the hidden input) to the update-quantity endpoint → status-0 warning popup, zero items added. (2) `details.blade.php:182` buy-now button lacks `data-auth`/`data-route`, so `buyNow()` degrades to plain add (no login modal, no checkout redirect) — compare working `quick-view-data:330-337`. (3) `initializePhoneInput()` (`country-picker-init.js:3`) is defined but never called anywhere, and `intlTelInput.css` is absent from the main layout — any initialized picker renders unstyled.
Expected behavior:  First click adds (status 1 + toast + nav update); Buy Now opens login modal for guests / redirects to checkout for authed; country picker renders styled or stays a clean tel input — never a raw list.
Forbidden behavior: No behavior change beyond the three fixes. No new endpoints. No touching backend PHP, routes, other blades, Flutter, assets (JS edit only), Control Zone, results/reviews/changelog.
Affected systems:     Storefront product page (details.blade), theme custom.js, auth modal phone UX
Tier / area:          A (browse→cart→buy is the transaction path)
Legacy debt IDs:      none
Affected APIs:        `POST cart.add`, `POST cart/updateQuantity-guest` (no contract change — correct routing only)
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (defect fix)
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (report lives in review file)
Assigned AI:          FRONTEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: frontend/VM-STORE-002-cart (from current `v1`; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
Work order:
- **Goal:** Fix first-click misroute, restore buy-now attrs, wire the country picker (or neutralize cleanly) — all proven by live walks.
- **Branch:** `frontend/VM-STORE-002-cart` from current `v1`.
- **Allowed files (exact):** `public/assets/js/custom.js` (`addToCart` guard only), `theme-views/product/details.blade.php` (buy-now button attrs only), country-picker wiring (init call + CSS include only, no logic change to phone values).
- **FORBIDDEN:** backend PHP, routes, other blades, Flutter, other assets, Control Zone, results/reviews/changelog.
- **Fixes:** (1) `let existCartItem ...` guard: only reroute when the element EXISTS and is non-empty; (2) details buy-now button: add `data-auth` + `data-route` mirroring quick-view pattern; (3) country picker: load `intlTelInput.css` on main layout + call `initializePhoneInput` for static inputs on ready and modal inputs on inject — OR, if init proves unsafe without browser proof, remove the dead init file + `phone-input-with-country-picker` classes so fields render as clean tel inputs (record which).
- **Acceptance:** (1) first click on details page POSTs `cart.add` and returns status 1 (evidence: live log); (2) buy-now as guest opens login modal, as authed redirects via `redirect_to_url` (evidence: live logs + code path); (3) register modal phone field renders cleanly with styled picker or plain input, zero raw list (evidence: rendered HTML + screenshot note).
- **Tests + DONE:** `run-all.ps1 -Ticket VM-STORE-002` full HEAD (JSON uncommitted, report counts); push branch; report FRONTEND_DONE as branch+SHA.
- **Rules:** exact-path `git add` only; sandbox only.
Dependencies (tickets/features): VM-STORE-001 (released; same area)
Tests required:       live first-click + buy-now + modal walks; run-all JSON at full SHA
Security requirements: CSRF unchanged (ajaxSetup kept); no new inputs
Acceptance criteria:
- [ ] First click adds to cart (evidence: live log status 1)
- [ ] Buy-now redirects/prompts correctly (evidence: live logs)
- [ ] Country field renders cleanly (evidence: rendered HTML note)
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          modal + picker renders (notes)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-28  Reviewer AI  BACKLOG (filed)  User-reported symptoms root-caused in code before any edit. Fixes execute under this ticket.
