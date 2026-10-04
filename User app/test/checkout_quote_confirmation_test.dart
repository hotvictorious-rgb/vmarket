import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/di_container.dart' as di;
import 'package:flutter_sixvalley_ecommerce/features/checkout/controllers/checkout_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/services/checkout_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter_sixvalley_ecommerce/services/storage_service.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'support/memory_storage.dart';

class QuoteService implements CheckoutServiceInterface {
  final keys = <String>[];
  int paymentCalls = 0;
  @override Future<dynamic> createDeliveryCheckoutIntent({required int addressId,
    required String idempotencyKey, int? billingAddressId, bool useCashback = false, List<int>? cartItemIds}) async {
    keys.add(idempotencyKey);
    return ApiResponseModel.withSuccess(Response(requestOptions: RequestOptions(path: '/intent'), statusCode: 200,
      data: {'order_group_id': 'OG-one', 'quote': {'currency': 'NGN', 'merchandise_subtotal': '100.00',
      'tax_total': '7.50', 'shipping_total': '20.00', 'cashback_amount': '10.00', 'total_amount': '117.50'}}));
  }
  @override Future<dynamic> initializeIntentPayment({required String orderGroupId}) async {
    paymentCalls++;
    throw StateError('Payment must not initialize without confirmation');
  }
  @override dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

void main() {
  testWidgets('frozen quote precedes initialization and restart reuses intent identity', (tester) async {
    final storage = MemoryStorage();
    storage.values[AppConstants.userLoginToken] = 'customer-token';
    await di.sl.reset();
    di.sl.registerSingleton<StorageService>(storage);
    final service = QuoteService();
    final controller = CheckoutController(checkoutServiceInterface: service);
    await tester.pumpWidget(MaterialApp(navigatorKey: navigatorKey, home: const Scaffold(body: SizedBox())));
    final checkout = controller.placeDeliveryOrder(addressId: 1, useCashback: true);
    await tester.pumpAndSettle();
    expect(find.text('Pay now: NGN 117.50'), findsOneWidget);
    expect(service.paymentCalls, 0);
    expect(controller.pendingDeliveryPayment?.orderGroupId, 'OG-one');
    await tester.tap(find.text('Back'));
    await tester.pumpAndSettle();
    await checkout;
    // A new controller models process recreation with persisted secure state.
    final restarted = CheckoutController(checkoutServiceInterface: service);
    await restarted.createDeliveryCheckoutIntent(addressId: 1, useCashback: true);
    expect(service.keys.length, 2);
    expect(service.keys.first, service.keys.last);
    expect(restarted.pendingDeliveryPayment?.orderGroupId, 'OG-one');
    expect(service.paymentCalls, 0);
    // Cancelling a quote allows changed choices because no gateway request started.
    await restarted.createDeliveryCheckoutIntent(addressId: 2, useCashback: false);
    expect(service.keys.last, isNot(service.keys.first));
    // Persist the ambiguity marker before a network request, including transport failure.
    await expectLater(restarted.initializeIntentPayment(orderGroupId: 'OG-one'), throwsStateError);
    expect(restarted.pendingDeliveryPayment?.initializationStarted, true);
    final callsBeforeReplacement = service.keys.length;
    final blocked = await restarted.createDeliveryCheckoutIntent(addressId: 3);
    expect(blocked.isSuccess, false);
    expect(service.keys.length, callsBeforeReplacement);
    expect(restarted.pendingDeliveryPayment?.initializationStarted, true);
    await tester.pumpWidget(const SizedBox());
    await tester.pump();
    await di.sl.reset();
  });
}
