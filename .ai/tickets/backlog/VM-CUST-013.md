# Ticket Template (Appendix A)

Ticket ID:            VM-CUST-013
Title:                Transaction-Path Button Audit — browse, cart, checkout, pay, track (storefront slice)
Type:                 FEATURE
Status:               BACKLOG
Blocked:              no
Created by / date:    Human operator / 2026-09-26
Size estimate:        audit (report + defect tickets; fixes ship under own tickets; full 136-control sweep stays in VM-CUST-012)

Business requirement:
Problem:            Launch needs the money path proven clickable, not the whole storefront at once. This ticket audits ONLY the transaction chain (browse → add to cart → qty/remove → cart total → proceed → shipping → payment → Paystack → order placed → account/track-order). Everything outside this chain stays in VM-CUST-012.
Expected behavior:  Per-control record (element id/data-attr → JS handler file:line → verb + URL → route → controller → service → tables → PASS/FAIL/UNVERIFIED). Static reads are NOT passes. Duplicates/dead controls reported as defects. Defects become own tickets.
Forbidden behavior: No application-code fixes under this ticket. No PASS without the file:line + route + verb triple. No misreporting group-prefixed routes as missing (verify via `php artisan route:list`).
Affected systems:     Storefront Web (theme_vmarket cart/checkout chain)
Tier / area:          A (transaction path)
Legacy debt IDs:      none
Affected APIs:        cart.*, customer.choose-shipping-address*, web-payment-request, track-order.*
Contract impact:      no (audit only; fixes may follow)
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (audit only)
Affected database tables:     carts, cart_shippings, orders (read-only verification)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (audit only; report lives in review file)
Assigned AI:          BACKEND AI   (dispatched by REVIEWER AI with an exact-prompt work order; Backend owns the live server so it walks the chain)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-CUST-013 (from current `v1`)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md`)
Dependencies (tickets/features): VM-TEST-001 landed (single-front rule); cross-references VM-STOREF-001/002/003/004 + VM-CATL-001 (audit confirms, never duplicate fixes — fixes ship under STOREF tickets); VM-CUST-011/VM-CUST-012 struck 2026-09-27 (do not exist)
Tests required:       live click-chain walk per control; `php artisan route:list` resolved set attached
Security requirements: state-changing GETs, missing auth middleware, and legacy-shipping cost leaks flagged as defects with file:line (see VM-CUST-011 F-01/F-02/F-03 for the pattern)
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] Every transaction-chain control recorded with handler → route → controller → service triple (evidence: audit report)
- [ ] No control marked PASS on static read alone (evidence: reviewer spot-check)
- [ ] Duplicate/dead controls listed as defects (evidence: defect ticket IDs)
- [ ] Every defect filed as its own ticket (evidence: ticket IDs)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes (Frontend stage only if fixes need UI)
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (audit; evidence is handler/route triples)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-26  Human  BACKLOG (created)  Launch-sequence ticket 3 of 4: transaction-path slice of the button audit; full sweep stays in VM-CUST-012.
- 2026-09-27  Reviewer AI  BACKLOG (triaged)  Static wiring-map report reviewed: ROUTE_BOUND tags are NOT passes (one mis-tag confirmed: help-topics listed bound with no route) — live file:line + route + verb triples required, which this ticket already mandates. Third-party draft work order REJECTED as-is (garbled paths, Backend filing tickets, refs to non-existent CUST-012, no BLOCKED-past-first-dead rule). Corrected work order held by Reviewer; dispatch after VM-TEST-001 lands (single front). Three leads folded into scope: (1) _order-summery dual cart/payment contexts, (2) pickup-reservations.create + submitPickupReservation + ₦0 hold post, (3) switchFulfillment inline onclick. VM-CUST-011/012 struck (missing).
