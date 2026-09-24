# Runbook: Incident Response & Postmortem Protocol

> **CONTROL ZONE RUNBOOK — HUMAN OPERATOR ONLY**

---

## 1. Severity Classifications
- **SEV1 (Critical)**: Payments broken, database corruption, auth bypass, personal data exposure. Response time: $< 15$ minutes.
- **SEV2 (Major)**: Core customer/merchant journey impaired (cart, search, order tracking). Response time: $< 1$ hour.
- **SEV3 (Minor)**: Non-critical feature degradation, reporting delay. Response time: $< 4$ hours.

---

## 2. Response Workflow

1. **Immediate Mitigation (Stop the Bleeding)**:
   - Flip feature flag / kill switch or activate maintenance mode.
   - Execute rollback if related to a recent release candidate.
2. **Investigation & Evidence Preservation**:
   - Collect error logs and network request IDs without wiping server logs.
3. **Draft Postmortem**:
   - Create postmortem at `.ai/incidents/INC-YYYY-MM-DD-NNN.md` using Appendix F template within 24 hours.
4. **Permanent Regression Test**:
   - Every incident fix mandates an automated regression test in `tests/regression/`.
