# Delivery App API Request Tracker (Rider-Owned)

> **Scope:** Delivery Rider App (`Delivery Man App/`, GetX + `lib/helper/get_di.dart`) ONLY.
> **Field-operations client.** Backend owns availability, fees, assignment, ownership, payment, stock, settlement, geography, hubs (per `VMARKET_DELIVERY_APP_SPEC.md` §1). Delivery AIs must NEVER edit backend code, NEVER revert backend changes. Commit ONLY files you personally changed.
> **Canonical refs:** `docs/api/endpoints_summary.md` · `.agents/rules/VMARKET_DELIVERY_APP_SPEC.md` · `ARCHITECTURE.md` (Delivery section).

---

## 1. Process (mandatory for ALL AIs working on Delivery app)

1. **Need an API?** Add a `DAPI-00X` row below first — do NOT decide dispatch, fees, eligibility, or settlement client-side. Display authorized work, submit action, render server state.
2. **Fill:** date, needed-by (screen/file + button), request/response shape, identity scope (rider token → hub assignment), why backend must own it, offline fallback.
3. **Notify backend team** with the API ID. Do NOT implement it in `backend/`.
4. **Adapt, don't revert:** on `git pull`, re-check against new contracts. Update parsers, never `git restore` backend files.
5. **Tick the boxes:**
   - `[x] Requested` — row added + backend notified.
   - `[x] Backend done` — backend AI confirms route+policy live (link commit).
   - `[x] Integrated` — app consumes it (link file:line) + field-verified (assigned → pickup → transit → delivered).
6. **New need later?** Append a row. Never delete history.
7. **Commit rule:** stage ONLY your files (e.g. `git add "Delivery Man App/..." docs/api/delivery_app_api_requests.md`). Never `git add .`, never stage `backend/` PHP. `[AI]` tag + `AI_CHANGELOG.md` in same commit.
8. **Stack rules:** GetX (`.obs` for maps/geo, minimal repaints); tokens ONLY in `flutter_secure_storage` — migrate any `shared_preferences` credential fallback.

### Status legend — `R` Requested · `B` Backend done · `I` Integrated + verified

---

## 2. Open requests (delivery app)

### DAPI-001 — Hub-scoped assigned queue with lane + ETA display
- [x] R · [ ] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** home dashboard + `current-orders` / `all-orders` screens (Accept, Start transit buttons)
- **Problem:** rider must see ONLY backend-assigned work for their hub; needs lane origin→destination + ETA to execute — never to compute fees.
- **Suggested (backend to confirm):** assigned-queue payload includes `{order_id, shop{...}, customer{masked}, origin_lga, destination_lga, eta_display, hub{id}, state}`; hub assignment derived from rider token.
- **Fallback:** current list as-is; no client-side assignment logic.
- **Backend commit:** _pending_ · **Integrated in:** _pending_

### DAPI-002 — Vendor pickup OTP verify (+ resend)
- [x] R · [ ] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** order-details pickup sheet (Verify pickup OTP button)
- **Problem:** handoff vendor→rider must be backend-verified, 6-digit, attempt-bounded; rider needs resend/error states.
- **Suggested:** confirm `verify-pickup-otp` (+ resend if provided) with `{success:false,code,message}` on expired/locked/mismatch. App shows server message verbatim.
- **Backend commit:** _pending_ · **Integrated in:** _pending_

### DAPI-003 — Customer delivery OTP verify + proof-of-delivery upload
- [x] R · [x] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** delivery sheet (Verify delivery OTP, Upload proof photo buttons)
- **Problem:** completion rider→customer must be backend-verified; proof image is evidence, not authorization.
- **Suggested:** confirm `verify-order-delivery-otp` + `order-delivery-verification` image upload linkage (proof attached to verified delivery, size/type validated server-side).
- **Backend commit:** `API_CONTRACT_REGISTRY.md` §5 (`POST /api/v2/delivery-man/verify-order-delivery-otp`, `POST /api/v2/delivery-man/order-delivery-verification`) · **Integrated in:** _pending_

### DAPI-004 — Cash-in-hand reconciliation (collect vs remit)
- [x] R · [ ] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** wallet screens (`delivery-wise-earned`, withdraw request/list, cash screens)
- **Problem:** rider cash handling must reconcile server-side; app must never net balances locally.
- **Suggested:** statement payload `{collected, remitted, cash_in_hand, withdraw_state}` + withdraw lifecycle; all math backend-owned, pessimistic-locked.
- **Backend commit:** _pending_ · **Integrated in:** _pending_

### DAPI-005 — Online/offline presence + push registration stability
- [x] R · [x] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** presence toggle + FCM token update + pause/resume delivery flows
- **Problem:** dispatch depends on reliable presence + push; suspend/blocked riders must see state but get actions rejected server-side.
- **Suggested:** confirm `is-online`, `update-fcm-token`, pause/resume semantics + suspended/blocked error codes the app can render.
- **Backend commit:** `API_CONTRACT_REGISTRY.md` §5 (`PUT /api/v2/delivery-man/is-online`, `PUT /api/v2/delivery-man/update-fcm-token`, `PUT /api/v2/delivery-man/order-update-is-pause`) · **Integrated in:** _pending_

### DAPI-006 — Hide `verification_code` / `pickup_verification_code` from rider order payloads (security hardening, Backend AI)
- [x] R · [x] B · [x] I (N/A — no app change required)
- **Date:** 2026-09-24 · **Needed by:** POD §18/§19 integrity — rider must NOT see the customer 6-digit OTP or vendor pickup code
- **Problem:** bare `Order` Eloquent serialization in delivery-man endpoints ships both OTP columns (`Order.php` `$fillable` L105-106, no `$hidden`) → rider can self-verify delivery without customer consent.
- **Request:** add the two columns to `Order::$hidden`; verify OTPs only via server-side constant-time checks (`hash_equals`); confirm vendor/seller + customer code-delivery paths still source from DB/server, not jailed JSON. Full ticket: `REQ-DELIVERY-20260924-001` in `.agents/sync/INBOX_DELIVERY.md` §2.
- **Backend commit:** `app/Models/Order.php` (`$hidden = ['verification_code', 'pickup_verification_code']`) · **Integrated:** no client change — app is server-OTP-only; verified live via `verify_order_delivery_otp` reading DB constant-time.

---

## 3. Delivery frontend fix backlog (no API needed)

- [ ] **DF-01:** Credential storage audit — `flutter_secure_storage` only; remove `shared_preferences` fallbacks for tokens.
- [ ] **DF-02:** Map/geo repaint audit — GetX `.obs` granularity; no full-screen rebuilds on location ticks.
- [ ] **DF-03:** Button audit: every dispatch/OTP/proof button traces screen → GetX controller → repo → route → server state → screen; no dead buttons.
- [ ] **DF-04:** Suspended/inactive/blocked states render backend message; no client-side access logic.

---

## 4. Rules for delivery AIs (strict)

1. Never decide: availability, fees, assignment, ownership, payment, stock, eligibility, settlement, geography, hubs, cashback, refunds.
2. Identity comes from the rider token. Never trust app-supplied `rider_id`/`hub_id`/`company_id`.
3. OTPs are 6-digit, backend-verified, expiring, attempt-bounded. Proof photos are evidence attached to verified events.
4. Hubs/riders are internal logistics infrastructure — never marketplace geography.
5. Follow `VMARKET_DELIVERY_APP_SPEC.md` §§1-31, the 10-step migration protocol for legacy cleanup, and this tracker's tick flow (`R → B → I`).
