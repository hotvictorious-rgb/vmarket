# Runbook: Database Backup & Disaster Recovery

> **CONTROL ZONE RUNBOOK — HUMAN OPERATOR ONLY**

---

## 1. Automated & Pre-Migration Snapshots
- Automated daily mysqldump snapshots run at 02:00 UTC and retain for 30 days.
- **Pre-Migration Snapshot**: Before applying any database migration in production, an explicit snapshot must be captured:
  ```bash
  mysqldump -u <DB_USER> -p<DB_PASS> <DB_NAME> --single-transaction --quick > backup_pre_migration_$(date +%Y%m%d_%H%M%S).sql
  ```
- Record the filename and checksum in the release manifest.

---

## 2. Disaster Recovery & Restoration

1. **Verify Integrity of Backup Archive**:
   Verify gzip / SQL dump integrity:
   ```bash
   head -n 20 backup_file.sql
   ```

2. **Restore Database**:
   ```bash
   mysql -u <DB_USER> -p<DB_PASS> <DB_NAME> < backup_file.sql
   ```

3. **Re-synchronize Framework**:
   ```bash
   php artisan optimize:clear
   ```
