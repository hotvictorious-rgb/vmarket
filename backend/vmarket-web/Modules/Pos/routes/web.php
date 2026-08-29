<?php

use Illuminate\Support\Facades\Route;
use Modules\Pos\app\Http\Controllers\PosController;
use Modules\Pos\app\Http\Controllers\ProductController;
use Modules\Pos\app\Http\Controllers\StockController;
use Modules\Pos\app\Http\Controllers\TransactionController;
use Modules\Pos\app\Http\Controllers\DebtController;
use Modules\Pos\app\Http\Controllers\ReportController;
use Modules\Pos\app\Http\Controllers\DashboardController;
use Modules\Pos\app\Http\Controllers\WarehouseController;

/*
|--------------------------------------------------------------------------
| Pos Module — Web Routes
|--------------------------------------------------------------------------
| All POS routes are prefixed /pos and protected by the seller guard.
| Unverified merchants (marketplace_status='pos_only') can access POS fully.
| Verified merchants (marketplace_status='approved') additionally list on marketplace.
|
| Middleware stack:
|   - web         : session, CSRF, cookie
|   - auth:seller : Vmarket seller guard (Seller model)
|   - pos.access  : checks can_access_pos & marketplace_status != 'suspended'
|
| [AI] Clients: Verified Merchant POS, Unverified Merchant Free POS.
*/

Route::prefix('pos')->name('pos.')->middleware(['web', 'auth:seller'])->group(function () {

    // ─── Dashboard ───────────────────────────────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ─── POS Terminal ─────────────────────────────────────────────────────────
    Route::get('/terminal', [PosController::class, 'index'])->name('index');
    Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
    Route::get('/receipt/{id}', [PosController::class, 'receipt'])->name('receipt');
    Route::get('/returns', [PosController::class, 'returns'])->name('returns');
    Route::post('/returns/process', [PosController::class, 'processReturn'])->name('returns.process');
    Route::post('/customer/quick-register', [PosController::class, 'quickRegisterCustomer'])->name('customer.quick-register');

    // ─── Products (POS Catalog) ───────────────────────────────────────────────
    Route::get('products/template/csv', [ProductController::class, 'downloadCsvTemplate'])->name('products.template.csv');
    Route::get('products/export/csv', [ProductController::class, 'exportCsv'])->name('products.export.csv');
    Route::get('products/export/json', [ProductController::class, 'exportJson'])->name('products.export.json');
    Route::post('products/import/csv', [ProductController::class, 'importCsv'])->name('products.import.csv');

    Route::resource('products', ProductController::class)->except(['show']);

    // ─── Warehouses / Branches ────────────────────────────────────────────────
    Route::get('warehouses/ajax/cities/{state_id}', [WarehouseController::class, 'getCitiesAjax'])->name('warehouses.cities-ajax');
    Route::get('warehouses/ajax/hubs/{city_id}', [WarehouseController::class, 'getHubsAjax'])->name('warehouses.hubs-ajax');
    Route::resource('warehouses', WarehouseController::class);

    // ─── Stock Management ─────────────────────────────────────────────────────
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/', [StockController::class, 'index'])->name('index');
        Route::get('/in', [StockController::class, 'stockInForm'])->name('in.form');
        Route::post('/in', [StockController::class, 'stockIn'])->name('in');
        Route::get('/transfers', [StockController::class, 'transfers'])->name('transfers');
        Route::post('/transfers', [StockController::class, 'createTransfer'])->name('transfers.create');
        Route::get('/adjustments', [StockController::class, 'adjustments'])->name('adjustments');
        Route::post('/adjustments', [StockController::class, 'createAdjustment'])->name('adjustments.create');
    });

    // ─── Transactions (Sales History, Ledgers) ────────────────────────────────
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/cashier-shifts', [TransactionController::class, 'cashierShifts'])->name('cashier-shifts');
        Route::get('/inventory-log', [TransactionController::class, 'inventoryLog'])->name('inventory-log');
        Route::get('/export', [TransactionController::class, 'export'])->name('export');
    });

    // ─── Debt Ledger ─────────────────────────────────────────────────────────
    Route::prefix('debts')->name('debts.')->group(function () {
        Route::get('/', [DebtController::class, 'index'])->name('index');
        Route::get('/customer/{id}', [DebtController::class, 'customerLedger'])->name('customer');
        Route::post('/payment', [DebtController::class, 'recordPayment'])->name('payment');
        Route::get('/export', [DebtController::class, 'export'])->name('export');
    });

    // ─── Reports ──────────────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/top-products', [ReportController::class, 'topProducts'])->name('top-products');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
    });
});
