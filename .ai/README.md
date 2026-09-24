# Victorious MARKET — Multi-AI Engineering Control System (.ai)

> **MANDATORY GOVERNANCE NOTICE**:
> This directory (`.ai/`) hosts the operational governance, formal state machine tickets, reviewer sign-offs, and release gating criteria for the Victorious MARKET monorepo under the **Multi-AI Engineering Control System Specification (v3)**.

---

## 1. Structure Overview

* **Control Zone (Human-Owned Only)**:
  - `BUSINESS_RULES.md`: Canonical marketplace logic, commission rates, and invariants.
  - `ARCHITECTURE.md`: Monorepo physical-to-logical structure mapping.
  - `SCOPE.md`: Authoritative systems, modules, and owner/reviewer registry.
  - `GATED_AREAS.md`: Tier classification (Tier A Critical, Tier B Gated, Tier C Legacy).
  - `LEGACY_DEBT.md`: Pre-existing test gap and characterization debt register.
  - `DATA_INVENTORY.md`: PII, payment tokens, and data retention inventory.
  - `agents/`: Logical role definitions and path boundaries (`AI-1` through `AI-8`).
  - `templates/`: Official ticket, review, decision, incident, and release manifests.
  - `schemas/`: Test-result schema definitions (`schema-v2.json`).
  - `runbooks/`: Operational deployment, backup restoration, and incident response procedures.

* **Operational & Working Areas**:
  - `tickets/`: Structured Markdown tickets tracking features, bugs, hotfixes, and decisions (`backlog/`, `ready/`, `in-progress/`, `review/`, `changes-required/`, `approved/`, `released/`, `cancelled/`).
  - `reviews/`: Independent reviewer evaluations (`customer/`, `vendor/`, `operations/`).
  - `decisions/`: Formal human approvals and architectural decisions (`DECISION-XXXX`).
  - `releases/`: Formal release candidate manifests (`RELEASE-YYYY-MM-DD-NNN`).
  - `incidents/`: Postmortem records (`INC-YYYY-MM-DD-NNN`).
  - `status/`: Real-time script-generated metrics, baseline audits, quarantine lists, and test runner outputs (`results/`).

---

## 2. Prime Directives for All AIs

1. **Non-Destructive Guarantee**: Never destroy existing production code or restructure folders unnecessarily.
2. **Worktree & Branch Isolation**: Every implementation AI works in its dedicated Git worktree and branch (`aiN/VM-FEATURE-NNN`).
3. **No Direct Commits to `main`**: All features must pass the hard release gate and be merged through `scripts/release/merge-release.ps1` with human authorization.
4. **Control Zone Immutability**: No AI may modify files in the Control Zone. Any change to business rules or test gates requires a human-approved decision ticket.
