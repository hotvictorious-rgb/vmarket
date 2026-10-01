# Ticket: VM-CUST-015

Ticket ID:            VM-CUST-015
Title:                Destructive GET cart/remove-all empties entire cart (CSRF-able)
Type:                 DEFECT
Status:               READY
Blocked:              no
Created by / date:    FRONTEND AI / 2026-09-30 (found during VM-CUST-013 live audit)
Size estimate:        tiny (route verb + any JS caller)

Business requirement:
Cart destruction must require POST with CSRF.
Problem:
`GET cart/remove-all` → `Web\CartController@remove_all_cart` deletes ALL cart rows for the customer/guest (`Cart::where(...)->delete()`), then redirects back. A malicious `<img>`/link can wipe any visitor's cart. Note POST `cart/remove` (single item) is correctly verbs.
Expected behavior:
- Route becomes POST (or DELETE) with CSRF; single-item POST `cart/remove` pattern reused.
Forbidden behavior:
- NEVER leave destructive actions on GET.
Affected systems:     Laravel Backend (Web CartController, routes/web), storefront cart UI caller if any
Tier / area:          A (transaction path)
Affected APIs:        cart/remove-all (verb change GET → POST)
Contract impact:      yes (verb change)
Client compatibility impact: no
Migration impact:     no
Data impact:          no
Documents updated:    none - justify (defect fix)
Assigned AI:          BACKEND AI
Required reviewers:   REVIEWER AI
Branch / base commit: TBD from current `v1`
Dependencies:         VM-CUST-013 (found here)
Tests required:       GET returns 405; POST empties cart; runner green.
Acceptance criteria:
- [ ] GET cart/remove-all → 405 (evidence: probe log).
- [ ] POST empties cart and redirects (evidence: probe log).

History (append-only):
- 2026-09-30  FRONTEND AI  BACKLOG -> READY  Filed from VM-CUST-013 D2 with file:line evidence.
