# Ticket: VM-CUST-004

Ticket ID:            VM-CUST-004
Title:                Customer Cart Widget Proof — Qty, Selection, Summary CTA Contract
Type:                 FEATURE
Status:               IN_PROGRESS
Blocked:              no (AI-2 staffed 2026-09-25, branch ai2/VM-CUST-004 @9e1758eb)
Created by / date:    AI-8 / 2026-09-25
Size estimate:        ~300 lines (1 test file + minor test hooks)

Business requirement:
Prove the customer cart journey buttons work in the Flutter User App with widget tests, so cart success never relies on eyeballing.
Problem:
`User app/test/widget_test.dart` proves only model parsing (LGA, reservation, OTP regex, cashback math). No widget test taps cart qty +/-/delete, check/uncheck, summary, or CTA contract.
Expected behavior:
- Cart screen renders grouped items with stock/variant indicators.
- Tapping qty + / - / delete calls cart controller with sanitized positive-int qty and updates row total.
- Tapping item/shop checkbox updates selected total via controller.
- Summary shows Item Total, Product Discount, Estimated Tax, Estimated Victorious Points; delivery fee shown only from backend intent snapshot, never computed locally.
- CTA contract: test asserts checkout intent route is requested (address/fulfillment next), not that a fee was calculated.
Forbidden behavior:
- NEVER assert a client-computed delivery fee, tax, or total as authoritative.
- NEVER render legacy shipping dropdown or coupon input widgets.
- NEVER break Provider/GetIt patterns; no GetX/BLoC in User App.
- NEVER touch backend PHP or other apps.
Affected systems:     User App (Flutter)
Tier / area:          B (Customer App widgets)
Legacy debt IDs:      none
Affected APIs:
- GET /api/v1/cart/list (mocked)
- POST /api/v1/cart/updateQuantity (mocked contract)
- POST /api/v1/cart/select-cart-items (mocked contract)
Contract impact:      no (consumes locked contracts via mocked dio/http)
Client compatibility impact: no
Feature flag / kill switch: none - test-only ticket, no prod behavior change
Affected database tables: none (frontend client scope)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none (uses existing flutter_test SDK; mock via hand-rolled fakes, no new packages without Section 24.3 approval)
Documents updated:    none - justify (test-only, no public API doc change)
Assigned AI:          AI-2
Required reviewers:   AI-5
Branch / base commit: ai2/VM-CUST-004 (from v1 HEAD, after VM-CUST-003 release)
Dependencies (tickets/features): VM-CUST-003 (must be RELEASED first — shared cart semantics)
Tests required:
- `User app/test/cart_journey_test.dart`: qty increment/decrement/delete, checkbox selection, summary labels, CTA contract, empty-cart CTA.
- `flutter test` green; `flutter analyze` clean.
Security requirements:
- No real credentials/tokens in tests; mocked identity only.
- Quantity inputs fuzzed with 0/negative/huge values — must sanitize, never crash.
Acceptance criteria:
- [ ] 1. Qty + / - / delete widget taps update row totals via controller (evidence: test log).
- [ ] 2. Item/shop checkbox taps update selected total (evidence: test log).
- [ ] 3. Summary asserts Item Total, Discount, Estimated Tax, Estimated Cashback labels; no fee math asserted (evidence: test log).
- [ ] 4. CTA test asserts intent/next-route requested (evidence: test log).
- [ ] 5. `flutter test` + `flutter analyze` clean (evidence: runner log).

Counters:             review_cycles: {AI5: 0, AI6: 0, AI7: 0}   integration_failures: 0   reopened_count: 0
Screenshots:          N/A (widget tests, no visual change)

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
- 2026-09-25  AI-8  READY -> IN_PROGRESS  Dispatched to staffed AI-2; branch ai2/VM-CUST-004 @9e1758eb (post-release v1, fresh hook + fixed runners). AI-2: checkout the branch in VictoriousAI/AI-2, implement, SELF_CHECKED + DONE per protocol.
