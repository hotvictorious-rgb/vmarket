# Ticket: VM-CUST-005

Ticket ID:            VM-CUST-005
Title:                Customer Address LGA Widget Proof — Country-State-LGA Cascade
Type:                 FEATURE
Status:               RELEASED
Blocked:              no
Created by / date:    AI-8 / 2026-09-25
Size estimate:        ~250 lines (1 test file + fixtures)

Business requirement:
Prove canonical address selection (Country -> State -> LGA) works in the User App with widget tests.
Problem:
Address cascade (`add_new_address_screen`, `country_search_dialog`, `geography_models`) has no widget proof; only LGA model init is unit-tested. Mismatched LGA risk is unguarded on the client.
Expected behavior:
- Country -> State -> LGA cascade renders; selecting Nigeria -> Akwa Ibom offers Uyo (142), Eket (125), Ikot Ekpene (133), Oron (140), Abak (118), Ikot Abasi (132).
- Mismatched State/LGA selection is rejected client-side with clear message (backend still revalidates).
- Save/list/delete operate on own addresses only (mocked `address_id` ownership).
- Free-text address + optional lat/lng captured; hubs/routes never shown as geography.
Forbidden behavior:
- NEVER expose delivery hubs, routes, or dispatch zones as geography.
- NEVER trust client-sent `origin_lga_id` or fee as authoritative.
- NEVER use legacy Hub/Zone/City/Zip widgets.
Affected systems:     User App (Flutter)
Tier / area:          B (Customer App widgets)
Legacy debt IDs:      none
Affected APIs:
- GET /api/v1/geography/countries (mocked)
- GET /api/v1/geography/states/{id} (mocked)
- GET /api/v1/geography/lgas/{id} (mocked)
- POST /api/v1/customer/address/add (mocked contract)
- GET /api/v1/customer/address/list (mocked contract)
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
Branch / base commit: frontend/VM-CUST-005 (from v1 HEAD)
Dependencies (tickets/features): VM-CUST-004
Tests required:
- `User app/test/address_lga_test.dart`: cascade render, valid select, mismatched reject, save payload shape `{country, state, lga, address}` with no fee fields.
- `flutter test` green; `flutter analyze` clean.
Security requirements:
- No cross-customer `address_id` use in fixtures; ownership negative test included.
Acceptance criteria:
- [x] 1. Valid Nigeria -> Akwa Ibom -> Uyo cascade passes (evidence: test log).
- [x] 2. Mismatched State/LGA rejected with message (evidence: test log).
- [x] 3. Save payload contains no fee/origin fields (evidence: test log).
- [x] 4. `flutter test` + `flutter analyze` clean (evidence: runner log).

Counters:             review_cycles: {AI5: 2, AI6: 0, AI7: 0}   integration_failures: 0   reopened_count: 0
Screenshots:          N/A

Implementation notes:
1. Implemented production mismatch guard in `AddressController`: `isStateLgaMatched()` and `setSelectedLga(LgaModel? lga)` reject state/LGA mismatches, reset selectedLga to null, and expose descriptive `geographyErrorMessage`.
2. Added client-side validation guard in `AddNewAddressScreen` before submit to prevent dispatching mismatched state/LGA pairs.
3. Created test suite at canonical path `User app/test/address_lga_test.dart` covering 5 comprehensive scenarios:
   - Valid Nigeria -> Akwa Ibom -> Uyo cascade (including Abak, Eket, Ikot Abasi, Ikot Ekpene, Oron).
   - Client-side rejection of mismatched State/LGA with clear error message.
   - Strict payload shape assertions verifying `{country_id, state_id, lga_id, address}` with ZERO fee/shipping/origin_lga fields.
   - Zero-Trust IDOR ownership scoping on list, add, and delete operations.
   - Free-text address and coordinate capture without exposing delivery hubs as geography.
4. All 18 frontend customer tests pass in `scripts/tests/run-frontend-tests.ps1`; `flutter analyze` clean (0 issues).

Review notes:
- 2026-09-25: AI-5 cycle 1 review against commit ca23e6e940d9c72be6de287ec20c815cfdd6abab. Decision: CHANGES_REQUIRED. The test passes synthetically by implementing the mismatch guard in the test harness instead of production code. The file was also placed at the wrong path.
- 2026-10-02: AI-5 cycle 2 review: Mismatch guard implemented directly in AddressController and AddNewAddressScreen production code; canonical test suite placed at `User app/test/address_lga_test.dart`; all 18 customer tests pass; flutter analyze clean. Decision: APPROVED.
Final decision:
RELEASE_CANDIDATE
Release commit:
Pending Reviewer merge.

History (append-only):
- 2026-09-25  AI-8  BACKLOG -> READY  Ticket created per human approval for frontend journey proof project
- 2026-10-02  AI-2  CHANGES_REQUIRED -> IN_PROGRESS -> RELEASE_CANDIDATE  Production mismatch guard added; tests implemented at canonical path; 18/18 green; analyze clean.
