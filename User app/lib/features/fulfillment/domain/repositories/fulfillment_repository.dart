import 'package:dio/dio.dart';
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/dio/dio_client.dart';
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/exception/api_error_handler.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/domain/repositories/fulfillment_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';

class FulfillmentRepository implements FulfillmentRepositoryInterface {
  final DioClient dioClient;
  FulfillmentRepository({required this.dioClient});

  @override
  Future<ApiResponseModel> checkAvailability({
    required int shopId,
    int? shippingAddressId,
    List<Map<String, dynamic>>? cartItems,
  }) async {
    try {
      final response = await dioClient.post(
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
  Future<ApiResponseModel> getDeliveryFee({
    required int shopId,
    required int shippingAddressId,
    List<Map<String, dynamic>>? cartItems,
  }) async {
    try {
      final response = await dioClient.post(
        AppConstants.fulfillmentDeliveryFeeUri,
        data: {
          'shop_id': shopId,
          'shipping_address_id': shippingAddressId,
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
  Future add(value) => throw UnimplementedError();

  @override
  Future delete(int id) => throw UnimplementedError();

  @override
  Future get(String id) => throw UnimplementedError();

  @override
  Future getList({int? offset}) => throw UnimplementedError();

  @override
  Future update(Map<String, dynamic> body, int id) => throw UnimplementedError();
}
