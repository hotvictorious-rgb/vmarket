# Ticket: VM-THEME-002

Ticket ID:            VM-THEME-002
Title:                User App Theme Consolidation â€” Aster Theme Screen & Widgets Retirement
Type:                 FEATURE
Status:               FRONTEND_DONE
Blocked:              no
Created by / date:    AI-Worker / 2026-09-29
Size estimate:        ~250 lines (Flutter Dart refactoring, renaming, import updates)

Business requirement:
Following the complete backend purge of `theme_aster` in `VM-THEME-001` (LD-002 retirement), consolidate the Customer Mobile App (`User app/`) on the single authoritative home screen and theme architecture, retiring obsolete `aster_theme` nomenclature and dead theme branches.

Problem:
1. `User app/lib/features/home/screens/aster_theme_home_screen.dart` remains as the active home screen file, carrying legacy Aster theme naming.
2. `User app/lib/features/home/widgets/aster_theme/` contains home screen widgets trapped in an obsolete theme directory.
3. `User app/lib/features/splash/domain/models/config_model.dart` still defaults fallback theme to `'theme_aster'`.
4. Multiple client files (`dashboard_screen.dart`, `login_screen.dart`, `logout_confirm_bottom_sheet_widget.dart`, `recommended_product_widget.dart`, `your_location_bar_widget.dart`) explicitly import and reference `AsterThemeHomeScreen`.

Expected behavior:
- `AsterThemeHomeScreen` normalized and renamed to `HomeScreen` in `User app/lib/features/home/screens/home_screen.dart`.
- `widgets/aster_theme/` relocated or normalized to canonical `lib/features/home/widgets/`.
- All callers re-pointed cleanly to `HomeScreen`.
- `config_model.dart` fallback updated to `'theme_vmarket'`.
- Obsolete `aster_theme_home_screen.dart` and `widgets/aster_theme/` deleted.
- Zero live `theme_aster` references across `User app/lib/`.
- `flutter analyze` passes with zero new errors or warnings.

Forbidden behavior:
- NEVER alter the visual design, widgets layout, or feature behavior of the Customer App home screen.
- NEVER break state management (Provider) or GetIt dependency injection.

Affected systems:     User App (Customer Flutter App)
Tier / area:          C (Theme & Presentation Consolidation)
Legacy debt IDs:      LD-002 (Mobile Finalization)
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
Assigned AI:          FRONTEND AI (dispatched by REVIEWER AI)
Required reviewers:   REVIEWER AI (sole coordinator and push authority)
Branch / base commit: frontend/VM-THEME-002 (from current v1)
Work order:
## WORK ORDER from REVIEWER AI -- VM-THEME-002 -- FRONTEND
- Goal: Retire Aster naming in User app home, keeping identical visuals, on single HomeScreen.
- Branch: frontend/VM-THEME-002 (create from current v1 e383c2b8, push only this branch; Reviewer deletes it after merge)
- Allowed files:
  - User app/lib/features/home/screens/aster_theme_home_screen.dart (rename to home_screen.dart, class AsterThemeHomeScreen to HomeScreen)
  - User app/lib/features/home/widgets/aster_theme/ (relocate to User app/lib/features/home/widgets/, update imports)
  - User app/lib/features/dashboard/screens/dashboard_screen.dart (lines 15,57,63)
  - User app/lib/features/auth/screens/login_screen.dart (lines 11,530)
  - User app/lib/features/home/widgets/your_location_bar_widget.dart (lines 4,25)
  - User app/lib/features/more/widgets/logout_confirm_bottom_sheet_widget.dart (lines 4,116)
  - User app/lib/features/product/widgets/recommended_product_widget.dart (rename fromAsterTheme param, keep behavior)
  - User app/lib/features/shop/widgets/more_store_list_view.dart (line 6)
  - User app/lib/features/splash/domain/models/config_model.dart (lines 280,283 fallback to theme_vmarket)
- FORBIDDEN: backend/vmarket-web/app, routes, config, database, backend views, Vendor app, Delivery Man App, AI_CHANGELOG.md, .ai/status/results
- Contract: VM-THEME-001 backend done (theme_vmarket sole theme, base v1 e383c2b8); no API change.
- Acceptance:
  1. HomeScreen created at home_screen.dart, wired in dashboard_screen.dart
  2. All callers re-pointed (git grep AsterThemeHomeScreen empty except history)
  3. aster_theme_home_screen.dart and widgets/aster_theme/ deleted
  4. config_model.dart fallback is theme_vmarket
  5. git grep aster in User app/lib zero code hits (document snackbar/mastercard false positives)
- Tests: git grep sweeps, flutter analyze User app zero new issues, home visual smoke.
- DONE: commits pushed to frontend/VM-THEME-002, ticket notes filled, FRONTEND_DONE in History.
- Rules: exact-path git add only, leave other files dirty, notes in ticket never changelog, blocked to REVIEWER in ticket.
- Push rule: NEVER merge, NEVER push to v1 or main. Only Reviewer merges after approval.


Dependencies (tickets/features): VM-THEME-001 (Backend Merged)

Tests required:
- Grep sweep: `git grep -i "aster" "User app/lib/"` returns 0 code hits.
- `flutter analyze "User app"`: zero errors.
- Visual smoke test: Home screen loads categories, banners, top stores, and featured products seamlessly.

Security requirements:
- None (UI refactoring).

Acceptance criteria:
- [ ] 1. `HomeScreen` created and wired as canonical home screen in `dashboard_screen.dart`.
- [ ] 2. All 6 referencing files re-pointed to `HomeScreen`.
- [ ] 3. `aster_theme_home_screen.dart` and `widgets/aster_theme/` removed.
- [ ] 4. `config_model.dart` fallback updated to `'theme_vmarket'`.
- [ ] 5. Grep shows zero live `aster` hits in `User app/lib/`.

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human â†’ REVIEWER AI â†’ FRONTEND AI â†’ REVIEWER AI (APPROVED) â†’ REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes. Workers push only their own feature branches.
Screenshots:          Required for review

Implementation notes:
- Clean 1-to-1 rename and import normalization.

History (append-only):
- 2026-09-29 21:20  BACKEND AI  <none> -> READY  Ticket filed following Reviewer audit against main.


- 2026-09-29  Reviewer AI  READY -> FRONTEND_DOING  Dispatch frontend work order on reviewer/VM-THEME-002-dispatch (base v1 e383c2b8). Backend VM-THEME-001 already RELEASED.


- 2026-09-29  Frontend AI  work complete  Pushed frontend/VM-THEME-002 commit 471293b7 (14 files, rename-only). Grep 0 aster, flutter analyze 0 new issues.
- 2026-09-29  Reviewer AI  FRONTEND_DONE collected  Verified at exact SHA 471293b7: 5/5 acceptance, boundary PASS, current-with-v1. Review REV-VM-THEME-002-471293b7 APPROVED (implementation). Gate inputs pending (REVIEW_APPROVED + result JSON) before merge-release.

