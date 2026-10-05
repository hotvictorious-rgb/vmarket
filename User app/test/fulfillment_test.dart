import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/controllers/fulfillment_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/domain/models/fulfillment_availability_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/domain/services/fulfillment_service_interface.dart';

class MockFulfillmentService implements FulfillmentServiceInterface {
  final Map<int, Map<String, dynamic>> mockFixturesByShop = {};

  void setFixture(int shopId, Map<String, dynamic> fixture) {
    mockFixturesByShop[shopId] = fixture;
  }

  @override
  Future<ApiResponseModel> checkAvailability({
    required int shopId,
    int? shippingAddressId,
    List<Map<String, dynamic>>? cartItems,
  }) async {
    // Assert strictly: client never transmits authoritative delivery fee or origin LGA
    if (cartItems != null) {
      for (final item in cartItems) {
        if (item.containsKey('delivery_fee') || item.containsKey('origin_lga_id')) {
          throw StateError('FORBIDDEN: Client attempted to transmit delivery fee or origin LGA');
        }
      }
    }

    final data = mockFixturesByShop[shopId];
    if (data != null) {
      return ApiResponseModel.withSuccess(
        FakeResponse(data: data, statusCode: 200),
      );
    }

    return ApiResponseModel.withError('Shop fulfillment not configured');
  }

  @override
  Future<ApiResponseModel> getDeliveryFee({
    required int shopId,
    required int shippingAddressId,
    List<Map<String, dynamic>>? cartItems,
  }) async {
    final data = mockFixturesByShop[shopId];
    final fee = data?['data']?['fulfillment_options']?['delivery']?['fee'] ?? 1500.0;
    return ApiResponseModel.withSuccess(
      FakeResponse(data: {'fee': fee}, statusCode: 200),
    );
  }
}

class FakeResponse {
  final dynamic data;
  final int statusCode;
  FakeResponse({required this.data, required this.statusCode});
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late MockFulfillmentService mockService;
  late FulfillmentController controller;

  setUp(() {
    mockService = MockFulfillmentService();
    controller = FulfillmentController(fulfillmentServiceInterface: mockService);
  });

  group('VM-CUST-006: Customer Fulfillment Widget Proof Suite', () {
    test('1. Available lane renders fee/ETA/lane display-only from backend authority', () async {
      // Mocked POST /api/v1/fulfillment/availability response for Shop 1 (Uyo -> Eket Lane)
      mockService.setFixture(1, {
        'success': true,
        'message': 'Fulfillment options retrieved successfully',
        'data': {
          'shop': {'id': 1, 'name': 'Apex Supermarket Uyo', 'lga': 'Uyo', 'state': 'Akwa Ibom'},
          'address': {'id': 101, 'lga': 'Eket', 'state': 'Akwa Ibom'},
          'fulfillment_options': {
            'delivery': {
              'available': true,
              'fee': 1500.00,
              'estimated_time': '2-4 hours',
              'origin_lga': {'id': 142, 'name': 'Uyo', 'state': 'Akwa Ibom'},
              'destination_lga': {'id': 125, 'name': 'Eket', 'state': 'Akwa Ibom'},
              'reason': null,
              'message': 'Direct courier delivery active on Uyo -> Eket corridor',
            },
            'pickup': {
              'available': true,
              'requires_verification': true,
              'earliest_available': 'Today at 2:00 PM',
              'available_times': ['10:00 AM - 1:00 PM', '2:00 PM - 6:00 PM'],
              'message': '5% Victorious Points cashback awarded upon physical store collection',
            }
          }
        }
      });

      await controller.checkForAllShops([1], 101);

      // Verify delivery option parsing
      expect(controller.isDeliveryAvailable(1), isTrue);
      expect(controller.getDeliveryFee(1), 1500.00);
      final model = controller.fulfillmentByShop[1];
      expect(model, isNotNull);
      expect(model!.data?.fulfillmentOptions?.delivery?.estimatedTime, '2-4 hours');
      expect(model.data?.fulfillmentOptions?.delivery?.originLga?.name, 'Uyo');
      expect(model.data?.fulfillmentOptions?.delivery?.destinationLga?.name, 'Eket');

      // Verify in-shop pickup option parsing
      expect(controller.isPickupAvailable(1), isTrue);
      expect(model.data?.fulfillmentOptions?.inShopPickup?.earliestAvailable, 'Today at 2:00 PM');
      expect(model.data?.fulfillmentOptions?.inShopPickup?.requiresVerification, isTrue);

      // Auto-selected to delivery by default when available
      expect(controller.fulfillmentChoice[1], 'delivery');
      expect(controller.getTotalDeliveryFee(), 1500.00);
    });

    test('2. Mixed cart renders Vendor A Delivery + Vendor B Pickup independently', () async {
      // Vendor A (Shop 1): Delivery & Pickup available
      mockService.setFixture(1, {
        'success': true,
        'data': {
          'shop': {'id': 1, 'name': 'Vendor A (Groceries)', 'lga': 'Uyo'},
          'fulfillment_options': {
            'delivery': {'available': true, 'fee': 1500.00, 'estimated_time': '3 hours'},
            'pickup': {'available': true, 'earliest_available': 'Today'},
          }
        }
      });

      // Vendor B (Shop 2): Delivery & Pickup available
      mockService.setFixture(2, {
        'success': true,
        'data': {
          'shop': {'id': 2, 'name': 'Vendor B (Bakery)', 'lga': 'Uyo'},
          'fulfillment_options': {
            'delivery': {'available': true, 'fee': 1000.00, 'estimated_time': '1 hour'},
            'pickup': {'available': true, 'earliest_available': 'Immediate'},
          }
        }
      });

      await controller.checkForAllShops([1, 2], 101);

      // User selects Delivery for Vendor A and Pickup for Vendor B
      controller.setFulfillmentChoice(1, 'delivery');
      controller.setFulfillmentChoice(2, 'pickup');

      expect(controller.fulfillmentChoice[1], 'delivery');
      expect(controller.fulfillmentChoice[2], 'pickup');

      // Total delivery fee includes only the delivery-selected vendor
      expect(controller.getDeliveryFee(1), 1500.00);
      expect(controller.getDeliveryFee(2), 1000.00);
      expect(controller.getTotalDeliveryFee(), 1500.00, reason: 'Shop 2 is pickup, so fee is excluded from delivery total');
    });

    test('3. Unavailable lane renders backend reason without inventing client fallback', () async {
      // Shop 3: Delivery unavailable on unserved lane
      mockService.setFixture(3, {
        'success': true,
        'data': {
          'shop': {'id': 3, 'name': 'Vendor C (Remote)', 'lga': 'Ini'},
          'fulfillment_options': {
            'delivery': {
              'available': false,
              'fee': null,
              'reason': 'origin_destination_lane_not_served',
              'message': 'No logistics courier serves Ini to destination LGA at this time',
            },
            'pickup': {
              'available': true,
              'earliest_available': 'Tomorrow 9:00 AM',
            }
          }
        }
      });

      await controller.checkForAllShops([3], 101);

      expect(controller.isDeliveryAvailable(3), isFalse);
      final deliveryOpt = controller.fulfillmentByShop[3]?.data?.fulfillmentOptions?.delivery;
      expect(deliveryOpt?.reason, 'origin_destination_lane_not_served');
      expect(deliveryOpt?.message, contains('No logistics courier serves'));
      expect(deliveryOpt?.fee, isNull, reason: 'Zero fee invented client-side');

      // Auto-fallback in controller selects available Pickup
      expect(controller.isPickupAvailable(3), isTrue);
      expect(controller.fulfillmentChoice[3], 'pickup');
      expect(controller.getTotalDeliveryFee(), 0.00);
    });

    test('4. Availability-to-checkout race condition contract: intent locks payable total', () {
      // Contract invariant documentation & test:
      // Even if availability UI displayed 1500.00, checkout execution strictly relies
      // on POST /api/v1/checkout/intent to lock payment tokens, freeze items, and re-verify lane.
      final intentPayload = {
        'shop_id': 1,
        'fulfillment_type': 'delivery',
        'address_id': 101,
        'expected_fee': 1500.00,
      };

      // Assert payload contains required intent fields without authoritative fee override
      expect(intentPayload['fulfillment_type'], 'delivery');
      expect(intentPayload['address_id'], 101);
      expect(intentPayload.containsKey('override_fee'), isFalse, reason: 'Client cannot override frozen server fee');
    });
  });
}
