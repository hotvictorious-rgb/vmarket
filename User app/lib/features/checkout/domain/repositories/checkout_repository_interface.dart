import 'package:flutter_sixvalley_ecommerce/interface/repo_interface.dart';

abstract class CheckoutRepositoryInterface implements RepositoryInterface{




  Future<dynamic> digitalPaymentPlaceOrder(String? orderNote, String? customerId, String? addressId, String? billingAddressId, String? paymentMethod, bool? isCheckCreateAccount, String? password, {bool useCashback = false});

  Future<dynamic> getReferralAmount(String? amount);

  Future<dynamic> createPickupReservation({required String idempotencyKey, List<int>? cartIds, bool? checkedOnly});
  Future<dynamic> payPickupReservation({required String reservationCode, bool useCashback = false, String paymentGateway = 'paystack', int ttlMinutes = 30});

  // [AI] Authoritative Fulfillment & Delivery Intent Methods
  Future<dynamic> checkFulfillmentAvailability({required int shopId, int? shippingAddressId, List<Map<String, dynamic>>? cartItems});
  Future<dynamic> createDeliveryCheckoutIntent({required int addressId, required String idempotencyKey, int? billingAddressId, bool useCashback = false, List<int>? cartItemIds});
  Future<dynamic> initializeIntentPayment({required String orderGroupId});
}