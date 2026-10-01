# Ticket: VM-CUST-007

Ticket ID:            VM-CUST-007
Title:                Customer Intent+Pay Widget Proof — Snapshot Lock, Paystack Init, No Success Declare
Type:                 FEATURE
Status:               READY
Blocked:              no
Created by / date:    AI-8 / 2026-09-25
Size estimate:        ~350 lines (1 test file + fixtures)

Business requirement:
Prove checkout intent snapshot lock and Paystack initiation semantics with widget tests — app never declares payment success locally.
Problem:
`checkout_controller/service`, `choose_payment_widget`, `payment_status_screen`, `digital_payment_order_place_screen` have no widget proof. Risk: client confuses initialized with verified/settled.
Expected behavior:
- Intent request shape is `{address_id, cart_item_ids}` (customer-owned only); test asserts no fee/origin fields sent.
- Snapshot display (fulfillment_type, lane, fee, ETA, prices, rewards) renders backend values immutably.
- Pay Now opens Paystack experience; test asserts app does NOT mark paid on open/redirect/callback/reference alone.
- Status polling `GET intent/{id}/status` drives UI: initiated/pending/failed/cancelled vs verified/settled; only verified/settled refreshes order.
- Pickup variant: `pending_inspection` renders as reservation, pay enabled only after `inspected_accepted`; rejected renders no-pay state.
Forbidden behavior:
- NEVER declare payment successful from client callback/reference.
- NEVER manufacture an order locally as confirmed.
- NEVER deduct stock or award cashback client-side.
Affected systems:     User App (Flutter)
Tier / area:          A (Customer payment — financially critical)
Legacy debt IDs:      none
Affected APIs:
- POST /api/v1/checkout/intent (mocked)
- POST /api/v1/checkout/intent/{id}/pay (mocked)
- GET /api/v1/checkout/intent/{id}/status (mocked: initiated/pending/verified/settled/failed)
- POST /api/v1/pickup-reservations + /{code}/pay (mocked contract shapes)
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
Branch / base commit: ai2/VM-CUST-007 (from v1 HEAD, after VM-CUST-006)
Dependencies (tickets/features): VM-CUST-006
Tests required:
- `User app/test/checkout_intent_pay_test.dart`: intent payload shape, snapshot render, pay-init-no-success, status-state mapping, pickup accepted/rejected paths.
- `flutter test` green; `flutter analyze` clean.
Security requirements:
- No real Paystack keys/refs in tests; mocked references only.
- Duplicate-callback fixture renders single order refresh (idempotency display).
Acceptance criteria:
- [ ] 1. Intent payload has no fee/origin fields (evidence: test log).
- [ ] 2. Pay init does not mark paid; only verified/settled does (evidence: test log).
- [ ] 3. Pickup accepted enables pay, rejected disables pay (evidence: test log).
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
