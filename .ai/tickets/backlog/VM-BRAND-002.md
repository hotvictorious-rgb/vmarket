# Ticket Template (Appendix A)

Ticket ID:            VM-BRAND-002
Title:                Per-app brand marks — VM (customer+storefront), VV (vendor), VD (delivery)
Type:                 FEATURE
Status:               BACKLOG
Blocked:              yes (needs VV + VD artwork files from human — nothing in repo; see inventory)
Created by / date:    Reviewer AI / 2026-09-29 (human ruling: customer/storefront=VM, vendor=VV, delivery=VD)
Size estimate:        small (asset swap + proof per app; zero code unless dimensions force layout touch)

Business requirement:
Problem:            All three Flutter apps ship the identical VM mark today (verified visually: User/Vendor/Delivery `logo.png` are the same purple VM lockup). Human rules per-app marks: VM for customer app + storefront, VV for vendor web/app, VD for delivery app. VV/VD artwork does not exist anywhere in the repo (exhaustive search: only `vm_icon.jpg` matches `*vm_*`; no `*vv*`/`*vd*`).
Expected behavior:  (1) Storefront: VM mark in auth modal rings — DONE in VM-STORE-006 (`vm_icon.jpg`, ring-friendly square). (2) Customer app: already VM (`logo.png`, `logo_splash.png`, `logo_with_name*.png`, mipmaps) — verify-only. (3) Vendor: swap `assets/images/logo.png`, `logo_white.png`, `logo_with_app_name.png` (+ `logo_splash` if referenced) + android mipmaps + iOS icons to VV art. (4) Delivery: swap `assets/image/logo.png`, `logo_splash.png`, `logo_with_name.png` + mipmaps to VD art. Central registries (`Vendor app/lib/utill/images.dart:7-9`, `Delivery Man App/lib/utill/images.dart:12-13,60`) mean file swaps need zero code changes if dimensions match.
Forbidden behavior: No placeholder VV/VD invented by AI (brand art comes from human only). No code changes for the swap. No touching backend PHP, routes, Blade, Control Zone, results/reviews/changelog.
Affected systems:     Flutter app assets, storefront modal rings (done)
Tier / area:          C (brand)
Legacy debt IDs:      none
Affected APIs:        none
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (asset swap)
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (report lives in review file)
Assigned AI:          FRONTEND AI (dispatched by REVIEWER AI with exact-prompt work orders)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: frontend/VM-BRAND-002 (from current `v1` AFTER artwork lands; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
Work order:
- **Goal:** Swap vendor + delivery artwork to human-supplied VV/VD files and prove per-app marks visually.
- **Branch:** `frontend/VM-BRAND-002` from current `v1` once files land.
- **Allowed files:** the asset files listed above only (+ `pubspec.yaml` ONLY if new filenames are introduced — prefer same-name overwrite); own ticket notes.
- **FORBIDDEN:** everything else.
- **Acceptance:** (1) VV/VD files in repo at the listed paths (evidence: paths + visual check); (2) rendered/screenshot proof per app (splash + login + header); (3) `flutter analyze` clean; (4) no VM mark remains in vendor/delivery asset dirs (evidence: grep).
- **Tests + DONE:** `flutter analyze` per app + `run-all.ps1 -Ticket VM-BRAND-002` full HEAD (JSON uncommitted, report counts); push; FRONTEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; sandbox only.
Dependencies (tickets/features): human supplies `vv_logo` + `vd_logo` artwork (PNG, launcher + in-app sizes); VM-STORE-006 (storefront VM part, released)
Tests required:       visual proof per app; flutter analyze; run-all JSON at full SHA
Security requirements: binary assets only from human; no code change
Acceptance criteria:
- [ ] VV art in vendor slots, VD art in delivery slots (evidence: paths + visuals)
- [ ] Customer + storefront confirmed VM (evidence: existing files + STORE-006)
- [ ] flutter analyze clean per touched app

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          per-app splash + login with correct mark

Implementation notes:
- 2026-09-29 inventory: all three `logo.png` files byte-distinct paths but visually identical VM marks (viewed). Registries centralize paths — swap is file-only.
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-29  Reviewer AI  BACKLOG (filed)  Human brand ruling. Storefront VM part executes immediately as VM-STORE-006 (vm_icon.jpg ships in theme). Vendor/VD wait on artwork.
