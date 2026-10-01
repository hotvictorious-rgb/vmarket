# Ticket: VM-CUST-014

Ticket ID:            VM-CUST-014
Title:                State-changing GET set-shipping-method + legacy ShippingMethod cost leak (dead-lane bypass)
Type:                 DEFECT
Status:               READY
Blocked:              no
Created by / date:    FRONTEND AI / 2026-09-30 (found during VM-CUST-013 live audit)
Size estimate:        small (controller + route verb + fee source)

Business requirement:
Checkout shipping must be set via POST with CSRF and priced exclusively from the authoritative `DeliveryLane` matrix.
Problem:
1. `GET customer/set-shipping-method` → `Customer\SystemController@setShippingMethod` WRITES `CartShipping` rows (`insertIntoCartShipping` → `save()`). State-changing GET: CSRF-able, cacheable, prefetch-triggerable.
2. `insertIntoCartShipping` prices via legacy `ShippingMethod::find($request['id'])->cost` on a client-supplied `id` — bypasses `FulfillmentAvailabilityService` / `DeliveryLane` authority. A tampered id selects a cheaper legacy method.
Expected behavior:
- Route becomes POST (CSRF-protected); fee resolved server-side from `DeliveryLane` for the session/order LGA; client-supplied amount/ids never trusted.
Forbidden behavior:
- NEVER trust client fee/amount fields. NEVER keep a GET writer.
Affected systems:     Laravel Backend (SystemController, routes/web), storefront shipping JS (`set-shipping-url` caller)
Tier / area:          A (transaction path, payment-adjacent)
Affected APIs:        GET customer/set-shipping-method (must become POST)
Contract impact:      yes (verb change; JS caller update bundled)
Client compatibility impact: no (same JSON shape)
Migration impact:     no
Data impact:          no
Documents updated:    none - justify (defect fix)
Assigned AI:          BACKEND AI
Required reviewers:   REVIEWER AI
Branch / base commit: TBD from current `v1`
Dependencies:         VM-CUST-013 (found here)
Tests required:       GET returns 405; POST with tampered id still yields lane fee; runner green.
Acceptance criteria:
- [ ] GET set-shipping-method → 405 (evidence: probe log).
- [ ] Lane fee authority proven under tampered id (evidence: probe log).

History (append-only):
- 2026-09-30  FRONTEND AI  BACKLOG -> READY  Filed from VM-CUST-013 D1 with file:line evidence.
