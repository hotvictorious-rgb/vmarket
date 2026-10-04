import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/domain/models/cart_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/domain/models/fulfillment_availability_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/services/checkout_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/helper/api_checker.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'dart:async';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_payment_state.dart';
import 'dart:convert';
import 'dart:math';
import 'package:flutter_sixvalley_ecommerce/di_container.dart' as di;
import 'package:flutter_sixvalley_ecommerce/services/storage_service.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/delivery_payment_state.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/screens/digital_payment_order_place_screen.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/screens/payment_status_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/show_custom_snakbar_widget.dart';

class CheckoutController with ChangeNotifier {
  final CheckoutServiceInterface checkoutServiceInterface;
  CheckoutController({required this.checkoutServiceInterface});

  int? _addressIndex;
  int? _billingAddressIndex;
  int? get billingAddressIndex => _billingAddressIndex;
  int? _shippingIndex;
  bool _isLoading = false;
  bool _isCheckCreateAccount = false;

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
  void setSelectedPayment(String payment) {
    selectedPaymentName = payment;
    notifyListeners();
  }

  bool _isAcceptTerms = false;
  bool get isAcceptTerms => _isAcceptTerms;

  final TextEditingController orderNoteController = TextEditingController();
  final TextEditingController passwordController = TextEditingController();
  final TextEditingController confirmPasswordController =
      TextEditingController();
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

  void resetPaymentMethod() {
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

  void initDefaultPaymentMethod(SplashController splashController,
      {bool isUpdate = true}) {
    final config = splashController.configModel;
    if (config == null) return;

    // [AI] Victorious MARKET V1: Explicit Paystack selection.
    // Paystack is the exclusive digital gateway for marketplace delivery checkout.
    if ((config.digitalPayment ?? false) &&
        (config.paymentMethods != null && config.paymentMethods!.isNotEmpty)) {
      int paystackIndex = config.paymentMethods!
          .indexWhere((m) => (m.keyName ?? '').toLowerCase() == 'paystack');
      if (paystackIndex != -1) {
        _paymentMethodIndex = paystackIndex;
        selectedDigitalPaymentMethodName =
            config.paymentMethods![paystackIndex].keyName ?? 'paystack';
      } else {
        _paymentMethodIndex = 0;
        selectedDigitalPaymentMethodName =
            config.paymentMethods![0].keyName ?? 'paystack';
      }
    } else {
      _paymentMethodIndex = -1;
      selectedDigitalPaymentMethodName = '';
    }

    if (isUpdate) {
      notifyListeners();
    }
  }

  void shippingAddressNull() {
    _addressIndex = null;
    notifyListeners();
  }

  void billingAddressNull() {
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

    ApiResponseModel apiResponse =
        await checkoutServiceInterface.digitalPaymentPlaceOrder(
      orderNote,
      customerId,
      addressId,
      billingAddressId,
      paymentMethod ?? 'paystack',
      _isCheckCreateAccount,
      passwordController.text.trim(),
      useCashback: useCashback,
    );

    if (apiResponse.response != null &&
        apiResponse.response?.statusCode == 200) {
      _addressIndex = null;
      _billingAddressIndex = null;
      sameAsBilling = false;
      _isLoading = false;

      RouterHelper.getDigitalPaymentScreenRoute(
        url: apiResponse.response?.data['redirect_link'] ?? '',
        fromWallet: false,
        action: RouteAction.pushReplacement,
      );
    } else if (apiResponse.error == 'Already registered ') {
      _isLoading = false;
      showCustomSnackBarWidget(
          getTranslated(apiResponse.error, Get.context!), Get.context!,
          snackBarType: SnackBarType.warning);
    } else if (apiResponse.response != null &&
        apiResponse.response!.statusCode == 403) {
      _isLoading = false;
      showCustomSnackBarWidget(
          getTranslated(apiResponse.error, Get.context!), Get.context!,
          snackBarType: SnackBarType.error);
    } else {
      _isLoading = false;
      showCustomSnackBarWidget(
          getTranslated('payment_method_not_properly_configured', Get.context!),
          Get.context!,
          snackBarType: SnackBarType.error);
    }
    notifyListeners();
    return apiResponse;
  }

  bool sameAsBilling = false;
  void setSameAsBilling({bool isUpdate = true}) {
    sameAsBilling = !sameAsBilling;
    if (isUpdate) {
      notifyListeners();
    }
  }

  void clearData() {
    orderNoteController.clear();
    passwordController.clear();
    confirmPasswordController.clear();
    _isCheckCreateAccount = false;
    _cashChangesAmount = null;
  }

  void setIsCheckCreateAccount(bool isCheck, {bool update = true}) {
    _isCheckCreateAccount = isCheck;
    if (update) {
      notifyListeners();
    }
  }

  void toggleChangeAmountShow() {
    _changeAmountShow = !_changeAmountShow;
    notifyListeners();
  }

  void onChangeCashChangesAmount(double? amount) => _cashChangesAmount = amount;

  Future<ApiResponseModel> getReferralAmount(String? amount) async {
    ApiResponseModel apiResponse =
        await checkoutServiceInterface.getReferralAmount(amount);
    if (apiResponse.response != null &&
        apiResponse.response!.statusCode == 200) {
      _referralAmount = ReferralAmount.fromJson(apiResponse.response.data);
    } else {
      ApiChecker.checkApi(apiResponse);
    }
    notifyListeners();
    return apiResponse;
  }

  void toggleTermsCheck({bool isUpdate = true}) {
    _isAcceptTerms = !_isAcceptTerms;
    if (isUpdate) {
      notifyListeners();
    }
  }

  void updatePaymentSelection() {
    notifyListeners();
  }

  Future<ApiResponseModel> submitPickupReservation({
    List<int>? cartIds,
    bool? checkedOnly = true,
  }) async {
    _isLoading = true;
    notifyListeners();

    final String idempotencyKey =
        'prc_${DateTime.now().millisecondsSinceEpoch}_${(1000 + (DateTime.now().microsecond % 9000))}';
    ApiResponseModel apiResponse =
        await checkoutServiceInterface.createPickupReservation(
      idempotencyKey: idempotencyKey,
      cartIds: cartIds,
      checkedOnly: checkedOnly,
    );

    _isLoading = false;
    notifyListeners();
    return apiResponse;
  }

  PickupPaymentState? get pendingPickupPayment {
    final storage = di.sl<StorageService>();
    return PickupPaymentState.decode(storage.getString(PickupPaymentState.storageKey), storage.getString(AppConstants.userLoginToken));
  }

  Future<void> clearPendingPickupPayment({String? reservationCode}) async {
    final storage = di.sl<StorageService>();
    final pending = pendingPickupPayment;
    if (pending != null && (reservationCode == null || pending.reservationCode == reservationCode)) {
      await storage.remove(PickupPaymentState.storageKey);
      notifyListeners();
    }
  }

  Future<ApiResponseModel> quotePickupReservation({required String reservationCode, bool useCashback = false}) =>
    checkoutServiceInterface.quotePickupReservation(reservationCode: reservationCode, useCashback: useCashback).then((value) => value as ApiResponseModel);

  Future<ApiResponseModel> payPickupReservation({required String quoteToken, required String reservationCode, bool useCashback = false}) async {
    _isLoading = true; notifyListeners();
    try {
      final storage = di.sl<StorageService>();
      final owner = storage.getString(AppConstants.userLoginToken) ?? '';
      if (owner.isEmpty) return ApiResponseModel.withError('Sign in to pay');
      final pending = pendingPickupPayment;
      if (pending != null && pending.reservationCode != reservationCode) return ApiResponseModel.withError('Check your previous pickup payment first');
      await storage.setString(PickupPaymentState.storageKey, PickupPaymentState(ownerToken: owner, reservationCode: reservationCode, quoteToken: quoteToken, useCashback: useCashback).encode());
      return await checkoutServiceInterface.payPickupReservation(quoteToken: quoteToken, reservationCode: reservationCode, useCashback: useCashback, paymentGateway: 'paystack', ttlMinutes: 30);
    } catch (e) { return ApiResponseModel.withError(e.toString()); }
    finally { _isLoading = false; notifyListeners(); }
  }

  // [AI] Authoritative Fulfillment & Delivery Intent Methods
  FulfillmentAvailabilityModel? _fulfillmentAvailability;
  FulfillmentAvailabilityModel? get fulfillmentAvailability =>
      _fulfillmentAvailability;
  bool _isCheckingFulfillment = false;
  bool get isCheckingFulfillment => _isCheckingFulfillment;

  Future<FulfillmentAvailabilityModel?> checkFulfillmentAvailability({
    required int shopId,
    int? shippingAddressId,
    List<Map<String, dynamic>>? cartItems,
  }) async {
    _isCheckingFulfillment = true;
    notifyListeners();

    ApiResponseModel apiResponse =
        await checkoutServiceInterface.checkFulfillmentAvailability(
      shopId: shopId,
      shippingAddressId: shippingAddressId,
      cartItems: cartItems,
    );

    _isCheckingFulfillment = false;
    if (apiResponse.response != null &&
        apiResponse.response!.statusCode == 200) {
      _fulfillmentAvailability =
          FulfillmentAvailabilityModel.fromJson(apiResponse.response!.data);
    } else {
      _fulfillmentAvailability = null;
    }
    notifyListeners();
    return _fulfillmentAvailability;
  }

  String? _currentIntentOrderGroupId;
  String? get currentIntentOrderGroupId => _currentIntentOrderGroupId;

  DeliveryPaymentState? get pendingDeliveryPayment {
    final storage = di.sl<StorageService>();
    return DeliveryPaymentState.decode(
        storage.getString(DeliveryPaymentState.storageKey),
        storage.getString(AppConstants.userLoginToken));
  }

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

    if (intentResponse.response == null ||
        intentResponse.response?.statusCode != 200) {
      _isLoading = false;
      notifyListeners();
      showCustomSnackBarWidget(
        getTranslated(
            intentResponse.error ?? 'Failed to create checkout intent',
            Get.context!),
        Get.context!,
        snackBarType: SnackBarType.error,
      );
      return;
    }

    final orderGroupId =
        intentResponse.response!.data['order_group_id']?.toString();
    if (orderGroupId == null) {
      _isLoading = false;
      notifyListeners();
      showCustomSnackBarWidget(
          'Invalid response: Missing order_group_id', Get.context!,
          snackBarType: SnackBarType.error);
      return;
    }

    _currentIntentOrderGroupId = orderGroupId;

    final quote = intentResponse.response!.data['quote'];
    if (Get.context == null) return;
    if (quote is! Map) {
      showCustomSnackBarWidget(
          'The final quote is unavailable. Please try again.', Get.context!,
          snackBarType: SnackBarType.error);
      return;
    }
    final confirmed = await showDialog<bool>(
        context: Get.context!,
        builder: (context) => AlertDialog(
              title: const Text('Confirm final checkout amount'),
              content: SingleChildScrollView(
                  child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                    Text(
                        'Merchandise: ${quote['currency']} ${quote['merchandise_subtotal']}'),
                    Text('Tax: ${quote['currency']} ${quote['tax_total']}'),
                    Text(
                        'Delivery: ${quote['currency']} ${quote['shipping_total']}'),
                    Text(
                        'Victorious Points: ${quote['currency']} ${quote['cashback_amount']}'),
                    const Divider(),
                    Text(
                        'Pay now: ${quote['currency']} ${quote['total_amount']}'),
                    for (final vendor in (quote['vendors'] as List? ?? [])) ...[
                      const Divider(),
                      Text(vendor['shop_name']?.toString() ?? 'Store'),
                      Text('Merchandise: ${quote['currency']} ${vendor['merchandise']}'),
                      Text('Tax: ${quote['currency']} ${vendor['tax']}'),
                      Text('Delivery: ${quote['currency']} ${vendor['shipping_cost']}'),
                      Text('Victorious Points: ${quote['currency']} ${vendor['allocated_cashback'] ?? '0.00'}'),
                    ],
                  ])),
              actions: [
                TextButton(
                    onPressed: () => Navigator.pop(context, false),
                    child: const Text('Back')),
                FilledButton(
                    onPressed: () => Navigator.pop(context, true),
                    child: const Text('Confirm and pay'))
              ],
            ));
    if (confirmed != true) return;

    // Phase 2: Initialize Paystack payment
    final payResponse =
        await initializeIntentPayment(orderGroupId: orderGroupId);

    if (payResponse.response != null &&
        payResponse.response!.statusCode == 200) {
      _addressIndex = null;
      _billingAddressIndex = null;
      sameAsBilling = false;
      _isLoading = false;

      final data = payResponse.response!.data;
      final pending = pendingDeliveryPayment;
      if (pending != null) {
        await di.sl<StorageService>().setString(
            DeliveryPaymentState.storageKey,
            DeliveryPaymentState(
                    ownerToken: pending.ownerToken,
                    requestSignature: pending.requestSignature,
                    idempotencyKey: pending.idempotencyKey,
                    orderGroupId: orderGroupId,
                    paymentRequestId: data['payment_request_id']?.toString(), initializationStarted: true)
                .encode());
      }
      if (data['authorization_url'] != null) {
        Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(
            builder: (_) => DigitalPaymentScreen(
                url: data['authorization_url'], orderGroupId: orderGroupId)));
      } else {
        Navigator.of(Get.context!).push(MaterialPageRoute(
            builder: (_) => PaymentStatusScreen(orderGroupId: orderGroupId)));
      }
    } else {
      _isLoading = false;
      Navigator.of(Get.context!).push(MaterialPageRoute(
          builder: (_) => PaymentStatusScreen(orderGroupId: orderGroupId)));
      showCustomSnackBarWidget(
        getTranslated(
            payResponse.error ?? 'Payment initialization failed', Get.context!),
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

    final storage = di.sl<StorageService>();
    final owner = storage.getString(AppConstants.userLoginToken) ?? '';
    final signature =
        jsonEncode([addressId, billingAddressId, useCashback, cartItemIds]);
    final pending = pendingDeliveryPayment;
    // Do not replace an unresolved payment just because the cart/address changed.
    if (pending?.orderGroupId != null &&
        pending!.initializationStarted && pending.requestSignature != signature) {
      _isLoading = false;
      notifyListeners();
      Navigator.of(Get.context!).push(MaterialPageRoute(
          builder: (_) =>
              PaymentStatusScreen(orderGroupId: pending.orderGroupId)));
      return ApiResponseModel.withError(
          'Check your existing payment before starting another checkout.');
    }
    final reuse = pending?.requestSignature == signature;
    var idempotencyKey = (reuse ? pending?.idempotencyKey : null) ??
        'dci_${DateTime.now().microsecondsSinceEpoch}_${Random.secure().nextInt(1 << 32)}';
    await storage.setString(
        DeliveryPaymentState.storageKey,
        DeliveryPaymentState(
                ownerToken: owner,
                requestSignature: signature,
                idempotencyKey: idempotencyKey,
                orderGroupId: reuse ? pending?.orderGroupId : null,
                initializationStarted: reuse && (pending?.initializationStarted ?? false))
            .encode());
    ApiResponseModel apiResponse =
        await checkoutServiceInterface.createDeliveryCheckoutIntent(
      addressId: addressId,
      idempotencyKey: idempotencyKey,
      billingAddressId: billingAddressId,
      useCashback: useCashback,
      cartItemIds: cartItemIds,
    );

    // A changed cart can keep the same selected IDs. Only an uninitiated quote
    // may be replaced after the backend reports a conflict or expiration.
    if (!(pending?.initializationStarted ?? false) &&
        (apiResponse.response?.statusCode == 409 ||
         (apiResponse.response?.statusCode == 200 && apiResponse.response!.data['status'] == 'expired'))) {
      idempotencyKey = 'dci_${DateTime.now().microsecondsSinceEpoch}_${Random.secure().nextInt(1 << 32)}';
      await storage.setString(DeliveryPaymentState.storageKey, DeliveryPaymentState(ownerToken: owner,
        requestSignature: signature, idempotencyKey: idempotencyKey).encode());
      apiResponse = await checkoutServiceInterface.createDeliveryCheckoutIntent(
        addressId: addressId, idempotencyKey: idempotencyKey, billingAddressId: billingAddressId,
        useCashback: useCashback, cartItemIds: cartItemIds);
    }

    if (apiResponse.response?.statusCode == 200) {
      final group = apiResponse.response!.data['order_group_id']?.toString();
      await storage.setString(
          DeliveryPaymentState.storageKey,
          DeliveryPaymentState(
                  ownerToken: owner,
                  requestSignature: signature,
                  idempotencyKey: idempotencyKey,
                  orderGroupId: group, initializationStarted: reuse && (pending?.initializationStarted ?? false))
              .encode());
    }

    _isLoading = false;
    notifyListeners();
    return apiResponse;
  }

  Future<ApiResponseModel> initializeIntentPayment({
    required String orderGroupId,
  }) async {
    _isLoading = true;
    notifyListeners();

    final pending = pendingDeliveryPayment;
    if (pending != null && pending.orderGroupId == orderGroupId) {
      await di.sl<StorageService>().setString(DeliveryPaymentState.storageKey,
        DeliveryPaymentState(ownerToken: pending.ownerToken, requestSignature: pending.requestSignature,
          idempotencyKey: pending.idempotencyKey, orderGroupId: pending.orderGroupId,
          paymentRequestId: pending.paymentRequestId, initializationStarted: true).encode());
    }

    ApiResponseModel apiResponse =
        await checkoutServiceInterface.initializeIntentPayment(
      orderGroupId: orderGroupId,
    );

    _isLoading = false;
    notifyListeners();
    return apiResponse;
  }
}
