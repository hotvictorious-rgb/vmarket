# Release Brief: RELEASE-2026-09-25-001

Release ID:                 RELEASE-2026-09-25-001
What changed (plain language):
Storefront cart no longer shows obsolete shipping-method dropdowns or coupon/referral inputs. The order summary shows Item Total, Product Discounts, Sub Total, Estimated Tax, and Estimated Victorious Points cashback, with delivery fees resolved downstream at checkout. The restored Proceed-to-Checkout button preserves the address-to-payment flow and the cart order-note save.

Who is affected (customers / vendors / operations):
- Customers: Clean cart, working quantity/selection AJAX, unbroken path to payment.
- Vendors: No change.
- Operations: No change.

Top 3 risks:
1. Address-to-payment transition regressing: Mitigated — restored button id verified against both consumer scripts + AI-5 exact-markup inspection across 3 review cycles.
2. Stale coupon session suppressing free-delivery indicator: Mitigated — guards decoupled from coupon session keys.
3. Dead JS retained (3 files): No behavior risk (no emitted selectors); tracked removal in VM-CUST-010.

Evidence summary:
- Hard Release Gate: 17/18. Sole open item is evidence-filename freshness for the exact SHA (check 3); backend code identical to the 7/7 PASS tree, blades reviewed across 3 cycles, fresh customer 6/6 PASS recorded 2026-09-25.
- Review Decisions: AI-5 APPROVED (cycle-3 names release commit 8a61392a).
- Test Result File: `.ai/status/results/VM-CUST-003/a10a6760d0f899168160e15d6f602b51bed8c66f.json` (7/7 PASS) + 6/6 Flutter customer suite.
- Supply-chain results: Zero secret alerts, static analysis clean.

Known limitations and unknowns:
- JS hygiene remainder (VM-CUST-010), translation-key seeding follow-up. Both non-blocking.

Migrations and rollback safety:
- Zero migrations. Rollback = revert merge 151d6308.

Client/version impact:
- Storefront Web only. No mobile API change.

Watch window and rollback triggers:
- 48 hours; rollback on cart/checkout error rate > 1.0%.

Recommendation from AI 8:
RECOMMEND APPROVAL AND MERGE under owner §30 sign-off with the documented check-3 equivalence case. No AI bypass performed; merge executed only on explicit human MERGE IT.

Human decision / date:
APPROVED by Human Operator (2026-09-25, MERGE IT)
