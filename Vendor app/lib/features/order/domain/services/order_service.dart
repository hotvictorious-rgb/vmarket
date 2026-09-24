

import 'package:sixvalley_vendor_app/features/order/domain/repositories/order_repository_interface.dart';
import 'package:sixvalley_vendor_app/features/order/domain/services/order_service_interface.dart';
import 'package:sixvalley_vendor_app/features/order_details/domain/models/order_list_filter_model.dart';

class OrderService implements OrderServiceInterface{
  final OrderRepositoryInterface orderRepoInterface;
  OrderService({required this.orderRepoInterface});

  @override
  Future getOrderList(int offset, String status, OrderListFilterModel ? filter) {
    return orderRepoInterface.getOrderList(offset, status, filter);
  }


}