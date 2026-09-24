# Runbook: Production Rollback Procedure

> **CONTROL ZONE RUNBOOK — HUMAN OPERATOR ONLY**

---

## 1. Rollback Triggers (Immediate Execution)
- Payment gateway webhook failure rate $> 1\%$.
- System-wide fatal exception rate in Laravel error logs $> 0.5\%$.
- Order checkout abandonment $> 10\%$ vs 7-day average.
- Detection of unauthenticated IDOR or data leakage (SEV1).

---

## 2. Emergency Rollback Protocol

1. **Activate Server Maintenance Mode (if critical)**:
   ```bash
   php artisan down --message="Victorious MARKET is undergoing scheduled maintenance. We will return shortly."
   ```

2. **Revert Application Code**:
   Checkout the previous verified release tag recorded in the release manifest:
   ```bash
   git checkout <PREVIOUS_RELEASE_TAG>
   ```

3. **Database Migration Rollback**:
   - Inspect the rollback safety declaration in the release manifest.
   - If backward-compatible rollback was certified:
     ```bash
     php artisan migrate:rollback --step=<N>
     ```
   - If migration cannot be cleanly rolled back, restore from the verified pre-release backup snapshot per `backup-restore.md`.

4. **Clear Application Cache**:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   ```

5. **Bring System Live & Postmortem**:
   ```bash
   php artisan up
   ```
   Open an incident file in `.ai/incidents/INC-YYYY-MM-DD-NNN.md` within 24 hours.
