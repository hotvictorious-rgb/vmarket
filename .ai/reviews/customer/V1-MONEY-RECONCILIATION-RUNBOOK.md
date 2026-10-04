# V1 money migration and historical reconciliation runbook [AI]

Purpose: operate the new per-order tracking safely. This runbook does not authorize production deployment, bank transfers or historical balance edits. It does not infer bank cash from application wallet counters.

## Before staging

Record code SHA, migration list, database engine/version, timezone and backup/restore evidence. Use a disposable staging copy with anonymized production-like orders and provider fixtures; never run migrate:fresh against a configured production database. Disable real gateway/email/SMS jobs on staging.

Inventory every held/eligible/disputed historical order, outstanding withdrawal, pending payment attempt and refund. Record canonical provider reference, actual cash capture, original redeemed reward, tax/logistics snapshots and already executed refunds/payouts. Snapshot administrator wallet aggregate and vendor/rider reserved amounts before changes.

## Apply and inspect

Deploy reviewed code/migrations together during a controlled window. Run the standard migrations on staging and verify new order tracking fields exist. Historical NULL means unknown, not zero. Execute `php artisan finance:reconcile-v1 --json` against the intended staging database and retain its raw output with the code SHA/date. The diagnostic is read-only and may exit nonzero for expected historical exceptions.

The second-pass migration `2026_10_04_000001_add_frozen_refund_allocations.php` adds nullable `order_details.refund_allocation`. New previews freeze exact line allocations once under the order lock. Historical completed refunds must match recorded merchandise, cash and reward allocations before remaining lines can be frozen; missing or conflicting evidence blocks processing. The general reconciliation command is not proof that every historical line allocation is valid. Historical rider withdrawals without a captured NGN beneficiary snapshot also require reconciliation before approval; rejection still releases a valid reservation.

Test a newly captured delivery and pickup with no/mixed/full merchandise reward funding. Verify captured cash + original redeemed reward equals merchandise + tax + logistics; rewards never pay tax/logistics. Test per-order refund/release and pending totals without relying on global clamps.

## Historical evidence packet, one order at a time

For each unknown row record:
- Order ID and canonical successful PaymentRequest/provider reference, currency, amount and provider settlement statement.
- Original immutable line snapshots, quantities, discounts, merchandise, tax, delivery, reward redemption and exchange-rate evidence.
- Every completed refund's immutable cash/reward/tax allocation and external cash proof. Exclude requests merely recommended or approved.
- Wallet release and actual bank withdrawal separately; freeze payout beneficiary and compare bank reference/proof. Investigate duplicate releases or payments.
- Current order hold versus already recognized merchandise/commission/tax. Formula for a held order: original captured cash + consumed reward liability minus completed refund funding minus any already recognized release. Every term needs evidence.
- Reconciliation discrepancy, proposed exact decimal correction and expected before/after balances. Unknown evidence remains an open exception.

Do not set NULL to order.order_amount or spread aggregate pending amounts across orders. Do not double-add an already recorded aggregate hold. Do not mark a customer refund paid using the legacy hold-resolution helper.

A second authorized finance reviewer approves the evidence packet before any historical database adjustment. Apply an approved reconciliation transaction only through a reviewed migration/tool specific to that packet, locking affected order/transaction/wallet rows and checking expected old values. Keep immutable before/after audit and provider/bank references. This repair supplies no automatic historical backfill; mismatched/ambiguous rows remain blocked until a separately reviewed correction is justified.

## Release verification

On MySQL8 use separate connections/processes for concurrent callbacks, payout approval/rejection, refund versus release, reward maturity versus dispute, quote versus reward reservation, and initialization lease expiry. Assert exactly one posting and consistent rollback; SQLite tests cannot establish lock-wait behavior.

Verify production scheduler wiring using actual server cron configuration and logged execution of `schedule:run`; a source Kernel schedule is insufficient. Confirm only one cron owner and observe reward maturity/settlement eligibility jobs. Use provider test-mode signed callbacks/webhooks, browser/device payment close/restart/recovery and expired/late captures. Check reconciliation cases are visible to an authorized operator and not rendered as fulfilled orders.

After reconciliation, rerun the read-only diagnostic, compare all application liabilities against provider/bank statements and record remaining exceptions explicitly. Only the authorized deployment owner can accept historical corrections and production launch.
