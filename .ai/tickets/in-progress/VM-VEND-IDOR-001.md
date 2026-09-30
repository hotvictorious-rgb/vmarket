# Ticket: VM-VEND-IDOR-001

Ticket ID:            VM-VEND-IDOR-001
Title:                Zero-trust user_id scoping on vendor payout methods (DISC-01 IDOR elimination)
Type:                 SECURITY
Status:               REVIEW_APPROVED
Blocked:              no
Created by / date:    BACKEND AI / 2026-09-29 (DISC-01 discovery; completed 2026-09-30)
Size estimate:        small (5 controller methods + proof)

Business requirement:
No vendor may view or mutate another vendor's payout methods (bank rails = theft target).
Problem (DISC-01):
`VendorPaymentInfoController` (Web: getUpdateView, updateDefault, updateStatus) and `RestAPI/v3/seller/VendorPaymentInfoController` (updateDefault, updateStatus) mutated `vendor_withdraw_method_info` rows by raw `id` without ownership scoping — hostile Vendor A could flip Vendor B's default/active payout method.
Expected behavior:
- Every mutation first loads `['id' => request id, 'user_id' => seller id]`; miss → 403 `Unauthorized_or_payment_method_not_found`; writes carry `user_id` scope.
Forbidden behavior:
- NEVER trust route/body `id` alone for owned resources.
Affected systems:     Laravel Backend (Web + API payout controllers)
Tier / area:          A (security, money-adjacent)
Affected APIs:        vendor payout-method update/default/status (Web + /api/v3/seller/*)
Contract impact:      no (403 shape on hostile calls; happy paths unchanged)
Migration impact:     no
Data impact:          no
Documents updated:    none - justify (guard fix)
Assigned AI:          BACKEND AI
Required reviewers:   REVIEWER AI
Branch / base commit: backend/VM-VEND-IDOR-001 (synced on origin/v1 @0c2b04ab)
Tests required:       hostile cross-tenant proof 9/9; runner green.
Acceptance criteria:
- [x] Hostile default/status mutations → 403, victim rows unchanged (evidence: scratch/test_vendor_payout_idor_fix.php 9/9 re-executed live 2026-09-30).
- [x] Legitimate owner flows 200 (evidence: same run).

Counters:             review_cycles: 1   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS.
Screenshots:          N/A

Implementation notes:
- Fix 1a4c2243 (2026-09-29) + sync 7f01aa80 + ticket in this release.
Review notes:
- Cycle 1 (REVIEWER AI): fix diff audited (ownership-first pattern on all 5 methods); proof re-executed live 9/9. APPROVED.
Final decision:
- APPROVED for v1 merge; ticket closes to released/.
Release commit:
- Pending gate PASS + merge-release.

History (append-only):
- 2026-09-29  BACKEND AI  BACKLOG -> IN_PROGRESS  DISC-01 filed; fix implemented + 9/9 proven.
- 2026-09-30  REVIEWER  IN_PROGRESS -> REVIEW_APPROVED  Re-verified live 9/9 on synced tree; release proceeds.
