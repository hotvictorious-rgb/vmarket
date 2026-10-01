# Ticket: VM-CUST-011

Ticket ID:            VM-CUST-011
Title:                Storefront Click-by-Click Customer Journey Audit — DOM → Endpoint → Backend Authority
Type:                 AUDIT
Status:               READY
Blocked:              no
Created by / date:    AI-8 / 2026-09-25
Size estimate:        ~1 audit report (no production code changes; defects get their own tickets)

Business requirement:
Prove — by walking the live storefront button-by-button from landing page to order confirmation — that every click in the customer journey reaches its correct backend endpoint, renders a backend-authoritative result, and never falls back to a legacy or client-computed decision. Per human directive 2026-09-25 ("test button by button customer journey, what is clicked, how it shows, what happens next, with their corresponding endpoints, aligned with rules").

Problem:
VM-CUST-003/010 proved *static* code hygiene (dead references, DOM id preservation) by inspection. No agent has yet exercised the **live click chain** end-to-end against the running backend, so a button can render correctly, be correctly named, and still route to a legacy handler. The scenario protocol (`CUSTOMER_APP_SCENARIO_AUDIT_PROTOCOL.md` §3, points 5–12 and 19–21) requires exactly this: per scenario, name the route, verb, controller, service, DB fields, and the stale/legacy/missing/contradictory findings.

Method (mandatory — audit only, AI-8 authors no app code):
Backend AI maintains the server at `http://127.0.0.1:8000`. Walk the storefront there. For **each** click below, record: element id/class/data-attr → JS handler (file:line) → HTTP verb + URL → Laravel route name → controller method → service → DB tables touched → verdict.

Expected behavior (the click chain to prove):
1. **Nav cart count** — `#update_nav_cart_url` → `POST cart.nav-cart` → `CartController@updateNavCart`.
2. **Add to cart** — product page → `POST cart.add` → `CartController@addToCart`. Assert stock revalidation is server-side, not trusted from the form.
3. **Qty +/−** — `POST cart.updateQuantity` (`cart.js`, `cart-list-page.js`). Assert negative/zero/huge qty is sanitized server-side.
4. **Remove item** — `POST cart.remove`. Assert AJAX re-render does not resurrect removed rows.
5. **Item / shop checkbox** — `POST cart.select-cart-items` → `updateCheckedCartItems`. Assert the server recomputes the selected total and the client does not.
6. **Order note** — `POST order_note`. Assert it returns `{status, redirect}`.
7. **Proceed to next** — `#proceed-to-next-action` → `cart-list-page.js:3 proceedToNextAction()` → posts `order_note`, then redirects to `route-checkout-details` (`checkout-details`). **Note the discrepancy to verify:** the button also carries `data-goto-checkout={{route('customer.choose-shipping-address-other')}}` (line 157 of `_order-summery.blade.php`) which `proceedToNextAction()` never reads — confirm whether that attribute is dead in the cart path and live only in the shipping path.
8. **Checkout details / shipping** — `checkout/shipping.blade.php` + `shipping-page.js`. `#proceed-to-payment-action` → `shipping-page.js:321-322` reads `data-checkout-payment` and `data-goto-checkout`. Assert the address/LGA saved here is canonical `country → state → lga` and that **no** hub/route/zone is offered as geography (ALIGNMENT §2, §7).
9. **Choose payment** — `payment-page.js:168` submits the form id bound to the selected payment method. Assert the destination is the two-phase intent (`DeliveryCheckoutIntentService`), not a direct order insert.
10. **Pay now** — `POST customer.web-payment-request` → `PaymentController@payment` → Paystack redirect. Assert the storefront never declares success on redirect alone; success is read back from backend state (ALIGNMENT §9).
11. **Order placed / success** — `order-placed`, `order-placed-success`. Assert `cashback_earned` is rendered from `CustomerCashbackLedger` and that Victorious Points are display-only.
12. **Account order + track order** — `account-oder`, `account-order-details*`, `track-order/*`. Assert every query is scoped to the authenticated customer (ALIGNMENT §12 — Customer A must not read Customer B's order).

Findings already filed by AI-8 (verify, then report; do not fix in this ticket):
- **F-01 (security, HIGH) — state-changing GET.** `routes/web/routes.php:342` registers `Route::get('set-shipping-method', 'setShippingMethod')`. `SystemController@setShippingMethod` (line 31) writes to the DB via `insertIntoCartShipping` (line 44) and returns JSON. A GET with a write side effect is not CSRF-protected by Laravel's token middleware, so any page on the internet can make a victim's browser hit `GET /customer/set-shipping-method?id=<method>&cart_group_id=<group>` and mutate their cart-shipping row. Confirm reachability and report.
- **F-02 (legacy debt, MEDIUM) — legacy cost still authoritative in a live route.** `insertIntoCartShipping` line 52 does `ShippingMethod::find($request['id'])->cost` and persists `CartShipping.cost`. This is LEGACY-SHIPPING-001, which the authoritative/legacy table in `CLAUDE.md` maps to `DeliveryLane` + `FulfillmentAvailabilityService`. VM-CUST-010 removed the *frontend* caller; the *route and controller* remain. Confirm whether any storefront path can still reach them, and whether the resulting `CartShipping.cost` reaches a total.
- **F-03 (auth, MEDIUM)** — the `customer.set-shipping-method`, `customer.set-payment-method`, `customer.choose-shipping-address*` routes (`routes.php:340-346`) carry no `customer` middleware. `setPaymentMethod` guards internally via `auth('customer')->check()`; `setShippingMethod` has **no** auth check at all. Confirm and report.

Forbidden behavior:
- NEVER edit application code under this ticket. It is an audit; defects are reported and split into new tickets by AI-8.
- NEVER change `backend/**`, `User app/**`, or any theme file.
- NEVER `git add .` / `git add -A`. Audit artifacts only, exact-path.
- NEVER report a step as PASS without the file:line + route + verb triple. Unverified = UNVERIFIED.
- NEVER edit DB config or web config to make a step pass.

Affected systems:     customer/storefront-web (audit of), backend/vmarket-web (read-only)
Tier / area:          A (checkout-critical path)
Legacy debt IDs:      LEGACY-SHIPPING-001, LEGACY-COUPON-001
Affected APIs:        none changed — read-only trace of routes/web/routes.php + the 12 steps above
Contract impact:      no
Client compatibility impact: no
Feature flag / kill switch: none - audit
Affected database tables: none changed
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    `.ai/reviews/storefront/AUDIT-VM-CUST-011.md` (new)
Assigned AI:          AI-1
Required reviewers:   AI-5
Branch / base commit: ai1/VM-CUST-011 (from current v1 HEAD)
Dependencies:         VM-CUST-010 (in REVIEW — audit must describe the post-CUST-010 chain, not the pre-merge chain)
Tests required:
- Live click trace, 12 steps, each with the file:line → route → controller → service → tables record.
- Explicit verdict per step: PASS / FAIL / UNVERIFIED.
- Findings F-01..F-03 confirmed or refuted with evidence.
Security requirements:
- Do not run destructive requests. Do not write to any table other than the auditing agent's own scratch cart.
Acceptance criteria:
- [ ] 1. All 12 steps traced with the full record triple (evidence: audit report table)
- [ ] 2. F-01, F-02, F-03 each confirmed or refuted with file:line + route evidence
- [ ] 3. Every UNVERIFIED marked as such; zero steps silently reported PASS
- [ ] 4. Audit report committed to `.ai/reviews/storefront/AUDIT-VM-CUST-011.md` on `ai1/VM-CUST-011`
- [ ] 5. Defects split into numbered follow-up tickets and posted to INBOX_COORDINATOR

Counters:             review_cycles: {AI5: 0, AI6: 0, AI7: 0}   integration_failures: 0   reopened_count: 0
Screenshots:          Attach screenshots for any FAIL step; PASS steps need the trace table only.

Implementation notes:
Review notes:
To be populated by AI-5 during review.
Final decision:
Pending audit and review.
Release commit:
Pending.

History (append-only):
- 2026-09-25  AI-8  FILED -> READY  Opened under human directive for button-by-button customer journey audit. F-01/F-02/F-03 pre-filed from AI-8 static trace.
