# ADR-005: Paystack Webhook Cryptographic Verification

## Date: 2026-08-12
## Status: Accepted

## Context
When customers pay via bank transfer, USSD, or dynamic payment links, network drops or browser closures can prevent synchronous frontend callback execution. Asynchronous Paystack webhooks are required, but processing unverified webhooks exposes the platform to spoofed payment attacks.

## Decision
1. Expose `POST /paystack/webhook` exempted from CSRF in `VerifyCsrfToken.php`.
2. Enforce strict `hash_hmac('sha512', $payload, $secretKey)` validation against the `X-Paystack-Signature` header using `hash_equals()` to prevent timing attacks.
3. Automatically fulfill `PaymentRequest` upon verified `charge.success` events. All V1 delivery orders are **prepaid** — COD/doorstep rider cash collection is decommissioned. The webhook fulfills only prepaid digital payment requests.

## Consequences
- **Positive:** 100% immune to spoofed payment callbacks.
- **Positive:** Unattended background payment reconciliation when buyers pay via USSD/transfer without returning to the web browser.
- **V1 Scope Note:** COD payment fulfillment path (previously handled via this webhook for rider Paystack links) has been removed. All delivery orders must be paid in full before vendor preparation begins.
