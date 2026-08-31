<?php

use Illuminate\Support\Facades\Route;
use Modules\Delivery\app\Http\Controllers\DashboardController;
use Modules\Delivery\app\Http\Controllers\FinanceController;
use Modules\Delivery\app\Http\Controllers\FleetController;
use Modules\Delivery\app\Http\Controllers\HubController;
use Modules\Delivery\app\Http\Controllers\RouteController;
use Modules\Delivery\app\Http\Controllers\ShipmentController;

/*
|--------------------------------------------------------------------------
| Victorious MARKET Delivery & Logistics Module Routes
|--------------------------------------------------------------------------
| All routes mapped to /delivery/* and /admin/delivery/*
| Protected by web and admin authentication guards.
*/

$deliveryRouteGroup = function () {
    // 1. Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // 2. Hub Management
    Route::group(['prefix' => 'hubs', 'as' => 'hubs.'], function () {
        Route::get('/', [HubController::class, 'index'])->name('index');
        Route::get('/create', [HubController::class, 'create'])->name('create');
        Route::post('/store', [HubController::class, 'store'])->name('store');
        Route::get('/show/{id}', [HubController::class, 'show'])->name('show');
        Route::post('/update/{id}', [HubController::class, 'update'])->name('update');
        Route::post('/status-toggle', [HubController::class, 'toggleStatus'])->name('status-toggle');
        Route::get('/ajax/cities/{state_id}', [HubController::class, 'ajaxGetCities'])->name('ajax.cities');
    });

    // 3. Corridor Routes & Dynamic Pricing Matrix
    Route::group(['prefix' => 'routes', 'as' => 'routes.'], function () {
        Route::get('/', [RouteController::class, 'index'])->name('index');
        Route::post('/store', [RouteController::class, 'store'])->name('store');
        Route::post('/update/{id}', [RouteController::class, 'update'])->name('update');
        Route::post('/status-toggle', [RouteController::class, 'toggleStatus'])->name('status-toggle');
    });

    // 4. Fleet & 3PL Logistics Partners
    Route::group(['prefix' => 'fleet', 'as' => 'fleet.'], function () {
        Route::get('/', [FleetController::class, 'index'])->name('index');
        Route::post('/company/store', [FleetController::class, 'storeCompany'])->name('company.store');
        Route::get('/rider/{id}', [FleetController::class, 'showRider'])->name('rider.show');
    });

    // 5. Shipments & Consolidated Linehaul Batches
    Route::group(['prefix' => 'shipments', 'as' => 'shipments.'], function () {
        Route::get('/', [ShipmentController::class, 'index'])->name('index');
        Route::post('/batch/create', [ShipmentController::class, 'createBatch'])->name('batch.create');
        Route::get('/waybill/{id}', [ShipmentController::class, 'waybill'])->name('waybill');
    });

    // 6. Financial Reconciliation & Remittances (COD)
    Route::group(['prefix' => 'finance', 'as' => 'finance.'], function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::post('/remittance/record', [FinanceController::class, 'recordRemittance'])->name('remittance.record');
    });
    Route::get('/cod', [FinanceController::class, 'index'])->name('cod.index');
};

// Registered under /delivery prefix (named delivery.*)
Route::group(['prefix' => 'delivery', 'as' => 'delivery.', 'middleware' => ['web', 'admin']], $deliveryRouteGroup);

// Also registered under /admin/delivery prefix as seamless alias
Route::group(['prefix' => 'admin/delivery', 'as' => 'admin.delivery.', 'middleware' => ['web', 'admin']], $deliveryRouteGroup);
