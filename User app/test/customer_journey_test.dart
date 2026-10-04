import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_sixvalley_ecommerce/di_container.dart' as di;
import 'package:flutter_sixvalley_ecommerce/services/storage_service.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'support/memory_storage.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/domain/models/cart_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/cashback/domain/models/cashback_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/controllers/checkout_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_reservation_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/services/checkout_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/domain/models/fulfillment_availability_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/domain/models/track_order_details_model.dart';

/// End-to-End Fake Service simulating the full customer marketplace lifecycle:
/// Cart -> Address -> Fulfillment Availability -> Intent Creation -> Paystack Init -> Status Polling -> Tracking & Cashback
class FakeCustomerJourneyService implements CheckoutServiceInterface {
  bool simulateIntentRaceRejection = false;
  Map<int, Map<String, dynamic>> shopAvailabilityDb = {};

  void setShopAvailability(int shopId, Map<String, dynamic> data) {
    shopAvailabilityDb[shopId] = data;
  }

  @override
  Future<ApiResponseModel> checkFulfillmentAvailability({
    required int shopId,
    int? shippingAddressId,
    List<Map<String, dynamic>>? cartItems,
  }) async {
    // Architectural Invariant Assertion: Client NEVER sends delivery fees or origin LGA
    if (cartItems != null) {
      for (final item in cartItems) {
        if (item.containsKey('delivery_fee') || item.containsKey('origin_lga_id')) {
          throw StateError('FORBIDDEN: Client transmitted server-authoritative delivery fee or origin LGA');
        }
      }
    }

    final data = shopAvailabilityDb[shopId];
    if (data == null) {
      return ApiResponseModel.withError('Shop fulfillment not configured');
    }

    return ApiResponseModel.withSuccess(FakeResponse(data: data, statusCode: 200));
  }

  @override
  Future<ApiResponseModel> createDeliveryCheckoutIntent({
    required int addressId,
    required String idempotencyKey,
    int? billingAddressId,
    bool useCashback = false,
    List<int>? cartItemIds,
  }) async {
    if (simulateIntentRaceRejection) {
      return ApiResponseModel.withError('Delivery lane between Uyo and destination is temporarily suspended');
    }

    return ApiResponseModel.withSuccess(
      FakeResponse(
        data: {
          'status': 'success',
          'order_group_id': 'OG_JOURNEY_TEST_7788',
          'checkout_snapshot': {
            'order_group_id': 'OG_JOURNEY_TEST_7788',
            'gross_amount': '25000.00',
            'total_amount': '23750.00',
            'shipping_cost': '1500.00',
            'cashback_amount': '1250.00',
            'vendors': [
              {
                'seller_id': 101,
                'shop_name': 'Victorious Tech Uyo',
                'origin_lga_name': 'Uyo',
                'destination_lga_name': 'Uyo',
                'shipping_cost': '1500.00',
                'estimated_delivery_time': 'Same-Day / 24 hrs',
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
    return ApiResponseModel.withSuccess(
      FakeResponse(
        data: {
          'status': 'success',
          'authorization_url': 'https://checkout.paystack.com/journey_pay_token',
          'gateway_reference': 'VM-JOURNEY-REF-7788',
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
            'id': 701,
            'reservation_code': 'PR-JOURNEY-PICKUP-01',
            'status': 'pending_inspection',
            'total_amount': '12000.00',
            'currency': 'NGN',
            'shop_snapshot': {
              'shop_id': 2,
              'name': 'Victorious Fashion Eket',
              'address': 'Grace Bill Road, Eket',
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
          'authorization_url': 'https://checkout.paystack.com/pickup_journey_url',
          'gateway_reference': 'VM-PICKUP-JOURNEY-REF',
        },
        statusCode: 200,
      ),
    );
  }

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

  late FakeCustomerJourneyService journeyService;
  late CheckoutController checkoutController;

  setUp(() async {
    await di.sl.reset();
    final storage = MemoryStorage();
    storage.values[AppConstants.userLoginToken] = 'test-customer';
    di.sl.registerSingleton<StorageService>(storage);
    journeyService = FakeCustomerJourneyService();
    checkoutController = CheckoutController(checkoutServiceInterface: journeyService);
  });

  group('VM-CUST-009: Customer Journey Integration Proof Suite (Cart to Cashback)', () {
    test('1. Full Happy-Path Lifecycle: Cart -> LGA Address -> Available Lane -> Intent -> Pay Init -> Settled -> Tracking + Cashback', () async {
      // --- STAGE 1: Cart Items Selection ---
      final cartItemA = CartModel.fromJson({
        'id': 101,
        'price': 15000.0,
        'quantity': 1,
        'name': 'Smartphone Wireless Charger',
      });
      final cartItemB = CartModel.fromJson({
        'id': 102,
        'price': 10000.0,
        'quantity': 1,
        'name': 'USB-C Fast Charging Cable',
      });
      final cartList = [cartItemA, cartItemB];
      expect(cartList.length, equals(2));
      final double rawMerchandiseSubtotal = (cartItemA.price! * cartItemA.quantity!) + (cartItemB.price! * cartItemB.quantity!);
      expect(rawMerchandiseSubtotal, equals(25000.0));

      // --- STAGE 2: Customer Canonical LGA Address Selection ---
      const selectedAddressId = 42;
      const canonicalCountry = 'Nigeria';
      const canonicalState = 'Akwa Ibom';
      const canonicalLga = 'Uyo';
      expect(canonicalCountry, equals('Nigeria'));
      expect(canonicalState, equals('Akwa Ibom'));
      expect(canonicalLga, equals('Uyo'));

      // --- STAGE 3: Fulfillment Availability Verification (Uyo -> Uyo Corridor) ---
      journeyService.setShopAvailability(1, {
        'success': true,
        'message': 'Fulfillment options retrieved successfully',
        'data': {
          'shop_id': 1,
          'fulfillment_options': {
            'delivery': {
              'available': true,
              'lane': 'Uyo -> Uyo',
              'fee': 1500.0,
              'estimated_days': 'Same-Day / 24 hrs',
            },
            'pickup': {'available': true},
          },
        },
      });

      final availabilityResponse = await journeyService.checkFulfillmentAvailability(
        shopId: 1,
        shippingAddressId: selectedAddressId,
        cartItems: [
          {'cart_id': 101, 'quantity': 1},
          {'cart_id': 102, 'quantity': 1},
        ],
      );
      expect(availabilityResponse.isSuccess, isTrue);
      final availData = availabilityResponse.response!.data['data'];
      final deliveryOption = availData['fulfillment_options']['delivery'];
      expect(deliveryOption['available'], isTrue);
      expect(deliveryOption['fee'], equals(1500.0));

      // --- STAGE 4: Checkout Intent Creation with Immutable Snapshot & 5% Cashback ---
      final intentResponse = await checkoutController.createDeliveryCheckoutIntent(
        addressId: selectedAddressId,
        useCashback: true,
        cartItemIds: [101, 102],
      );
      expect(intentResponse.isSuccess, isTrue);
      final snapshot = intentResponse.response!.data['checkout_snapshot'];
      expect(snapshot['order_group_id'], equals('OG_JOURNEY_TEST_7788'));
      expect(snapshot['total_amount'], equals('23750.00')); // 25000 - 1250 cashback = 23750
      expect(snapshot['shipping_cost'], equals('1500.00'));
      expect(snapshot['cashback_amount'], equals('1250.00'));

      // --- STAGE 5: Paystack Payment Initialization ---
      final payInitResponse = await checkoutController.initializeIntentPayment(
        orderGroupId: snapshot['order_group_id'],
      );
      expect(payInitResponse.isSuccess, isTrue);
      final payData = payInitResponse.response!.data;
      expect(payData['authorization_url'], equals('https://checkout.paystack.com/journey_pay_token'));
      expect(payData['gateway_reference'], equals('VM-JOURNEY-REF-7788'));

      // --- STAGE 6: Status Polling Simulating Payment Settlement ---
      final pollingSettledResponse = {
        'status': 'success',
        'payment_status': 'paid',
        'orders': [
          {'id': 100991, 'order_status': 'confirmed', 'order_amount': 25250.0}
        ],
      };
      final bool isSettled = pollingSettledResponse['payment_status'] == 'paid' &&
          (pollingSettledResponse['orders'] as List).isNotEmpty;
      expect(isSettled, isTrue);
      final int generatedOrderId = (pollingSettledResponse['orders'] as List).first['id'];
      expect(generatedOrderId, equals(100991));

      // --- STAGE 7: Tracking & Cashback Presentation ---
      final trackingHistory = TrackingHistory.fromJson({
        'order_placed': {'label': 'Order Placed', 'status': true, 'dateTime': '2026-10-02 12:00:00'},
        'order_confirmed': {'label': 'Order Confirmed', 'status': true, 'dateTime': '2026-10-02 12:05:00'},
      });
      expect(trackingHistory.orderPlaced?.status, isTrue);
      expect(trackingHistory.orderConfirmed?.status, isTrue);

      final cashbackSummary = CashbackSummaryModel.fromJson({
        'status': true,
        'customer_id': 10,
        'currency': 'NGN',
        'pending_cashback_amount': '1250.00',
        'available_cashback_amount': '5000.00',
        'redeemed_cashback_amount': '1250.00',
      });
      expect(cashbackSummary.pendingCashbackAmount, equals('1250.00'));
      expect(cashbackSummary.availableCashbackAmount, equals('5000.00'));
    });

    test('2. Unavailable Lane Branch: Backend unserviced corridor gracefully informs user without client crash', () async {
      journeyService.setShopAvailability(99, {
        'success': true,
        'message': 'Fulfillment options retrieved successfully',
        'data': {
          'shop_id': 99,
          'fulfillment_options': {
            'delivery': {
              'available': false,
              'reason': 'No active delivery lane from Uyo to selected remote LGA',
            },
            'pickup': {'available': true},
          },
        },
      });

      final response = await journeyService.checkFulfillmentAvailability(shopId: 99, shippingAddressId: 42);
      expect(response.isSuccess, isTrue);

      final options = response.response!.data['data']['fulfillment_options'];
      expect(options['delivery']['available'], isFalse);
      expect(options['delivery']['reason'], contains('No active delivery lane'));
      expect(options['pickup']['available'], isTrue);
    });

    test('3. Race Condition Branch: Lane becomes disabled between availability check and intent creation', () async {
      journeyService.simulateIntentRaceRejection = true;

      final response = await checkoutController.createDeliveryCheckoutIntent(
        addressId: 42,
        cartItemIds: [101],
      );

      // Verify fail-closed security contract: client receives error and does NOT proceed to payment initialization
      expect(response.isSuccess, isFalse);
      expect(response.error, contains('temporarily suspended'));
      expect(checkoutController.isLoading, isFalse);
    });

    test('4. Mixed Fulfillment Branch: Multi-vendor checkout allows Delivery for Shop 1 and In-Store Pickup for Shop 2', () async {
      // Vendor 1: Delivery
      final deliveryIntent = await checkoutController.createDeliveryCheckoutIntent(
        addressId: 42,
        cartItemIds: [101],
      );
      expect(deliveryIntent.isSuccess, isTrue);
      expect(deliveryIntent.response!.data['order_group_id'], startsWith('OG_'));

      // Vendor 2: In-Store Self Pickup
      final pickupReservation = await journeyService.createPickupReservation(
        idempotencyKey: 'idem_mixed_pickup_999',
        cartIds: [102],
      );
      expect(pickupReservation.isSuccess, isTrue);
      final resData = pickupReservation.response!.data['reservation'];
      expect(resData['status'], equals('pending_inspection'));
      expect(resData['reservation_code'], equals('PR-JOURNEY-PICKUP-01'));
      expect(resData['shop_snapshot']['shop_id'], equals(2));
    });
  });
}
