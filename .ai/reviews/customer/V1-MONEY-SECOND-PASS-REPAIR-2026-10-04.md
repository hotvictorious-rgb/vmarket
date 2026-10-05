# V1 second-pass money repairs [AI]

**Current follow-up (2026-10-05): M11/M12 and the pickup client gate below are repaired and locally verified.** The earlier NOT APPROVED findings and interruption record are preserved as historical evidence. The scoped security/money follow-up is recorded in `V1-SECURITY-REPAIR-2026-10-04.md`; production rollout checks remain pending.

Scope: branch `v1`, starting source `576337b4`, latest reviewed source `55eb8e7d`. User requested all findings repaired in line with business rules. Backend repairs address the earlier findings, but M07 client completion remains blocked by new confirmed defects below. This report does not certify production deployment, bank balances or MySQL concurrency. Several shared-checkout commits were created concurrently by another task; this reviewer did not create or push those commits.

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

At source `55eb8e7d`, current isolated combined PHPUnit suite: **48 tests, 465 assertions, zero failures/errors**, 69.541 seconds. Raw runner output: `v1-money-second-pass-current.log`. Tests force SQLite in memory before framework provider boot and fake external gateway I/O. No configured application database was used.

Current customer checkout/refund subset: **7 tests passed** (`checkout_quote_confirmation_test.dart`, `checkout_intent_pay_test.dart`, `refund_display_status_test.dart`). Vendor refund recommendation widgets: **7 tests passed**. Actual rider payout JavaScript upload regression: **4 checks passed**. JavaScript syntax checks pass for pickup payment and both payout adapters. PHP syntax checks pass for **64 changed non-Blade PHP files** across the reviewed commit range. `git diff --check` passed.

Customer `dart analyze lib/features/checkout`: **0 errors, 6 warnings, 8 infos**, exit1. Warnings include unused imports/locals; two infos flag async context use at checkout_screen.dart328/341. This does not meet a zero-warning CI gate. Passing delivery/refund tests do not exercise the new pickup recovery HTTP path.

## Current open findings — do not approve M07 or close audit

### M11 — High: corrupt customer pickup quote/status interpolation

`User app/lib/features/checkout/domain/repositories/checkout_repository.dart:100` embeds control character U+0002 followed by `4` where Dart `$` interpolation is required. The quote URL therefore contains literal malformed placeholder text rather than the pickup endpoint/reservation code.

`User app/lib/features/checkout/screens/payment_status_screen.dart:89` has the same corruption in all three URL segments and in its Authorization bearer value. Exact byte inspection confirmed U+0002. These strings remain syntactically valid Dart, so a zero-error analyzer result does not establish request correctness. The actual quote endpoint cannot be reached with that route, and the recovery request cannot authenticate or construct its intended absolute URL.

Required repair: restore ordinary interpolation in both paths and add actual repository HTTP request assertions plus pickup recovery widget tests. Cover paid/failed/cancel/restart, secure identity written before initialization, canceled quote making zero pay calls and no redirect treated as payment proof. No pickup-specific widget test file exists in the current checkout; existing delivery tests are insufficient.

### M12 — Medium: known no-attempt result traps a durable pickup identity

CheckoutController saves pending pickup identity before the pay request (correct for ambiguous transport). If that request is rejected before creating an attempt, for example expired/changed quote409, backend status returns payment_status=unpaid with null payment_request_id. PaymentStatusScreen handles paid, failed and expired, but not this authoritative no-attempt outcome. The saved identity survives, and PickupPaymentScreen sends every later review into status instead of requesting a fresh quote. This is a source-traced recovery dead end, not an executed widget reproduction.

Required repair: distinguish proven no-attempt from ambiguous/pending/captured state. Clear or renew only an owner-matching identity after authoritative no-attempt evidence, allowing a fresh quote; retain identity for pending/unknown/reconciliation. Test pay409-before-attempt followed by unpaid status and successful fresh quote. Handle refunded terminal state explicitly too.

### Work interruption and remaining gate

All three implementation agents stopped with the tool error **Your workspace is out of credits**. Their completed changes were reviewed and the current available suites ran, but client completion and new pickup widget tests were not finished. Reviewer charter `.ai/agents/REVIEWER_AI.md` explicitly says "You never write implementation code"; implementation fixes cannot be substituted silently by this reviewer. Review status is **NOT APPROVED**, not all fixed. Resume implementation after workspace credits are restored, then rerun the combined backend/client gate against the final source SHA.

Additional before-release checks remain the historical reconciliation/migration, real MySQL contention, signed provider test-mode events, live browser/device recovery and deployed scheduler evidence described above. No universal production-ready or all-paths certification is asserted.

## 2026-10-05 follow-up — pickup client repairs verified

M11 malformed quote/status URL and Authorization interpolation is repaired. Actual repository/HTTP tests exercise the requests. M12 now distinguishes authoritative unpaid/no-attempt evidence from pending/unknown capture state: only owner-matching no-attempt evidence clears the durable identity and permits a fresh quote; refunded is handled as terminal. Quote cancellation creates no payment, identity is retained before initialization, and strict server status controls payment/OTP presentation.

Customer reset/pickup combined runner passed23 tests, storefront actual production pickup adapter passed1. Targeted customer auth/entire checkout analysis exited0 with no issues. The final combined actual backend security/money suite passed74 tests754 assertions with zero errors/failures (`v1-security-repair-additive-final-2026-10-05.log`) at source0406bb09, retaining the48 money cases. The implementation-agent credit interruption was resolved by the later continuation. These results close the local M07/M11/M12 gate; historical reconciliation, staging migrations, application-level MySQL contention, provider callbacks, scheduler and physical recovery checks remain separate release requirements.
