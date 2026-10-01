# DECISION-008 (DRAFT for human ruling)

DECISION-008
Type:                     TECHNICAL
Question:               Who commits the 3 reviewed theme-JS hygiene files of VM-CUST-003 (`cart.js`, `cart-list-page.js`, `_route-for-js.blade.php`), given the DECISION-006 AI-2 hook allowlist rejects them?
Background:             AI-5 cycle-2 APPROVED a 5-file remediation tree. The 2 behavior-critical blades shipped as 8a61392a. The 3 JS files are dead-code removal only (`setShippingIdFunction` / `renderCouponCodeApply` re-init + orphaned `#set-shipping-url` span; old JS no-ops safely against new blades). On 2026-09-25 a commit of the 3 files in the AI-2 tree was hook-rejected. DEEP-AUDIT CORRECTION (2026-09-25): the AI-2 tree carries the PRE-DECISION-006 hook (blanket `backend/` ban, no exceptions — predates commits a10a6760/dc273bfa). The CURRENT hook (root/MAIN/v1) DOES allow AI-2 `cart*.js`/`shipping-page.js`/`payment-page.js` and the 2 blades; only `layouts/partials/_route-for-js.blade.php` remains excluded. So: `cart.js` + `cart-list-page.js` need only a rebased AI-2 tree; `_route-for-js.blade.php` needs a ruling. Full diff preserved at `scratch/VM-CUST-003-js-cleanup.patch` (4170 bytes).
AI positions:
- AI-8: No authorship stake; presents options. AI-8 authored no app code in this ticket.
- AI-5 (from cycle-2 report): JS removal verified correct, non-blocking; flagged as follow-up-safe.
Technical evidence: hook rejection transcript (2026-09-25, AI-2 tree); SHA256 verified — shipped blades identical to approved tree; old-JS/new-blade interop traced safe (bindings `#proceed-to-next-action` intact in both versions).
Business implications: None for V1 launch either way (no behavior change). Keeping the patch unapplied leaves trivial dead code; amending the hook widens AI-2 scope slightly beyond DECISION-006 storefront-views intent.
Options:
- Option A (Recommended): Assign to AI-1 as ticket VM-CUST-010 (owns `backend/vmarket-web/**` literally; hook passes in every tree version). Small, reviewable, keeps DECISION-006 intact.
- Option B: AI-2 rebases onto current hook (inherits JS allowlist), commits `cart.js` + `cart-list-page.js` itself; `_route-for-js.blade.php` still needs a home (AI-1 per Option A, or human hook amendment).
- Option C: Human amends hook allowlist (+`layouts/partials/_route-for-js.blade.php`) and AI-2 commits all 3 on a follow-up branch with AI-5 re-review.
- Option D: Fold into VM-THEME-001 (same theme area, Tier C) — slowest, couples unrelated debt.
Decision required from human: A, B, or C.
Final decision:          A
Date:                    2026-09-25
Authorizes changes to: scripts/git/validate-commit.ps1 allowlist (only under Option B)
Expiry:                   none
