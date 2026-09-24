# Vendor Web + Vendor App API Request Tracker (Merchant-Owned)

> **Scope:** Vendor Flutter App (`Vendor app/`, Provider + GetIt) + Vendor Web Panel (Blade) ONLY.
> **Same APIs, same rules, same data** for web and app (per `VMARKET_VENDOR_SPEC.md` §0). Backend is owned by other AIs — vendor AIs must NEVER edit backend code, NEVER revert backend changes. Commit ONLY files you personally changed.
> **Canonical refs:** `docs/api/endpoints_summary.md` · `.agents/rules/VMARKET_VENDOR_SPEC.md` · `ARCHITECTURE.md` (Vendor App section).

---

## 1. Process (mandatory for ALL AIs working on Vendor web/app)

1. **Need an API?** Add a `VAPI-00X` row below first — do NOT invent merchant logic client-side (ownership, stock, order state, payout math, KYC approval, OTP). Backend decides; web/app render.
2. **Fill:** date, needed-by (screen/file + button), request/response shape, authorization scope (`seller_id` + `shop_id`/branch), why backend must own it, fallback.
3. **Notify backend team** with the API ID. Do NOT implement it in `backend/`.
4. **Adapt, don't revert:** on `git pull`, re-check screens against new contracts. Rename parsers, never `git restore` backend files.
5. **Tick the boxes:**
   - `[x] Requested` — row added + backend notified.
   - `[x] Backend done` — backend AI confirms route+policy+validation live (link commit).
   - `[x] Integrated` — web AND app consume it (link both files) + verified.
6. **New need later?** Append a row. Never delete history.
7. **Commit rule:** stage ONLY your files (e.g. `git add "Vendor app/..." docs/api/vendor_web_app_api_requests.md`). Never `git add .`, never stage `backend/` PHP you don't own. `[AI]` tag + `AI_CHANGELOG.md` in same commit.
8. **Stack rules:** Vendor App = Provider + GetIt (`lib/di_container.dart`); tokens ONLY in `flutter_secure_storage`; Feature-First (`lib/features/{x}`).

### Status legend — `R` Requested · `B` Backend done · `I` Integrated + verified

---

## 2. Open requests (vendor web + app)

### VAPI-001 — Branch-scoped order queue (shop/branch isolation)
- [x] R · [ ] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** Vendor app `features/order/` list + Vendor web orders panel (branch filter, accept/prep/handoff buttons)
- **Problem:** merchant with Shop Uyo + Shop Eket must never see/act on the wrong branch's orders; employee scope must hold on both clients.
- **Suggested (backend to confirm):** `GET /api/v3/seller/orders/list?shop_id=&status=` scoped by `seller_id` + branch policy; every mutation re-checks branch ownership. Web + app use identical filters.
- **Fallback:** current unfiltered list; vendor AI must not fake isolation client-side.
- **Backend commit:** _pending_ · **Integrated web:** _pending_ · **Integrated app:** _pending_

### VAPI-002 — Pickup reservation verify / accept / reject (employee-scoped)
- [x] R · [x] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** Vendor app `features/pickup_reservation/` + Vendor web pickup queue (Verify code, Accept, Reject buttons)
- **Problem:** Branch 1 must never inspect Branch 2's reservations; OTP is backend-generated/verified.
- **Suggested:** confirm canonical trio (`verify` / `accept` / `reject`) with employee+branch policy, idempotent accept, rejection releases hold. Both clients show identical states (`pending_inspection → accepted/rejected`).
- **Backend commit:** `API_CONTRACT_REGISTRY.md` §4 (`POST /api/v3/seller/pickup-reservations/*`) · **Integrated web:** _pending_ · **Integrated app:** _pending_

### VAPI-003 — Inventory + 24hr stock-hold visibility (reserved vs available)
- [x] R · [ ] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** Vendor app `features/product/` stock screens + web inventory (hold badges, release on expiry)
- **Problem:** vendor needs to see what is reserved by pickup holds vs truly sellable; app must never compute stock.
- **Suggested:** product/inventory payload includes `{available_qty, reserved_qty, hold_expires_at?}`; holdsOwnedByShop scope. Display-only on both clients.
- **Backend commit:** _pending_ · **Integrated web:** _pending_ · **Integrated app:** _pending_

### VAPI-004 — Payout statement (transactions + withdrawal lifecycle)
- [x] R · [x] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** Vendor app `features/wallet|transaction|bank_info/` + web finance (Withdraw, status, history buttons)
- **Problem:** balance math + 48-hr cooldown + OTP live in backend; clients must show identical states.
- **Suggested:** confirm `transactions?status=`, `balance-withdraw`, `close-withdraw-request` shapes + cooldown/OTP errors in `{success:false,code,message}`; NUBAN resolve + KYC status shared by web/app.
- **Backend commit:** `API_CONTRACT_REGISTRY.md` §6 (`GET transactions`, `POST balance-withdraw`, `DELETE close-withdraw-request`) · **Integrated web:** _pending_ · **Integrated app:** _pending_

### VAPI-005 — KYC + bank-change status (single contract for web + app)
- [x] R · [x] B · [ ] I
- **Date:** 2026-09-24 · **Needed by:** Vendor app `features/bank_info/` + web settings (Submit KYC, Resolve account, Send OTP buttons)
- **Suggested:** one status endpoint consumed by both (`kyc/status` + bank-info state), identical cooldown messaging. No client-side approval logic.
- **Backend commit:** `API_CONTRACT_REGISTRY.md` §6 (`GET kyc/status`, `POST kyc/submit`, `GET paystack/banks`, `POST paystack/resolve-account`, `POST bank-info/send-otp`) · **Integrated web:** _pending_ · **Integrated app:** _pending_

---

## 3. Vendor frontend fix backlog (no API needed)

- [ ] **VF-01:** Audit every order/finance button on BOTH web and app for branch scoping display (hide nothing security-critical — backend enforces; UI only guides).
- [ ] **VF-02:** Tokens/secrets only in `flutter_secure_storage` (app) — remove any `shared_preferences` fallback for auth.
- [ ] **VF-03:** Kill duplicate merchant math (earnings, balances, cooldown timers computed client-side) — render backend values.
- [ ] **VF-04:** Order state buttons mirror backend state machine only (no invented states).

---

## 4. Rules for vendor AIs (strict)

1. Web and app consume SAME endpoints, SAME fields, SAME error format. A fix on one must be mirrored on the other or logged as debt with a `VAPI` row.
2. Branch isolation is server-enforced; client `shop_id` is a filter hint, never proof of ownership.
3. OTPs (bank change, pickup verify) are backend-generated/verified with 6-digit code, 15-min expiry, 5-attempt lockout.
4. Money moves inside `DB::transaction()` + `lockForUpdate()` on backend; frontend never nets balances.
5. Follow §§0-11 of `VMARKET_VENDOR_SPEC.md`, the 10-step migration protocol for legacy cleanup, and this tracker's tick flow (`R → B → I`).
