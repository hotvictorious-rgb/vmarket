# ADR-003: Secret Pickup OTP for Logistics Handoff

## Date: 2026-08-11
## Status: Accepted

## Context
Delivery riders could advance orders to `out_for_delivery` remotely without physically verifying possession of goods at the vendor's storefront.

## Decision
Generate a 4-digit Pickup OTP for the vendor upon order processing. Require the rider to physically input this OTP into the Delivery Man App to verify possession before changing order status to `out_for_delivery`.

## Consequences
- **Positive:** Guaranteed physical handoff tracking.
- **Positive:** Complete elimination of lost inventory during dispatch transitions.
