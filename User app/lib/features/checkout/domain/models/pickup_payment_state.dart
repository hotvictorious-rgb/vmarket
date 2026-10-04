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
  status['payment_status'] == 'paid' && status['order_id'] != null &&
  RegExp(r'^\d{6}$').hasMatch(status['pickup_verification_code']?.toString() ?? '');
