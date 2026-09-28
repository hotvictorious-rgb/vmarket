# Ticket Template (Appendix A)

Ticket ID:            VM-PAY-001
Title:                Money-Path Audit — Paystack live keys, webhook, intent freeze, cashback ledger
Type:                 FEATURE
Status:               IN_PROGRESS (Reviewer-led read-only evidence pass; live sandbox walk pending Backend capacity)
Blocked:              no
Created by / date:    Human operator / 2026-09-26
Size estimate:        audit (report + defect tickets; fixes ship under own tickets)

Business requirement:
Problem:            Nothing may launch until money is proven correct: Paystack live credentials + webhook handling, two-phase checkout intent freeze, and CustomerCashback ledger writes have never been verified end to end on the current tree.
Expected behavior:  Audit report proving (or refuting with file:line evidence) each link: intent creation freezes authoritative snapshot → Paystack redirect + callback/webhook → atomic payment row lock + double-execution guard → order settlement → cashback ledger entry with zero drift. Every defect becomes its own ticket.
Forbidden behavior: No live charges during audit (sandbox only). No product-code fixes under this ticket. No production credentials in tickets, logs, or prompts.
Affected systems:     Checkout, Paystack gateway, Order settlement, Cashback ledger
Tier / area:          A
Legacy debt IDs:      none
Affected APIs:        POST /api/v1/checkout/intent, Paystack callback/webhook, fulfillment/availability
Contract impact:      no (audit only; fixes may follow)
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (audit only)
Affected database tables:     payment_requests, checkout_intents, orders, customer_cashback_ledger (read-only verification)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (audit only; report lives in review file)
Assigned AI:          BACKEND AI   (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-PAY-001 (from current `v1`)
Work order:           (Reviewer AI exact-prompt work order — single-coordinator session, 2026-09-28; native subagent dispatch unavailable so Reviewer ran the read-only evidence pass directly. Backend live-fire walk still required for formal BACKEND_DONE)
- **Goal:** Prove/refute each money-path link with file:line evidence, sandbox only, zero product-code changes.
- **Branch:** `backend/VM-PAY-001` from `v1@e994ecad` when Backend capacity lands (this audit branch `reviewer/VM-PAY-001-audit` holds evidence only; NEVER merge it).
- **Allowed:** read-only inspection of checkout/Paystack/settlement/cashback controllers+services+routes+migrations; writes to this ticket copy only.
- **Forbidden:** product-code edits; Flutter/Blade/assets; Control Zone; `.ai/status/results/**`; `.ai/reviews/**`; `AI_CHANGELOG.md`; live charges; prod credentials.
- **DONE:** sandbox double-delivery log + ledger-row-vs-total numeric proof + full `run-all.ps1 -Ticket VM-PAY-001` at HEAD (JSON uncommitted per hook) + defects filed inline. Report BACKEND_DONE as branch+SHA.
Dependencies (tickets/features): none
Tests required:       sandbox-only end-to-end walk with per-step evidence (element/handler/route/controller/service/table/verdict); existing invariant + security suites stay green
Security requirements: atomic payment locks + double-execution guard verified live; no real credentials; sandbox only
Acceptance criteria:  (checklist; each item gets an evidence link)
- [ ] Intent freeze proven: snapshot immutable between intent and settlement (evidence: per-step audit rows)
- [ ] Paystack callback + webhook both settle exactly once under duplicate delivery (evidence: double-delivery test log)
- [ ] Cashback ledger entry matches settled total with zero drift (evidence: ledger row vs order total)
- [ ] Every defect filed as its own ticket with file:line + severity (evidence: ticket IDs)

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes (Frontend stage only if fixes need UI)
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (audit; evidence is logs + ledger rows)

Implementation notes:
- **Link 1 intent freeze — PROVEN (code):** SHA-256 fingerprint + key-format check + same-key replay / 409 conflict + immutable snapshot + `DB::transaction` (`app/Services/DeliveryCheckoutIntentService.php:76-79,260-294,372-404`); settlement consumes the snapshot (`DeliveryOrderSettlementService.php:437 createOrdersFromDeliverySnapshot`, `:583 pruneSnapshotCartItems`).
- **Link 2 exactly-once — PROVEN (code):** callback (`PaystackController.php:128-201`) + webhook (`:529-602`, HMAC-SHA512 `:541`) converge on `settleVerifiedPayment`; `is_paid==1` → `ALREADY_PAID`; reference isolation + `hash_equals` (`DeliveryOrderSettlementService.php:76-103`).
- **Link 3 settlement locks — PROVEN (code):** `DB::transaction + lockForUpdate` on intent/request/customer; atomic stock `decrement` with `>=` guard, `$affected===0` → `PostPaymentStockFailureException` → reconciliation txn (`:525-546,295-314`).
- **Link 4 cashback zero-drift — PROVEN (code):** BCMath calc, atomic increment under caller's lock, capture/release keyed by `order_group_id` (`PickupCashbackAwardService.php:23-33,83-121`; settlement `:209-238,318-319`).
- **Link 5 OTP/IDOR — PARTIAL:** intent endpoints `auth('api')` + `where(customer_id)` scoped (`DeliveryCheckoutIntentController.php:73,129,206,214,251`); 6-digit/expiry/attempt bounds on payment OTP paths not fully traced — Backend to complete.
- **Suites run:** security `PaymentFulfillmentBoundarySecurityTest.php` 21/21 PASS Δ=0.00 (MAIN tree, standalone); invariants `DeliveryLaneRoutingInvariantTest.php` 8/8 OK (REVIEWER_AI tree, 1 pre-existing PHPUnit deprecation).
- **Defects:** none found in read-only pass → no defect tickets. Observation (not filed): webhook rider `delivery_payment` branch does unlocked `$order->update` (`PaystackController.php:604-625`); receipt invariant holds (never marks delivered) — Backend to rule during live-fire whether the COD surface stays.
- **Pending for BACKEND_DONE:** live sandbox double-delivery log; ledger-row-vs-total numeric proof; full `run-all.ps1 -Ticket VM-PAY-001` at HEAD (JSON uncommitted per hook).
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-26  Human  BACKLOG (created)  Launch-sequence ticket 1 of 4: money path must be proven before vendors onboard.
- 2026-09-28  Reviewer AI  BACKLOG -> IN_PROGRESS (evidence pass, single-coordinator session)  Chose VM-PAY-001 over VM-CUST-003 (Tier B, retired AI-2 assignment) and VM-THEME-001 (Tier C): Tier A money-first per launch sequence. Read-only audit banked (links 1-4 code-PROVEN, link 5 partial, suites green, zero defects). Live sandbox walk + full run-all deferred to Backend capacity under the pasted work order.
- 2026-09-28  Reviewer AI  IN_PROGRESS (live-fire BLOCKED on keys)  Key audit: no `PAYSTACK_*` in testing `.env`, no `config/paystack.php`; `PaystackBankService::getSecretKey` reads DB `addon_settings` or env — neither provisioned here. Double-delivery + ledger live proofs need human-provisioned Paystack SANDBOX keys (never prod). Server :8000 up on testing sqlite, ready when keys land. Related: VM-ORD-001 released (import fix); VM-ORD-002 BLOCKED escalated (Author repoint decision).
