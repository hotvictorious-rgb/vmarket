# Ticket Template (Appendix A)

Ticket ID:            VM-PAY-003
Title:                Fee boundary float casts violate decimal-string contract (2 sites)
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-29 (found during doc-alignment pass; contract vs code mismatch)
Size estimate:        xs (string formatting at 2 return sites + contract test)

Business requirement:
Problem:            The API money contract requires exact decimal strings, but `GeographyController` and `FulfillmentAvailabilityService` cast `fee` to `(float)` at the response boundary. At ₦500/₦1,000 values the drift is invisible, but the contract is violated and downstream BCMath consumers receive floats.
Expected behavior:  Both sites return `"fee": "1000.00"` (string, 2dp) sourced from the stored decimal without float transit.
Forbidden behavior: No ledger/settlement logic changes. No other files. No backend beyond the 2 sites, no Flutter/Blade/assets, Control Zone, results/reviews/changelog.
Affected systems:     geography/fulfillment fee responses
Tier / area:          B (contract integrity; money-adjacent)
Affected APIs:        geography + fulfillment fee endpoints (response shape only)
Contract impact:      yes (brings responses INTO contract compliance)
Documents updated:    none - justify (report lives in review file)
Assigned AI:          BACKEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-PAY-003 (from current `v1`)
Work order:
- **Goal:** Return fee as decimal string at both boundary sites with a contract assertion.
- **Branch:** `backend/VM-PAY-003` from current `v1`.
- **Allowed files:** the 2 return sites + a contract assertion (extend existing suite, no new harness); own ticket notes.
- **FORBIDDEN:** everything else.
- **Acceptance:** (1) live responses show string fees (evidence: response bodies); (2) assertion guards the shape; (3) suites green.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-PAY-003` full HEAD (JSON uncommitted, report counts); push; BACKEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; testing sqlite + sandbox only.
Dependencies (tickets/features): VM-PAY-001 (belongs to the money-proof family)
Tests required:       response-shape proof; suites green; run-all JSON at full SHA
Acceptance criteria:
- [ ] String fees live (evidence: bodies)
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-29  Reviewer AI  BACKLOG (filed)  From VM-DOC-ALIGN-001 row 7.
