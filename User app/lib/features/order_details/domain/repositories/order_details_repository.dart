import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/dio/dio_client.dart';
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/exception/api_error_handler.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/domain/repositories/order_details_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'package:provider/provider.dart';

class OrderDetailsRepository implements OrderDetailsRepositoryInterface{
  final DioClient? dioClient;
  OrderDetailsRepository({required this.dioClient});

  @override
  Future<ApiResponseModel> get(String orderID) async {
    try {
      final response = await dioClient!.get(AppConstants.orderDetailsUri+orderID);
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponseModel> getOrderFromOrderId(String orderID) async {
    try {
      final response = await dioClient!.get('${AppConstants.getOrderFromOrderId}$orderID&guest_id=${Provider.of<AuthController>(Get.context!, listen: false).getGuestToken()}');
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e));
    }
  }


  @override
  Future<ApiResponseModel> trackYourOrder(String orderId, String phoneNumber) async {
    try {
      final response = await dioClient!.post(
        AppConstants.orderTrack,
        data: {
          'order_id': orderId,
          'phone_number' : phoneNumber
        }
      );
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e));
    }
  }

  Future<ApiResponseModel> reorder(String orderId) async {
    try {
      final response = await dioClient!.post(AppConstants.reorder,
          data: {'order_id': orderId,
          });
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponseModel> getTrackOrderDetailsId(String orderID) async {
    try {
      final response = await dioClient!.get('${AppConstants.orderDetailsTrack}$orderID');
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e));
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
  Future getList({int? offset = 1}) {
    // TODO: implement getList
    throw UnimplementedError();
  }

  @override
  Future update(Map<String, dynamic> body, int id) {
    // TODO: implement update
    throw UnimplementedError();
  }

  @override
  Future getOrderInvoice(String orderID) async{
    try {
      final response = await dioClient!.get('${AppConstants.generateInvoice}$orderID');
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<http.StreamedResponse> confirmInShopPickup({
    required int orderId,
    required String pickupCode,
    required dynamic verificationImage,
  }) async {
    http.MultipartRequest request = http.MultipartRequest(
      'POST',
      Uri.parse('${AppConstants.baseUrl}${AppConstants.confirmInShopPickupUri}'),
    );
    request.headers.addAll(<String, String>{
      'Authorization': 'Bearer ${Provider.of<AuthController>(Get.context!, listen: false).getUserToken()}',
      'Accept': 'application/json',
    });
    request.fields.addAll(<String, String>{
      'order_id': orderId.toString(),
      'pickup_code': pickupCode,
    });
    if (verificationImage is File) {
      request.files.add(await http.MultipartFile.fromPath('verification_image', verificationImage.path));
    }
    return await request.send();
  }

  @override
  Future<http.StreamedResponse> confirmDoorstepDelivery({
    required int orderId,
    required String deliveryCode,
    required dynamic verificationImage,
  }) async {
    http.MultipartRequest request = http.MultipartRequest(
      'POST',
      Uri.parse('${AppConstants.baseUrl}${AppConstants.confirmDoorstepDeliveryUri}'),
    );
    request.headers.addAll(<String, String>{
      'Authorization': 'Bearer ${Provider.of<AuthController>(Get.context!, listen: false).getUserToken()}',
      'Accept': 'application/json',
    });
    request.fields.addAll(<String, String>{
      'order_id': orderId.toString(),
      'delivery_code': deliveryCode,
    });
    if (verificationImage is File) {
      request.files.add(await http.MultipartFile.fromPath('verification_image', verificationImage.path));
    }
    return await request.send();
  }

}
