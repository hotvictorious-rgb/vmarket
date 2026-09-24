# Runbook: Secret & Credential Rotation Protocol

> **CONTROL ZONE RUNBOOK — HUMAN OPERATOR ONLY**

---

## 1. Trigger Scenarios
- Accidental secret commit to git.
- Compromised staff account or leaked `.env`.
- Scheduled quarterly rotation of payment keys.

---

## 2. Rotation Execution Steps

1. **Payment Gateway Keys (Paystack, Flutterwave, Stripe)**:
   - Generate new API keys and webhook secret tokens in provider merchant dashboard.
   - Update `.env` on production web root.
   - Run `php artisan config:cache`.
   - Send sandbox test webhook to verify cryptographic signature.
   - Revoke old keys in provider dashboard.

2. **Database Credentials**:
   - Create new database user and grant privileges in cPanel MySQL.
   - Update `DB_USERNAME` and `DB_PASSWORD` in `.env`.
   - Run `php artisan config:cache`.
   - Drop old database user once connections have transitioned.

3. **Application Key (`APP_KEY`)**:
   - `php artisan key:generate`.
   - Note: rotating `APP_KEY` will invalidate existing user sessions and encrypted cookies.
