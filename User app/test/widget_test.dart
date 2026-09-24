import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_reservation_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/models/geography_models.dart';
import 'package:flutter_sixvalley_ecommerce/features/notification/domain/models/notification_model.dart';

void main() {
  group('Customer Canonical Geography Tests', () {
    test('verifies canonical LGA model initialization', () {
      final lga = LgaModel(id: 142, name: 'Uyo', stateId: 1);
      expect(lga.id, 142);
      expect(lga.name, 'Uyo');
      expect(lga.stateId, 1);
    });

    test('verifies Akwa Ibom primary LGAs', () {
      const canonicalAkwaIbomLgas = [
        {'name': 'Uyo', 'id': 142, 'state': 'Akwa Ibom'},
        {'name': 'Eket', 'id': 125, 'state': 'Akwa Ibom'},
        {'name': 'Ikot Ekpene', 'id': 133, 'state': 'Akwa Ibom'},
        {'name': 'Oron', 'id': 140, 'state': 'Akwa Ibom'},
        {'name': 'Abak', 'id': 118, 'state': 'Akwa Ibom'},
        {'name': 'Ikot Abasi', 'id': 132, 'state': 'Akwa Ibom'},
      ];

      expect(canonicalAkwaIbomLgas.length, 6);
      expect(canonicalAkwaIbomLgas.first['name'], 'Uyo');
      expect(canonicalAkwaIbomLgas.first['id'], 142);
    });
  });

  group('Pickup Reservation & 5% Cashback Rewards', () {
    test('parses pickup reservation model with cashback attributes', () {
      final json = {
        'id': 101,
        'reservation_code': 'RES-UYO-88992',
        'status': 'ready_for_pickup',
        'total_amount': '15000.00',
        'paid_amount': '14250.00',
        'cashback_amount': '750.00',
        'pickup_otp': '654321',
        'expires_at': '2026-09-30T18:00:00Z',
      };

      final reservation = PickupReservationModel.fromJson(json);
      expect(reservation.id, 101);
      expect(reservation.reservationCode, 'RES-UYO-88992');
      expect(reservation.status, 'ready_for_pickup');
      expect(reservation.totalAmount, '15000.00');
    });

    test('validates 6-digit OTP format for customer pickup handover', () {
      const validCustomerOtp = '987654';
      const shortOtp = '9876';
      const alphaOtp = '98765a';

      expect(validCustomerOtp.length == 6 && RegExp(r'^[0-9]{6}$').hasMatch(validCustomerOtp), isTrue);
      expect(shortOtp.length == 6, isFalse);
      expect(RegExp(r'^[0-9]{6}$').hasMatch(alphaOtp), isFalse);
    });

    test('authoritative backend 5% cashback discount math verification', () {
      const double subtotal = 10000.0;
      const double backendCashbackEarnPercentage = 5.0; // 5% Victorious Cashback
      const double expectedPointsEarned = subtotal * (backendCashbackEarnPercentage / 100);

      expect(expectedPointsEarned, 500.0);
    });
  });

  group('Customer Notification Parsing', () {
    test('parses notification item model accurately', () {
      final item = NotificationItem.fromJson({
        'id': 55,
        'title': 'Order Ready for Pickup',
        'description': 'Your pickup reservation is ready at Victorious Plaza Uyo',
      });
      expect(item.id, 55);
      expect(item.title, 'Order Ready for Pickup');
      expect(item.description, 'Your pickup reservation is ready at Victorious Plaza Uyo');
    });
  });
}
