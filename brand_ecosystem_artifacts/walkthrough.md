# Security Fix & Invariant Walkthrough

## Overview
This milestone resolves the three P1 issues identified during the read-only audit:
1. **P1-A:** Closed Vendor API payment authority bypass in `updateOrderDetails` and `order_detail_status` (`POST /api/v3/seller/orders/order-detail-info-update` & `PUT /api/v3/seller/orders/order-detail-status/{id}`).
2. **P1-B:** Closed Web Vendor due-payment bypass in `orderDueAmountMarkAsPaid` (`POST /vendor/orders/customer-due-amount-mark-as-paid`).
3. **P1-C:** Canonicalized the Self-Pickup OTP flow across backend database, customer API, Customer App Flutter model/UI, and WhatsApp notifications.
4. **P3:** Replaced `rand(100000, 999999)` with CSPRNG `random_int(100000, 999999)` in `PhoneVerificationController.php`.

---

## Committed Changes

### Commit: `70649aee`
**Branch:** `marketplace-clean-baseline`  
**Message:** `fix(security): close vendor payment and pickup OTP bypasses [AI]`

### Files Committed:
1. `backend/vmarket-web/app/Http/Controllers/RestAPI/v3/seller/OrderController.php`
2. `backend/vmarket-web/app/Http/Controllers/Vendor/Order/OrderController.php`
3. `backend/vmarket-web/app/Http/Controllers/RestAPI/v1/auth/PhoneVerificationController.php`
4. `backend/vmarket-web/app/Http/Controllers/RestAPI/v1/OrderController.php`
5. `backend/vmarket-web/app/Services/WhatsAppOrderService.php`
6. `User app/lib/features/order/domain/models/order_model.dart`
7. `User app/lib/features/order_details/widgets/order_payment_info_widget.dart`
8. `backend/vmarket-web/tests/Unit/PaymentFulfillmentBoundarySecurityTest.php`
9. `AI_CHANGELOG.md`

---

## Verification Summary
- **Payment & Handover Security Tests:** 21/21 passed ($\Delta = 0.00$)
- **Marketplace Listing Freshness Tests:** 23/23 passed ($\Delta = 0.00$)
- **Vendor Product Feed Isolation Tests:** 31/31 passed ($\Delta = 0.00$)
- **Total Assertions Passed:** 75/75 passed with 100% zero mathematical drift
- **PHP Syntax Linter (`php -l`):** 0 syntax errors across all modified PHP files
- **Git Working Tree:** Clean (`nothing to commit, working tree clean`)
- **POS Repository (`pos/`):** 100% untouched
