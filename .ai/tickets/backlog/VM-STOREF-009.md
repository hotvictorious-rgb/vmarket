# Ticket Template (Appendix A)

Ticket ID:            VM-STOREF-009
Title:                Dead JS modules + cross-file ReferenceError risk + load-order guards
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-27 (triaged from scratch/storefront-ticket-drafts/VM-STOREF-009-DRAFT.md deep-scan batch; filed to BACKLOG, no dispatch — queued behind reroutes, triage position 10/10, runs after 001-008)
Size estimate:        small-medium

Business requirement:
Problem:            Every visible button must have a defined handler (no dead clicks) across cart/shipping/payment/list/detail/tracking. product-view.js:1 single line (dead); shipping.js:392 mapsShopping never self-invoked; payment.js:70 checkoutFromPayment no-op if nothing checked; cart-details.js:42-47,59,69 calls updateNavCart/couponCode/actionCheckoutFunctionInit/cartQuantityInitialize/getVariantPrice/renderQuickViewFunction from custom.js (load-order ReferenceError risk); product-list-filter:25 no debounce + no popstate; home.js commented blocks + invalid ltr opt; duplicate keydown bind shipping.js:184,288.
Expected behavior:  Dead files removed or implemented; cross-file calls guarded (typeof check + fallback); single keydown bind; debounced filter + popstate or documented limit; no console.log leftovers (payment.js:166,197).
Forbidden behavior: No new JS frameworks; no inline business math added.
Affected systems:     customer/storefront-web
Tier / area:          B
Legacy debt IDs:      none
Affected APIs:        none (wiring only)
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify
Assigned AI:          FRONTEND AI (backend confirms no contract change at dispatch)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: frontend/VM-STOREF-009 (from v1 after reroutes land)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md` at dispatch)
Dependencies (tickets/features): reroutes merged; after 001-008 to avoid conflicts (same JS files)
Tests required:       script load-order shuffle still works; no ReferenceError in console; dead includes removed (network 404 zero)
Security requirements: no eval; no unchecked innerHTML with user data
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] zero dead includes (evidence: network + grep)
- [ ] shuffled load order passes (evidence: console clean)
- [ ] no duplicate handlers (evidence: click-count test)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          console + network

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-27  Reviewer AI  BACKLOG (filed)  Triaged from scratch draft (storefront JS audit). Type ruled BUG. Runs last per same-file conflict rule. Queued behind reroutes; triage position 10/10.
