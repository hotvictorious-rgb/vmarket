import 'dart:convert';

class PickupPaymentState {
  static const storageKey = 'pending_pickup_payment_v1';
  final String ownerToken;
  final String reservationCode;
  final String quoteToken;
  final bool useCashback;
  const PickupPaymentState({required this.ownerToken, required this.reservationCode,
    required this.quoteToken, required this.useCashback});
  String encode() => jsonEncode({'owner': ownerToken, 'code': reservationCode,
    'quote_token': quoteToken, 'use_cashback': useCashback});
  static PickupPaymentState? decode(String? raw, String? owner) {
    try {
      final data = jsonDecode(raw ?? '') as Map;
      if (owner == null || owner.isEmpty || data['owner'] != owner) return null;
      return PickupPaymentState(ownerToken: owner, reservationCode: data['code'] as String,
        quoteToken: data['quote_token'] as String, useCashback: data['use_cashback'] == true);
    } catch (_) { return null; }
  }
}

bool pickupPaymentCompleted(Map<String, dynamic> status) =>
  (status['status'] == null || status['status'] == true || status['status'] == 'success') &&
  status['payment_status'] == 'paid' &&
  int.tryParse(status['order_id']?.toString() ?? '') != null &&
  RegExp(r'^\d{6}$').hasMatch(status['pickup_verification_code']?.toString() ?? '');

bool pickupPaymentMayRetry(Map<String, dynamic> status) =>
  (status['status'] == null || status['status'] == true || status['status'] == 'success') &&
  (status['payment_status'] == 'expired' || status['payment_status'] == 'failed' ||
    (status['payment_status'] == 'unpaid' && status.containsKey('payment_request_id') && status['payment_request_id'] == null));

bool validPickupQuote(dynamic data) {
  if (data is! Map || data['status'] != true || data['quote_token'] is! String ||
      (data['quote_token'] as String).isEmpty || data['quote'] is! Map) {
    return false;
  }
  final quote = data['quote'] as Map;
  if (quote['currency'] != 'NGN' || quote['expires_at'] is! String) {
    return false;
  }
  for (final key in ['merchandise_subtotal', 'tax_total', 'shipping_total', 'cashback_amount', 'total_amount']) {
    if (quote[key] is! String || !RegExp(r'^\d+\.\d{2}$').hasMatch(quote[key])) {
      return false;
    }
  }
  return true;
}
