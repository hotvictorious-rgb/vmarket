# V1 money lifecycle repair decision

User authorization: “fix all”, 2026-10-03, following the V1 branch money-path audit and review of commits f7227d75/306caff8.

## Scope and impact

Repair findings F01–F16 in `.ai/reviews/customer/AUDIT-2026-10-03-MONEY-PATHS.md` on branch `v1`. Backend, admin, vendor web/mobile, customer storefront/mobile and rider payout surfaces are affected. Historical POS/shared-wallet balances are potentially affected by reconciliation. Branding, catalog presentation and native device permissions are not affected.

The backend remains the authority for amounts, funding partitions, eligibility and transitions. Additive quote/status DTOs preserve existing fields. Invalid payout and refund transitions now fail before mutation. New nullable per-order accounting fields must not silently rewrite historical balances.

## Settlement meaning

V1 settlement releases the vendor's earned merchandise entitlement into the existing withdrawable wallet. It does not certify a separate external bank transfer. The existing manual withdrawal approval, with payment evidence, records the actual payout. Exactly one release and one subsequent withdrawal are permitted; a release must never also represent an external payment.

Merchandise allocation stays 90% vendor / 10% gross platform commission. Customer rewards follow the existing new-money merchandise rule, normally 5%; delivery and tax remain separate. A configured zero reward rate must remain zero. Consuming previously earned rewards supplies funding, not new revenue or another reward earning event.

V1 earning configuration is bounded to 0–5% to preserve the approved 90/5/5 funding envelope. The configured point-to-NGN conversion is immutable through normal admin settings: changing the value of existing points requires a separate explicit liability migration. This retains the current configured conversion and avoids retroactive revaluation without inventing a second reward ledger. Refund restoration uses the original redemption allocation where available.

Refunds release only the refunded order's remaining hold, restore only the actual redeemed reward allocation, and require evidence for the cash portion. Vendor acceptance/rejection of a return cannot certify payment of a refund. Provider success is terminal against late pending/failed events.

## Verification and rollout

Implementation agents own actual-controller/service regression tests. Reviewer executes and challenges those tests. SQLite behavioral evidence does not certify MySQL locking, foreign keys or contention. Payment providers are faked in tests; no real transfer is authorized by this repair request.

Deploy code and migrations together after review. Reconcile historic payment captures/refunds, per-order holds, wallet entitlements, payout evidence, tax/delivery liabilities and reward lots against provider/bank statements. Ambiguous legacy backing requires an exception, not a guessed aggregate-wallet correction. Never reset balances or infer bank cash from wallet counters.

Exact implementation evidence and remaining deployment checks will be recorded in the repair review. This decision does not authorize production deployment, historical financial adjustments or a production-readiness certificate.
