import 'dart:io';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/show_custom_snakbar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/domain/models/order_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/domain/models/order_details_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/domain/models/track_order_details_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/domain/services/order_details_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/review/controllers/review_controller.dart';
import 'package:flutter_sixvalley_ecommerce/helper/api_checker.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'package:image_picker/image_picker.dart';
import 'package:open_file/open_file.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:provider/provider.dart';
import 'package:path/path.dart' as path;




class OrderDetailsController with ChangeNotifier {
  final OrderDetailsServiceInterface orderDetailsServiceInterface;
  OrderDetailsController({required this.orderDetailsServiceInterface});

  bool _isLoading = false;
  bool get isLoading => _isLoading;

  XFile? _imageFile;
  XFile? get imageFile => _imageFile;
  List <XFile?>_refundImage = [];
  List<XFile?> get refundImage => _refundImage;
  List<File> reviewImages = [];
  bool _isInvoiceLoading = false;
  bool get isInvoiceLoading => _isInvoiceLoading;



  List<OrderDetailsModel>? _orderDetails;
  List<OrderDetailsModel>? get orderDetails => _orderDetails;

  TrackOrderDetailsModel? _trackOrderDetailsModel;
  TrackOrderDetailsModel? get trackOrderDetailsModel => _trackOrderDetailsModel;

  Future <ApiResponseModel> getOrderDetails(String orderID) async {
    _orderDetails = null;
    ApiResponseModel apiResponse = await orderDetailsServiceInterface.getOrderDetails(orderID);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      _orderDetails = null;
      _orderDetails = [];
      apiResponse.response!.data.forEach((order) {
        OrderDetailsModel orderDetailsModel = OrderDetailsModel.fromJson(order);
        _orderDetails!.add(orderDetailsModel);
      });
    }
    notifyListeners();
    return apiResponse;
  }

  void emptyOrderDetails() {
    _orderDetails = null;
    orders = null;
    notifyListeners();
  }



  Future <ApiResponseModel> getOrderInvoice(String orderID, context) async {
    _isInvoiceLoading = true;
    notifyListeners();
    ApiResponseModel apiResponse = await orderDetailsServiceInterface.getOrderInvoice(orderID);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      await requestPermissions();
      final downloadsDirectory = Directory('/storage/emulated/0/Download');
      List<int> intList = List<int>.from(apiResponse.response!.data);

      String fileName = '$orderID.pdf';
      var filePath = path.join(downloadsDirectory.path, '$orderID.pdf');

      int fileCounter = 1;

      while (await File(filePath).exists()) {
        fileName = '$orderID($fileCounter).pdf';
        filePath = path.join(downloadsDirectory.path, fileName);
        fileCounter++;
      }

      final file = File(filePath);
      await file.writeAsBytes(intList);
      await OpenFile.open(filePath);
      showCustomSnackBarWidget(getTranslated('invoice_downloaded_successfully', context), Get.context!, snackBarType: SnackBarType.success);
    } else {
      showCustomSnackBarWidget(getTranslated('invoice_download_failed', context), Get.context!, snackBarType: SnackBarType.success);
    }
    _isInvoiceLoading = false;
    notifyListeners();
    return apiResponse;
  }




  Orders? orders;
  Future <void> getOrderFromOrderId(String orderID) async {
    ApiResponseModel apiResponse = await orderDetailsServiceInterface.getOrderFromOrderId(orderID);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      orders = Orders.fromJson(apiResponse.response!.data);
    }
    notifyListeners();
  }



  void pickImage(bool isRemove, {bool fromReview = false}) async {
    if(isRemove) {
      _imageFile = null;
      _refundImage = [];
      reviewImages = [];
    }else {
      _imageFile = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: AppConstants.imageQuality);
      if (_imageFile != null) {
        if(fromReview){
          reviewImages.add(File(_imageFile!.path));
        }else{
          _refundImage.add(_imageFile);
        }
      }
    }
    notifyListeners();
  }


  void removeImage(int index, {bool fromReview = false}){
    if(fromReview){
      reviewImages.removeAt(index);
    }else{
      _refundImage.removeAt(index);
    }

    notifyListeners();
  }



  bool searching = false;
  Future<ApiResponseModel> trackOrder({String? orderId, String? phoneNumber, bool isUpdate = true}) async {
    searching = true;
    if(isUpdate) {
      notifyListeners();
    }

    ApiResponseModel apiResponse = await orderDetailsServiceInterface.trackOrder(orderId!, phoneNumber!);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      searching = false;
      _orderDetails = [];
      apiResponse.response!.data.forEach((order) => _orderDetails!.add(OrderDetailsModel.fromJson(order)));
    } else {
      searching = false;
      ApiChecker.checkApi( apiResponse);
    }
    notifyListeners();
    return apiResponse;
  }


  Future<ApiResponseModel> getTrackOrderDetailsId({String? orderId, bool isUpdate = true}) async {
    searching = true;
    if(isUpdate) {
      notifyListeners();
    }

    ApiResponseModel apiResponse = await orderDetailsServiceInterface.getTrackOrderDetailsId(orderId!);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      searching = false;
      _trackOrderDetailsModel = null;
      _trackOrderDetailsModel  =  TrackOrderDetailsModel.fromJson(apiResponse.response?.data);
    } else {
      searching = false;
      ApiChecker.checkApi( apiResponse);
    }
    notifyListeners();
    return apiResponse;
  }


  Future<void> setOrderReviewExpanded(int index, bool status) async {

    if(status){
      final reviewController = Provider.of<ReviewController>(Get.context!, listen: false);
      reviewController.orderWiseReview = null;
    }

    if(_orderDetails != null){
      for(int i = 0; i< _orderDetails!.length;  i++){
        if(i == index){
          _orderDetails![i].isExpanded = status;
        } else{
          _orderDetails![i].isExpanded = false;
        }
      }
    }
    notifyListeners();
  }

  Future<void> requestPermissions() async {
    var status = await Permission.storage.status;
    if (!status.isGranted) {
      await Permission.storage.request();
    }
  }


  bool isOfflineChecked = false;
  bool isCODChecked = false;
  
  int _paymentMethodIndex = -1;
  int get paymentMethodIndex => _paymentMethodIndex;

}
