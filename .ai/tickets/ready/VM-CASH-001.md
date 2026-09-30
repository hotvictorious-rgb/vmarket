# Ticket: VM-CASH-001

Ticket ID:            VM-CASH-001
Title:                Cashback timing split-brain: immediate loyalty credit vs 24h ledger availability (both paths)
Type:                 DEFECT
Status:               READY
Blocked:              no
Created by / date:    FRONTEND AI / 2026-09-30 (external v1 audit finding, deepened with dual-path analysis)
Size estimate:        medium (design decision + settlement changes + tests)

Business requirement:
Rulebook §19: cashback becomes eligible only after customer receipt + 24-hour window with no unresolved dispute.
Problem (verified file:line):
1. `PickupCashbackAwardService.php:107-124` writes `cashback_redemptions` status=`captured` AND increments `users.loyalty_point` immediately at payment settlement — before handover, before `received_at`, before any window.
2. Same pattern on delivery: `DeliveryOrderSettlementService.php:222` (`captured`) with immediate increment alongside.
3. No spend-side availability gate found: loyalty redemption reads the raw `loyalty_point` balance (capped at 10%/order), so credited points are spendable instantly on BOTH paths.
4. A parallel COMPLIANT mechanism exists and is ignored by the spend path: `CustomerCashbackLedger.available_at = $order->refund_window_expires_at` (CustomerCashbackLedger.php:122) + `MatureCustomerCashbackCommand` (matures when `available_at <= now()`).
5. Base-amount inconsistency: pickup computes on `reservation.total_amount` (PickupCashbackAwardService.php:85); rulebook §20 demands eligible merchandise value only.
6. Doc drift: `MatureCustomerCashbackCommand.php:14` cites a "7-day return inspection period" vs the 24h V1 rule.
Expected behavior (one option; Reviewer picks):
- (a) Defer capture until handover + 24h expiry (reserved → released states), or (b) keep immediate capture but gate ALL spending on `available_at`-matured balances. Either way, merchandise-only base, single timing semantic across delivery + pickup.
Forbidden behavior:
- NEVER silently keep two timing semantics. NEVER break the BCMath zero-drift accounting while refactoring.
Affected systems:     Settlement services (delivery + pickup), CashbackRedemption/Ledger models, loyalty spend path, mature command/cron
Tier / area:          A (money-adjacent loyalty liability)
Affected APIs:        CustomerCashbackController (exposes available_at already — keep contract)
Contract impact:      no (internal timing; API already surfaces available_at)
Migration impact:     maybe (if new states/columns; backup point required then)
Data impact:          yes (liability timing) — analyze, don't mutate, in ticket
Documents updated:    none - justify (fix ticket; rulebook already states the rule)
Assigned AI:          BACKEND AI (financial state machine; pessimistic locks + BCMath mandatory)
Required reviewers:   REVIEWER AI
Branch / base commit: TBD from current `v1`
Dependencies:         none (spend-path behavior change; announce in release notes)
Tests required:       settle → assert unspendable pre-window; mature → assert spendable; merchandise-only base under mixed fees; runner green.
Acceptance criteria:
- [ ] No loyalty value spendable before receipt + 24h on either path (evidence: probe/test log).
- [ ] Cashback base = merchandise only (evidence: mixed-fee test).
- [ ] Mature-command docblock matches 24h rule (evidence: diff).

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS.
Screenshots:          N/A

History (append-only):
- 2026-09-30  FRONTEND AI  BACKLOG -> READY  Filed from adjudicated external audit with dual-path file:line proof. Implementation deliberately deferred (state-machine redesign, not a micro-fix).
