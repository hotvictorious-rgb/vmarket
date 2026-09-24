# User App + Storefront API Request Tracker (Frontend-Owned)

> **Scope:** Flutter Customer App (`User app/`) + public storefront (`theme_vmarket` views/assets) ONLY.
> **Backend is owned by other AIs.** Frontend AIs must NEVER edit backend code, NEVER revert backend changes — always adapt. Commit ONLY files you personally changed.
> **Canonical backend SSOT:** `docs/api/endpoints_summary.md` + `.agents/rules/VMARKET_CUSTOMER_APP_SPEC.md` + `.agents/rules/VMARKET_STOREFRONT_SPEC.md`.

---

## 1. Process (mandatory for ALL AIs working on User app / storefront)

1. **Need an API?** Add a row below first — do NOT invent client-side business logic (price, fee, stock, eligibility, payment success). Backend decides; frontend renders.
2. **Fill:** ID, date, needed-by (app/screen + button), request shape, response shape needed, why backend must own it, fallback until available.
3. **Notify backend team** (mention API ID, e.g. `FAPI-001`). Do NOT implement it yourself in `backend/`.
4. **Adapt, don't revert:** when backend changes land (`git pull`), re-check your screens against the new contract. If backend renamed a field, update the frontend parser — never `git restore` backend files.
5. **Tick the boxes in this file:**
   - `[x] Requested` — row added + backend team notified.
   - `[x] Backend done` — backend AI confirms route+controller+validation live (link commit).
   - `[x] Integrated` — frontend consumes it (link screen/file:line) + verified on device/browser.
6. **Need another API later?** Append a new `FAPI-00X` row. Never delete old rows — history matters.
7. **Commit rule:** stage ONLY your files, e.g. `git add "User app/..." docs/api/user_app_storefront_api_requests.md`. Never `git add .`, never stage `backend/` PHP you didn't own.

### Status legend
- `[ ]` todo · `[x]` done
- Columns: `R` = Requested · `B` = Backend done · `I` = Integrated + verified

---

## 2. Open requests (User app + web)

### FAPI-001 — Direct checkout-intent status (crash-recovery polling)
- [x] R · [ ] B · [ ] I
- **Date requested:** 2026-09-24 · **Needed by:** `User app/lib/features/checkout/screens/payment_status_screen.dart` (delivery path, "Check Again" button)
- **Problem today:** app scans `GET /api/v1/customer/order/list?limit=5` and matches `order_group_id` client-side. Misses when customer has >5 orders; fragile.
- **Suggested endpoint (backend team to confirm/design):** `GET /api/v1/checkout/intent/{orderGroupId}/status`
- **Suggested response:** `{ order_group_id, intent_status, payment_status, authorization_url?, orders:[{id, status}] }`, auth-scoped to customer, `{success:false,code,message}` errors.
- **Fallback until available:** keep list-scan; do NOT block checkout on it.
- **Backend commit:** _pending_ · **Integrated in:** _pending_

### FAPI-002 — Locked fulfillment response shape (delivery objects + ETA)
- [x] R · [ ] B · [ ] I
- **Date requested:** 2026-09-24 · **Needed by:** `User app` fulfillment model + `checkout_screen.dart` fee/ETA display; storefront shipping step (generic notice only, never personalized SEO HTML)
- **Problem today:** backend sends `delivery.origin_lga/destination_lga` as OBJECTS `{id,name,state}` + `estimated_time`, but app parses them as strings and drops ETA.
- **Suggested contract (backend confirms, frontend adapts):** keep `fulfillment_options.{delivery,pickup}`; guarantee `delivery:{available,fee,estimated_time,eta_display?,origin_lga{id,name,state},destination_lga{id,name,state},reason,message?}` and `pickup:{available,available_times[],earliest_available?,reason,message?}`.
- **Frontend work (ours):** parse objects + render ETA/slots. No backend edit.
- **Backend commit:** _pending_ · **Integrated in:** _pending_

### FAPI-003 — Canonical order-track contract (app + guest web)
- [x] R · [ ] B · [ ] I
- **Date requested:** 2026-09-24 · **Needed by:** app `trackingUri`, `orderTrack`, `orderDetailsTrack`; storefront `track-order.result` form (order ID + phone)
- **Problem today:** three similar endpoints (`track`, `track-order`, `track-order-details`) — unclear which is authoritative.
- **Suggested:** backend declares ONE canonical track contract (order_id + optional phone for guest), deprecates others. Frontend migrates both clients to it.
- **Backend commit:** _pending_ · **Integrated in:** _pending_

### FAPI-004 — `cashback_earned` in delivery order details + web order-placed payload
- [x] R · [ ] B · [ ] I
- **Date requested:** 2026-09-24 · **Needed by:** app `pickup_order_success_screen.dart` earn badge; storefront `checkout/complete` + order confirmation (currently no earn badge on web)
- **Problem today:** pickup reservation exposes `cashback_earned`; delivery order + web confirmation don't consistently include it.
- **Suggested:** include `cashback_earned:{amount,percent}` (nullable) in delivery order details AND web order-placed data. Backend owns eligibility/math.
- **Backend commit:** _pending_ · **Integrated in:** _pending_

### FAPI-005 — Reservation `show` includes `order_id + payment status`
- [x] R · [ ] B · [ ] I
- **Date requested:** 2026-09-24 · **Needed by:** `payment_status_screen.dart` pickup path (fetches reservation → order OTP)
- **Problem today:** OTP lives only on Order; app does reservation→order two-hop fetch. Works, but depends on `show` returning `order_id`.
- **Suggested:** guarantee `GET /api/v1/customer/pickup-reservations/{code}` returns `{reservation:{code,state,order_id?,payment_status?}}` (order_id null until settled). No OTP on reservation (stays on Order — IDOR-safe).
- **Backend commit:** _pending_ · **Integrated in:** _pending_

---

## 3. Frontend-owned fixes backlog (no API needed — do these in `User app/` / `theme_vmarket`)

- [ ] **FF-01:** Parse fulfillment LGA objects + render `estimated_time` and `available_times` (model + checkout UI).
- [ ] **FF-02:** `payment_status_screen.dart` token via `flutter_secure_storage` (never `SharedPreferences`).
- [ ] **FF-03:** Standardize `use_cashback` to bool on intent path; keep legacy 1/0 shim untouched.
- [ ] **FF-04:** Keep `estimatedCashback/totalPayable` in checkout labeled estimate-only; never send to backend (only `useCashback` bool is sent).
- [ ] **FF-05:** Continue cart decoupling (done: `b154835c`) — no fee math in cart.

---

## 4. Rules for frontend AIs (read every time before editing)

1. Backend is SSOT. Flutter/storefront RENDER backend decisions; never calculate fees, prices, cashback, stock, eligibility, or payment success.
2. One concept → one implementation. Search for legacy (`shipping_cost`, `CartShipping`, `digital-payment`, `delivery_city/state/hub`) and classify KEEP/MIGRATE/DEPRECATE/REMOVE.
3. Check every button: screen → controller → service → route → response → screen. Dead buttons are bugs.
4. Web storefront = discovery layer, not a second engine. No personalized fee/availability in indexable HTML or JSON-LD.
5. Never touch `backend/app|routes|config|database|services` PHP. Allowed: `User app/**`, `backend/vmarket-web/resources/themes/theme_vmarket/**`, `backend/vmarket-web/public/themes/theme_vmarket/**` (views/assets only), plus this tracker doc.
6. Never revert backend files. Adapt frontend parsers to new backend shapes.
7. Commit only your own files with `[AI]` tag + update `AI_CHANGELOG.md` in the same commit.
8. Tick this tracker: `B` when backend lands, `I` when you integrate + verify. Add future needs as new `FAPI-00X` rows.
