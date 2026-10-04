# V1 second-pass money repair decision [AI]

User authorization: fix all found inline with business rules, 2026-10-04. Starting source576337b4, branchv1.

## Pre-change impact analysis
REQUEST: Repair M01–M10 and additional parity/precision observations from V1-MONEY-SECOND-PASS-2026-10-04.md. Investigate terminal-status/rider earnings replay; repair if confirmed.
ROOT CAUSE: Alternate endpoints bypass shared authority; pickup gross/net and recovery metadata differ from delivery; refund allocations truncate independently; clients do not all confirm/recover the same frozen contract.
AFFECTED: Laravel repositories/services/controllers/middleware; Admin/Vendor web order/payout controls; Customer web/mobile pickup/refunds; Vendor mobile refunds/rider approval; Rider request contract.
POTENTIALLY AFFECTED: Existing in-flight attempts, historical refunds and payout snapshots. Existing bank/wallet balances require evidence-backed reconciliation; never auto-backfill financial truth.
NOT AFFECTED: Branding, native camera/location permissions, discovery/search presentation.
BACKEND IMPACT: OrderRepository, canonical refund allocation in OrderManager, payment/refund/withdrawal services, order/pickup controllers and seller employee authorization. Additive schema only if durable snapshot/quote fields need it; nullable historical rows stay unknown.
API IMPACT: Preserve refund preview fields as exact strings. Add server pickup quote identity/confirmation and outcome recovery. Reject invalid/unsafe order mutations and payout enums. Hardened requests intentionally reject previously unsafe submissions.
STATE MANAGEMENT: Existing Provider customer/vendor and GetX rider architecture. Reuse secure StorageService; persist owner-bound pickup identity before initializing payment.
SECURITY: Scope every resource to actor; owner-only finance operations; canonical paid state immutable; terminal states resist replay; provider proof distinct from UI redirects and admin explanations.
BUSINESS RULES: Vendor90%/platform10% pure merchandise, normal5% reward on new-money merchandise with configured0–5 range. Rewards cannot fund tax/delivery. Refund allocations conserve original cash/reward/tax with final-penny residual. Manual bank payout requires proof against frozen beneficiary. No guessed balance corrections.
TEST PLAN: Actual endpoint/service SQLite regressions for cross-owner edits, forbidden fields, digitalpaid overrides, repeatedinvalidpayouts, no/mixed/fullreward pickup, replacement and timeout/expiry interleavings, taxedpartial/fullrefund pennies, refundpreview loyaltyon/off, earnedrewardadjustment and frozenquote/recovery widgets. Reviewer runs old+new integration suites, PHPsyntax, changedclienttests/analyzers and Blade smoke. MySQL8/contention and live gateway/device/deployedcron remain explicit external rollout checks.

## Work allocation
Finance implementer owns M01/M02/M05/M06/M08/M09, exact rider requests and terminal-state checks. Payment implementer owns M03/M04/M10 and frozen pickup quote/status contracts. Frontend implementer consumes reviewed backend contract for M07 and updates recommendation/refund/payout presentation. Reviewer owns decision/review/changelog and executes verification. Workers do not push/deploy.

## Acceptance
All reported findings map to actual fixes and meaningful regression tests. Existingmoney tests remain green. Do not mark productionready using local SQLite or invented resultJSON. Preserve preexisting scripts/import_db.php, scripts/local_proxy.js and debugbar artifacts.
