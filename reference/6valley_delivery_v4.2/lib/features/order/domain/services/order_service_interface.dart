
abstract class OrderServiceInterface {
  Future<dynamic> getCurrentOrders();
  Future<dynamic> getAllOrderHistory(String type, String startDate, String endDate, String search, int isPause);
  Future<dynamic> getSingleOrderHistory(String id);

}