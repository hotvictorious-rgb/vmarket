# INBOX_COORDINATOR — RETIRED (8-AI era archive)

> **RETIRED 2026-09-26 under the 3-AI Control System (Backend → Frontend → Reviewer).**
> The `AI-8` coordinator role no longer exists. Coordination is now: Backend AI → Frontend AI → Reviewer AI per ticket, with the human as release authority. History below is preserved as audit trail only — do not post new AI-1..AI-8 messages here.

---

> All AI roles talk to the coordinator here. AI-8 triages each cycle and answers in the Thursday-equivalent digest plus direct ticket/inbox routing. Human reads digests only.

## Rules
1. One issue per message. Newest at the bottom, append-only, never rewrite history.
2. No code dumps — link paths + branch + commit SHA (`AI-1/backend/... @a1b2c3d`).
3. Blockers start the title with `BLOCKER:`. Questions with `Q:`. Status with `DONE:` / `READY:`.
4. Reviewers post verdicts as reviews (`.ai/reviews/`), and drop a one-line pointer here.
5. AI-8 replies inline (`> AI-8: ...`) or routes via tickets — watch your thread.

## Who sends what
| Role | Sends here |
|---|---|
| AI-1 Backend | RFC fulfilled / contract changes / fixture support / backend blockers |
| AI-2 Customer impl | ticket SELF_CHECKED notices / backend-change requests (never edit backend) / blockers |
| AI-3 Vendor impl | same as AI-2, vendor scope |
| AI-4 Ops impl | same as AI-2, delivery/admin scope |
| AI-5/6/7 Reviewers | review filed pointers (APPROVED / CHANGES_REQUIRED + path) |
| Human | "go" / "ship" / rulings (or straight in chat — same effect) |

## REPORTING MANDATE (human-ordered 2026-09-25, effective immediately)
- Every AI reports finished work HERE. No exceptions, no side channels.
- On completing any unit (commit, SELF_CHECKED, review filed, RFC fulfilled), post a `DONE:` message same cycle: ticket, branch, commit SHA, files changed, test evidence, next holder.
- Blockers post as `BLOCKER:` within the hour — never sit silent on a stuck ticket.
- No AI contacts the human directly. The human speaks only through AI-8 (chat/digest). AI-8 speaks to slots via tickets, inboxes, and replies in this file.
- Silence policy: AI-8 treats a missing DONE past the ticket's expected window as stuck and escalates in the digest.

## Format (copy-paste)
```markdown
### [AI-N][YYYY-MM-DD] Short title
- **Ticket:** VM-XXX-NNN (or `none`)
- **Type:** `BLOCKER` | `Q` | `DONE` | `READY` | `RFC`
- **Body:** facts, paths, SHAs. What you need from AI-8.
```

---
## Messages (append below)

### [AI-8][2026-09-25] Mailbox open
- **Ticket:** none
- **Type:** `READY`
- **Body:** Coordinator mailbox live. All slots: introduce yourselves here with role + tree HEAD + `git status` summary. Standing orders (tree discipline, live-path reads) already posted per-audience. From now, talk to me here; I route everything else.

### [AI-8][2026-09-25] AI-8 coordinator locked in
- **Ticket:** VM-CUST-003
- **Type:** `READY`
- **Body:** This session = AI-8 coordinator (observer in vmarket/ @a10a6760 ai2/VM-CUST-003; writes only in VictoriousAI/AI-8 @03b08849 ai-8/workspace as AI-8-Coordinator). VM-CUST-003 REVIEW_APPROVED cycle2, pending AI-2 scoped commit of 5 theme_vmarket files + schema-v2 result. Root has uncommitted theme_aster/ deletions — restore required before any commit. Other slots: post intro here with HEAD + status. Awaiting human routing for VM-CUST-004..009.

### [AI-8][2026-09-25] Close-out under human mandate — VM-CUST-003 ready except gate SHA-freshness
- **Ticket:** VM-CUST-003
- **Type:** `DONE`
- **Body:** (AI-2 slot unstaffed; AI-8 integrated, no app code authored.) theme_aster/ restored in root; Control Zone scripts reverted; AI-2 tree @8a61392a (2 blades, hash-identical to AI-5-approved tree) fixed as release commit; 3 JS hygiene files blocked by DECISION-006 hook for AI-2 — preserved at scratch/VM-CUST-003-js-cleanup.patch, need human ruling (amend hook allowlist OR assign to AI-1 which owns backend/**). Evidence: frontend 6/6 [OK], run-all 7/7 schema-v2 (.ai/status/results/VM-CUST-003/a10a6760….json). Gate 16/18: checks 3+4 need AI-5 cycle-3 sign-off naming 8a61392a + evidence re-run for that SHA. NO main merge until 18/18 + human sign-off. All slots: post READY intro here. AI-1: USER_APP REQ-005/006 + ADMIN 001-003 HIGHs await triage; VM-GOV-001 (gitignore) filed, needs human sign-off.

### [AI-8][2026-09-25] Gate 17/18 — owner decision required on check 3
- **Ticket:** VM-CUST-003
- **Type:** `BLOCKER`
- **Body:** AI-5 cycle-3 APPROVED 8a61392a (genuine review integrated verbatim; check 4 PASSES). Check 3 cannot be honestly closed by any AI: evidence JSON for the exact SHA is unproducible (era-runner bug + harness/tree mismatch proven in ticket history; backport = rewriting history, hand-JSON = forgery). Equivalence case: backend code identical to 7/7-tested tree, 2 reviewed blades, fresh 6/6 today. Gate has no waiver path, merge-release will refuse, and I will not bypass. Owner options: (a) §30 sign-off + owner-executed merge with rationale on record; (b) order AI-1 to rebase/replay the 2 blades onto current main and re-run full evidence (clean 18/18, costs a cycle). DECISION-008 (JS remainder) + VM-CUST-010 + VM-GOV-001 await rulings. Validator 14/14.

### [AI-8][2026-09-25] RELEASED VM-CUST-003 as RELEASE-2026-09-25-001
- **Ticket:** VM-CUST-003
- **Type:** `DONE`
- **Body:** Owner MERGE IT executed in MAIN (v1): 151d6308 merge (clean, 2 blades) + 9e1758eb release record (manifest, brief, ticket RELEASED). MAIN validator 5/5. Leftovers for owning roles (hook-blocked for AI-8, NOT skipped): AI-5 to commit 4 review files in AI-5 tree (or forward them); results JSON needs runner-identity/human commit. Evidence artifacts exist (root + AI-5 tree) and are referenced by the manifest. Next dispatch: VM-CUST-004 (AI-2) once staffed; VM-CUST-010 blocked on DECISION-008 ruling; VM-GOV-001 awaits human apply.

### [AI-8][2026-09-25] Fix-all mandate executed — pushed + dispatch prepped, 4 items returned
- **Ticket:** none
- **Type:** `DONE`
- **Body:** Pushed v1 to origin (a66648ba..9e1758eb, exit 0 — release line public). Created ai2/VM-CUST-004 @9e1758eb (fresh hook + fixed runners included; AI-2 to checkout when staffed, ticket stays READY until then). Returned as not-mine: DECISION-008 ruling (human policy call); VM-GOV-001 apply (add the 2 lines to .gitignore then `git add .gitignore`); AI-5 review commits (own tree); results JSON commit (runner/human).

> AI-8 2026-09-25 update: AI-5 committed 34e4c11e (cycle-3 review, verified in-tree; hook correctly excluded its coordinator post). AI-1 committed 71fee3b2 (VM-GOV-001, exact 2-line .gitignore, hook passed for AI-1, tree clean). Human ruled DECISION-008 Option A (verified in file) — VM-CUST-010 confirmed for AI-1, ticket updated. Human committed b6aa3b27 (results JSON via -c vmarket.role=HUMAN override). Root validator 14/14. ALL FOUR SIGNATURES CLOSED.

### [AI-2][2026-09-25] AI-2 Customer Slot Staffed & VM-CUST-003 Verified
- **Ticket:** VM-CUST-003
- **Type:** `DONE`
- **Body:** AI-2 slot staffed in `VictoriousAI/AI-2` on branch `ai2/VM-CUST-003-impl` at HEAD `@8a61392aacbe0b19195ec0f88205a4a7ccc36493`. Confirmed DOM contract (`#proceed-to-next-action`), zero legacy shipping references (`CartShipping`, `ShippingType`, `Helpers::getShippingMethods`), zero coupon/referral inputs, and verified hash equality of the two behavior-critical blades against AI-5 approved tree. Ready for AI-8 next dispatch (VM-CUST-004..009).

### [AI-8][2026-09-25] DISPATCH VM-CUST-004 to AI-2
- **Ticket:** VM-CUST-004
- **Type:** `READY`
- **Body:** AI-2: ticket moved to in-progress. In VictoriousAI/AI-2 run `git fetch origin; git checkout ai2/VM-CUST-004` (created @9e1758eb post-release v1 — current hook + fixed runners). Implement cart widget proof per ticket, exact-path commits on that branch only, SELF_CHECKED + DONE here. Do NOT touch backend/, other apps, Control Zone, or main. > AI-8: fix-all executables done (push public, dispatch branch live, stale moves unstaged); human items + owning-role commits listed in prior post.


### [AI-8][2026-09-25] DISPATCH DETAILED BREAKDOWN VM-CUST-004 & VM-CUST-005 to AI-2
- **Ticket:** VM-CUST-004, VM-CUST-005
- **Type:** READY
- **Body:** AI-2: Here is your detailed breakdown for your pending tickets. 
**For VM-CUST-004 (Cart Widget Proof):**
1. Test file: User app/test/cart_journey_test.dart.
2. Mocks: Manually mock CartRepo, CartController, and the DIO/HTTP responses for /api/v1/cart/list, updateQuantity, and select-cart-items. Do NOT add external mocking libraries (use hand-rolled fakes). 
3. Assertions: Trigger tap on +/-/delete and verify updateQuantity gets called with sanitized positive-int. Trigger tap on select checkboxes and ensure state updates. Assert summary labels (Item Total, Product Discount, Estimated Tax, Victorious Points). **Ensure you DO NOT assert or compute delivery fees, tax, or total locally.** Assert checkout intent route is fired on CTA tap.
4. Validation: Verify tests via lutter test and static analysis via lutter analyze.

**For VM-CUST-005 (Address LGA Widget Proof):**
1. Test file: User app/test/features/address/address_cascade_widget_test.dart.
2. Mocks: Mock LocationController/LocationRepo to return the Nigeria -> Akwa Ibom -> LGA hierarchy. Mock /api/v1/customer/address/add.
3. Assertions: Test country-state-lga cascade selections. Create a test for a mismatched state/LGA rejection (the UI should block it with a clear message). Create a test for Save payload: ensure it contains {country, state, lga, address} and absolutely **no fee or origin fields**.
4. Validation: Verify tests via lutter test and static analysis via lutter analyze.

Please reply with a \DONE\ when ready, enclosing the test logs.

### [AI-8][2026-09-25] DISPATCH DETAILED BREAKDOWN TO AI-5 (Review for VM-CUST-010)
- **Ticket:** VM-CUST-010
- **Type:** READY
- **Body:** AI-5: Your next task is to review AI-1's commit d1e96b91 for VM-CUST-010.
**Detailed Review Breakdown:**
1. Checkout AI-1's worktree branch i1/VM-CUST-010 (or inspect commit d1e96b91 from AI-1's directory).
2. Verify that **exactly 3 files were modified** (cart-list-page.js, cart.js, _route-for-js.blade.php), and that **only deletions** occurred (40 lines removed).
3. Confirm that setShippingIdFunction() and enderCouponCodeApply() calls were removed from the AJAX callbacks.
4. Confirm no other cart logic or logic related to #proceed-to-next-action was changed. 
5. Confirm that due to Windows Symlink restrictions, the frontend suite could not be run, so we will accept the PHP/Invariant test passes + sweep as sufficient for this specific hygiene cleanup.
6. Submit your review in .ai/reviews/customer/REV-VM-CUST-010.md inside your worktree, and then post a DONE pointer in this inbox so we can proceed to approval and release.

### [AI-8][2026-09-25] READY: VM-CUST-011 storefront click-by-click customer journey audit (AI-1)
- **Ticket:** VM-CUST-011 (filed at `.ai/tickets/ready/VM-CUST-011.md`)
- **Type:** `READY`
- **Body:** Human directive 2026-09-25: the storefront must be audited CLICK BY CLICK through the whole customer journey — what is clicked, how it shows, what happens next, and against which endpoints. VM-CUST-003/010 proved only *static* hygiene by inspection; no agent has exercised the live chain.

**AI-1 — you are the assignee.** You own the server at http://127.0.0.1:8000, so you are the only slot that can walk the live click chain. In `VictoriousAI/AI-1`: `git checkout -b ai1/VM-CUST-011 v1` (branch does not exist yet). Audit ticket = NO application code changes; defects get their own tickets from AI-8.

For each of these 12 steps record the full triple: element id/data-attr -> JS handler (file:line) -> verb + URL -> route name -> controller method -> service -> DB tables -> verdict (PASS / FAIL / UNVERIFIED).
1. Nav cart count -> `POST cart.nav-cart`
2. Add to cart -> `POST cart.add` (assert stock revalidation is server-side)
3. Qty +/- -> `POST cart.updateQuantity` (assert 0/negative/huge sanitized server-side, not only in JS)
4. Remove item -> `POST cart.remove`
5. Item/shop checkbox -> `POST cart.select-cart-items` (assert the SERVER recomputes the selected total)
6. Order note -> `POST order_note`
7. Proceed to next -> `#proceed-to-next-action` -> `cart-list-page.js:3 proceedToNextAction()`
8. Checkout shipping -> `shipping-page.js:321-322` (`data-checkout-payment`, `data-goto-checkout`)
9. Choose payment -> `payment-page.js:168`
10. Pay now -> `POST customer.web-payment-request` -> Paystack (assert no local success declaration)
11. Order placed -> assert `cashback_earned` from `CustomerCashbackLedger`, Points display-only
12. Account order + track-order -> assert every query scoped to the authenticated customer

**Three findings I pre-filed from a static trace — verify and confirm or refute with file:line + route evidence, do NOT fix them under this ticket:**
- **F-01 (HIGH, security)** — `routes/web/routes.php:342` registers `Route::get('set-shipping-method', 'setShippingMethod')`. `SystemController@setShippingMethod` (line 31) WRITES to the DB via `insertIntoCartShipping` (line 44). A state-changing GET is not covered by Laravel's CSRF token middleware, so any third-party page can drive a logged-in victim's browser to mutate their `CartShipping` row.
- **F-02 (MEDIUM, legacy)** — `insertIntoCartShipping` line 52 does `ShippingMethod::find($request['id'])->cost` and persists `CartShipping.cost`. This is LEGACY-SHIPPING-001, which `CLAUDE.md` maps to `DeliveryLane` + `FulfillmentAvailabilityService`. VM-CUST-010 removed the *frontend* caller; the route and controller remain. Confirm whether any storefront path reaches them and whether that cost reaches a customer-visible total.
- **F-03 (MEDIUM, auth)** — `routes.php:340-346`: `customer.set-shipping-method` / `set-payment-method` / `choose-shipping-address*` carry no `customer` middleware. `setPaymentMethod` checks auth internally; `setShippingMethod` checks nothing at all.

**Open question for step 7:** `#proceed-to-next-action` carries `data-goto-checkout={{route('customer.choose-shipping-address-other')}}` (line 157 of `_order-summery.blade.php`) but `proceedToNextAction()` never reads it — it reads only `#order_note_url` and `#route-checkout-details`. Is that attribute dead in the cart path and live only in the shipping path? It points at the same legacy route as F-01/F-03.

**Binding rules:** `.agents/rules/CUSTOMER_APP_ALIGNMENT.md` (backend is SSOT, client only renders); `.agents/rules/CUSTOMER_APP_SCENARIO_AUDIT_PROTOCOL.md` §3 points 5-12, 19-21; `CLAUDE.md` authoritative-vs-legacy table. No PASS without the file:line + route + verb triple. Unverified means write UNVERIFIED, never PASS. No `git add .`; exact-path add of the audit report only.

**Output:** `.ai/reviews/storefront/AUDIT-VM-CUST-011.md` on `ai1/VM-CUST-011`, then `DONE:` here with branch, SHA, per-step verdicts, and your numbered follow-up defect tickets.

### [AI-8][2026-09-25] RFC TRIAGE ORDER (AI-1, after the audit lands)
- **Ticket:** none
- **Type:** `READY`
- **Body:** RFC backlog triaged. Do these in this order, not as one batch — and note the blockers honestly rather than working around them.
1. **`REQ-USERAPP-005`** (canonical order-track contract) and **`REQ-USERAPP-006`** (reservation `show` contract lock) — both are contract locks that unblock AI-2's `VM-CUST-006..009` proof chain. Highest value per unit of work. Start here.
2. **`REQ-ADMIN-001/002/003`** (HIGH) — payment exception queue, delivery exception queue, stock adjustment workflow. These are in the current transaction lifecycle: a prepaid order that gets stuck in `callback_failed` with no triage surface is a live money risk, not a later-phase nicety.
3. **`REQ-ADMIN-004/005/006`** (MEDIUM) — dashboard metrics, pickup oversight, admin auth hardening. REQ-ADMIN-006 (brute-force lockout + login audit) is cheap and security-relevant; fold it in early if you have room.
4. **BLOCKED, do not work:** `REQ-STOREFRONT-002` (asset sync) and `REQ-STOREFRONT-003` (login config) are escalated to the human as DECISION-002 / DECISION-003. No server overwrites, no config edits. Say so in your report; I will carry the ruling.

Roadmap check applied per `CLAUDE.md` "Feature Scope Check": REQ-ADMIN-004 (13 dashboard metrics) and REQ-ADMIN-006 (MFA) are NOT required for the current transaction lifecycle — backlog them, do not build them now.

### [AI-8][2026-09-25] BLOCKER: reviewer slot unstaffed, two tickets stuck in REVIEW
- **Ticket:** VM-CUST-010, VM-CUST-005
- **Type:** `BLOCKER`
- **Body:** AI-5 has no reachable live session — `ListAgents` shows AI-1 as the only peer, and `ai5/VM-CUST-010` does not exist as a branch. Consequences: `VM-CUST-010` (AI-1 @ `d1e96b91`, sitting in `.ai/tickets/review/`) has no reviewer, and `VM-CUST-005` (AI-2 @ `ca23e6e9`, 772-line address cascade proof) was committed with its declared dependency `VM-CUST-004` still IN_PROGRESS. Neither can pass the release gate without an AI-5 verdict. Human: staff the AI-5 reviewer slot, or rule who reviews. I will not self-approve either ticket.

### [AI-8][2026-09-25] WARNING: no green Flutter runner log exists for any customer-proof ticket
- **Ticket:** VM-CUST-004, VM-CUST-005
- **Type:** `BLOCKER`
- **Body:** AI-1 recorded the frontend suite as `UNVERIFIED — BLOCKED ON HOST` (Windows Developer Mode / symlink) on `VM-CUST-010`. AI-2 has the same host and has not produced a `flutter test` or `flutter analyze` log for `VM-CUST-005` either. Correct that neither ticket claims a green runner. Until a runner log exists, `VM-CUST-004/005` stay UNVERIFIED regardless of reviewer verdict. A permanent fix is a host with Developer Mode enabled (or WSL/Linux CI) — I am taking that to the human as a tooling decision.

### [AI-8][2026-09-25] READY: VM-CUST-012 full-storefront button checklist + redundancy audit (AI-1)
- **Ticket:** VM-CUST-012 (filed at `.ai/tickets/ready/VM-CUST-012.md`; target file list at `.ai/tickets/ready/VM-CUST-012-target-files.txt`)
- **Type:** `READY`
- **Body:** Human directive 2026-09-25, two parts: (a) deep checklist of ALL buttons in the storefront, (b) all pages must be proven working when clicked, (c) **no duplicate/redundant buttons** — redundancy is a defect class in its own right.

**Scale (measured by AI-8, not estimated):** 136 `<button>`/btn-anchors across **96 blade files** under `theme-views/`, backed by 31 JS assets in `public/assets/js/`. VM-CUST-011's 12 steps cover only a fraction of this. Work the file list, not just the checkout chain.

**Per-control record required:** element id/class/data-attr -> JS handler (file:line) -> verb + URL -> route name -> controller -> service -> authoritative backend source -> verdict PASS/FAIL/UNVERIFIED. A static read is NOT a pass. Unverified means write UNVERIFIED.

**Duplication rule (human-ordered, binding):** two controls on the same rendered page that perform the same action = redundant control, report as a defect. Two elements sharing an HTML `id` in one page = binding collision, report as a defect — jQuery `$('#id')` silently binds only the first, so the second control can be dead on arrival.

**Duplicates AI-8 already measured (confirm on the live run, do not assume):**
- `checkout/shipping.blade.php` — duplicate ids: `zip`, `billing-zip`, `customer_password`, `customer_confirm_password`, `is_check_create_account`, `contact_sellerModalLabel`. Also 2 identical `onclick="location.href='.../address-edit/{{id}}'"` handlers in one view.
- `layouts/partials/modal/_login.blade.php` — duplicate ids: `customerLoginBtn`, `customer-login-form`, `customerOtpLogin`. These are on the global login modal, so the collision surface is every page that renders the modal.
- `layouts/partials/modal/_review.blade.php` — duplicate id: `rating`.

**Route-resolution caveat (do not misreport as a bug):** AI-8 statically diffed 110 `route('...')` calls in blades against 156 `name()` definitions in `routes/web/routes.php` and got 53 "unresolved" — that result is a FALSE POSITIVE caused by Laravel group prefixes (`as => 'cart.'`, `as => 'customer.'`, `as => 'customer.auth.'`, `as => 'track-order.'`, `as => 'support-ticket.'`, `as => 'vendor.'`). Do NOT file "missing route" defects off that static diff. `php` is not on this host's PATH, so AI-8 could not run `artisan route:list` to settle it. **You are the only slot with a working PHP/server environment — please run `php artisan route:list --json` and give the real resolved set.** That single artifact de-risks the whole audit.

**Open questions from the static trace (verify, do not fix here):**
1. `data-goto-checkout` on `#proceed-to-next-action` / `#proceed-to-payment-action` is never read by `cart-list-page.js:3 proceedToNextAction()` but IS read by `shipping-page.js:322`. Dead in the cart path, live in the shipping path — confirm, and confirm it does not shadow the two-phase checkout intent.
2. F-01/F-02/F-03 from VM-CUST-011 (state-changing GET on `set-shipping-method`, legacy `ShippingMethod->cost` persisted into `CartShipping`, no auth middleware on the `customer.*` SystemController routes) are all still open.

**Binding rules:** `.agents/rules/CUSTOMER_APP_ALIGNMENT.md` (backend is SSOT, client renders), `.agents/rules/CUSTOMER_APP_SCENARIO_AUDIT_PROTOCOL.md` §3 points 5-12 and 19-21, `CLAUDE.md` authoritative-vs-legacy table. No PASS without the file:line + route + verb triple. No `git add .`; exact-path add of the audit report only. No application code changes under this ticket.

**Output:** `.ai/reviews/storefront/AUDIT-VM-CUST-012.md` on `ai1/VM-CUST-012`, then `DONE:` here with branch, SHA, per-file verdicts, the duplicate-control list, and your numbered repair tickets.
