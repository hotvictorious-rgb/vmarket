# V1 security repair review [AI]

User supplied findings against55eb8e7d were independently confirmed in the current local v1 baselineb14af2e0. Scope covers credentials, password-reset authorization, principal logout, raw search SQL and seller order history. Existing pickup client repairs continue separately. No production credentials, external Firebase accounts, bank transfers or deployed database were used.

## Findings and intended repair

| Finding | Verified cause and repair |
|---|---|
| 1 Critical: employee becomes owner | Owner auth_token serialized through seller-info plus hash-or-raw fallback. Hide credentials in Seller/VendorEmployee models, explicitly select safe profile columns, require hash-only authentication, bind trusted principal context and invalidate all prior seller/employee/rider tokens with a reviewed migration. Actual HTTP tests must reject an owner's stored digest and verify employee secret exclusion. |
| 2 Critical: rider reset lacks ownership proof | Public final password operation accepted phone/password alone. Require account/user_type/purpose-bound server-issued proof within the same locked transaction as password update, single-use consumption and token revocation. Missing/wrong/expired/cross-account/replay must fail without changing password. |
| 3 High: generic verification record authorizes reset | Caller-written Firebase token-store records were accepted as password-reset proof. Disable arbitrary public token storage. Only authoritative provider phone verification can mint dedicated reset proof; native proof is server issued. Generic verification rows cannot authorize password changes. Customer web counterpart has the same boundary and must use the shared service. |
| 4 High: employee logout targets owner | Seller context was checked before employee and alternate employee field was wrong. Revoke trusted middleware-bound principal only. Owner context remains available for tenancy, separately from authenticated employee identity. Tests include forged principal fields. |
| 5 High: raw SQL interpolation | Bind user search text in WebController, ProductRepository and BrandController ranking/LOCATE. Wider scan also found CategoryController JSON_SET parent interpolation; use Laravel's bound JSON path update. Existing fixed fragments in other reports are not user SQL. |
| 6 High: history crosses tenant/branch | Authorize parent seller order before fetching histories, then employee order module and known branch. Nested route is classified as an order resource. Missing/other-owner/unknown-branch records must not disclose history. |
| 7 Runtime defect: seller Carbon reference | Import Carbon while replacing unsafe reset fallback; import-only repair is insufficient. Legitimate native and Firebase reset flows must remain usable with new purpose-bound proof. |
| 8 Evidence quality | Custom UniversalUserAuthenticationAndAccountSafetyProofTest is not a PHPUnit TestCase, and copied MockOrder/closure logic cannot certify routed production behavior. Do not use numerical security scores or missing pillar scripts as release evidence. This review uses explicit production HTTP/query tests and records limitations below. |

## Additional review requirements

Password-reset credentials are hashed at rest, bind exact account ID/actor/purpose, expire in15 minutes and count wrong attempts under lock. Provider-verified phone must match the requested account identity. Firebase verification must work without caller-created general verification records; disabling only storage while retaining that precondition would break legitimate flow. Native OTPs must not become a fixed123456 due to APP_MODE configuration. Account lookup must resolve exactly one principal and preserve chosen password spaces.

The migration intentionally requires new seller/employee/rider login and new reset issuance. It cannot restore invalidated secrets on rollback. Existing money balances are untouched. Customer reset revokes Passport access/refresh credentials and relevant persisted session/remember/temporary credentials. Client rider OTP and verified Firebase reset_token must be threaded into the final request; sessionInfo itself is not reset proof.

## Verification and rollout

Pending final auth implementation and combined actual tests. Search/history isolated suite currently5 tests44 assertions passed, executing production search queries and actual routed seller requests. Category coverage is a supplemental repository/MySQL grammar binding probe on SQLite, not a CategoryController HTTP test or live MySQL exploit test.

No production launch approval is issued while work or required checks remain. SQLite tests cannot prove concurrent MySQL consumption/lock behavior. Live Firebase configuration, SMS/email delivery, fresh login after credential invalidation and production session behavior need staged verification. This review does not certify every unrelated route or replace the wider money rollout checks.
