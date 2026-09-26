# Victorious MARKET — Authoritative Testing Rules

> **CONTROL ZONE FILE — HUMAN OWNER ONLY**  
> AI agents are STRICTLY FORBIDDEN from editing this file. Any proposed modifications must be submitted via a ticket accompanied by an approved Decision Record (`.ai/decisions/DECISION-XXXX.md`).

---

## 1. Testing Philosophy & Test-Owner Rules

1. **Evidence-Based Engineering**:
   - No feature, bugfix, or refactor may be merged into `main` based on conversational assertions. Every change requires machine-generated automated test results proving functionality, security, and backward compatibility.
2. **Test Ownership Rule**:
   - Cross-cutting and system tests are authored by the implementation AI responsible for the system under test:
     - `tests/contract/` and `tests/security/`: **AI 1** (Backend AI).
     - `tests/integration/` and `tests/e2e/` (Customer App): **AI 2**.
     - `tests/integration/` and `tests/e2e/` (Vendor App): **AI 3**.
     - `tests/integration/` and `tests/e2e/` (Delivery / Admin): **AI 4**.
     - `tests/regression/`: The AI whose bugfix resolved the issue.
     - `tests/performance/`: **AI 1** (Backend AI).
3. **Reviewers Never Author Tests**:
   - Reviewers (AI 5, AI 6, AI 7) verify, challenge, and execute tests. If a reviewer identifies a testing omission, they MUST issue a finding in the review report demanding the implementer write the test. Reviewers must never write tests or edit application code directly.

---

## 2. Test Suite Categories & Commands

All suites must be runnable via standardized, clean-checkout commands:

| Suite Category | Primary Purpose | Standard Command | Owner |
|----------------|-----------------|------------------|-------|
| `backend` | PHPUnit / Feature & Unit tests | `php artisan test` or `./vendor/bin/phpunit` | AI 1 |
| `customer_frontend` | Flutter unit, widget, and state tests | `flutter test` in `User app/` | AI 2 |
| `vendor_frontend` | Flutter unit, widget, and state tests | `flutter test` in `Vendor app/` | AI 3 |
| `operations_frontend` | Flutter unit & widget tests (Delivery) | `flutter test` in `Delivery Man App/` | AI 4 |
| `contract` | OpenAPI schema validation & client compatibility | `php artisan test tests/contract` | AI 1 |
| `integration` | Multi-actor journey simulations | `php artisan test tests/integration` | AI 1 & AI 2-4 |
| `security` | IDOR scoping, OTP limits, payment locks | `php artisan test tests/security` | AI 1 |
| `database` | Migration up/down deterministic rollback | `php artisan migrate:fresh --seed` & rollback | AI 1 |
| `regression` | Permanent regression test suite | `php artisan test tests/regression` | Bugfix AI |
| `performance` | Eager-loading verification & N+1 query checks | `php artisan test tests/performance` | AI 1 |
| `supply_chain` | Dependency audits, secret scans, licenses | `composer audit`, secret-scanner | AI 1-4 |

---

## 3. Schema v2 & Machine-Readable Results

1. **Runner-Generated Guarantee**:
   - Test results are written **EXCLUSIVELY** by runner scripts located in `scripts/tests/` (e.g. `scripts/tests/run-all.ps1`).
   - AI agents are STRICTLY PROHIBITED from creating or editing test result JSON files by hand.
2. **Schema v2 Result Location**:
   - Test results are saved to `.ai/status/results/<ticket-id>/<commit-sha>.json`.
   - Raw output logs are stored alongside the result and cryptographically fingerprinted using SHA-256 hashes (`log_sha256`).
3. **Status Taxonomy**:
   - `PASS`: Test suite executed successfully with zero failures.
   - `FAIL`: One or more tests failed; permanently blocks release.
   - `N/A`: Check is not applicable to the ticket; requires a clear justification and explicit reviewer/human sign-off.
   - `NOT_RUN`: Missing result entry; automatically treated as blocking.

---

## 4. Commit Binding & Stale Results Rule

1. **Exact Commit SHA Matching**:
   - Every test result and review approval is permanently bound to an exact Git commit SHA.
   - **Any new commit invalidates all prior test results and approvals.**
   - If code is modified following a review finding, test runners MUST be re-executed against the new commit SHA before re-review.
   - The release gate strictly compares the HEAD commit SHA against the test result `commit` attribute. Mismatched SHAs are rejected as stale.

---

## 5. Regression Test Mandate & Characterization Tests

1. **Permanent Bug Regression Rule**:
   - Every bug ticket (Type: `BUG` or `HOTFIX`) must introduce a permanent automated regression test in `tests/regression/` reproducing the failure before the fix and confirming its resolution after the fix.
   - A bug ticket cannot reach `RELEASE_CANDIDATE` without this test.
2. **Legacy Characterization Tests**:
   - Before modifying untested legacy code (Tier C or Tier A legacy paths), the implementing AI must author characterization tests capturing the existing behavior before altering implementation logic.

---

## 6. Flaky Test Quarantine Protocol

1. **Quarantine Bounds**:
   - An intermittently failing test is a system defect and must not be quietly ignored.
   - A flaky test may only be quarantined by creating a dedicated ticket, assigning an owner, and setting a hard expiry date (maximum 14 calendar days).
   - Quarantined tests are tracked in `.ai/status/QUARANTINE.md`.
2. **Tier A Quarantine Prohibition**:
   - Tests in Tier A (Critical) areas (Payments, Auth, Ledger, Balance Locks) CANNOT be quarantined without explicit human sign-off.
   - Expired quarantines automatically block the release gate.
