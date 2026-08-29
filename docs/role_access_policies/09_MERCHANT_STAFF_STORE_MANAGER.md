# Role Security & Endpoint Access Policy: Merchant Staff: Store Manager

> **Role Scope:** `Merchant Staff: Store Manager`  
> **Total Allowed Endpoints:** `79`  
> **Total Disallowed / Blocked Endpoints:** `1504`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

Assigned store manager for a physical branch register, handling sales, counter shifts, orders, and daily reconciliation.

### Core Authorized Capabilities:
- ✅ **In-Store POS Terminal & Counter Register Operations**
- ✅ **Branch Sales Reports and Till Drawer Shift Closeout**
- ✅ **Store Order Fulfillment and Customer Debt Collections**

### Strict Architectural Restrictions:
- ⛔ **Scoped strictly to assigned physical store branch (shop_id isolation)**
- ⛔ **Blocked from requesting bank payouts or modifying merchant company registration**

---

## 2. Authorized Endpoints Access Matrix (79 Endpoints)

| # | Method | URI | Route Name | Action / Controller |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/vendor/orders/list/{status}` | `vendor.orders.list` | `App\Http\Controllers\Vendor\Order\OrderController@index` |
| 2 | `GET` | `/vendor/orders/customers` | `vendor.orders.customers` | `App\Http\Controllers\Vendor\Order\OrderController@getCustomers` |
| 3 | `GET` | `/vendor/orders/export-excel/{status}` | `vendor.orders.export-excel` | `App\Http\Controllers\Vendor\Order\OrderController@exportList` |
| 4 | `GET` | `/vendor/orders/generate-invoice/{id}` | `vendor.orders.generate-invoice` | `App\Http\Controllers\Vendor\Order\OrderController@generateInvoice` |
| 5 | `GET` | `/vendor/orders/generate-packing-slip/{id}` | `vendor.orders.generate-packing-slip` | `App\Http\Controllers\Vendor\Order\OrderController@generatePackingSlip` |
| 6 | `GET` | `/vendor/orders/details/{id}` | `vendor.orders.details` | `App\Http\Controllers\Vendor\Order\OrderController@getView` |
| 7 | `POST` | `/vendor/orders/address-update` | `vendor.orders.address-update` | `App\Http\Controllers\Vendor\Order\OrderController@updateAddress` |
| 8 | `POST` | `/vendor/orders/payment-status` | `vendor.orders.payment-status` | `App\Http\Controllers\Vendor\Order\OrderController@updatePaymentStatus` |
| 9 | `POST` | `/vendor/orders/update-deliver-info` | `vendor.orders.update-deliver-info` | `App\Http\Controllers\Vendor\Order\OrderController@updateDeliverInfo` |
| 10 | `GET` | `/vendor/orders/add-delivery-man/{order_id}/{d_man_id}` | `vendor.orders.add-delivery-man` | `App\Http\Controllers\Vendor\Order\OrderController@addDeliveryMan` |
| 11 | `POST` | `/vendor/orders/amount-date-update` | `vendor.orders.amount-date-update` | `App\Http\Controllers\Vendor\Order\OrderController@updateAmountDate` |
| 12 | `POST` | `/vendor/orders/digital-file-upload-after-sell` | `vendor.orders.digital-file-upload-after-sell` | `App\Http\Controllers\Vendor\Order\OrderController@uploadDigitalFileAfterSell` |
| 13 | `POST` | `/vendor/orders/status` | `vendor.orders.status` | `App\Http\Controllers\Vendor\Order\OrderController@updateStatus` |
| 14 | `POST` | `/vendor/orders/customer-return-amount` | `vendor.orders.customer-return-amount` | `App\Http\Controllers\Vendor\Order\OrderController@orderReturnAmountToCustomer` |
| 15 | `POST` | `/vendor/orders/customer-due-amount` | `vendor.orders.customer-due-amount` | `App\Http\Controllers\Vendor\Order\OrderController@orderDueAmountSwitchToCOD` |
| 16 | `POST` | `/vendor/orders/customer-due-amount-mark-as-paid` | `vendor.orders.customer-due-amount-mark-as-paid` | `App\Http\Controllers\Vendor\Order\OrderController@orderDueAmountMarkAsPaid` |
| 17 | `GET` | `/vendor/orders/search-for-edit-order-product` | `vendor.orders.search-for-edit-order-product` | `App\Http\Controllers\Vendor\Order\OrderEditController@getSearchEditOrderProductsView` |
| 18 | `POST` | `/vendor/orders/edit-order-product-modal-view` | `vendor.orders.edit-order-product-modal-view` | `App\Http\Controllers\Vendor\Order\OrderEditController@getEditOrderProductModalView` |
| 19 | `POST` | `/vendor/orders/edit-order-product-add` | `vendor.orders.edit-order-product-add` | `App\Http\Controllers\Vendor\Order\OrderEditController@addEditOrderProduct` |
| 20 | `POST` | `/vendor/orders/edit-order-product-variant-price` | `vendor.orders.edit-order-product-variant-price` | `App\Http\Controllers\Vendor\Order\OrderEditController@checkProductVariantPrice` |
| 21 | `POST` | `/vendor/orders/edit-order-product-list-update` | `vendor.orders.edit-order-product-list-update` | `App\Http\Controllers\Vendor\Order\OrderEditController@updateEditOrderProductList` |
| 22 | `POST` | `/vendor/orders/edit-order-product-remove` | `vendor.orders.edit-order-product-remove` | `App\Http\Controllers\Vendor\Order\OrderEditController@removeEditOrderProduct` |
| 23 | `POST` | `/vendor/orders/edit-order-generate` | `vendor.orders.edit-order-generate` | `App\Http\Controllers\Vendor\Order\OrderEditController@generateEditOrderByProductList` |
| 24 | `POST` | `/vendor/orders/verify-pickup-otp` | `vendor.orders.verify-pickup-otp` | `App\Http\Controllers\Vendor\Order\InShopHandoverController@verifyPickupOtp` |
| 25 | `GET` | `/pos` | `pos.dashboard` | `Modules\Pos\app\Http\Controllers\DashboardController@index` |
| 26 | `GET` | `/pos/dashboard` | `pos.` | `Modules\Pos\app\Http\Controllers\DashboardController@index` |
| 27 | `GET` | `/pos/terminal` | `pos.index` | `Modules\Pos\app\Http\Controllers\PosController@index` |
| 28 | `POST` | `/pos/checkout` | `pos.checkout` | `Modules\Pos\app\Http\Controllers\PosController@checkout` |
| 29 | `GET` | `/pos/receipt/{id}` | `pos.receipt` | `Modules\Pos\app\Http\Controllers\PosController@receipt` |
| 30 | `GET` | `/pos/returns` | `pos.returns` | `Modules\Pos\app\Http\Controllers\PosController@returns` |
| 31 | `POST` | `/pos/returns/process` | `pos.returns.process` | `Modules\Pos\app\Http\Controllers\PosController@processReturn` |
| 32 | `POST` | `/pos/customer/quick-register` | `pos.customer.quick-register` | `Modules\Pos\app\Http\Controllers\PosController@quickRegisterCustomer` |
| 33 | `GET` | `/pos/products/template/csv` | `pos.products.template.csv` | `Modules\Pos\app\Http\Controllers\ProductController@downloadCsvTemplate` |
| 34 | `GET` | `/pos/products/export/csv` | `pos.products.export.csv` | `Modules\Pos\app\Http\Controllers\ProductController@exportCsv` |
| 35 | `GET` | `/pos/products/export/json` | `pos.products.export.json` | `Modules\Pos\app\Http\Controllers\ProductController@exportJson` |
| 36 | `POST` | `/pos/products/import/csv` | `pos.products.import.csv` | `Modules\Pos\app\Http\Controllers\ProductController@importCsv` |
| 37 | `GET` | `/pos/products` | `pos.products.index` | `Modules\Pos\app\Http\Controllers\ProductController@index` |
| 38 | `GET` | `/pos/products/create` | `pos.products.create` | `Modules\Pos\app\Http\Controllers\ProductController@create` |
| 39 | `POST` | `/pos/products` | `pos.products.store` | `Modules\Pos\app\Http\Controllers\ProductController@store` |
| 40 | `GET` | `/pos/products/{product}/edit` | `pos.products.edit` | `Modules\Pos\app\Http\Controllers\ProductController@edit` |
| 41 | `PUT` | `/pos/products/{product}` | `pos.products.update` | `Modules\Pos\app\Http\Controllers\ProductController@update` |
| 42 | `DELETE` | `/pos/products/{product}` | `pos.products.destroy` | `Modules\Pos\app\Http\Controllers\ProductController@destroy` |
| 43 | `GET` | `/pos/warehouses/ajax/cities/{state_id}` | `pos.warehouses.cities-ajax` | `Modules\Pos\app\Http\Controllers\WarehouseController@getCitiesAjax` |
| 44 | `GET` | `/pos/warehouses/ajax/hubs/{city_id}` | `pos.warehouses.hubs-ajax` | `Modules\Pos\app\Http\Controllers\WarehouseController@getHubsAjax` |
| 45 | `GET` | `/pos/warehouses` | `pos.warehouses.index` | `Modules\Pos\app\Http\Controllers\WarehouseController@index` |
| 46 | `GET` | `/pos/warehouses/create` | `pos.warehouses.create` | `Modules\Pos\app\Http\Controllers\WarehouseController@create` |
| 47 | `POST` | `/pos/warehouses` | `pos.warehouses.store` | `Modules\Pos\app\Http\Controllers\WarehouseController@store` |
| 48 | `GET` | `/pos/warehouses/{warehouse}` | `pos.warehouses.show` | `Modules\Pos\app\Http\Controllers\WarehouseController@show` |
| 49 | `GET` | `/pos/warehouses/{warehouse}/edit` | `pos.warehouses.edit` | `Modules\Pos\app\Http\Controllers\WarehouseController@edit` |
| 50 | `PUT` | `/pos/warehouses/{warehouse}` | `pos.warehouses.update` | `Modules\Pos\app\Http\Controllers\WarehouseController@update` |
| 51 | `DELETE` | `/pos/warehouses/{warehouse}` | `pos.warehouses.destroy` | `Modules\Pos\app\Http\Controllers\WarehouseController@destroy` |
| 52 | `GET` | `/pos/stock` | `pos.stock.index` | `Modules\Pos\app\Http\Controllers\StockController@index` |
| 53 | `GET` | `/pos/stock/in` | `pos.stock.in.form` | `Modules\Pos\app\Http\Controllers\StockController@stockInForm` |
| 54 | `POST` | `/pos/stock/in` | `pos.stock.in` | `Modules\Pos\app\Http\Controllers\StockController@stockIn` |
| 55 | `GET` | `/pos/stock/transfers` | `pos.stock.transfers` | `Modules\Pos\app\Http\Controllers\StockController@transfers` |
| 56 | `POST` | `/pos/stock/transfers` | `pos.stock.transfers.create` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 57 | `POST` | `/pos/stock/transfers/out` | `pos.stock.transfer.out` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 58 | `POST` | `/pos/stock/transfers/{id}/recall` | `pos.stock.transfer.recall` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 59 | `POST` | `/pos/stock/transfers/{id}/accept` | `pos.stock.transfer.accept` | `Modules\Pos\app\Http\Controllers\StockController@createTransfer` |
| 60 | `GET` | `/pos/stock/transfers/{id}/waybill` | `pos.stock.waybill` | `Modules\Pos\app\Http\Controllers\StockController@waybill` |
| 61 | `GET` | `/pos/stock/adjustments` | `pos.stock.adjustments` | `Modules\Pos\app\Http\Controllers\StockController@adjustments` |
| 62 | `POST` | `/pos/stock/adjustments` | `pos.stock.adjustments.create` | `Modules\Pos\app\Http\Controllers\StockController@createAdjustment` |
| 63 | `POST` | `/pos/stock/adjustments/record` | `pos.stock.adjustments.record` | `Modules\Pos\app\Http\Controllers\StockController@createAdjustment` |
| 64 | `GET` | `/pos/stock/unsupplied` | `pos.stock.unsupplied` | `Modules\Pos\app\Http\Controllers\StockController@unsuppliedOrders` |
| 65 | `GET` | `/pos/transactions` | `pos.transactions.index` | `Modules\Pos\app\Http\Controllers\TransactionController@index` |
| 66 | `GET` | `/pos/transactions/cashier-shifts` | `pos.transactions.cashier-shifts` | `Modules\Pos\app\Http\Controllers\TransactionController@cashierShifts` |
| 67 | `GET` | `/pos/transactions/inventory-log` | `pos.transactions.inventory-log` | `Modules\Pos\app\Http\Controllers\TransactionController@inventoryLog` |
| 68 | `GET` | `/pos/transactions/export` | `pos.transactions.export` | `Modules\Pos\app\Http\Controllers\TransactionController@export` |
| 69 | `GET` | `/pos/transactions/export/csv` | `pos.transactions.export.csv` | `Modules\Pos\app\Http\Controllers\TransactionController@export` |
| 70 | `GET` | `/pos/transactions/export/json` | `pos.transactions.export.json` | `Modules\Pos\app\Http\Controllers\TransactionController@export` |
| 71 | `GET` | `/pos/debts` | `pos.debts.index` | `Modules\Pos\app\Http\Controllers\DebtController@index` |
| 72 | `GET` | `/pos/debts/customer/{id}` | `pos.debts.customer` | `Modules\Pos\app\Http\Controllers\DebtController@customerLedger` |
| 73 | `POST` | `/pos/debts/payment` | `pos.debts.payment` | `Modules\Pos\app\Http\Controllers\DebtController@recordPayment` |
| 74 | `GET` | `/pos/debts/export` | `pos.debts.export` | `Modules\Pos\app\Http\Controllers\DebtController@export` |
| 75 | `GET` | `/pos/reports` | `pos.reports.index` | `Modules\Pos\app\Http\Controllers\ReportController@index` |
| 76 | `GET` | `/pos/reports/profit-loss` | `pos.reports.profit-loss` | `Modules\Pos\app\Http\Controllers\ReportController@profitLoss` |
| 77 | `GET` | `/pos/reports/top-products` | `pos.reports.top-products` | `Modules\Pos\app\Http\Controllers\ReportController@topProducts` |
| 78 | `GET` | `/pos/reports/export/{type}` | `pos.reports.export` | `Modules\Pos\app\Http\Controllers\ReportController@exportCsv` |
| 79 | `GET` | `/pos/reports/export-json/{type}` | `pos.reports.export.json` | `Modules\Pos\app\Http\Controllers\ReportController@exportJson` |

---

## 3. Disallowed & Gated Endpoints Summary (1504 Endpoints Blocked)

Attempting to access any of the 1504 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

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


