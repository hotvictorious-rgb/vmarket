import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/dio/dio_client.dart';
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/exception/api_error_handler.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/cashback/domain/repositories/cashback_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';

class CashbackRepository implements CashbackRepositoryInterface {
  final DioClient? dioClient;
  CashbackRepository({required this.dioClient});

  @override
  Future<ApiResponseModel> getCashbackSummary() async {
    try {
      final response = await dioClient!.get(AppConstants.customerCashbackSummaryUri);
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponseModel> getCashbackList(int offset, int limit, {String? status}) async {
    try {
      String uri = '${AppConstants.customerCashbackListUri}?limit=$limit&offset=$offset';
      if (status != null && status.isNotEmpty) {
        uri += '&status=$status';
      }
      final response = await dioClient!.get(uri);
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future add(value) => throw UnimplementedError();
  @override
  Future delete(int id) => throw UnimplementedError();
  @override
  Future get(String id) => throw UnimplementedError();
  @override
  Future getList({int? offset = 1}) => throw UnimplementedError();
  @override
  Future update(Map<String, dynamic> body, int id) => throw UnimplementedError();
}