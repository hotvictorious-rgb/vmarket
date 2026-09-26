# Victorious MARKET — Multi-AI Engineering Control System (.ai)

> **MANDATORY GOVERNANCE NOTICE**:
> This directory (`.ai/`) hosts the operational governance, formal state machine tickets, reviewer sign-offs, and release gating criteria for the Victorious MARKET monorepo under the **3-AI Control System (Backend → Frontend → Reviewer)**. The 8-role specification (v3) is retired; historical tickets/reviews preserve it as audit trail only.

---

## 1. Structure Overview

* **Control Zone (Human-Owned Only)**:
  - `BUSINESS_RULES.md`: Canonical marketplace logic, commission rates, and invariants.
  - `ARCHITECTURE.md`: Monorepo physical-to-logical structure mapping.
  - `SCOPE.md`: Authoritative systems, modules, and owner/reviewer registry.
  - `GATED_AREAS.md`: Tier classification (Tier A Critical, Tier B Gated, Tier C Legacy).
  - `LEGACY_DEBT.md`: Pre-existing test gap and characterization debt register.
  - `DATA_INVENTORY.md`: PII, payment tokens, and data retention inventory.
  - `agents/`: Role charters for exactly 3 AIs (`BACKEND_AI`, `FRONTEND_AI`, `REVIEWER_AI`).
  - `templates/`: Official ticket, review, decision, incident, and release manifests.
  - `schemas/`: Test-result schema definitions (`schema-v2.json`).
  - `runbooks/`: Operational deployment, backup restoration, and incident response procedures.

* **Operational & Working Areas**:
  - `tickets/`: Structured Markdown tickets tracking features, bugs, hotfixes, and decisions (`backlog/`, `ready/`, `in-progress/`, `review/`, `changes-required/`, `approved/`, `released/`, `cancelled/`).
   - `reviews/`: Reviewer AI verdicts (`APPROVED` / `CHANGES_REQUIRED` per ticket @ exact SHA).
  - `decisions/`: Formal human approvals and architectural decisions (`DECISION-XXXX`).
  - `releases/`: Formal release candidate manifests (`RELEASE-YYYY-MM-DD-NNN`).
  - `incidents/`: Postmortem records (`INC-YYYY-MM-DD-NNN`).
  - `status/`: Real-time script-generated metrics, baseline audits, quarantine lists, and test runner outputs (`results/`).

---

## 2. Prime Directives for All AIs

1. **Non-Destructive Guarantee**: Never destroy existing production code or restructure folders unnecessarily.
2. **Worktree & Branch Isolation**: Backend AI works on `backend/VM-*` branches, Frontend AI on `frontend/VM-*` branches, Reviewer AI on detached HEAD + `reviewer/` metadata branches only.
3. **No Direct Commits to `main`**: All features must pass the hard release gate and be merged through `scripts/release/merge-release.ps1` with human authorization.
4. **Control Zone Immutability**: No AI may modify files in the Control Zone. Any change to business rules or test gates requires a human-approved decision ticket.
