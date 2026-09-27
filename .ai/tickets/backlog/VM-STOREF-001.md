# Ticket Template (Appendix A)

Ticket ID:            VM-STOREF-001
Title:                Storefront checkout crash on missing route('support-ticket')
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-27 (triaged from scratch/storefront-ticket-drafts/VM-STOREF-001-DRAFT.md deep-scan batch; filed to BACKLOG, no dispatch — queued behind reroutes, triage position 1/10)
Size estimate:        small (<100 lines, 1 blade line + verification)

Business requirement:
Problem:            Customer checkout shipping step (delivery/pickup tabs) must render without exception. theme-views/checkout/shipping.blade.php:88 links `route('support-ticket')` which has no definition. Defined names are support-ticket.index|comment|close|delete (routes/web/routes.php:185-191) + ticket-submit + account-tickets. Rendering the fulfillment block throws RouteNotFoundException → checkout dead.
Expected behavior:  Support link routes to account-tickets or support-ticket.index (with context) or contacts; page renders for guest + authed, delivery + pickup tabs.
Forbidden behavior: No new route inventing a parallel ticket system; no hardcoded URL; no removal of support entry.
Affected systems:     customer/storefront-web
Tier / area:          A (checkout path)
Legacy debt IDs:      none
Affected APIs:        none (blade route helper only)
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (link fix, no behavior flag)
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (one-line link fix)
Assigned AI:          FRONTEND AI (single-blade; backend confirms correct destination at dispatch)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: frontend/VM-STOREF-001 (from v1 after reroutes land)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md` at dispatch)
Dependencies (tickets/features): reroutes merged (v1 moving); none else
Tests required:       render shipping.blade with delivery+pickup tabs guest/authed; click support link lands 200; grep route('support-ticket') zero hits
Security requirements: no open redirect; destination route keeps customer middleware
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] shipping.blade:88 points to existing named route (evidence: grep + rendered href)
- [ ] checkout shipping renders 200 guest + authed (evidence: HTTP log)
- [ ] no other route('support-ticket') bare references (evidence: repo grep)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          frontend: before exception vs after render

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-27  Reviewer AI  BACKLOG (filed)  Triaged from scratch draft (storefront deep scan §5). Route-group claim spot-checked against routes/web/routes.php:185. Queued behind reroutes; triage position 1/10.
