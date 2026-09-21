abstract class OrderDetailsServiceInterface {

  Future<dynamic> getOrderFromOrderId(String orderID);

  Future <dynamic> getOrderDetails(String orderID);

  Future <dynamic> getOrderInvoice(String orderID);

  Future<dynamic> trackOrder(String orderId, String phoneNumber);

  Future<dynamic> getTrackOrderDetailsId(String orderId);



  
}
