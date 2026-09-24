# Ticket: VM-CUST-003

Ticket ID:            VM-CUST-003
Title:                Storefront Web Cart Alignment & Legacy Shipping Elimination
Type:                 FEATURE
Status:               IN_PROGRESS
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
- [ ] 1. `cart-details.blade.php` has zero references to `CartShipping`, `ShippingType`, or `Helpers::getShippingMethods`.
- [ ] 2. `_order-summery.blade.php` removes legacy coupon input form and aligns summary labels.
- [ ] 3. Quantity increment (+), decrement (-), and delete actions work via AJAX and update row totals.
- [ ] 4. Checkbox selection per item and per shop updates summary calculations.
- [ ] 5. "Proceed to Checkout" CTA links to the authoritative checkout route (`route('checkout-details')`).
- [ ] 6. Customer frontend regression tests pass 6/6.

Counters:             review_cycles: {AI5: 0, AI6: 0, AI7: 0}   integration_failures: 0   reopened_count: 0
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
- 2026-09-24 18:30  AI-8  BACKLOG -> READY  Ticket created and assigned to AI-2 for implementation
- 2026-09-24 19:10  AI-8  READY -> IN_PROGRESS  Branch `ai2/VM-CUST-003` created from current HEAD; dispatched to AI-2
