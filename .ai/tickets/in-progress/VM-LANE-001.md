Ticket ID:            VM-LANE-001
Title:                Canonical Geography & Directional Delivery Lane Routing Engine
Type:                 FEATURE
Status:               SELF_CHECKED
Blocked:              no
Created by / date:    AI-8 / 2026-09-24 13:45 UTC
Size estimate:        ~250 lines (Within Section 29.2 limit of <= 400 lines)

Business requirement:
Enforce canonical hierarchical geography (Country -> State -> LGA) and calculate exact directional delivery fees and transit estimates via server-side DeliveryLane records (Origin LGA -> Destination LGA -> DeliveryLane). No client-side delivery fee calculation is permitted.

Problem:
Customer checkout and vendor fulfillment must deterministically resolve origin shop LGA and customer shipping destination LGA to calculate accurate directional delivery fees and prevent fulfillment drift.

Expected behavior:
1. Public API endpoints provide canonical geography lists: Country -> State -> LGA.
2. Endpoint `POST /api/v1/shipping-method/calculate-lane-fee` accepts `origin_lga_id` and `destination_lga_id`, returning the exact delivery fee, estimated delivery days, and active lane status.
3. If no active directional lane exists between the two LGAs, backend returns a machine-readable error `LANE_NOT_SERVICEABLE`.
4. Order checkout intent binds the exact resolved DeliveryLane fee to the order snapshot.

Forbidden behavior:
- Client applications must NEVER calculate or override delivery lane fees.
- Fuzzy matching or unbounded SQL queries on geography names are forbidden.
- Zero floating-point math in fee calculations; store and calculate as fixed-precision decimals.

Affected systems:     backend/geography-lanes (per SCOPE.md)
Tier / area:          A (per GATED_AREAS.md)
Legacy debt IDs:      none
Affected APIs:        GET /api/v1/shipping-method/countries, GET /api/v1/shipping-method/states, GET /api/v1/shipping-method/lgas, POST /api/v1/shipping-method/calculate-lane-fee, POST /api/v1/geography/calculate-lane-fee
Contract impact:      yes
Client compatibility impact:  no
Feature flag / kill switch:   none - core fulfillment architecture
Affected database tables:     delivery_lanes, lgas, states, countries
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    .ai/API_CONTRACT.md
Assigned AI:          AI-1
Required reviewers:   AI 5, AI 6, AI 7, plus human sign-off
Branch / base commit: ai1/VM-LANE-001 / 03b08849
Dependencies (tickets/features): none
Tests required:       Unit test for lane resolution, contract test for calculate-lane-fee endpoint, boundary tests for invalid LGA IDs.
Security requirements: Zero-trust input validation on LGA IDs; rate-limited quote calculation.
Acceptance criteria:
- [x] Canonical LGA endpoints return active Nigerian states and LGAs.
- [x] Directional lane lookup calculates fee with zero drift (Delta = 0.00).
- [x] Inactive or non-existent lanes return 422 with LANE_NOT_SERVICEABLE code.
- [x] Automated contract and feature tests pass 100%.
- [x] Runner-generated Schema-v2 JSON result recorded at .ai/status/results/VM-LANE-001/03b088491a10d8b3e18e059c7cae84ea705f48bb.json.

Counters:             review_cycles: {AI5: 0, AI6: 0, AI7: 0}   integration_failures: 0   reopened_count: 0
Screenshots:          N/A (Backend API)

Implementation notes:
- Implemented `calculateLaneFee` in `GeographyController` with zero-trust validation for `origin_lga_id` and `destination_lga_id`.
- Enforces `DeliveryLane::findLane()` lookup returning exact decimal fee, estimated transit days, and active status.
- Returns standard error response with code `LANE_NOT_SERVICEABLE` (HTTP 422) if lane does not exist or is disabled.
- Added route aliases in `routes/rest_api/v1/api.php` under both `/api/v1/geography/` and `/api/v1/shipping-method/`.
- Authored `DeliveryLaneRoutingInvariantTest.php` with 8 comprehensive unit invariant tests covering directional asymmetry, zero drift, intra-LGA rates, negative fee rejection, and LGA scoping.
- Runner-generated Schema-v2 test result recorded with SHA-256 cryptographic log hashes.

Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-24 13:45 UTC  AI-8  BACKLOG -> READY      Requirement specified and verified against Roadmap §11A
- 2026-09-24 13:51 UTC  AI-8  READY -> IN_PROGRESS  Assigned to AI-1 following human authorization
- 2026-09-24 14:04 UTC  AI-1  IN_PROGRESS -> IMPLEMENTED  Implemented calculateLaneFee and unit invariant test suite
- 2026-09-24 14:04 UTC  AI-1  IMPLEMENTED -> SELF_CHECKED  Executed run-all.ps1; 100% tests passed; schema-v2 evidence attached
