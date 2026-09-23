import 'package:get/get_connect/connect.dart';
import 'package:sixvalley_delivery_boy/data/api/api_client.dart';
import 'package:sixvalley_delivery_boy/features/wallet/domain/repositories/wallet_repository_interface.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';


class WalletRepository implements WalletRepositoryInterface{
  final ApiClient apiClient;
  WalletRepository({required this.apiClient});

  @override
  Future<Response> getDeliveryWiseEarned({String? startDate, String? endDate, int? offset, String? type}) async {
    final _queryParams = <String, String>{
      'limit': '10',
      'offset': '$offset',
      'type': '$type',
      if ((startDate ?? '').isNotEmpty) 'start_date': '$startDate',
      if ((endDate ?? '').isNotEmpty) 'end_date': '$endDate',
    };
    return apiClient.getData('${AppConstants.deliveryWiseEarnedUri}?${Uri(queryParameters: _queryParams).query}');
  }

  @override
  

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
