# 🛡️ Nigerian Fintech & KYC Subsystem

## 1. Overview
The Nigerian Fintech & KYC module provides zero-cost bank verification and anti-theft security tailored for Nigerian commerce.

## 2. Key Components
1. **Paystack NUBAN Bank Resolution:** `PaystackBankService.php` connects to the Paystack API to verify account numbers against commercial and digital banks in Nigeria.
2. **Dual-Target Name Cross-Matching:** `NigerianKycService.php` checks verified account holder names against both personal and corporate shop names using Levenshtein distance.
3. **48-Hour Cooldown & Email OTP:** Prevents immediate withdrawals following bank details changes.
4. **Admin Approval Hub:** `admin-views/vendor/view.blade.php` displays match scores and allows 1-click verification.
