# Ticket Template (Appendix A)

Ticket ID:            VM-STOREF-007
Title:                Tracking/invoice noise + stray duplicate + empty view maps
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-27 (triaged from scratch/storefront-ticket-drafts/VM-STOREF-007-DRAFT.md deep-scan batch; filed to BACKLOG, no dispatch — queued behind reroutes, triage position 7/10)
Size estimate:        small

Business requirement:
Problem:            Order tracking + invoice must be trustworthy post-payment. tracking-page.blade.php:11 invalid type attr on form + useless @csrf on GET; result lacks @if(errors) for bad ID (controller flash unverified); stray order/invoice.blade copy.php duplicate; file_names.php:47,60-61 empty maps (floating_nav, wallet_account, compare_list) → view('') fails if hit.
Expected behavior:  Valid form markup; invalid-ID error view (not 500); duplicate deleted; empty maps filled or callers removed with decision.
Forbidden behavior: No new tracking tables; no hardcoded totals (already via OrderManager — keep).
Affected systems:     customer/storefront-web
Tier / area:          B
Legacy debt IDs:      none
Affected APIs:        track-order.* + generate-invoice (existing)
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify
Assigned AI:          FRONTEND AI (backend confirms invalid-ID controller path at dispatch)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: frontend/VM-STOREF-007 (from v1 after reroutes land)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md` at dispatch)
Dependencies (tickets/features): reroutes merged
Tests required:       bad-ID renders error view; duplicate absent; empty maps resolved
Security requirements: invoice scoped to owner (IDOR); no enumeration leak beyond existing
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] markup valid + error view for bad ID (evidence: 404/error render)
- [ ] duplicate file gone (evidence: glob)
- [ ] empty maps filled or removed (evidence: grep + route hit)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          tracking error + invoice

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-27  Reviewer AI  BACKLOG (filed)  Triaged from scratch draft (storefront §7). Type ruled BUG (defects + duplicate removal). Queued behind reroutes; triage position 7/10.
