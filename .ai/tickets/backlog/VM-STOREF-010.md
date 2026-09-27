# Ticket Template (Appendix A)

Ticket ID:            VM-STOREF-010
Title:                Display integrity: Naira mojibake + {!! !!} purifier audit + header modal fallback + legacy shipping UI note
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-27 (triaged from scratch/storefront-ticket-drafts/VM-STOREF-010-DRAFT.md deep-scan batch; filed to BACKLOG, no dispatch — queued behind reroutes, triage position 8/10)
Size estimate:        medium (encoding + audit + header; shipping UI migration scoped separately)

Business requirement:
Problem:            Prices must show exact ₦ with Δ=0.00 trust; rich text safe; header auth always reachable; fees only from DeliveryLane (authoritative). (a) Pay ?0 mojibake for ₦0 in shipping.blade:59,67,99,821,830 (charset/font). (b) {!! !!} stored-XSS surface: product/details:203, account-order-details/_reviews:113, business_page:14, _wish-list-data:43,121 + admin/vendor views flagged in prior scan. (c) Header Sign In/Register modal-only (_header:80,84 href=javascript) dead if modal missing. (d) Cart still renders legacy ShippingType/CartShipping/Helpers::getShippingMethods (cart-details:42-147) with zero DeliveryLane/FulfillmentAvailabilityService call in shipping.js — migration flagged, not broken today.
Expected behavior:  (a) UTF-8 meta + font renders ₦ everywhere. (b) Purifier audit: sanitize-at-input verified or escape applied where rich text not needed; no raw customer HTML. (c) Header links are anchors with href fallback to login/register routes. (d) Decision note scopes lane migration (likely separate ticket).
Forbidden behavior: No blanket escaping breaking rich text; no new shipping engine in this ticket (authoritative DeliveryLane stands; migration ships separately if ruled).
Affected systems:     customer/storefront-web, backend/core-api
Tier / area:          B (escalates to A for XSS if purifier missing → Reviewer rules at dispatch/review)
Legacy debt IDs:      none
Affected APIs:        none
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    yes if XSS confirmed (report at review)
Dependency changes:   none (purifier package only if missing + justified at dispatch)
Documents updated:    none - justify (or decision record for lane migration scope)
Assigned AI:          BACKEND AI (purifier + encoding headers) then FRONTEND AI (blade/header)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-STOREF-010 then frontend/VM-STOREF-010 (from v1 after reroutes land)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md` at dispatch)
Dependencies (tickets/features): reroutes merged
Tests required:       ₦ renders on shipping/payment/invoice; stored-XSS probe neutralized; JS-off header auth reaches login; lane-migration decision recorded
Security requirements: HTML purifier on vendor/admin inputs; output escaping default; header no javascript: hrefs
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] ₦ correct everywhere (evidence: screenshots)
- [ ] XSS probes safe (evidence: hostile input log)
- [ ] header auth works JS-off (evidence: no-JS click)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          Naira + header + probe results

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-27  Reviewer AI  BACKLOG (filed)  Triaged from scratch draft (storefront §1,3,9 + cross-cutting). Lane migration stays decision-note-only (no new engine). XSS escalation path noted. Queued behind reroutes; triage position 8/10.
