import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_sixvalley_ecommerce/features/refund/domain/models/refund_display_status.dart';
void main() {
  test('seller recommendations remain awaiting administrator review', () {
    expect(refundDisplayStatus('approved', 'seller'), contains('Awaiting administrator review'));
    expect(refundDisplayStatus('rejected', 'seller'), contains('Awaiting administrator review'));
    expect(refundDisplayStatus('rejected', 'admin'), 'rejected');
    expect(refundDisplayStatus('refunded', 'admin'), 'refunded');
  });
}
