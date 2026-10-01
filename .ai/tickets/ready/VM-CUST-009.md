# Ticket: VM-CUST-009

Ticket ID:            VM-CUST-009
Title:                Customer Journey Integration Proof + Runner Wiring (Cart to Cashback)
Type:                 FEATURE
Status:               READY
Blocked:              no
Created by / date:    AI-8 / 2026-09-25
Size estimate:        ~380 lines (1 integration test + runner/docs touch)

Business requirement:
Prove the full customer journey end-to-end in Flutter with one mocked-backend integration test and wire it into the release gate runner.
Problem:
Unit/widget proofs (VM-CUST-004..008) do not prove stage-to-stage handoff: cart -> address -> availability -> intent -> pay-init -> status -> order refresh. `run-frontend-tests.ps1 -App customer` currently runs only 6 model tests.
Expected behavior:
- `User app/test/customer_journey_test.dart` drives mocked services through: add-to-cart -> LGA address -> availability (Uyo->Uyo + Uyo->Eket unavailable branch) -> intent snapshot -> pay init -> verified status -> order refresh -> tracking + cashback display.
- Mixed fulfillment branch covered (one shop delivery, one pickup).
- Race branch covered: availability available then intent rejects (lane disabled) -> UI returns to fulfillment with message.
- Runner records expanded suite; release gate `customer_frontend` stops relying on analyze-only justification.
Forbidden behavior:
- NEVER hit real backend/Paystack from tests; mocked dio/http only.
- NEVER add prod fee math to make tests pass.
- NEVER break existing 6 model tests; they stay green.
Affected systems:     User App (Flutter)
Tier / area:          A (Customer journey integration — gate-critical)
Legacy debt IDs:      none
Affected APIs:
- All VM-CUST-004..008 mocked contracts chained (cart, geography, address, fulfillment/availability, checkout intent/pay/status, orders, cashback)
Contract impact:      no
Client compatibility impact: no
Feature flag / kill switch: none - test-only ticket
Affected database tables: none (frontend client scope)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none (hand-rolled fakes; new test packages only with Section 24.3 approval)
Documents updated:    none - justify (test-only; runner log is evidence)
Assigned AI:          AI-2
Required reviewers:   AI-5
Branch / base commit: ai2/VM-CUST-009 (from v1 HEAD, after VM-CUST-008)
Dependencies (tickets/features): VM-CUST-004, VM-CUST-005, VM-CUST-006, VM-CUST-007, VM-CUST-008 (chain in order)
Tests required:
- `User app/test/customer_journey_test.dart` full chain + unavailable + race branches.
- `scripts/tests/run-frontend-tests.ps1 -App customer` green with expanded count; `flutter analyze` clean.
- Web cart AJAX endpoints re-verified (quantity + selection) as companion evidence for VM-CUST-003 closure.
Security requirements:
- Mocked identity throughout; no secrets in fixtures.
Acceptance criteria:
- [ ] 1. Happy-path chain passes mocked end-to-end (evidence: test log).
- [ ] 2. Unavailable-lane + intent-race branches render correct fallback (evidence: test log).
- [ ] 3. Runner customer suite green with expanded count, no regressions in 6 model tests (evidence: runner log + schema-v2 result).

Counters:             review_cycles: {AI5: 0, AI6: 0, AI7: 0}   integration_failures: 0   reopened_count: 0
Screenshots:          N/A

Implementation notes:
To be populated by AI-2 during implementation.
Review notes:
To be populated by AI-5 during review.
Final decision:
Pending implementation and review.
Release commit:
Pending.

History (append-only):
- 2026-09-25  AI-8  BACKLOG -> READY  Ticket created per human approval for frontend journey proof project
