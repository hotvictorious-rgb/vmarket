# V1 credential, reset and search repair [AI]

User authorization: repair the eight security findings supplied against source55eb8e7d. Current reviewed baselineb14af2e0, branchv1. Existing pickup work remains in progress; preserve its uncommitted changes and unrelated backend .rnd.

## Impact analysis

Root causes: serialized owner credential plus hash-or-plaintext lookup permits digest replay; rider password update is not bound to OTP proof; caller-written generic verification records cross reset purpose; employee logout revokes the seller object instead of authenticated employee; request values interpolate into raw SQL; nested history endpoint lacks parent ownership and employee branch/module enforcement; seller Carbon reference is unresolved; modeled proof scripts overstate actual routed coverage.

Changes: backend owns model/DTO credential exclusion, hash-only authentication and credential invalidation migration, transactionally consumed actor/account/purpose-bound reset proof and session revocation, authenticated-principal logout, parameterized raw search and parent-order history authority. Frontend consumes frozen reset contracts only after backend review; reviewer verifies actual routed HTTP/controller queries and writes evidence. No production migration, external Firebase operation, credential capture or database balance change is authorized by this local repair.

Compatibility: clearing existing seller/employee/rider credentials requires sign-in again after the migration. Password reset requests without fresh purpose-bound proof intentionally fail. Client reset payloads need explicit OTP/reset credential; backend cannot rely on a separate verification request having occurred. Profile DTO preserves required presentation fields while excluding authentication secrets. Parameter binding preserves search semantics. Other sellers and unauthorized branches see no order-history records.

Verification: isolated SQLite-before-provider Laravel HTTP route/middleware regressions for stored-digest replay denial, employee profile secret exclusion, principal logout isolation, reset missing/wrong/expired/cross-actor/replayed credentials and revoked sessions, malicious generic verification record rejection, legitimate reset success, tenant/branch/module history access and bound hostile search. Rerun existing money suite and affected client tests. Exclude custom copied-logic scripts from security certification; do not fabricate unavailable pillar evidence. MySQL concurrency, external Firebase verification and live rollout remain separately unverified.

Work split: auth backend implementer owns findings1–4/7; search/ownership backend implementer owns5–6 and raw-query scan. Frontend independently maps current client reset contracts then waits for BACKEND_DONE. Reviewer reviews all changes and runs current-source verification. Workers do not commit/push/deploy.
