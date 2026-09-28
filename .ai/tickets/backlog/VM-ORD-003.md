# Ticket Template (Appendix A)

Ticket ID:            VM-ORD-003
Title:                Admin ProductController trait property conflict — $productService promoted twice, class cannot load
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-28 (found during VM-ORD-002 verification: after Author bindings resolved, `route:list` hits this fatal)
Size estimate:        xs (2-line change in 1 file)

Business requirement:
Problem:            `Admin\Product\ProductController:81` promotes `private readonly ProductService $productService` in its own `__construct`, while composed `App\Traits\ProductTrait:18-20` promotes the identical property in the trait `__construct`. PHP fatals at class composition: the class cannot load AT ALL, so every admin product route 500s and `php artisan route:list` exits 255. (Trait ctor is shadowed by the class ctor; the duplicate property declaration is the fatal, not injection.)
Expected behavior:  Class loads; container injects `ProductService` once with identical effective behavior. Minimal fix: demote `:81` to a plain `ProductService $productService` param + explicit `$this->productService = $productService;` in the ctor body (trait-composed property initialized once). No other file touched — sibling trait users (`Vendor\ProductController:283`, etc.) are only fixed if `route:list` proves them broken, under this same ticket if so.
Forbidden behavior: No behavior change (same service, same property, same readonly semantics). No trait signature changes (9 other users depend on the trait ctor). No prod traffic.
Affected systems:     admin product routes, artisan console (route:list)
Tier / area:          A (admin catalog management; blocks VM-CUST-013 route:list evidence with VM-ORD-001/002)
Legacy debt IDs:      none
Affected APIs:        admin product CRUD routes; `php artisan route:list`
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (2-line composition fix)
Affected database tables:     none
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (report lives in review file)
Assigned AI:          BACKEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-ORD-003 (from current `v1` AFTER VM-ORD-002 releases; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
Work order:
- **Goal:** Resolve the trait/class `$productService` dual promotion so the admin controller loads with identical injection behavior.
- **Branch:** `backend/VM-ORD-003` from post-ORD-002 `v1`.
- **Allowed files:** `app/Http/Controllers/Admin/Product/ProductController.php` (ctor lines ~60-84 only); own ticket notes.
- **FORBIDDEN:** trait file, sibling controllers (unless route:list proves same fatal — then list, don't freelance), Flutter/Blade/assets, Control Zone, results/reviews/changelog.
- **Acceptance:** (1) `php -l` clean; (2) class loads (route:list progresses past it — full exit-0 if no further latent fatals, else next defect filed); (3) admin product index smoke (server :8000, session) without fatal; (4) suites green.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-ORD-003` full HEAD (JSON uncommitted, report counts); push; BACKEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; testing sqlite + sandbox only.
Dependencies (tickets/features): VM-ORD-002 (branches after it releases); VM-CUST-013 (needs this chain for route:list evidence)
Tests required:       `php -l`; `route:list` progress; admin product smoke; suites green; run-all JSON at full SHA
Security requirements: no secrets; zero behavior delta
Acceptance criteria:
- [ ] Admin ProductController loads (no composition fatal)
- [ ] `php artisan route:list` completes exit 0 (or next latent defect filed with file:line)
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (composition fix)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-28  Reviewer AI  BACKLOG (filed)  Exposed by VM-ORD-002 restoration: next fatal is the trait dual-promotion, not Author. Distinct root cause → own ticket.
