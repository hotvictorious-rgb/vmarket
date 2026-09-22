abstract class CheckoutServiceInterface{




  Future<dynamic> digitalPaymentPlaceOrder(String? orderNote, String? customerId, String? addressId, String? billingAddressId, String? paymentMethod, bool? isCheckCreateAccount, String? password, {bool useCashback = false});

  Future<dynamic> getReferralAmount(String? amount);

  Future<dynamic> createPickupReservation({required String idempotencyKey, List<int>? cartIds, bool? checkedOnly});
}