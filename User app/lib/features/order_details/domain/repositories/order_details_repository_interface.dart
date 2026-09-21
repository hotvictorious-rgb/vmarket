import 'package:flutter_sixvalley_ecommerce/interface/repo_interface.dart';

abstract class OrderDetailsRepositoryInterface<T> extends RepositoryInterface{

  Future<dynamic> getOrderFromOrderId(String orderID);

  Future<dynamic> getOrderInvoice(String orderID);

  Future<dynamic> trackYourOrder(String orderId, String phoneNumber);

  Future<dynamic> getTrackOrderDetailsId(String orderId);



  
}