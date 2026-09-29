# Ticket: VM-THEME-001

Ticket ID:            VM-THEME-001
Title:                Theme_Aster Removal Finalization â€” Dangling Reference Normalization
Type:                 FEATURE
Status:               IN_PROGRESS (backend stage RELEASED, frontend stage pending)
Blocked:              no
Created by / date:    AI-8 / 2026-09-25
Size estimate:        ~300 lines (conditional normalization + verification, no new features)

Business requirement:
Finish the `theme_aster/` removal so exactly one storefront theme (`theme_vmarket`) exists with zero dangling references, per the one-concept-one-implementation rule and LD-002.
Problem:
PREMISE UPDATE 2026-09-26: `resources/themes/theme_aster/` EXISTS on disk again (a prior bulk deletion was reverted during the VM-CUST-003 close-out to protect the release). So this ticket now covers the FULL removal, in dependency order: (1) re-point `file_path.php` placeholder fallbacks from `theme_aster` to `theme_vmarket`; (2) change `seed_sqlite_core.php` default `theme_name` to `theme_vmarket`; (3) normalize `theme_root_path() == 'theme_aster'` conditionals (WebController, BannerService, PdfGenerator, AdvanceSearch, admin/vendor blades, validation requests) to their current effective (else-branch) behavior; (4) remove `theme_aster` from `Constant.php` registry + admin theme-setup UI; (5) delete `resources/themes/theme_aster/` dead-last; (6) Frontend removes the Aster home screen + admin toggle option. ~20+ code references plus the folder itself. All currently evaluate to safe fallbacks or parallel screens, but they are dead weight inviting future misuse.
Expected behavior:
- Repo-wide grep for `theme_aster` returns only historical docs/changelog hits (explicitly listed as retained history).
- Fallbacks re-pointed BEFORE deletion: `file_path.php` â†’ `theme_vmarket` keys; seeder default â†’ `theme_vmarket`; registry + admin UI â†’ sole theme.
- Conditionals normalize to current effective behavior (paginate limits, banner service, category/flash-deal guards keep else-branch values).
- `theme_root_path()` default + `ThemeServiceProvider` fallback remain `theme_vmarket` (verify, don't change).
- Folder `theme_aster/` deleted dead-last, only after grep + smoke prove zero live references.
- Frontend removes Aster home screen + theme toggle option (starts only after Reviewer confirms BACKEND_DONE).
Forbidden behavior:
- NEVER change effective runtime behavior (same paginate limits, same assets, same fallbacks as today).
- NEVER touch `theme_vmarket/` rendering or cart/checkout blades (CUST-003 territory).
- NEVER bulk-add unrelated files; exact-path commits only.
Affected systems:     Storefront Web Theme config, Admin theme-setup views
Tier / area:          C (Legacy debt retirement per LD-002)
Legacy debt IDs:      LD-002 (retires on completion)
Affected APIs:        none
Contract impact:      no
Client compatibility impact: no
Feature flag / kill switch: none - cleanup ticket
Affected database tables: none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (Reviewer writes the single AI_CHANGELOG.md entry at release)
Assigned AI:          BACKEND AI first, then FRONTEND AI   (dispatched by REVIEWER AI with exact-prompt work orders; frontend starts only after Reviewer confirms BACKEND_DONE)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-THEME-001, then frontend/VM-THEME-001 (from current `v1`)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md`)
Dependencies (tickets/features): VM-CUST-003 (must be RELEASED first â€” same theme area)
Tests required:
- Repo-wide grep evidence: zero live `theme_aster` code refs (docs/history excepted with list).
- Storefront smoke: home, product, cart, checkout-details render without errors.
- `flutter analyze` unaffected (no app code touched).
Security requirements:
- No route/controller changes; views-only normalization.
Acceptance criteria:
- [ ] 1. Grep shows zero live code refs (evidence: grep log + retained-history list).
- [ ] 2. Effective behavior unchanged (paginate/asset/fallback parity note + smoke log).
- [ ] 3. LD-002 marked RETIRED in follow-up (human-owned file; Backend AI proposes text).

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human â†’ REVIEWER AI â†’ BACKEND AI â†’ REVIEWER AI â†’ FRONTEND AI â†’ REVIEWER AI (APPROVED) â†’ REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          N/A

Implementation notes:
To be populated by AI-2 during implementation.
Review notes:
To be populated by AI-5 during review.
Final decision:
Pending implementation and review.
Release commit:
Pending.

History (append-only):
- 2026-09-25  AI-8  BACKLOG -> READY  Split from VM-CUST-003 per DECISION-001 (human approved Option A)
- 2026-09-26  Human  READY (premise refreshed)  theme_aster/ folder is back on disk (restored in VM-CUST-003 close-out); ticket rescoped to full ordered removal. Migrated to 3-AI model: Backend-then-Frontend dispatch, REVIEWER AI sole gatekeeper + push authority. Launch-sequence ticket 4 of 4.
- 2026-09-29  Reviewer AI  READY -> IN_PROGRESS, backend stage RELEASED (RELEASE-2026-09-29-012, single-coordinator session)  Backend content verified file-by-file (34/34 lint, zero live refs, folder gone), run-all 17/17 at de7f7c33 (fixed runtime), gate 18/18 PASS, CUST-003 dependency closed. Merged (e0d99e43). Frontend stage (aster home screen + admin toggle) dispatches next.
- 2026-09-29  Frontend AI  BACKEND_DONE -> FRONTEND_DONE (single-coordinator session)  Full aster inventory: screen is the SOLE home (dashboard/login/location/logout all bind it) - deletion would destroy the app, so the screen + call sites + fromAsterTheme flag (default false) stay with rationale recorded. Changed: config_model default identifier theme_aster -> theme_vmarket (no routing effect; no switch exists). Admin theme-setup: no aster toggle found (dynamic theme list; folder deletion removed it). branch=frontend/VM-THEME-001.
