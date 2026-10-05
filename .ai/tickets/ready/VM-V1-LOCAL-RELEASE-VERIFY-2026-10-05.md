# V1 local release verification continuation [AI]

Status: INTEGRATION_TESTING
Blocked: no
Branch: v1
Baseline: f3565fcd1d97e8184841ea9d33bc8dd018965664
Documents updated: this ticket; forthcoming local release verification report

## Authorization and scope

User asked to work now on the outstanding migration, concurrency, provider, scheduler, recovery and reconciliation checks. Keep V1. Local host is APP_ENV=local with MariaDB10.4.32/InnoDB at127.0.0.1:3306. Configured database is vmarket and APP_MODE=live; configuration alone does not prove a test provider account. No production deployment, actual bank payment, real email/SMS or historical correction is included in this local verification.

## WORK ORDER — BACKEND database/concurrency

- Goal: test actual Laravel money/reset implementations under separate InnoDB connections, not copied SQL or modeled closures.
- Exact new disposable database: vmarket_v1_verify_finance_20261005_01, local host only. Schema comes from repository installation dump, never a copy of customer data. Reject database switches, cross-database and external-file SQL. Root approved this exact target after read-only inventory.
- Allowed: dedicated backend test harness/worker/fixtures and isolated test configuration, coherent files within400changed lines each. Report a product defect before editing its exact owner-controlled file.
- Forbidden: writes/migrations/fresh/drop on configured vmarket; external provider/mail/SMS; unrelated product/client files; Git pushes.
- Before provider boot, force disposable DB, array cache/session, fake external I/O and isolated gateway settings. Workers must assert database name and engine before mutation. Retain database for review.
- Required evidence: fresh/previous migration state; reset single-use contention; callback exactly-once processing; refund/release and payout terminal races as production services permit. Identify unsupported scenarios explicitly. Verify isolation, rollback and deterministic final ledger state.
- DONE: source frozen, exact files, raw command/test results and actual engine/version. MariaDB results are not a MySQL8 certificate.

## WORK ORDER — BACKEND diagnostics/provider/scheduler

- Goal: read-only configured local reconciliation, migration inventory, scheduler authority and signed gateway route tests.
- Allowed: read-only PDO/database snapshot and dedicated isolated production HTTP tests. Never print secret values; provider modes/credential presence only.
- Verify command/provider boot cannot mutate DB before execution, then enforce session transaction READ ONLY on every configured connection. Compare before/after relevant financial fingerprints. Keep raw report/diagnostic exit, including expected exception failure.
- List scheduler wiring and local task evidence; never run monetary scheduled jobs on configured data. Execute scheduled job behavior only in disposable/SQLite fixtures. Distinguish source registration from actual server execution.
- Provider tests use test-only secrets and mocked server responses unless a genuine test-mode configuration is independently verified. Missing/live-only credentials remain pending; do not send real SMS/email or charge customers.
- DONE: actual raw results, credential mode/presence only, missing release evidence and proposed fixes with scope.

## WORK ORDER — FRONTEND recovery/capabilities

- Goal: establish native/browser capability and exercise actual production recovery with fake backend data.
- Allowed: frontend tests and local disposable browser fixture; no backend implementation or real account/provider actions.
- Inventory adb/emulator/Flutter devices and installed browser automation. Headless automation of a new disposable browser may be possible even when connected-browser inventory is empty.
- Tests must exercise actual widgets/controllers/repositories/production browser JS, owner-bound durable identity, close/reopen and authoritative status. Fake provider tests cannot certify native plugin/device behavior.
- DONE: frozen exact owned files, actual commands/results, analyzer scope, supported/unsupported targets; no invented device certificate.

## Acceptance and evidence discipline

Reviewer executes independent checks, records each as PASS/FAIL/NOT_RUN with scope, retains failure evidence, commits exact owned components only. No universal release score. Missing provider/bank statements are evidence gaps, not permission to backfill balances. All delegates report through root. No concurrent task work is reverted or staged.

## History

- 2026-10-05 03:55UTC: dispatched read-only capability/diagnostic work and actual-service concurrency work; exact disposable database approved. Existing74/754 code repair gate remains valid for prior source, not a certificate of new concurrency coverage.
