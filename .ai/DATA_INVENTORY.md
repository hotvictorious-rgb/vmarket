# Victorious MARKET — Personal Data & Privacy Inventory (DATA_INVENTORY.md)

> **CONTROL ZONE FILE — HUMAN OWNERSHIP ONLY**  
> **Status:** Stage 1 Privacy Inventory under Multi-AI Control System Specification (v3) §27.2  
> **Regulatory Reference:** Nigeria Data Protection Act 2023 (NDPA 2023) & PCI-DSS Scope Minimization  
> **Notice:** This document states engineering data controls. Legal confirmation is provided by the human owner.

---

## 1. Personal & Financial Data Asset Inventory

| Data Element | Storage Location | Processing Purpose | Access Control Scoping | Retention Period | Third-Party Recipients |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Customer Names & Phone Numbers** | `users.f_name`, `users.l_name`, `users.phone` | Authentication, order notifications, OTP delivery | Customer owner, assigned rider on active order, Super Admin | 5 years post-account closure (statutory audit) | SMS Gateway Provider (for 6-digit OTP delivery) |
| **Customer Email Addresses** | `users.email` | Account recovery, digital order receipts | Customer owner, Super Admin | 5 years post-account closure | Email Provider (receipt transmission) |
| **Encrypted Password Hashes** | `users.password` | Cryptographic login verification | Backend auth engine only (Bcrypt/Argon2) | Lifetime of account | None (Never exported or logged) |
| **Customer Physical Addresses** | `shipping_addresses` | Doorstep delivery logistics routing | Customer owner, assigned rider during transit | 5 years post-order completion | None (Internal routing) |
| **Payment Card Details** | **NOT STORED ON SYSTEM** | Payment transaction processing | **Zero-Storage Principle**: Tokens handled entirely within Paystack PCI-DSS Level 1 iframe | Instantaneous (Provider session) | **Paystack** |
| **Payment References & Tokens** | `payment_requests.transaction_ref`, `orders.payment_status` | Escrow verification, reconciliation, refunds | Customer owner, merchant seller, Admin finance | 7 years (tax & accounting compliance) | Paystack |
| **In-Shop Release Verification PIN** | `orders.pickup_verification_code` (Hidden in model) | Counter physical custody release | Verified customer owner, counter release controller | Until order marked `delivered` | None |
| **Delivery Handover Verification OTP** | `orders.verification_code` (Hidden in model) | Proof of Delivery (POD) cryptographic completion | Verified customer owner, rider OTP submission endpoint | Until order marked `delivered` | None |
| **Merchant Bank Account Numbers (NUBAN)** | `shops.account_no`, `shops.bank_name` | Direct vendor payout settlement | Merchant owner, Admin finance | 7 years (banking audit compliance) | Paystack Transfer API |
| **Rider Real-Time GPS Coordinates** | `tracking_events` (Ephemeral) | In-transit live order tracking for customer | Customer tracking screen, dispatch portal | 30 days post-order delivery | Google Maps SDK |

---

## 2. Engineering Privacy & Protection Controls (§27.4)

1. **Model Serialization Masking (`Order::$hidden`)**:
   `verification_code` and `pickup_verification_code` are strictly hidden from Eloquent serialization so that API payloads never leak verification PINs to riders or third parties.
2. **Zero Plaintext Sensitive Tokens**:
   API bearer tokens in mobile apps (`User app/`, `Vendor app/`, `Delivery Man App/`) are stored exclusively in hardware-backed `flutter_secure_storage`. Shared preferences storage of tokens is strictly banned.
3. **No Personal Data in Application Logs**:
   Log messages must never output plaintext passwords, full credit card numbers, OTP codes, or full street addresses.
4. **Data Subject Rights (Access & Erasure)**:
   Any request to delete or anonymize personal data must be processed via authorized admin workflows while retaining anonymized transactional totals required by financial tax law.
