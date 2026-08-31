<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Modules\Pos\app\Http\Controllers\PosController;
use Modules\Pos\app\Http\Controllers\ProductController;
use Modules\Pos\app\Http\Controllers\StockController;
use Modules\Pos\app\Http\Controllers\TransactionController;
use Modules\Pos\app\Http\Controllers\DebtController;
use Modules\Pos\app\Http\Controllers\ReportController;
use Modules\Pos\app\Http\Controllers\DashboardController;
use Modules\Pos\app\Http\Controllers\WarehouseController;
use Modules\Pos\app\Http\Controllers\AuditorController;
use Modules\Pos\app\Http\Controllers\WholesaleController;
use Modules\Pos\app\Http\Controllers\UserController;
use Modules\Pos\app\Http\Controllers\SettingController;
use Modules\Pos\app\Http\Controllers\SubscriptionController;
use Modules\Pos\app\Http\Controllers\SaaSAdminController;

/*
|--------------------------------------------------------------------------
| Pos Module — Web Routes & SaaS Full In-Store Integration
|--------------------------------------------------------------------------
| Preserves 100% of Hysam's original UI, UX, workflows, and route names
| while running on the unified single Vmarket platform.
|
| Middleware stack:
|   - web         : session, CSRF, cookie
|   - pos.access  : multi-guard authorization (Super Admin, Verified & Unverified Merchants, Employees)
|
| [AI] Clients: Super Admin Command Center, Verified Merchant POS, Unverified Free-Tier POS.
*/

Route::middleware(['web', 'pos.access'])->group(function () {

    // ─── Return Hub to Vmarket Admin / Vendor Panel ─────────────────────────
    Route::get('/pos-sso-return', function () {
        if (Auth::guard('seller')->check()) {
            return redirect()->route('vendor.dashboard.index');
        }
        if (Auth::guard('vendor_employee')->check()) {
            return redirect()->route('vendor.dashboard.index');
        }
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard.index');
        }
        return redirect()->route('vendor.auth.login');
    })->name('pos.sso.return');

    Route::match(['get', 'post'], '/pos/logout', function () {
        if (Auth::guard('seller')->check()) {
            Auth::guard('seller')->logout();
            session()->flush();
            return redirect()->route('vendor.auth.login');
        }
        if (Auth::guard('vendor_employee')->check()) {
            Auth::guard('vendor_employee')->logout();
            session()->flush();
            return redirect()->route('vendor.auth.login');
        }
        if (Auth::guard('admin')->check()) {
            Auth::guard('admin')->logout();
            session()->flush();
            $adminLoginUrl = getWebConfig(name: 'admin_login_url') ?: 'admin';
            return redirect('login/' . $adminLoginUrl);
        }
        session()->flush();
        return redirect()->route('vendor.auth.login');
    })->name('logout');

    // ─── 1. Executive Dashboard ───────────────────────────────────────────────
    Route::get('/pos', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pos/dashboard', [DashboardController::class, 'index'])->name('pos.dashboard');

    // ─── 2. Visual Point of Sale (POS) ────────────────────────────────────────
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/terminal',                 [PosController::class, 'index'])->name('index');
        Route::get('/pos',                      [PosController::class, 'index']);
        Route::post('/checkout',                [PosController::class, 'checkout'])->name('checkout');
        Route::post('/customer/quick-register', [PosController::class, 'quickRegisterCustomer'])->name('customer.quick_register');
        Route::post('/customer/quick-register-alt', [PosController::class, 'quickRegisterCustomer'])->name('customer.quick-register');
        Route::get('/receipt/{id}',             [PosController::class, 'receipt'])->name('receipt');
        Route::get('/returns',                  [PosController::class, 'returns'])->name('returns');
        Route::post('/returns',                 [PosController::class, 'processReturn'])->name('returns.process');
    });

    // ─── 3. Products Catalog Management ───────────────────────────────────────
    Route::prefix('pos/products')->name('products.')->group(function () {
        Route::get('/',                 [ProductController::class, 'index'])->name('index');
        Route::get('/template/csv',     [ProductController::class, 'downloadCsvTemplate'])->name('template.csv');
        Route::get('/export/csv',       [ProductController::class, 'exportCsv'])->name('export.csv');
        Route::get('/export/json',      [ProductController::class, 'exportJson'])->name('export.json');
        Route::post('/import/csv',      [ProductController::class, 'importCsv'])->name('import.csv');
        Route::post('/',                [ProductController::class, 'store'])->name('store');
        Route::post('/{id}',            [ProductController::class, 'update'])->name('update');
        Route::post('/{id}/delete',     [ProductController::class, 'destroy'])->name('destroy');
    });
    Route::get('/pos/products-index-alias',            [ProductController::class, 'index'])->name('pos.products.index');
    Route::post('/pos/products-store-alias',           [ProductController::class, 'store'])->name('pos.products.store');
    Route::post('/pos/products-update-alias/{id}',     [ProductController::class, 'update'])->name('pos.products.update');
    Route::post('/pos/products-destroy-alias/{id}',    [ProductController::class, 'destroy'])->name('pos.products.destroy');
    Route::get('/pos/products/export/csv-alias',      [ProductController::class, 'exportCsv'])->name('pos.products.export.csv');
    Route::get('/pos/products/export/json-alias',     [ProductController::class, 'exportJson'])->name('pos.products.export.json');
    Route::get('/pos/products/template/csv-alias',    [ProductController::class, 'downloadCsvTemplate'])->name('pos.products.template.csv');
    Route::post('/pos/products/import/csv-alias',     [ProductController::class, 'importCsv'])->name('pos.products.import.csv');

    // ─── Warehouses / Branches ────────────────────────────────────────────────
    Route::prefix('pos/warehouses')->name('warehouses.')->group(function () {
        Route::get('/ajax/cities/{state_id}', [WarehouseController::class, 'getCitiesAjax'])->name('cities-ajax');
        Route::get('/ajax/hubs/{city_id}',    [WarehouseController::class, 'getHubsAjax'])->name('hubs-ajax');
    });
    Route::resource('pos/warehouses', WarehouseController::class)->names('pos.warehouses');
    Route::get('/pos/warehouses-unprefixed', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::get('/pos/warehouses/create-unprefixed', [WarehouseController::class, 'create'])->name('warehouses.create');

    // ─── 4. Stock Hub (Goods In, Transfers, Dispatch, Adjustments) ────────────
    Route::prefix('pos/stock')->name('stock.')->group(function () {
        Route::get('/',                      [StockController::class, 'index'])->name('index');
        Route::get('/transfers',             [StockController::class, 'transfers'])->name('transfers');
        Route::get('/waybill/{id}',          [StockController::class, 'waybill'])->name('waybill');
        Route::get('/in',                    [StockController::class, 'stockInForm'])->name('in.form');
        Route::post('/in',                   [StockController::class, 'stockIn'])->name('in');
        Route::post('/transfer-out',         [StockController::class, 'createTransfer'])->name('transfer.out');
        Route::post('/transfer-in/{id}',     [StockController::class, 'createTransfer'])->name('transfer.in');
        Route::post('/transfers/{id}/receive', [StockController::class, 'createTransfer'])->name('transfers.receive');
        Route::post('/transfer-recall/{id}', [StockController::class, 'createTransfer'])->name('transfer.recall');
        Route::get('/unsupplied',            [StockController::class, 'unsuppliedOrders'])->name('unsupplied');
        Route::post('/dispatch/{saleId}',    [StockController::class, 'dispatchConfirm'])->name('dispatch');
        Route::get('/adjustments',           [StockController::class, 'adjustments'])->name('adjustments');
        Route::post('/adjustments',          [StockController::class, 'createAdjustment'])->name('adjustments.record');
    });
    Route::get('/pos/stock-alias',                    [StockController::class, 'index'])->name('pos.stock.index');
    Route::post('/pos/stock/in-alias',                [StockController::class, 'stockIn'])->name('pos.stock.in');
    Route::get('/pos/stock/transfers-alias',          [StockController::class, 'transfers'])->name('pos.stock.transfers');
    Route::post('/pos/stock/transfer-out-alias',      [StockController::class, 'createTransfer'])->name('pos.stock.transfer.out');
    Route::get('/pos/stock/adjustments-alias',        [StockController::class, 'adjustments'])->name('pos.stock.adjustments');
    Route::post('/pos/stock/adjustments-record-alias',[StockController::class, 'createAdjustment'])->name('pos.stock.adjustments.record');
    Route::get('/pos/stock/unsupplied-alias',         [StockController::class, 'unsuppliedOrders'])->name('pos.stock.unsupplied');

    // ─── 5. Reports & AI Data Export Hub ──────────────────────────────────────
    Route::prefix('pos/reports')->name('reports.')->group(function () {
        Route::get('/',                      [ReportController::class, 'index'])->name('index');
        Route::get('/export-csv/{type}',     [ReportController::class, 'exportCsv'])->name('export.csv');
        Route::get('/export-json/{type}',    [ReportController::class, 'exportJson'])->name('export.json');
    });
    Route::get('/pos/reports-alias',         [ReportController::class, 'index'])->name('pos.reports.index');
    Route::get('/pos/reports/export-alias/{type}', [ReportController::class, 'exportCsv'])->name('pos.reports.export');
    Route::get('/pos/reports/export-csv-alias/{type}', [ReportController::class, 'exportCsv'])->name('pos.reports.export.csv');
    Route::get('/pos/reports/export-json-alias/{type}', [ReportController::class, 'exportJson'])->name('pos.reports.export.json');

    // ─── 6. Auditor Anti-Theft & Reconciliation Hub ───────────────────────────
    Route::prefix('pos/auditor')->name('auditor.')->group(function () {
        Route::get('/',                      [AuditorController::class, 'index'])->name('index');
    });

    // ─── 7. Debt & Part-Payment Recovery Hub ──────────────────────────────────
    Route::prefix('pos/debts')->name('debts.')->group(function () {
        Route::get('/',                      [DebtController::class, 'index'])->name('index');
        Route::post('/pay/{id}',             [DebtController::class, 'recordPayment'])->name('pay');
    });
    Route::get('/pos/debts-alias',            [DebtController::class, 'index'])->name('pos.debts.index');

    // ─── 8. Dedicated Wholesale Operations & Office Pricing Hub ───────────────
    Route::prefix('pos/wholesale')->name('wholesale.')->group(function () {
        Route::get('/',                      [WholesaleController::class, 'index'])->name('index');
        Route::post('/price/{id}',           [WholesaleController::class, 'priceOrder'])->name('price');
        Route::get('/invoice/{id}',          [WholesaleController::class, 'commercialInvoice'])->name('invoice');
    });

    // ─── 9. Transactions History & Audit Trail (Exportable) ───────────────────
    Route::prefix('pos/transactions')->name('transactions.')->group(function () {
        Route::get('/',                      [TransactionController::class, 'index'])->name('index');
        Route::get('/export-csv/{tab}',      [TransactionController::class, 'exportCsv'])->name('export.csv');
        Route::get('/export-json/{tab}',     [TransactionController::class, 'exportJson'])->name('export.json');
    });
    Route::get('/pos/transactions-alias',                 [TransactionController::class, 'index'])->name('pos.transactions.index');
    Route::get('/pos/transactions/export-csv-alias/{tab}',[TransactionController::class, 'exportCsv'])->name('pos.transactions.export.csv');
    Route::get('/pos/transactions/export-json-alias/{tab}',[TransactionController::class, 'exportJson'])->name('pos.transactions.export.json');
    Route::get('/pos/transactions/inventory-log-alias',  [TransactionController::class, 'inventoryLog'])->name('pos.transactions.inventory-log');
    Route::get('/pos/transactions/cashier-shifts-alias', [TransactionController::class, 'cashierShifts'])->name('pos.transactions.cashier-shifts');

    // ─── 10. Workers & Role Permissions Hub ───────────────────────────────────
    Route::prefix('pos/users')->name('users.')->group(function () {
        Route::get('/',                      [UserController::class, 'index'])->name('index');
        Route::post('/',                     [UserController::class, 'store'])->name('store');
        Route::post('/update/{id}',          [UserController::class, 'update'])->name('update');
        Route::post('/toggle/{id}',          [UserController::class, 'toggleStatus'])->name('toggle');
        Route::post('/reset-password/{id}',  [UserController::class, 'resetPassword'])->name('reset.password');
    });
    Route::get('/pos/users-alias',           [UserController::class, 'index'])->name('pos.users.index');

    // ─── 11. System Settings Hub ──────────────────────────────────────────────
    Route::prefix('pos/settings')->name('settings.')->group(function () {
        Route::get('/',                       [SettingController::class, 'index'])->name('index');
        Route::post('/',                      [SettingController::class, 'update'])->name('update');
        Route::post('/warehouse',             [SettingController::class, 'storeWarehouse'])->name('warehouse.store');
        Route::post('/warehouse/update/{id}', [SettingController::class, 'updateWarehouse'])->name('warehouse.update');
        Route::post('/warehouse/toggle/{id}', [SettingController::class, 'toggleWarehouse'])->name('warehouse.toggle');
    });
    Route::get('/pos/settings-alias',        [SettingController::class, 'index'])->name('pos.settings.index');

    // ─── 12. Merchant Subscription & Paystack Portal ──────────────────────────
    Route::prefix('pos/subscription')->name('subscription.')->group(function () {
        Route::get('/',                       [SubscriptionController::class, 'index'])->name('index');
        Route::post('/paystack/init',         [SubscriptionController::class, 'initializePaystack'])->name('paystack.init');
        Route::post('/paystack/verify',       [SubscriptionController::class, 'initializePaystack'])->name('paystack.verify');
        Route::post('/offline-submit',        [SubscriptionController::class, 'submitOfflinePayment'])->name('offline.submit');
    });
    Route::get('/pos/subscription-alias',     [SubscriptionController::class, 'index'])->name('pos.subscription.index');

    // ─── 13. Master SaaS Super Admin Platform Panel ───────────────────────────
    Route::prefix('pos/saas')->name('saas.')->group(function () {
        Route::get('/',                       [SaaSAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/tenants',                [SaaSAdminController::class, 'tenants'])->name('tenants');
        Route::get('/activity',               [SaaSAdminController::class, 'activity'])->name('activity');
        Route::get('/settings',               [SaaSAdminController::class, 'settings'])->name('settings');
        Route::get('/invoices',               [SaaSAdminController::class, 'invoices'])->name('invoices');
    });
    Route::get('/pos/saas-alias',             [SaaSAdminController::class, 'dashboard'])->name('pos.saas.dashboard');

    // ─── 14. User Guide & Training Center ─────────────────────────────────────
    Route::get('/pos/help', function () {
        return view('pos::help.index');
    })->name('help.index');
});
