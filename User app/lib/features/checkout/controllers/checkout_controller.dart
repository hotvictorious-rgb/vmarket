import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/domain/models/cart_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/services/checkout_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/helper/api_checker.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/show_custom_snakbar_widget.dart';
import 'package:provider/provider.dart';



class CheckoutController with ChangeNotifier {
  final CheckoutServiceInterface checkoutServiceInterface;
  CheckoutController({required this.checkoutServiceInterface});

  int? _addressIndex;
  int? _billingAddressIndex;
  int? get billingAddressIndex => _billingAddressIndex;
  int? _shippingIndex;
  bool _isLoading = false;
  bool _isCheckCreateAccount = false;
  bool _newUser = false;

  int _paymentMethodIndex = -1;
  bool _onlyDigital = true;
  bool get onlyDigital => _onlyDigital;
  int? get addressIndex => _addressIndex;
  int? get shippingIndex => _shippingIndex;
  bool get isLoading => _isLoading;
  int get paymentMethodIndex => _paymentMethodIndex;
  bool get isCheckCreateAccount => _isCheckCreateAccount;

  bool _changeAmountShow = false;
  bool get changeAmountShow => _changeAmountShow;

  double? _cashChangesAmount;
  double? get cashChangesAmount => _cashChangesAmount;

  ReferralAmount? _referralAmount;
  ReferralAmount? get referralAmount => _referralAmount;

  String selectedPaymentName = '';
  void setSelectedPayment(String payment){
    selectedPaymentName = payment;
    notifyListeners();
  }

  bool _isAcceptTerms = false;
  bool get isAcceptTerms => _isAcceptTerms;


  final TextEditingController orderNoteController = TextEditingController();
  final TextEditingController passwordController = TextEditingController();
  final TextEditingController confirmPasswordController = TextEditingController();
  List<String> inputValueList = [];





  String? extractId(String idsString) {

    String cleaned = idsString.replaceAll(RegExp(r'[\[\]\s]'), '');
    return cleaned.isNotEmpty ? cleaned : null;
  }

  String? getFirstOrderId(String idsString) {
    if (idsString.trim().isEmpty) return null;

    List<String> ids = idsString.split(',').map((e) => e.trim()).toList();

    return ids.isNotEmpty ? ids.first : null;
  }



  void setAddressIndex(int index) {
    _addressIndex = index;
    notifyListeners();
  }
  void setBillingAddressIndex(int index) {
    _billingAddressIndex = index;
    notifyListeners();
  }


  void resetPaymentMethod(){
    _paymentMethodIndex = -1;
    selectedDigitalPaymentMethodName = '';
  }

  void initDefaultPaymentMethod(SplashController splashController, {bool onlyDigital = false, bool isUpdate = true}) {
    final config = splashController.configModel;
    if (config == null) return;

    // [AI] Victorious MARKET V1 Directive 57321: COD and offline payments are decommissioned.
    // Digital payment (Paystack) is authoritative for delivery checkout.
            
    if ((config.digitalPayment ?? false) && (config.paymentMethods != null && config.paymentMethods!.isNotEmpty)) {
      _paymentMethodIndex = 0;
      selectedDigitalPaymentMethodName = config.paymentMethods![0].keyName ?? '';
    } else {
      _paymentMethodIndex = -1;
      selectedDigitalPaymentMethodName = '';
    }

    if (isUpdate) {
      notifyListeners();
    }
  }


  void shippingAddressNull(){
    _addressIndex = null;
    notifyListeners();
  }

  void billingAddressNull(){
    _billingAddressIndex = null;
    notifyListeners();
  }

  void setSelectedShippingAddress(int index) {
    _shippingIndex = index;
    notifyListeners();
  }
  void setSelectedBillingAddress(int index) {
    _billingAddressIndex = index;
    notifyListeners();
  }


String selectedDigitalPaymentMethodName = '';

  void setDigitalPaymentMethodName(int index, String name) {
    _paymentMethodIndex = index;
    selectedDigitalPaymentMethodName = name;
                notifyListeners();
  }


  void digitalOnly(bool value, {bool isUpdate = false}){
    _onlyDigital = value;
    if(isUpdate){
      notifyListeners();
    }

  }




  List<TextEditingController> inputFieldControllerList = [];

  Future<ApiResponseModel> digitalPaymentPlaceOrder({String? orderNote, String? customerId,
    String? addressId, String? billingAddressId,
    String? couponCode,
    String? couponDiscount,
    String? paymentMethod}) async {
    _isLoading =true;
    notifyListeners();

    ApiResponseModel apiResponse = await checkoutServiceInterface.digitalPaymentPlaceOrder(orderNote, customerId, addressId, billingAddressId, couponCode, couponDiscount, paymentMethod, _isCheckCreateAccount, passwordController.text.trim());

    if (apiResponse.response != null && apiResponse.response?.statusCode == 200) {
      _addressIndex = null;
      _billingAddressIndex = null;
      sameAsBilling = false;
      _isLoading = false;

      RouterHelper.getDigitalPaymentScreenRoute(
        url: apiResponse.response?.data['redirect_link'] ?? '',
        fromWallet: false,
        action: RouteAction.pushReplacement,
      );

    } else if(apiResponse.error == 'Already registered ') {
      _isLoading = false;
      showCustomSnackBarWidget(getTranslated(apiResponse.error, Get.context!), Get.context!, snackBarType: SnackBarType.warning);
    } else if(apiResponse.response != null && apiResponse.response!.statusCode == 403) {
      _isLoading = false;
      showCustomSnackBarWidget(getTranslated(apiResponse.error, Get.context!), Get.context!, snackBarType: SnackBarType.error);
    } else {
      _isLoading = false;
      showCustomSnackBarWidget(getTranslated('payment_method_not_properly_configured', Get.context!), Get.context!, snackBarType: SnackBarType.error);
    }
    notifyListeners();
    return apiResponse;
  }

  bool sameAsBilling = false;
  void setSameAsBilling({bool isUpdate = true}) {
    sameAsBilling = !sameAsBilling;
    if(isUpdate) {
      notifyListeners();
    }
  }

  void clearData(){
    orderNoteController.clear();
    passwordController.clear();
    confirmPasswordController.clear();
    _isCheckCreateAccount = false;
    _cashChangesAmount = null;
  }


  void setIsCheckCreateAccount(bool isCheck, {bool update = true}) {
    _isCheckCreateAccount = isCheck;
    if(update) {
      notifyListeners();
    }
  }



  void toggleChangeAmountShow(){
    _changeAmountShow = !_changeAmountShow;
    notifyListeners();
  }

  void onChangeCashChangesAmount(double? amount)=> _cashChangesAmount = amount;


  Future<ApiResponseModel> getReferralAmount(String? amount) async {
    ApiResponseModel apiResponse = await checkoutServiceInterface.getReferralAmount(amount);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      _referralAmount = ReferralAmount.fromJson(apiResponse.response.data);
    } else {
      ApiChecker.checkApi( apiResponse);
    }
    notifyListeners();
    return apiResponse;
  }


  void toggleTermsCheck({bool isUpdate = true}) {
    _isAcceptTerms = !_isAcceptTerms;
    if(isUpdate) {
      notifyListeners();
    }
  }


  void updatePaymentSelection(){
    notifyListeners();
  }


}
