# Role Security & Endpoint Access Policy: Admin Staff: Finance Controller & Auditor

> **Role Scope:** `Admin Staff: Finance Controller & Auditor`  
> **Total Allowed Endpoints:** `53`  
> **Total Disallowed / Blocked Endpoints:** `1530`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

Super Admin employee assigned to financial audit, refund requests, withdrawal disbursements, and tax reporting.

### Core Authorized Capabilities:
- ✅ **Executive Financial P&L and Sales Analytics Hub**
- ✅ **Merchant Withdrawal Request Review & Processing**
- ✅ **Customer Refund Disputes and Credit Reconciliations**
- ✅ **Commission Split & Tax Audits with Zero Mathematical Drift**

### Strict Architectural Restrictions:
- ⛔ **Blocked from modifying product descriptions, categories, or dispatching delivery riders**
- ⛔ **Blocked from modifying employee roles or core SaaS database configurations**

---

## 2. Authorized Endpoints Access Matrix (53 Endpoints)

| # | Method | URI | Route Name | Action / Controller |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/admin/dashboard` | `admin.dashboard.index` | `App\Http\Controllers\Admin\DashboardController@index` |
| 2 | `POST` | `/admin/dashboard/order-status` | `admin.dashboard.order-status` | `App\Http\Controllers\Admin\DashboardController@getOrderStatus` |
| 3 | `GET` | `/admin/dashboard/earning-statistics` | `admin.dashboard.earning-statistics` | `App\Http\Controllers\Admin\DashboardController@getEarningStatistics` |
| 4 | `GET` | `/admin/dashboard/order-statistics` | `admin.dashboard.order-statistics` | `App\Http\Controllers\Admin\DashboardController@getOrderStatistics` |
| 5 | `GET` | `/admin/dashboard/real-time-activities` | `admin.dashboard.real-time-activities` | `App\Http\Controllers\Admin\DashboardController@getRealTimeActivities` |
| 6 | `GET` | `/admin/customer/wallet/report` | `admin.customer.wallet.report` | `App\Http\Controllers\Admin\Customer\CustomerWalletController@index` |
| 7 | `POST` | `/admin/customer/wallet/add-fund` | `admin.customer.wallet.add-fund` | `App\Http\Controllers\Admin\Customer\CustomerWalletController@addFund` |
| 8 | `GET` | `/admin/customer/wallet/export` | `admin.customer.wallet.export` | `App\Http\Controllers\Admin\Customer\CustomerWalletController@exportList` |
| 9 | `GET` | `/admin/customer/wallet/bonus-setup` | `admin.customer.wallet.bonus-setup` | `App\Http\Controllers\Admin\Customer\CustomerWalletController@getBonusSetupView` |
| 10 | `POST` | `/admin/customer/wallet/bonus-setup` | `admin.customer.wallet.` | `App\Http\Controllers\Admin\Customer\CustomerWalletController@addBonusSetup` |
| 11 | `POST` | `/admin/customer/wallet/bonus-setup-update` | `admin.customer.wallet.bonus-setup-update` | `App\Http\Controllers\Admin\Customer\CustomerWalletController@update` |
| 12 | `POST` | `/admin/customer/wallet/bonus-setup-status` | `admin.customer.wallet.bonus-setup-status` | `App\Http\Controllers\Admin\Customer\CustomerWalletController@updateStatus` |
| 13 | `GET` | `/admin/customer/wallet/bonus-setup/edit/{id}` | `admin.customer.wallet.bonus-setup-edit` | `App\Http\Controllers\Admin\Customer\CustomerWalletController@getUpdateView` |
| 14 | `DELETE` | `/admin/customer/wallet/bonus-setup-delete` | `admin.customer.wallet.bonus-setup-delete` | `App\Http\Controllers\Admin\Customer\CustomerWalletController@deleteBonus` |
| 15 | `GET` | `/admin/report/inhouse-product-sale` | `admin.report.inhouse-product-sale` | `App\Http\Controllers\Admin\InhouseProductSaleController@index` |
| 16 | `GET` | `/admin/report/transaction/refund-transaction-list` | `admin.report.transaction.refund-transaction-list` | `App\Http\Controllers\Admin\Report\RefundTransactionController@index` |
| 17 | `GET` | `/admin/report/transaction/refund-transaction-export` | `admin.report.transaction.refund-transaction-export` | `App\Http\Controllers\Admin\Report\RefundTransactionController@exportRefundTransaction` |
| 18 | `GET` | `/admin/report/transaction/refund-transaction-summary-pdf` | `admin.report.transaction.refund-transaction-summary-pdf` | `App\Http\Controllers\Admin\Report\RefundTransactionController@getRefundTransactionPDF` |
| 19 | `GET` | `/admin/report/earning` | `admin.report.earning` | `App\Http\Controllers\Admin\ReportController@admin_earning` |
| 20 | `GET` | `/admin/report/admin-earning` | `admin.report.admin-earning` | `App\Http\Controllers\Admin\ReportController@admin_earning` |
| 21 | `GET` | `/admin/report/admin-earning-excel-export` | `admin.report.admin-earning-excel-export` | `App\Http\Controllers\Admin\ReportController@exportAdminEarning` |
| 22 | `POST` | `/admin/report/admin-earning-duration-download-pdf` | `admin.report.admin-earning-duration-download-pdf` | `App\Http\Controllers\Admin\ReportController@admin_earning_duration_download_pdf` |
| 23 | `GET` | `/admin/report/vendor-earning` | `admin.report.vendor-earning` | `App\Http\Controllers\Admin\ReportController@vendorEarning` |
| 24 | `GET` | `/admin/report/vendor-earning-excel-export` | `admin.report.vendor-earning-excel-export` | `App\Http\Controllers\Admin\ReportController@exportVendorEarning` |
| 25 | `GET` | `/admin/report/set-date` | `admin.report.set-date` | `App\Http\Controllers\Admin\ReportController@set_date` |
| 26 | `GET` | `/admin/report/order` | `admin.report.order` | `App\Http\Controllers\Admin\OrderReportController@order_list` |
| 27 | `GET` | `/admin/report/order-report-excel` | `admin.report.order-report-excel` | `App\Http\Controllers\Admin\OrderReportController@orderReportExportExcel` |
| 28 | `GET` | `/admin/report/order-report-pdf` | `admin.report.order-report-pdf` | `App\Http\Controllers\Admin\OrderReportController@exportOrderReportInPDF` |
| 29 | `GET` | `/admin/report/all-product` | `admin.report.all-product` | `App\Http\Controllers\Admin\ProductReportController@all_product` |
| 30 | `GET` | `/admin/report/all-product-excel` | `admin.report.all-product-excel` | `App\Http\Controllers\Admin\ProductReportController@allProductExportExcel` |
| 31 | `GET` | `/admin/report/vendor-report` | `admin.report.vendor-report` | `App\Http\Controllers\Admin\VendorProductSaleReportController@vendorReport` |
| 32 | `GET` | `/admin/report/vendor-report-export` | `admin.report.vendor-report-export` | `App\Http\Controllers\Admin\VendorProductSaleReportController@exportVendorReport` |
| 33 | `GET` | `/admin/transaction/order-transaction-list` | `admin.transaction.order-transaction-list` | `App\Http\Controllers\Admin\TransactionReportController@order_transaction_list` |
| 34 | `GET` | `/admin/transaction/pdf-order-wise-transaction` | `admin.transaction.pdf-order-wise-transaction` | `App\Http\Controllers\Admin\TransactionReportController@pdf_order_wise_transaction` |
| 35 | `GET` | `/admin/transaction/order-transaction-export-excel` | `admin.transaction.order-transaction-export-excel` | `App\Http\Controllers\Admin\TransactionReportController@orderTransactionExportExcel` |
| 36 | `GET` | `/admin/transaction/order-transaction-summary-pdf` | `admin.transaction.order-transaction-summary-pdf` | `App\Http\Controllers\Admin\TransactionReportController@order_transaction_summary_pdf` |
| 37 | `GET` | `/admin/transaction/wallet-bonus` | `admin.transaction.wallet-bonus` | `App\Http\Controllers\Admin\TransactionReportController@wallet_bonus` |
| 38 | `GET` | `/admin/transaction/expense-transaction-list` | `admin.transaction.expense-transaction-list` | `App\Http\Controllers\Admin\ExpenseTransactionReportController@getExpenseTransactionList` |
| 39 | `GET` | `/admin/transaction/pdf-order-wise-expense-transaction` | `admin.transaction.pdf-order-wise-expense-transaction` | `App\Http\Controllers\Admin\ExpenseTransactionReportController@generateOrderWiseExpenseTransactionPdf` |
| 40 | `GET` | `/admin/transaction/expense-transaction-export-excel` | `admin.transaction.expense-transaction-export-excel` | `App\Http\Controllers\Admin\ExpenseTransactionReportController@expenseTransactionExportExcel` |
| 41 | `GET` | `/admin/transaction/expense-transaction-summary-pdf` | `admin.transaction.expense-transaction-summary-pdf` | `App\Http\Controllers\Admin\ExpenseTransactionReportController@generateExpenseTransactionSummaryPDF` |
| 42 | `GET` | `/admin/refund-section/refund/list/{status}` | `admin.refund-section.refund.list` | `App\Http\Controllers\Admin\Order\RefundController@index` |
| 43 | `GET` | `/admin/refund-section/refund/export/{status}` | `admin.refund-section.refund.export` | `App\Http\Controllers\Admin\Order\RefundController@exportList` |
| 44 | `GET` | `/admin/refund-section/refund/details/{id}` | `admin.refund-section.refund.details` | `App\Http\Controllers\Admin\Order\RefundController@getDetailsView` |
| 45 | `POST` | `/admin/refund-section/refund/refund-status-update` | `admin.refund-section.refund.refund-status-update` | `App\Http\Controllers\Admin\Order\RefundController@updateRefundStatus` |
| 46 | `GET` | `/admin/report/get-tax-report` | `admin.report.get-tax-report` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\AdminTaxReportController@getTaxReport` |
| 47 | `GET` | `/admin/report/get-tax-details` | `admin.report.getTaxDetails` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\AdminTaxReportController@getTaxDetails` |
| 48 | `GET` | `/admin/report/tax-details-report-export` | `admin.report.getTaxDetailsExport` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\AdminTaxReportController@adminTaxDetailsExport` |
| 49 | `GET` | `/admin/report/admin-tax-report-export` | `admin.report.adminTaxReportExport` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\AdminTaxReportController@adminTaxReportExport` |
| 50 | `GET` | `/admin/report/vendor-wise-taxes` | `admin.report.vendor-wise-taxes` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\VendorTaxReportController@vendorWiseTaxes` |
| 51 | `GET` | `/admin/report/vendor-wise-taxes-export` | `admin.report.vendorWiseTaxExport` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\VendorTaxReportController@vendorWiseTaxExport` |
| 52 | `GET` | `/admin/report/vendor-tax-report` | `admin.report.vendorTax` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\VendorTaxReportController@vendorTax` |
| 53 | `GET` | `/admin/report/vendor-tax-export` | `admin.report.vendorTaxExport` | `Modules\TaxModule\app\Http\Controllers\Admin\Reports\VendorTaxReportController@vendorTaxExport` |

---

## 3. Disallowed & Gated Endpoints Summary (1530 Endpoints Blocked)

Attempting to access any of the 1530 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

### Sample Gated Endpoints for this Role:

| # | Method | Gated URI | Guard Interceptor | Reason for Gating |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/_debugbar/open` | `auth / rbac` | Strictly isolated outside role boundary |
| 2 | `GET` | `/_debugbar/clockwork/{id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 3 | `GET` | `/_debugbar/assets/stylesheets` | `auth / rbac` | Strictly isolated outside role boundary |
| 4 | `GET` | `/_debugbar/assets/javascript` | `auth / rbac` | Strictly isolated outside role boundary |
| 5 | `DELETE` | `/_debugbar/cache/{key}/{tags?}` | `auth / rbac` | Strictly isolated outside role boundary |
| 6 | `POST` | `/_debugbar/queries/explain` | `auth / rbac` | Strictly isolated outside role boundary |
| 7 | `POST` | `/oauth/token` | `auth / rbac` | Strictly isolated outside role boundary |
| 8 | `GET` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 9 | `POST` | `/oauth/token/refresh` | `auth / rbac` | Strictly isolated outside role boundary |
| 10 | `POST` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 11 | `DELETE` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 12 | `GET` | `/oauth/tokens` | `auth / rbac` | Strictly isolated outside role boundary |
| 13 | `DELETE` | `/oauth/tokens/{token_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 14 | `GET` | `/oauth/clients` | `auth / rbac` | Strictly isolated outside role boundary |
| 15 | `POST` | `/oauth/clients` | `auth / rbac` | Strictly isolated outside role boundary |
| 16 | `PUT` | `/oauth/clients/{client_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 17 | `DELETE` | `/oauth/clients/{client_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 18 | `GET` | `/oauth/scopes` | `auth / rbac` | Strictly isolated outside role boundary |
| 19 | `GET` | `/oauth/personal-access-tokens` | `auth / rbac` | Strictly isolated outside role boundary |
| 20 | `POST` | `/oauth/personal-access-tokens` | `auth / rbac` | Strictly isolated outside role boundary |
| 21 | `DELETE` | `/oauth/personal-access-tokens/{token_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 22 | `GET` | `/sanctum/csrf-cookie` | `auth / rbac` | Strictly isolated outside role boundary |
| 23 | `GET` | `/_ignition/health-check` | `auth / rbac` | Strictly isolated outside role boundary |
| 24 | `POST` | `/_ignition/execute-solution` | `auth / rbac` | Strictly isolated outside role boundary |
| 25 | `POST` | `/_ignition/update-config` | `auth / rbac` | Strictly isolated outside role boundary |


