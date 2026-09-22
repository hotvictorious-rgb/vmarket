
import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/dio/dio_client.dart';
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/exception/api_error_handler.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/repositories/checkout_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'dart:async';
import 'package:provider/provider.dart';

class CheckoutRepository implements CheckoutRepositoryInterface{
  final DioClient? dioClient;
  CheckoutRepository({required this.dioClient});









  @override
  Future<ApiResponseModel> digitalPaymentPlaceOrder(
      String? orderNote,
      String? customerId,
      String? addressId,
      String? billingAddressId,
      String? paymentMethod,
      bool? isCheckCreateAccount,
      String? password,
      {bool useCashback = false}
      ) async {

    try {
      int isCheckAccount = isCheckCreateAccount! ? 1: 0;
      final response = await dioClient!.post(AppConstants.digitalPayment, data: {
        "order_note": orderNote,
        "customer_id":  customerId,
        "address_id": addressId,
        "billing_address_id": billingAddressId,
        "use_cashback": useCashback ? 1 : 0,
        "payment_platform" : "app",
        "payment_method" : paymentMethod ?? "paystack",
        "callback" : null,
        "payment_request_from" : "app",
        'guest_id' : Provider.of<AuthController>(Get.context!, listen: false).getGuestToken(),
        'is_guest': !Provider.of<AuthController>(Get.context!, listen: false).isLoggedIn(),
        'is_check_create_account' : isCheckAccount.toString(),
        'password' : password,
      });
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      final error = e as DioException;
      return ApiResponseModel.withError( ApiErrorHandler.getMessage(e), responseValue: (error.response) );
    }
  }

  @override
  Future<ApiResponseModel> getReferralAmount(String? amount) async {
    try {
      final response = await dioClient!.post(
        AppConstants.referralAmountUri,
        data : {'coupon_discount' : amount}
      );
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponseModel> createPickupReservation({
    required String idempotencyKey,
    List<int>? cartIds,
    bool? checkedOnly,
  }) async {
    try {
      final response = await dioClient!.post(
        AppConstants.pickupReservationsUri,
        data: {
          'idempotency_key': idempotencyKey,
          'cart_ids': cartIds,
          'checked_only': checkedOnly,
        },
      );
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      final error = e as DioException;
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e), responseValue: (error.response));
    }
  }

  @override
  Future<ApiResponseModel> payPickupReservation({
    required String reservationCode,
    bool useCashback = false,
    String paymentGateway = 'paystack',
    int ttlMinutes = 30,
  }) async {
    try {
      final response = await dioClient!.post(
        '${AppConstants.pickupReservationsUri}/$reservationCode/pay',
        data: {
          'use_cashback': useCashback ? 1 : 0,
          'payment_gateway': paymentGateway,
          'ttl_minutes': ttlMinutes,
        },
      );
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      final error = e as DioException;
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e), responseValue: (error.response));
    }
  }

  // [AI] Authoritative Fulfillment & Delivery Intent Methods
  @override
  Future<ApiResponseModel> checkFulfillmentAvailability({
    required int shopId,
    int? shippingAddressId,
    List<Map<String, dynamic>>? cartItems,
  }) async {
    try {
      final response = await dioClient!.post(
        AppConstants.fulfillmentAvailabilityUri,
        data: {
          'shop_id': shopId,
          if (shippingAddressId != null) 'shipping_address_id': shippingAddressId,
          if (cartItems != null) 'cart_items': cartItems,
        },
      );
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      final error = e as DioException;
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e), responseValue: (error.response));
    }
  }

  @override
  Future<ApiResponseModel> createDeliveryCheckoutIntent({
    required int addressId,
    required String idempotencyKey,
    int? billingAddressId,
    bool useCashback = false,
    List<int>? cartItemIds,
  }) async {
    try {
      final response = await dioClient!.post(
        AppConstants.checkoutIntentUri,
        data: {
          'address_id': addressId,
          'idempotency_key': idempotencyKey,
          if (billingAddressId != null) 'billing_address_id': billingAddressId,
          'use_cashback': useCashback,
          if (cartItemIds != null) 'cart_item_ids': cartItemIds,
        },
      );
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      final error = e as DioException;
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e), responseValue: (error.response));
    }
  }

  @override
  Future<ApiResponseModel> initializeIntentPayment({
    required String orderGroupId,
  }) async {
    try {
      final response = await dioClient!.post(
        '${AppConstants.checkoutIntentPayUri}$orderGroupId/pay',
        data: {
          'payment_method': 'paystack',
        },
      );
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      final error = e as DioException;
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e), responseValue: (error.response));
    }
  }


  @override
  Future add(value) {
    // TODO: implement add
    throw UnimplementedError();
  }

  @override
  Future delete(int id) {
    // TODO: implement delete
    throw UnimplementedError();
  }

  @override
  Future get(String id) {
    // TODO: implement get
    throw UnimplementedError();
  }

  @override
  Future getList({int? offset}) {
    // TODO: implement getList
    throw UnimplementedError();
  }

  @override
  Future update(Map<String, dynamic> body, int id) {
    // TODO: implement update
    throw UnimplementedError();
  }
}
