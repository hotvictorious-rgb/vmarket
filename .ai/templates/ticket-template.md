# Ticket Template (Appendix A)

Ticket ID:            VM-<FEATURE>-NNN
Title:
Type:                 FEATURE | BUG | HOTFIX | CHANGE_REQUEST | DECISION_REQUEST
Status:               BACKLOG
Blocked:              no   (reason if yes; ESCALATED if a limit was hit)
Created by / date:
Size estimate:        (must satisfy Section 29.2; otherwise split)

Business requirement:
Problem:
Expected behavior:
Forbidden behavior:
Affected systems:     (must exist in SCOPE.md)
Tier / area:          A | B | C  (per GATED_AREAS.md)
Legacy debt IDs:      (if any N/A is claimed)
Affected APIs:
Contract impact:      yes | no
Client compatibility impact:  yes | no   (min supported version affected?)
Feature flag / kill switch:   (name, or "none - justify")
Affected database tables:
Migration impact:     yes | no
Data impact:          yes | no
Compliance impact:    yes | no
Dependency changes:   none | list with justification (Section 24.3)
Documents updated:    (list, or "none - justify")
Assigned AI:          BACKEND AI | FRONTEND AI   (dispatched by REVIEWER AI with an exact-prompt work order; backend first, frontend only after Reviewer confirms BACKEND_DONE)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-<FEATURE>-NNN | frontend/VM-<FEATURE>-NNN
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md`)
Dependencies (tickets/features):
Tests required:
Security requirements:
Acceptance criteria:  (checklist; each item gets an evidence link)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `main` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          (frontend tickets)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- YYYY-MM-DD HH:MM  <role>  <old state> -> <new state>  <reason>
