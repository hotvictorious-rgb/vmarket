import 'package:flutter_sixvalley_ecommerce/features/fulfillment/domain/repositories/fulfillment_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/domain/services/fulfillment_service_interface.dart';

class FulfillmentService implements FulfillmentServiceInterface {
  final FulfillmentRepositoryInterface fulfillmentRepositoryInterface;

  FulfillmentService({required this.fulfillmentRepositoryInterface});

  @override
  Future checkAvailability({required int shopId, int? shippingAddressId, List<Map<String, dynamic>>? cartItems}) async {
    return await fulfillmentRepositoryInterface.checkAvailability(
      shopId: shopId,
      shippingAddressId: shippingAddressId,
      cartItems: cartItems
    );
  }

  @override
  Future getDeliveryFee({required int shopId, required int shippingAddressId, List<Map<String, dynamic>>? cartItems}) async {
    return await fulfillmentRepositoryInterface.getDeliveryFee(
      shopId: shopId,
      shippingAddressId: shippingAddressId,
      cartItems: cartItems
    );
  }
}
