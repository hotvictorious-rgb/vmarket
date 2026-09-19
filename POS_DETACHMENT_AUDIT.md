# Victorious MARKET V1: Comprehensive POS Detachment Audit
**Document Version:** 1.0.0  
**Audit Date:** 2026-09-19  
**Status:** Certified Read-Only Audit  
**Author:** Antigravity (Advanced Agentic Assistant)

---

## Executive Summary & Architectural Prime Directive

Victorious MARKET (Vmarket) is transitioning permanently to **Marketplace-Only Architecture**. All Point-of-Sale (POS), retail counter checkout, in-store cashier drawer accounting, inter-branch transfer waybills, cashier blind shifts, in-store customer debt ledgers, and POS SaaS subscription features are officially decommissioned.

### Core Strategic Mandates:
1. **Marketplace-Only V1 Invariant:** There are no active POS workflows, no POS controllers, no POS routes, no POS Flutter modules, no POS wallet branches, and no `order_type !== 'POS'` heuristic exceptions in active business logic.
2. **Historical Data Preservation:** Eliminating active POS functionality does **NOT** mean destructive wiping of historical database records. Historical financial records (`orders.order_type = 'POS'`, historical transaction ledgers) are preserved for statutory tax, accounting, and compliance audits, but rendered strictly immutable and read-only.
3. **Clean Architectural Detachment:** POS detachment is strictly decoupled from WhatsApp detachment and AI detachment. Each detachment is audited and executed independently with zero collateral impact on core marketplace operations.

---

## 1. Complete POS Inventory

| Category | Component / Asset | File / Database Location | Classification | Operational Function |
| :--- | :--- | :--- | :---: | :--- |
| **Backend Service** | `OrderService::getPOSOrderData()` | `app/Services/OrderService.php:15-44` | **A** | Legacy POS order builder (0 callers) |
| **Backend Trait** | `VendorPOSManagement` | `app/Traits/API/v3/VendorPOSManagement.php` | **A** | Legacy POS cart item calculation trait (0 callers) |
| **Backend Filter** | `OrderRepository` POS query filters | `app/Repositories/OrderRepository.php:93, 195` | **B** | Legacy POS query filters in order repository |
| **Backend Filter** | `ProductRepository` search_from 'pos' | `app/Repositories/ProductRepository.php:237` | **B** | Product search filter for POS cashier search |
| **Backend RBAC** | `VendorRoleController::MODULE_PERMISSIONS` | `app/Http/Controllers/Vendor/Employee/VendorRoleController.php:18` | **B** | `'pos_management' => 'In-Store POS System'` role option |
| **Backend RBAC** | `GlobalConstant::EMPLOYEE_ROLE_MODULE_PERMISSION` | `app/Enums/GlobalConstant.php:1295` | **B** | `'pos_management' => 'pos_management'` module permission |
| **Backend View Routing** | `Admin/Order/OrderController::details` | `app/Http/Controllers/Admin/Order/OrderController.php:548-550` | **B** | Dead call to missing view `admin-views.pos.order.order-details` |
| **Backend View Routing** | `Vendor/Order/OrderController::details` | `app/Http/Controllers/Vendor/Order/OrderController.php:549-551` | **B** | Dead call to missing view `vendor-views.pos.order.order-details` |
| **Backend Order Count** | `Admin/Vendor/VendorController::order_list` | `app/Http/Controllers/Admin/Vendor/VendorController.php:292` | **B** | POS order count split branch |
| **Backend Profile Guard**| `UserProfileController::trackOrder` | `app/Http/Controllers/Web/UserProfileController.php:867-868` | **B** | Warning toast when customer attempts tracking POS order |
| **Backend Search Menu** | `AdminMenuWithRoutesTrait` | `app/Packages/AdvanceSearch/Traits/AdminMenuWithRoutesTrait.php:39-48` | **B** | Admin search index entry for dead route `admin/pos` |
| **Backend Email Enum** | `EmailTemplateKey::REGISTRATION_FROM_POS` | `app/Enums/EmailTemplateKey.php:22` | **B** | Email template key for in-store customer creation |
| **Backend Customer API**| `Admin & Vendor CustomerController::add` | `app/Http/Controllers/Admin/Customer/CustomerController.php:388`, `Vendor/CustomerController.php:64` | **B** | Quick-add customer email referencing `registration-from-pos` |
| **Backend Config** | `ConfigController::configuration` | `app/Http/Controllers/RestAPI/v1/ConfigController.php:135` | **B** | Returns `'pos_active' => (string)getWebConfig('seller_pos')` |
| **Backend Settings** | `VendorSettingsController::seller_pos` | `app/Http/Controllers/Admin/Settings/VendorSettingsController.php:44` | **B** | Admin toggle endpoint for `seller_pos` |
| **Backend Settings** | `VendorController::updateSetting` | `app/Http/Controllers/Admin/Vendor/VendorController.php:445-446` | **B** | Admin per-seller toggle for `pos_status` |
| **Backend CORS** | `config/cors.php` | `config/cors.php:25` | **A** | CORS allowed origin `'https://pos.victoriousmarket.com.ng'` |
| **Database Table** | `pos_customer_ledgers` | Migration `2026_08_26_000001` | **D** | Physical customer credit and aging debt book |
| **Database Table** | `pos_debt_transactions` | Migration `2026_08_26_000001` | **D** | Debt repayment and installment transaction log |
| **Database Table** | `pos_cashier_shifts` | Migration `2026_08_26_000001` | **D** | Physical cashier shift opening/closing & float cash |
| **Database Table** | `pos_transfers` | Migration `2026_08_26_000001` | **D** | Physical inter-branch waybills and driver transport |
| **Database Table** | `pos_transfer_items` | Migration `2026_08_26_000001` | **D** | Line items dispatched across physical retail branches |
| **Database Table** | `pos_subscriptions` | Migration `2026_08_26_000001` | **D** | Subscriptions for multi-branch POS SaaS |
| **Database Column** | `sellers.pos_status` | Migration `2022_02_01_214654` | **D** | Legacy flag indicating if seller had POS enabled |
| **Database Column** | `sellers.marketplace_status` | Migration `2026_08_26_000001` | **B** | Defaulted to `'pos_only'`; must default to marketplace |
| **Database Column** | `shops.is_primary_branch`, `branch_code` | Migration `2026_08_26_000001` | **C/D**| Branch segregation metadata |
| **Database Column** | `orders.order_type` | Schema baseline | **C/D**| Historical identifier: `'POS'`, `'default_type'`, `'pickup'` |
| **Database Settings** | `pos_*` settings in `business_settings` | `business_settings` table | **D** | 6 global business settings keys (`pos_free_branch_limit`, etc.) |
| **Database Settings** | `seller_pos` in `business_settings` | `business_settings` table | **D** | Global toggle for POS feature |
| **Blade View** | `admin-views/business-settings/seller-settings.blade.php` | Lines 38-47 | **B** | Admin toggle switch for `seller_pos` with modal images |
| **Blade View** | `admin-views/vendor/view/setting.blade.php` | Lines 173, 182-187 | **B** | Per-vendor POS toggle switch |
| **Blade View** | `vendor-views/subscription/index.blade.php` | Lines 9, 15, 64 | **B** | POS SaaS subscription card & waybill marketing text |
| **Blade View** | `admin-views/order/partials/_filter-offcanvas.blade.php` | Line 96 | **B** | Checkbox for filtering POS orders |
| **Blade View** | `vendor-views/order/partials/_filter-offcanvas.blade.php` | Lines 74-75 | **B** | Checkbox for filtering POS orders |
| **Blade View** | `admin-views/order/list.blade.php` | Line 239 | **B** | Badge `{!! $order->order_type == 'POS' ? '(POS)' : '' !!}` |
| **Blade View** | `vendor-views/order/list.blade.php` | Line 236 | **B** | Badge `{!! $order->order_type == 'POS' ? '(POS)' : '' !!}` |
| **Blade View** | `resources/themes/theme_aster/theme-views/order/digital-product-download.blade.php` | Line 11 | **B** | References missing route `digital-product-download-pos.index` |
| **Blade View** | `resources/views/email-templates/digital-product-download.blade.php` | Lines 263-264 | **B** | References missing route `digital-product-download-pos.index` |
| **Frontend Asset** | `public/assets/back-end/js/admin/pos-script.js` | Full file (740 lines) | **A** | Orphaned JavaScript cashier cart script |
| **Frontend Asset** | `public/assets/back-end/js/vendor/pos-script.js` | Full file (740 lines) | **A** | Orphaned JavaScript cashier cart script |
| **Frontend Asset** | `public/assets/back-end/img/pos.png` | Image file | **A** | POS navigation / card icon |
| **Frontend Asset** | `public/assets/new/back-end/img/modal/pos-seller-on.png` | Image file | **A** | Modal toggle image |
| **Frontend Asset** | `public/assets/new/back-end/img/modal/pos-seller-off.png` | Image file | **A** | Modal toggle image |
| **Flutter Vendor** | `Vendor app/lib/utill/images.dart` | Line 94 | **A** | `static const String pos = 'assets/images/pos_icon.png';` |
| **Flutter Vendor** | `Vendor app/assets/images/pos_icon.png` | Image file | **A** | POS image asset |
| **Flutter Vendor** | `order_list_filter_model.dart` | Line 76 | **B** | `if (onlyPosOrder == 1) orderTypes.add('POS');` |
| **Flutter Vendor** | `order_details_screen.dart` | Lines 126, 134, 268, 271, 430, 474, 701 | **B** | POS conditional UI branches |
| **Flutter Vendor** | `order_payment_info_widget.dart` | Lines 35, 78, 85, 124 | **B** | POS conditional payment layout |
| **Flutter Vendor** | `shipping_and_biilling_widget.dart`| Lines 35, 314 | **B** | POS hiding shipping/billing details |
| **Flutter Vendor** | `order_widget.dart` | Lines 32, 50, 109, 197 | **B** | POS badge `#orderId (POS)` and price conversion |
| **Flutter Vendor** | `refund_pricing_widget.dart` | Line 192 | **B** | POS tax calculation branch |
| **Flutter Vendor** | `order_setup_bottom_sheet.dart` | Line 302 | **B** | POS delivery setup check |
| **Flutter User** | `User app/lib/features/order_details/*` | Multiple files | **B** | Cosmetic `orderType == 'POS'` checks |
| **Flutter Delivery**| Delivery Man App | Whole app | **C** | **Zero POS references**. Completely clean. |

---

## 2. Classification Matrix

* **Classification A (Safe to remove directly):**
  - `app/Services/OrderService.php` (method `getPOSOrderData`)
  - `app/Traits/API/v3/VendorPOSManagement.php`
  - `public/assets/back-end/js/admin/pos-script.js`
  - `public/assets/back-end/js/vendor/pos-script.js`
  - `public/assets/back-end/img/pos.png`
  - `public/assets/new/back-end/img/modal/pos-seller-on.png`
  - `public/assets/new/back-end/img/modal/pos-seller-off.png`
  - `Vendor app/assets/images/pos_icon.png`
  - `config/cors.php` origin `https://pos.victoriousmarket.com.ng`

* **Classification B (Shared code requiring extraction / refactor):**
  - `app/Repositories/OrderRepository.php`: remove POS filter closures (`where('order_type', 'POS')`).
  - `app/Repositories/ProductRepository.php`: remove `search_from == 'pos'` closure.
  - `app/Http/Controllers/Admin/Order/OrderController.php:548-550`: remove dead redirect to non-existent POS view; all orders render standard order-details.
  - `app/Http/Controllers/Vendor/Order/OrderController.php:549-551`: remove dead redirect to non-existent POS view.
  - `app/Http/Controllers/Admin/Vendor/VendorController.php:292`: remove POS order count split.
  - `app/Http/Controllers/Web/UserProfileController.php:867-868`: remove POS tracking error branch.
  - `app/Packages/AdvanceSearch/Traits/AdminMenuWithRoutesTrait.php:39-48`: remove dead `admin/pos` menu item from search index.
  - `app/Http/Controllers/Vendor/Employee/VendorRoleController.php:18`: remove `'pos_management'` from employee role permissions.
  - `app/Enums/GlobalConstant.php:1295`: remove `'pos_management'` from `EMPLOYEE_ROLE_MODULE_PERMISSION`.
  - `app/Http/Controllers/RestAPI/v1/ConfigController.php:135`: remove or set `'pos_active' => '0'`.
  - `app/Http/Controllers/Admin/Settings/VendorSettingsController.php:44`: remove POS toggle write.
  - `app/Http/Controllers/Admin/Vendor/VendorController.php:445-446`: remove `pos_status` write.
  - Blade views: clean order list filters, invoice POS checks, and settings toggles.
  - Flutter apps: refactor UI widgets so they don't branch on POS.

* **Classification C (Shared Marketplace Core to Protect):**
  - `orders` table and `Order` model.
  - `order_details` table and `OrderDetail` model.
  - `products`, `product_stocks`, `current_stock` management.
  - `Seller` and `SellerWallet` models.
  - `Admin` and `AdminWallet` models.
  - Digital payment pipelines (`Paystack`, etc.).
  - Marketplace OTP fulfillment (`Vendor Pickup Code`, `Customer Delivery Code`, `Customer Handover OTP`).
  - Delivery Man logistics system.

* **Classification D (Historical / Deprecated Database Artifact):**
  - Tables: `pos_customer_ledgers`, `pos_debt_transactions`, `pos_cashier_shifts`, `pos_transfers`, `pos_transfer_items`, `pos_subscriptions`.
  - Columns: `orders.order_type` (preserves historical `'POS'` records).
  - Business settings: historical keys in `business_settings`.

* **Classification E (Requires Manual Architecture Review):**
  - `sellers.marketplace_status`: originally added to gate vendors between `pos_only` and `approved`. In marketplace-only V1, all active approved sellers are marketplace merchants. A migration will ensure all existing active sellers have `marketplace_status = 'approved'`.

---

## 3. Shared Core Protection Analysis

To ensure zero collateral damage to marketplace features during POS removal:

| Shared Asset | POS Touchpoint | Marketplace Protection Invariant |
| :--- | :--- | :--- |
| **Product Inventory (`current_stock`)** | POS historically decremented stock on cashier scan | Marketplace orders decrement inventory upon checkout / payment; POS removal leaves inventory management untouched. |
| **Order Schema (`orders.order_type`)** | POS used `order_type = 'POS'` | Marketplace uses `order_type = 'default_type'` (delivery) and `order_type = 'pickup'` (customer collection). Column remains intact; historical POS rows preserved. |
| **Vendor Balance (`SellerWallet`)** | POS orders could touch vendor wallet | Only `VendorSettlementService::executeManualSettlement()` can disburse vendor earnings, strictly for verified marketplace orders. |
| **Platform Escrow (`AdminWallet`)** | POS orders did not hold pending balance | Unsettled 3P marketplace orders hold vendor entitlement in `AdminWallet.pending_amount`. Pre-settlement refunds release this liability with $\Delta = 0.00$. |
| **Coupon Customer Selection** | Previously used POS customer model | Already decoupled in `AI_CHANGELOG.md` (2026-09-12); marketplace coupons search `Customer` model directly. |

---

## 4. Backend POS Components

### 4.1 Dead Legacy Classes & Traits
- `app/Services/OrderService.php::getPOSOrderData()`:
  - Lines 15-44 contain a helper returning order array with `'order_type' => 'POS'`, `'payment_status' => 'paid'`, `'order_status' => 'delivered'`.
  - Ripgrep search confirms **zero active callers**.
  - Action: Remove method.
- `app/Traits/API/v3/VendorPOSManagement.php`:
  - Contains `getOrderDetailsAddData()` for cashier cart price resolution.
  - Ripgrep search confirms **zero active callers**.
  - Action: Remove file.

### 4.2 Repositories & Controllers with POS Logic Branches
- `app/Repositories/OrderRepository.php`:
  - Lines 93-95 & 195-197 contain `->when($filters['filter'] == 'POS', ...)`.
  - Action: Remove POS filter closure.
- `app/Repositories/ProductRepository.php`:
  - Lines 237-239 contain `->when(isset($filters['search_from']) && $filters['search_from'] == 'pos', ...)`.
  - Action: Remove POS search filter.
- `app/Http/Controllers/Admin/Order/OrderController.php`:
  - Lines 548-550 branch on `$order['order_type'] != 'default_type'` to render `admin-views.pos.order.order-details`. That view does not exist.
  - Action: Unify details rendering to standard `admin-views.order.order-details`.
- `app/Http/Controllers/Vendor/Order/OrderController.php`:
  - Lines 549-551 branch on `$order['order_type'] != 'default_type'` to render `vendor-views.pos.order.order-details`. That view does not exist.
  - Action: Unify details rendering to standard `vendor-views.order.order-details`.
- `app/Http/Controllers/Admin/Vendor/VendorController.php`:
  - Line 292 splits order count by `order_type => 'POS'`.
  - Action: Clean filter count to use standard marketplace query.

---

## 5. Frontend & Blade POS Components

### 5.1 Blade Views with POS Elements
1. **Admin Business Settings (`seller-settings.blade.php`):**
   - Lines 38-47 contain switcher for `seller_pos` with modal asset links `pos-seller-on.png` / `pos-seller-off.png`.
   - Action: Remove the POS toggle section from vendor settings.
2. **Admin Vendor Detail Settings (`admin-views/vendor/view/setting.blade.php`):**
   - Lines 173, 182-187 contain switcher for `seller-pos` / `pos_status`.
   - Action: Remove POS toggle switcher from vendor profile.
3. **Vendor Subscription Index (`vendor-views/subscription/index.blade.php`):**
   - Lines 9, 15, 64 display "Unlimited Multi-Branch POS & Waybills".
   - Action: Remove POS and waybill references; retain marketplace seller subscription cards.
4. **Order List Filters (`admin-views/order/partials/_filter-offcanvas.blade.php` & `vendor-views`):**
   - Checkboxes with `name="order_types[]" value="pos"`.
   - Action: Remove POS checkbox from order filter drawer.
5. **Order Invoices (`admin-views/order/invoice.blade.php`, `vendor-views`, and `theme_aster`):**
   - Lines checking `@if ($order->order_type == 'POS' || $order->order_type == 'pos')`.
   - Action: Retain read-only fallback display for historical invoices, ensuring older records can still render customer name if billing address is null.
6. **Digital Product Download Forms:**
   - Aster theme & email templates referencing `digital-product-download-pos.index`.
   - Action: Point to standard customer order digital product download route `digital-product-download.index`.

### 5.2 Standalone Frontend Scripts
- `public/assets/back-end/js/admin/pos-script.js` (740 lines)
- `public/assets/back-end/js/vendor/pos-script.js` (740 lines)
- Action: Unreferenced in any blade view. Mark for deletion in backend repository.

---

## 6. Flutter Mobile Applications

### 6.1 Vendor App
- **Images:** `lib/utill/images.dart:94` defines `static const String pos = 'assets/images/pos_icon.png';`. Remove constant and asset file.
- **Order Filters:** `lib/features/order_details/domain/models/order_list_filter_model.dart:76` checks `if (onlyPosOrder == 1) orderTypes.add('POS');`. Remove filter option.
- **Order Details:** `order_details_screen.dart` has multiple checks checking for `orderType == 'POS'`. Refactor to standard marketplace presentation.
- **Home Screen:** `order_widget.dart` checks for `orderType == 'POS'` to display `(POS)` tag and handle amount conversion. Refactor to standard marketplace order presentation.

### 6.2 Customer User App
- `lib/features/order_details/widgets/order_details_status_widget.dart` and `order_details_widget.dart`:
  - Contains defensive checks: `orderProvider.orders?.orderType != 'POS'`.
  - In Marketplace-only V1, customer app only displays marketplace orders. Remove dead checks or allow benign pass-through for historical orders.

### 6.3 Delivery Man App
- Completely clean. Zero references to POS. No edits required.

---

## 7. Database POS Objects & Historical Data Strategy

### 7.1 Historical Data Preservation Directive
**Historical financial transactions must NEVER be blindly destroyed.**
Historical orders where `orders.order_type = 'POS'` represent prior financial transactions, completed sales, and historical tax liabilities.

### 7.2 Database Table Classification
1. **`pos_customer_ledgers`:** 
   - Table created for physical customer debt tracking.
   - Status: Deprecated. Contains zero active marketplace operations. Retain table in schema as read-only archive to prevent data loss on existing databases, but disconnect all application write endpoints.
2. **`pos_debt_transactions`:**
   - Physical debt installment payments. Deprecated. Retain table in schema as read-only archive.
3. **`pos_cashier_shifts`:**
   - In-store cashier shift open/close logs. Deprecated. Retain as archive.
4. **`pos_transfers` & `pos_transfer_items`:**
   - Physical inter-branch transfer waybills. Deprecated. Retain as archive.
5. **`pos_subscriptions`:**
   - POS SaaS subscriptions. Deprecated. Retain as archive.
6. **`orders` table (`order_type` column):**
   - **MUST REMAIN.** Identifies order stream:
     - `'default_type'` = Online marketplace delivery
     - `'pickup'` = Online marketplace customer collection
     - `'POS'` = Historical in-store orders (archived)
7. **`sellers` table (`marketplace_status` column):**
   - In Omnichannel POS migration, column defaulted to `'pos_only'`.
   - In Marketplace-only V1, all active sellers must be marketplace sellers.
   - Migration requirement: Run database update `UPDATE sellers SET marketplace_status = 'approved' WHERE status = 'approved' AND (marketplace_status = 'pos_only' OR marketplace_status IS NULL)`.

---

## 8. POS Permissions & RBAC

### 8.1 Employee Role Modules
- `App\Enums\GlobalConstant::EMPLOYEE_ROLE_MODULE_PERMISSION`:
  - Remove `'pos_management' => 'pos_management'` from available admin employee permission keys.
- `App\Http\Controllers\Vendor\Employee\VendorRoleController::MODULE_PERMISSIONS`:
  - Remove `'pos_management' => 'In-Store POS System'` from vendor role permissions.
- Database `admin_roles` and `vendor_roles`:
  - Any existing role containing `'pos_management'` in its JSON `modules` column will gracefully ignore the key because no route or controller checks `pos_management`.

---

## 9. Configuration & Environment Dependencies

- **`config/cors.php`:**
  - Remove `'https://pos.victoriousmarket.com.ng'` from `'allowed_origins'`.
- **`business_settings`:**
  - Keys `pos_free_branch_limit`, `pos_multi_branch_monthly_price`, `pos_multi_branch_annual_price`, `pos_trial_days`, `pos_receipt_footer_text`, `pos_reorder_qr_status`, `seller_pos` are rendered dormant.

---

## 10. Risk Analysis & Mitigation

| Risk ID | Identified Risk | Severity | Mitigation Strategy |
| :--- | :--- | :---: | :--- |
| **R-01** | Historical POS invoice crashes when customer or admin views past receipt | Medium | Retain read-only display fallbacks in `invoice.blade.php` so `$order->order_type == 'POS'` continues rendering customer name safely. |
| **R-02** | Active vendor blocked from marketplace because `marketplace_status = 'pos_only'` | High | Include automatic migration/data patch ensuring all approved sellers have `marketplace_status = 'approved'`. |
| **R-03** | Missing route error on digital product downloads referencing `digital-product-download-pos` | Low | Replace route call in email template and Aster theme with standard `digital-product-download.index`. |
| **R-04** | Breaking Flutter JSON deserialization if `orderType` is missing | Low | Flutter models continue reading `orderType` as nullable string; backend continues returning it. |
| **R-05** | Financial drift if historical POS order is touched by settlement command | Critical | `VendorSettlementService` strictly enforces that manual settlement only executes for marketplace orders; historical POS orders are blocked. |

---

## 11. Exact Deletion & Detachment Order

1. **Step 1: Database Migration / Data Normalization**
   - Create migration updating all approved sellers to `marketplace_status = 'approved'`.
2. **Step 2: Remove Orphaned Dead Code**
   - Delete `OrderService::getPOSOrderData()`.
   - Delete `app/Traits/API/v3/VendorPOSManagement.php`.
   - Delete `public/assets/back-end/js/admin/pos-script.js` & `vendor/pos-script.js`.
   - Delete unused POS image assets.
3. **Step 3: Decouple Repositories & Controllers**
   - Remove POS filter closures from `OrderRepository` and `ProductRepository`.
   - Remove dead POS view redirects in Admin & Vendor `OrderController`.
   - Remove POS tracking error branch in `UserProfileController`.
   - Remove POS role module from `VendorRoleController` and `GlobalConstant`.
   - Remove `https://pos.victoriousmarket.com.ng` from `config/cors.php`.
4. **Step 4: Decouple Blade Views**
   - Remove POS settings toggle from `seller-settings.blade.php` and `setting.blade.php`.
   - Remove POS filter checkbox from order offcanvas filter drawers.
   - Clean digital product download route references.
5. **Step 5: Decouple Flutter Vendor & User Apps**
   - Remove `pos_icon.png` and references in `images.dart`.
   - Remove POS filter checkbox from `order_list_filter_model.dart`.
   - Clean POS conditional UI checks in `order_details_screen.dart`, `order_widget.dart`, `order_payment_info_widget.dart`.
6. **Step 6: Execute Full Regression Suite**
   - Run unit, integration, and mathematical financial invariance tests.

---

## 12. Verification & Regression Test Plan

1. **Test POS Filter Elimination:** Verify that admin and vendor order lists query exclusively marketplace orders without SQL errors.
2. **Test Order Details Parity:** Verify that `OrderController@details` renders smoothly for all orders without searching for non-existent POS views.
3. **Test Vendor Marketplace Access:** Verify that all approved vendors can list products and receive orders without `pos_only` gating.
4. **Test Historical Invoice Rendering:** Verify that historical orders with `order_type = 'POS'` render invoice HTML without exceptions.
5. **Test Role Creation:** Verify that creating admin employee and vendor employee roles succeeds without POS module checkboxes.
6. **Test Syntax Validation:** Run `php -l` across all modified PHP files to guarantee zero syntax errors.
7. **Test Financial Zero-Drift:** Run `php artisan test --filter=Commit7FinancialSettlementTest` ensuring $\Delta = 0.00$.
