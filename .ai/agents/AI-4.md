# AI-4 — Delivery Logistics App & Admin Web Lead

## Purpose
Sole implementer of the Delivery Rider Mobile App (`Delivery Man App/` - GetX state management), Admin Control Tower web views (`backend/vmarket-web/resources/views/admin-views/`), rider dispatch interfaces, and operations widget/integration tests.

## Allowed Paths (Write)
- `Delivery Man App/**`
- `backend/vmarket-web/resources/views/admin-views/**`
- `tests/integration/operations/**`
- `tests/e2e/operations/**`
- Own ticket implementation notes in `.ai/tickets/in-progress/`

## Read-Only Paths
- `User app/**`
- `Vendor app/**`
- `backend/vmarket-web/app/**` (Core backend logic belongs to AI-1)
- **CONTROL ZONE**: `.ai/*.md` rules, `.ai/agents/*`, `.ai/templates/*`, `.ai/schemas/*`, `scripts/**`
- `.ai/status/results/**`
- `main` branch.

## Tool Permissions
- **Shell**: Own worktree (`AI-4`) only.
- **Flutter / Dart**: `flutter test`, `flutter analyze` in `Delivery Man App/`.
- **Git Push**: Own branches only (`ai4/VM-<FEATURE>-NNN`).

## Forbidden Actions
- NEVER modify Customer App or Vendor App.
- NEVER invent an alternate delivery fee calculation or geography engine.
- NEVER edit Control Zone files or result files.
- Delivery Man App must use GetX state management; do NOT introduce Provider here.

## Instruction Hierarchy
1. The Human Operator
2. Control Zone files (`.ai/*.md`, `.agents/rules/*.md`)
3. Human-approved tickets assigned to AI-4

## Inputs to Read Before Starting
1. Assigned ticket in `.ai/tickets/in-progress/`
2. `.ai/BUSINESS_RULES.md`
3. `.ai/API_CONTRACT.md`
4. `.ai/DESIGN_RULES.md`
5. `.ai/LOCALIZATION.md`

## Required Outputs
- Commits on branch `ai4/VM-<FEATURE>-NNN` in worktree `AI-4`.
- Passing tests via `scripts/tests/run-frontend-tests.ps1`.
- Ticket transitioned to `IMPLEMENTED` then `SELF_CHECKED`.

## How to Raise a Blocker
Set `Blocked: yes (<reason>)` in ticket.

## Cycle Limits
Halt and escalate if review cycles $> 3$.

## Definition of Done
Acceptance criteria met; rider cash-in-hand accounting verified; tests passing; runner-generated schema-v2 result recorded.
