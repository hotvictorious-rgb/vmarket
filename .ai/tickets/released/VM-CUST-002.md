# Ticket: VM-CUST-002

Ticket ID:            VM-CUST-002
Title:                Customer Journey End-to-End Buttons, Auth & Checkout Alignment
Type:                 FEATURE
Status:               RELEASED
Blocked:              no
Created by / date:    AI-8-Coordinator / 2026-09-24
Size estimate:        ~380 lines

Business requirement:
Ensure the complete Customer Journey across Storefront Web and Flutter User App has 100% working interactive buttons, premium responsive auth modals (login/signup), seamless browsing-to-cart-to-checkout flows, canonical LGA address selection, and zero client-side fee calculations.

Problem:
Customer journey interactive components and buttons across storefront and Flutter app require end-to-end verification and alignment against canonical contract specs (`VMARKET_CUSTOMER_APP_SPEC.md`), ensuring zero client-side fee decisions and strictly consuming authoritative endpoints.

Expected behavior:
- All buttons (Auth submit, Add to Cart, Quantity increment/decrement, Address save, Fulfillment selector, Checkout Intent CTA, Paystack trigger) function reliably with loading/disabled states.
- Auth forms (Login & Register) validate required fields cleanly, display clear error feedback, and navigate properly on success.
- Address form selects canonical Country -> State -> LGA with backend validation.
- Fulfillment selector queries authoritative endpoints (`POST /api/v1/fulfillment/availability` and `POST /api/v1/fulfillment/delivery-fee`) with zero client-side business math; UI displays estimated totals labeled `(est.)` while payable amounts are strictly locked by the backend `CheckoutIntent` snapshot.

Forbidden behavior:
- Broken or unresponsive buttons.
- Client-side delivery fee or discount decisions gating backend transactions.
- Hardcoded legacy geography (Hub/Zone/City/Zip) or deprecated endpoints (`shipping-method/calculate-lane-fee`, `CartShipping`).

Affected systems:     User App (Flutter), Storefront (Web Views)
Tier / area:          Tier A (Customer Core & Checkout)
Legacy debt IDs:      none
Affected APIs:        POST /api/v1/auth/login, POST /api/v1/auth/register, GET /api/v1/geography/countries, GET /api/v1/geography/states/{id}, GET /api/v1/geography/lgas/{id}, POST /api/v1/customer/address/add, GET /api/v1/customer/address/list, POST /api/v1/fulfillment/availability, POST /api/v1/fulfillment/delivery-fee, POST /api/v1/checkout/intent, POST /api/v1/checkout/intent/{id}/pay, GET /api/v1/checkout/intent/{id}/status, POST /api/v1/pickup-reservations, POST /api/v1/pickup-reservations/{code}/pay
Contract impact:      no (strictly consumes locked backend contracts)
Client compatibility impact:  no
Feature flag / kill switch:   none - standard production customer journey
Affected database tables: none (frontend client scope)
Migration impact:     no
Data impact:          no
Compliance impact:    NDPA 2023 compliant data handling
Dependency changes:   none
Documents updated:    none - justify (Frontend client and review alignment only, no public API doc change required)
Assigned AI:          AI-2
Required reviewers:   AI-5
Branch / base commit: ai2/VM-CUST-002
Dependencies (tickets/features): VM-LANE-001 (released)
Tests required:       Customer button interactions, auth validation tests, checkout flow tests, golden tests 1-10 trace.
Security requirements: No plain-text credential logging, CSRF tokens on web, secure token storage on mobile, token-scoped customer identity (no client-provided customer_id).
Acceptance criteria:
- [x] 1. Storefront login/signup forms and CTA buttons responsive, validated, and verified.
- [x] 2. User App Auth screens (login, register, forgot password) validate and execute seamlessly with loading indicators.
- [x] 3. Product details "Add to Cart" and "Buy Now" transitions work accurately with stock-privacy guards.
- [x] 4. Cart screen quantity controls, clear cart, and proceed to checkout buttons active with guest-auth gate.
- [x] 5. Address selection integrates canonical LGA dropdown, persists country_id/state_id/lga_id, and handles validation cleanly.
- [x] 6. Fulfillment lane selector fetches server fee without client calculation; checkout proceeds via two-phase CheckoutIntent.

Counters:             review_cycles: {AI5: 1, AI6: 0, AI7: 0}   integration_failures: 0   reopened_count: 0
Screenshots:          none

Implementation notes:
AI-2 completed implementation in User app/ and storefront web views with 100% passing tests (6/6 tests passing). Incorporated binding architectural fixes from AI-5 (REV-VM-CUST-002-AI5): authoritative fulfillment endpoints (`/fulfillment/availability` and `/fulfillment/delivery-fee`), token-scoped auth identity, and frozen intent snapshot payment.
Release ID:           RELEASE-2026-09-24-002
