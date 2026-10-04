# V1 second-pass money repairs [AI]

Scope: branch `v1`, starting source `576337b4`. User requested all findings repaired in line with business rules. This report supersedes the open code findings in `V1-MONEY-SECOND-PASS-2026-10-04.md`; it does not certify production deployment, bank balances or MySQL concurrency. Final verification and source commits are recorded below when complete.

## Business rules preserved

NGN funding uses exact decimal arithmetic. Redeemed rewards pay merchandise only; tax and delivery remain cash obligations. Vendor entitlement is 90% of pure merchandise and platform commission is 10%. Reward earning uses configured 0–5% of eligible new-money merchandise, pending receipt and maturity. Wallet release and external bank withdrawal are separate events. Refunds restore original recorded cash/reward/tax funding exactly, without creating new money or using another order's hold.

## Finding-to-fix map

| Finding | Repair and regression coverage |
|---|---|
| M01: arbitrary order-column writes and cross-vendor edits | Repository allows only validated scheduling fields, enforces seller ownership and admin capabilities, rejects terminal orders, limits rider charges to authoritative shipping funding and records changes. Tests reject financial columns, another seller's order, invalid actors and excessive charges. |
| M02: alternate rider payout bypass | Admin/vendor web/API use the same terminal-decision service. Approval requires frozen NGN beneficiary, valid uploaded PNG/JPEG/PDF proof, sufficient reserved and current balances. Replays cannot debit again; employee routes are blocked. Requests validate exact two-decimal amounts and capture server-side beneficiary. Tests exercise real controller approval/rejection, uploaded proof and replay. |
| M03: pickup gross/net escrow mismatch | Orders record net cash in order_amount and gross funding in init_order_amount; captured cash plus consumed redemption must equal gross. All verified settlement failures enter durable reconciliation with truthful reason. Tests run no/mixed/full reward funding and replay, including accounting failure. |
| M04: replacement loses rewards or regresses after I/O | Replacement inherits locked funding and redemption metadata. Initialization responses recheck lease and terminal state; late success/rejection cannot overwrite callback completion. Tax stays cash-only. Tests exercise missing-reference replacement and callback-during-initialization. |
| M05: refund preview fails | Preview delegates to canonical allocation, preserves expected response keys as exact decimals and does not require current/pending reward balance. Actual controller tests exercise legacy loyalty enabled and disabled. |
| M06: lost refund pennies | New nullable refund_allocation stores all line allocations together under the order lock. Deterministic largest-remainder distribution conserves every penny. Preview, creation and manual/provider execution share the immutable values, including valid zero allocations. Full actual refund cycles exercise tiny totals and 33.33/33.33/33.34 before and after release. |
| M07: pickup quote and recovery gaps | Owner-scoped server quote issues bounded confirmation token; public payment requires matching frozen totals. Authoritative status distinguishes fulfillment from capture anomalies. App/storefront must confirm quote and retain/recover attempt identity before opening payment; client verification is recorded below. |
| M08: manual digital paid-state mutation | Generic admin endpoint validates enum/capability and rejects digital overrides and reversal of existing paid state, including superadmin explanations. Dedicated evidence workflow remains authoritative. Tests reject paid/unpaid mutation and terminal completion reset. |
| M09: provider refunds count tax as merchandise | Prior completed returns sum frozen merchandise allocations rather than cash/tax/reward transaction totals. High-tax partial-refund tests verify remaining merchandise and final closure. |
| M10: changed delivery cart cancels payable attempt | Customer serialization anchor precedes intent/payment locks. Changed cart/address cannot supersede an unresolved payment attempt; API/web return explicit conflict. Tests verify intent, reward hold and old attempt remain intact. |

Additional parity repairs cover one-time vendor refund recommendations, customer labels separating recommendation/admin approval/payment, multipart proof uploads, frozen beneficiary presentation, exact NGN display and checkout analyzer cleanup. Terminal order status cannot be reset to credit rider earnings again.

## Historical data and release limits

The additive migration `2026_10_04_000001_add_frozen_refund_allocations.php` is required alongside these changes. Historical completed refunds without matching canonical evidence and withdrawals without beneficiary snapshots fail closed. Historical unknown escrow remains unknown. No balances were reset and no automatic backfill was supplied. Follow `V1-MONEY-RECONCILIATION-RUNBOOK.md` for evidence and separately reviewed corrections.

Local SQLite tests execute production services/controllers with fake provider I/O. They cannot establish MySQL row-lock contention, actual provider/bank settlement, deployed cron execution or physical device/browser recovery. Staging migration/reconciliation, two-connection MySQL concurrency, provider test-mode signed callbacks and real client restart/close checks remain required before release. Those checks are not reported as passed.

## Verification

Pending final combined run and reviewed client changes. No universal production-ready or all-paths certification is asserted.
