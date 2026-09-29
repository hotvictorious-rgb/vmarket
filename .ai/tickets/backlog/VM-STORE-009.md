# Ticket Template (Appendix A)

Ticket ID:            VM-STORE-009
Title:                Guest cart button dead-ends (bounces home); country default BD in data
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-29 (user asked: does the cart button open items?)
Size estimate:        xs (header conditional + sandbox config note)

Business requirement:
Problem:            (1) Header cart button always links `shop-cart`, but `WebController@shop_cart:538-581` bounces guests to home with a warning when `guest_checkout` is off (V1 forbids guest checkout — correct rule, bad UX: badge counts items guests can never view). (2) Testing `business_settings.country_code` is `BD` (stock default); NG marketplace must default NG.
Expected behavior:  Guests (with guest checkout off) clicking cart get the login modal (same pattern as buy-now); authed users + guest-checkout-on go to shop-cart. Staging `country_code` set to NG (sandbox data fix, documented; prod admin must set likewise).
Forbidden behavior: No change to the guest-checkout rule itself. No other header changes. No backend PHP, routes, Flutter, assets, Control Zone, results/reviews/changelog.
Affected systems:     Storefront header cart button; staging business_settings (data only)
Tier / area:          B (cart UX correctness)
Affected APIs:        none
Contract impact:      no
Documents updated:    none - justify (report lives in review file)
Assigned AI:          FRONTEND AI
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: frontend/VM-STORE-009 (from current `v1`)
Work order:           (executed same-session per human run-order)
Dependencies (tickets/features): none
Tests required:       served-HTML proof per auth state; run-all JSON at full SHA
Acceptance criteria:
- [ ] Guest + guest-checkout-off cart button opens login modal (evidence: served HTML)
- [ ] Authed cart button still links shop-cart (evidence: served HTML logic + code path)
- [ ] Staging country_code NG (evidence: DB read)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-29  Reviewer AI  BACKLOG (filed, executed same-session)  User question traced to guest-bounce + BD default.
