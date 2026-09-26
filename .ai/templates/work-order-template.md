# Work Order Template (exact-prompt dispatch from Reviewer AI)

Reviewer AI copies this template into the ticket as the work order. It must be copy-paste ready: the worker AI needs zero follow-up questions.

```markdown
## WORK ORDER from REVIEWER AI — <Ticket ID> — <BACKEND | FRONTEND>
- **Goal (one sentence):** ...
- **Branch:** `backend/VM-...` | `frontend/VM-...` (create from current `v1`, push only this branch; Reviewer deletes it after merge)
- **Allowed files (exact paths/scopes):** ...
- **FORBIDDEN paths (do not touch):** ... (Backend: all Flutter + Blade + assets. Frontend: all PHP logic.)
- **Contract / inputs you consume:** ... (links to registry entries, schemas, prior handoff)
- **Acceptance criteria (each needs evidence):**
  1. [ ] ... (evidence: ...)
  2. [ ] ...
- **Tests to run + evidence to attach:** ... (`php -l` every file / PHPUnit / `flutter analyze` / runner script)
- **DONE definition:** commits pushed to your branch + ticket notes filled + status word `BACKEND_DONE` | `FRONTEND_DONE` reported in ticket History.
- **Rules:** exact-path `git add` only (never bulk); leave other AIs' files dirty; change notes in ticket notes (NEVER edit `AI_CHANGELOG.md` — Reviewer writes the one entry at release); blocked → report to REVIEWER AI in ticket, never to the human, never sideways.
- **Push rule:** NEVER merge, NEVER push to `main`. Only Reviewer AI merges + pushes after approval.
```
