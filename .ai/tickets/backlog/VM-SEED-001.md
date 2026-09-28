# Ticket Template (Appendix A)

Ticket ID:            VM-SEED-001
Title:                Staging seed produces zero purchasable seller products (no writer for freshness fields)
Type:                 BUG
Status:               BACKLOG
Blocked:              no
Created by / date:    Reviewer AI / 2026-09-28 (found during VM-CUST-003 live cart proof: all 3 seed products rejected by `marketplacePurchasable`)
Size estimate:        xs-small (seeder defaults and/or confirm-flow writer; sandbox seed only)

Business requirement:
Problem:            `Product::scopeMarketplacePurchasable` requires seller products to carry `marketplace_confirmed_at` within 7 days and `availability_expires_at` in the future (`Product.php:224-270`), but NOTHING in `app/` writes `marketplace_confirmed_at` (repo-wide grep: readers only), and staging seed rows carry NULL for both fields. Proven live on testing sqlite: all seed seller products fail `add_to_cart` with "out of stock or unavailable" until the two fields are hand-backfilled (verification 2026-09-28, product 1). Fresh staging = zero buyable products = browse→cart dead for every customer.
Expected behavior:  Fresh staging seed yields ≥1 purchasable seller product via the CANONICAL path (seller confirm flow writes the fields, or seeder sets entitled defaults) — no hand SQL.
Forbidden behavior: No weakening of the purchasability gate. No prod data touched (seed/sandbox only).
Affected systems:     staging seed, seller listing freshness, storefront cart proof (VM-CUST-003 criteria 3/4 depend on buyable seed data)
Tier / area:          A (blocks every staging purchase proof: CUST-003, CUST-013, VEND-002, PAY-001 live-fire)
Legacy debt IDs:      none
Affected APIs:        `POST /cart/add` (gate symptom); seller confirm flow (missing writer)
Contract impact:      no
Client compatibility impact:  no
Feature flag / kill switch:   none - justify (seed/sandbox only)
Affected database tables:     products (seed rows only)
Migration impact:     no
Data impact:          no
Compliance impact:    no
Dependency changes:   none
Documents updated:    none - justify (report lives in review file)
Assigned AI:          BACKEND AI (dispatched by REVIEWER AI with an exact-prompt work order)
Required reviewers:   REVIEWER AI (sole coordinator, gatekeeper, and push authority; verdict bound to exact commit SHA)
Branch / base commit: backend/VM-SEED-001 (from current `v1`; push only this branch; NEVER merge; NEVER push to `v1`/`main`; Reviewer deletes it after merge)
Work order:
- **Goal:** Make fresh staging seed produce purchasable seller products through the canonical confirm path or entitled seed defaults.
- **Branch:** `backend/VM-SEED-001` from current `v1`.
- **Allowed files:** `seed_sqlite_core.php` and/or seller confirm-flow endpoint + seeder (whichever the investigation names as canonical); own ticket notes.
- **FORBIDDEN:** purchasability gate logic (`Product.php` scopes — do NOT weaken); Flutter/Blade/assets; Control Zone; results/reviews/changelog.
- **Acceptance:** (1) decision recorded (confirm-flow writer vs seed defaults, with rule citation); (2) fresh staging DB shows ≥1 seller product passing `marketplacePurchasable` with zero hand SQL; (3) `POST /cart/add` returns status 1 on that product (evidence: live log); (4) `php -l` + suites green.
- **Tests + DONE:** `run-all.ps1 -Ticket VM-SEED-001` full HEAD (JSON uncommitted, report counts); push; BACKEND_DONE branch+SHA.
- **Rules:** exact-path `git add` only; testing sqlite + sandbox only.
Dependencies (tickets/features): VM-CUST-003 (criteria 3/4 live proofs need buyable seed); VM-VEND-002 (publish proof needs the confirm writer if that option wins)
Tests required:       fresh-seed purchasability count; live `cart/add` status 1; suites green; run-all JSON at full SHA
Security requirements: no prod data; no secrets; gate logic untouched
Acceptance criteria:
- [ ] Canonical mechanism named (confirm writer vs seed defaults)
- [ ] Fresh staging has buyable seller product with zero hand SQL
- [ ] Live `cart/add` status 1 on seeded product
- [ ] Suites green, run-all JSON at full SHA

Counters:             review_cycles: 0   integration_failures: 0   reopened_count: 0
Pipeline:             Human → REVIEWER AI → BACKEND AI → REVIEWER AI (APPROVED) → REVIEWER AI pushes
Push rule:            ONLY Reviewer AI merges to `v1` and pushes, after APPROVED + gate PASS, via `scripts/release/merge-release`. Workers push only their own feature branches.
Screenshots:          none - justify (seed fix; evidence is counts + live log)

Implementation notes:
Review notes:
Final decision:
Release commit:

History (append-only):
- 2026-09-28  Reviewer AI  BACKLOG (filed)  Live cart proof needed hand-backfill of `marketplace_confirmed_at` + `availability_expires_at` (NULL on all seed rows; no writer exists in `app/`). Sandbox test-data edits used for the proof stay local to testing sqlite; canonical fix ships here.
