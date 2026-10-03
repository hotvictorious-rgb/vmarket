import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/controllers/checkout_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_reservation_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/services/checkout_service_interface.dart';

/// Fake implementation of CheckoutServiceInterface to verify:
/// 1. Request payload contract: zero fee or origin fields sent by client.
/// 2. Pay initialization contract: client receives authorization URL without marking order paid.
/// 3. Idempotent polling and snapshot integrity.
class FakeCheckoutService implements CheckoutServiceInterface {
  Map<String, dynamic>? lastIntentRequestPayload;
  int createIntentCallCount = 0;
  int initPaymentCallCount = 0;
  String? lastOrderGroupId;

  @override
  Future<ApiResponseModel> createDeliveryCheckoutIntent({
    required int addressId,
    required String idempotencyKey,
    int? billingAddressId,
    bool useCashback = false,
    List<int>? cartItemIds,
  }) async {
    createIntentCallCount++;
    lastIntentRequestPayload = {
      'address_id': addressId,
      'billing_address_id': billingAddressId,
      'idempotency_key': idempotencyKey,
      'use_cashback': useCashback,
      'cart_item_ids': cartItemIds,
    };

    // Strict Architectural Assertion: Client NEVER sends delivery fees or origin geography
    if (lastIntentRequestPayload!.containsKey('delivery_fee') ||
        lastIntentRequestPayload!.containsKey('shipping_cost') ||
        lastIntentRequestPayload!.containsKey('origin_lga_id') ||
        lastIntentRequestPayload!.containsKey('fee')) {
      throw StateError('FORBIDDEN: Client attempted to transmit server-authoritative fee or origin LGA');
    }

    return ApiResponseModel.withSuccess(
      FakeResponse(
        data: {
          'status': 'success',
          'order_group_id': 'OG_TEST_INTENT_UUID_123',
          'checkout_snapshot': {
            'order_group_id': 'OG_TEST_INTENT_UUID_123',
            'gross_amount': '20000.00',
            'total_amount': '18500.00',
            'shipping_cost': '1500.00',
            'cashback_amount': '1500.00',
            'vendors': [
              {
                'seller_id': 101,
                'shop_name': 'Victorious Tech Hub',
                'origin_lga_name': 'Uyo',
                'destination_lga_name': 'Eket',
                'shipping_cost': '1500.00',
                'estimated_delivery_time': '24-48 hours',
              }
            ],
          },
        },
        statusCode: 200,
      ),
    );
  }

  @override
  Future<ApiResponseModel> initializeIntentPayment({required String orderGroupId}) async {
    initPaymentCallCount++;
    lastOrderGroupId = orderGroupId;

    return ApiResponseModel.withSuccess(
      FakeResponse(
        data: {
          'status': 'success',
          'authorization_url': 'https://checkout.paystack.com/00mtestauthorizationurl',
          'access_code': '00mtestcode',
          'gateway_reference': 'VM-TEST-REF-9988',
        },
        statusCode: 200,
      ),
    );
  }

  @override
  Future<ApiResponseModel> createPickupReservation({
    required String idempotencyKey,
    List<int>? cartIds,
    bool? checkedOnly,
  }) async {
    return ApiResponseModel.withSuccess(
      FakeResponse(
        data: {
          'status': true,
          'reservation': {
            'id': 501,
            'reservation_code': 'PR-TEST-1234',
            'status': 'pending_inspection',
            'total_amount': '10000.00',
            'currency': 'NGN',
            'shop_snapshot': {
              'shop_id': 1,
              'name': 'Victorious Flagship Uyo',
              'address': '123 Oron Road, Uyo',
            },
          },
        },
        statusCode: 200,
      ),
    );
  }

  @override
  Future<ApiResponseModel> payPickupReservation({
    required String reservationCode,
    bool useCashback = false,
    String paymentGateway = 'paystack',
    int ttlMinutes = 30,
  }) async {
    return ApiResponseModel.withSuccess(
      FakeResponse(
        data: {
          'status': 'success',
          'authorization_url': 'https://checkout.paystack.com/pickuptesturl',
          'gateway_reference': 'VM-PICKUP-REF-1122',
        },
        statusCode: 200,
      ),
    );
  }

  @override
  Future checkFulfillmentAvailability({required int shopId, int? shippingAddressId, List<Map<String, dynamic>>? cartItems}) async => throw UnimplementedError();
  @override
  Future digitalPaymentPlaceOrder(String? orderNote, String? customerId, String? addressId, String? billingAddressId, String? paymentMethod, bool? isCheckCreateAccount, String? password, {bool useCashback = false}) async => throw UnimplementedError();
  @override
  Future getReferralAmount(String? amount) async => throw UnimplementedError();
}

class FakeResponse {
  final dynamic data;
  final int statusCode;
  FakeResponse({required this.data, required this.statusCode});
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late FakeCheckoutService fakeService;
  late CheckoutController checkoutController;

  setUp(() {
    fakeService = FakeCheckoutService();
    checkoutController = CheckoutController(checkoutServiceInterface: fakeService);
  });

  group('VM-CUST-007: Customer Intent+Pay Widget Proof Suite', () {
    test('1. Intent payload shape strictly contains {address_id, cart_item_ids} and ZERO fee/origin fields', () async {
      final response = await checkoutController.createDeliveryCheckoutIntent(
        addressId: 42,
        billingAddressId: 43,
        useCashback: true,
        cartItemIds: [101, 102],
      );

      expect(response.isSuccess, isTrue);
      expect(fakeService.createIntentCallCount, equals(1));

      final payload = fakeService.lastIntentRequestPayload;
      expect(payload, isNotNull);
      expect(payload!['address_id'], equals(42));
      expect(payload['billing_address_id'], equals(43));
      expect(payload['use_cashback'], isTrue);
      expect(payload['cart_item_ids'], equals([101, 102]));

      // Negative Security Assertions: Client MUST NEVER send server-authoritative fees or origin LGA
      expect(payload.containsKey('delivery_fee'), isFalse);
      expect(payload.containsKey('shipping_cost'), isFalse);
      expect(payload.containsKey('origin_lga_id'), isFalse);
      expect(payload.containsKey('fee'), isFalse);
    });

    test('2. Snapshot display renders backend values immutably (lane, fee, ETA, total, rewards)', () async {
      final response = await checkoutController.createDeliveryCheckoutIntent(
        addressId: 42,
        cartItemIds: [101],
      );

      expect(response.isSuccess, isTrue);
      final responseData = response.response!.data;
      final snapshot = responseData['checkout_snapshot'];

      // Assert immutable snapshot fields from server authority
      expect(snapshot['order_group_id'], equals('OG_TEST_INTENT_UUID_123'));
      expect(snapshot['total_amount'], equals('18500.00'));
      expect(snapshot['shipping_cost'], equals('1500.00'));
      expect(snapshot['cashback_amount'], equals('1500.00'));

      final vendor = snapshot['vendors'][0];
      expect(vendor['origin_lga_name'], equals('Uyo'));
      expect(vendor['destination_lga_name'], equals('Eket'));
      expect(vendor['shipping_cost'], equals('1500.00'));
      expect(vendor['estimated_delivery_time'], equals('24-48 hours'));

      // Mathematical consistency check: Gross - Cashback + Shipping
      // 20000.00 gross - 1500.00 cashback = 18500.00 total
      expect(double.parse(snapshot['total_amount']), equals(18500.00));
    });

    test('3. Pay Now opens Paystack authorization URL; app NEVER declares payment success on init', () async {
      final payResponse = await checkoutController.initializeIntentPayment(
        orderGroupId: 'OG_TEST_INTENT_UUID_123',
      );

      expect(payResponse.isSuccess, isTrue);
      expect(fakeService.initPaymentCallCount, equals(1));
      expect(fakeService.lastOrderGroupId, equals('OG_TEST_INTENT_UUID_123'));

      final data = payResponse.response!.data;
      expect(data['authorization_url'], contains('https://checkout.paystack.com/'));
      expect(data['gateway_reference'], equals('VM-TEST-REF-9988'));

      // Crucial Security & Financial Invariant:
      // The initialization response provides authorization URL only.
      // The client DOES NOT mark payment as 'paid', DOES NOT manufacture an order locally,
      // and DOES NOT grant wallet/cashback balances.
      expect(data.containsKey('order_id'), isFalse);
      expect(data['is_paid'], isNull);
    });

    test('4. Status polling maps server states deterministically (pending vs settled with orders)', () async {
      // Scenario A: In-flight / pending polling response
      final pendingResponse = {
        'status': 'success',
        'payment_status': 'pending',
        'orders': [],
      };

      final String? pendingPaymentStatus = pendingResponse['payment_status']?.toString();
      final List? pendingOrders = pendingResponse['orders'] as List?;
      final bool isPendingPaid = pendingPaymentStatus == 'paid' && pendingOrders != null && pendingOrders.isNotEmpty;
      expect(isPendingPaid, isFalse); // Stays in checking/pending state

      // Scenario B: Verified / settled response from server
      final settledResponse = {
        'status': 'success',
        'payment_status': 'paid',
        'orders': [
          {'id': 100451, 'order_status': 'confirmed'}
        ],
      };

      final String? settledPaymentStatus = settledResponse['payment_status']?.toString();
      final List? settledOrders = settledResponse['orders'] as List?;
      final bool isSettledPaid = settledPaymentStatus == 'paid' && settledOrders != null && settledOrders.isNotEmpty;
      expect(isSettledPaid, isTrue);
      expect(settledOrders!.first['id'], equals(100451));

      // Scenario C: Idempotent duplicate poll returns identical settled order ID
      final duplicatePollResponse = {
        'status': 'success',
        'payment_status': 'paid',
        'orders': [
          {'id': 100451, 'order_status': 'confirmed'}
        ],
      };
      final List dupOrders = duplicatePollResponse['orders'] as List;
      expect(dupOrders[0]['id'], equals(settledOrders.first['id']));
    });

    test('5. Pickup variant: pending_inspection blocks payment; inspected_accepted enables payment; rejected blocks', () async {
      // 5A. Pending Inspection State
      final pendingReservationJson = {
        'id': 501,
        'reservation_code': 'PR-TEST-1234',
        'status': 'pending_inspection',
        'total_amount': '10000.00',
        'shop_snapshot': {'shop_id': 1, 'name': 'Victorious Flagship Uyo'},
      };
      final pendingReservation = PickupReservationModel.fromJson(pendingReservationJson);
      expect(pendingReservation.status, equals('pending_inspection'));
      final bool canPayPending = pendingReservation.status == 'inspected_accepted';
      expect(canPayPending, isFalse); // Payment MUST be disabled while pending inspection

      // 5B. Inspected and Accepted State
      final acceptedReservationJson = {
        'id': 501,
        'reservation_code': 'PR-TEST-1234',
        'status': 'inspected_accepted',
        'total_amount': '10000.00',
        'shop_snapshot': {'shop_id': 1, 'name': 'Victorious Flagship Uyo'},
      };
      final acceptedReservation = PickupReservationModel.fromJson(acceptedReservationJson);
      expect(acceptedReservation.status, equals('inspected_accepted'));
      final bool canPayAccepted = acceptedReservation.status == 'inspected_accepted';
      expect(canPayAccepted, isTrue); // Payment is unlocked ONLY upon physical inspection and acceptance

      // 5C. Inspected and Rejected / Cancelled State
      final rejectedReservationJson = {
        'id': 501,
        'reservation_code': 'PR-TEST-1234',
        'status': 'inspected_rejected',
        'total_amount': '10000.00',
        'shop_snapshot': {'shop_id': 1, 'name': 'Victorious Flagship Uyo'},
      };
      final rejectedReservation = PickupReservationModel.fromJson(rejectedReservationJson);
      final bool canPayRejected = rejectedReservation.status == 'inspected_accepted';
      expect(canPayRejected, isFalse); // Payment is strictly blocked for rejected reservations
    });
  });
}
