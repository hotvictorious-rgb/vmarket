# Ticket Template (Appendix A)

Ticket ID:            VM-VEND-004
Title:                Pending-vendor restricted dashboard (sign-in with awaiting-approval scope)
Type:                 FEATURE
Status:               BACKLOG
Blocked:              yes (needs Phase-1 entity fields ticket first — checklist has nothing to read yet)
Created by / date:    Reviewer AI / 2026-09-29 (human ruling: unverified vendors sign in to a restricted awaiting-approval dashboard)
Size estimate:        medium (auth scope + status endpoints + hostile tests; split if over 400 lines)

Business requirement:
Problem:            Pending vendors get a hard 401 with no visibility (`LoginController:52`, `SellerMiddleware:21` approved-only). They cannot see why they wait or what is missing.
Expected behavior:  Pending sellers authenticate and receive a token scoped to exactly one surface: awaiting-approval dashboard returning the verification checklist (Identity/Business/CAC/Payout/Store states from Phase-1 fields), missing-item upload links, support contact. All other seller endpoints keep requiring `approved` (verified hostilely per endpoint).
Forbidden behavior: No pending access beyond the status surface (no publish/orders/withdrawals/payouts). No weakening of existing approved gates. No prod data (sandbox only).
Affected systems:     Seller API auth, SellerMiddleware boundary, status endpoints
Tier / area:          A (vendor onboarding + auth security)
Affected APIs:        `POST api/v3/seller/auth/login` (pending scope), new `GET` status/checklist endpoints
Contract impact:      yes (login response gains scope/status for pending; documented, backward-compatible)
Documents updated:    none - justify (report lives in review file)
Assigned AI:          BACKEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-VEND-004 (from current `v1` AFTER Phase-1 fields release)
Work order:
- **Goal:** Scoped pending-vendor sign-in with a verification-checklist surface, all other gates intact.
- **Branch:** `backend/VM-VEND-004` from post-Phase-1 `v1`.
- **Allowed files:** seller auth controller (pending-token scope only), new status controller/endpoints, SellerMiddleware-adjacent scope guard (new middleware preferred over editing); own ticket notes.
- **FORBIDDEN:** approved-gate logic, all other controllers/services, Flutter/Blade/assets, Control Zone, results/reviews/changelog.
- **Acceptance:** (1) pending login returns scoped token + checklist (evidence: live log); (2) pending token hits publish/orders/withdraw/payout endpoints → 401/403 each (evidence: hostile matrix log); (3) approved flows byte-identical (evidence: regression walk); (4) suites green.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-VEND-004` full HEAD (JSON uncommitted, report counts); push; BACKEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; testing sqlite + sandbox only.
Dependencies (tickets/features): Phase-1 entity-fields ticket (checklist columns must exist)
Tests required:       hostile per-endpoint matrix; regression walk; suites green; run-all JSON at full SHA
Security requirements: least-privilege scope; pending surface read-only (no mutations except resubmission endpoints if explicitly scoped)
Acceptance criteria:
- [ ] Pending sign-in returns scoped token + checklist
- [ ] Pending token blocked everywhere else (matrix green)
- [ ] Approved flows unchanged

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-29  Reviewer AI  BACKLOG (filed, blocked)  Human awaiting-approval ruling. Waits on Phase-1 fields.
