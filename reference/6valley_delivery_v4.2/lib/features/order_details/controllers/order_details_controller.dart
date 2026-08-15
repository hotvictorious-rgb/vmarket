import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:sixvalley_delivery_boy/features/order/controllers/order_controller.dart';
import 'package:sixvalley_delivery_boy/features/order_details/domain/models/order_details_model.dart';
import 'package:sixvalley_delivery_boy/features/order_details/domain/services/order_details_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_delivery_boy/features/wallet/controllers/wallet_controller.dart';
import 'package:sixvalley_delivery_boy/data/api/api_checker.dart';
import 'package:sixvalley_delivery_boy/data/api/api_client.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_snackbar_widget.dart';

class OrderDetailsController extends GetxController implements GetxService {
  final OrderDetailsServiceInterface orderDetailsServiceInterface;
  OrderDetailsController({required this.orderDetailsServiceInterface});


  bool _isLoading = false;
  bool get isLoading => _isLoading;
  int _orderTypeFilterIndex = 0;
  int get orderTypeFilterIndex => _orderTypeFilterIndex;
  OrderDetailsModel? _orderDetailsModel;
  OrderDetailsModel? get orderDetailsModel => _orderDetailsModel;
  List<OrderDetailsModel>? _orderDetails;
  List<OrderDetailsModel>? get orderDetails => _orderDetails;

  double? _totalPrice;
  double? get totalPrice => _totalPrice;
  set setTotalPrice(double? amount) => _totalPrice = amount;





  final List<String> reasonList = [
    'could_not_contact_with_the_customer',
    'customer_cant_collect_the_parcel_now_request_to_deliver_delay',
    'could_not_find_the_location',
    'delivery_man_transport_broken',
    'other'
  ];


  String? _reasonValue = '';
  String? get reasonValue => _reasonValue;

  List<OrderModel>? _orderList;
  List<OrderModel>? get orderList => _orderList != null ? _orderList!.reversed.toList() : _orderList;

  void setReason(String? value, {bool reload = true}){
    _reasonValue = value;
    if(reload){
      update();
    }

  }





  Future<List<OrderDetailsModel>?> getOrderDetails(String orderID, BuildContext context) async {
    _orderDetails= null;
    Response response = await orderDetailsServiceInterface.getOrderDetails(orderID: orderID);
    if (response.body != null && response.statusCode == 200) {
      _orderDetails = [];
      response.body.forEach((orderDetail) => _orderDetails?.add(OrderDetailsModel.fromJson(orderDetail)));
    } else {
      ApiChecker.checkApi( response);
    }
    update();
    return _orderDetails;
  }






  Future<bool> updateOrderStatus({int? orderId, String? status,BuildContext? context}) async {
    _isLoading =  true;
    update();
    Response response = await  orderDetailsServiceInterface.updateOrderStatus(orderId: orderId, status: status);
    bool _isSuccess;
    if(response.body != null && response.statusCode == 200) {
      Get.back();
      showCustomSnackBarWidget(response.body['message'], isError: false);
      _isSuccess = true;
      Get.find<OrderController>().getCurrentOrders();
      Get.find<OrderController>().getAllOrderHistory('', '', '', '',0);
      Get.find<ProfileController>().getProfile();
    }else {
      ApiChecker.checkApi(response);
      _isSuccess = false;
    }
    _isLoading = false;
    update();
    return _isSuccess;
  }



  Future<bool> cancelOrderStatus({int? orderId, String? cause,BuildContext? context}) async {
    _isLoading = true;
    update();
    bool _isSuccess = await orderDetailsServiceInterface.cancelOrderStatus(orderId: orderId,  cause: cause);
    Get.back();
    if(_isSuccess) {
      getOrderDetails(orderId.toString(), context!);
    }

    _isLoading = false;
    update();
    return _isSuccess;
  }

  Future<bool> rescheduleOrderStatus({int? orderId, String? deliveryDate, String? cause, BuildContext? context}) async {
    _isLoading = true;
    update();
    bool _isSuccess = await orderDetailsServiceInterface.rescheduleOrder(orderId: orderId, deliveryDate: deliveryDate, cause: cause);
    Get.back();

    if(_isSuccess) {
      showCustomSnackBarWidget('order_status_rescheduled_successfully'.tr, isError: false);
    }
    _isLoading = false;
    update();
    return _isSuccess;
  }

  Future<bool> pauseAndResumeOrder({int? orderId, int? isPos, String? cause, BuildContext? context}) async {
    _isLoading = true;
    update();
    bool _isSuccess = await orderDetailsServiceInterface.pauseAndResumeOrder(orderId: orderId, isPos: isPos, cause: cause);
    Get.find<OrderController>().getCurrentOrders();
    _isLoading = false;
    update();
    return _isSuccess;
  }

  Future<Response?> updatePaymentStatus({int? orderId, String? status}) async {
    Response apiResponse = await orderDetailsServiceInterface.updatePaymentStatus(orderId: orderId, status: status);
    update();
    return apiResponse;
  }




  void setEarningFilterIndex(int index) {
    _orderTypeFilterIndex = index;
    if(_orderTypeFilterIndex == 0){
      Get.find<WalletController>().getOrderWiseDeliveryCharge('', '', 1, '');
    }else if(_orderTypeFilterIndex == 1){
      Get.find<WalletController>().getOrderWiseDeliveryCharge('', '', 1, 'TodayEarn');
    }
    else if(_orderTypeFilterIndex == 2){
      Get.find<WalletController>().getOrderWiseDeliveryCharge('', '', 1, 'ThisWeekEarn');
    }
    else if(_orderTypeFilterIndex == 3){
      Get.find<WalletController>().getOrderWiseDeliveryCharge('', '', 1, 'ThisMonthEarn');
    }
    update();
  }




  DateTime? _startDate;
  final DateFormat _dateFormat = DateFormat('yyyy-MM-d');
  DateTime? get startDate => _startDate;
  DateFormat get dateFormat => _dateFormat;

  void selectDate(BuildContext context){
    showDatePicker(

      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2022),
      lastDate: DateTime(2030),
    ).then((date) {
      _startDate = date;
      update();
    });
  }


  List<XFile>? identityImage;
  XFile? cameraImage;
  List<XFile> identityImages = [];
  List<MultipartBody> multipartList = [];
  void pickImage( {bool camera = false}) async {
    multipartList = [];
    identityImage = null;
    if(camera){
      cameraImage = (await ImagePicker().pickImage(source: ImageSource.camera));
      if(cameraImage != null){
        identityImages.add(cameraImage!);
      }
    }else{
      identityImage = (await ImagePicker().pickMultiImage());
    }

      if(identityImage != null){
        identityImages.addAll(identityImage!);
      }
      for(int i=0; i<identityImages.length; i++){
        multipartList.add(MultipartBody('image[$i]', identityImages[i]));
      }

    update();
  }


  void removeImage(int index){
    identityImages.removeAt(index);
    update();
  }

  bool endOfPage = false;
  void gotoEndOfPage(){
    endOfPage = true;
    update();
  }
  void gotoEndOfPageInitialize(){
    endOfPage = false;
  }

  bool otpVerified = false;
  void toggleProceedToNext(){
    identityImages.clear();
    otpVerified = true;
    update();
  }

  String? otp;
  void setOtp(String otp) {
    otp = otp;
    if(otp != '') {
      update();
    }
  }

  bool uploading = false;
  Future<Response> uploadOrderVerificationImage( String oderId) async {
    uploading = true;
    update();
    Response? response = await orderDetailsServiceInterface.uploadOrderVerificationImage(oderId, multipartList);
    if(response!.statusCode == 200){
      uploading = false;
      showCustomSnackBarWidget('image_uploaded_successfully'.tr, isError: false);
    }else{
      uploading = false;
      ApiChecker.checkApi(response);
    }

    update();
    return response;
  }

  Future<Response?> otpVerificationForOrderVerification({int? orderId, String? otp}) async {
    _isLoading = true;
    Response apiResponse = await orderDetailsServiceInterface.verifyOrderDeliveryOtp(orderId: orderId, verificationCode: otp);
    if (apiResponse.statusCode == 200) {
      showCustomSnackBarWidget('otp_verified_successfully'.tr, isError: false);
      Get.back();
    } else {
      ApiChecker.checkApi(apiResponse);
    }
    _isLoading = false;
    update();
    return apiResponse;
  }

  Future resendOtpForOrderVerification({int? orderId}) async {
     await orderDetailsServiceInterface.resendOtpForOrderVerification(orderId: orderId);
    update();
  }

  TextEditingController searchOrderController = TextEditingController();


  void emptyIdentityImage() {
    identityImages = [];
  }


}
