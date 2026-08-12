# 🗄️ Vmarket Schema Overview

## 1. Migrations Directory
All migrations reside in `backend/Admin and web new install V16.1/database/migrations`.

## 2. Specialized Columns
- `sellers.bank_updated_at`: Controls the 48-hour security cooldown.
- `sellers.kyc_status`: Tracks verification state (`unverified`, `submitted`, `verified`, `rejected`).
- `withdraw_requests.proof_of_payment`: Stores relative path to payment transfer receipt.
- `orders.pickup_verification_code`: Stores secret 4-digit pickup OTP.
