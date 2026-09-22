
import 'dart:convert';
import 'dart:developer';
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/dio/dio_client.dart';
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/exception/api_error_handler.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/search_product/domain/repositories/search_product_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/services/storage_service.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';

class SearchProductRepository implements SearchProductRepositoryInterface{
  final DioClient? dioClient;
  final StorageService storageService;
  SearchProductRepository({required this.dioClient, required this.storageService});

  @override
  Future<ApiResponseModel> getSearchProductList(String query, String? categoryIds, String? brandIds, String? sort, String? priceMin, String? priceMax, int offset, String? productType) async {

    try {
      log("===limit==>" );
      final response = await dioClient!.post(AppConstants.searchUri,
        data: {
          'search' : base64.encode(utf8.encode(query)),
          'category': categoryIds??'[]',
          'brand' : brandIds??'[]',
          'sort_by': sort,
          'price_min' : priceMin,
          'price_max' : priceMax,
          'limit' : '20',
          'offset' : offset,
          'guest_id' : '1',
          'product_type' : productType ?? 'all'
        }
      );
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e));
    }
  }


  @override
  Future<ApiResponseModel> getSearchProductName(String name) async {
    try {
      final response = await dioClient!.get('${AppConstants.getSuggestionProductName}$name&limit=10&&offset=1');
      return ApiResponseModel.withSuccess(response);
    } catch (e) {
      return ApiResponseModel.withError(ApiErrorHandler.getMessage(e));
    }
  }


  @override
  Future<void> saveSearchProductName(String searchAddress) async {
    try {
      List<String> searchKeywordList = storageService.getStringList(AppConstants.searchProductName) ?? [];
      if (!searchKeywordList.contains(searchAddress)) {
        searchKeywordList.add(searchAddress);
      }
      await storageService.setStringList(AppConstants.searchProductName, searchKeywordList);
    } catch (e) {
      rethrow;
    }
  }

  @override
  List<String> getSavedSearchProductName() {
    return storageService.getStringList(AppConstants.searchProductName) ?? [];
  }

  @override
  Future<bool> clearSavedSearchProductName() async {
    await storageService.setStringList(AppConstants.searchProductName, []);
    return true;
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
