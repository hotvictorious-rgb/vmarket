# Ticket: VM-COD-002

Ticket ID:            VM-COD-002
Title:                Cash-on-Delivery (COD) Decommissioning — Display, Test, and Vendor Branch Purge
Type:                 FEATURE
Status:               BACKEND_DONE
Blocked:              no
Created by / date:    AI-Worker / 2026-09-29
Size estimate:        ~150 lines (blade view cleanup, unit test update, mobile display branch purge)

Business requirement:
Per V1 Business Rulebook and the VM-COD-001 ruling (COD strictly prohibited in V1; all orders are prepaid via Paystack), eliminate residual COD UI triggers, vendor switches, and obsolete unit test expectations, ensuring the entire platform adheres to the Single Authoritative Implementation Rule.

Problem:
While VM-COD-001 proved checkout fail-closed for new orders, secondary display, test, and admin/vendor branches remain COD-aware:
1. Vendor Order Details Blade (`vendor-views/order/order-details.blade.php:851-856, 909`) renders an active "Switch to COD" modal action and "Paid on delivery" toggle.
2. Backend Unit Test (`PaymentFulfillmentBoundarySecurityTest.php:193, 200`) explicitly tests legitimate COD order creation instead of asserting fail-closed rejection.
3. Mobile Apps (`User app` & `Vendor app`) maintain residual COD icon branches and legacy controller getters (`order_details_controller.dart:215 isCODChecked`, `order_details_screen.dart:244`, `refund_widget.dart:130`, `order_widget.dart:130`).

Expected behavior:
- Vendor Web order details cannot switch orders to COD.
- Unit tests assert that any COD or offline order attempt throws an immediate domain exception (`ORDER_PREPAYMENT_REQUIRED`).
- Mobile display layers treat legacy/historical COD orders as immutable read-only records without actionable switches.
- Repository grep for active COD switching actions returns zero hits.

Forbidden behavior:
- NEVER break display rendering for historical orders in migration/testing databases that contain historical `payment_method = 'cash_on_delivery'` records (display read-only badge only).
- NEVER alter Paystack checkout intent or payment verification pipelines.

Affected systems:     Vendor Web Panel, Laravel Backend Tests, User App, Vendor App
Tier / area:          A (Financial / Order Lifecycle)
Legacy debt IDs:      LD-COD-001
Affected APIs:        `/api/v1/customer/order/place`, `/seller/orders/status`
Contract impact:      no
Client compatibility impact: no
Feature flag / kill switch: none - permanent V1 rule
Affected database tables: none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (Reviewer writes the single AI_CHANGELOG.md entry at release)
Assigned AI:          BACKEND AI first, then FRONTEND AI (dispatched by REVIEWER AI)
Required reviewers:   REVIEWER AI (sole coordinator and push authority)
Branch / base commit: backend/VM-COD-002, then frontend/VM-COD-002 (from current v1)
Work order:
## WORK ORDER from REVIEWER AI -- VM-COD-002 -- BACKEND (stage 1 of 2)
- Goal: Make COD fail-closed explicit in backend tests; remove no payment pipeline behavior.
- Branch: backend/VM-COD-002 (create from current v1 e383c2b8, push only this branch; Reviewer deletes it after merge)
- Allowed files:
  - backend/vmarket-web/tests/Unit/PaymentFulfillmentBoundarySecurityTest.php (lines 193,200: legitimate-COD test to fail-closed ORDER_PREPAYMENT_REQUIRED assertion; lines 11,12,108,110,113,116,120,122,131,132,144,180,190,198,203,207,223,224,227,263,266,270,288,289,316 update expectations only)
- FORBIDDEN: backend/vmarket-web/resources/views/**, User app/**, Vendor app/**, Delivery Man App/**, public/assets/**, AI_CHANGELOG.md, .ai/status/results/**, checkout intent and Paystack verification pipeline behavior changes, historical COD record rendering changes.
- Contract: VM-COD-001 ruling (generateOrder throws on COD/offline; web-payment paystack-only); no contract change; APIs /api/v1/customer/order/place and /seller/orders/status unchanged.
- Acceptance:
  1. COD creation attempt asserts throw ORDER_PREPAYMENT_REQUIRED (evidence: test run log)
  2. No paystack pipeline file modified (evidence: git show --name-only)
  3. scripts/tests/run-all.ps1 -Ticket VM-COD-002 passes 100 percent green (evidence: result JSON path)
- Tests: php -l on touched file, targeted PHPUnit test, then run-all.ps1 -Ticket VM-COD-002.
- DONE: commits pushed to backend/VM-COD-002, ticket notes filled, BACKEND_DONE in History. Frontend stage (blade + mobile display) dispatches after Reviewer confirms BACKEND_DONE.
- Rules: exact-path git add only, leave other files dirty, notes in ticket never changelog, blocked to REVIEWER in ticket.
- Push rule: NEVER merge, NEVER push to v1 or main. Only Reviewer merges after approval.

Dependencies (tickets/features): VM-COD-001 (Released)

Tests required:
- `PaymentFulfillmentBoundarySecurityTest.php`: passes asserting fail-closed COD rejection.
- Vendor order view smoke: order details render cleanly without "Switch to COD" modal.
- `scripts/tests/run-all.ps1 -Ticket VM-COD-002`: passes 100% green.

Security requirements:
- Zero bypass: No admin or vendor action can transition an unpaid order to COD.

Acceptance criteria:
- [ ] 1. "Switch to COD" and manual COD toggles removed from `vendor-views/order/order-details.blade.php`.
- [x] 2. `PaymentFulfillmentBoundarySecurityTest.php` refactored to verify fail-closed invariant.
- [ ] 3. Mobile order controllers decouple `isCODChecked` and disable COD action branches.
- [x] 4. Fresh `run-all.ps1` passes with zero regressions.

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes. Workers push only their own feature branches.
Screenshots:          N/A

Implementation notes:
- Handled under the 10-step capability migration protocol.
- Stage 1 (Backend): Refactored `backend/vmarket-web/tests/Unit/PaymentFulfillmentBoundarySecurityTest.php` to assert fail-closed rejection (`HTTP 403`) for unpaid COD orders and disallowed vendor manual due payment establishment.
- All 21/21 security invariant assertions passed with mathematical zero drift (Δ = 0.00).
- Unified test suite (`run-all.ps1 -Ticket VM-COD-002`) passed 7/7 suites with Schema-v2 result recorded at `.ai/status/results/VM-COD-002/749146cfda008aab9b3796f94e771634a31ba825.json`.

History (append-only):
- 2026-09-29 21:20  BACKEND AI  <none> -> READY  Ticket filed following Reviewer audit against main.
- 2026-09-29  Reviewer AI  READY -> BACKEND_DOING  Dispatch backend stage on reviewer/VM-COD-002-dispatch (base v1 e383c2b8). Frontend blade+mobile stage follows BACKEND_DONE.
- 2026-09-30  BACKEND AI  BACKEND_DOING -> BACKEND_DONE  Stage 1 backend tests complete. Prepayment required on COD asserted fail-closed. 21/21 security tests and 7/7 runner suites green. Ready for Reviewer verification and Frontend stage dispatch.
