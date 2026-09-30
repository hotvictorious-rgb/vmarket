# Ticket: VM-GOV-002

Ticket ID:            VM-GOV-002
Title:                Runner honesty: real flutter analyze or honest N/A (end synthetic PASS)
Type:                 GOVERNANCE
Status:               IN_PROGRESS
Blocked:              no
Created by / date:    FRONTEND AI / 2026-09-30 (external v1 audit finding, re-verified in-script)
Size estimate:        ~70 lines (runner only; no product code)

Business requirement:
Every recorded suite status must be true: executed-and-green, failed, or honestly N/A.
Problem:
`scripts/tests/run-all.ps1` recorded 10 non-executed suites as PASS with justification text (`customer/vendor/operations_frontend`, `integration`, `e2e`, `regression`, `performance`, `client_compat`, `license`, `build`). PASS means executed-and-green; these were not executed. False confidence is worse than fewer suites.
Expected behavior:
- The 3 Flutter suites execute `flutter analyze --no-pub` live (FAIL only on analyzer errors; warnings/infos recorded, non-blocking; SDK-missing/timeout → N/A with justification + approver).
- Suites with no live executor here (`integration`, `e2e`, `regression`, `performance`, `client_compat`, `license`, `build`) record N/A with explicit "NOT EXECUTED" justification + approver (gate-accepted). Each carries a note to replace with real execution where the capability exists.
Forbidden behavior:
- NEVER record PASS for a non-executed suite. NEVER fail releases on legacy lint warnings (errors only).
Affected systems:     scripts/tests/run-all.ps1 only
Tier / area:          C (governance)
Contract impact:      no (results schema unchanged: same 17 names, same fields)
Migration impact:     no
Data impact:          no
Documents updated:    none - justify (runner-internal comment documents the rule)
Assigned AI:          FRONTEND AI (scripts change under explicit human "fix all" order; HUMAN-role commit)
Required reviewers:   REVIEWER AI
Branch / base commit: frontend/VM-GOV-002 (from origin/v1 @0c2b04ab)
Tests required:       PSParser 0 errors; live flutter smoke on User app (warnings recorded, 0 errors); next release JSON shows real flutter + N/A mix.
Acceptance criteria:
- [ ] No `"status" = "PASS"` without execution in script (evidence: grep + parse).
- [ ] Live flutter analyze wired with error-only FAIL (evidence: smoke log).

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS.
Screenshots:          N/A

Implementation notes:
- flutter 3.47.4 present; User-app smoke: analyzer runs, warnings/infos only, 0 errors.
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-30  FRONTEND AI  BACKLOG -> IN_PROGRESS  Filed from external-audit adjudication; executing immediately.
