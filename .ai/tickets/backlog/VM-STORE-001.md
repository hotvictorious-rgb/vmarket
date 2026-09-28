# Ticket Template (Appendix A)

Ticket ID:            VM-STORE-001
Title:                Storefront dead-route remediation — 6 missing named routes 500 live order/checkout views
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-28 (found during storefront button/form audit: 109 view route refs vs 904 registry names)
Size estimate:        small (Blade-only; exact-path edits, zero behavior change beyond restoring render)

Business requirement:
Problem:            6 named routes referenced in `theme_vmarket` views do not exist, and Blade `route()` throws at RENDER time — the containing pages HTTP 500: (1) `support-ticket` (bare; only `support-ticket.index|comment|delete|close` exist) in checkout `shipping.blade.php:88` + `account-order-summary.blade.php:246` → checkout shipping step + order details down; (2) `pay-offline-method-list` unconditional spans in `tracking.blade.php:537` + `account-order-summary.blade.php:493` → order tracking/result views down; (3) `customer.customer-order-edit-pay-amount` forms in `tracking.blade.php:504`, both `_choose-payment-method-*.blade.php` modals → same pages down on include; (4) `messages` + `chat` in `delivery-man-info.blade.php:66,82` (no chat backend exists anywhere) → delivery-man order view down on modal render; (5) `mercadopago.make_payment` in `payment/marcedo-pogo.blade.php:166` (legacy gateway view, never rendered — no `view()` refs found; left untouched, noted).
Expected behavior:  Every listed view renders without `RouteNotFoundException`; support buttons land on `account-tickets` (exists); dead offline/chat surfaces removed (V1 prepaid, no chat backend); retry-pay UI removal tracked as follow-up backend feature.
Forbidden behavior: No invented endpoints. No repointing forms to semantically wrong actions. No `theme_aster` touched. No logic changes.
Affected systems:     Storefront Web Theme (`theme_vmarket` checkout/order/users-profile views)
Tier / area:          A (checkout shipping + order tracking/details are transaction path)
Legacy debt IDs:      LEGACY-OFFLINE-PAY-001 (proposed: offline/COD surfaces), LEGACY-CHAT-001 (proposed: chat UI without backend)
Affected APIs:        none (views only; no route/controller changes)
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (render restoration)
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (report lives in review file)
Assigned AI:          FRONTEND AI (dispatched by REVIEWER AI with an exact-prompt work order; Blade views are Frontend-owned)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: frontend/VM-STORE-001 (from current `v1`; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
Work order:
- **Goal:** Remove/repoint the 6 dead route references so all listed views render, with zero invented endpoints.
- **Branch:** `frontend/VM-STORE-001` from current `v1`.
- **Allowed files (exact):** `resources/themes/theme_vmarket/theme-views/checkout/shipping.blade.php` (`:88` repoint only), `.../users-profile/account-order-details/account-order-summary.blade.php` (`:246` repoint + `:493` span removal only), `.../order/tracking.blade.php` (`:492` modal include removal + `:495-532` offline block removal + `:537` span removal only), `.../order/partials/_choose-payment-method-modal.blade.php` + `.../partials/_choose-payment-method-order-details.blade.php` (delete files ONLY if unreferenced after include removal — verify with repo-wide grep first; else leave), `.../users-profile/account-order-details/delivery-man-info.blade.php` (chat modal trigger+form removal only; review form stays).
- **FORBIDDEN:** backend PHP, routes, other blades, Flutter, assets, Control Zone, results/reviews/changelog. No new routes. No form repointing to wrong actions.
- **Dispositions:** support-ticket → `route('account-tickets')` (verified exists); offline spans/blocks → DELETE (V1 prepaid; `Route::has`-guarded `_route-for-js` stays); pay-modal includes/forms → DELETE include + files if orphaned (retry-pay needs a backend endpoint that does not exist — follow-up ticket, do not invent); chat modal → DELETE trigger+form (no backend; review modal stays); marcedo-pogo → LEAVE (unrendered).
- **Acceptance:** (1) repo-wide grep for the 6 names returns zero live view refs (marcedo file + history excepted with list); (2) `php -l` N/A (Blade — instead: render smoke); (3) live smoke on server :8000: shop-cart, track-order, home, product page render without 500; authenticated order views verified to the extent a guest session allows + code-path reasoning recorded; (4) `git diff --stat` shows only allowed files.
- **Tests + DONE:** smoke log attached; push branch; report FRONTEND_DONE as branch+SHA.
- **Rules:** exact-path `git add` only; sandbox only.
Dependencies (tickets/features): VM-CUST-013 (this audit's chain; defects-per-ticket rule)
Tests required:       render smoke per touched view; grep-zero proof; no PHP errors in server log for those renders
Security requirements: no new inputs; removed surfaces reduce attack area (dead POST targets gone)
Acceptance criteria:
- [ ] Zero live refs to the 6 dead names (evidence: grep log + exception list)
- [ ] Touched views render without 500 (evidence: smoke log)
- [ ] No invented endpoints; retry-pay endpoint filed as follow-up (evidence: ticket ID)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → FRONTEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          before/after render pair for shipping step (500 → renders)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-28  Reviewer AI  BACKLOG (filed)  109 refs vs 904 names cross-check (JSON route dump, truncation-safe). 6 dead named. Render-fatal analysis per site (unconditional vs gated). Dispositions above.
