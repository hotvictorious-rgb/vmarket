import '../lib/features/checkout/domain/models/delivery_payment_state.dart';

void check(bool passed, String message) {
  if (!passed) throw StateError(message);
}

void main() {
  const state = DeliveryPaymentState(ownerToken: 'customer-a', requestSignature: 'address-cart',
    idempotencyKey: 'checkout-key', orderGroupId: 'OG-1', paymentRequestId: 'PR-1');
  final recovered = DeliveryPaymentState.decode(state.encode(), 'customer-a');
  check(recovered?.orderGroupId == 'OG-1', 'App restart lost checkout identity');
  check(recovered?.idempotencyKey == 'checkout-key', 'Retry changed idempotency identity');
  check(recovered?.paymentRequestId == 'PR-1', 'Restart lost attempt identity');
  check(DeliveryPaymentState.decode(state.encode(), 'customer-b') == null, 'Cross-account pending intent exposed');
  check(DeliveryPaymentState.decode(state.encode(), '') == null, 'Logged-out recovery accepted');
  check(DeliveryPaymentState.decode('invalid-json', 'customer-a') == null, 'Corrupt state did not recover safely');
  final complete = <String, dynamic>{'intent_status': 'converted_to_orders', 'payment_status': 'paid',
    'orders': [{'id': 1, 'payment_status': 'paid'}, {'id': 2, 'payment_status': 'paid'}]};
  check(deliveryPaymentCompleted(complete), 'Canonical completion rejected');
  check(!deliveryPaymentCompleted({...complete, 'intent_status': 'pending'}), 'Capture without conversion shown successful');
  check(!deliveryPaymentCompleted({...complete, 'payment_status': 'reconciliation_required'}), 'Anomaly shown successful');
  check(!deliveryPaymentCompleted({...complete, 'orders': []}), 'Payment without placed orders shown successful');
  check(!deliveryPaymentCompleted({...complete, 'orders': [{'payment_status': 'unpaid'}]}), 'Unpaid order shown successful');
  check(pickupHandoverCode({'verification_code': '111111', 'pickup_verification_code': '222222'}) == '222222', 'Delivery OTP selected for pickup');
  check(pickupHandoverCode({'verification_code': '111111'}) == null, 'Missing pickup OTP fabricated');
  check(pickupHandoverCode({'pickup_verification_code': 'XXXXXX'}) == null, 'Placeholder OTP accepted');
  print('14 delivery recovery and pickup OTP regressions passed');
}
