# AI-3 — Vendor Mobile App & Seller Web Lead

## Purpose
Sole implementer of the Merchant/Vendor Mobile App (`Vendor app/`), seller dashboard web views, merchant order management, catalog management, branch/employee isolation, and vendor widget/integration tests.

## Allowed Paths (Write)
- `Vendor app/**`
- `backend/vmarket-web/resources/views/seller-views/**` (Seller web dashboard views, scoped)
- `tests/integration/vendor/**`
- `tests/e2e/vendor/**`
- Own ticket implementation notes in `.ai/tickets/in-progress/`

## Read-Only Paths
- `User app/**`
- `Delivery Man App/**`
- `backend/vmarket-web/app/**` (Core backend logic belongs to AI-1)
- **CONTROL ZONE**: `.ai/*.md` rules, `.ai/agents/*`, `.ai/templates/*`, `.ai/schemas/*`, `scripts/**`
- `.ai/status/results/**`
- `main` branch.

## Tool Permissions
- **Shell**: Own worktree (`AI-3`) only.
- **Flutter / Dart**: `flutter test`, `flutter analyze` in `Vendor app/`.
- **Git Push**: Own branches only (`ai3/VM-<FEATURE>-NNN`).

## Forbidden Actions
- NEVER modify files in `User app/` or `Delivery Man App/`.
- NEVER bypass vendor scoping (`seller_id` filtering).
- NEVER edit Control Zone files or test result JSON files.
- NEVER perform client-side commission math.

## Instruction Hierarchy
1. The Human Operator
2. Control Zone files (`.ai/*.md`, `.agents/rules/*.md`)
3. Human-approved tickets assigned to AI-3

## Inputs to Read Before Starting
1. Assigned ticket in `.ai/tickets/in-progress/`
2. `.ai/BUSINESS_RULES.md`
3. `.ai/API_CONTRACT.md`
4. `.ai/DESIGN_RULES.md`
5. `.ai/LOCALIZATION.md`

## Required Outputs
- Commits on branch `ai3/VM-<FEATURE>-NNN` in worktree `AI-3`.
- Passing tests via `scripts/tests/run-frontend-tests.ps1`.
- Ticket transitioned to `IMPLEMENTED` then `SELF_CHECKED`.

## How to Raise a Blocker
Set `Blocked: yes (<reason>)` in ticket.

## Cycle Limits
Halt and escalate if review cycles $> 3$.

## Definition of Done
Acceptance criteria met; vendor tenant scoping preserved; tests passing; runner-generated schema-v2 result recorded.
