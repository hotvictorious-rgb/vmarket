<?php

use App\Http\Controllers\Logistics\Auth\LoginController;
use App\Http\Controllers\Logistics\DashboardController;
use App\Http\Controllers\Logistics\OrderController;
use App\Http\Controllers\Logistics\RiderController;
use App\Http\Controllers\Logistics\WalletController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'logistics', 'as' => 'logistics.'], function () {

    // Guest Authentication Routes
    Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
        Route::get('login', [LoginController::class, 'login'])->name('login');
        Route::post('login', [LoginController::class, 'submitLogin'])->name('login.post');
        Route::get('register', [LoginController::class, 'register'])->name('register');
        Route::post('register', [LoginController::class, 'submitRegister'])->name('register.post');
        Route::get('logout', [LoginController::class, 'logout'])->name('logout');
    });

    // Authenticated Portal Routes
    Route::group(['middleware' => ['auth:logistics']], function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Fleet / Riders Management
        Route::group(['prefix' => 'riders', 'as' => 'riders.'], function () {
            Route::get('/', [RiderController::class, 'index'])->name('index');
            Route::get('create', [RiderController::class, 'create'])->name('create');
            Route::post('store', [RiderController::class, 'store'])->name('store');
            Route::get('edit/{id}', [RiderController::class, 'edit'])->name('edit');
            Route::post('update/{id}', [RiderController::class, 'update'])->name('update');
            Route::post('status', [RiderController::class, 'status'])->name('status');
        });

        // Orders & Dispatches
        Route::group(['prefix' => 'orders', 'as' => 'orders.'], function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('show/{id}', [OrderController::class, 'show'])->name('show');
            Route::post('assign-rider', [OrderController::class, 'assignRider'])->name('assign-rider');
        });

        // Financial Ledger & Wallet
        Route::group(['prefix' => 'wallet', 'as' => 'wallet.'], function () {
            Route::get('/', [WalletController::class, 'index'])->name('index');
            Route::post('withdraw', [WalletController::class, 'requestWithdraw'])->name('withdraw');
        });
    });
});
