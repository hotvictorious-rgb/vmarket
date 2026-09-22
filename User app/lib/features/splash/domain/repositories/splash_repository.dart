import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/dio/dio_client.dart';
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/exception/api_error_handler.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/domain/repositories/splash_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/services/storage_service.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';

class SplashRepository implements SplashRepositoryInterface{
  final DioClient? dioClient;
  final StorageService storageService;
  SplashRepository({required this.dioClient, required this.storageService});

  @override
  Future<ApiResponseModel> getConfig() async {
    try {
      final response = await dioClient!.get('/api/v1/feed/sync');
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponseModel> getBusinessPages(String type) async {
    try {
      final response = await dioClient!.get(AppConstants.businessPagesUri+type);
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e));
    }
  }


  @override
  void initSharedData() async {
    if (!storageService.containsKey(AppConstants.intro)) {
      await storageService.setBool(AppConstants.intro, true);
    }
    if(!storageService.containsKey(AppConstants.currency)) {
      await storageService.setString(AppConstants.currency, '');
    }
  }

  @override
  String getCurrency() {
    return storageService.getString(AppConstants.currency) ?? '';
  }

  @override
  void setCurrency(String currencyCode) {
    storageService.setString(AppConstants.currency, currencyCode);
  }

  @override
  void disableIntro() {
    storageService.setBool(AppConstants.intro, false);
  }

  @override
  bool? showIntro() {
    return storageService.getBool(AppConstants.intro);
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
  Future getList({int? offset = 1}) {
    // TODO: implement getList
    throw UnimplementedError();
  }

  @override
  Future update(Map<String, dynamic> body, int id) {
    // TODO: implement update
    throw UnimplementedError();
  }




}
