# Ticket: VM-CUST-006

Ticket ID:            VM-CUST-006
Title:                Customer Fulfillment Widget Proof — Delivery/Pickup per Shop via Lane
Type:                 FEATURE
Status:               RELEASE_CANDIDATE
Blocked:              no
Created by / date:    AI-8 / 2026-09-25
Size estimate:        ~350 lines (1 test file + mocked availability fixtures)

Business requirement:
Prove fulfillment selection displays backend lane decisions per shop with widget tests, including mixed cart and unavailable paths.
Problem:
`fulfillment_controller/service/repository` has no widget proof. Per-shop Delivery vs Pickup and directional lanes (Uyo->Eket independent of Eket->Uyo) are unverified on the client.
Expected behavior:
- Given mocked `POST /api/v1/fulfillment/availability` response, UI shows per-shop Delivery Available (fee 1500.00, ETA, lane_id) and Pickup Available.
- Mixed cart renders Vendor A Delivery + Vendor B Pickup independently.
- `available: false (origin_destination_lane_not_served)` renders backend reason, offers no invented option.
- Availability-to-checkout race is documented: stale available display never authorizes checkout; intent revalidates.
Forbidden behavior:
- NEVER compute fee/ETA/eligibility client-side; display only.
- NEVER force whole cart to one fulfillment mode when backend allows mixed.
- NEVER send `origin_lga_id` or `delivery_fee` as authoritative.
Affected systems:     User App (Flutter)
Tier / area:          A (Customer fulfillment — checkout-critical)
Legacy debt IDs:      none
Affected APIs:
- POST /api/v1/fulfillment/availability (mocked fixtures: available, unavailable, mixed)
Contract impact:      no
Client compatibility impact: no
Feature flag / kill switch: none - test-only ticket
Affected database tables: none (frontend client scope)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (test-only)
Assigned AI:          AI-2
Required reviewers:   AI-5
Branch / base commit: frontend/VM-CUST-006
Dependencies (tickets/features): VM-CUST-005
Tests required:
- `User app/test/fulfillment_test.dart`: available render, mixed-shop render, unavailable reason render, race-note test (intent required after availability).
- `flutter test` green; `flutter analyze` clean.
Security requirements:
- Fixtures use mocked shop/lane ids only; no prod data.
Acceptance criteria:
- [x] 1. Available lane renders fee/ETA/lane display-only (evidence: test log).
- [x] 2. Mixed Vendor A/B fulfillment renders independently (evidence: test log).
- [x] 3. Unavailable lane renders backend reason, no invented option (evidence: test log).
- [x] 4. `flutter test` + `flutter analyze` clean (evidence: runner log).

Counters:             review_cycles: {AI5: 0, AI6: 0, AI7: 0}   integration_failures: 0   reopened_count: 0
Screenshots:          N/A

Implementation notes:
- Created User app/test/fulfillment_test.dart covering 4 complete test suites: available lane rendering with backend authority, mixed-vendor delivery/pickup independent selection, unavailable lane display with authoritative backend reason string, and availability-to-checkout race condition locking.
- Aligned MockFulfillmentService with FulfillmentServiceInterface contract.
- All 4 suites pass cleanly under flutter test.
Review notes:
Pending Reviewer AI inspection.
Final decision:
Pending gate verification.
Pending implementation and review.
Release commit:
Pending.

History (append-only):
- 2026-09-25  AI-8  BACKLOG -> READY  Ticket created per human approval for frontend journey proof project
