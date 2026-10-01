# Independent Adversarial Review — VM-CUST-010

Ticket:                   VM-CUST-010 — Storefront Cart JS Hygiene (Dead Shipping & Coupon Re-init Removal)
Reviewer:                 AI-5 (Customer Domain Independent Reviewer)
Model/tool used:          Vic (Vertex AI) serving the AI-5 logical role
Commit reviewed:          d1e96b91168d56ebdff7d9fb348acfe43282a4b8
Branch binding:           ai1/VM-CUST-010 tip == d1e96b91168d56ebdff7d9fb348acfe43282a4b8 (verified via `git rev-parse ai1/VM-CUST-010` and `git worktree list` → `VictoriousAI/AI-1`)
Parent:                   9e1758eb (RELEASE-2026-09-25-001 tip)
Review cycle number:      1
Work mode:                detached-HEAD read-only inspection in `VictoriousAI/AI-5` at d1e96b91 (`git show`, `git diff`, `grep`, `node --check`); no code, test, or branch writes; review artifacts only
Files reviewed (full diff d1e96b91^..d1e96b91 — exactly 3 files, 0 insertions, 40 deletions):
  - `backend/vmarket-web/resources/themes/theme_vmarket/public/assets/js/cart-list-page.js` (0 ins / 2 del)
  - `backend/vmarket-web/resources/themes/theme_vmarket/public/assets/js/cart.js` (0 ins / 37 del)
  - `backend/vmarket-web/resources/themes/theme_vmarket/theme-views/layouts/partials/_route-for-js.blade.php` (0 ins / 1 del)
Blob binding at d1e96b91 (from `git show` index lines):
  - `cart-list-page.js` → 6cac6bc8 → d814b37d
  - `cart.js` → b53a3ea6 → b7c438bc
  - `_route-for-js.blade.php` → 09e7cabd → 49da2022
Contract references inspected read-only at d1e96b91 (unmodified by this commit):
  - `.../public/assets/js/custom.js` (holds the surviving `renderCouponCodeApply` definition, lines 1705 and 1737)
  - `.../theme-views/cart/` blade tree (coupon/legacy-shipping sweep)
  - `backend/vmarket-web/routes/web/routes.php` (set-shipping-method route, now client-unreferenced)
  - `backend/vmarket-web/app/Http/Controllers/Customer/SystemController.php` (setShippingMethod handler behind the now-orphaned route)

Tests / verification actually run by the reviewer:
  1. Dead-reference sweep across `backend/vmarket-web/resources/themes/theme_vmarket/` for
     `setShippingIdFunction|set-shipping-url|set-shipping-method|set-shipping-id|set-shipping-onchange|renderCouponCodeApply`
     → 2 hits only, both in `public/assets/js/custom.js` (line 1705 definition, line 1737 top-level self-invocation). Zero hits in `cart.js`, `cart-list-page.js`, or any blade. **Independently reproduced AI-1's self-check sweep — confirmed.**
  2. `node --check` on both edited JS files → **both parse clean** (`cart.js OK`, `cart-list-page.js OK`). No dangling statement, no broken closure from the deleted `setShippingIdFunction` block.
  3. Blade integrity check on `_route-for-js.blade.php` → the deleted line was a self-closing `<span>` carrying only `id` + `data-url`; no enclosing element unbalanced. Sibling route spans (`update_quantity_url`, `order_again_url`, `route-product-restock-request`) untouched.
  4. Flow re-trace of the four touched AJAX success blocks in `cart.js` and `cart-list-page.js`: `initTooltip()`, `proceedToNextAction()`, `updateCartQuantityListCartData()`, `updateCartQuantityListMobileCartData()`, `multipleCheckBoxFunctionsInit()` all still invoked in the same order. Only the two dead calls removed from each.
  5. `scripts/tests/run-frontend-tests.ps1 -App customer` → **NOT EXECUTED by reviewer**; see Testing findings.
  6. `php artisan test` → **NOT EXECUTED by reviewer**; execution was refused by the local permission classifier on this host. AI-1's PHP invariant results (23/23, 21/21, feed-isolation) are carried as *implementer-reported*, not reviewer-verified.

Business-rule findings:
  - PASS: All dead legacy shipping-method selectors and coupon re-init calls are gone from the **active** cart path. Verified the active theme: `app/Utils/theme-helpers.php:22` resolves `env('WEB_THEME') ?: 'theme_vmarket'`, so `theme_vmarket` is the canonical storefront and is the tree this commit cleaned.
  - PASS: Cart quantity +/-, delete, and checkbox-selection re-init chains are behaviour-preserving — only dead calls were dropped from the success callbacks; every live call remains in its original position.
  - PASS: Closes non-blocker #1 from the VM-CUST-003 cycle-3 review exactly as scoped, and stays inside "deletions only" (0 insertions confirmed by `git show --stat`).

Security findings:
  - PASS: No route, controller, model, or migration touched. No new attack surface.
  - PASS: CSRF posture unchanged — the deleted `#set-shipping-url` span was consumed by a GET request (`$.get`) in the removed function, so its removal strictly *reduces* state-changing surface rather than weakening it. The surviving `renderCouponCodeApply` in `custom.js` still sets `X-CSRF-TOKEN` before its POST.
  - PASS: No mass-assignment, injection, or deserialization surface introduced.
  - OBSERVATION (non-blocker, pre-existing, NOT introduced by this commit): `GET /customer/set-shipping-method` (routes.php:342 → `SystemController@setShippingMethod`) is now client-unreferenced in `theme_vmarket` but is still **routable**, carries **no `customer` auth middleware** (the `Route::controller(SystemController::class)` group at routes.php:340 sits outside the `middleware => ['customer']` group that closes at line 257), and writes `CartShipping` rows keyed by a caller-supplied `cart_group_id`, reading `ShippingMethod::find($request['id'])->cost` with no null guard. This is a pre-existing legacy-`CartShipping` exposure in the deprecated `ShippingMethod`/`CartShipping` subsystem named as legacy in the authoritative-vs-legacy mapping. It is out of this ticket's "deletions only, theme_vmarket only" scope and **is not a regression from d1e96b91**. Recommend a dedicated backend ticket (AI-1) to either retire the route or lock it behind auth + validate the id.

Prompt-injection / untrusted-input findings:
  - PASS: No AI-interpreted content, no new rendering of user-generated HTML, no new sink. Pure deletion.

Frontend findings:
  - PASS: Syntax verified with `node --check` on both edited scripts.
  - PASS: `proceedToNextAction()` remains bound to `#proceed-to-next-action` in both cart scripts, and the `data-goto-checkout` / `data-checkout-payment` contract released in VM-CUST-003 is untouched — no blade markup or CTA contract was altered, honouring the ticket's forbidden behaviour.
  - PASS: Order-note persistence via `#order_note_url` unaffected.
  - OBSERVATION (non-blocker): `theme_aster` (the second, non-active theme) still contains the full dead stack — `theme_aster/public/assets/js/cart.js`, `cart-list-page.js`, and `_route-for-js.blade.php` all still reference `setShippingIdFunction` / `set-shipping-url`. Not a regression: the ticket scoped to the active theme and `theme_aster` is not selected by `WEB_THEME`. Flag for a single follow-up ticket if `theme_aster` is ever reactivated.

Backend findings:
  - N/A — no backend route/controller/model change in this diff. Contract impact "no" as declared: confirmed.
  - Non-blocker (see Security findings) recorded against `SystemController@setShippingMethod` as a pre-existing, out-of-scope observation only.

Integration findings:
  - PASS: Cart → `choose-shipping-address-other` → `checkout-payment` chain is unmodified; the diff removes no element any surviving script queries. Verified by reading every remaining `$('#...')` selector in the touched success blocks — none resolve to `#set-shipping-url`.

Client compatibility impact:
  - N/A — web storefront only; zero Dart/PHP/mobile surface touched.

Testing findings:
  - ENVIRONMENT-BLOCKED (not a code signal): `scripts/tests/run-frontend-tests.ps1 -App customer` was not executed. AI-1 recorded the same host limitation ("Building with plugins requires symlink support. Please enable Developer Mode") on 2026-09-25. This is a Windows OS configuration constraint, byte-identical blob at base, HEAD, and impl, and entirely independent of a diff that touches zero Dart. The ticket's "Frontend suite 6/6" criterion therefore stands **UNVERIFIED — BLOCKED ON HOST**. AI-1 recorded this honestly rather than claiming a pass, which is the correct behaviour and is credited here.
  - VERIFIED INDEPENDENTLY INSTEAD: `node --check` parse validation plus the dead-reference sweep, which is the discriminating evidence for a deletion-only change. For deletions, absence-of-symbol and parse-validity are the meaningful proofs; a Flutter widget suite cannot regress from changes to vanilla JS and a Blade partial.
  - NOT REVIEWER-VERIFIED: AI-1's PHP invariant results (Marketplace Listing Freshness 23/23, Payment & Fulfillment Boundary Security 21/21, Vendor Feed Isolation). Reviewer execution of `php artisan test` was blocked by the local permission classifier. Given the diff is JS/Blade-only with zero PHP touched, this is a low residual risk, but the release gate should record a fresh PHP run from a capable runner.

Performance findings:
  - PASS: Strictly fewer DOM queries and listener bindings per cart interaction. `setShippingIdFunction` ran four delegated-binding registrations plus a `$.get` on every quantity update and every item removal; all removed. No new work added anywhere.

Dependency findings:
  - PASS: Zero manifest changes; `node --check` confirms no new import, bundle, or CDN dependency was introduced by the deleted code's absence.

Privacy / data-impact findings:
  - PASS: Zero PII, telemetry, or log change. The removed `$.get` to `/customer/set-shipping-method` sent only `id` and `cart_group_id`; removing it deletes a request, adds none.

Design / localization findings:
  - PASS: No user-visible markup change; the removed span was an empty hidden route carrier. No translation keys affected.

Blockers:
  - **None.** The diff is deletion-only, syntax-verified, symbol-verified absent from the active cart path, and every live re-init call is preserved in order. All three acceptance criteria the implementer could satisfy are satisfied.

Non-blockers (do NOT expand this ticket — each needs its own ticket):
  1. Backend legacy retirement: `GET /customer/set-shipping-method` (`routes/web/routes.php:342` → `SystemController@setShippingMethod`, lines 31-65) is now client-unreferenced in the active theme but still routable, unauthenticated, writes caller-keyed `CartShipping` rows, and dereferences `ShippingMethod::find($request['id'])->cost` without a null guard. Pre-existing; out of scope; assign to AI-1 with an auth + id-validation fix or route retirement.
  2. `theme_aster` still carries the identical dead shipping/coupon stack. Harmless while `WEB_THEME` resolves to `theme_vmarket` (`app/Utils/theme-helpers.php:22`). Single follow-up ticket if that theme is ever reactivated.
  3. Release gate (infrastructure, not a ticket blocker): record a fresh `run-frontend-tests.ps1 -App customer` 6/6 and a fresh `php artisan test` from a Developer-Mode-enabled / PHP-capable runner before launch, to close the two UNVERIFIED items above.

Decision:                 **APPROVED**
