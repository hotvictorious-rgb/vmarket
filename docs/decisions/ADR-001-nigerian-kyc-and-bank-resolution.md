# ADR-001: 100% Free Nigerian KYC & NUBAN Name Cross-Matching

## Date: 2026-08-11
## Status: Accepted

## Context
Vendors operating in the Nigerian market require identity verification to prevent fraud and account impersonation. Paid verification APIs (Doja, Prembly) introduce a recurring ₦100/verification cost.

## Decision
Utilize the free Paystack NUBAN bank resolution endpoint to fetch the official account holder name and compare it algorithmically against both the vendor's personal name and corporate shop name (with corporate suffix normalization). Allow optional NIN and CAC document uploads for admin review.

## Consequences
- **Positive:** Zero ongoing operational costs for merchant verification.
- **Positive:** Instant real-time account holder validation on the mobile app.
- **Positive:** Clear audit trail in the Admin Panel with 1-click badge assignment.
