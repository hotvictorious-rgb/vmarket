# Ticket: VM-GOV-001

Ticket ID:            VM-GOV-001
Title:                Gitignore worktree isolation — ignore .env.ai and per-worktree SQLite DBs
Type:                 FEATURE
Status:               READY
Blocked:              no
Created by / date:    AI-8 / 2026-09-25
Size estimate:        ~4 lines (1 file, .gitignore only)

Business requirement:
Per-worktree isolation files created by `scripts/git/setup-worktrees.ps1` (`.env.ai`, `database_ai-*.sqlite`) must never be committed, or worktree secrets/DBs leak across slots.
Problem:
Root `.gitignore` lacks `backend/**/.env.ai` and `backend/**/database/database_*.sqlite`. AI-1 worktree currently carries this fix as uncommitted dirt (`M .gitignore`, +2 lines) which cannot ship with backend work (scope separation).
Expected behavior:
- `.gitignore` gains the two patterns; `git status` in all 8 worktrees no longer shows `.env.ai` / `database_*.sqlite` as untracked.
- No other ignore rules change; no tracked file becomes ignored.
Forbidden behavior:
- NEVER commit any `.env.ai` or `database_*.sqlite` content in this or any ticket.
- NEVER bundle this with app/backend work; exact-path commit of `.gitignore` only.
- NEVER push secrets or production credentials.
Affected systems:     infrastructure/control-system
Tier / area:          C (control hygiene)
Legacy debt IDs:      none
Affected APIs:        none
Contract impact:      no
Client compatibility impact: no
Feature flag / kill switch: none - version-control hygiene only
Affected database tables: none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none
Assigned AI:          AI-8 (human executes; Control Zone adjacent — needs human sign-off per §4)
Required reviewers:   none (human approves directly)
Branch / base commit: ai-8/workspace (or human-directed branch)
Dependencies:         none
Tests required:
- `git status` clean of `.env.ai` / `database_*.sqlite` in all worktrees after apply
- `git check-ignore` confirms both patterns
Security requirements:
- Verify no secret was ever committed (secret_scan PASS in run-all 2026-09-25)
Acceptance criteria:
- [ ] `.gitignore` contains both patterns
- [ ] All worktrees report no untracked `.env.ai` / `database_*.sqlite`
- [ ] `validate-tickets.ps1` still passes

Counters:             review_cycles: {AI5: 0, AI6: 0, AI7: 0}   integration_failures: 0   reopened_count: 0
Screenshots:          N/A

Implementation notes:
- Patch already proven in AI-1 worktree dirt (+2 lines). Human to apply via exact-path commit on a governance branch.
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-25  AI-8  BACKLOG -> READY  Filed from AI-1 worktree dirt found during close-out; awaiting human sign-off per Control Zone §4.
