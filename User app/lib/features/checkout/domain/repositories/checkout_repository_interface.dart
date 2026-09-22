import 'package:flutter_sixvalley_ecommerce/interface/repo_interface.dart';

abstract class CheckoutRepositoryInterface implements RepositoryInterface{




  Future<dynamic> digitalPaymentPlaceOrder(String? orderNote, String? customerId, String? addressId, String? billingAddressId, String? paymentMethod, bool? isCheckCreateAccount, String? password, {bool useCashback = false});

  Future<dynamic> getReferralAmount(String? amount);

  Future<dynamic> createPickupReservation({required String idempotencyKey, List<int>? cartIds, bool? checkedOnly});
}