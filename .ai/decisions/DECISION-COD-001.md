# DECISION-COD-001

Type:                     BUSINESS (REQUEST — NOT YET APPROVED)
Question: Is Cash-on-Delivery a supported V1 path (gated) or prohibited?
Background: Newer policy docs (backend spec COD prohibition, delivery spec §24, INBOX_DELIVERY, LOGISTICS_POLICY rewrite) say prohibited/prepaid-only. The codebase instead hardens COD branches (Sept-19 idempotency directive, `cash_in_hand` live in rider controller + wallet model + v3 seller controller, refund COD legs). `.ai/BUSINESS_RULES.md` §3 still names COD in lifecycle states. Both directions are internally coherent; the repo currently implements the gated variant.
AI positions:             Reviewer AI — either direction shippable; gated variant is what runs today (zero migration); prohibition requires code deletion tickets + refund-leg rework. No position taken on business merit.
Technical evidence: `DeliveryManController` + `DeliverymanWallet` + v3 `OrderController` COD paths (live); `place_order()` COD decommissioned (403); `VM-DOC-ALIGN-001-matrix.md` row 1.
Business implications: Prohibition = simpler ledger, harder rural onboarding; gated support = wider reach, permanent dual-path maintenance + test surface.
Options:
  A. PROHIBIT: COD forbidden in V1; pickup≠COD locked; follow-up tickets delete COD branches, refund legs, wallet cash fields.
  B. GATED SUPPORT: COD stays as implemented (idempotent, guarded); docs updated to describe it; pickup≠COD locked.
Decision required from human: A or B.
Final decision:           PENDING
Date:                     2026-09-29 (filed)
Authorizes changes to:    (none until approved — then: `.ai/BUSINESS_RULES.md` §3/§6, backend COD branches or their deletion tickets, refund service legs, rider wallet docs)
Expiry:                   N/A
