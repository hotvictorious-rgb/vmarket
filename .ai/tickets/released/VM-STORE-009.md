# Ticket Template (Appendix A)

Ticket ID:            VM-STORE-009
Title:                Guest cart button dead-ends (bounces home); country default BD in data
Type:                 BUG
Status:               RELEASED
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-29 (user asked: does the cart button open items?)
Size estimate:        xs (header conditional + sandbox config note)

Business requirement:
Problem:            (1) Header cart button always links `shop-cart`, but `WebController@shop_cart:538-581` bounces guests to home with a warning when `guest_checkout` is off (V1 forbids guest checkout ΓÇö correct rule, bad UX: badge counts items guests can never view). (2) Testing `business_settings.country_code` is `BD` (stock default); NG marketplace must default NG.
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
Pipeline:             Human ΓåÆ REVIEWER AI ΓåÆ FRONTEND AI ΓåÆ REVIEWER AI (APPROVED) ΓåÆ REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.

Implementation notes:
Review notes: .ai/reviews/REV-VM-STORE-009-1486007087566a90cf2603bc656f5e01714045c8.md (Decision APPROVED; served-HTML proof + 17/17 at exact SHA)
Final decision: APPROVED → RELEASED as RELEASE-2026-09-29-008
Release commit: a65c643c (merge v1; feature 1486007087566a90cf2603bc656f5e01714045c8)

History (append-only):
- 2026-09-29  Reviewer AI  BACKLOG (filed, executed same-session)  User question traced to guest-bounce + BD default.
- 2026-09-29  Frontend AI  BACKLOG -> BACKEND_DONE (single-coordinator session)  Header cart conditional; served guest HTML opens loginModal; staging country_code BD->NG (sandbox data fix, prod admin must set likewise). branch=frontend/VM-STORE-009.
- 2026-09-29  Frontend AI  run-all 17/17 PASS at fix commit 3ac7b922 (7 executed + 10 justified).
- 2026-09-29  Reviewer AI  BACKEND_DONE -> REVIEW_APPROVED -> RELEASED (RELEASE-2026-09-29-008, single-coordinator session)  Served-HTML proof + NG data fix, run-all 17/17 at 14860070, gate 18/18 PASS. Merged --no-ff (a65c643c); ticket released; branch deleted after merge.
