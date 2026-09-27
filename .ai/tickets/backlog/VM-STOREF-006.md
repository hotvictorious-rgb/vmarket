# Ticket Template (Appendix A)

Ticket ID:            VM-STOREF-006
Title:                Wishlist clear-all via GET + JS-only adds + missing empty state
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-27 (triaged from scratch/storefront-ticket-drafts/VM-STOREF-006-DRAFT.md deep-scan batch; filed to BACKLOG, no dispatch — queued behind reroutes, triage position 6/10)
Size estimate:        small-medium

Business requirement:
Problem:            Wishlist persist + add-all-to-cart must be safe and work without JS. account-wishlist.blade.php:29 clear-all is GET with @csrf (useless on GET; routes:179 defines GET) → refresh/crawler wipes list, no CSRF protection. Adds are <a href=javascript data-action> with no form fallback (dead JS-off). Empty illustration ungated (cart has one, wishlist unclear).
Expected behavior:  Clear-all → POST/DELETE + @csrf (like single-delete modal :157 correct pattern); adds have native POST fallbacks; explicit empty state.
Forbidden behavior: No GET state changes elsewhere; no new wishlist tables.
Affected systems:     customer/storefront-web, backend/core-api
Tier / area:          B (state-changing GET is data-loss adjacent; security requirements carry weight)
Legacy debt IDs:      none
Affected APIs:        POST store-wishlist/delete-wishlist, GET delete-wishlist-all (to be versioned to POST/DELETE)
Contract impact:      yes (method change for clear-all; version + migration note in API contract registry at dispatch)
Client compatibility impact:  yes (min web version; document at dispatch)
Feature flag / kill switch:   none - justify
Affected database tables:     wishlists (no schema)
Migration impact:     no
Data impact:          yes (deletion guard; no PII change — review must carry privacy findings per gate check 14)
Compliance impact:    no
Dependency changes:   none
Documents updated:    API contract registry entry for method change (pinned at dispatch)
Assigned AI:          BACKEND AI then FRONTEND AI
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-STOREF-006 then frontend/VM-STOREF-006 (from v1 after reroutes land)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md` at dispatch)
Dependencies (tickets/features): reroutes merged
Tests required:       crawler/refresh does not wipe; CSRF enforced; JS-off add works; empty state renders
Security requirements: state-changing via POST/DELETE + @csrf + owner scope; helper getPriceRangeWithDiscount output escaped-safe (_wish-list-data:43,121)
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] clear-all requires POST + token (evidence: GET 405 + POST 200)
- [ ] adds work JS-off (evidence: no-JS log)
- [ ] empty state present (evidence: screenshot)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          wishlist empty + network methods

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-27  Reviewer AI  BACKLOG (filed)  Triaged from scratch draft (storefront §10). Contract+compat yes → registry entry required at dispatch. Queued behind reroutes; triage position 6/10.
