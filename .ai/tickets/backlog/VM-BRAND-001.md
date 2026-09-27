# Ticket Template (Appendix A)

Ticket ID:            VM-BRAND-001
Title:                Brand Alignment — retire non-canonical colors, enforce #5E17EB/#FFD700/#FFFFFF
Type:                 FEATURE
Status:               BACKLOG
Blocked:              no
Created by / date:    Human operator / 2026-09-26
Size estimate:        small (token re-pointing only, zero behavior change)

Business requirement:
Problem:            Three palettes compete: `.ai/DESIGN_RULES.md` specified `#4A154B`/`#D4AF37` (now corrected to canonical by human ruling 2026-09-26), Flutter apps ship `#5E17EB`/`#FFD700`, and `User app/README.md` claims `#6A1B9A`. Blade/seed stragglers (`#5e2e85` announcement default, any `#4A154B`/`#D4AF37`/`#6A1B9A` refs) contradict the canonical brand.
Expected behavior:  Repo-wide grep for `#4A154B`, `#D4AF37`, `#6A1B9A` (any case) returns zero live code/doc hits outside explicitly-listed retained history. Every re-point maps to the canonical token (`color-primary` `#5E17EB`, `color-accent` `#FFD700`, `color-surface` `#FFFFFF`) with zero visual or behavior change beyond the intended color correction.
Forbidden behavior: No new palette invented. No behavior or layout changes bundled in. No guessing on ambiguous refs — list them for Reviewer ruling instead.
Affected systems:     Storefront Blade, seed defaults, docs (README claim), Flutter themes (verify-only, already canonical)
Tier / area:          C
Legacy debt IDs:      none
Affected APIs:        none
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify
Affected database tables:     business_settings (announcement color default — read-only verification; migration only if Reviewer orders it)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (report lives in review file)
Assigned AI:          BACKEND AI first (seed default + grep registry), then FRONTEND AI (Blade/README re-pointing; Flutter verify-only)   (dispatched by REVIEWER AI with exact-prompt work orders)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-BRAND-001, then frontend/VM-BRAND-001 (from current `v1`)
Work order:           (Reviewer AI pastes the exact-prompt work order here per `.ai/templates/work-order-template.md`)
Dependencies (tickets/features): none
Tests required:       before/after grep logs; storefront smoke (home, product, cart render); `flutter analyze` unaffected
Security requirements: none beyond standard (no secrets, no logic changes)
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] Zero live `#4A154B`/`#D4AF37`/`#6A1B9A` refs outside retained-history list (evidence: grep logs + list)
- [ ] Seed announcement default + README claim corrected to canonical (evidence: diff)
- [ ] Effective rendering unchanged except intended color correction (evidence: smoke log + screenshot pair)
- [ ] Flutter themes verified already-canonical, untouched (evidence: grep log)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          (before/after pair for corrected surfaces)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-26  Human  BACKLOG (created)  Human-ruled canonical palette #5E17EB/#FFD700/#FFFFFF; DESIGN_RULES.md corrected same day. Queue behind launch sequence unless Reviewer orders otherwise.
