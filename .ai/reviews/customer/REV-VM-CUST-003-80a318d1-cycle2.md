# Independent Adversarial Review — VM-CUST-003

Ticket:                   VM-CUST-003 — Storefront Web Cart Alignment & Legacy Shipping Elimination
Reviewer:                 AI-5 (Customer Domain Independent Reviewer)
Model/tool used:          Muse Spark serving AI-5 logical role
Commit reviewed:          80a318d14e1f775ea6ee2f45f2456178873fad26 + uncommitted AI-2 remediation working tree (5 ticket files)
Review cycle number:      2
Files reviewed:
  - backend/vmarket-web/resources/themes/theme_vmarket/theme-views/partials/_order-summery.blade.php
  - backend/vmarket-web/resources/themes/theme_vmarket/theme-views/cart/cart-details.blade.php
  - backend/vmarket-web/resources/themes/theme_vmarket/public/assets/js/cart.js
  - backend/vmarket-web/resources/themes/theme_vmarket/public/assets/js/cart-list-page.js
  - backend/vmarket-web/resources/themes/theme_vmarket/theme-views/layouts/partials/_route-for-js.blade.php
  - backend/vmarket-web/resources/themes/theme_vmarket/public/assets/js/shipping-page.js (contract reference, unmodified)
  - backend/vmarket-web/resources/themes/theme_vmarket/public/assets/js/payment-page.js (contract reference, unmodified)
  - backend/vmarket-web/app/Utils/OrderManager.php::getFreeDeliveryOrderAmountArray (read-only verification)
  - backend/vmarket-web/app/Utils/language.php translate() fallback (read-only verification)
  - scripts/tests/run-backend-tests.ps1 (cosmetic-only diff, out of ticket blast radius)
Tests run by reviewer:
  - scripts/tests/run-frontend-tests.ps1 -App customer → 6/6 PASS, runner [OK] (independently re-run by AI-5, 2026-09-25)
  - DOM event-contract trace: cart-list-page.js:3-29 vs _order-summery @else CTA; shipping-page.js:287-371 vs same CTA; payment-page.js:168-179 vs checkout-payment CTA
  - Repo-wide grep for CartShipping|ShippingType|getShippingMethods|coupon.apply|promo-code|coupon_discount|getReferralDiscountAmount in theme_vmarket cart paths → zero code references (comments only)
  - Verified $shipping_type / $shippingMethod fully eliminated from cart-details.blade.php (no undefined-variable residue)
  - Verified OrderManager::getFreeDeliveryOrderAmountArray has zero coupon dependency (lines 2022-2073)
  - Verified translate() auto-creates missing keys into new-messages.php with human-readable fallback (language.php:19-51)

Business-rule findings:
  - PASS: Legacy CartShipping / ShippingType / Helpers::getShippingMethods fully removed from cart-details and _order-summery. No shipping dropdown rendered in cart.
  - PASS: Legacy coupon form (route coupon.apply, #promo-code, session coupon_discount) and referral discount (CustomerManager::getReferralDiscountAmount) removed from summary. Summary shows Item Total, Product Discounts, Sub Total, Estimated Tax (item tax only), Estimated Victorious Points cashback at loyalty_point_earn_rate_percent with 5.0 default, Total, plus delivery-fees-at-checkout footnote. Presentation-only cashback; settlement authority remains backend. Matches ticket acceptance criteria 1, 2.
  - PASS: Cycle-1 blocker remediated. @else branch again emits <button id="proceed-to-next-action" data-goto-checkout="customer.choose-shipping-address-other" data-checkout-payment="checkout-payment" type="button"> with $isProductNullStatus disabled guard and proceed_to_checkout label. Both consumer scripts reachable again.
  - PASS: Free-delivery guard change (@if $free_delivery_status['status']) is behaviour-preserving for canonical path; OrderManager function reads only free_delivery web configs + cart grand total, no coupon session key. Removes stale suppression vector where a leftover coupon_type=free_delivery session key could hide the indicator after coupon input removal.

Security findings:
  - PASS: No new IDOR. Cart AJAX (quantity +/-, delete, select-cart-items) remains session-scoped via CartManager::getCartListQuery; no cross-session id accepted from client.
  - PASS: CSRF preserved — cart-list-page.js order_note POST sends _token; select-cart-items uses X-CSRF-TOKEN header; shipping-page.js address POST uses X-CSRF-TOKEN header.
  - PASS: Quantity sanitization unchanged (minimum_order_qty bounds enforced in cart.js updateCartCommon path, backend validation untouched by this diff).
  - PASS: order_note payload still flows through cart-list-page.js POST to #order_note_url session endpoint (handler reachable again after id restore); no new injection sink introduced. Server-side sanitization of order_note is pre-existing backend behaviour, unchanged.

Prompt-injection / untrusted-input findings:
  - No AI-interpreted content, no new rendering of user-generated HTML. order_note is stored via session POST and rendered through pre-existing escaping paths. No prompt-injection surface.

Frontend findings:
  - PASS (was CRITICAL BLOCKER in cycle 1, now fixed): #proceed-to-next-action contract restored. cart-list-page.js binds that id to persist order_note then redirect to response.redirect or #route-checkout-details; shipping-page.js binds same id to validate #address-form / #billing-address-form, POST serialized address to data-goto-checkout, redirect to data-checkout-payment. Both attributes present with correct routes. Address→payment transition no longer loops; cart order_note no longer dropped.
  - PASS: checkout-payment branch (#proceed-to-payment-action) behaviourally identical — payment-page.js reads only data-type + radio data-form; its data-goto-checkout value is inert. Noted the value changed from customer.choose-shipping-address-other to checkout-details in the diff; harmless but undocumented — listed as non-blocker.
  - PASS: Dead-code removal correct — setShippingIdFunction + renderCouponCodeApply calls removed from cart.js / cart-list-page.js re-init chains; orphaned #set-shipping-url span removed from _route-for-js.blade.php; no remaining JS references to #set-shipping-method (verified by diff).
  - Observation (non-blocker): three translate keys are new and absent from messages.php (delivery_fees_calculated_at_checkout, estimated_victorious_points_cashback, estimated_tax). translate() self-heals by writing human-readable defaults into new-messages.php on first render, so no raw-key UI break. Recommend seeding canonical entries in messages.php in a follow-up for deterministic builds.

Backend findings:
  - N/A (no backend route/controller/model change in this diff). Contract impact: none claimed, confirmed — web routes unchanged.

Integration findings:
  - PASS: Cart → checkout-details → checkout-payment chain restored. data-goto-checkout target route customer.choose-shipping-address-other retained (ticket out-of-scope note corrected — it is a live dependency, not dead code). set-shipping-method route now unreferenced from cart blades (correctly flagged as follow-up candidate, not a blocker).

Client compatibility findings:
  - N/A (ticket declares no client impact; web-only theme change, no mobile API change).

Testing findings:
  - PASS: Customer frontend suite 6/6 (geography, pickup/cashback math, notifications) re-run independently by AI-5. Ticket acceptance criteria 1-6 map cleanly to verified diff.
  - Noted: full run-backend-tests.ps1 exit 2 from pre-existing DB-less ExampleTest (no such table: orders) is unrelated to this blades+JS-only diff; tracked separately per ticket note 9. No re-run required for this verdict, but release gate must still record it as baseline failure, not PASS.

Performance findings:
  - N/A (no query change except removal of per-row ShippingType/CartShipping lookups — strictly fewer queries on cart render).

Dependency findings:
  - None (ticket declares none; confirmed — only cosmetic [X]→[FAIL] / check→OK string change in run-backend-tests.ps1, no manifest change).

Privacy / data-impact findings:
  - N/A (ticket declares Data impact: no; confirmed — no PII, no log change).

Design / localization findings:
  - Non-blocker only: seed the 3 new translation keys in messages.php (see Frontend findings) so all locales render deterministically without relying on new-messages.php auto-creation at runtime.

Blockers:
  - None. Cycle-1 critical blocker verified remediated by exact-markup inspection + both consumer-script traces + regression suite.

Non-blockers:
  1. Consider seeding delivery_fees_calculated_at_checkout / estimated_victorious_points_cashback / estimated_tax in resources/lang/en/messages.php (follow-up, low priority — runtime fallback already handles it).
  2. Document the checkout-payment branch data-goto-checkout value change (choose-shipping-address-other → checkout-details) as inert/cosmetic in the ticket, since payment-page.js ignores that attribute.
  3. Follow-up candidates already listed in ticket (set-shipping-method route, custom.js renderCouponCodeApply, CartManager-level legacy models) remain correctly out of scope; do not expand this ticket.
  4. run-backend-tests.ps1 string-only change ([X]/check → [FAIL]/[OK]) is unrelated noise in this diff — harmless, but keep test-runner edits in their own ticket going forward.

Decision:                 APPROVED
