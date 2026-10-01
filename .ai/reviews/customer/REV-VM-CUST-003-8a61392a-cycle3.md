# Independent Adversarial Review — VM-CUST-003

Ticket:                   VM-CUST-003 — Storefront Web Cart Alignment & Legacy Shipping Elimination
Reviewer:                 AI-5 (Customer Domain Independent Reviewer)
Model/tool used:          Muse Spark serving AI-5 logical role
Commit reviewed:          8a61392aacbe0b19195ec0f88205a4a7ccc36493
Branch binding:           ai2/VM-CUST-003-impl tip == 8a61392aacbe0b19195ec0f88205a4a7ccc36493 (verified via `git rev-parse ai2/VM-CUST-003-impl`)
Parent:                   03b088491a10d8b3e18e059c7cae84ea705f48bb (merge-base with 80a318d1; both lines fork here)
Review cycle number:      3 (sign-off: 2-blade commit vs cycle-2 APPROVED tree)
Work mode:                detached-HEAD read-only inspection (`git show` / `git ls-tree` / `git diff` only); no code, test, or branch writes; no commits
Files reviewed (full diff 8a61392a^..8a61392a — exactly 2 blades, +20 / -184):
  - backend/vmarket-web/resources/themes/theme_vmarket/theme-views/cart/cart-details.blade.php (5 ins, 123 del)
  - backend/vmarket-web/resources/themes/theme_vmarket/theme-views/partials/_order-summery.blade.php (15 ins, 61 del)
Blob binding at 8a61392a:
  - cart-details.blade.php → f3be04267cd5890f550aa5aa06f7219639f5c5d3
  - _order-summery.blade.php → 95e852b1acebc15cf63a6d1066f7620a347a2a05
Contract references inspected read-only at 8a61392a (unmodified, byte-identical to pre-fix base 03b08849):
  - .../public/assets/js/cart-list-page.js (blob 6cac6bc86404152c02025cf83dd284886f181332)
  - .../public/assets/js/cart.js (blob b53a3ea64d871b50fc1a871f4174f820cd8c7f53)
  - .../theme-views/layouts/partials/_route-for-js.blade.php (blob 09e7cabd24c487185f1df2641dd99994d98644fa)
  - .../public/assets/js/shipping-page.js and payment-page.js (consumer-contract trace only)
Tests run by reviewer:
  - `scripts/tests/run-frontend-tests.ps1 -App customer` → RUNNER PARSE FAILURE, pre-existing and out of scope (see Testing findings). Same blob efbff278de0e24403771ce35f7bf2828083c70bc at HEAD, 8a61392a, and base 03b08849.
  - Direct `flutter test` in `User app/` → ENVIRONMENT-BLOCKED: `flutter pub get` resolved, then build aborted with "Building with plugins requires symlink support. Please enable Developer Mode". No test executed; working tree left clean (`git status --porcelain` unchanged).
  - Baseline carried: cycle-2 independently re-ran customer frontend suite 6/6 PASS on 2026-09-25. This diff touches zero Dart code, so no Flutter regression vector exists.
  - DOM event-contract trace at 8a61392a: #proceed-to-next-action bindings in cart-list-page.js and shipping-page.js vs @else CTA; #proceed-to-payment-action binding in payment-page.js vs checkout-payment branch.
  - Repo-wide legacy grep at 8a61392a in both blades for CartShipping|ShippingType|getShippingMethods|coupon.apply|promo-code|coupon_discount|getReferralDiscountAmount → zero code references (comment-only hits for VM-CUST-003 scope notes).

Business-rule findings:
  - PASS: cart-details.blade.php has zero references to CartShipping / ShippingType / Helpers::getShippingMethods (parent had full legacy stack: CartShipping/CartShipping queries, ShippingType lookups, getShippingMethods dropdown). No shipping dropdown rendered in cart.
  - PASS: _order-summery.blade.php has zero code references to coupon input, promo-code, referral discount. Summary shows Item Total (item_price), Product Discounts, Sub Total, Estimated Victorious Points cashback at loyalty_point_earn_rate_percent with 5.0 default, Total, Estimated Tax (item tax only, conditional on VAT config), plus delivery-fees-at-checkout footnote. Presentation-only cashback; settlement authority remains backend. Matches ticket acceptance criteria 1, 2.
  - PASS: Cycle-1 blocker remediated and present in committed tree. @else branch emits <button id="proceed-to-next-action" data-goto-checkout="customer.choose-shipping-address-other" data-checkout-payment="checkout-payment" type="button"> with $isProductNullStatus disabled guard and proceed_to_checkout label — exact-markup match to the cycle-2 APPROVED description.
  - PASS: Free-delivery guard (@if $free_delivery_status['status']) behaviour-preserving for canonical path; order_note textarea retained (session order_note preserved, handler reachable via restored id).
  - SCOPE NOTE (non-blocker, stated plainly): strict full-tree hash-equality with the cycle-2 working tree (80a318d1 + uncommitted 5-file tree) is NOT confirmable from committed history — that working tree was never committed. Blade-scoped equality IS confirmed: both committed blades satisfy every cycle-2 APPROVED assertion verbatim. The JS/_route-for-js dead-code removals described in cycle-2 (setShippingIdFunction/renderCouponCodeApply removal, #set-shipping-url span removal) are absent here — those files at 8a61392a are byte-identical to pre-fix base. Retained dead JS is inert (no blade emits the selectors anymore) and falls inside the ticket's declared out-of-scope follow-ups (cycle-2 non-blocker #3). Not a regression.

Security findings:
  - PASS: No new IDOR. No route/controller/model change. Cart AJAX paths untouched by this diff; session scoping unchanged.
  - PASS: CSRF posture unchanged (no AJAX call-site modified in this commit).
  - PASS: Quantity sanitization unchanged (backend validation untouched).
  - PASS: order_note flow preserved through restored id; no new injection sink. No mass-assignment surface (Blade display only).

Prompt-injection / untrusted-input findings:
  - No AI-interpreted content, no new rendering of user-generated HTML. order_note passes through pre-existing session/escaping paths. No prompt-injection surface.

Frontend findings:
  - PASS: #proceed-to-next-action contract restored and consumable — cart-list-page.js binds the id; shipping-page.js binds the same id and reads data('checkout-payment'), which the blade provides. Address→payment transition no longer loops; cart order_note no longer dropped.
  - PASS: checkout-payment branch (#proceed-to-payment-action, data-goto-checkout=checkout-details, inert per payment-page.js which reads only data-type + radio) behaviourally identical to cycle-2 approved state. Undocumented value retained as-is; harmless, non-blocker (carried from cycle-2).
  - OBSERVATION (non-blocker): JS dead code retained (setShippingIdFunction/renderCouponCodeApply definitions + calls, .set-shipping-onchange/.set-shipping-id handlers, #set-shipping-url span). Inert — zero emitted selectors from the new blades — but recommend removal in a dedicated cleanup ticket. Do NOT expand this ticket.
  - OBSERVATION (non-blocker, carried from cycle-2): three translate keys are new and absent from messages.php (delivery_fees_calculated_at_checkout, estimated_victorious_points_cashback, estimated_tax). translate() self-heals into new-messages.php with human-readable defaults; seed canonical entries in messages.php as follow-up for deterministic builds.

Backend findings:
  - N/A (no backend route/controller/model change in this diff). Contract impact: none claimed, confirmed — web routes unchanged.

Integration findings:
  - PASS: Cart → choose-shipping-address-other → checkout-payment chain restored via data attributes consumed by the (unmodified) page scripts. set-shipping-method route now unreferenced from cart blades — correctly flagged as follow-up candidate, not a blocker.

Client compatibility findings:
  - N/A (web-only theme change, no mobile API change; zero Dart files touched).

Testing findings:
  - ENVIRONMENT-BLOCKED, NOT a code signal: (1) run-frontend-tests.ps1 fails to parse under Windows PowerShell 5.1 at the `[✓]` line — file has no BOM, so the UTF-8 checkmark breaks the PS5.1 parser. Identical blob at base/HEAD/impl → pre-existing tooling issue, out of ticket scope, reviewer must not edit (CONTROL ZONE read-only). Recommend a test-infra owner re-save with BOM or drop the non-ASCII glyph in its own ticket. (2) Direct flutter test blocked by missing symlink support (Developer Mode) in this sandbox. Working tree verified clean afterwards.
  - BASELINE: cycle-2 recorded 6/6 PASS same-day (2026-09-25); this blades-only diff cannot regress Dart tests. Release gate must still record a fresh 6/6 from a Flutter-capable environment before launch — tracked as a gate action, not a ticket blocker.

Performance findings:
  - N/A (no query change except removal of per-row ShippingType/CartShipping lookups — strictly fewer queries on cart render).

Dependency findings:
  - None (no manifest change; only Blade deletions/additions).

Privacy / data-impact findings:
  - N/A (ticket declares Data impact: no; confirmed — no PII, no log change).

Design / localization findings:
  - Non-blocker only: seed the 3 new translation keys in messages.php (see Frontend findings) so all locales render deterministically without relying on new-messages.php auto-creation at runtime.

Blockers:
  - None. Cycle-1 critical blocker verified remediated in the committed tree by exact-markup inspection + both consumer-script traces. All 6 ticket acceptance criteria satisfied by blade contents.

Non-blockers:
  1. JS hygiene follow-up (dedicated ticket): remove setShippingIdFunction/renderCouponCodeApply dead code from cart.js + cart-list-page.js re-init chains and the orphaned #set-shipping-url span from _route-for-js.blade.php; confirm zero remaining #set-shipping-method references. Inert today; do not expand VM-CUST-003.
  2. Seed delivery_fees_calculated_at_checkout / estimated_victorious_points_cashback / estimated_tax in resources/lang/en/messages.php (low priority — runtime fallback already handles it).
  3. Document the checkout-payment branch data-goto-checkout value (checkout-details, inert — payment-page.js ignores it) wherever release notes enumerate the diff.
  4. Test-infra (separate tickets, not this one): fix run-frontend-tests.ps1 PS5.1 parse (BOM or ASCII glyph); release gate to record a fresh customer 6/6 from a Developer-Mode-enabled runner.
  5. Strict full-tree hash-equality with the uncommitted cycle-2 working tree cannot be reconstructed from history; blade-scoped equality (the ticket's "(2 blades)" scope) is confirmed via blob SHAs above. Future remediation commits should be staged visibly so reviewers can bind exact SHAs.

Decision:                 APPROVED
