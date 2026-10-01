# Ticket: VM-CUST-008

Ticket ID:            VM-CUST-008
Title:                Customer Order-Track-Cashback Widget Proof — Own Orders, Safe Tracking, Points Display
Type:                 FEATURE
Status:               READY
Blocked:              no
Created by / date:    AI-8 / 2026-09-25
Size estimate:        ~300 lines (1 test file + fixtures)

Business requirement:
Prove post-order screens (orders, tracking, cashback) render backend authority with widget tests — no invented statuses, no internal data leak, no points math.
Problem:
`order_screen/widget`, `tracking_result_screen/status_stepper`, `cashback_screen/card` have no widget proof beyond notification parsing.
Expected behavior:
- Orders list/details render backend order number, products, prices, fulfillment mode, address snapshot, fee, payment/order status, timestamps.
- Status displays follow backend contract only (pending/confirmed/processing/out_for_delivery/delivered/canceled/returned/failed); no local transitions.
- Tracking shows customer-safe info only; hub/rider internals never rendered.
- Cancel/Return buttons render only when backend flags cancellable/eligible.
- Cashback renders available/earned/redemption from backend; 6-digit OTP shown as collection instructions display-only.
Forbidden behavior:
- NEVER fetch another customer's order by changed `order_id` (negative test required).
- NEVER leak rider/hub/dispatch internals to tracking UI.
- NEVER compute cashback balance/redemption locally.
Affected systems:     User App (Flutter)
Tier / area:          B (Customer post-order)
Legacy debt IDs:      none
Affected APIs:
- GET /api/v1/customer/orders (mocked)
- GET /api/v1/customer/order/{id} (mocked + ownership negative case)
- GET /api/v1/cashback/summary + /list (mocked)
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
Branch / base commit: ai2/VM-CUST-008 (from v1 HEAD, after VM-CUST-007)
Dependencies (tickets/features): VM-CUST-007
Tests required:
- `User app/test/order_track_cashback_test.dart`: order render, status fidelity, tracking safe-field allowlist, cancel/return gating, cashback display, ownership negative test, OTP 6-digit display format.
- `flutter test` green; `flutter analyze` clean.
Security requirements:
- Ownership negative test: Customer B order id rejected in mock service layer test.
Acceptance criteria:
- [ ] 1. Order details render backend fields only (evidence: test log).
- [ ] 2. Tracking renders allowlisted fields, no internals (evidence: test log).
- [ ] 3. Cancel/Return gated by backend flags; ownership enforced (evidence: test log).
- [ ] 4. `flutter test` + `flutter analyze` clean (evidence: runner log).

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
