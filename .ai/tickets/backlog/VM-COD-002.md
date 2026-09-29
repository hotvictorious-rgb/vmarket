# Ticket Template (Appendix A)

Ticket ID:            VM-COD-002
Title:                Delete dead COD branches per file (DECISION-COD-001 approved)
Type:                 FEATURE
Status:               BACKLOG
Blocked:              yes (runs AFTER VM-COD-001 releases — guards must be live before branches die)
Created by / date:    Reviewer AI / 2026-09-29
Size estimate:        medium (split per area if over 400 lines: reports / seller / rider / refund-legs)

Business requirement:
Problem:            After VM-COD-001 enforcement lands, ~82 COD references (reports, seller controllers, rider wallet cash paths, refund legs, `collected_cash`/`cash_in_hand` writes) become unreachable dead weight that invites future misuse. Delete per file with proof each deletion is unreachable-behind-guard.
Expected behavior:  Zero `cash_on_delivery|CashOnDelivery|COD\b` code paths reachable; `collected_cash`/`cash_in_hand` writes removed (columns stay — no migrations); rider wallet purely digital.
Forbidden behavior: No behavior change to live paths (deletion only, verified unreachable first). No migrations (columns remain). No Flutter/Blade/assets in this ticket (UI purge separate if needed), Control Zone, results/reviews/changelog.
Affected systems:     reports, seller/vendor controllers, rider wallet, refund legs
Tier / area:          A (money-path hygiene)
Affected APIs:        none (deletion only)
Contract impact:      no
Documents updated:    none - justify (report lives in review file)
Assigned AI:          BACKEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-COD-002 (from post-VM-COD-001 `v1`)
Work order:
- **Goal:** Delete unreachable COD branches file by file with reachability proof each.
- **Branch:** `backend/VM-COD-002` from post-VM-COD-001 `v1`.
- **Allowed files:** backend PHP dead-branch deletions only; own ticket notes.
- **FORBIDDEN:** live-path changes, migrations, Flutter/Blade/assets, Control Zone, results/reviews/changelog.
- **Acceptance:** (1) per-file unreachable proof (guard trace); (2) grep-zero for live COD paths; (3) suites green.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-COD-002` full HEAD (JSON uncommitted, report counts); push; BACKEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; testing sqlite + sandbox only.
Dependencies (tickets/features): VM-COD-001 (guards live first)
Tests required:       reachability proofs; suites green; run-all JSON at full SHA
Acceptance criteria:
- [ ] Dead branches deleted with proof each
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-29  Reviewer AI  BACKLOG (filed, blocked)  Runs after VM-COD-001.
