import 'dart:convert';

/// Durable identity only; amounts and payment outcomes always come from the API.
class DeliveryPaymentState {
  static const storageKey = 'pending_delivery_payment';
  final String ownerToken;
  final String requestSignature;
  final String idempotencyKey;
  final String? orderGroupId;
  final String? paymentRequestId;
  final bool initializationStarted;
  const DeliveryPaymentState(
      {required this.ownerToken,
      required this.requestSignature,
      required this.idempotencyKey,
      this.orderGroupId,
      this.paymentRequestId, this.initializationStarted = false});
  String encode() => jsonEncode({
        'owner': ownerToken,
        'request': requestSignature,
        'key': idempotencyKey,
        'group': orderGroupId,
        'attempt': paymentRequestId,
        'initialization_started': initializationStarted
      });
  static DeliveryPaymentState? decode(String? value, String? ownerToken) {
    if (value == null || ownerToken == null || ownerToken.isEmpty) return null;
    try {
      final data = jsonDecode(value);
      if (data['owner'] != ownerToken ||
          data['key'] is! String ||
          data['request'] is! String) { return null; }
      return DeliveryPaymentState(
          ownerToken: ownerToken,
          requestSignature: data['request'],
          idempotencyKey: data['key'],
          orderGroupId: data['group'],
          paymentRequestId: data['attempt'], initializationStarted: data['initialization_started'] == true);
    } catch (_) {
      return null;
    }
  }
}

bool deliveryPaymentCompleted(Map<String, dynamic> data) {
  final orders = data['orders'];
  return data['intent_status'] == 'converted_to_orders' &&
      data['payment_status'] == 'paid' &&
      orders is List &&
      orders.isNotEmpty &&
      orders.every((order) => order['payment_status'] == 'paid');
}

String? pickupHandoverCode(Map<String, dynamic> order) {
  final code = order['pickup_verification_code']?.toString();
  return code != null && RegExp(r'^\d{6}$').hasMatch(code) ? code : null;
}
