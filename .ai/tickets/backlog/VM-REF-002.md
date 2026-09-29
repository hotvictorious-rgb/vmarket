# Ticket Template (Appendix A)

Ticket ID:            VM-REF-002
Title:                Manual-only refunds (DECISION-REFUND-001 approved)
Type:                 FEATURE
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-29 (human ruling: Approved ≠ Refunded)
Size estimate:        small (gate the automatic path behind explicit flag + record reference/proof)

Business requirement:
Problem:            Approving a refund today auto-executes Paystack refunds (`PaystackRefundService` async machine), but V1 policy is manual: approval records the decision, the operator executes externally and records reference/proof. (Also needs VM-PAY-002's accrual fix if automatic ever returns.)
Expected behavior:  Approval path stops at decision-recorded + reference/proof fields; automatic execution sits behind an explicit disabled-by-default flag for future rollout. No silent money movement.
Forbidden behavior: No deletion of the async machinery (flagged, not removed). No behavior change to request/review flows. No Flutter/Blade/assets, Control Zone, results/reviews/changelog.
Affected systems:     Admin refund approval, PaystackRefundService trigger
Tier / area:          A (money movement)
Affected APIs:        admin refund actions (behavior only)
Contract impact:      no
Documents updated:    none - justify (report lives in review file)
Assigned AI:          BACKEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-REF-002 (from current `v1`)
Work order:
- **Goal:** Gate automatic refund execution behind an explicit flag; approval records decision + reference/proof.
- **Branch:** `backend/VM-REF-002` from current `v1`.
- **Allowed files:** admin refund approval path + trigger gate only; own ticket notes.
- **FORBIDDEN:** machinery deletion, request/review flows, Flutter/Blade/assets, Control Zone, results/reviews/changelog.
- **Acceptance:** (1) approve → decision recorded, zero Paystack calls (evidence: sandbox log + HTTP mock/spy); (2) flag-on restores current behavior (evidence: same proof); (3) suites green.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-REF-002` full HEAD (JSON uncommitted, report counts); push; BACKEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; testing sqlite + sandbox only.
Dependencies (tickets/features): DECISION-REFUND-001 (approved); VM-PAY-002 (accrual basis if automatic returns)
Tests required:       no-auto-call proof; flag-on parity proof; suites green; run-all JSON at full SHA
Acceptance criteria:
- [ ] Approval moves no money by default (evidence: sandbox proof)
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-29  Reviewer AI  BACKLOG (filed)  Human manual-refund ruling.
