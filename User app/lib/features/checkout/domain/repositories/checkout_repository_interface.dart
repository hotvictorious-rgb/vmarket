import 'package:flutter_sixvalley_ecommerce/interface/repo_interface.dart';

abstract class CheckoutRepositoryInterface implements RepositoryInterface{




  Future<dynamic> digitalPaymentPlaceOrder(String? orderNote, String? customerId, String? addressId, String? billingAddressId, String? couponCode, String? couponDiscount, String? paymentMethod, bool? isCheckCreateAccount, String? password);


  Future<dynamic> getReferralAmount(String? amount);


}