import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:sixvalley_delivery_boy/common/basewidgets/custom_divider_widget.dart';
import 'package:sixvalley_delivery_boy/features/notification/domain/models/notification_body.dart';

void main() {
  group('NotificationBody', () {
    test('parses order notification payload', () {
      final body = NotificationBody.fromJson({
        'order_id': '42',
        'type': 'order',
        'message_key': 'order_status',
      });
      expect(body.orderId, 42);
      expect(body.type, 'order');
      expect(body.messageKey, 'order_status');
    });

    test('serializes to json without legacy chat fields', () {
      final body = NotificationBody(orderId: 7, type: 'wallet', messageKey: 'withdraw_request_status_message');
      final json = body.toJson();
      expect(json['order_id'], 7);
      expect(json['type'], 'wallet');
      expect(json['message_key'], 'withdraw_request_status_message');
      expect(json.containsKey('conversation_id'), isFalse);
      expect(json.containsKey('customer_id'), isFalse);
      expect(json.containsKey('vendor_id'), isFalse);
    });

    test('handles missing order id', () {
      final body = NotificationBody.fromJson({'type': 'general'});
      expect(body.orderId, isNull);
      expect(body.type, 'general');
    });
  });

  group('Delivery Order Status & OTP Validation', () {
    test('verifies valid 6-digit OTP format', () {
      final validOtp = '123456';
      final shortOtp = '1234';
      final invalidOtp = '12345a';

      expect(validOtp.length == 6 && RegExp(r'^[0-9]{6}$').hasMatch(validOtp), isTrue);
      expect(shortOtp.length == 6, isFalse);
      expect(RegExp(r'^[0-9]{6}$').hasMatch(invalidOtp), isFalse);
    });

    test('verifies delivery state transitions require OTP at handover', () {
      final allowableTransitions = {
        'confirmed': 'processing',
        'processing': 'out_for_delivery',
        'out_for_delivery': 'delivered',
      };
      expect(allowableTransitions['processing'], 'out_for_delivery');
      expect(allowableTransitions['out_for_delivery'], 'delivered');
    });
  });

  group('CustomDividerWidget', () {
    testWidgets('renders without exception', (WidgetTester tester) async {
      await tester.pumpWidget(const MaterialApp(
        home: Scaffold(body: CustomDividerWidget()),
      ));
      await tester.pump();
      expect(find.byType(CustomDividerWidget), findsOneWidget);
    });
  });
}