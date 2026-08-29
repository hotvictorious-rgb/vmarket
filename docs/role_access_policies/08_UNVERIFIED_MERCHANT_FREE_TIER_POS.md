# Role Security & Endpoint Access Policy: Unverified Merchant (Free-Tier In-Store POS)

> **Role Scope:** `Unverified Merchant (Free-Tier In-Store POS)`  
> **Total Allowed Endpoints:** `57`  
> **Total Disallowed / Blocked Endpoints:** `1526`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

Newly onboarded merchant awaiting KYC verification. Enjoys 100% Free In-Store Counter POS for offline physical sales, but is strictly isolated from online marketplace sales.

### Core Authorized Capabilities:
- ✅ **Free-Tier In-Store POS Counter Sales (1 Physical Store)**
- ✅ **Offline Barcode Scanning and Instant Receipt Printing**
- ✅ **Cash Register Shift Drawer Reconciliation**

### Strict Architectural Restrictions:
- ⛔ **Strictly BLOCKED from online marketplace selling until KYC approved**
- ⛔ **Marketplace return buttons masked with "Free In-Store POS (Pending KYC)" badge**
- ⛔ **Blocked from online customer order feeds and multi-branch warehouse transfers**

---

## 2. Authorized Endpoints Access Matrix (57 Endpoints)

| # | Method | URI | Route Name | Action / Controller |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/vendor/auth/login` | `vendor.auth.` | `App\Http\Controllers\Vendor\Auth\LoginController@getLoginView` |
| 2 | `POST` | `/vendor/auth/login` | `vendor.auth.login` | `App\Http\Controllers\Vendor\Auth\LoginController@login` |
| 3 | `GET` | `/pos` | `pos.dashboard` | `Modules\Pos\app\Http\Controllers\DashboardController@index` |
| 4 | `GET` | `/pos/dashboard` | `pos.` | `Modules\Pos\app\Http\Controllers\DashboardController@index` |
| 5 | `GET` | `/pos/terminal` | `pos.index` | `Modules\Pos\app\Http\Controllers\PosController@index` |
| 6 | `POST` | `/pos/checkout` | `pos.checkout` | `Modules\Pos\app\Http\Controllers\PosController@checkout` |
| 7 | `GET` | `/pos/receipt/{id}` | `pos.receipt` | `Modules\Pos\app\Http\Controllers\PosController@receipt` |
| 8 | `GET` | `/pos/returns` | `pos.returns` | `Modules\Pos\app\Http\Controllers\PosController@returns` |
| 9 | `POST` | `/pos/returns/process` | `pos.returns.process` | `Modules\Pos\app\Http\Controllers\PosController@processReturn` |
| 10 | `POST` | `/pos/customer/quick-register` | `pos.customer.quick-register` | `Modules\Pos\app\Http\Controllers\PosController@quickRegisterCustomer` |
| 11 | `GET` | `/pos/products/template/csv` | `pos.products.template.csv` | `Modules\Pos\app\Http\Controllers\ProductController@downloadCsvTemplate` |
| 12 | `GET` | `/pos/products/export/csv` | `pos.products.export.csv` | `Modules\Pos\app\Http\Controllers\ProductController@exportCsv` |
| 13 | `GET` | `/pos/products/export/json` | `pos.products.export.json` | `Modules\Pos\app\Http\Controllers\ProductController@exportJson` |
| 14 | `POST` | `/pos/products/import/csv` | `pos.products.import.csv` | `Modules\Pos\app\Http\Controllers\ProductController@importCsv` |
| 15 | `GET` | `/pos/products` | `pos.products.index` | `Modules\Pos\app\Http\Controllers\ProductController@index` |
| 16 | `GET` | `/pos/products/create` | `pos.products.create` | `Modules\Pos\app\Http\Controllers\ProductController@create` |
| 17 | `POST` | `/pos/products` | `pos.products.store` | `Modules\Pos\app\Http\Controllers\ProductController@store` |
| 18 | `GET` | `/pos/products/{product}/edit` | `pos.products.edit` | `Modules\Pos\app\Http\Controllers\ProductController@edit` |
| 19 | `PUT` | `/pos/products/{product}` | `pos.products.update` | `Modules\Pos\app\Http\Controllers\ProductController@update` |
| 20 | `DELETE` | `/pos/products/{product}` | `pos.products.destroy` | `Modules\Pos\app\Http\Controllers\ProductController@destroy` |
| 21 | `GET` | `/pos/warehouses/ajax/cities/{state_id}` | `pos.warehouses.cities-ajax` | `Modules\Pos\app\Http\Controllers\WarehouseController@getCitiesAjax` |
| 22 | `GET` | `/pos/warehouses/ajax/hubs/{city_id}` | `pos.warehouses.hubs-ajax` | `Modules\Pos\app\Http\Controllers\WarehouseController@getHubsAjax` |
| 23 | `GET` | `/pos/warehouses` | `pos.warehouses.index` | `Modules\Pos\app\Http\Controllers\WarehouseController@index` |
| 24 | `GET` | `/pos/warehouses/create` | `pos.warehouses.create` | `Modules\Pos\app\Http\Controllers\WarehouseController@create` |
| 25 | `POST` | `/pos/warehouses` | `pos.warehouses.store` | `Modules\Pos\app\Http\Controllers\WarehouseController@store` |
| 26 | `GET` | `/pos/warehouses/{warehouse}` | `pos.warehouses.show` | `Modules\Pos\app\Http\Controllers\WarehouseController@show` |
| 27 | `GET` | `/pos/warehouses/{warehouse}/edit` | `pos.warehouses.edit` | `Modules\Pos\app\Http\Controllers\WarehouseController@edit` |
| 28 | `PUT` | `/pos/warehouses/{warehouse}` | `pos.warehouses.update` | `Modules\Pos\app\Http\Controllers\WarehouseController@update` |
| 29 | `DELETE` | `/pos/warehouses/{warehouse}` | `pos.warehouses.destroy` | `Modules\Pos\app\Http\Controllers\WarehouseController@destroy` |
| 30 | `GET` | `/pos/stock` | `pos.stock.index` | `Modules\Pos\app\Http\Controllers\StockController@index` |
| 31 | `GET` | `/pos/stock/in` | `pos.stock.in.form` | `Modules\Pos\app\Http\Controllers\StockController@stockInForm` |
| 32 | `POST` | `/pos/stock/in` | `pos.stock.in` | `Modules\Pos\app\Http\Controllers\StockController@stockIn` |
| 33 | `GET` | `/pos/stock/transfers` | `pos.stock.transfers` | `Modules\Pos\app\Http\Controllers\StockController@transfers` |
| 34 | `POST` | `/pos/stock/transfers` | `pos.stock.transfers.create` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 35 | `POST` | `/pos/stock/transfers/out` | `pos.stock.transfer.out` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 36 | `POST` | `/pos/stock/transfers/{id}/recall` | `pos.stock.transfer.recall` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 37 | `POST` | `/pos/stock/transfers/{id}/accept` | `pos.stock.transfer.accept` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 38 | `GET` | `/pos/stock/transfers/{id}/waybill` | `pos.stock.waybill` | `Modules\Pos\app\Http\Controllers\StockController@waybill` |
| 39 | `GET` | `/pos/stock/adjustments` | `pos.stock.adjustments` | `Modules\Pos\app\Http\Controllers\StockController@adjustments` |
| 40 | `POST` | `/pos/stock/adjustments` | `pos.stock.adjustments.create` | `Modules\Pos\app\Http\Controllers\StockController@createAdjustment` |
| 41 | `POST` | `/pos/stock/adjustments/record` | `pos.stock.adjustments.record` | `Modules\Pos\app\Http\Controllers\StockController@createAdjustment` |
| 42 | `GET` | `/pos/stock/unsupplied` | `pos.stock.unsupplied` | `Modules\Pos\app\Http\Controllers\StockController@unsuppliedOrders` |
| 43 | `GET` | `/pos/transactions` | `pos.transactions.index` | `Modules\Pos\app\Http\Controllers\TransactionController@index` |
| 44 | `GET` | `/pos/transactions/cashier-shifts` | `pos.transactions.cashier-shifts` | `Modules\Pos\app\Http\Controllers\TransactionController@cashierShifts` |
| 45 | `GET` | `/pos/transactions/inventory-log` | `pos.transactions.inventory-log` | `Modules\Pos\app\Http\Controllers\TransactionController@inventoryLog` |
| 46 | `GET` | `/pos/transactions/export` | `pos.transactions.export` | `Modules\Pos\app\Http\Controllers\TransactionController@export` |
| 47 | `GET` | `/pos/transactions/export/csv` | `pos.transactions.export.csv` | `Modules\Pos\app\Http\Controllers\TransactionController@export` |
| 48 | `GET` | `/pos/transactions/export/json` | `pos.transactions.export.json` | `Modules\Pos\app\Http\Controllers\TransactionController@export` |
| 49 | `GET` | `/pos/debts` | `pos.debts.index` | `Modules\Pos\app\Http\Controllers\DebtController@index` |
| 50 | `GET` | `/pos/debts/customer/{id}` | `pos.debts.customer` | `Modules\Pos\app\Http\Controllers\DebtController@customerLedger` |
| 51 | `POST` | `/pos/debts/payment` | `pos.debts.payment` | `Modules\Pos\app\Http\Controllers\DebtController@recordPayment` |
| 52 | `GET` | `/pos/debts/export` | `pos.debts.export` | `Modules\Pos\app\Http\Controllers\DebtController@export` |
| 53 | `GET` | `/pos/reports` | `pos.reports.index` | `Modules\Pos\app\Http\Controllers\ReportController@index` |
| 54 | `GET` | `/pos/reports/profit-loss` | `pos.reports.profit-loss` | `Modules\Pos\app\Http\Controllers\ReportController@profitLoss` |
| 55 | `GET` | `/pos/reports/top-products` | `pos.reports.top-products` | `Modules\Pos\app\Http\Controllers\ReportController@topProducts` |
| 56 | `GET` | `/pos/reports/export/{type}` | `pos.reports.export` | `Modules\Pos\app\Http\Controllers\ReportController@exportCsv` |
| 57 | `GET` | `/pos/reports/export-json/{type}` | `pos.reports.export.json` | `Modules\Pos\app\Http\Controllers\ReportController@exportJson` |

---

## 3. Disallowed & Gated Endpoints Summary (1526 Endpoints Blocked)

Attempting to access any of the 1526 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

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


