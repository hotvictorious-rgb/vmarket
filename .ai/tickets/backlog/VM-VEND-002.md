# Ticket Template (Appendix A)

Ticket ID:            VM-VEND-002
Title:                Vendor Onboarding Proof — register, catalog publish, inventory, order notification
Type:                 FEATURE
Status:               BACKLOG
Blocked:              no
Created by / date:    Human operator / 2026-09-26
Size estimate:        medium (backend validation + vendor-views proof; split if over 400 lines)

Business requirement:
Problem:            No vendors = no marketplace. The vendor journey (registration → approval → catalog publish → inventory → receiving an order) has never been proven end to end on the current tree.
Expected behavior:  A vendor can register, get approved, publish a product with stock, and receive + acknowledge a customer order; each step verified against backend contracts with evidence. Defects become own tickets.
Forbidden behavior: No client-side pricing/inventory math in vendor views. No bypassing branch/employee isolation. No seed-data shortcuts presented as proof (live walk only).
Affected systems:     Vendor Web Panel, Vendor Mobile App, Product catalog, Inventory, Order notifications
Tier / area:          A
Legacy debt IDs:      none
Affected APIs:        vendor auth, product CRUD, inventory update, order list/acknowledge
Contract impact:      yes (vendor API use verified; missing fields become RFCs, never guesses)
Client compatibility impact:  no
Feature flag / kill switch:   none - justify
Affected database tables:     sellers, products, product_stocks, orders, shops
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (report lives in review file)
Assigned AI:          BACKEND AI first, then FRONTEND AI   (dispatched by REVIEWER AI with exact-prompt work orders; frontend starts only after Reviewer confirms BACKEND_DONE)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-VEND-002, then frontend/VM-VEND-002 (from current `v1`)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md`)
Dependencies (tickets/features): VM-PAY-001 (order notification proof needs settled money path; may run in parallel as proof-only, fixes ordered after)
Tests required:       live walk per step with file:line + route + verb evidence; branch-isolation negative test (vendor B cannot see vendor A data); `flutter analyze` if app touched
Security requirements: vendor IDOR scoping on every step; branch/employee isolation verified hostilely
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] Vendor registration + approval walk proven (evidence: per-step rows)
- [ ] Product publish + stock update proven, server-side (evidence: DB rows vs UI)
- [ ] Order appears for the right vendor only; acknowledge works (evidence: cross-vendor negative test log)
- [ ] Every defect filed as its own ticket (evidence: ticket IDs)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          (vendor-views proof steps)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-26  Human  BACKLOG (created)  Launch-sequence ticket 2 of 4: vendor journey proof.
