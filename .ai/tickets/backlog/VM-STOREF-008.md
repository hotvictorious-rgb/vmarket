# Ticket Template (Appendix A)

Ticket ID:            VM-STOREF-008
Title:                Digital OTP client-only timer + duplicate binds + silent errors
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-27 (triaged from scratch/storefront-ticket-drafts/VM-STOREF-008-DRAFT.md deep-scan batch; filed to BACKLOG, no dispatch — queued behind reroutes, triage position 2/10)
Size estimate:        medium

Business requirement:
Problem:            Handover/delivery OTP must enforce 6-digit + 15-min + 5-attempt server-side; client is display only. tracking.js:24-43,156-173 countdown is pure setTimeout (DevTools bypass); otp length hardcoded 4 in two places (:75) with no attempt/lockout UI; :185+44 double-bind click → multi-POST; paste never enables submit; error callbacks :51,128 empty and resend has none; verify-otp.js:1-26 100% comments (dead).
Expected behavior:  Server enforces expiry/attempts (verified); client shows timer from server new_time, disables resend until 0:00, single-bound submit, paste triggers state, all errors toast + no message wipe on fail.
Forbidden behavior: No client OTP generation/validation; no 4-digit fallback; no removal of server checks.
Affected systems:     customer/storefront-web, backend/core-api
Tier / area:          A (auth/fulfillment integrity)
Legacy debt IDs:      none
Affected APIs:        digital download verify + otp-reset (existing; exact names pinned at dispatch)
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify
Affected database tables:     none (no schema)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify
Assigned AI:          BACKEND AI (prove server enforcement) then FRONTEND AI
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-STOREF-008 then frontend/VM-STOREF-008 (from v1 after reroutes land)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md` at dispatch)
Dependencies (tickets/features): reroutes merged; coordinate with VM-PAY-001 audit posture (OTP/handover path is pickup/delivery, PAY covers Paystack/intent/cashback — no duplicate fixes)
Tests required:       DevTools timer bypass still rejected server-side; 5-attempt lockout proven; single POST per click; failed send preserves draft + toast
Security requirements: exact identity match, 15-min bound, 5-attempt lockout; no OTP in logs/URLs
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] bypass attempt rejected (evidence: hostile probe log)
- [ ] single submit per click (evidence: network count)
- [ ] errors visible, drafts kept (evidence: offline simulation)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          OTP modal + network

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-27  Reviewer AI  BACKLOG (filed)  Triaged from scratch draft (tracking.js + verify-otp.js audit). PAY-001 coordination noted (distinct paths, no duplicate fixes). Queued behind reroutes; triage position 2/10.
