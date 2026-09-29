# Ticket Template (Appendix A)

Ticket ID:            VM-STORE-006
Title:                Auth modal rings use VM icon mark (not vic wordmark)
Type:                 FEATURE
Status:               RELEASED
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-29 (human ruling: VM mark for customer/storefront; ring fits square icon)
Size estimate:        xs (2-line src swap)

Business requirement:
Problem:            VM-STORE-005 put `vic_logo.webp` (wide wordmark) in the 44px logo rings; human rules the VM mark. `vm_icon.jpg` (square purple VM cart icon, ships in theme) fits the ring and is the VM mark.
Expected behavior:  Both rings render `vm_icon.jpg`.
Forbidden behavior: No other changes. No backend PHP, routes, Flutter, assets (no new files), Control Zone, results/reviews/changelog.
Affected systems:     Storefront auth modals
Tier / area:          C (brand)
Affected APIs:        none
Contract impact:      no
Documents updated:    none - justify (report lives in review file)
Assigned AI:          FRONTEND AI
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: frontend/VM-STORE-006-logo (from current `v1`)
Work order:           (executed same-session per human run-order)
Dependencies (tickets/features): VM-STORE-005 (released); VM-BRAND-002 (vendor/VD parts wait on artwork)
Tests required:       rendered-HTML proof; run-all JSON at full SHA
Acceptance criteria:
- [ ] Both rings reference vm_icon (evidence: rendered HTML)
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          modal rings (notes)

Implementation notes:
Review notes: .ai/reviews/REV-VM-STORE-006-b12ddf629f66f80ddba0a740b51fa85c060159cc.md (Decision APPROVED; 17/17 at exact SHA)
Final decision: APPROVED → RELEASED as RELEASE-2026-09-29-002
Release commit: 710f0542 (merge v1; feature b12ddf629f66f80ddba0a740b51fa85c060159cc)

History (append-only):
- 2026-09-29  Reviewer AI  BACKLOG (filed, executed same-session)  Human VM ruling.
- 2026-09-29  Frontend AI  BACKLOG -> BACKEND_DONE (single-coordinator session)  2 src swaps vic_logo.webp -> vm_icon.jpg. branch=frontend/VM-STORE-006-logo.
- 2026-09-29  Frontend AI  run-all 17/17 PASS at fix commit 0936ba93 (7 executed + 10 justified).
- 2026-09-29  Reviewer AI  BACKEND_DONE -> REVIEW_APPROVED -> RELEASED (RELEASE-2026-09-29-002, single-coordinator session)  run-all 17/17 at b12ddf62, gate 18/18 PASS. Merged --no-ff (710f0542); ticket released; branch deleted after merge.
