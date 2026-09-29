# Ticket: VM-CUST-010

Ticket ID:            VM-CUST-010
Title:                Storefront cart JS hygiene â€” remove dead shipping/coupon re-init (hook-blocked remainder of CUST-003)
Type:                 FEATURE
Status:               READY
Blocked:              yes (NEEDS-SCOPE-REFRESH: patch file missing, JS paths drifted - see History)
Created by / date:    AI-8 / 2026-09-25
Size estimate:        ~50 lines (3 files, deletions only; exact patch at scratch/VM-CUST-003-js-cleanup.patch)

Business requirement:
Finish the AI-5-approved VM-CUST-003 cleanup that the DECISION-006 hook blocked for AI-2, so no dead shipping/coupon code paths remain in the cart flow. DECISION-008 ruled Option A (2026-09-25): AI-1 assigned, confirmed.
Problem:
`cart.js` / `cart-list-page.js` still call `setShippingIdFunction()` and `renderCouponCodeApply()` in AJAX re-init chains, and `_route-for-js.blade.php` still renders the orphaned `#set-shipping-url` span â€” all bound to elements removed by 8a61392a. Harmless no-ops today, but dead code inviting future misuse. (Audit note 2026-09-25: current hook would allow AI-2 the JS pair after a rebase; this ticket keeps all 3 with AI-1 per DECISION-008 Option A unless the human rules B/C/D.)
Expected behavior:
- Apply `scratch/VM-CUST-003-js-cleanup.patch` verbatim (or reproduce its deletions by hand and diff against it: must match exactly).
- Cart quantity +/-, delete, checkbox selection, order-note persist, and `#proceed-to-next-action` flows verified unchanged.
- Zero references to `#set-shipping-method`, `setShippingIdFunction`, `renderCouponCodeApply` in cart paths afterwards.
Forbidden behavior:
- NEVER alter blade markup or CTA contracts (CUST-003 territory, released).
- NEVER add behavior; deletions only.
- NEVER bulk-add files; exact-path commit of the 3 files only.
Affected systems:     customer/storefront-web
Tier / area:          C (post-release hygiene)
Legacy debt IDs:      LEGACY-SHIPPING-001, LEGACY-COUPON-001
Affected APIs:        none
Contract impact:      no
Client compatibility impact: no
Feature flag / kill switch: none - dead-code removal
Affected database tables: none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none
Assigned AI:          AI-1 (default under DECISION-008 Option A; reassigned per human ruling)
Required reviewers:   AI-5
Branch / base commit: ai1/VM-CUST-010 (from release merge of VM-CUST-003)
Dependencies:         VM-CUST-003 (must be RELEASED first)
Tests required:
- `scripts/tests/run-frontend-tests.ps1 -App customer` 6/6
- DOM re-trace: `#proceed-to-next-action` bindings in cart-list-page.js / shipping-page.js intact
Security requirements:
- CSRF/session behavior unchanged (no handler logic touched)
Acceptance criteria:
- [ ] Patch applied verbatim (diff-empty vs scratch/VM-CUST-003-js-cleanup.patch)
- [ ] Zero dead references in cart paths
- [ ] Frontend suite 6/6 + AI-5 review APPROVED

Counters:             review_cycles: {AI5: 0, AI6: 0, AI7: 0}   integration_failures: 0   reopened_count: 0
Screenshots:          N/A

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-25  AI-8  BACKLOG -> READY  Filed as the executable branch of DECISION-008; blocked on VM-CUST-003 release + human ruling.
- 2026-09-25  Human  ruling: Option A  AI-1 confirmed assignee. Ready to dispatch once VM-CUST-003 release is published (done: RELEASE-2026-09-25-001).

- 2026-09-29  Reviewer AI  READY held (Blocked: yes NEEDS-SCOPE-REFRESH)  Pre-dispatch verification: scratch/VM-CUST-003-js-cleanup.patch not found; setShippingIdFunction now in public/assets/front-end/js/cart-details.js + custom.js (not cart.js/cart-list-page.js); set-shipping-url span zero hits. Worker must NOT execute stale patch. Scope refresh required: re-locate dead re-init calls, re-issue exact patch, rename branch frontend/VM-CUST-010 from v1 e383c2b8, then unblock.

