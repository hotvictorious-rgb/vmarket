abstract class FulfillmentServiceInterface {
  Future checkAvailability({required int shopId, int? shippingAddressId, List<Map<String, dynamic>>? cartItems});
  Future getDeliveryFee({required int shopId, required int shippingAddressId, List<Map<String, dynamic>>? cartItems});
}
