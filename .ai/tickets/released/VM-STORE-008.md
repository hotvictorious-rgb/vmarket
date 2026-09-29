# Ticket Template (Appendix A)

Ticket ID:            VM-STORE-008
Title:                Phone country default Nigeria (not US) + drop duplicated default span
Type:                 FEATURE
Status:               RELEASED
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-29 (human ruling: Nigeria default on all phone forms)
Size estimate:        xs (1 file, -2/+1)

Business requirement:
Problem:            The only `system-default-country-code` source in the theme falls back to `'us'` (and is duplicated twice in `blog-layouts`). Nigerian marketplace must default NG (+234) wherever the picker initializes.
Expected behavior:  Single span, fallback `'ng'`. No other change (picker init remains unwired pending browser proof — VM-STORE-004).
Forbidden behavior: No init wiring. No other blades. No backend PHP, routes, Flutter, assets (no new files), Control Zone, results/reviews/changelog.
Affected systems:     Storefront blog layout head spans (global JS data layer)
Tier / area:          B (UX correctness)
Affected APIs:        none
Contract impact:      no
Documents updated:    none - justify (report lives in review file)
Assigned AI:          FRONTEND AI
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: frontend/VM-STORE-008 (from current `v1`)
Work order:           (executed same-session per human run-order)
Dependencies (tickets/features): VM-STORE-004 (picker stylesheet)
Tests required:       served-HTML proof (single span, ng fallback); run-all JSON at full SHA
Acceptance criteria:
- [ ] One default-country span with ng fallback (evidence: served HTML)
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.

Implementation notes:
Review notes: .ai/reviews/REV-VM-STORE-008-d5ed4b8df505e07b42720584b547b9d48df5cff4.md (Decision APPROVED; 17/17 at exact SHA)
Final decision: APPROVED → RELEASED as RELEASE-2026-09-29-007
Release commit: 529fdb0b (merge v1; feature d5ed4b8df505e07b42720584b547b9d48df5cff4)

History (append-only):
- 2026-09-29  Reviewer AI  BACKLOG (filed, executed same-session)  Human NG-default ruling.
- 2026-09-29  Frontend AI  BACKLOG -> BACKEND_DONE (single-coordinator session)  Dedupe + ng fallback, 1 file. branch=frontend/VM-STORE-008.
- 2026-09-29  Frontend AI  run-all 17/17 PASS at fix commit 7f23236d (7 executed + 10 justified).
- 2026-09-29  Reviewer AI  BACKEND_DONE -> REVIEW_APPROVED -> RELEASED (RELEASE-2026-09-29-007, single-coordinator session)  run-all 17/17 at d5ed4b8d, gate 18/18 PASS. Merged --no-ff (529fdb0b); ticket released; branch deleted after merge.
