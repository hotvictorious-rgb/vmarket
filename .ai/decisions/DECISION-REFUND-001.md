# DECISION-REFUND-001

Type:                     BUSINESS (REQUEST — NOT YET APPROVED)
Question: Are V1 refunds manual-only (Approved ≠ Refunded, operator executes with recorded reference) or is automatic Paystack execution kept?
Background: Root `BUSINESS_RULES.md:258` mandates automated Paystack refunds; V1 planning direction says manual (Approved ≠ Refunded). The async Paystack refund machinery exists, is sophisticated (pending/processing/needs-attention/failed/processed states + webhooks), and `PaystackRefundService:595-629` reverses against `commission_earned` (see VM-PAY-002 for the accrual gap it assumes covered).
AI positions:             Reviewer AI — either direction shippable; manual-only matches current ops capacity and the no-unregistered-selling control posture. No position taken on business merit.
Technical evidence: `PaystackRefundService` states machine; root rules line 258; `VM-DOC-ALIGN-001-matrix.md` row 5.
Business implications: Manual = operator workload, full control, no surprise money movement. Automatic = speed, chargeback-adjacent risk, requires the PAY-002 accrual fix first (else reversals hit unsourced balances).
Options:
  A. MANUAL-ONLY: approval records decision; operator executes externally and records reference/proof; automatic execution disabled behind an explicit flag for future rollout.
  B. KEEP AUTOMATIC: current machinery stays authoritative; PAY-002 must land first.
Decision required from human: A or B.
Final decision:           PENDING
Date:                     2026-09-29 (filed)
Authorizes changes to:    (none until approved — then: root `BUSINESS_RULES.md:258` wording + either the admin execution UI or the automatic path)
Expiry:                   N/A
