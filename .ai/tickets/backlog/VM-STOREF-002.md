# Ticket Template (Appendix A)

Ticket ID:            VM-STOREF-002
Title:                Checkout address forms missing action + CSRF (419 on direct POST)
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-27 (triaged from scratch/storefront-ticket-drafts/VM-STOREF-002-DRAFT.md deep-scan batch; filed to BACKLOG, no dispatch — queued behind reroutes, triage position 3/10)
Size estimate:        small-medium (2 forms + JS contract note)

Business requirement:
Problem:            Canonical address Country→State→LGA + street/landmark must persist before DeliveryLane fee calc. checkout/shipping.blade.php:109 #address-form and :546 #billing-address-form are `<form method=post>` with no action and no @csrf. Save buttons :211-212,539-540 are type=button dismiss-modal only. Works only via undocumented JS (shipping.js:343 checkoutFromShipping posts to route-customer-choose-shipping-address-other). Direct POST → 419. Address-edit buttons :155,479 use onclick location.href (no href fallback).
Expected behavior:  Forms carry action + @csrf following account-address-add/edit pattern (route address-store|address-update); buttons submit natively with JS progressive enhancement; edit links are <a href> with valid route('address-edit',id) (exists routes:213).
Forbidden behavior: No parallel address tables; no GET for state-changing save; no removal of LGA fields.
Affected systems:     customer/storefront-web, backend/core-api
Tier / area:          A (address → lane fee calc)
Legacy debt IDs:      none
Affected APIs:        POST address-store, POST address-update, GET address-edit (existing)
Contract impact:      no (reuse existing)
Client compatibility impact:  no
Feature flag / kill switch:   none - justify
Affected database tables:     shipping_addresses (no schema change)
Migration impact:     no
Data impact:          yes (address writes; audited, no PII expansion — review must carry privacy findings per gate check 14)
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify
Assigned AI:          BACKEND AI first (confirm contract + CSRF), then FRONTEND AI (blade)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-STOREF-002 then frontend/VM-STOREF-002 (from v1 after reroutes land)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md` at dispatch)
Dependencies (tickets/features): reroutes merged
Tests required:       POST address-store direct (no JS) 302/200; CSRF present in HTML; edit link href resolves; checkoutFromShipping still works
Security requirements: @csrf on both forms; IDOR scope address to auth customer; no mass-assignment beyond allowlist
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] both forms have action + @csrf (evidence: blade snippet + curl POST)
- [ ] native submit persists address (evidence: DB row + redirect)
- [ ] edit controls are anchors with href (evidence: rendered HTML)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          forms + network POST 200

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-27  Reviewer AI  BACKLOG (filed)  Triaged from scratch draft (storefront §5). Data impact yes → privacy findings required at review. Queued behind reroutes; triage position 3/10.
