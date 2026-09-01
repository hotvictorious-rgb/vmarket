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

/**
 * [AI] Victorious MARKET — In-Store POS & SaaS Module Routes
 *
 * Business Context: Omnichannel in-store point of sale, physical barcode scanning,
 *                   branch inventory synchronization, cashier shifts, debt recovery,
 *                   and multi-branch SaaS management.
 *
 * @role_access       Super Admin (Role 1), Verified Merchant (Role 3), Unverified Merchant (Role 4),
 *                    Verified Staff (Role 5), Unverified Staff (Role 6)
 * @security_checks   Multi-Guard pos.access, Zero-Trust IDOR ($sellerId scope), Pessimistic Concurrency Locks
 * @financial_math    Real-time inventory decrement (Delta = 0.00), Split Payments, Zero Duplicate Writes
 */

Route::middleware(['web', 'pos.access'])->group(function () {

    // ─── 0. Single Sign-On (SSO) Return & Logout Hub ─────────────────────────
    Route::get('/pos-sso-return', function () {
        if (Auth::guard('seller')->check() || Auth::guard('vendor_employee')->check()) {
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
        } elseif (Auth::guard('vendor_employee')->check()) {
            Auth::guard('vendor_employee')->logout();
        } elseif (Auth::guard('admin')->check()) {
            Auth::guard('admin')->logout();
            session()->flush();
            $adminLoginUrl = getWebConfig(name: 'admin_login_url') ?: 'admin';
            return redirect('login/' . $adminLoginUrl);
        }
        session()->flush();
        return redirect()->route('vendor.auth.login');
    })->name('pos.logout');

    // ─── 1. Executive POS Dashboard ──────────────────────────────────────────
    Route::get('/pos',           [DashboardController::class, 'index'])->name('pos.dashboard');
    Route::get('/pos/dashboard', [DashboardController::class, 'index'])->name('pos.dashboard.index');

    // ─── 2. POS Visual Counter & Barcode Register ────────────────────────────
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/terminal',                   [PosController::class, 'index'])->name('index');
        Route::post('/checkout',                  [PosController::class, 'checkout'])->name('checkout');
        Route::post('/customer/quick-register',   [PosController::class, 'quickRegisterCustomer'])->name('customer.quick_register');
        Route::get('/receipt/{id}',               [PosController::class, 'receipt'])->name('receipt');
        Route::get('/returns',                    [PosController::class, 'returns'])->name('returns');
        Route::post('/returns',                   [PosController::class, 'processReturn'])->name('returns.process');
        Route::get('/product/{id}/branch-stocks', [PosController::class, 'getBranchStocks'])->name('product.branch_stocks');
    });

    // ─── 3. In-Store Products Catalog ────────────────────────────────────────
    Route::prefix('pos/products')->name('pos.products.')->group(function () {
        Route::get('/',                 [ProductController::class, 'index'])->name('index');
        Route::get('/create',           [ProductController::class, 'create'])->name('create');
        Route::get('/{id}/edit',        [ProductController::class, 'edit'])->name('edit');
        Route::get('/template/csv',     [ProductController::class, 'downloadCsvTemplate'])->name('template.csv');
        Route::get('/export/csv',       [ProductController::class, 'exportCsv'])->name('export.csv');
        Route::get('/export/json',      [ProductController::class, 'exportJson'])->name('export.json');
        Route::post('/import/csv',      [ProductController::class, 'importCsv'])->name('import.csv');
        Route::post('/',                [ProductController::class, 'store'])->name('store');
        Route::post('/{id}',            [ProductController::class, 'update'])->name('update');
        Route::put('/{id}',             [ProductController::class, 'update'])->name('update.put');
        Route::post('/{id}/delete',     [ProductController::class, 'destroy'])->name('destroy');
        Route::delete('/{id}',          [ProductController::class, 'destroy'])->name('destroy.delete');
    });

    // ─── 4. Warehouses / Physical Store Branches ─────────────────────────────
    Route::prefix('pos/warehouses')->name('pos.warehouses.')->group(function () {
        Route::get('/ajax/cities/{state_id}', [WarehouseController::class, 'getCitiesAjax'])->name('cities-ajax');
        Route::get('/ajax/hubs/{city_id}',    [WarehouseController::class, 'getHubsAjax'])->name('hubs-ajax');
        Route::get('/',                       [WarehouseController::class, 'index'])->name('index');
        Route::get('/create',                 [WarehouseController::class, 'create'])->name('create');
        Route::post('/',                      [WarehouseController::class, 'store'])->name('store');
        Route::get('/{warehouse}',            [WarehouseController::class, 'show'])->name('show');
        Route::get('/{warehouse}/edit',       [WarehouseController::class, 'edit'])->name('edit');
        Route::post('/{warehouse}',           [WarehouseController::class, 'update'])->name('update');
        Route::put('/{warehouse}',            [WarehouseController::class, 'update'])->name('update.put');
        Route::delete('/{warehouse}',         [WarehouseController::class, 'destroy'])->name('destroy');
        Route::post('/{warehouse}/delete',    [WarehouseController::class, 'destroy'])->name('destroy.post');
    });

    // ─── 5. Physical Stock Hub (Goods In, Transfers, Dispatch, Adjustments) ──
    Route::prefix('pos/stock')->name('pos.stock.')->group(function () {
        Route::get('/',                        [StockController::class, 'index'])->name('index');
        Route::get('/transfers',               [StockController::class, 'transfers'])->name('transfers');
        Route::get('/waybill/{id}',            [StockController::class, 'waybill'])->name('waybill');
        Route::get('/in',                      [StockController::class, 'stockInForm'])->name('in.form');
        Route::post('/in',                     [StockController::class, 'stockIn'])->name('in');
        Route::post('/transfer-out',           [StockController::class, 'createTransfer'])->name('transfer.out');
        Route::post('/transfer-in/{id}',       [StockController::class, 'createTransfer'])->name('transfer.in');
        Route::post('/transfers/{id}/receive', [StockController::class, 'createTransfer'])->name('transfers.receive');
        Route::post('/transfer-recall/{id}',   [StockController::class, 'createTransfer'])->name('transfer.recall');
        Route::get('/unsupplied',              [StockController::class, 'unsuppliedOrders'])->name('unsupplied');
        Route::post('/dispatch/{saleId}',      [StockController::class, 'dispatchConfirm'])->name('dispatch');
        Route::get('/adjustments',             [StockController::class, 'adjustments'])->name('adjustments');
        Route::post('/adjustments',            [StockController::class, 'createAdjustment'])->name('adjustments.record');
    });

    // ─── 6. Reports & AI Data Export Hub ─────────────────────────────────────
    Route::prefix('pos/reports')->name('pos.reports.')->group(function () {
        Route::get('/',                      [ReportController::class, 'index'])->name('index');
        Route::get('/export-csv/{type}',     [ReportController::class, 'exportCsv'])->name('export.csv');
        Route::get('/export-json/{type}',    [ReportController::class, 'exportJson'])->name('export.json');
    });

    // ─── 7. Auditor Anti-Theft & Reconciliation Hub ──────────────────────────
    Route::prefix('pos/auditor')->name('pos.auditor.')->group(function () {
        Route::get('/',                      [AuditorController::class, 'index'])->name('index');
    });

    // ─── 8. Debt & Credit Recovery Hub ───────────────────────────────────────
    Route::prefix('pos/debts')->name('pos.debts.')->group(function () {
        Route::get('/',                      [DebtController::class, 'index'])->name('index');
        Route::post('/pay/{id?}',            [DebtController::class, 'recordPayment'])->name('pay');
        Route::post('/payment/{id?}',        [DebtController::class, 'recordPayment'])->name('payment');
        Route::get('/export',                [DebtController::class, 'export'])->name('export');
    });

    // ─── 9. Wholesale Operations & Commercial Invoicing Hub ──────────────────
    Route::prefix('pos/wholesale')->name('pos.wholesale.')->group(function () {
        Route::get('/',                      [WholesaleController::class, 'index'])->name('index');
        Route::post('/price/{id}',           [WholesaleController::class, 'priceOrder'])->name('price');
        Route::get('/invoice/{id}',          [WholesaleController::class, 'commercialInvoice'])->name('invoice');
    });

    // ─── 10. Transactions History & Audit Trail (Exportable) ─────────────────
    Route::prefix('pos/transactions')->name('pos.transactions.')->group(function () {
        Route::get('/',                      [TransactionController::class, 'index'])->name('index');
        Route::get('/export-csv/{tab}',      [TransactionController::class, 'exportCsv'])->name('export.csv');
        Route::get('/export-json/{tab}',     [TransactionController::class, 'exportJson'])->name('export.json');
        Route::get('/inventory-log',         [TransactionController::class, 'inventoryLog'])->name('inventory-log');
        Route::get('/cashier-shifts',        [TransactionController::class, 'cashierShifts'])->name('cashier-shifts');
    });

    // ─── 11. Store Workers & Role Permissions Hub ────────────────────────────
    Route::prefix('pos/users')->name('pos.users.')->group(function () {
        Route::get('/',                      [UserController::class, 'index'])->name('index');
        Route::post('/',                     [UserController::class, 'store'])->name('store');
        Route::post('/update/{id}',          [UserController::class, 'update'])->name('update');
        Route::post('/toggle/{id}',          [UserController::class, 'toggleStatus'])->name('toggle');
        Route::post('/reset-password/{id}',  [UserController::class, 'resetPassword'])->name('reset.password');
    });

    // ─── 12. POS System Settings Hub ─────────────────────────────────────────
    Route::prefix('pos/settings')->name('pos.settings.')->group(function () {
        Route::get('/',                       [SettingController::class, 'index'])->name('index');
        Route::post('/',                      [SettingController::class, 'update'])->name('update');
        Route::post('/warehouse',             [SettingController::class, 'storeWarehouse'])->name('warehouse.store');
        Route::post('/warehouse/update/{id}', [SettingController::class, 'updateWarehouse'])->name('warehouse.update');
        Route::post('/warehouse/toggle/{id}', [SettingController::class, 'toggleWarehouse'])->name('warehouse.toggle');
    });

    // ─── 13. Merchant Subscription & Paystack Portal ─────────────────────────
    Route::prefix('pos/subscription')->name('pos.subscription.')->group(function () {
        Route::get('/',                       [SubscriptionController::class, 'index'])->name('index');
        Route::post('/paystack/init',         [SubscriptionController::class, 'initializePaystack'])->name('paystack.init');
        Route::post('/paystack/verify',       [SubscriptionController::class, 'initializePaystack'])->name('paystack.verify');
        Route::post('/offline-submit',        [SubscriptionController::class, 'submitOfflinePayment'])->name('offline.submit');
    });

    // ─── 14. Master SaaS Super Admin Platform Panel ──────────────────────────
    Route::prefix('pos/saas')->name('pos.saas.')->group(function () {
        Route::get('/',                       [SaaSAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/tenants',                [SaaSAdminController::class, 'tenants'])->name('tenants');
        Route::post('/tenants',               [SaaSAdminController::class, 'storeTenant'])->name('tenants.store');
        Route::post('/tenants/{id}/plan',     [SaaSAdminController::class, 'updateTenantPlan'])->name('tenants.plan');
        Route::get('/activity',               [SaaSAdminController::class, 'activity'])->name('activity');
        Route::get('/settings',               [SaaSAdminController::class, 'settings'])->name('settings');
        Route::post('/settings',              [SaaSAdminController::class, 'updateSettings'])->name('settings.update');
        Route::get('/invoices',               [SaaSAdminController::class, 'invoices'])->name('invoices');
        Route::post('/invoices/{id}/approve', [SaaSAdminController::class, 'approveInvoice'])->name('invoices.approve');
        Route::post('/invoices/{id}/reject',  [SaaSAdminController::class, 'rejectInvoice'])->name('invoices.reject');
    });

    // ─── 15. User Guide & Training Center ────────────────────────────────────
    Route::get('/pos/help', function () {
        return view('pos::help.index');
    })->name('pos.help.index');
});
