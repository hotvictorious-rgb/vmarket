# ADR-004: Standardized State Management & Secure Token Storage

## Date: 2026-08-11
## Status: Accepted

## Context
Inconsistent state management across apps and insecure plaintext `shared_preferences` storage of user and vendor auth tokens created vulnerabilities on rooted/jailbroken devices.

## Decision
1. Standardize `User app` and `Vendor app` on **Provider + GetIt**.
2. Standardize `Delivery Man App` on **GetX**.
3. Migrate all auth token persistence across all 3 apps to **`flutter_secure_storage`**.

## Consequences
- **Positive:** Enterprise-grade AES encryption backed by Android KeyStore and iOS Keychain.
- **Positive:** Clear architectural boundaries with zero state management collisions.
