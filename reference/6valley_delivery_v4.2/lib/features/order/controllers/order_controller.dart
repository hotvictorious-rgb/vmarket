import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:intl/intl.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/services/order_service_interface.dart';
import 'package:sixvalley_delivery_boy/data/api/api_checker.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';

class OrderController extends GetxController implements GetxService {
  final OrderServiceInterface orderServiceInterface;
  OrderController({required this.orderServiceInterface});


  List<OrderModel> _currentOrders = [];
  List<OrderModel> get currentOrders => _currentOrders;
  bool _isLoading = false;
  bool get isLoading => _isLoading;
  int _orderTypeIndex = 0;
  int get orderTypeIndex => _orderTypeIndex;
  List<OrderModel> _allOrderHistory = [];
  List<OrderModel> pauseOrderHistory = [];
  List<OrderModel> deliveredOrderHistory = [];
  List<OrderModel> get allOrderHistory => _allOrderHistory;

  String? selectedOrderLat = '23.83721';
  String? selectedOrderLng = '90.363715';

  void setSelectedOrderLatLng(LatLng latLng){
    selectedOrderLat = latLng.latitude.toString();
    selectedOrderLng = latLng.longitude.toString();
  }


  void selectedOrderLatLng(String? lat, String? lng){
    selectedOrderLat = lat;
    selectedOrderLng = lng;
    update();
  }



  List<OrderModel>? _orderList;
  List<OrderModel>? get orderList => _orderList != null ? _orderList!.reversed.toList() : _orderList;



  Future<void> getCurrentOrders() async {
    _isLoading = true;
    _currentOrders = await orderServiceInterface.getCurrentOrders();
    _isLoading = false;
    update();
  }




  Future <void> getAllOrderHistory(String type, String startDate, String endDate, String search, int isPause) async {
    _isLoading = true;
    Response response = await orderServiceInterface.getAllOrderHistory(type,startDate,endDate, search, isPause);
    if (response.body != null && response.statusCode == 200) {
      _allOrderHistory = [];
      pauseOrderHistory = [];
      deliveredOrderHistory = [];
      if(type == 'delivered'){
        response.body.forEach((order) {deliveredOrderHistory.add(OrderModel.fromJson(order));});
      }else if(isPause == 1 ){
        response.body.forEach((order) {pauseOrderHistory.add(OrderModel.fromJson(order));});
      }else{
        response.body.forEach((order) {_allOrderHistory.add(OrderModel.fromJson(order));});
      }
    } else {
      ApiChecker.checkApi(response);
    }

    _isLoading = false;
    update();
  }




  Future orderRefresh(BuildContext context) async{
    getCurrentOrders();
    return getCurrentOrders();
  }




  void setOrderTypeIndex(int index, {String startDate = '', String endDate = '', String search = '', bool reload = false, isUpdate = true}) {
    _orderTypeIndex = index;
    if(orderTypeIndex == 0){
      getAllOrderHistory('', startDate, endDate, search, 0);
    }else if(orderTypeIndex == 1){
      getAllOrderHistory('out_for_delivery', startDate, endDate, search, 0);
    } else if(orderTypeIndex == 2){
      getAllOrderHistory('', startDate, endDate, search, 1);
    } else if(orderTypeIndex == 3){
      getAllOrderHistory('delivered', startDate, endDate, search, 0);
    }else if(orderTypeIndex == 4){
      getAllOrderHistory('return', startDate, endDate, search, 0);
    }else if(orderTypeIndex == 5){
      getAllOrderHistory('canceled', startDate, endDate, search, 0);
    }

    if(isUpdate){
      update();
    }


  }

  DateTime? _startDate;
  final DateFormat _dateFormat = DateFormat('yyyy-MM-d');
  DateTime? get startDate => _startDate;
  DateFormat get dateFormat => _dateFormat;

  void selectDate(BuildContext context){
    showDatePicker(

      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2022),
      lastDate: DateTime(2030),
    ).then((date) {
      _startDate = date;
      update();
    });
  }


  TextEditingController searchOrderController = TextEditingController();

  void setSearchText({String? searchText, bool isUpdate = true}){
    searchOrderController.text = searchText ?? '';
    if(isUpdate){
      update();
    }
  }


}
