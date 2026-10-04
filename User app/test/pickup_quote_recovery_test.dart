import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/di_container.dart' as di;
import 'package:flutter_sixvalley_ecommerce/services/storage_service.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/controllers/checkout_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/services/checkout_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_payment_state.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_reservation_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/screens/pickup_payment_screen.dart';
import 'support/memory_storage.dart';
class PickupService implements CheckoutServiceInterface {
  int payCalls = 0;
  bool durableAtCall = false;
  @override Future<dynamic> quotePickupReservation({required String reservationCode, bool useCashback = false}) async => ApiResponseModel.withSuccess(Response(requestOptions: RequestOptions(path: '/quote'), statusCode: 200, data: {'status': true, 'quote_token': 'frozen-proof', 'quote': {'currency': 'NGN', 'merchandise_subtotal': '101.01', 'tax_total': '7.57', 'shipping_total': '0.00', 'cashback_amount': '20.00', 'total_amount': '88.58', 'expires_at': '2026-10-05'}}));
  @override Future<dynamic> payPickupReservation({String? quoteToken, required String reservationCode, bool useCashback = false, String paymentGateway = 'paystack', int ttlMinutes = 30}) async {
    payCalls++;
    final storage = di.sl<StorageService>();
    final pending = PickupPaymentState.decode(storage.getString(PickupPaymentState.storageKey), 'owner');
    durableAtCall = pending?.quoteToken == quoteToken && pending?.reservationCode == reservationCode;
    throw StateError('Connection lost after gateway may have initialized');
  }
  @override dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
void main() {
  late MemoryStorage storage; late PickupService service; late CheckoutController controller;
  setUp(() async { await di.sl.reset(); storage = MemoryStorage(); storage.values[AppConstants.userLoginToken] = 'owner'; di.sl.registerSingleton<StorageService>(storage); service = PickupService(); controller = CheckoutController(checkoutServiceInterface: service); });
  tearDown(() async => di.sl.reset());
  testWidgets('pickup quote displays exact backend decimals and cancel never initializes payment', (tester) async {
    await tester.pumpWidget(ChangeNotifierProvider.value(value: controller, child: MaterialApp(home: PickupPaymentScreen(reservation: PickupReservationModel(reservationCode: 'R-one', totalAmount: '999.99')))));
    await tester.tap(find.text('Review payment')); await tester.pumpAndSettle();
    expect(find.text('Total to pay: NGN 88.58'), findsOneWidget);
    expect(find.text('Tax: NGN 7.57'), findsOneWidget);
    expect(service.payCalls, 0);
    await tester.tap(find.text('Cancel')); await tester.pumpAndSettle();
    expect(service.payCalls, 0); expect(controller.pendingPickupPayment, isNull);
  });
  test('initialization failure retains durable owner-scoped recovery identity before network call', () async {
    await controller.payPickupReservation(quoteToken: 'frozen-proof', reservationCode: 'R-one', useCashback: true);
    expect(service.durableAtCall, true); expect(service.payCalls, 1);
    final restarted = CheckoutController(checkoutServiceInterface: service);
    expect(restarted.pendingPickupPayment?.reservationCode, 'R-one');
    expect(restarted.pendingPickupPayment?.useCashback, true);
    expect(PickupPaymentState.decode(storage.getString(PickupPaymentState.storageKey), 'other-owner'), isNull);
    await restarted.payPickupReservation(quoteToken: 'other-proof', reservationCode: 'R-two');
    expect(service.payCalls, 1);
  });
  test('callback success or order identifier alone never proves pickup paid or exposes delivery OTP', () {
    expect(pickupPaymentCompleted({'status': true, 'order_id': 1, 'payment_status': 'pending', 'pickup_verification_code': '123456'}), false);
    expect(pickupPaymentCompleted({'status': true, 'order_id': 1, 'payment_status': 'paid', 'verification_code': '123456'}), false);
    expect(pickupPaymentCompleted({'status': true, 'order_id': 1, 'payment_status': 'paid', 'pickup_verification_code': '123456'}), true);
  });
  test('authoritative unpaid without attempt releases recovery; ambiguous attempts stay blocked', () {
    expect(pickupPaymentMayRetry({'status': true, 'payment_status': 'unpaid', 'payment_request_id': null}), true);
    expect(pickupPaymentMayRetry({'status': true, 'payment_status': 'unpaid', 'payment_request_id': 'PR-one'}), false);
    expect(pickupPaymentMayRetry({'status': true, 'payment_status': 'pending', 'payment_request_id': 'PR-one'}), false);
    expect(pickupPaymentMayRetry({'payment_status': 'unpaid'}), false);
  });
  testWidgets('malformed quote never opens confirmation or initializes payment', (tester) async {
    final malformed = MalformedPickupService();
    final checkout = CheckoutController(checkoutServiceInterface: malformed);
    await tester.pumpWidget(ChangeNotifierProvider.value(value: checkout, child: MaterialApp(home: PickupPaymentScreen(reservation: PickupReservationModel(reservationCode: 'R-one')))));
    await tester.tap(find.text('Review payment')); await tester.pumpAndSettle();
    expect(find.text('Confirm payment'), findsNothing); expect(malformed.payCalls, 0);
    expect(find.text('Unable to prepare the quote. Please try again.'), findsOneWidget);
  });

}

class MalformedPickupService extends PickupService {
  @override Future<dynamic> quotePickupReservation({required String reservationCode, bool useCashback = false}) async =>
    ApiResponseModel.withSuccess(Response(requestOptions: RequestOptions(path: '/quote'), statusCode: 200, data: {'status': true, 'quote_token': '', 'quote': {'total_amount': 12.5}}));
}
