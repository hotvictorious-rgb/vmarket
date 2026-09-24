# Runbook: Staging & Production Deployment

> **CONTROL ZONE RUNBOOK — HUMAN OPERATOR ONLY**

---

## 1. Prerequisites
- Target release tag must exist on `main` (e.g. `RELEASE-2026-09-24-001`).
- Release manifest generated and signed by human operator in `.ai/releases/`.
- Verified pre-deployment database backup created and verified per `backup-restore.md`.
- Staging smoke test passed.

---

## 2. Deployment Procedure (cPanel Safe Overlay)

1. **SSH / Terminal Connection to Web Root**:
   Navigate to the web root (`public_html` or designated subdomain directory).

2. **Overlay Codebase**:
   Pull only `backend/vmarket-web/` files onto the web server root.
   ```bash
   git pull origin main
   ```
   **CRITICAL NON-DESTRUCTIVE MANDATE**:
   NEVER delete `.env`, `storage/`, `vendor/`, or `public/assets/`.

3. **Database Migrations (if applicable)**:
   ```bash
   php artisan migrate --force
   ```

4. **Framework Cache Clearing & Optimization**:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. **Start Post-Release Watch Window**:
   Initiate 48-hour watch window. Monitor payment conversion, order queues, and error logs.
