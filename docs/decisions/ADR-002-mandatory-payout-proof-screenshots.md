# ADR-002: Mandatory Payment Proof Screenshots for Payouts

## Date: 2026-08-11
## Status: Accepted

## Context
Admins approving vendor and delivery man withdrawal requests previously did so without attaching proof of transfer, creating high risks of reconciliation disputes.

## Decision
Enforce strict backend validation in `VendorController.php` and `DeliverymanWithdrawController.php` requiring a valid screenshot/receipt image whenever an approval status is submitted.

## Consequences
- **Positive:** Zero financial reconciliation disputes between Super Admin and vendors/riders.
- **Positive:** Transparent in-app receipt previews for all payout recipients.
