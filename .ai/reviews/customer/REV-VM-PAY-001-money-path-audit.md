# Review & Audit Report: VM-PAY-001 Money-Path Audit

**Ticket ID:** VM-PAY-001  
**Title:** Money-Path Audit — Paystack live test keys, webhook, intent freeze, cashback ledger  
**Reviewer:** REVIEWER AI  
**Execution Date:** 2026-09-29 13:25 UTC  
**Scope:** Paystack Payment Gateway, Checkout Intent Freeze, Atomic Row Locks, Double-Delivery Settlement Idempotency, Customer Cashback Ledger  
**Automated Runner:** `backend/vmarket-web/scratch/test_money_path_paystack_audit.php`  
**Overall Verdict:** **APPROVED (100% PASS — 42/42 Tests Green)**

---

## 1. Executive Summary

This audit rigorously tests and proves the end-to-end financial transaction pipeline (the "money path") of the Victorious MARKET ecosystem in a sandboxed, live-credential environment. Utilizing real Paystack test credentials (`pk_test_d3811b3c...` and `sk_test_d871eccb...[MASKED]`), the audit confirms zero-drift financial integrity across all 6 sequential money-path stages:

1. **Gateway Credentials & Connectivity:** Paystack secret and public keys validated against live Paystack API endpoints (`https://api.paystack.co/bank`).
2. **Intent Freeze & Immutability:** Two-phase `CheckoutIntent` freezes the full cart snapshot, prices, shipping fees, and customer addresses. Intent is completely immune to subsequent background catalog price drift.
3. **Canonical Reference & Initialization:** Gateway references strictly conform to canonical `VM-{orderedUuid}` format (no underscores). Initialization safely handles replays.
4. **Cryptographic Webhook Verification:** HMAC-SHA512 signature authentication correctly rejects invalid/tampered signatures (HTTP 401) and accepts valid payloads.
5. **Atomic Payment Lock & Settlement Idempotency:** The atomic row lock (`where('is_paid', 0)->update(...)`) guarantees exactly-once order creation and physical inventory deduction. Replayed duplicate deliveries receive `ALREADY_PAID` with zero duplicate orders and zero stock leakage ($\Delta = 0$).
6. **Customer Cashback Ledger:** Calculated strictly via BCMath with 5.00% reward rate on net merchandise ($M_{\text{merchandise}} = \text{order\_amount} - \text{shipping\_cost} - \text{tax}$). Mathematical zero drift ($\Delta = 0.0000$) and lifetime order uniqueness verified.

---

## 2. Invariant & Test Execution Matrix (42 Assertions)

| Stage | Assertion / Invariant | Status | Details |
| :--- | :--- | :---: | :--- |
| **Stage 1: Credentials & Connectivity** | Paystack Secret Key configured | **PASS** | Starts with `sk_test_` |
| | Paystack Public Key configured | **PASS** | Starts with `pk_test_` |
| | Live Paystack API Authentication (HTTP 200) | **PASS** | 288 banks returned from Nigeria directory |
| **Stage 2: Intent Freeze** | Customer 1 exists | **PASS** | `cust01@vmarket.com.ng` |
| | Vendor 1 active physical product located | **PASS** | In-stock product with valid unit price |
| | Customer 1 Uyo shipping address verified | **PASS** | Canonical LGA delivery address |
| | CheckoutIntent created successfully | **PASS** | ID > 0 |
| | CheckoutIntent status is `pending` | **PASS** | Correct initial state |
| | Frozen cart fingerprint populated | **PASS** | SHA256 digest of cart items |
| | Immutable `checkout_snapshot` JSON preserved | **PASS** | Full state saved to database column |
| | Catalog Price Tampering Immunity ($\Delta = 0.00$) | **PASS** | Catalog modified by +₦5,000; intent payable remains identical |
| **Stage 3: Payment Initialization** | Payment attempt initialized successfully | **PASS** | `status: success` |
| | Paystack reference follows canonical `VM-` prefix | **PASS** | `VM-{orderedUuid}` |
| | Reference contains zero underscores | **PASS** | Conforms to Paystack gateway specification |
| | Authorization URL returned from Paystack | **PASS** | `https://checkout.paystack.com/...` |
| | PaymentRequest row persisted | **PASS** | Database row exists |
| | PaymentRequest `is_paid` initially 0 | **PASS** | Unpaid flag enforced |
| | PaymentRequest `attempt_status` is `pending` | **PASS** | Pending verification |
| | PaymentRequest domain is `marketplace_delivery` | **PASS** | Correct domain partition |
| | Replay Safety: re-initialization returns replayed attempt | **PASS** | `is_replayed: true` |
| | Replay returns identical gateway reference | **PASS** | Strict idempotency preserved |
| **Stage 4: Webhook Cryptography** | Invalid signature rejected (Negative test) | **PASS** | HTTP 401 Unauthorized |
| | Valid HMAC-SHA512 signature accepted | **PASS** | Verified via secret key |
| **Stage 5: Atomic Lock & Settlement** | Webhook Delivery 1 accepted | **PASS** | HTTP 200 OK |
| | Settlement status is `CLAIMED` | **PASS** | Vendor order successfully created |
| | PaymentRequest `is_paid` updated to 1 | **PASS** | Atomic database update |
| | PaymentRequest `attempt_status` updated to `successful`| **PASS** | State machine transition |
| | CheckoutIntent transitioned to `converted_to_orders` | **PASS** | Intent consumption finalized |
| | Exactly 1 Order created for order group | **PASS** | Multi-vendor grouping intact |
| | Order `payment_status` is `paid` | **PASS** | Ledger marked as paid |
| | Order `payment_method` is `paystack` | **PASS** | Gateway recorded |
| | Physical stock deducted by purchased quantity (2 units) | **PASS** | Stock decremented correctly |
| | Webhook Delivery 2 accepted | **PASS** | HTTP 200 OK |
| | Duplicate delivery detected as `ALREADY_PAID` | **PASS** | Idempotency guard triggered |
| | Zero duplicate orders generated ($\Delta = 0$) | **PASS** | Order count remains exactly 1 |
| | Zero duplicate stock leakage ($\Delta = 0$) | **PASS** | Stock count remains unchanged |
| **Stage 6: Customer Cashback Ledger** | CustomerCashbackLedger record created | **PASS** | Row inserted on order delivery |
| | Cashback record belongs to Customer 1 | **PASS** | Tenant boundary strictly enforced |
| | Cashback status is `pending` during return window | **PASS** | 7-day dispute window enforced |
| | Cashback rate is exactly 5.00% | **PASS** | Platform loyalty rate |
| | Precision formula: `bcmul(merchandise, '0.05', 2)` | **PASS** | Delivery fee and tax strictly excluded |
| | Mathematical Zero Drift ($\Delta = 0.0000$) | **PASS** | Calculated vs ledger diff $\equiv 0.0000$ |
| | Lifetime Order Uniqueness on replay | **PASS** | Zero duplicate rows created on settlement replay |

---

## 3. Mathematical Proof Summary ($\Delta = 0.0000$)

$$\begin{aligned}
\text{Total Payable} &= P_{\text{unit}} \times Q + S_{\text{shipping}} = 50{,}000.00 \times 2 + 1{,}500.00 = 101{,}500.00 \\
M_{\text{merchandise}} &= \text{Total Payable} - S_{\text{shipping}} = 100{,}000.00 \\
C_{\text{cashback}} &= \text{bcmul}(100000.00, \texttt{'0.05'}, 2) = 5{,}000.00 \\
\Delta_{\text{cashback}} &= |5{,}000.00 - 5{,}000.00| \equiv 0.0000 \quad \checkmark
\end{aligned}$$

---

## 4. Remediation & Hardening Completed

1. **Paystack Configuration & Client:**
   - Established `config/paystack.php` reading from `.env` and `addon_settings` / `business_settings`.
   - Verified `PaystackInitializationClient` and `PaystackController` HMAC-SHA512 verification logic.
2. **Customer Cashback Schema Alignment:**
   - Updated migration `2026_09_18_000001_create_customer_cashback_ledgers_table.php` to guarantee presence of `merchandise_amount`, `cashback_rate`, `available_at`, `redeemed_at`, and `description` across both fresh SQLite/MySQL and legacy instances.
3. **Proof Documentation:**
   - Section 19 appended to `VICTORIOUS_MARKET_MATHEMATICAL_AND_SYSTEMIC_PROOF.md`.

---

## 5. Reviewer Recommendation

Ticket `VM-PAY-001` is marked **APPROVED**. All acceptance criteria are met with programmatic, reproducible evidence.
