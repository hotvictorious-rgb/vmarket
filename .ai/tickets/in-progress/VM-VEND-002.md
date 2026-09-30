# Ticket Template (Appendix A)

Ticket ID:            VM-VEND-002
Title:                Vendor Onboarding Proof — register, catalog publish, inventory, order notification
Type:                 FEATURE
Status:               IN_PROGRESS
Blocked:              no (backend proof stage released as RELEASE-2026-09-30-007; open for frontend vendor-views walk per pipeline)
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

Counters:             review_cycles: 1   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          (vendor-views proof steps)

Implementation notes:
- Backend proof committed as f7cb6619 on backend/VM-VEND-002 (rebased on origin/v1 @cf14b6d0): VendorOnboardingProofTest 31/31 (register Uyo LGA 142 → approve → publish 18500/30/fresh → IDOR → order → exclude → ack → rollback, Δ=0.00). Runner JSON banked 17/17 (Tier A + Tier B runs).
Review notes:
- Cycle 1 (REVIEWER AI): backend proof APPROVED on validated artifacts (JSON binding re-checked, test code-skimmed for stage coverage + sandbox rollback, php -l clean, dummy creds only). 31-suite live re-execution witnessed via banked logs, not re-run by reviewer. See .ai/reviews/REV-VM-VEND-002-f7cb6619.md.
Final decision:
- Backend proof stage APPROVED for v1 merge; vendor-views walk stays open for frontend stage.
Release commit:       0c2b04ab (RELEASE-2026-09-30-007, backend proof stage)

History (append-only):
- 2026-09-26  Human  BACKLOG (created)  Launch-sequence ticket 2 of 4: vendor journey proof.
- 2026-09-30  REVIEWER  BACKLOG -> REVIEW_APPROVED (backend proof stage)  Proof suite + runner JSON validated; backend stage merges to v1.
- 2026-09-30  REVIEWER  REVIEW_APPROVED -> IN_PROGRESS  Backend proof stage released in RELEASE-2026-09-30-007; ticket open in-progress for frontend vendor-views walk.
