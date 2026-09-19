abstract class CheckoutServiceInterface{




  Future<dynamic> digitalPaymentPlaceOrder(String? orderNote, String? customerId, String? addressId, String? billingAddressId, String? couponCode, String? couponDiscount, String? paymentMethod, bool? isCheckCreateAccount, String? password);


  Future<dynamic> getReferralAmount(String? amount);

}