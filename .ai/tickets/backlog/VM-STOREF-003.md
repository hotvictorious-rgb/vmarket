# Ticket Template (Appendix A)

Ticket ID:            VM-STOREF-003
Title:                Cart dead without JS + silent AJAX + orphan order-note
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-27 (triaged from scratch/storefront-ticket-drafts/VM-STOREF-003-DRAFT.md deep-scan batch; filed to BACKLOG, no dispatch — queued behind reroutes, triage position 5/10)
Size estimate:        medium (blade fallbacks + JS error handlers)

Business requirement:
Problem:            Cart add/update/remove/quantity/coupon must work with server truth. cart-details.blade.php:15 `<form action=javascript:>` no method/CSRF/action; qty/select/shipping only via cart-details.js:30,54,125,157 which have zero error/fail callbacks and no offline handling; empty catch :112 swallows stock errors; orphan order-note form :567 GET no-action never hits POST order_note (routes:128).
Expected behavior:  Native form fallbacks (POST cart.add/update/remove with @csrf) + JS enhancement; every AJAX has error toast + retry; order-note posts to order_note or is removed if deprecated; stock-minimum swap still works.
Forbidden behavior: No client total recomputation (server HTML re-render stays); no new cart tables.
Affected systems:     customer/storefront-web, backend/core-api
Tier / area:          A (cart gatekeeper path)
Legacy debt IDs:      none
Affected APIs:        cart.add/remove/updateQuantity/nav-cart/variant_price/order_note (existing)
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify
Affected database tables:     none (no schema)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify
Assigned AI:          BACKEND AI (confirm contracts) then FRONTEND AI (blade+JS)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-STOREF-003 then frontend/VM-STOREF-003 (from v1 after reroutes land)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md` at dispatch)
Dependencies (tickets/features): reroutes merged
Tests required:       disable-JS add/qty/remove works; offline/toast on failed POST; order-note persists or form removed with decision note; coupon re-bind no duplicates
Security requirements: @csrf on all POST forms; cart scoped to session/customer; no price override params
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] native cart ops work JS-off (evidence: no-JS test log)
- [ ] failed AJAX toasts + no silent swallow (evidence: 500/offline simulation)
- [ ] order-note path resolved (evidence: DB row or removal decision)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          cart + devtools offline toast

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-27  Reviewer AI  BACKLOG (filed)  Triaged from scratch draft (storefront §4 + cart-details.js audit). Queued behind reroutes; triage position 5/10.
