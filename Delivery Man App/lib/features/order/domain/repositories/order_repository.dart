import 'package:get/get_connect/http/src/response/response.dart';
import 'package:sixvalley_delivery_boy/data/api/api_client.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/repositories/order_repository_interface.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';

class OrderRepository implements OrderRepositoryInterface{
  final ApiClient apiClient;

  OrderRepository({required this.apiClient});

  @override
  Future<Response> getCurrentOrders() async {
    return apiClient.getData(AppConstants.currentOrderUri);
  }



  @override
  Future<Response> getAllOrderHistory(String type, String startDate, String endDate, String search, int isPause) async {
    final bool _hasDateRange = (startDate.isNotEmpty && endDate.isNotEmpty);
    final _queryParams = <String, String>{
      'status': type,
      'search': search,
      'is_pause': '$isPause',
      if (_hasDateRange) 'date_type': 'custom_date',
      if (_hasDateRange) 'start_date': startDate,
      if (_hasDateRange) 'end_date': endDate,
    };
    Response response = await apiClient.getData('${AppConstants.allOrderHistoryUri}?${Uri(queryParameters: _queryParams).query}');
      return response;
  }

  @override
  Future<Response> getSingleOrderHistory(String id) async {
    Response response = await apiClient.getData('${AppConstants.singleOrderHistoryUri}?id=$id');
    return response;
  }

  @override
  Future add(value) {
    // TODO: implement add
    throw UnimplementedError();
  }

  @override
  Future delete(int? id) {
    // TODO: implement delete
    throw UnimplementedError();
  }

  @override
  Future get(int? id) {
    // TODO: implement get
    throw UnimplementedError();
  }

  @override
  Future getList() {
    // TODO: implement getList
    throw UnimplementedError();
  }

  @override
  Future update(Map<String, dynamic> body, int? id) {
    // TODO: implement update
    throw UnimplementedError();
  }

}
