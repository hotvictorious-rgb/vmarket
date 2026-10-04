# V1 money-path repair review

Status: implemented and locally verified on branch v1. This file is not release approval.

Scope: user-authorized repair of audit F01–F16 and the duplicate OAuth token variable, in the existing `v1` checkout. No live gateway transfers, production database changes or deployment performed.

## Implemented changes under review

| Finding | Repair |
| --- | --- |
| F01 | Shared vendor return recommendation service; admin/completed states immutable; vendor rejection retains the financial dispute hold until admin resolution. |
| F02 | Terminal approve/reject payout enum, reservation sufficiency and replay guards, finance authorization and audit evidence. |
| F03 | Bind the application console kernel in Laravel 12; one schedule definition with overlap protection. |
| F04 | Existing settlement action becomes wallet entitlement release; the subsequent withdrawal is the bank payout. Notes/method/reference persisted and replay cannot create another expense. |
| F05 | Nullable per-order remaining hold; refunds and releases consume only that order's backing. Unknown historical holds require verified reconciliation. |
| F06 | Merchandise split excludes tax/logistics; funding includes redeemed reward liability; platform components posted separately. |
| F07 | Refund cash obligation comes from locked persisted funding. A submitted cashback label cannot bypass cash-payment evidence. |
| F08 | Resolve successful canonical gateway payment references using durable delivery/pickup links; terminal refund accounting resists late provider events. |
| F09 | Legacy GET can only replay an owned existing canonical payment URL; unmatched signed captures/refunds persist reconciliation evidence. |
| F10 | Maturity rechecks receipt/dispute and fresh reward amount under locks before credit; repeat execution cannot credit twice. |
| F11 | Expiry cleanup shares settlement lock order, releases reward reservations and does not overwrite converted intents. Reconciliation is distinct from fulfillment success. |
| F12 | Customer app and storefront confirm a frozen backend quote before initialization, including per-vendor tax, delivery and reward allocation. |
| F13 | Secure durable delivery identity, quote-only versus initialization-started state, restart recovery and authoritative outcome checks after WebView close/callback. Callback order IDs follow canonical settlement linkage. |
| F14 | Pickup recovery accepts only the dedicated six-digit pickup handover code. |
| F15 | New V1 payout requests use NGN decimal amounts; cancellation restores the exact recorded reservation without another exchange conversion. |
| F16 | New actual-controller/service and checkout-controller regressions; old mathematical script no longer claims universal production certification. SQLite evidence remains distinct from MySQL contention proof. |

The earlier F04 claim that no settlement controller/route existed was incorrect. The controller, route and form were already present at baseline `74845a6d`. The original audit now records that correction; the conflicting payout meanings and replay/accounting defects remain valid.

## Business and schema decisions

See `.ai/decisions/ADR-V1-MONEY-LIFECYCLE-2026-10-03.md`. No entitlement is paid twice. Vendor merchandise receives 90%; gross platform commission is 10%; normal new-money reward earning remains 5% and zero configuration is honored. Tax and delivery remain separate. Existing point conversion is immutable through ordinary V1 settings to prevent retroactive revaluation.

Migration `2026_10_03_000010_add_order_escrow_remaining.php` adds nullable remaining backing, recognized merchandise/commission/tax counters and settlement method/notes. It intentionally does not infer or rewrite historical money. Deploy the migration with the code. Historical held orders cannot be released/refunded through the new tracked path until their backing is verified.

## Verification evidence

Final reviewer run on 2026-10-04: `C:\xampp\php\php.exe vendor/bin/phpunit --configuration C:\Users\USER\Downloads\vmarket\.ai\reviews\customer\v1-money.phpunit.xml` from the backend directory. **30 tests, 243 assertions passed**, no failures/errors or configuration deprecations, PHP 8.2.12. Raw runner output: `v1-money-final.log`. This combines 16 finance tests (111 assertions), 13 gateway/Blade tests (100 assertions), and the delivery lifecycle test (32 assertions), using isolated SQLite memory databases and fake provider I/O.

Reviewer syntax validation passed for all 50 changed/new PHP files; `git diff --check` passed. Customer agent executed 31 focused Flutter tests, reran 10 checkout tests after recovery changes, and reran the final controller regression; 14 standalone state/OTP checks passed. Customer targeted analysis had zero errors with existing warnings. Vendor targeted analysis passed with no issues; dependency manifests/locks were unchanged.

These are working-tree integration results immediately before the repair commits; they do not certify an earlier commit or the full monorepo. New regressions call actual controllers/services, including payout replay, partial refunds preserving another order's hold, recognized tax/debt reversal, penny residuals, payment initialization interleavings, maturity freshness and quote confirmation.

## Remaining operational checks

Actual MySQL 8 two-connection contention and production constraints, deployed cron execution, browser/device walkthroughs, and provider/bank-statement reconciliation have not been certified. Local available database binaries are MariaDB 10.4, not the production MySQL 8 engine. No production-readiness claim follows from SQLite or modeled checks.

Historical balance adjustments require evidence and explicit authorization; the repair does not reset wallets. Owned-inventory margin also requires actual cost data; recognized sales are not proof of profit.

Read-only diagnostic after migration: `php artisan finance:reconcile-v1 --json`. It reports discrepancies and does not adjust balances. The unwired legacy hold-resolution helper is not customer refund-payment evidence; do not expose it as such. The retired synthetic undelivered refund helper now fails closed.

