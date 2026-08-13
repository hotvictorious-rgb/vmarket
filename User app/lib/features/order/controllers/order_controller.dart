import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/domain/models/order_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/domain/services/order_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/helper/api_checker.dart';



class OrderController with ChangeNotifier {
  final OrderServiceInterface orderServiceInterface;
  OrderController({required this.orderServiceInterface});

  bool _isLoading = false;
  bool get isLoading => _isLoading;

  OrderModel? runningOrderModel;
  OrderModel? deliveredOrderModel;
  OrderModel? canceledOrderModel;

  OrderModel? get orderModel {
    if (_orderTypeIndex == 0) return runningOrderModel;
    if (_orderTypeIndex == 1) return deliveredOrderModel;
    return canceledOrderModel;
  }

  set orderModel(OrderModel? model) {
    if (_orderTypeIndex == 0) {
      runningOrderModel = model;
    } else if (_orderTypeIndex == 1) {
      deliveredOrderModel = model;
    } else {
      canceledOrderModel = model;
    }
  }

  Future<void> getOrderList(int offset, String status, {String? type, bool refresh = false}) async {
    // Check if we already have data for this tab on offset 1 to provide instant UI
    if (offset == 1 && !refresh) {
      if (status == 'ongoing' && runningOrderModel != null && runningOrderModel!.orders!.isNotEmpty) {
        notifyListeners();
      } else if (status == 'delivered' && deliveredOrderModel != null && deliveredOrderModel!.orders!.isNotEmpty) {
        notifyListeners();
      } else if (status == 'canceled' && canceledOrderModel != null && canceledOrderModel!.orders!.isNotEmpty) {
        notifyListeners();
      }
    }

    if (offset == 1 && refresh) {
      if (status == 'ongoing') runningOrderModel = null;
      if (status == 'delivered') deliveredOrderModel = null;
      if (status == 'canceled') canceledOrderModel = null;
      notifyListeners();
    }

    ApiResponseModel apiResponse = await orderServiceInterface.getOrderList(offset, status, type: type);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      OrderModel fetchedModel = OrderModel.fromJson(apiResponse.response?.data);

      if (status == 'ongoing') {
        if (offset == 1) {
          runningOrderModel = fetchedModel;
        } else if (runningOrderModel != null && runningOrderModel!.orders != null) {
          runningOrderModel!.orders!.addAll(fetchedModel.orders ?? []);
          runningOrderModel!.offset = fetchedModel.offset;
          runningOrderModel!.totalSize = fetchedModel.totalSize;
        }
      } else if (status == 'delivered') {
        if (offset == 1) {
          deliveredOrderModel = fetchedModel;
        } else if (deliveredOrderModel != null && deliveredOrderModel!.orders != null) {
          deliveredOrderModel!.orders!.addAll(fetchedModel.orders ?? []);
          deliveredOrderModel!.offset = fetchedModel.offset;
          deliveredOrderModel!.totalSize = fetchedModel.totalSize;
        }
      } else if (status == 'canceled') {
        if (offset == 1) {
          canceledOrderModel = fetchedModel;
        } else if (canceledOrderModel != null && canceledOrderModel!.orders != null) {
          canceledOrderModel!.orders!.addAll(fetchedModel.orders ?? []);
          canceledOrderModel!.offset = fetchedModel.offset;
          canceledOrderModel!.totalSize = fetchedModel.totalSize;
        }
      }
    } else {
      if (offset == 1) {
        OrderModel emptyModel = OrderModel(orders: [], totalSize: 0, offset: '1', limit: '10');
        if (status == 'ongoing') runningOrderModel = emptyModel;
        if (status == 'delivered') deliveredOrderModel = emptyModel;
        if (status == 'canceled') canceledOrderModel = emptyModel;
      }
      ApiChecker.checkApi(apiResponse);
    }
    notifyListeners();
  }

  int _orderTypeIndex = 0;
  int get orderTypeIndex => _orderTypeIndex;

  String selectedType = 'ongoing';
  void setIndex(int index, {bool notify = true}) {
    _orderTypeIndex = index;
    if (_orderTypeIndex == 0) {
      selectedType = 'ongoing';
      if (runningOrderModel == null || runningOrderModel!.orders == null) {
        getOrderList(1, 'ongoing');
      }
    } else if (_orderTypeIndex == 1) {
      selectedType = 'delivered';
      if (deliveredOrderModel == null || deliveredOrderModel!.orders == null) {
        getOrderList(1, 'delivered');
      }
    } else if (_orderTypeIndex == 2) {
      selectedType = 'canceled';
      if (canceledOrderModel == null || canceledOrderModel!.orders == null) {
        getOrderList(1, 'canceled');
      }
    }
    if (notify) {
      notifyListeners();
    }
  }

  Orders? trackingModel;
  Future<void> initTrackingInfo(String orderID) async {
    ApiResponseModel apiResponse = await orderServiceInterface.getTrackingInfo(orderID);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      trackingModel = Orders.fromJson(apiResponse.response!.data);
    }
    notifyListeners();
  }

  Future<ApiResponseModel> cancelOrder(BuildContext context, int? orderId) async {
    _isLoading = true;
    ApiResponseModel apiResponse = await orderServiceInterface.cancelOrder(orderId);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      _isLoading = false;
      // Refresh current active orders list
      getOrderList(1, selectedType, refresh: true);
    } else {
      _isLoading = false;
      ApiChecker.checkApi(apiResponse);
    }
    notifyListeners();
    return apiResponse;
  }

  void resetOrderList({bool isUpdate = true}) {
    runningOrderModel = null;
    deliveredOrderModel = null;
    canceledOrderModel = null;

    if (isUpdate) {
      notifyListeners();
    }
  }
}
