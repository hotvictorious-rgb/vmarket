import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/domain/models/cart_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_reservation_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/fulfillment_availability_model.dart';
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

  bool _isPickup = false;
  bool get isPickup => _isPickup;

  void setFulfillmentType(bool isPickup, {bool notify = true}) {
    _isPickup = isPickup;
    if (notify) {
      notifyListeners();
    }
  }

  int _paymentMethodIndex = -1;
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

  bool _isUseCashback = false;
  bool get isUseCashback => _isUseCashback;

  void toggleUseCashback({bool isUpdate = true}) {
    _isUseCashback = !_isUseCashback;
    if (isUpdate) {
      notifyListeners();
    }
  }

  void setUseCashback(bool value, {bool isUpdate = true}) {
    _isUseCashback = value;
    if (isUpdate) {
      notifyListeners();
    }
  }

  void initDefaultPaymentMethod(SplashController splashController, {bool isUpdate = true}) {
    final config = splashController.configModel;
    if (config == null) return;

    // [AI] Victorious MARKET V1: Explicit Paystack selection.
    // Paystack is the exclusive digital gateway for marketplace delivery checkout.
    if ((config.digitalPayment ?? false) && (config.paymentMethods != null && config.paymentMethods!.isNotEmpty)) {
      int paystackIndex = config.paymentMethods!.indexWhere((m) => (m.keyName ?? '').toLowerCase() == 'paystack');
      if (paystackIndex != -1) {
        _paymentMethodIndex = paystackIndex;
        selectedDigitalPaymentMethodName = config.paymentMethods![paystackIndex].keyName ?? 'paystack';
      } else {
        _paymentMethodIndex = 0;
        selectedDigitalPaymentMethodName = config.paymentMethods![0].keyName ?? 'paystack';
      }
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



  List<TextEditingController> inputFieldControllerList = [];

  Future<ApiResponseModel> digitalPaymentPlaceOrder({
    String? orderNote,
    String? customerId,
    String? addressId,
    String? billingAddressId,
    String? paymentMethod,
    bool useCashback = false,
  }) async {
    _isLoading = true;
    notifyListeners();

    ApiResponseModel apiResponse = await checkoutServiceInterface.digitalPaymentPlaceOrder(
      orderNote,
      customerId,
      addressId,
      billingAddressId,
      paymentMethod ?? 'paystack',
      _isCheckCreateAccount,
      passwordController.text.trim(),
      useCashback: useCashback,
    );

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

  Future<ApiResponseModel> submitPickupReservation({
    List<int>? cartIds,
    bool? checkedOnly = true,
  }) async {
    _isLoading = true;
    notifyListeners();

    final String idempotencyKey = 'prc_${DateTime.now().millisecondsSinceEpoch}_${(1000 + (DateTime.now().microsecond % 9000))}';
    ApiResponseModel apiResponse = await checkoutServiceInterface.createPickupReservation(
      idempotencyKey: idempotencyKey,
      cartIds: cartIds,
      checkedOnly: checkedOnly,
    );

    _isLoading = false;
    notifyListeners();
    return apiResponse;
  }

  Future<ApiResponseModel> payPickupReservation({
    required String reservationCode,
    bool useCashback = false,
  }) async {
    _isLoading = true;
    notifyListeners();

    ApiResponseModel apiResponse = await checkoutServiceInterface.payPickupReservation(
      reservationCode: reservationCode,
      useCashback: useCashback,
      paymentGateway: 'paystack',
      ttlMinutes: 30,
    );

    _isLoading = false;
    notifyListeners();
    return apiResponse;
  }

  // [AI] Authoritative Fulfillment & Delivery Intent Methods
  FulfillmentAvailabilityModel? _fulfillmentAvailability;
  FulfillmentAvailabilityModel? get fulfillmentAvailability => _fulfillmentAvailability;
  bool _isCheckingFulfillment = false;
  bool get isCheckingFulfillment => _isCheckingFulfillment;

  Future<FulfillmentAvailabilityModel?> checkFulfillmentAvailability({
    required int shopId,
    int? shippingAddressId,
    List<Map<String, dynamic>>? cartItems,
  }) async {
    _isCheckingFulfillment = true;
    notifyListeners();

    ApiResponseModel apiResponse = await checkoutServiceInterface.checkFulfillmentAvailability(
      shopId: shopId,
      shippingAddressId: shippingAddressId,
      cartItems: cartItems,
    );

    _isCheckingFulfillment = false;
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      _fulfillmentAvailability = FulfillmentAvailabilityModel.fromJson(apiResponse.response!.data);
    } else {
      _fulfillmentAvailability = null;
    }
    notifyListeners();
    return _fulfillmentAvailability;
  }

  String? _currentIntentOrderGroupId;
  String? get currentIntentOrderGroupId => _currentIntentOrderGroupId;

  Future<void> placeDeliveryOrder({
    required int addressId,
    int? billingAddressId,
    bool useCashback = false,
    List<int>? cartItemIds,
  }) async {
    _isLoading = true;
    notifyListeners();

    // Phase 1: Create CheckoutIntent
    final intentResponse = await createDeliveryCheckoutIntent(
      addressId: addressId,
      billingAddressId: billingAddressId,
      useCashback: useCashback,
      cartItemIds: cartItemIds,
    );

    if (intentResponse.response == null || intentResponse.response?.statusCode != 200) {
      _isLoading = false;
      notifyListeners();
      showCustomSnackBarWidget(
        getTranslated(intentResponse.error ?? 'Failed to create checkout intent', Get.context!),
        Get.context!,
        snackBarType: SnackBarType.error,
      );
      return;
    }

    final orderGroupId = intentResponse.response!.data['order_group_id']?.toString();
    if (orderGroupId == null) {
      _isLoading = false;
      notifyListeners();
      showCustomSnackBarWidget('Invalid response: Missing order_group_id', Get.context!, snackBarType: SnackBarType.error);
      return;
    }

    _currentIntentOrderGroupId = orderGroupId;

    // Phase 2: Initialize Paystack payment
    final payResponse = await initializeIntentPayment(orderGroupId: orderGroupId);

    if (payResponse.response != null && payResponse.response!.statusCode == 200) {
      _addressIndex = null;
      _billingAddressIndex = null;
      sameAsBilling = false;
      _isLoading = false;

      final data = payResponse.response!.data;
      if (data['authorization_url'] != null) {
        RouterHelper.getDigitalPaymentScreenRoute(
          url: data['authorization_url'],
          fromWallet: false,
          action: RouteAction.pushReplacement,
        );
      } else {
        showCustomSnackBarWidget('Payment initialized but no authorization URL returned', Get.context!, snackBarType: SnackBarType.error);
      }
    } else {
      _isLoading = false;
      showCustomSnackBarWidget(
        getTranslated(payResponse.error ?? 'Payment initialization failed', Get.context!),
        Get.context!,
        snackBarType: SnackBarType.error,
      );
    }
    notifyListeners();
  }

  Future<ApiResponseModel> createDeliveryCheckoutIntent({
    required int addressId,
    int? billingAddressId,
    bool useCashback = false,
    List<int>? cartItemIds,
  }) async {
    _isLoading = true;
    notifyListeners();

    final String idempotencyKey = 'dci_${DateTime.now().millisecondsSinceEpoch}_${(1000 + (DateTime.now().microsecond % 9000))}';
    ApiResponseModel apiResponse = await checkoutServiceInterface.createDeliveryCheckoutIntent(
      addressId: addressId,
      idempotencyKey: idempotencyKey,
      billingAddressId: billingAddressId,
      useCashback: useCashback,
      cartItemIds: cartItemIds,
    );

    _isLoading = false;
    notifyListeners();
    return apiResponse;
  }

  Future<ApiResponseModel> initializeIntentPayment({
    required String orderGroupId,
  }) async {
    _isLoading = true;
    notifyListeners();

    ApiResponseModel apiResponse = await checkoutServiceInterface.initializeIntentPayment(
      orderGroupId: orderGroupId,
    );

    _isLoading = false;
    notifyListeners();
    return apiResponse;
  }
}

