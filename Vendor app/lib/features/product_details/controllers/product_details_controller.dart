import 'package:flutter/material.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_vendor_app/data/model/response/base/api_response.dart';
import 'package:sixvalley_vendor_app/features/product/domain/models/product_model.dart';
import 'package:sixvalley_vendor_app/features/product_details/domain/services/product_details_service_interface.dart';
import 'package:sixvalley_vendor_app/helper/api_checker.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/main.dart';

class ProductDetailsController extends ChangeNotifier{

  final ProductDetailsServiceInterface productDetailsServiceInterface;
  ProductDetailsController({required this.productDetailsServiceInterface});

  bool _isLoading = false;
  bool get isLoading => _isLoading;
  Product? _productDetails;
  Product? get productDetails => _productDetails;

  bool _isShowMoreActive = false;
  String? _visibleProductDescription;
  String? get visibleProductDescription => _visibleProductDescription;



  Future<void> getProductDetails(int? productId) async {
    _isLoading = true;
    ApiResponse apiResponse = await productDetailsServiceInterface.getProductDetails(productId);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      _productDetails = Product.fromJson(apiResponse.response!.data);
      _isLoading = false;
    } else {
      _isLoading = false;
      ApiChecker.checkApi(apiResponse);
    }
    notifyListeners();
  }

  Future<void> productStatusOnOff( BuildContext context, int? productId, int status) async {
    ApiResponse apiResponse = await productDetailsServiceInterface.productStatusOnOff(productId, status);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      _productDetails!.status = status;
      showCustomSnackBarWidget(getTranslated('status_updated_successfully', Get.context!), Get.context!, isError: false);
      getProductDetails(productId);
    } else {
      ApiChecker.checkApi(apiResponse);
    }
    notifyListeners();
  }


  void updateVisibleProductDescription(String description, {bool isInitialize = false, bool isUpdate = true}) {

    if (isInitialize) {
      _isShowMoreActive = false;
    }

    if(description.length > 300){
      _isShowMoreActive = !_isShowMoreActive;
    }

    if (description.length > 300 && _isShowMoreActive) {
      _visibleProductDescription = '${description.substring(0, 300)} <span style="color: cornflowerblue;  font-size: 12px;"> ... Show more</span>';
    } else if (description.length > 300 && !_isShowMoreActive) {
      _visibleProductDescription = '${description.trim()} <span style="color: cornflowerblue; font-size: 12px;"> Show less</span>';
    } else {
      _visibleProductDescription = description;
    }

    if(isUpdate){
      notifyListeners();
    }
  }





}