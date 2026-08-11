import 'package:sixvalley_delivery_boy/data/api/api_client.dart';
import 'package:get/get_connect/http/src/response/response.dart';

abstract class OrderDetailsServiceInterface {
  Future<dynamic> getOrderDetails({String? orderID});
  Future<dynamic> updateOrderStatus({int? orderId, String? status, String? pickupVerificationCode});
  Future<dynamic> rescheduleOrder({int? orderId, String? deliveryDate, String? cause});
  Future<dynamic> pauseAndResumeOrder({int? orderId, int? isPos, String? cause});
  Future<dynamic> cancelOrderStatus({int? orderId, String? cause});
  Future<dynamic> updatePaymentStatus({int? orderId, String? status});
  Future<Response?> uploadOrderVerificationImage( String orderId, List<MultipartBody>? verificationImage);
  Future<dynamic> verifyOrderDeliveryOtp({int? orderId, String? verificationCode});
  Future<dynamic> resendOtpForOrderVerification({int? orderId});
  Future<dynamic> generatePaystackLink({int? orderId});
}