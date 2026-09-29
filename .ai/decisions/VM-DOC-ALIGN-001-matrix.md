# VM-DOC-ALIGN-001 — Contradiction Matrix (READ-ONLY scan, no code touched)

Date: 2026-09-29 — Reviewer AI, single-coordinator session, human run-order "align every single doc".
Method: every claim below was read in-file (file:line cited). Nothing inferred from memory.
Rule for this pass: describe reality, mark conflicts, decide nothing — decisions live in DECISION-COD-001 / DECISION-REFUND-001 (both REQUESTS) plus prior human rulings where already given.

## Ranking Box (human read-first)

1. **COD is genuinely undecided.** Newer policy docs prohibit it; the codebase hardens (not deletes) COD branches with idempotency guards; the rider wallet model, v3 seller controller, and refund service all carry live COD paths. → DECISION-COD-001.
2. **Refund execution is genuinely undecided.** Root `BUSINESS_RULES.md:258` mandates automated Paystack refunds; V1 direction + prior planning say manual (Approved ≠ Refunded). The async refund machinery exists and is sophisticated. → DECISION-REFUND-001.
3. **Everything else below is reconcilable without new policy** (terminology, stale counts, historical marks, one float-cast defect filed separately).

## Matrix

| # | Rule | Current code behavior (verified) | Docs agreeing | Docs conflicting | Verdict | Action |
|---|------|----------------------------------|---------------|------------------|---------|--------|
| 1 | COD in V1 | SUPPORTED-BUT-GATED: `cash_in_hand` live in `DeliveryManController`, wallet model, v3 seller `OrderController`; Sept-19 directive hardened (idempotent) rather than deleted; `place_order()` COD variant decommissioned (403) | Backend spec §COD-prohibition, INBOX_DELIVERY, LOGISTICS_POLICY rewrite, delivery spec §24 | `.ai/BUSINESS_RULES.md` §3 (pending/COD states), root `BUSINESS_RULES.md` legacy passages, refund service COD legs, WhatsApp/commerce docs | CONTESTED | DECISION-COD-001; no code/doc edits either way until ruled |
| 2 | Pickup model | SUPPORTED, two-code (`reservation_code` RES + 6-digit `pickup_verification_code`), reserve→inspect→accept/reject→pay→settle→OTP-collect; `PickupReservationService` active; reservation holds ZERO stock (24h TTL) | `.ai/ARCHITECTURE.md` §2, root rules, V1RB, customer/vendor specs | Older generic order-flow passages (imply classic pay-first) | ALIGNED (code wins; generic passages are heritage, not contradiction) | Standardize terms per §Terminology below; no behavior change |
| 3 | Pickup ≠ COD | Code keeps them distinct (pickup pays online post-inspection; COD pays cash at door) | Architecture two-code doc, LOGISTICS_POLICY | Any doc conflating them (none found in canonical specs; legacy commerce docs) | ALIGNED | Lock the distinction into the rulebook rewrite when approved |
| 4 | Stock deduction timing | Settlement-stage atomic decrement (`DeliveryOrderSettlementService`, `PostPaymentStockFailureException` reconciliation); reservation holds nothing | Service code, V1RB | `.ai/BUSINESS_RULES.md` §3.3 ("reserved when order enters pending") — CONTROL ZONE, not edited here | DOC-DRIFT (control file) | Proposed wording in DECISION record; human approves |
| 5 | Refund execution | BOTH exist: manual request/approve flow + automatic Paystack execution (`PaystackRefundService`, async states) | Root `BUSINESS_RULES.md:258` (auto) | V1 direction + planning (manual) | CONTESTED | DECISION-REFUND-001; no edits either way until ruled |
| 6 | Coupons vs Points | Coupons DECOMMISSIONED from checkout/intent/settlement/mobile (Sept-22 unification); Victorious Points authoritative; coupon controllers hardened but legacy | Customer spec §audit list, V1RB (defers coupon matrices), changelog | `.ai/BUSINESS_RULES.md` §2 (lists coupons among backend-calculated items — true but legacy-flavored); old WhatsApp/commerce docs | ALIGNED (engine gone; references are heritage) | Note heritage status; no code action |
| 7 | Money precision | BCMath ledgers (Δ=0.00 proven live); **but** `GeographyController` + `FulfillmentAvailabilityService` cast `fee` to `(float)` at the API boundary | Contract (decimal strings), `.ai` rules (float forbidden) | The two cast sites | CODE-DRIFT (minor) | Filed as defect ticket (not in this doc pass) |
| 8 | Geography | `Country → State → LGA` enforced; Ward/Area/Hub banned from customer flows (`CUSTOMER_APP_ALIGNMENT.md:40-47`) | All 6 specs, lanes, Admin alignment | None in canonical docs | ALIGNED | None |
| 9 | Mixed fulfillment | Partitioned intents (delivery intent + pickup reservation, decoupled) — NOT one blended checkout | Customer spec, RULEBOOK model | `V1_BUSINESS_RULEBOOK.md:809` defers "mixed single checkouts" (compatible: defers the blended variant, not partitions) | ALIGNED (terminology note added) | None |
| 10 | Delivery fee authority | Backend lane lookup, frozen in intent snapshot (verified in code) | All specs, PAY-001 audit | Legacy frontend-calc references in old docs | ALIGNED | None |
| 11 | Vendor isolation | Scoped queries enforced; payout IDOR filed separately; branch matrix partially proven | Specs, AGENTS §9 | None conceptually | ALIGNED (with open defect ticket, not a doc problem) | None here |
| 12 | OTP standards | 6-digit, exact identity, 15-min, 5-attempt (verified in code + live) | Specs, AGENTS §9 | Older 4-digit mentions in heritage docs | ALIGNED | Standardize terms |
| 13 | Proof authority | `VICTORIOUS_MARKET_..._PROOF.md` (81KB) mixes current proofs with Sept-18-era assumptions + "100/100" claims | Nothing current cites it as authority | Its own historical assumptions | HISTORICAL | Banner added this pass (below); re-certify per release |
| 14 | Ticket truth | `STATUS.md` generated 2026-09-26; branch has moved (14 releases since) | Generator script exists | The stale file itself | STALE (mechanical) | Regenerated this pass via `generate-status.ps1` |
| 15 | Worktree map | `.ai/ARCHITECTURE.md` §3 describes nested worktrees + `origin/main` trunk; reality is sibling worktrees, trunk is `v1` | 3-AI charters (current) | The §3 diagram | DOC-DRIFT (control file) | Proposed correction in decision record; human approves |

## Terminology (locked for this pass; to be enforced in the rulebook rewrite)

- **Pickup Reservation** — ₦0.00 hold for physical inspection; holds zero stock; 24h TTL.
- **Reservation code** (`RES-XXXXXXXX`) — check-in identity, not payment.
- **Pickup verification code / Handover OTP** — 6-digit, post-settlement, authorizes release.
- **Delivery verification code** — 6-digit, rider→customer, authorizes `delivered`.
- **Rider pickup code** — vendor→rider handover secret (NOT customer-facing).
- **Settlement** — the atomic payment/order/inventory transaction. **Collection** — physical handover. Never interchange them.

## Decision list (human-only)

- D1 (DECISION-COD-001): Is COD a supported V1 path (gated) or prohibited? Pickup≠COD locked either way.
- D2 (DECISION-REFUND-001): Manual-only refunds, or keep automatic Paystack execution?
- D3: Approve the `.ai/BUSINESS_RULES.md` §3.3 + §6.1 wording corrections (stock timing, gateway list) — CONTROL ZONE, needs explicit approval.
- D4: Approve the `.ai/ARCHITECTURE.md` §3 worktree/trunk correction — CONTROL ZONE, needs explicit approval.
- D5 (already ruled, recorded): no unregistered selling; TIN deferred; no auto-approve; VM/VV/VD marks; NG default.

## What this pass changed (all reversible via git)

1. `VICTORIOUS_MARKET_..._PROOF.md` — historical-evidence banner prepended (root file, no control header).
2. `.ai/status/STATUS.md` (+ METRICS) — regenerated via `generate-status.ps1` (script-generated file, designed for it).
3. Two DECISION-REQUEST records created (no approvals forged).
4. Zero product-code changes. Zero Control-Zone content edits. Open defect ticket filed for the float-fee casts.
