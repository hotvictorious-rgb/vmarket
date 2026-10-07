abstract class OrderDetailsServiceInterface {

  Future<dynamic> getOrderFromOrderId(String orderID);

  Future <dynamic> getOrderDetails(String orderID);

  Future <dynamic> getOrderInvoice(String orderID);

  Future<dynamic> trackOrder(String orderId, String phoneNumber);

  Future<dynamic> getTrackOrderDetailsId(String orderId);

  Future<dynamic> confirmInShopPickup({required int orderId, required String pickupCode, required dynamic verificationImage});

  Future<dynamic> confirmDoorstepDelivery({required int orderId, required String deliveryCode, required dynamic verificationImage});
}
