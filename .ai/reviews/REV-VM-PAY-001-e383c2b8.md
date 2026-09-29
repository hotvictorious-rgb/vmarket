# Review: VM-PAY-001 (money-path audit)

Ticket: VM-PAY-001 (Money-Path Audit - Paystack live keys, webhook, intent freeze, cashback ledger)
Reviewer: REVIEWER AI (sole gatekeeper)
Commit reviewed: e383c2b8 (v1 HEAD examined; no worker branch exists - see below)
Review cycle: 1
Files reviewed: .ai/tickets/review/VM-PAY-001.md; branch inventory; .ai/status/results/VM-PAY-001 (absent); .ai/reviews/customer/REV-VM-PAY-001-money-path-audit.md (name non-conforming, content not re-verified this cycle)

Tests run by reviewer: none (no SHA-bound artifact to execute; gate prerequisites unmet - review means run, so this is CHANGES_REQUIRED, not approval).

Findings:
- Gate FAIL 1: Status is REVIEW, must be INTEGRATION_TESTING, RELEASE_CANDIDATE, or REVIEW_APPROVED (verify-release-gate.ps1).
- Gate FAIL 2: no worker branch backend/VM-PAY-001 exists; only reviewer/VM-PAY-001-audit. Nothing mergeable via merge-release.
- Gate FAIL 3: no .ai/status/results/VM-PAY-001/<SHA>.json (17-suite result missing).
- Gate FAIL 4: review file is not REV-VM-PAY-001-<fullSHA>.md with Decision: APPROVED.
- Note: 42/42 audit assertions claimed in ticket notes are unbound to a commit; cannot gate on prose.

Decision: CHANGES_REQUIRED

Exact fix order for BACKEND AI:
1. git checkout -b backend/VM-PAY-001 origin/v1.
2. Re-run scratch/test_money_path_paystack_audit.php on that SHA (sandbox only, no live charges, no credentials in logs).
3. Produce scripts/tests/run-all.ps1 -Ticket VM-PAY-001 result JSON at .ai/status/results/VM-PAY-001/<SHA>.json (17/17 suites).
4. Fill ticket Implementation notes + set Status to REVIEW_APPROVED (only when 1-3 are pushed to backend/VM-PAY-001).
5. Report DONE in ticket History; Reviewer re-reviews at exact SHA and runs verify-release-gate before any merge.

Boundary: backend audit scope only; no frontend changes under this ticket.
