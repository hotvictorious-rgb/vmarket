# Role Security & Endpoint Access Policy: Merchant Staff: Counter Cashier

> **Role Scope:** `Merchant Staff: Counter Cashier`  
> **Total Allowed Endpoints:** `22`  
> **Total Disallowed / Blocked Endpoints:** `1561`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

Front-of-house cashier operating barcode registers, handling split-tender payments, and printing sales receipts.

### Core Authorized Capabilities:
- ✅ **Counter Register Barcode Lookups and Instant Fast Cart Addition**
- ✅ **Split-Tender Payment Processing (Cash + POS Terminal Card)**
- ✅ **Customer Debt Installment Records and Receipt Printing**

### Strict Architectural Restrictions:
- ⛔ **Blocked from administrative store settings, inventory write-offs, and store employee lists**
- ⛔ **Strictly locked to active assigned cash register shift**

---

## 2. Authorized Endpoints Access Matrix (22 Endpoints)

| # | Method | URI | Route Name | Action / Controller |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/pos` | `pos.dashboard` | `Modules\Pos\app\Http\Controllers\DashboardController@index` |
| 2 | `GET` | `/pos/terminal` | `pos.index` | `Modules\Pos\app\Http\Controllers\PosController@index` |
| 3 | `GET` | `/pos/products/template/csv` | `pos.products.template.csv` | `Modules\Pos\app\Http\Controllers\ProductController@downloadCsvTemplate` |
| 4 | `GET` | `/pos/products/export/csv` | `pos.products.export.csv` | `Modules\Pos\app\Http\Controllers\ProductController@exportCsv` |
| 5 | `GET` | `/pos/products/export/json` | `pos.products.export.json` | `Modules\Pos\app\Http\Controllers\ProductController@exportJson` |
| 6 | `POST` | `/pos/products/import/csv` | `pos.products.import.csv` | `Modules\Pos\app\Http\Controllers\ProductController@importCsv` |
| 7 | `GET` | `/pos/products` | `pos.products.index` | `Modules\Pos\app\Http\Controllers\ProductController@index` |
| 8 | `GET` | `/pos/products/create` | `pos.products.create` | `Modules\Pos\app\Http\Controllers\ProductController@create` |
| 9 | `POST` | `/pos/products` | `pos.products.store` | `Modules\Pos\app\Http\Controllers\ProductController@store` |
| 10 | `GET` | `/pos/products/{product}/edit` | `pos.products.edit` | `Modules\Pos\app\Http\Controllers\ProductController@edit` |
| 11 | `PUT` | `/pos/products/{product}` | `pos.products.update` | `Modules\Pos\app\Http\Controllers\ProductController@update` |
| 12 | `DELETE` | `/pos/products/{product}` | `pos.products.destroy` | `Modules\Pos\app\Http\Controllers\ProductController@destroy` |
| 13 | `GET` | `/pos/transactions` | `pos.transactions.index` | `Modules\Pos\app\Http\Controllers\TransactionController@index` |
| 14 | `GET` | `/pos/transactions/cashier-shifts` | `pos.transactions.cashier-shifts` | `Modules\Pos\app\Http\Controllers\TransactionController@cashierShifts` |
| 15 | `GET` | `/pos/transactions/inventory-log` | `pos.transactions.inventory-log` | `Modules\Pos\app\Http\Controllers\TransactionController@inventoryLog` |
| 16 | `GET` | `/pos/transactions/export` | `pos.transactions.export` | `Modules\Pos\app\Http\Controllers\TransactionController@export` |
| 17 | `GET` | `/pos/transactions/export/csv` | `pos.transactions.export.csv` | `Modules\Pos\app\Http\Controllers\TransactionController@export` |
| 18 | `GET` | `/pos/transactions/export/json` | `pos.transactions.export.json` | `Modules\Pos\app\Http\Controllers\TransactionController@export` |
| 19 | `GET` | `/pos/debts` | `pos.debts.index` | `Modules\Pos\app\Http\Controllers\DebtController@index` |
| 20 | `GET` | `/pos/debts/customer/{id}` | `pos.debts.customer` | `Modules\Pos\app\Http\Controllers\DebtController@customerLedger` |
| 21 | `POST` | `/pos/debts/payment` | `pos.debts.payment` | `Modules\Pos\app\Http\Controllers\DebtController@recordPayment` |
| 22 | `GET` | `/pos/debts/export` | `pos.debts.export` | `Modules\Pos\app\Http\Controllers\DebtController@export` |

---

## 3. Disallowed & Gated Endpoints Summary (1561 Endpoints Blocked)

Attempting to access any of the 1561 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

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


