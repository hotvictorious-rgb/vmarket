import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/repositories/checkout_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/services/checkout_service_interface.dart';

class CheckoutService implements CheckoutServiceInterface{
  CheckoutRepositoryInterface checkoutRepositoryInterface;


  CheckoutService({required this.checkoutRepositoryInterface});


  @override
  Future digitalPaymentPlaceOrder(String? orderNote, String? customerId, String? addressId, String? billingAddressId, String? couponCode, String? couponDiscount, String? paymentMethod, bool? isCheckCreateAccount, String? password) async {
    return await checkoutRepositoryInterface.digitalPaymentPlaceOrder(orderNote, customerId, addressId, billingAddressId, couponCode, couponDiscount, paymentMethod, isCheckCreateAccount, password);
  }




  @override
  Future getReferralAmount(String? amount) async {
    return await checkoutRepositoryInterface.getReferralAmount(amount);
  }
}