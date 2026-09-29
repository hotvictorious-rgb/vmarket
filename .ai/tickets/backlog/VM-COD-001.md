# Ticket Template (Appendix A)

Ticket ID:            VM-COD-001
Title:                COD fail-closed enforcement at entry points (DECISION-COD-001 approved)
Type:                 FEATURE
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-29 (human ruling: COD prohibited in V1)
Size estimate:        small (guards only; branch deletion ships in VM-COD-002)

Business requirement:
Problem:            82 COD-related code hits remain across controllers/reports/riders. Regardless of later deletion, COD must be IMPOSSIBLE today: any `payment_method=cash_on_delivery|offline_payment` reaching checkout intent, payment init, or order creation must fail closed with 403/`InvalidPaymentMethodException` (pattern exists in `OrderManager::generateOrder` + web `PaymentController` paystack-only gate — extend to every entry).
Expected behavior:  Probe matrix (intent/pay/init/order-create × COD/offline payloads) all rejected; prepaid flows byte-identical.
Forbidden behavior: No branch deletion here (VM-COD-002). No behavior change to prepaid paths. No Flutter/Blade/assets, Control Zone, results/reviews/changelog.
Affected systems:     checkout intent, payment init, order creation guards
Tier / area:          A (money-path integrity)
Affected APIs:        intent/pay/order-create endpoints (validation only)
Contract impact:      no
Documents updated:    none - justify (report lives in review file)
Assigned AI:          BACKEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-COD-001 (from current `v1`)
Work order:
- **Goal:** Fail closed on any COD/offline payment_method at every money entry point, proven by hostile probe matrix.
- **Branch:** `backend/VM-COD-001` from current `v1`.
- **Allowed files:** entry-point validators/guards only (intent service, payment init, order-create paths); own ticket notes.
- **FORBIDDEN:** branch deletion, prepaid logic, Flutter/Blade/assets, Control Zone, results/reviews/changelog.
- **Acceptance:** (1) probe matrix all-403 with file:line per guard; (2) prepaid regression walk green; (3) suites green.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-COD-001` full HEAD (JSON uncommitted, report counts); push; BACKEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; testing sqlite + sandbox only.
Dependencies (tickets/features): DECISION-COD-001 (approved)
Tests required:       hostile COD/offline matrix; prepaid regression; suites green; run-all JSON at full SHA
Acceptance criteria:
- [ ] COD/offline rejected at every entry (evidence: matrix)
- [ ] Prepaid paths unchanged (evidence: regression walk)
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.

Implementation notes:
- Full hit list (82): `cod-hits.txt` method (re-run at dispatch; list rots fast).
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-29  Reviewer AI  BACKLOG (filed)  Human COD-prohibition ruling. Guards first, deletion second.
