import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/repositories/checkout_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/services/checkout_service_interface.dart';

class CheckoutService implements CheckoutServiceInterface{
  CheckoutRepositoryInterface checkoutRepositoryInterface;


  CheckoutService({required this.checkoutRepositoryInterface});


  @override
  Future digitalPaymentPlaceOrder(String? orderNote, String? customerId, String? addressId, String? billingAddressId, String? paymentMethod, bool? isCheckCreateAccount, String? password, {bool useCashback = false}) async {
    return await checkoutRepositoryInterface.digitalPaymentPlaceOrder(orderNote, customerId, addressId, billingAddressId, paymentMethod, isCheckCreateAccount, password, useCashback: useCashback);
  }




  @override
  Future getReferralAmount(String? amount) async {
    return await checkoutRepositoryInterface.getReferralAmount(amount);
  }

  @override
  Future createPickupReservation({required String idempotencyKey, List<int>? cartIds, bool? checkedOnly}) async {
    return await checkoutRepositoryInterface.createPickupReservation(idempotencyKey: idempotencyKey, cartIds: cartIds, checkedOnly: checkedOnly);
  }

  @override
  Future payPickupReservation({required String reservationCode, bool useCashback = false, String paymentGateway = 'paystack', int ttlMinutes = 30}) async {
    return await checkoutRepositoryInterface.payPickupReservation(reservationCode: reservationCode, useCashback: useCashback, paymentGateway: paymentGateway, ttlMinutes: ttlMinutes);
  }

  // [AI] Authoritative Fulfillment & Delivery Intent Methods
  @override
  Future checkFulfillmentAvailability({required int shopId, int? shippingAddressId, List<Map<String, dynamic>>? cartItems}) async {
    return await checkoutRepositoryInterface.checkFulfillmentAvailability(shopId: shopId, shippingAddressId: shippingAddressId, cartItems: cartItems);
  }

  @override
  Future createDeliveryCheckoutIntent({required int addressId, required String idempotencyKey, int? billingAddressId, bool useCashback = false, List<int>? cartItemIds}) async {
    return await checkoutRepositoryInterface.createDeliveryCheckoutIntent(addressId: addressId, idempotencyKey: idempotencyKey, billingAddressId: billingAddressId, useCashback: useCashback, cartItemIds: cartItemIds);
  }

  @override
  Future initializeIntentPayment({required String orderGroupId}) async {
    return await checkoutRepositoryInterface.initializeIntentPayment(orderGroupId: orderGroupId);
  }
}