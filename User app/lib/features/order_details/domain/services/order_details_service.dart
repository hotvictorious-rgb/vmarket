import 'package:flutter_sixvalley_ecommerce/features/order_details/domain/repositories/order_details_repository_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/domain/services/order_details_service_interface.dart';

class OrderDetailsService implements OrderDetailsServiceInterface{
  OrderDetailsRepositoryInterface orderDetailsRepositoryInterface;
  OrderDetailsService({required this.orderDetailsRepositoryInterface});



  @override
  Future getOrderFromOrderId(String orderID) async{
    return await orderDetailsRepositoryInterface.getOrderFromOrderId(orderID);
  }

  @override
  Future getOrderDetails(String orderID) async{
    return await orderDetailsRepositoryInterface.get(orderID);
  }

  @override
  Future getOrderInvoice(String orderID) async{
    return await orderDetailsRepositoryInterface.getOrderInvoice(orderID);
  }

  @override
  Future trackOrder(String orderId, String phoneNumber) async{
    return await orderDetailsRepositoryInterface.trackYourOrder(orderId, phoneNumber);
  }

  @override
  Future getTrackOrderDetailsId(String orderId) async{
    return await orderDetailsRepositoryInterface.getTrackOrderDetailsId(orderId);
  }

  @override
  Future confirmInShopPickup({required int orderId, required String pickupCode, required dynamic verificationImage}) async {
    return await orderDetailsRepositoryInterface.confirmInShopPickup(
      orderId: orderId,
      pickupCode: pickupCode,
      verificationImage: verificationImage,
    );
  }

  @override
  Future confirmDoorstepDelivery({required int orderId, required String deliveryCode, required dynamic verificationImage}) async {
    return await orderDetailsRepositoryInterface.confirmDoorstepDelivery(
      orderId: orderId,
      deliveryCode: deliveryCode,
      verificationImage: verificationImage,
    );
  }

}
