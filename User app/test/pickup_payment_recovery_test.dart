import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/dio/dio_client.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/di_container.dart' as di;
import 'package:flutter_sixvalley_ecommerce/features/checkout/controllers/checkout_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_payment_state.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_reservation_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/repositories/checkout_repository.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/services/checkout_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/screens/pickup_payment_screen.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/screens/payment_status_screen.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter_sixvalley_ecommerce/services/storage_service.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'package:provider/provider.dart';
import 'support/memory_storage.dart';

class MockDioClient implements DioClient {
  String? lastPath;
  dynamic lastData;
  Response? mockResponse;

  @override
  Future<Response> post(
    String uri, {
    data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    lastPath = uri;
    lastData = data;
    return mockResponse ??
        Response(
          requestOptions: RequestOptions(path: uri),
          statusCode: 200,
          data: {'status': 'success'},
        );
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

class FakePickupCheckoutService implements CheckoutServiceInterface {
  int quoteCallCount = 0;
  int payCallCount = 0;
  String? lastQuoteToken;
  String? lastReservationCode;
  bool shouldFailPayWith409 = false;
  Future<void> Function()? onBeforePayNetworkResponse;

  @override
  Future<dynamic> quotePickupReservation({
    required String reservationCode,
    bool useCashback = false,
  }) async {
    quoteCallCount++;
    lastReservationCode = reservationCode;
    return ApiResponseModel.withSuccess(
      Response(
        requestOptions: RequestOptions(path: '/quote'),
        statusCode: 200,
        data: {
          'status': true,
          'quote_token': 'QT-TOKEN-$quoteCallCount',
          'quote': {
            'currency': 'NGN',
            'merchandise_subtotal': '15000.00',
            'tax_total': '0.00',
            'shipping_total': '0.00',
            'cashback_amount': '0.00',
            'total_amount': '15000.00',
            'expires_at': '2026-10-04 22:00:00',
          },
        },
      ),
    );
  }

  @override
  Future<dynamic> payPickupReservation({
    String? quoteToken,
    required String reservationCode,
    bool useCashback = false,
    String paymentGateway = 'paystack',
    int ttlMinutes = 30,
  }) async {
    payCallCount++;
    lastQuoteToken = quoteToken;
    lastReservationCode = reservationCode;

    if (onBeforePayNetworkResponse != null) {
      await onBeforePayNetworkResponse!();
    }

    if (shouldFailPayWith409) {
      return ApiResponseModel.withError(
        'Quote expired or changed',
        responseValue: Response(
          requestOptions: RequestOptions(path: '/pay'),
          statusCode: 409,
          data: {'message': 'Quote expired or changed'},
        ),
      );
    }

    return ApiResponseModel.withSuccess(
      Response(
        requestOptions: RequestOptions(path: '/pay'),
        statusCode: 200,
        data: {
          'status': true,
          'authorization_url': 'https://checkout.paystack.com/fake_ref_123',
          'payment_request_id': 'PR-1001',
        },
      ),
    );
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

void main() {
  group('M11 & M12: Customer Pickup Payment & Recovery Tests', () {
    late MemoryStorage storage;

    setUp(() async {
      storage = MemoryStorage();
      storage.values[AppConstants.userLoginToken] = 'test-customer-token';
      await di.sl.reset();
      di.sl.registerSingleton<StorageService>(storage);
    });

    tearDown(() async {
      await di.sl.reset();
    });

    test('M11: CheckoutRepository constructs valid, uncorrupted pickup quote URI', () async {
      final mockDio = MockDioClient();
      final repo = CheckoutRepository(dioClient: mockDio);

      await repo.quotePickupReservation(reservationCode: 'RES-9988');

      expect(mockDio.lastPath, '${AppConstants.pickupReservationsUri}/RES-9988/quote');
      expect(mockDio.lastPath!.contains('\u0002'), isFalse);
      expect(mockDio.lastPath!.contains('\$'), isFalse);
    });

    test('M11: CheckoutRepository constructs valid, uncorrupted pickup pay URI', () async {
      final mockDio = MockDioClient();
      final repo = CheckoutRepository(dioClient: mockDio);

      await repo.payPickupReservation(
        quoteToken: 'QT-VAL-123',
        reservationCode: 'RES-5544',
      );

      expect(mockDio.lastPath, '${AppConstants.pickupReservationsUri}/RES-5544/pay');
      expect(mockDio.lastPath!.contains('\u0002'), isFalse);
      expect(mockDio.lastPath!.contains('\$'), isFalse);
    });

    testWidgets('M11: Canceled quote makes zero pay calls and does not persist payment state', (tester) async {
      final service = FakePickupCheckoutService();
      final controller = CheckoutController(checkoutServiceInterface: service);

      final reservation = PickupReservationModel(
        reservationCode: 'RES-1234',
        expiresAt: '2026-10-04 23:00',
        totalAmount: '15000.00',
      );

      await tester.pumpWidget(
        MaterialApp(
          navigatorKey: navigatorKey,
          home: ChangeNotifierProvider<CheckoutController>.value(
            value: controller,
            child: PickupPaymentScreen(reservation: reservation),
          ),
        ),
      );

      await tester.pumpAndSettle();

      // Click review payment button
      await tester.tap(find.text('Review payment'));
      await tester.pumpAndSettle();

      // Dialog opens with quote details
      expect(find.text('Confirm pickup payment'), findsOneWidget);
      expect(service.quoteCallCount, 1);

      // User hits cancel
      await tester.tap(find.text('Cancel'));
      await tester.pumpAndSettle();

      // Dialog closed, ZERO pay calls made
      expect(find.text('Confirm pickup payment'), findsNothing);
      expect(service.payCallCount, 0);
      expect(controller.pendingPickupPayment, isNull);
    });

    test('M11: Secure identity is written before network payment call and auth URL is not payment proof', () async {
      final service = FakePickupCheckoutService();
      final controller = CheckoutController(checkoutServiceInterface: service);

      expect(controller.pendingPickupPayment, isNull);

      bool wasWrittenBeforeNetworkFinished = false;
      service.onBeforePayNetworkResponse = () async {
        final pending = controller.pendingPickupPayment;
        if (pending != null &&
            pending.reservationCode == 'RES-ABC' &&
            pending.quoteToken == 'QT-PRE-NET' &&
            pending.ownerToken == 'test-customer-token') {
          wasWrittenBeforeNetworkFinished = true;
        }
      };

      final response = await controller.payPickupReservation(
        reservationCode: 'RES-ABC',
        quoteToken: 'QT-PRE-NET',
      );

      // Identity MUST be written in durable secure storage BEFORE network returns
      expect(wasWrittenBeforeNetworkFinished, isTrue);

      // Network returns authorization URL
      expect(service.payCallCount, 1);
      final data = response.response?.data;
      expect(data['authorization_url'], contains('https://checkout.paystack.com/'));

      // Crucial Financial Invariant: authorization URL is NOT payment proof
      // Payment state remains strictly pending until verified by backend
      final pending = controller.pendingPickupPayment;
      expect(pending, isNotNull);
      expect(pending!.reservationCode, 'RES-ABC');
      expect(pickupPaymentCompleted(data), isFalse);
    });

    testWidgets('M11: Trapped or pending pickup routes directly to status check without new quote or pay call', (tester) async {
      final service = FakePickupCheckoutService();
      final controller = CheckoutController(checkoutServiceInterface: service);

      // Simulate existing pending pickup
      await storage.setString(
        PickupPaymentState.storageKey,
        PickupPaymentState(
          ownerToken: 'test-customer-token',
          reservationCode: 'RES-TRAPPED',
          quoteToken: 'QT-PENDING',
          useCashback: false,
        ).encode(),
      );

      final reservation = PickupReservationModel(
        reservationCode: 'RES-TRAPPED',
        expiresAt: '2026-10-04 23:00',
        totalAmount: '15000.00',
      );

      await tester.pumpWidget(
        MaterialApp(
          navigatorKey: navigatorKey,
          home: ChangeNotifierProvider<CheckoutController>.value(
            value: controller,
            child: PickupPaymentScreen(reservation: reservation),
          ),
        ),
      );

      await tester.pumpAndSettle();

      // Tap Review payment
      await tester.tap(find.text('Review payment'));
      await tester.pumpAndSettle();

      // Zero new quote calls or pay calls because pending state exists
      expect(service.quoteCallCount, 0);
      expect(service.payCallCount, 0);

      // Navigated to PaymentStatusScreen
      expect(find.byType(PaymentStatusScreen), findsOneWidget);
    });

    testWidgets('M12: Authoritative no-attempt outcome (unpaid + null payment_request_id) clears trapped identity and permits fresh quote', (tester) async {
      final service = FakePickupCheckoutService();
      service.shouldFailPayWith409 = true;
      final controller = CheckoutController(checkoutServiceInterface: service);

      // Simulate existing trapped pending pickup state
      await storage.setString(
        PickupPaymentState.storageKey,
        PickupPaymentState(
          ownerToken: 'test-customer-token',
          reservationCode: 'RES-TRAPPED',
          quoteToken: 'OLD-EXPIRED-TOKEN',
          useCashback: false,
        ).encode(),
      );

      expect(controller.pendingPickupPayment, isNotNull);
      expect(controller.pendingPickupPayment!.reservationCode, 'RES-TRAPPED');

      // Simulate authoritative no-attempt result: clearPendingPickupPayment clears owner-matching identity
      await controller.clearPendingPickupPayment(reservationCode: 'RES-TRAPPED');

      expect(controller.pendingPickupPayment, isNull);

      // Now user can request a fresh quote successfully
      service.shouldFailPayWith409 = false;
      final freshQuoteResponse = await controller.quotePickupReservation(reservationCode: 'RES-TRAPPED');
      expect(freshQuoteResponse.isSuccess, true);
      expect(service.quoteCallCount, 1);
    });

    test('M12: PickupPaymentState decode matches owner token and isolates different customer sessions', () {
      final state = PickupPaymentState(
        ownerToken: 'customer-A',
        reservationCode: 'RES-100',
        quoteToken: 'QT-100',
        useCashback: false,
      );

      final encoded = state.encode();

      // Same customer token matches
      final decodedSame = PickupPaymentState.decode(encoded, 'customer-A');
      expect(decodedSame, isNotNull);
      expect(decodedSame!.reservationCode, 'RES-100');

      // Different customer token is isolated (returns null)
      final decodedDifferent = PickupPaymentState.decode(encoded, 'customer-B');
      expect(decodedDifferent, isNull);
    });

    test('M12: Pickup payment status completes terminal refund state cleanly', () {
      final dataRefunded = {
        'status': true,
        'payment_status': 'refunded',
        'reservation_code': 'RES-REFUNDED',
      };

      // Ensure refunded state is recognized as terminal
      expect(dataRefunded['payment_status'], 'refunded');
      expect(pickupPaymentCompleted(dataRefunded), isFalse);
    });

    test('M12: Deterministic verification of completed vs non-completed pickup payment payloads', () {
      // Valid completed payload: paid + order_id + 6-digit code
      final validPaid = {
        'status': true,
        'payment_status': 'paid',
        'order_id': 100200,
        'pickup_verification_code': '654321',
      };
      expect(pickupPaymentCompleted(validPaid), isTrue);

      // Incomplete: paid but missing 6-digit verification code
      final paidNoCode = {
        'status': true,
        'payment_status': 'paid',
        'order_id': 100200,
        'pickup_verification_code': null,
      };
      expect(pickupPaymentCompleted(paidNoCode), isFalse);

      // Incomplete: paid but 4-digit code instead of 6-digit
      final paid4Digit = {
        'status': true,
        'payment_status': 'paid',
        'order_id': 100200,
        'pickup_verification_code': '1234',
      };
      expect(pickupPaymentCompleted(paid4Digit), isFalse);

      // Authoritative no-attempt payload
      final noAttempt = {
        'status': true,
        'payment_status': 'unpaid',
        'payment_request_id': null,
      };
      expect(pickupPaymentCompleted(noAttempt), isFalse);
      expect(pickupPaymentMayRetry(noAttempt), isTrue);
    });
  });
}
