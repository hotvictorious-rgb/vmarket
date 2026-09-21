
import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';

import 'package:flutter_sixvalley_ecommerce/features/product/domain/models/product_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/search_product/domain/models/suggestion_product_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/search_product/domain/services/search_product_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/helper/api_checker.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'package:provider/provider.dart';

class SearchProductController with ChangeNotifier {
  final SearchProductServiceInterface? searchProductServiceInterface;
  SearchProductController({required this.searchProductServiceInterface});

  int _filterIndex = 0;
  List<String> _historyList = [];

  int get filterIndex => _filterIndex;
  List<String> get historyList => _historyList;

  double minPriceForFilter = AppConstants.minFilter;
  double maxPriceForFilter = AppConstants.maxFilter;

  bool _isLoading = false;
  bool get isLoading => _isLoading;

  int _productTypeIndex = 0;
  int get productTypeIndex => _productTypeIndex;

  String? _minPrice;
  String? get minPrice => _minPrice;

  String? _maxPrice;
  String? get maxPrice => _maxPrice;

  void setMinMaxPriceForFilter(RangeValues currentRangeValues){
    minPriceForFilter = currentRangeValues.start;
    maxPriceForFilter = currentRangeValues.end;
    notifyListeners();
  }


  bool _isFilterApplied = false;
  bool _isSortingApplied = false;

  bool get isFilterApplied => _isFilterApplied;
  bool get isSortingApplied => _isSortingApplied;


  void setFilterApply({bool? isFiltered, bool? isSorted, bool isUpdate = true}) {
    if(isFiltered != null) {
      _isFilterApplied = isFiltered;
    }

    if(isSorted != null) {
      _isSortingApplied = isSorted;
    }

    if(isFiltered != null && isSorted != null && isFiltered && isSorted) {
      _minPrice = null;
      _maxPrice = null;
    }

    if(isUpdate) {
      notifyListeners();
    }
  }

  String sortText = 'low-high';
  void setFilterIndex(int index) {
    _filterIndex = index;
    if(index == 0){
      sortText = 'default';
    } else if(index == 1){
      sortText = 'latest';
    } else if(index == 2){
      sortText = 'a-z';
    }else if(index == 3){
      sortText = 'z-a';
    }
    else if(index == 4){
      sortText = 'low-high';
    }else if(index ==5){
      sortText = 'high-low';
    }
    notifyListeners();
  }

  double minFilterValue = 0;
  double maxFilterValue = 0;
  void setFilterValue(double min, double max){
  minFilterValue = min;
  maxFilterValue = max;
  }



  bool _isClear = true;
  bool get isClear => _isClear;

  void cleanSearchProduct({bool notify = false}) {
    // searchedProduct = ProductModel(products: []);
    searchedProduct = null;
    minFilterValue = 0;
    maxFilterValue = 0;
    _isClear = true;
    if(notify){
      notifyListeners();
    }
  }






  ProductModel? searchedProduct;
  Future searchProduct({required String query, String? categoryIds, String? brandIds, String? sort, String? priceMin, String? priceMax, required int offset}) async {
    if(query.isNotEmpty){
      searchController.text = query;
    }

    if(offset == 1) {
      _isLoading = true;
      notifyListeners();
    }

    ApiResponseModel apiResponse = await searchProductServiceInterface!.getSearchProductList(query, categoryIds, brandIds, sort, priceMin, priceMax, offset, _productTypeIndex == 1 ? 'physical' : 'all');
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      if(offset == 1) {
        searchedProduct = null;
        if(ProductModel.fromJson(apiResponse.response!.data).products != null) {
          searchedProduct = ProductModel.fromJson(apiResponse.response!.data);

          if(searchedProduct?.minPrice != null){
            minFilterValue = searchedProduct!.minPrice!;
            _minPrice = searchedProduct!.minPrice!.toString();
          }
          if(searchedProduct?.maxPrice != null){
            maxFilterValue = searchedProduct!.maxPrice!;
            _maxPrice = searchedProduct!.maxPrice!.toString();
          }

          if(priceMax != null&& priceMax.isNotEmpty) {
            _maxPrice = priceMax;
            maxFilterValue = double.tryParse(priceMax) ?? 0;
          }

          if(priceMin != null && priceMin.isNotEmpty) {
            _minPrice = priceMin;
            minFilterValue = double.tryParse(priceMin) ?? 0;
          }

        }
        if(offset == 1) {
          _isLoading = false;
          notifyListeners();
        }
      }else{
        if(ProductModel.fromJson(apiResponse.response!.data).products != null){
          searchedProduct?.products?.addAll(ProductModel.fromJson(apiResponse.response!.data).products!) ;
          searchedProduct?.offset = (ProductModel.fromJson(apiResponse.response!.data).offset) ;
          searchedProduct?.totalSize = (ProductModel.fromJson(apiResponse.response!.data).totalSize) ;
        }
      }
    } else {
      ApiChecker.checkApi( apiResponse);
    }
    notifyListeners();
  }


  TextEditingController searchController = TextEditingController();
  FocusNode searchFocusNode = FocusNode();

  SuggestionModel? suggestionModel;
  List<String> nameList = [];
  List<int> idList = [];
  final Map<String, SuggestionModel> _suggestionCache = {};

  Future<void> getSuggestionProductName(String name) async {
    String trimmedQuery = name.trim().toLowerCase();
    if (trimmedQuery.isEmpty) {
      nameList = [];
      idList = [];
      suggestionModel = null;
      notifyListeners();
      return;
    }

    // Instant local memory response if already searched in this session
    if (_suggestionCache.containsKey(trimmedQuery)) {
      suggestionModel = _suggestionCache[trimmedQuery];
      nameList = [];
      idList = [];
      if (suggestionModel?.products != null) {
        for (int i = 0; i < suggestionModel!.products!.length; i++) {
          nameList.add(suggestionModel!.products![i].name!);
          idList.add(suggestionModel!.products![i].id!);
        }
      }
      notifyListeners();
      return;
    }

    ApiResponseModel apiResponse = await searchProductServiceInterface!.getSearchProductName(name);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      nameList = [];
      idList = [];
      suggestionModel = SuggestionModel.fromJson(apiResponse.response?.data);
      _suggestionCache[trimmedQuery] = suggestionModel!;
      for(int i=0; i< suggestionModel!.products!.length; i++){
        nameList.add(suggestionModel!.products![i].name!);
        idList.add(suggestionModel!.products![i].id!);
      }
    }
    notifyListeners();
  }

  void initHistoryList() {
    _historyList = [];
    _historyList.addAll(searchProductServiceInterface!.getSavedSearchProductName());
  }



  void saveSearchAddress(String searchAddress) async {
    searchProductServiceInterface!.saveSearchProductName(searchAddress);
    if (!_historyList.contains(searchAddress)) {
      _historyList.add(searchAddress);
    }
    notifyListeners();
  }

  void removeSearchAddress(int? index) async {
    _historyList.removeAt(index!);
    searchProductServiceInterface!.clearSavedSearchProductName();
    for(int i =0; i<_historyList.length; i++ ) {
      searchProductServiceInterface!.saveSearchProductName(_historyList[i]);
    }
    notifyListeners();
  }

  void clearSearchAddress() async {
    searchProductServiceInterface!.clearSavedSearchProductName();
    _historyList = [];
    notifyListeners();
  }

  void setInitialFilerData() {
    _filterIndex = 0;
  }

  void setProductTypeIndex(int index, bool notify) {
    _productTypeIndex = index;
    if(notify) {
      notifyListeners();
    }
  }

}
