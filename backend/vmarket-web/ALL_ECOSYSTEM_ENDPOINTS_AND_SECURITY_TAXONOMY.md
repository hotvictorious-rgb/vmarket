# ALL_ECOSYSTEM_ENDPOINTS_AND_SECURITY_TAXONOMY.md
## Victorious MARKET — Official Endpoint Catalogue & Security Taxonomy
**Last audited:** 2026-08-30 07:00 UTC
**Audit method:** `php artisan route:list` (live Laravel kernel, not static analysis)
**Audit result:** 0 duplicate routes | 0 syntax errors | 5 dead controllers removed

## Verified Route Count Summary

| Platform | Guard | Route Count | Source File |
|---|---|---|---|
| REST API v1 — Customer Mobile App | auth:api | 175 | routes/rest_api/v1/api.php |
| REST API v2 — Delivery Man App | auth:api (delivery_man) | 100 | routes/rest_api/v2/api.php |
| REST API v3 — Vendor Seller App | auth:api (seller) | 162 | routes/rest_api/v3/seller.php |
| Admin Panel — Web Dashboard | auth:admin + module:* | 651 | routes/admin/routes.php |
| Vendor Panel — Seller Web Dashboard | auth:seller + module:* | 203 | routes/vendor/routes.php |
| Web Storefront — Customer Web | session/guest | 295 | routes/web/routes.php |
| Infrastructure (debugbar, ignition) | open/internal | 9 | shared.php |
| TOTAL REGISTERED ROUTES | | 1595 | |

Audit date: 2026-08-30. Only unique METHOD+URI combos counted. No duplicates detected.

## Dead Controller Cleanup (2026-08-30)

### DELETED — Confirmed Unreachable

- app/Http/Controllers/Admin/PaymentMethodController.php — Legacy superseded by ThirdParty/PaymentMethodController. No route. 361 lines.
- app/Http/Controllers/Admin/SmsGatewayController.php — No route ever registered. 34 lines.
- app/Http/Controllers/Auth/ConfirmPasswordController.php — Stock Laravel stub. No route. 40 lines.
- app/Http/Controllers/Auth/ResetPasswordController.php — Stock Laravel stub. No route. 30 lines.
- app/Http/Controllers/Vendor/PaymentInformationController.php — No route. Stub view only. 71 lines.

### KEPT — Pending Route Wiring

- app/Http/Controllers/Vendor/Branch/BranchTransferController.php — [AI] New. Inter-branch stock transfer. Needs vendor route.
- app/Http/Controllers/Vendor/POS/CustomerDebtController.php — [AI] New. Debt ledger for POS. Needs vendor/pos route.

## Admin Sub-group Route Counts (651 total)

| Group | Count | Module Gate |
|---|---|---|
| admin/system-setup | 59 | module:system_settings |
| admin/business-settings | 47 | module:system_settings |
| admin/third-party | 42 | module:3rd_party_setup |
| admin/deal | 40 | module:promotion_management |
| admin/products | 37 | module:product_management |
| admin/pages-and-media | 34 | module:pages_and_media |
| admin/blog | 29 | module:pages_and_media |
| admin/customer | 29 | module:customer_management |
| admin/delivery-man | 28 | module:delivery_management |
| admin/vendors | 27 | module:vendor_management |
| admin/report | 26 | module:report |
| admin/orders | 23 | module:order_management |
| admin/delivery | 21 | module:delivery_management |
| admin/seo-settings | 19 | module:system_settings |
| admin/delivery-hubs | 15 | module:delivery_management |
| admin/whatsapp-crm | 11 | module:crm |
| admin/coupon | 9 | module:promotion_management |
| admin/brand | 9 | module:product_management |
| admin/transaction | 9 | module:transaction |
| admin/vat-tax | 9 | module:vat_tax |
| admin/pos-management | 3 | module:pos_management [FIXED] |
| (remaining groups) | ~166 | various |

## Security Fixes Applied (This Audit Session)

| ID | Severity | Description |
|---|---|---|
| VULN-001 | CRITICAL | RestAPI OrderController IDOR scoped to customer_id |
| VULN-002 | CRITICAL | RestAPI v1 OTP brute-force 5-attempt lockout + 15-min expiry |
| VULN-003 | CRITICAL | Vendor OrderController seller_id forced from auth |
| VULN-004 | CRITICAL | ShippingAddressRepository ownership check before mutate |
| VULN-005 | HIGH | DeliveryManController OTP entropy upgraded to random_bytes |
| VULN-006 | HIGH | ForgotPasswordController brute-force lockout |
| VULN-007 | HIGH | PaystackController webhook atomic lock + double-exec guard |
| VULN-008 | HIGH | TransactionReportController PII enumeration stripped |
| VULN-009 | HIGH | Vendor ProductController export cross-seller bleed fix |
| VULN-NEW-001 | CRITICAL | pos-management routes added module:pos_management gate |
| VULN-NEW-004 | MEDIUM | Admin PaymentMethodController live/test key bucket isolation |
| VULN-NEW-005 | MEDIUM | Paystack reference random_bytes(8) entropy |
| VULN-FRONT-001 | HIGH | ThirdParty PaymentMethodController live/test key bucket isolation |
| VULN-FRONT-002 | HIGH | UpdateStatus() key_name whitelist + settings_type scoping |
| VULN-FRONT-003 | MEDIUM | Meta-fields stripped from live_values before store |
| VULN-FRONT-004 | MEDIUM | Secret keys masked as type=password with Sensitive badge |

## 10 Mandatory Standalone Security Test Suites

The following 10 standalone PHPUnit/Laravel security test suites are maintained in `tests/Security/` and must be executed by all AIs:

1. `tests/Security/Suite01_SuperAdminAccessTest.php` — Super Admin universal access & MRR/POS SaaS privilege verification
2. `tests/Security/Suite02_AdminEmployeeModuleGateTest.php` — Admin Employee `module:*` middleware gate enforcement
3. `tests/Security/Suite03_VerifiedMerchantIsolationTest.php` — Verified Merchant zero cross-tenant bleed on products/orders/shops
4. `tests/Security/Suite04_UnverifiedMerchantBlockTest.php` — Unverified Merchant (status=0) marketplace and withdrawal blockage
5. `tests/Security/Suite05_DeliveryManIsolationTest.php` — Active vs Inactive Delivery Rider order/wallet micro-isolation
6. `tests/Security/Suite06_CustomerIDORTest.php` — Customer IDOR protection on orders, shipping addresses, and account deletion
7. `tests/Security/Suite07_PaymentGatewaySecurityTest.php` — Payment Gateway row locks, Paystack entropy, and `UpdateStatus()` whitelist
8. `tests/Security/Suite08_OTPBruteForceTest.php` — 6-digit OTP format, 5-attempt lockout, 15-minute expiration, exact matching
9. `tests/Security/Suite09_AntiMassAssignmentAndInputValidationTest.php` — Mass assignment protection and pessimistic concurrency locks
10. `tests/Security/Suite10_NineRoleCrossAccessMatrixTest.php` — Complete 9-role cross-actor boundary and mutual exclusion matrix

## Technical Debt

- BranchTransferController: wire to vendor routes for inter-branch stock transfer
- CustomerDebtController: wire to vendor/pos routes for debt ledger
- POS management sidebar nav: add link to /admin/pos-management/dashboard
- Paystack keys: move PAYSTACK_SECRET_KEY to .env (constructor already has env() fallback)

