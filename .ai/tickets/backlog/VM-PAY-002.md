# Ticket Template (Appendix A)

Ticket ID:            VM-PAY-002
Title:                Third-party manual settlement skips admin revenue recognition (commission/fee/tax)
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-28 (mechanics verified file:line during payment-line audit; prescription corrected — see notes)
Size estimate:        small (one method + sandbox ledger proof; no new tables)

Business requirement:
Problem:            `OrderManager::disburseSettledVendorOrder` (`Utils/OrderManager.php:415-491`) flips hold→disburse, decrements `pending_amount`, and credits the seller 90% — but never credits `AdminWallet.commission_earned`, `delivery_charge_earned`, or `total_tax_collected`. Per `V1_BUSINESS_RULEBOOK.md` §12 every third-party sale owes VMarket 10% (5 cashback pool + 5 retained), so the platform leg of settled money is invisible on the admin ledger while cashback awards draw against it. Worse, `PaystackRefundService.php:595-629` reverses "90% seller / 10% admin commission" on refunds — against an accrual that never happened (clamped subtraction masks the misattribution and can eat unrelated accruals).
Expected behavior:  Manual settlement accrues the admin leg with the SAME arithmetic the in-house delivered path uses, inside the existing locked txn (idempotency inherited from `:425-429`): `commission_earned += commission`, `delivery_charge_earned += shipping_cost` (only when VMarket is the shipping-responsible party — mirror `:194,:332-350` responsibility logic), `total_tax_collected += total_tax`. Sandbox proof: pre/post `AdminWallet` + `SellerWallet` + `OrderTransaction` balance table with Δ=0.00 across two consecutive runs (second run must be a no-op).
Forbidden behavior: No hardcoded commission rate (current `:440` hardcodes 10% — must use per-order `admin_commission` like `:193`, honoring per-merchant customization). No touching the early-return boundary (`:187-189` stays — auto-disburse remains blocked). No double-credit path (all writes inside the locked idempotent txn). No prod data (sandbox ledger only).
Affected systems:     VendorSettlementService, OrderManager settlement, AdminWallet ledger, refund accounting
Tier / area:          A (money ledger correctness)
Legacy debt IDs:      none
Affected APIs:        admin manual settlement action (existing); no contract change
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (ledger fix behind existing admin action)
Affected database tables:     admin_wallets, seller_wallets, order_transactions, orders (sandbox rows only)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (proof lives in review file; math proof updated at release)
Assigned AI:          BACKEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-PAY-002 (from current `v1`; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
Work order:
- **Goal:** Accrue the admin revenue leg in manual third-party settlement with per-order rates and locked-txn idempotency, proven by a sandbox Δ=0.00 ledger table.
- **Branch:** `backend/VM-PAY-002` from current `v1`.
- **Allowed files:** `app/Utils/OrderManager.php` (`disburseSettledVendorOrder` only); own ticket notes.
- **FORBIDDEN:** early-return boundary, in-house path, refund service, Flutter/Blade/assets, Control Zone, results/reviews/changelog.
- **Acceptance:** (1) per-order `admin_commission` used (hardcoded 10% removed; mismatch vs order rate logged); (2) shipping-responsibility gate mirrors in-house logic; (3) sandbox ledger table pre/post + rerun-no-op proof with Δ=0.00; (4) refund-path consistency note (post-fix accrual covers the `:595-629` reversal basis); (5) `recognizeDeliveryFeeUponCustomerReceipt` (currently dead, zero callers) either wired or deleted — no third variant; (6) suites green.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-PAY-002` full HEAD (JSON uncommitted, report counts); push; BACKEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; testing sqlite + sandbox only.
Dependencies (tickets/features): VM-PAY-001 (live-fire; this fix should land before its ledger proof runs)
Tests required:       sandbox ledger table + rerun idempotency; suites green; run-all JSON at full SHA
Security requirements: locked-txn idempotency for all new writes; no prod data; per-order rates only
Acceptance criteria:
- [ ] Admin leg accrued with per-order rate + responsibility gate (evidence: code + ledger table)
- [ ] Rerun is a byte-identical no-op, Δ=0.00 (evidence: two-run table)
- [ ] Refund reversal basis covered (evidence: note tracing `:595-629`)
- [ ] Dead helper wired or deleted (evidence: grep-zero for orphans or call site)
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (ledger fix; evidence is balance tables)

Implementation notes:
- Verified 2026-09-28: early return `:187-189` + idempotency guards (`:180-182` auto path, `:425-431` manual path) all present; `executeManualSettlement:162-177` wraps disburse in outer txn + writes audit `Transaction`; `:212-220` delivery-earned touch belongs to the UNDELIVERED-refund path only; `BUSINESS_RULES.md:763` mandates per-merchant commission (kills any hardcoded-rate fix).
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-28  Reviewer AI  BACKLOG (filed)  Pasted escrow analysis verified claim-by-claim; prescription corrected (per-order rate, responsibility gate, dead helper, Δ=0.00 proof). Implementation NOT started — money movement needs explicit human go-ahead.
