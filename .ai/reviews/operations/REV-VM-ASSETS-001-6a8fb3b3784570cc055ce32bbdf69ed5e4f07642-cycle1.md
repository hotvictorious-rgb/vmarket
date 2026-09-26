# Review: VM-ASSETS-001 styling fix (b) — ruling SPLIT then review

Ticket: VM-ASSETS-001 (split from VM-TEST-001; VM-THEME-001 ID taken on v1 by Aster removal)
Reviewer:                 REVIEWER AI (sole gatekeeper for all backend + frontend code)
Model/tool used:          Muse Spark / git collection (no implementation edits)
Commit reviewed:          6a8fb3b3784570cc055ce32bbdf69ed5e4f07642 (backend/VM-TEST-001; verified on origin/backend/VM-TEST-001 via fetch + rev-parse 2026-09-26 — pushed after cycle-1 collection)
Review cycle number:      1
Files reviewed:
- backend/vmarket-web/config/filesystems.php (`links` array added: storage + themes)
- backend/vmarket-web/app/Http/Controllers/InstallController.php (`step5()` shell_exec removed, single storage:link)
- backend/vmarket-web/app/Http/Controllers/UpdateController.php (same normalization)
Tests run by reviewer:    none this cycle (collection-only + php -l/grep evidence still owed by Backend on new branch)

Ruling (split-or-keep):   SPLIT. VM-TEST-001 work order explicitly forbids `app/**` product logic and any product-code changes (acceptance criterion 4 requires `git diff --stat` test-harness-only). The 3-file themes fix is product bootstrap code, so it cannot land under VM-TEST-001. New ticket VM-ASSETS-001 (fresh branch backend/VM-ASSETS-001 from v1@3976e8aa) is the correct vehicle. VM-TEST-001 must be cleaned back to harness-only.

Business-rule findings:   direction correct — unifies theme symlink creation into Laravel `storage:link` via `filesystems.php` `links`, removing Unix-only `ln -s` that fails on Windows (root cause of first-serve unstyled). No behavior change beyond making the link reliably exist.
Security findings:        positive — removes `shell_exec` from web-request-adjacent install/update path (command-injection surface). Links confined to `resources/themes` + `storage/app/public`. No new risk.
Prompt-injection / untrusted-input findings: none.
Frontend findings:        none (bootstrap-only; no Blade/Flutter touched).
Backend findings:
- `links` array shape is standard (`public_path => storage_path/base_path`). Correct.
- Single `Artisan::call('storage:link')` in try/catch in both controllers; pasted snippet showing a duplicate call is not present in the committed diff — verified clean.
- Base staleness: commit sits on c55a74ef-era backend/VM-TEST-001; must be re-applied cleanly on v1@3976e8aa as backend/VM-ASSETS-001.
Integration findings:     now pushed to origin/backend/VM-TEST-001 (verified 6a8fb3b3 on origin) — must be reverted/stripped from that branch on next Backend push; the fix itself must live only on backend/VM-ASSETS-001.
Client compatibility findings: none.
Testing findings (blockers):
- Missing: `php -l` on all 3 files.
- Missing: `grep -rn "shell_exec|ln -s"` empty proof on both controllers.
- Missing: fresh-link recreation proof on Windows (delete public/storage + public/themes in temp copy, run `php artisan storage:link`, show both resolve).
- Missing: `scripts/tests/run-backend-tests.ps1` tail proving no new failures.
Performance findings:     none.
Dependency findings:      none.
Privacy / data-impact findings: none.
Design / localization findings: none.

Blockers:
1. Re-apply the exact 3-file change on fresh branch backend/VM-ASSETS-001 from v1@3976e8aa; push only that branch.
2. Attach php -l + grep-empty + fresh-link + runner evidence; declare BACKEND_DONE in VM-ASSETS-001 History.
3. Strip these 3 files from backend/VM-TEST-001 (see companion VM-TEST-001 review).
Non-blockers: none.

Decision:                 CHANGES_REQUIRED
