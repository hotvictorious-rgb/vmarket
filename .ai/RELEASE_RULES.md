# Victorious MARKET — Authoritative Release Rules

> **CONTROL ZONE FILE — HUMAN OWNER ONLY**  
> AI agents are STRICTLY FORBIDDEN from editing this file. Any proposed modifications must be submitted via a ticket accompanied by an approved Decision Record (`.ai/decisions/DECISION-XXXX.md`).

---

## 1. Release Gate Criteria (The 18 Mandates)

A feature branch may only merge into `main` if every check below passes without exception:

| # | Gate Check | Verification Standard |
|---|------------|-----------------------|
| 1 | **Business Requirements** | Ticket acceptance criteria ticked with verifiable evidence links. |
| 2 | **Implementation Complete** | Tickets in `INTEGRATION_TESTING`; zero unclosed child tickets. |
| 3 | **Automated Tests** | Schema v2 result file exists for the exact release commit SHA with all required suites `PASS` (or valid `N/A`). |
| 4 | **Required Reviewers** | All required reviewers (AI 5, 6, 7) have logged `APPROVED` reports matching the release commit SHA. |
| 5 | **Unresolved Blockers** | Zero tickets with `Blocked: yes`; zero unresolved blocker items across all review reports. |
| 6 | **API Contract Consistency** | Contract test suite passes; OpenAPI schema diff matches ticket impact. |
| 7 | **Business Rules Drift** | Zero modifications to `.ai/BUSINESS_RULES.md` without an approved `DECISION-XXXX` record. |
| 8 | **Clean Git Tree** | `git status` in release worktree is completely clean. |
| 9 | **Database Migration Safety** | `php artisan migrate` up and down rollback executed cleanly in isolated test DB. |
| 10 | **Build Verification** | Native builds pass cleanly for backend and affected Flutter mobile/web apps. |
| 11 | **Baseline Invariant** | Zero new failures compared to `.ai/status/BASELINE.md`; legacy N/A entries cite valid `LD-NNN` debt IDs. |
| 12 | **Supply Chain Integrity** | Zero new high/critical CVEs (`composer audit`); secret scan is 100% clean; lockfiles pinned. |
| 13 | **Client Compatibility** | Compatibility test suite passes; minimum supported mobile versions preserved. |
| 14 | **Compliance & Data Privacy** | Data/Compliance impact tickets include privacy checks; `.ai/DATA_INVENTORY.md` updated. |
| 15 | **Documentation Parity** | `validate-docs` passes; architecture and API references updated. |
| 16 | **Escalations & Quarantines** | Zero unresolved escalations; zero expired flaky-test quarantines in `QUARANTINE.md`. |
| 17 | **Verified Backup Point** | Releases containing database migrations cite a verified snapshot identifier. |
| 18 | **Release Brief & Human Sign-off** | 1-page release brief signed off by the human operator for Tier A releases. |

---

## 2. Release & Merge Automation

1. **Gate Engine**:
   - Verification is automated via `scripts/release/verify-release-gate.ps1`.
   - The script exits with a non-zero exit code if any check fails, displaying the exact reason.
2. **Safe Merge Executor**:
   - Merging into `main` is handled exclusively via `scripts/release/merge-release.ps1`.
   - Merging is strictly refused if `verify-release-gate.ps1` returns a non-zero exit code.
   - Pushing directly to `main` without running the gate script is prohibited.

---

## 3. Loop Control & Escalation Limits

To prevent infinite review cycles between implementers and reviewers, the following hard limits trigger immediate escalation:

| Trigger Condition | Limit Bound | Action on Breach |
|-------------------|-------------|------------------|
| **Review Cycles per Reviewer** | 3 cycles | Ticket blocked (`Blocked: yes (ESCALATED)`); routed to human |
| **Failed Integration Runs** | 2 consecutive runs | Implementation halted; root-cause escalation to human |
| **Time in Single State** | 5 days (flag), 10 days (escalate) | Flagged in `STATUS.md`; requires human re-prioritization |
| **Repeated Finding** | Same issue raised 2 cycles | Immediate escalation; deadlocked review resolved by human |

---

## 4. Hotfix Path & Emergency Protocol

1. **Scope & Naming**:
   - Reserved exclusively for active production emergencies. Ticket type: `HOTFIX`.
   - Branch: `hotfix/VM-<FEATURE>-NNN` branched directly from the latest production release tag.
2. **Reduced Gate Requirements**:
   - Targeted unit/feature tests for the affected component.
   - Security and IDOR test suite.
   - Regression test reproducing the defect.
   - Minimum 1 independent reviewer approval.
   - **Mandatory human operator sign-off.**
3. **Post-Hotfix Reconciliation**:
   - Within 24 hours of hotfix deployment, the full automated test suite must run.
   - The hotfix branch must be back-merged into `main` and all active `feature/` branches.

---

## 5. Deployment, Staging & Rollback Standards

1. **Staging Smoke Test**:
   - Releases must be deployed to staging first. Smoke test checklist: user login, catalog search, cart & checkout with sandbox payment, vendor order visibility, delivery dispatch assignment.
2. **Watch Window & Rollback Triggers**:
   - Standard release watch window: **24 to 72 hours**.
   - Immediate rollback triggers:
     - Payment processing failure rate $> 1\%$.
     - Order placement drop-off $> 10\%$ compared to baseline.
     - SEV1 unhandled fatal exceptions in API or mobile app crash rate $> 0.5\%$.
3. **Rollback Strategy**:
   - Every release manifest must detail: previous release commit SHA, migration rollback safety, and server-side feature flags/kill switches for mobile clients.

---

## 6. Incident Severity & Postmortem Protocols

- **SEV1 (Critical)**: Payments broken, authentication bypass, data exposure, system-wide downtime. Mitigation SLA: $< 1$ hour.
- **SEV2 (Major)**: Core feature unavailable (e.g. cart, checkout, vendor payouts), performance degradation. Mitigation SLA: $< 4$ hours.
- **SEV3 (Minor)**: Cosmetic UI issue, non-critical background task delay.
- Every SEV1/SEV2 incident mandates a postmortem in `.ai/incidents/INC-YYYY-MM-DD-NNN.md` and a permanent regression test.

---

## 7. Client Version Support Policy

- **Minimum Supported Customer App Version**: `1.0.0`
- **Minimum Supported Vendor App Version**: `1.0.0`
- **Minimum Supported Delivery Man App Version**: `1.0.0`
- Breaking API deprecations require a 60-day server-side backward-compatibility bridge and a mandatory in-app forced-upgrade screen before retirement.
