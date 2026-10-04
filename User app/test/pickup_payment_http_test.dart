import 'dart:convert';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_payment_status_client.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_payment_state.dart';

void main() {
  test('authenticated pickup HTTP status encodes path and supports unpaid/no-attempt recovery', () async {
    final client = MockClient((request) async {
      expect(request.method, 'GET');
      expect(request.headers['Authorization'], 'Bearer owner-proof');
      expect(request.url.pathSegments[4], 'R/?# &');
      expect(request.url.query, isEmpty); expect(request.url.fragment, isEmpty);
      return http.Response(jsonEncode({'status': true, 'reservation_status': 'inspected_accepted', 'payment_status': 'unpaid', 'payment_request_id': null}), 200);
    });
    final result = await fetchPickupPaymentStatus(client, 'https://example.test', '/api/v1/customer/pickup-reservations', 'R/?# &', 'owner-proof');
    expect(pickupPaymentMayRetry(result), true);
    client.close();
  });
  test('malformed or unauthenticated HTTP response never supplies payment proof', () async {
    for (final response in [http.Response('{}', 200), http.Response('[]', 200), http.Response('{"status":true,"payment_status":"success"}', 200), http.Response('{}', 401)]) {
      final client = MockClient((_) async => response);
      await expectLater(fetchPickupPaymentStatus(client, 'https://example.test', '/pickup', 'R-one', 'owner'), throwsA(anything));
      client.close();
    }
  });
}

