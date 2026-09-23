<?php

use App\Http\Controllers\RestAPI\v2\delivery_man\auth\LoginController as DeliveryManLoginController;
use App\Http\Controllers\RestAPI\v2\delivery_man\DeliveryManController;
use App\Http\Controllers\RestAPI\v2\delivery_man\WithdrawController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Delivery Mobile APP API Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'v2', 'middleware' => ['api_lang']], function () {

    Route::group(['prefix' => 'delivery-man'], function () {

        Route::group(['prefix' => 'auth', 'middleware' => ['throttle:10,1']], function () {
            Route::controller(DeliveryManLoginController::class)->group(function () {
                Route::post('login', 'login');
                Route::post('forgot-password', 'reset_password_request');
                Route::post('verify-otp', 'otp_verification_submit');
                Route::post('reset-password', 'reset_password_submit');
            });
        });

        Route::group(['middleware' => ['delivery_man_auth', 'actch:deliveryman_app']], function () {
            Route::controller(DeliveryManController::class)->group(function () {
                Route::put('language-change', 'language_change');
                Route::put('is-online', 'is_online');
                Route::get('info', 'info');
                Route::post('distance-api', 'distance_api');
                Route::get('current-orders', 'get_current_orders');
                Route::get('all-orders', 'get_all_orders');
                Route::post('record-location-data', 'record_location_data');
                Route::get('order-delivery-history', 'get_order_history');
                Route::put('update-order-status', 'update_order_status');
                Route::put('update-expected-delivery', 'update_expected_delivery');
                Route::put('order-update-is-pause', 'order_update_is_pause');
                Route::get('order-item', 'getOrderItem');
                Route::get('order-details', 'get_order_details');
                Route::get('last-location', 'get_last_location');
                Route::put('update-fcm-token', 'update_fcm_token');

                Route::get('delivery-wise-earned', 'delivery_wise_earned');
                Route::get('order-list-by-date', 'order_list_date_filter');
                Route::get('search', 'search');
                Route::get('profile-dashboard-counts', 'profile_dashboard_counts');
                Route::put('update-info', 'update_info');
                Route::put('bank-info', 'bank_info');
                Route::get('review-list', 'review_list');
                Route::put('save-review', 'is_saved');
                Route::get('emergency-contact-list', 'emergency_contact_list');
                Route::get('notifications', 'get_all_notification');
                Route::post('resend-verification-code', 'resend_verification_code');
                Route::post('order-delivery-verification', 'order_delivery_verification');
            });

            // [AI] OTP brute-force protection: 5 attempts per minute per IP
            Route::middleware('throttle:5,1')->group(function () {
                Route::controller(DeliveryManController::class)->group(function () {
                    Route::post('change-status', 'change_status');
                    Route::post('verify-order-delivery-otp', 'verify_order_delivery_otp');
                });
            });

            Route::controller(WithdrawController::class)->group(function () {
                Route::post('withdraw-request', 'sendWithdrawRequest');
                Route::get('withdraw-list-by-approved', 'getWithdrawListByApproved');
            });
        });

    });
});

