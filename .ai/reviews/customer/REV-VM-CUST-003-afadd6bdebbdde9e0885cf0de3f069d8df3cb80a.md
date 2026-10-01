# Independent Adversarial Review — VM-CUST-003

Ticket:                   VM-CUST-003 — Storefront Web Cart Alignment & Legacy Shipping Elimination
Reviewer:                 AI-5 (Customer Domain Independent Reviewer)
Commit reviewed:          afadd6bdebbdde9e0885cf0de3f069d8df3cb80a (as staged by AI-8 / uncommitted on 80a318d1)
Review cycle number:      1
Files reviewed:
  - backend/vmarket-web/resources/themes/theme_vmarket/theme-views/cart/cart-details.blade.php
  - backend/vmarket-web/resources/themes/theme_vmarket/theme-views/partials/_order-summery.blade.php
  - backend/vmarket-web/resources/themes/theme_vmarket/theme-views/checkout/shipping.blade.php
  - backend/vmarket-web/resources/themes/theme_vmarket/public/assets/js/shipping-page.js
  - backend/vmarket-web/resources/themes/theme_vmarket/public/assets/js/cart-list-page.js
  - backend/vmarket-web/resources/themes/theme_vmarket/theme-views/layouts/partials/_route-for-js.blade.php

Tests run by reviewer:
  - Manual code path trace & adversarial DOM event audit.
  - Verification of CSRF tokens on AJAX routes.

Business-rule findings:
  - Verified: Legacy `CartShipping`, `ShippingType`, and `Helpers::getShippingMethods` calls removed.
  - Verified: Legacy coupon fields removed. Victorious Points cashback correctly estimates at 5% based on `loyalty_point_earn_rate_percent`.

Security findings:
  - No IDOR or new authorization vulnerabilities detected on cart item manipulation.
  - CSRF tokens are correctly verified on AJAX cart endpoints.
  - Quantity sanitization is enforced by `minimum_order_qty` bounds in the DOM and backend.

Prompt-injection / untrusted-input findings:
  - `order-note` payload bypasses validation inadvertently (see Frontend findings below).

Frontend findings (CRITICAL):
  - **CRITICAL BLOCKER**: The ID `#proceed-to-next-action` was changed to `#proceed-to-checkout-action` on the `<a>` tag inside the shared `_order-summery.blade.php` component (`@else` branch).
  - The `_order-summery.blade.php` partial is shared across `cart-details` (Cart), `shipping` (Address), and `checkout-payment` (Payment).
  - **Shipping / Address Page Broken**: In `shipping.blade.php` (checkout details), the `shipping-page.js` client controller binds to `#proceed-to-next-action` to validate the delivery address form and submit it via POST to transition to `checkout-payment`. Because the button is now a direct link (`href="{{ route('checkout-details') }}"`), JS validation does not fire. Users clicking it reload the address page in an infinite loop and can NEVER proceed to payment.
  - **Cart Page Broken**: In `cart-details.blade.php`, `cart-list-page.js` binds to `#proceed-to-next-action` to POST the cart `order_note` to the session before redirecting. The new anchor tag bypasses this execution entirely, dropping the customer's text payload.

Backend findings: 
  - N/A (Backend API routes remained un-mutated and invariants passed).

Integration findings:
  - Order flow transition from Address form to Payment is broken. End-to-end checkout pipeline is severed for web storefront.

Blockers:
  1. Restore `#proceed-to-next-action` button behavior or DOM contract for the `else` branch in `_order-summery.blade.php` to ensure `shipping-page.js` and `cart-list-page.js` can successfully hook click events and serialize the necessary POST payloads before navigation.

Non-blockers: 
  - Consider explicitly passing a rendering flag (e.g., `['hide_proceed_button' => true]`) to `_order-summery.blade.php` and keeping the step-specific buttons inside the parent wrapper blades (`shipping.blade.php`, `cart-details.blade.php`) if reusing the summary component across all three checkout lifecycle stages leads to excessive `@if(str_contains(request()->url()))` complexity.

Decision:                 CHANGES_REQUIRED