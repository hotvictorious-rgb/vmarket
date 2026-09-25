# Ticket: VM-CUST-003

Ticket ID:            VM-CUST-003
Title:                Storefront Web Cart Alignment & Legacy Shipping Elimination
Type:                 FEATURE
Status:               RELEASED
Blocked:              no
Created by / date:    AI-8 / 2026-09-24
Size estimate:        Medium (approx 250 lines diff across theme blades and JS)

Business requirement:
Storefront web customers must be able to view their cart, adjust item quantities, check/uncheck items,
see an accurate order summary without legacy shipping method dropdowns or coupon code inputs,
and proceed to the canonical checkout flow.

Problem:
`backend/vmarket-web/resources/themes/theme_vmarket/theme-views/cart/cart-details.blade.php` and
`_order-summery.blade.php` contain legacy `CartShipping`, `ShippingType`, and `Helpers::getShippingMethods()`
calls, rendering obsolete shipping dropdowns directly in the cart view. Additionally, the summary view
renders a legacy coupon code input instead of Victorious Points alignment, and computes totals client-side
without backend fulfillment authority.

Expected behavior:
1. Cart list displays grouped items by shop/vendor with clean stock and variant indicators.
2. Quantity increase/decrease/delete AJAX works smoothly with active loading states.
3. Item check/uncheck updates selected item total via AJAX.
4. Zero legacy shipping dropdowns rendered in cart views; delivery fees are handled downstream during fulfillment/checkout.
5. Order summary displays Item Total, Product Discounts, Estimated Tax, and Estimated Victorious Points cashback.
6. "Proceed to Checkout" button navigates to canonical checkout flow.
7. Empty cart state renders cleanly with a "Continue Shopping" CTA.

Forbidden behavior:
- NEVER query `CartShipping`, `ShippingType`, or `ShippingMethod` from Blade templates.
- NEVER render client-side shipping method dropdowns in the cart view.
- NEVER allow client-side fee or discount overrides.
- NEVER break existing cart session management or guest cart merging.

Affected systems:     Storefront Web Theme (`theme_vmarket`), Web Cart Controller
Tier / area:          B (Customer Web Views)
Legacy debt IDs:      LEGACY-SHIPPING-001, LEGACY-COUPON-001
Affected APIs:
- `POST /cart/add`
- `POST /cart/remove`
- `POST /cart/updateQuantity`
- `POST /cart/select-cart-items`
- `GET /shop-cart`
Contract impact:      no (web routes remain unchanged; internal view logic aligned)
Client compatibility impact: no
Feature flag / kill switch: none - core customer journey
Affected database tables: `carts`
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none
Assigned AI:          AI-2
Required reviewers:   AI-5
Branch / base commit: ai2/VM-CUST-003 (from current master/v1 HEAD)
Dependencies:         VM-CUST-002 (Released), VM-LANE-001 (Released)
Tests required:
- Web Cart view rendering without PHP errors or legacy model calls
- Cart quantity update AJAX endpoint verification
- Item selection toggle AJAX endpoint verification
- Flutter regression tests pass (`scripts/tests/run-frontend-tests.ps1 -App customer`)
Security requirements:
- CSRF token validation on all AJAX POST routes
- Session-scoped cart item modification (prevent modifying other sessions' carts)
- Input sanitization on quantities (positive integers only)

Acceptance criteria:
- [x] 1. `cart-details.blade.php` has zero references to `CartShipping`, `ShippingType`, or `Helpers::getShippingMethods`.
- [x] 2. `_order-summery.blade.php` removes legacy coupon input form and aligns summary labels.
- [x] 3. Quantity increment (+), decrement (-), and delete actions work via AJAX and update row totals.
- [x] 4. Checkbox selection per item and per shop updates summary calculations.
- [x] 5. "Proceed to Checkout" CTA links to the authoritative checkout route (`route('checkout-details')`).
- [x] 6. Customer frontend regression tests pass 6/6.

Counters:             review_cycles: {AI5: 2, AI6: 0, AI7: 0}   integration_failures: 0   reopened_count: 0
Screenshots:          N/A

Implementation notes:
1. Eliminated legacy shipping references in `cart-details.blade.php` (AI-2): removed the `CartShipping` /
   `ShippingType` / `Helpers` imports, the per-seller `ShippingType::where(...)` lookup, the
   `CartShipping::where(['cart_group_id' => ...])` chosen-shipping lookup, the
   `Helpers::getShippingMethods(...)` sellerwise dropdown, the inhouse `<select class="set-shipping-onchange">`,
   the `<th>{{ translate('shipping_cost') }}</th>` header, and both desktop/mobile shipping-cost cells.
   Delivery fees now resolve downstream via `FulfillmentAvailabilityService` + `DeliveryLane`.
2. Transformed `_order-summery.blade.php` (AI-2): removed the legacy coupon form
   (`route('coupon.apply')` / `#promo-code` / `#coupon-code-apply`), the Shipping total row, the Coupon
   Discount row (`session('coupon_discount')`), and the Referral Discount row
   (`CustomerManager::getReferralDiscountAmount()`). Summary now shows Item Total, Product Discounts,
   Estimated Tax (item tax only), and Estimated Victorious Points cashback, plus a
   "delivery fees calculated at checkout" footnote.
3. Cashback estimate mirrors the authoritative settlement service: `loyalty_point_earn_rate_percent` web config
   with a 5% default, matching `PickupCashbackAwardService::award()`. Presentation-only; backend remains the
   settlement authority (Backend-as-Single-Source-of-Truth rule preserved).
4. Checkout CTA (AI-2): the `@else` branch of the shared `_order-summery.blade.php` partial renders a
   `<button id="proceed-to-next-action">` carrying `data-goto-checkout="{{ route('customer.choose-shipping-address-other') }}"`
   and `data-checkout-payment="{{ route('checkout-payment') }}"`, preserving the pre-existing DOM event
   contract for `cart-list-page.js` (order-note persistence + forward navigation) and `shipping-page.js`
   (address validation + `checkout-payment` transition). The `checkout-payment` branch keeps
   `#proceed-to-payment-action` with `data-type="checkout-payment"`; verified `payment-page.js` reads only
   `data-type` and `data-route` for that button, so its `data-goto-checkout` value is inert.
5. AJAX cart interactions validated in `cart.js` and `cart-list-page.js` (AI-2): quantity +/-, delete, and
   checkbox selection POST with `_token` and replace `#cart-summary` without a full page reload.
   `setShippingIdFunction()` and `renderCouponCodeApply()` removed from the AJAX re-init chains as dead code.
6. Orphaned `set-shipping-url` span removed from `layouts/partials/_route-for-js.blade.php` (AI-2) now that no
   cart view binds `#set-shipping-method`.
7. Residual legacy-coupon coupling closed (AI-1): two free-delivery progress-bar guards in
   `cart-details.blade.php` were still gated on
   `session()->missing('coupon_type') || session('coupon_type') != 'free_delivery'`. With the coupon input
   removed, a stale `coupon_type` session key could suppress a legitimate free-delivery indicator.
   Both now read `@if ($free_delivery_status['status'])`.
   `OrderManager::getFreeDeliveryOrderAmountArray()` has no coupon dependency, so this is behaviour-preserving
   for the canonical path.
8. Regression tests: `scripts/tests/run-frontend-tests.ps1 -App customer` -> 6/6 pass, runner `[OK]`
   (AI-2 test run ID 2026-09-25_08:45; independently re-run and confirmed by AI-1).
9. Backend invariant suites pass (`MarketplaceListingFreshnessTest` 23/23; Payment & Fulfillment Boundary
   Security tests 1-14 including the constant-time `hash_equals()` pickup OTP check, replay rejection, and
   5-attempt rate limit). The full `run-backend-tests.ps1` exits 2 solely from the pre-existing
   DB-less `tests/Feature/ExampleTest.php` (`no such table: orders`) — unrelated to this ticket's diff,
   which touches only theme blades and theme JS. Tracked separately, not a VM-CUST-003 blocker.

Known out-of-scope legacy debt (follow-up candidate, NOT a VM-CUST-003 blocker):
- `routes/web/routes.php:342` `set-shipping-method` route — still routed, now unreferenced from cart blades.
- `routes/web/routes.php:344` `choose-shipping-address-other` — retained intentionally: it is the
  `data-goto-checkout` target bound by `shipping-page.js` when the shared summary CTA is clicked on
  `checkout-details`. Removing it would break address persistence. Not a VM-CUST-003 blocker.
- `custom.js:1705-1737` `renderCouponCodeApply()` — global bundle, now inert (binds to a removed element).
- Legacy `ShippingMethod` / `ShippingType` / `CartShipping` models and `Helpers::getShippingMethods()` remain at
  the `app/Utils/CartManager.php` utility layer and in non-cart views. Removing them is a larger
  architecture task outside this ticket's declared blast radius.

Review notes:
AI-5 independent adversarial review cycle 1 completed. Decision: **CHANGES_REQUIRED**.
Full report: `.ai/reviews/customer/REV-VM-CUST-003-afadd6bdebbdde9e0885cf0de3f069d8df3cb80a.md`.
Blocker: the shared `_order-summery.blade.php` partial changed the proceed button id from
`#proceed-to-next-action` to `#proceed-to-checkout-action` on an `<a>` tag in the `@else` branch.
The partial is included by all three lifecycle views. `shipping-page.js` (checkout-details /
address page) binds `#proceed-to-next-action` to validate the delivery-address form and POST it
to advance to `checkout-payment`; `cart-list-page.js` (cart page) binds the same id to POST the
`order_note` into session before redirecting. With the anchor in place neither handler runs, the
address form is never submitted, and the address page reloads in a loop — customers can never
reach payment, and the cart order note is silently dropped.
Remediation: restore the button + id contract for the `@else` branch, or decouple the step CTA
out of the shared partial and render it in the parent views.

Remediation note (AI-2, review cycle 2):
Resolved by restoring the DOM contract in the shared partial rather than decoupling it, since the
partial is rendered by three views and each view's page script binds the same id. The `@else` branch now
emits:
  <button id="proceed-to-next-action"
          data-goto-checkout="{{ route('customer.choose-shipping-address-other') }}"
          data-checkout-payment="{{ route('checkout-payment') }}" type="button">
      {{ translate('proceed_to_checkout') }}
  </button>
Verification performed:
1. `cart-list-page.js:3-29` binds `#proceed-to-next-action`, POSTs `order_note` to
   `#order_note_url`, and redirects to the response redirect (falling back to
   `#route-checkout-details`) — the canonical `checkout-details` entry. Handler now reachable.
2. `shipping-page.js:287-371` binds `#proceed-to-next-action`, validates `#address-form` and
   `#billing-address-form` required fields, POSTs the serialized address to `$(this).data('goto-checkout')`
   and redirects to `$(this).data('checkout-payment')`. Both attributes are present again, so the
   address -> payment transition no longer reloads the page in a loop.
3. `payment-page.js:168-179` reads only `data-type` and the payment form id for
   `#proceed-to-payment-action`; the `checkout-payment` branch of the partial is unchanged in behaviour.
4. The button retains the `{{ isset($isProductNullStatus) && $isProductNullStatus == 1 ? 'disabled' : '' }}`
   guard, matching the original implementation, so null-product carts cannot advance.
5. `scripts/tests/run-frontend-tests.ps1 -App customer` -> 6/6 pass, runner `[OK]`
   (AI-2 re-run after remediation, 2026-09-25).

Final decision:
APPROVED — AI-5 independent adversarial review cycle 2 (2026-09-25): no blockers, DOM contract restored, 6/6 frontend re-run, zero legacy refs. Report: `.ai/reviews/customer/REV-VM-CUST-003-80a318d1-cycle2.md`.

Release commit:
8a61392aacbe0b19195ec0f88205a4a7ccc36493 (`ai2/VM-CUST-003-impl`, VictoriousAI/AI-2) — the 2 behavior-critical blades (`cart-details.blade.php` -128/+20-net, `_order-summery.blade.php`): hash-verified identical to the AI-5-approved tree (SHA256 52C92574… / DB0FB42E…).
Scope note (hook constraint): the 3 reviewed JS hygiene files (`cart.js` -37, `cart-list-page.js` -2, `_route-for-js.blade.php`) could NOT be committed by AI-2 — the DECISION-006 pre-commit hook allowlist permits only the 2 blades and rejects the 3 JS paths (proven by hook rejection 2026-09-25). They are dead-code removal only (old JS no-ops against new blades) and are preserved as `scratch/VM-CUST-003-js-cleanup.patch` for a follow-up (human: amend hook allowlist or assign to AI-1 which owns `backend/**`).
Evidence:
- `scripts/tests/run-frontend-tests.ps1 -App customer` 6/6 PASS, runner [OK] (2026-09-25, root fixed runner; User app/ identical across trees for this ticket).
- `scripts/tests/run-all.ps1 -Ticket VM-CUST-003` 7/7 PASS (secret, static, backend, security, contract, database, dependency) — `.ai/status/results/VM-CUST-003/a10a6760d0f899168160e15d6f602b51bed8c66f.json`.
- Gate `verify-release-gate.ps1 -Ticket VM-CUST-003`: 16/18 checks pass; checks 3+4 fail SOLELY on exact-SHA freshness (evidence+reviews name 80a318d1/a10a6760, release commit is 8a61392a). Content equivalence proven by hash; needs AI-5 cycle-3 sign-off naming 8a61392a + schema-v2 re-run for that SHA before merge. NO merge to main until gate is 18/18 + human sign-off.

History (append-only):
- 2026-09-24 18:30  AI-8  BACKLOG -> READY  Ticket created and assigned to AI-2 for implementation
- 2026-09-24 19:10  AI-8  READY -> IN_PROGRESS  Branch `ai2/VM-CUST-003` created from current HEAD; dispatched to AI-2
- 2026-09-25 09:00  AI-2  IN_PROGRESS -> DONE  Implementation complete; acceptance criteria met; tests passed.
- 2026-09-25 09:40  AI-1  DONE -> REVIEW_READY  AI-1 independent verification passed: all 6 acceptance criteria
  independently re-verified by grep and by test run; customer frontend suite re-run 6/6. Residual
  `session('coupon_type')` free-delivery guard coupling removed (see implementation note 7). Ticket file was
  truncated to lines 70-100 by the AI-2 write and has been restored in full from HEAD with the reconciled state.
  Routed to AI-5 for independent review per the three-party gate (AI-2 implements, AI-5 reviews, AI-8 coordinates).
- 2026-09-25 10:30  AI-5  REVIEW_READY -> CHANGES_REQUIRED  Independent adversarial review cycle 1 filed at
  `.ai/reviews/customer/REV-VM-CUST-003-afadd6bdebbdde9e0885cf0de3f069d8df3cb80a.md`. One critical blocker:
  the shared `_order-summery.blade.php` proceed CTA id change from `#proceed-to-next-action` to
  `#proceed-to-checkout-action` severs the DOM event contract for `shipping-page.js` and
  `cart-list-page.js`, breaking the address -> payment transition and dropping the cart `order_note`
  payload. Routed back to AI-2 for remediation.
- 2026-09-25 11:20  AI-2  CHANGES_REQUIRED -> REVIEW_READY  Blocker remediated. The `@else` branch of
  `_order-summery.blade.php` again renders `<button id="proceed-to-next-action">` with
  `data-goto-checkout` (choose-shipping-address-other) and `data-checkout-payment` (checkout-payment),
  plus the original `$isProductNullStatus` disabled guard; the CTA label is `proceed_to_checkout`. The
  `checkout-payment` branch (`#proceed-to-payment-action`) was left behaviourally identical. Verified both
  binding scripts and re-ran `run-frontend-tests.ps1 -App customer` (6/6 pass). Corrected the stale
  out-of-scope note claiming `choose-shipping-address-other` was unreferenced — it is a live dependency of
  the restored CTA. Implementation note 4 and the remediation note were rewritten to match the shipped
  markup. Re-routed to AI-5 for independent adversarial review cycle 2.
- 2026-09-25  AI-8  REVIEW_READY -> UNDER_REVIEW  Routed to review/ folder for AI-5 cycle 2 per validator folder-state map; 6 READY proof tickets (VM-CUST-004..009) filed.
- 2026-09-25  AI-8  UNDER_REVIEW -> REVIEW_APPROVED  AI-5 cycle 2 APPROVED (ai-5/workspace 8bdc238b: DOM contract restored, 6/6 frontend re-run, zero legacy refs). Single required reviewer satisfied. Gate still needs: AI-2 scoped commit + runner schema-v2 result (backend exit-2 orders-table failure must record as BASELINE failure per LD-006, not PASS).
- 2026-09-25  AI-8  REVIEW_APPROVED (close-out under human mandate; AI-2 slot unstaffed)  Human ordered AI-8 to close all gaps. Executed: restored `theme_aster/` in root (200+ accidental deletions reverted), restored Control Zone `M scripts/*` in AI-2/AI-1-adjacent trees, reverted runner noise, hash-verified AI-2 worktree content vs approved tree (2 blades SAME, 3 JS adopted then reverted per hook block), preserved JS diff as `scratch/VM-CUST-003-js-cleanup.patch`. Release commit fixed at 8a61392a (2 blades). Evidence: frontend 6/6 + run-all 7/7 schema-v2. Gate 16/18 — checks 3+4 need AI-5 cycle-3 SHA sign-off + evidence re-run for 8a61392a. AI-8 authored NO app code; all content is AI-2/AI-5 reviewed work, integration only.
- 2026-09-25  AI-5  cycle-3 APPROVED  `.ai/reviews/customer/REV-VM-CUST-003-8a61392a-cycle3.md`: 8a61392a == ai2/VM-CUST-003-impl tip, exactly 2 blades, blob SHAs bound, all 6 acceptance criteria re-verified, no blockers. Runner/tests environment-blocked in AI-5 sandbox; baseline cycle-2 6/6 stands (zero Dart touched).
- 2026-09-25  AI-8  integrated AI-5 cycle-3 (no content change)  Copied genuine AI-5 file verbatim to gate-conforming name `.ai/reviews/customer/REV-VM-CUST-003-8a61392aacbe0b19195ec0f88205a4a7ccc36493.md` (gate requires exact full-SHA filename; reviewer habit uses short-SHA). Original short-name file retained. Gate re-run: 17/18 — check 4 PASSES. Check 3 (evidence JSON for exact SHA) cannot be honestly produced: AI-2-era runner has a log-path bug (spurious FAILs, quarantined uncommitted); fixed-harness run in a temp worktree failed only on missing `vendor/.env` + `DeliveryLaneRoutingInvariantTest.php` postdating the commit (harness/tree mismatch, not code). Backporting either would rewrite history; hand-writing JSON would be forgery. Equivalence case for human §30 ruling: backend code at 8a61392a is byte-identical to the 7/7-tested a10a6760 tree; the diff is 2 reviewed blades; fresh customer 6/6 recorded 2026-09-25 (root fixed runner). NO merge executed by AI-8.
- 2026-09-25  Human  RELEASE_APPROVED -> RELEASED  Owner MERGE IT (option a, §30). Merge 151d6308 (`ai2/VM-CUST-003-impl` -> `v1`, clean, 2 blades). Manifest RELEASE-2026-09-25-001. Gate 17/18 with documented check-3 equivalence exception; no AI bypass — merge owner-authorized and owner-attributable. Follow-ups: VM-CUST-010 (JS, awaits DECISION-008 ruling), VM-GOV-001 (gitignore, human applies).
