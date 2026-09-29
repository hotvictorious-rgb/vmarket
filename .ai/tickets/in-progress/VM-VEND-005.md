# Ticket Template (Appendix A)

Ticket ID:            VM-VEND-005
Title:                Vendor entity fields â€” business type, CAC, NIN, payout + verification audit (Phase 1)
Type:                 FEATURE
Status:               BACKEND_DONE
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-29 (Phase 1 of the vendor onboarding model; unblocks VM-VEND-004)
Size estimate:        medium (migration + fillable + registration acceptance; admin UI read-only follows)

Business requirement:
Problem:            `sellers` has no business taxonomy: no `business_type`, no legal/display name split, no CAC/NIN fields, no verification audit, payout bank columns sit on the seller row, and nothing records who verified what. The verification center (VM-VEND-004) has nothing to read.
Expected behavior:  Migration adds: `business_type` (individual/bn/ltd/other), `legal_name`, `cac_status` (pending/under_review/verified/rejected/expired), `cac_number`, `cac_document`, `nin_number`, `nin_document`, `verification_method/verified_by/verified_at/verification_notes`, `payout_status` (pending/verified/suspended). Models get `$fillable`; registration accepts + stores the new optional fields (validation: CAC number/document required ONLY when claiming registered status); existing rows backfill `individual`/`PENDING`. No flow gating in this ticket (gating ships in VM-VEND-004).
Forbidden behavior: No gating logic (anyone can still register; approval semantics unchanged). No bank-column moves yet (Phase 3). No prod data (migration + seed only).
Affected systems:     sellers table/model, vendor registration acceptance
Tier / area:          A (vendor onboarding foundation)
Affected APIs:        vendor registration (additive optional fields only)
Contract impact:      yes (additive request fields; documented)
Documents updated:    none - justify (report lives in review file)
Assigned AI:          BACKEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-VEND-005 (from current `v1`)
Work order:
- **Goal:** Land the entity columns + acceptance with backfill, zero gating change.
- **Branch:** `backend/VM-VEND-005` from current `v1`.
- **Allowed files:** new migration (timestamped, default fallbacks), `Seller` model (`$fillable` only), registration service validation/data-mapper (new optional fields only), seeder backfill if needed; own ticket notes.
- **FORBIDDEN:** gating/approval logic, bank moves, Flutter/Blade/assets, Control Zone, results/reviews/changelog.
- **Acceptance:** (1) `migrate` + `migrate:rollback` clean on testing sqlite; (2) registration with BN/Ltd/individual payloads stores all fields (evidence: DB rows); (3) conditional rules proven: CAC number+document required when type != individual, NIN required when individual, TIN stays nullable; (4) existing sellers read as individual/PENDING; (5) sell-gate inventory attached (publish/list/order endpoints enforcing approved+verified — code refs, no changes here); (6) suites green.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-VEND-005` full HEAD (JSON uncommitted, report counts); push; BACKEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; testing sqlite + sandbox only.
Dependencies (tickets/features): none (unblocks VM-VEND-004)
Tests required:       migration fresh+rollback; registration payload walk; suites green; run-all JSON at full SHA
Security requirements: NIN/CAC document paths never serialized to public payloads (scope test)
Acceptance criteria:
- [ ] Migration + rollback clean
- [ ] BN/Ltd/individual payloads persist correctly
- [ ] Conditional CAC/NIN rules proven
- [ ] Sell-gate inventory attached
- [ ] Backfill correct, no flow change
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human â†’ REVIEWER AI â†’ BACKEND AI â†’ REVIEWER AI (APPROVED) â†’ REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-29  Reviewer AI  BACKLOG (filed)  Phase 1 of onboarding model. Unblocks VM-VEND-004 checklist.
- 2026-09-29  Human ruling (policy, recorded by Reviewer AI)  (1) NO unregistered selling: only approved + verified vendors sell; registration stays open to all (apply â†’ verify â†’ approve â†’ list). (2) TIN deferred (nullable at onboarding; required later per compliance rollout). (3) No auto-approve tiers: every vendor admin-reviewed (current pending flow stays). CAC number/document REQUIRED at application (no unregistered selling means no unverified path to market).
- 2026-09-29  Backend AI  BACKLOG -> BACKEND_DONE (single-coordinator session; RUNTIME CORRECTED mid-ticket: worktree vendor/ was a junction into the user checkout, so early walks executed foreign code — junction replaced with real copy, class identity re-proven by ReflectionClass, all walks below re-ran green on own code)  Migration (fresh+rollback+rerun clean) + Seller fillable + conditional rules (CAC iff non-individual, NIN iff individual, TIN nullable) + getAddData mapper. Walks on :8000 own code: BN+CAC -> status 1 row id 14 fully stored; individual+NIN -> status 1; BN w/o CAC doc rejected; individual w/o NIN rejected. Sell-gate inventory: SellerMiddleware approved-only, API pending 401, marketplacePurchasable approved-seller, publish scopes approved. branch=backend/VM-VEND-005.
