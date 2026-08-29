# Role Security & Endpoint Access Policy: Merchant Staff: Storekeeper & Inventory Clerk

> **Role Scope:** `Merchant Staff: Storekeeper & Inventory Clerk`  
> **Total Allowed Endpoints:** `32`  
> **Total Disallowed / Blocked Endpoints:** `1551`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

Back-of-house warehouse clerk managing physical stock intake, barcode labeling, and stock transfers.

### Core Authorized Capabilities:
- ✅ **Batch Stock Intake & Physical Inventory Counts**
- ✅ **Inter-Branch Waybill Transfers & Dispatch Labeling**
- ✅ **Stock Adjustment Audits & Damage Write-Off Logging**

### Strict Architectural Restrictions:
- ⛔ **Blocked from financial reports, cashier till drawers, and merchant wallet operations**

---

## 2. Authorized Endpoints Access Matrix (32 Endpoints)

| # | Method | URI | Route Name | Action / Controller |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/pos/products/template/csv` | `pos.products.template.csv` | `Modules\Pos\app\Http\Controllers\ProductController@downloadCsvTemplate` |
| 2 | `GET` | `/pos/products/export/csv` | `pos.products.export.csv` | `Modules\Pos\app\Http\Controllers\ProductController@exportCsv` |
| 3 | `GET` | `/pos/products/export/json` | `pos.products.export.json` | `Modules\Pos\app\Http\Controllers\ProductController@exportJson` |
| 4 | `POST` | `/pos/products/import/csv` | `pos.products.import.csv` | `Modules\Pos\app\Http\Controllers\ProductController@importCsv` |
| 5 | `GET` | `/pos/products` | `pos.products.index` | `Modules\Pos\app\Http\Controllers\ProductController@index` |
| 6 | `GET` | `/pos/products/create` | `pos.products.create` | `Modules\Pos\app\Http\Controllers\ProductController@create` |
| 7 | `POST` | `/pos/products` | `pos.products.store` | `Modules\Pos\app\Http\Controllers\ProductController@store` |
| 8 | `GET` | `/pos/products/{product}/edit` | `pos.products.edit` | `Modules\Pos\app\Http\Controllers\ProductController@edit` |
| 9 | `PUT` | `/pos/products/{product}` | `pos.products.update` | `Modules\Pos\app\Http\Controllers\ProductController@update` |
| 10 | `DELETE` | `/pos/products/{product}` | `pos.products.destroy` | `Modules\Pos\app\Http\Controllers\ProductController@destroy` |
| 11 | `GET` | `/pos/warehouses/ajax/cities/{state_id}` | `pos.warehouses.cities-ajax` | `Modules\Pos\app\Http\Controllers\WarehouseController@getCitiesAjax` |
| 12 | `GET` | `/pos/warehouses/ajax/hubs/{city_id}` | `pos.warehouses.hubs-ajax` | `Modules\Pos\app\Http\Controllers\WarehouseController@getHubsAjax` |
| 13 | `GET` | `/pos/warehouses` | `pos.warehouses.index` | `Modules\Pos\app\Http\Controllers\WarehouseController@index` |
| 14 | `GET` | `/pos/warehouses/create` | `pos.warehouses.create` | `Modules\Pos\app\Http\Controllers\WarehouseController@create` |
| 15 | `POST` | `/pos/warehouses` | `pos.warehouses.store` | `Modules\Pos\app\Http\Controllers\WarehouseController@store` |
| 16 | `GET` | `/pos/warehouses/{warehouse}` | `pos.warehouses.show` | `Modules\Pos\app\Http\Controllers\WarehouseController@show` |
| 17 | `GET` | `/pos/warehouses/{warehouse}/edit` | `pos.warehouses.edit` | `Modules\Pos\app\Http\Controllers\WarehouseController@edit` |
| 18 | `PUT` | `/pos/warehouses/{warehouse}` | `pos.warehouses.update` | `Modules\Pos\app\Http\Controllers\WarehouseController@update` |
| 19 | `DELETE` | `/pos/warehouses/{warehouse}` | `pos.warehouses.destroy` | `Modules\Pos\app\Http\Controllers\WarehouseController@destroy` |
| 20 | `GET` | `/pos/stock` | `pos.stock.index` | `Modules\Pos\app\Http\Controllers\StockController@index` |
| 21 | `GET` | `/pos/stock/in` | `pos.stock.in.form` | `Modules\Pos\app\Http\Controllers\StockController@stockInForm` |
| 22 | `POST` | `/pos/stock/in` | `pos.stock.in` | `Modules\Pos\app\Http\Controllers\StockController@stockIn` |
| 23 | `GET` | `/pos/stock/transfers` | `pos.stock.transfers` | `Modules\Pos\app\Http\Controllers\StockController@transfers` |
| 24 | `POST` | `/pos/stock/transfers` | `pos.stock.transfers.create` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 25 | `POST` | `/pos/stock/transfers/out` | `pos.stock.transfer.out` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 26 | `POST` | `/pos/stock/transfers/{id}/recall` | `pos.stock.transfer.recall` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 27 | `POST` | `/pos/stock/transfers/{id}/accept` | `pos.stock.transfer.accept` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 28 | `GET` | `/pos/stock/transfers/{id}/waybill` | `pos.stock.waybill` | `Modules\Pos\app\Http\Controllers\StockController@waybill` |
| 29 | `GET` | `/pos/stock/adjustments` | `pos.stock.adjustments` | `Modules\Pos\app\Http\Controllers\StockController@adjustments` |
| 30 | `POST` | `/pos/stock/adjustments` | `pos.stock.adjustments.create` | `Modules\Pos\app\Http\Controllers\StockController@createAdjustment` |
| 31 | `POST` | `/pos/stock/adjustments/record` | `pos.stock.adjustments.record` | `Modules\Pos\app\Http\Controllers\StockController@createAdjustment` |
| 32 | `GET` | `/pos/stock/unsupplied` | `pos.stock.unsupplied` | `Modules\Pos\app\Http\Controllers\StockController@unsuppliedOrders` |

---

## 3. Disallowed & Gated Endpoints Summary (1551 Endpoints Blocked)

Attempting to access any of the 1551 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

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


