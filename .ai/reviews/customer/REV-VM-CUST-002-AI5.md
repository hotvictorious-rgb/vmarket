# Independent Adversarial Review — VM-CUST-002

Review ID:            REV-VM-CUST-002-AI5
Ticket:               VM-CUST-002 — Customer Journey End-to-End Buttons, Auth & Checkout Alignment
Reviewer:             AI-5 (Customer Domain Independent Reviewer)
Date (UTC):           2026-09-24
Status of ticket:     READY (no implementation diff; branch `ai2/VM-CUST-002` does not exist)
Governance basis:
  - `.agents/rules/CUSTOMER_APP_ALIGNMENT.md` (20 mandatory rules)
  - `.agents/rules/CUSTOMER_APP_SCENARIO_AUDIT_PROTOCOL.md` (Phases C1–C9, 22-step checklist)
  - `.agents/rules/VMARKET_CUSTOMER_APP_SPEC.md` (77-section canonical contract)
  - `.agents/sync/API_CONTRACT_REGISTRY.md` (authoritative endpoints)
Scope reviewed:       Customer journey buttons, auth validation, canonical LGA address selection,
                      fulfillment lane selector, zero-client-math checkout.

---

## 1. VERDICT

**VERDICT: CHANGES_REQUIRED**

The ticket direction (working buttons, canonical LGA selection, backend-decided fees) is
correct and aligned with the frozen architecture. However the ticket as written contains a
**wrong fulfillment endpoint**, an **incomplete API contract list**, and **acceptance criteria
too narrow** to satisfy the 20 mandatory rules and the C1–C9 audit protocol. It must be
amended before AI-2 implementation begins. No code is approved or rejected by this review
because there is no implementation diff to inspect (see §2).

---

## 2. Review precondition — what was actually inspected

- Ticket file: `.ai/tickets/ready/VM-CUST-002.md` (62 lines, READY, `review_cycles: {AI5: 0,...}`).
- Branch check: `ai2/VM-CUST-002` does not exist; `ai-2/workspace` has no unique commits
  beyond shared HEAD `03b08849`. There is therefore **no AI-2 diff** for button/auth/checkout
  work to verify. This review audits (a) the ticket definition against governance, and
  (b) the current codebase state the ticket will build on, so the gaps below are binding
  entry criteria for implementation.
- Codebase evidence sampled (paths verified to exist):
  - `User app/lib/features/fulfillment/domain/services/fulfillment_service.dart`
  - `User app/lib/features/fulfillment/domain/repositories/fulfillment_repository.dart`
  - `User app/lib/features/fulfillment/controllers/fulfillment_controller.dart:71-79`
  - `User app/lib/features/address/screens/add_new_address_screen.dart` (665 lines; State/LGA
    dropdowns at ~lines 436–518; save/validation at ~lines 562–623)
  - `User app/lib/features/checkout/domain/services/checkout_service.dart`
  - `User app/lib/features/checkout/domain/repositories/checkout_repository.dart:28-61,144-186`
  - `User app/lib/features/checkout/screens/checkout_screen.dart:179,228,289-321,831-834,925-930`
  - `User app/lib/features/cart/screens/cart_screen.dart:192-194,324,476,905`
  - `User app/lib/features/auth/screens/login_screen.dart:114,438-466`
  - `User app/lib/utill/app_constants.dart` (endpoint URIs)
  - `backend/vmarket-web/routes/rest_api/v1/api.php:239-253,379-394`
  - `backend/vmarket-web/resources/views/web-views/customer-views/` (auth blades only)

---

## 3. Critical findings (must fix in ticket before implementation)

### CR-1 — Ticket names a non-existent legacy fulfillment endpoint (BLOCKING)
- Ticket text (§ Expected behavior): fulfillment selector queries
  `POST /api/v1/shipping-method/calculate-lane-fee`.
- Verified reality:
  - That route **does not exist** in `api.php`. The authoritative routes are
    `POST /api/v1/fulfillment/availability` (`FulfillmentAvailabilityController@checkAvailability`)
    and `POST /api/v1/fulfillment/delivery-fee` (`@getDeliveryFee`) — see `api.php:248-253`.
  - `AppConstants` confirms: `fulfillmentAvailabilityUri = '/api/v1/fulfillment/availability'`,
    `fulfillmentDeliveryFeeUri = '/api/v1/fulfillment/delivery-fee'`. No
    `shipping-method/calculate-lane-fee` constant exists.
  - `FulfillmentRepository.checkAvailability/getDeliveryFee` already bind to the
    `fulfillment/*` URIs — correct.
- Governance violated: Alignment Rules §2 (frozen architecture), §14 (legacy cleanup —
  `shipping_method` is an explicitly banned legacy token), Scenario Protocol §1.7
  (legacy shipping models must not drive marketplace checkout), Rule §5 (URL must match
  exactly on both sides).
- Required change: replace every occurrence of
  `POST /api/v1/shipping-method/calculate-lane-fee` with
  `POST /api/v1/fulfillment/availability` (primary selector) and
  `POST /api/v1/fulfillment/delivery-fee` (fee-only, where used). Add a ticket-level
  prohibition: "no `shipping-method` / `CartShipping` / `shipping_cost` references in new code."

### CR-2 — Affected-APIs list is wrong and incomplete (BLOCKING)
- Ticket lists: `/api/v1/auth/login`, `/api/v1/auth/register`,
  `/api/v1/customer/address/add`, `/api/v1/shipping-method/calculate-lane-fee`,
  `/api/v1/checkout/intent`.
- Problems:
  1. Contains the non-existent endpoint from CR-1.
  2. Omits the other half of the two-phase checkout the ticket's own flow requires:
     `POST /api/v1/checkout/intent/{id}/pay` and `GET /api/v1/checkout/intent/{id}/status`
     (`api.php:390-394`).
  3. Omits `POST /api/v1/fulfillment/availability`, `POST /api/v1/fulfillment/delivery-fee`,
     `GET /api/v1/geography/countries`, `GET /api/v1/geography/states/{country_id}`,
     `GET /api/v1/geography/lgas/{state_id}` — all in-scope for criteria #5/#6.
  4. Omits pickup-reservation routes (`POST /api/v1/pickup-reservations`, `.../{code}/pay`)
     even though `checkout_screen.dart:231-276` implements a pickup channel and
     `CheckoutService.createPickupReservation/payPickupReservation` exist.
  5. Omits cart endpoints relied on by criteria #3/#4 (`GET /cart/totals`, `POST /cart/add`,
     etc.).
- Governance violated: Rule §5 (every endpoint touched must be verified on URL, method,
  auth, request/response, error format, pagination); Scenario Protocol 22-step checklist
  items 5–12 (route, controller, service, request/response schema, auth/scope, error contract).
- Required change: rewrite the Affected-APIs field to the verified list above with HTTP
  verbs, and require a per-endpoint contract table (URL, method, auth guard, request fields,
  response fields, error format `{success:false,code,message}`) in the implementation plan.

### CR-3 — Acceptance criteria do not cover the mandatory journey (BLOCKING)
Current criteria #1–#6 cover buttons, auth validate, add-to-cart, cart controls, LGA save,
server fee fetch. Missing, all mandatory per the cited governance:
- Forgot-password / OTP / token-expiry / disabled-account handling (Spec §4, Rules §6, Phase C1).
- Fulfillment directionality proof: Uyo→Uyo available, Uyo→Eket available,
  Eket→Uyo disabled ⇒ unavailable (Rules §8, §18 golden tests 1–3; Phase C4).
- Mixed-fulfillment cart partition, pickup channel, pickup rejection states (Rules §11, §13;
  Phases C4/C6; golden test 5).
- Two-phase intent discipline: intent snapshot immutability, re-query availability at intent
  time (not trusting prior UI response), idempotency key on intent + reservation, duplicate
  payment guard (`$affected > 0`), crash-reopen recovery via `intent/{id}/status`
  (Rules §8, §9; Phases C5/C8; golden tests 6, 8, 10).
- Stock race proof (`lockForUpdate`, deduction only at settlement — Rules §10; golden test 7).
- IDOR/tamper matrix: A-accesses-B order/address/reservation ⇒ 403/404; fake shop/fee/LGA/
  price/cashback rejected (Rules §6, §12; Phase C8; golden test 9).
- Error-contract, loading/shimmer, retry/offline behavior (Rule §5; Protocol checklist 12–14).
- Legacy classification for every legacy token touched (`KEEP/MIGRATE/DEPRECATE/REMOVE` —
  Rule §14; Phase C9) and the 12-item phase-completion report (Rule §15).
- Required change: extend acceptance criteria (or split follow-up tickets explicitly
  referenced) to include the above, mapped to C1–C9 phases and golden tests 1–10.
  A ticket that ships criteria #1–#6 alone cannot satisfy the Final Gate (Rule §19).

### CR-4 — "Zero client-side math" claim is not enforceable as worded (BLOCKING)
- What is clean (verified): `createDeliveryCheckoutIntent` payload sends only
  `address_id, idempotency_key, billing_address_id, use_cashback, cart_item_ids`
  (`checkout_repository.dart:153-162`) — no fee/total/price submitted. Fulfillment
  repository forwards backend `fee` without recomputing. Checkout CTA gates on backend
  `fulfillmentAvailability...delivery.available` before intent (`checkout_screen.dart:305-314`).
  This is the correct pattern and must be locked in the ticket.
- What contradicts the ticket's absolute wording:
  1. `FulfillmentController.getTotalDeliveryFee()` (lines 71–79) sums per-shop backend fees
     client-side (`total += getDeliveryFee(shopId)`).
  2. `checkout_screen.dart:822-834` computes `totalPayable =
     (_order + activeShipping - discount - estimatedCashback + _tax)` and renders it as
     **"Total Payable"** (`lines 925–930`) without an `(est.)` qualifier (only the cashback
     line is labeled estimate). Cart screen similarly sums
     `amount/discount/totalCost` client-side (`cart_screen.dart:192-194,324,476,905`) and
     `CartHelper().calculateVatTax` runs client-side (`checkout_screen.dart:197`).
  4. `CheckoutRepository.digitalPaymentPlaceOrder` (lines 28–61, legacy compat path still
     live at `POST /api/v1/digital-payment`, `api.php:382-384`) submits client-supplied
     `customer_id` — a direct violation of Rules §4 ("never trust `customer_id`"), §6
     (derive customer from token), §12 (IDOR scoping).
- Governance: Spec §1 permits "temporary display values for UX" but Rules §4/§6/§12 forbid
  client-decided prices/fees/cashback/ownership. The ticket's "zero client-side math" /
  "without client calculation" must be refined to: **display-only estimates, clearly labeled,
  never submitted, never gating payment; the payable charged is the backend intent snapshot.**
- Required change: (a) relabel any client-summed total as estimate until replaced by the
  intent snapshot (`intent/{id}/status`) before Paystack; (b) state whether
  `getTotalDeliveryFee` is display-only or removed; (c) require removal or server-scoping of
  the legacy `digitalPaymentPlaceOrder` `customer_id` parameter (or document its
  deprecation with caller migration); (d) add a review checkpoint grepping for
  `delivery_fee|grand_total|shipping_cost|CartShipping` submissions.

### CR-5 — LGA criterion is real but underspecified; legacy residue unaddressed (BLOCKING)
- What is good (verified): `add_new_address_screen.dart` implements canonical
  Country→State→LGA dropdowns (`StateModel`/`LgaModel`, `setSelectedState/setSelectedLga`,
  lines 436–518) with save-time enforcement (lines 570–577 warn when State/LGA unselected)
  and persists `country_id/state_id/lga_id/lgaName` (`lines 584–591`). This satisfies the
  shape of criterion #5.
- Residue the ticket ignores:
  - Free-text `city` field (mirrors LGA name but remains editable), `zip` defaulting to
    `'100001'`, country default `'IN'`/Bangladesh (`line 60,79,585`), `CodePickerWidget`
    country picker, `restrictedCountryList`/`restrictedZipList` gates, map-picked address —
    all coexisting with canonical IDs (Rule §7 requires `country_id→state_id→lga_id→address`
    with backend rejection of mismatches; Rule §2 bans Hub/Route/Corridor/Ward/Area/Zone and
    legacy City/Zip geography in customer flows).
  - `AddressModel` carries both canonical IDs and legacy strings — dual source of truth.
- Required change: criterion #5 must assert (i) LGA dropdown is the **sole** geography source
  (city/zip/country fields either removed, read-only-derived, or proven ignored by backend);
  (ii) backend validation proof (state∈country, LGA∈state, active, owner-scoped; mismatch ⇒
  rejection); (iii) classification of `restricted-country/zip`, `delivery_hubs/states/cities`
  routes (`api.php:231-236` still serve hub geography) as KEEP/MIGRATE/DEPRECATE/REMOVE.

### CR-6 — Storefront scope statement is unverifiable (MAJOR)
- Ticket claims "Storefront Web … login/signup forms and CTA buttons responsive and verified"
  plus "browsing-to-cart-to-checkout flows" in storefront web views.
- Verified: `backend/vmarket-web/resources/views/web-views/` contains only
  `customer-views/auth/login.blade.php` and `register.blade.php`. No storefront cart, address,
  fulfillment, or checkout blades were found. Recent HEAD commits reference storefront auth
  modals, but the ticket provides no file list tying criterion #1 to those views.
- Required change: either (a) list exact storefront files/routes in scope, or (b) narrow
  criterion #1 to the Flutter app + the two auth blades, filing storefront cart/checkout as
  explicit out-of-scope/follow-up. An unverifiable criterion cannot pass review.

---

## 4. Button / journey spot-checks (advisory — to be proven per criterion during implementation)

| # | Area (ticket criterion) | Sampled evidence | Adversarial note |
|---|---|---|---|
| 1 | Storefront auth buttons | `web-views/customer-views/auth/login|register.blade.php` exist; recent commits `3c92d1e5`, `8a3334f9` touch modals | No test evidence in ticket; require responsive + CSRF + error-feedback proof per file. |
| 2 | App auth validate + execute | `login_screen.dart:114` (`if (!isLoading)`), `:438-466` (password empty/length checks, `CustomButton`, `isLoading` gate) | Pattern looks correct; still require OTP/forgot/token-expiry/disabled-account cases (missing from criteria). |
| 3 | Add to Cart / Buy Now | Cart routes + `cart_screen.dart:311-324` guarded CTA exist; `product_details` shipping dialog present | Require stock-privacy ("In Stock"/"Out of Stock", no raw qty), stale-stock revalidation, min-qty guard proof (`cart_screen.dart:881` exists — must be exercised). |
| 4 | Cart qty / clear / proceed | `cart_screen.dart:122-194` qty/amount computation; `:364-431` checkout CTA with guest-checkout gate | Client-side amount summation present (see CR-4); require loading/disabled states + empty-cart + guest-gate tests. |
| 5 | LGA address save | State/LGA dropdowns + null-guards + `country_id/state_id/lga_id` persist (see CR-5) | Require backend mismatch-rejection proof + legacy field disposition. |
| 6 | Lane selector, no client math | `FulfillmentController.checkForAllShops` auto-selects from backend `available` flags (`:36-43`); fee getters render-only | Client-side fee summation + "Total Payable" labeling + legacy `customer_id` path remain (see CR-4); require intent-snapshot-before-pay proof. |

---

## 5. Alignment matrices (ticket definition vs governance)

### 5.1 Customer App Alignment (20 rules) — ticket-level conformance
| Rule | Status | Note |
|---|---|---|
| §1 Authoritative architecture | PARTIAL | One-implementation intent stated ("strictly consumes locked contracts"), but CR-1/CR-2 cite a wrong endpoint and omit phases of the same flow. |
| §2 Frozen architecture (Country→State→LGA; directional lanes; pickup≠lanes) | FAIL | CR-1 legacy endpoint; CR-5 legacy residue; directionality (Uyo⇄Eket independence) absent from criteria. |
| §3 Pre-change protocol | FAIL | Ticket shows no AGENTS/CHANGELOG/impact/ARCHITECTURE reads, no authoritative-impl mapping, no phase confirmation. |
| §4 Backend SSOT / Flutter renders | PARTIAL | Intent payload clean; display summations + legacy `customer_id` path remain (CR-4). |
| §5 API contracts | FAIL | Wrong URL + missing verbs/auth/schemas/error format (CR-2). |
| §6 Authentication | PARTIAL | Login/register listed; token-derived identity, expiry/disabled/rate-limit handling not required. |
| §7 Address & geography | PARTIAL | LGA dropdown present; backend mismatch-rejection + legacy disposition missing (CR-5). |
| §8 Fulfillment availability | FAIL | No re-verification-at-intent, snapshot-immutability, or directionality proof required. |
| §9 Payment | FAIL | Webhook/verification, `$affected>0` guard, failure-scenario matrix (cancel/timeout/crash/duplicate/delay/mismatch) not required. |
| §10 Stock | FAIL | Settlement-only deduction + `lockForUpdate` race proof not required. |
| §11 Pickup security | FAIL | Pickup channel implemented in code but absent from ticket criteria/APIs. |
| §12 IDOR & injection | FAIL | Fake fee/price/LGA/shop/cashback + A-vs-B matrix not required; legacy `customer_id` unaddressed. |
| §13 Order state machine | FAIL | No render-only-states requirement for delivery/pickup lifecycles. |
| §14 Legacy cleanup | FAIL | Banned tokens (`shipping_method`, `CartShipping`, `shipping_cost`, hub/city/zip) not classified. |
| §15 12-item completion report | FAIL | Not required by ticket. |
| §16/§17 34-phase plan & order | PARTIAL | Maps roughly to phases 3/5/8/10/14, but phase mapping unstated and sequenced Alone. |
| §18 Golden tests 1–10 | FAIL | None referenced. |
| §19 Final gate (screen→API→controller→service→DB→response→screen) | FAIL | No trace matrix required. |
| §20 Commit convention | N/A | Pre-implementation; apply at implementation time. |

### 5.2 Scenario Audit Protocol (C1–C9) — coverage of ticket criteria #1–#6
| Phase | Status | Note |
|---|---|---|
| C1 Foundation (auth, geography, address scoping, profile) | PARTIAL | Login/register + LGA dropdown covered; OTP/forgot/session/profile/address-scoping missing. |
| C2 Discovery (catalog, stock privacy) | PARTIAL | "Browsing" asserted without stock-privacy or seller/shop-conflation guards. |
| C3 Cart (storage, qty, revalidation, multi-vendor) | PARTIAL | Qty/proceed covered; stale-stock revalidation + multi-vendor grouping missing. |
| C4 Fulfillment (directional lanes, pickup, mixed partition) | FAIL | Wrong endpoint (CR-1); directionality, pickup availability, mixed partition missing. |
| C5 Checkout & payment (intent, pay, webhook lock, recovery, drops) | FAIL | Intent creation listed; pay/status phases, webhook lock, ambiguous-transport + drop recovery missing. |
| C6 Orders (lifecycles, tracking, OTP handover) | NOT_IN_SCOPE | Correctly out of scope, but pickup-reservation success nav in code touches it — declare boundary. |
| C7 Post-order (cashback, returns, tickets) | NOT_IN_SCOPE | Cashback estimate code touched — declare display-only boundary (CR-4). |
| C8 Abuse (IDOR, tamper, idempotency, races) | FAIL | Entirely absent. |
| C9 Legacy cleanup | FAIL | Entirely absent. |
| 22-step checklist per scenario | FAIL | No screen/controller/service/model/route/controller/service/table/schema matrices provided. |

---

## 6. Required ticket amendments (acceptance gate for re-review)

1. **Fix fulfillment contract (CR-1):** replace `POST /api/v1/shipping-method/calculate-lane-fee`
   with `POST /api/v1/fulfillment/availability` (+ `POST /api/v1/fulfillment/delivery-fee`
   where applicable); ban `shipping-method`/`CartShipping`/`shipping_cost` in new code.
2. **Rewrite Affected-APIs (CR-2):** full verb-qualified list — auth login/register,
   `geography/countries|states/{id}|lgas/{id}`, `customer/address/add|(update|list)`,
   `fulfillment/availability|delivery-fee`, `checkout/intent|intent/{id}/pay|intent/{id}/status`,
   `pickup-reservations(|/{code}/pay)`, cart `totals|add|update|remove` — plus per-endpoint
   contract table (auth guard, request/response fields, error format).
3. **Expand acceptance criteria (CR-3 + §5 matrices):** add directionality (Uyo→Uyo, Uyo→Eket,
   Eket→Uyo-disabled), intent re-verification + snapshot immutability + idempotency,
   payment-failure matrix, stock-race note, IDOR/tamper matrix, error/loading/offline behavior,
   legacy KEEP/MIGRATE/DEPRECATE/REMOVE table, 12-item completion report (Rule §15), and
   golden tests 1–10 disposition (pass/defer-with-ticket-ID).
4. **Harden zero-math wording (CR-4):** "display-only estimates, labeled `(est.)`, never
   submitted; payable charged = backend intent snapshot via `intent/{id}/status` before
   Paystack"; disposition `getTotalDeliveryFee`, cart/VAT summations, and the legacy
   `digitalPaymentPlaceOrder(customer_id)` path (remove/scope/deprecate with migration).
5. **Complete LGA criterion (CR-5):** LGA dropdown as sole geography source; backend
   mismatch-rejection proof; disposition of city/zip/country-picker/restricted-lists/hub
   routes and dual-source `AddressModel` fields.
6. **Fix storefront scope (CR-6):** enumerate exact storefront files/routes or narrow
   criterion #1 with a filed follow-up.
7. **Attach traceability:** per-scenario 22-step matrices (screen → controller → service →
   repository → route → controller → service → tables → response → screen) for every
   criterion, with file:line references; no `review_cycles` increment without them.

---

## 7. Reviewer notes

- This is a **ticket-design rejection, not a code rejection**: the current Flutter/Backend
  code already exhibits the right architecture (backend-decided fees, intent payload without
  totals, LGA dropdowns, guarded CTAs). The ticket must be corrected so AI-2's implementation
  does not regress it toward the legacy `shipping-method` contract or ship an untested
  "Total Payable."
- Re-review entry condition: amended ticket + implementation diff on a real branch with the
  matrices, contract table, and test evidence listed in §6. AI-5 will then execute the
  22-point inspection per scenario and adjudicate PASS/FAIL line-by-line.
- No production-readiness or Final Gate (Rule §19) claim is supported by this ticket in its
  current form.

---

**VERDICT: CHANGES_REQUIRED**
