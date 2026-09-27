# Ticket Template (Appendix A)

Ticket ID:            VM-STOREF-005
Title:                Product list filters neutered on theme_vmarket
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-27 (triaged from scratch/storefront-ticket-drafts/VM-STOREF-005-DRAFT.md deep-scan batch; filed to BACKLOG, no dispatch — queued behind reroutes, triage position 9/10)
Size estimate:        medium (include partials + wire AJAX + debounce)

Business requirement:
Problem:            Discovery with sort/filter by price/brand/rating. product/view.blade.php:17 only name text search. Six _filter-product-{brands,categories,price,reviews,sort,filter} partials exist but are never @include'd. POST ajax-filter-products (routes:169, ShopViewController@filterProductsAjaxResponse:557) unused here.
Expected behavior:  vmarket list includes working brand/category/price/review/sort UI wired to existing ajax-filter endpoint (or explicit decision to defer with rationale); Enter + button both trigger; debounced keystrokes; popstate keeps URL params; empty state retained.
Forbidden behavior: No new filter backend inventing pricing scope; must reuse marketplaceEligible scope.
Affected systems:     customer/storefront-web, backend/core-api
Tier / area:          B (discovery usability; linked to VM-CATL-001 timeout fix — no duplicate: CATL fixes the endpoint, this wires the UI)
Legacy debt IDs:      none
Affected APIs:        POST ajax-filter-products (existing)
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify
Assigned AI:          BACKEND AI (confirm scope) then FRONTEND AI
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-STOREF-005 then frontend/VM-STOREF-005 (from v1 after reroutes land)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md` at dispatch)
Dependencies (tickets/features): reroutes merged; after VM-CATL-001 (filter UI needs a working endpoint)
Tests required:       each filter changes grid via AJAX; error toast on fail; URL params survive back/forward or documented limitation
Security requirements: GET only, no CSRF needed; price bounds validated server-side
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] brand/price/rating/sort filter the grid (evidence: click recordings)
- [ ] no stale grid on failure (evidence: 500 simulation toast)
- [ ] partials included or deferral decision recorded

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          filtered grids

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-27  Reviewer AI  BACKLOG (filed)  Triaged from scratch draft (storefront §2 + product-list-filter.js). CATL-001 linkage noted (endpoint vs UI, not duplicates). Queued behind reroutes; triage position 9/10.
