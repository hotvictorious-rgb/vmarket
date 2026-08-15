import 'package:sixvalley_delivery_boy/data/api/api_client.dart';

abstract class OrderDetailsServiceInterface {
  Future<dynamic> getOrderDetails({String? orderID});
  Future<dynamic> updateOrderStatus({int? orderId, String? status});
  Future<dynamic> rescheduleOrder({int? orderId, String? deliveryDate, String? cause});
  Future<dynamic> pauseAndResumeOrder({int? orderId, int? isPos, String? cause});
  Future<dynamic> cancelOrderStatus({int? orderId, String? cause});
  Future<dynamic> updatePaymentStatus({int? orderId, String? status});
  Future<dynamic> uploadOrderVerificationImage( String orderId, List<MultipartBody>? verificationImage);
  Future<dynamic> verifyOrderDeliveryOtp({int? orderId, String? verificationCode});
  Future<dynamic> resendOtpForOrderVerification({int? orderId});
}