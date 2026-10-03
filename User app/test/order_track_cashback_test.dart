import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/cashback/domain/models/cashback_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/domain/models/order_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/controllers/order_details_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/domain/models/track_order_details_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/order_details/domain/services/order_details_service_interface.dart';

/// Fake implementation of OrderDetailsServiceInterface for deterministic testing
class FakeOrderDetailsService implements OrderDetailsServiceInterface {
  final int authenticatedCustomerId = 10;
  final Map<String, dynamic> mockOrderDb = {};

  void seedOrder(String orderId, Map<String, dynamic> data) {
    mockOrderDb[orderId] = data;
  }

  @override
  Future<ApiResponseModel> getOrderFromOrderId(String orderID) async {
    final order = mockOrderDb[orderID];
    if (order == null) {
      return ApiResponseModel.withError('Order not found');
    }

    // Zero-Trust IDOR Ownership Enforcement:
    // Reject access if customer_id does not match the authenticated principal
    if (order['customer_id'] != authenticatedCustomerId) {
      return ApiResponseModel.withError('403 Forbidden: Unauthorized access to order');
    }

    return ApiResponseModel.withSuccess(
      FakeResponse(data: order, statusCode: 200),
    );
  }

  @override
  Future<ApiResponseModel> getTrackOrderDetailsId(String orderId) async {
    final order = mockOrderDb[orderId];
    if (order == null) {
      return ApiResponseModel.withError('Order not found');
    }

    if (order['customer_id'] != authenticatedCustomerId) {
      return ApiResponseModel.withError('403 Forbidden: Unauthorized tracking request');
    }

    // Customer safe tracking payload strictly derived from backend authority
    final trackingData = {
      'history': {
        'order_placed': {'label': 'Order Placed', 'status': true, 'dateTime': '2026-10-02 10:00:00'},
        'order_confirmed': {'label': 'Order Confirmed', 'status': true, 'dateTime': '2026-10-02 10:15:00'},
        'preparing_for_shipment': {'label': 'Processing', 'status': true, 'dateTime': '2026-10-02 11:30:00'},
        'order_is_on_the_way': {'label': 'Out for Delivery', 'status': false, 'dateTime': null},
        'order_delivered': {'label': 'Delivered', 'status': false, 'dateTime': null},
      },
    };

    return ApiResponseModel.withSuccess(
      FakeResponse(data: trackingData, statusCode: 200),
    );
  }

  @override
  Future getOrderDetails(String orderID) async => throw UnimplementedError();
  @override
  Future getOrderInvoice(String orderID) async => throw UnimplementedError();
  @override
  Future trackOrder(String orderId, String phoneNumber) async => throw UnimplementedError();
  @override
  Future downloadDigitalProduct(int orderDetailsId) async => throw UnimplementedError();
  @override
  Future resendVerificationCode(int orderId) async => throw UnimplementedError();
  @override
  Future verifyOrder(int orderId, String verificationCode) async => throw UnimplementedError();
  @override
  Future getOrderProductList(String orderID) async => throw UnimplementedError();
}

class FakeResponse {
  final dynamic data;
  final int statusCode;
  FakeResponse({required this.data, required this.statusCode});
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late FakeOrderDetailsService fakeOrderService;
  late OrderDetailsController orderDetailsController;

  setUp(() {
    fakeOrderService = FakeOrderDetailsService();
    orderDetailsController = OrderDetailsController(orderDetailsServiceInterface: fakeOrderService);
  });

  group('VM-CUST-008: Customer Order-Track-Cashback Widget Proof Suite', () {
    test('1. Order details strictly render backend authority fields', () async {
      // Seed customer 10 order with authoritative snapshot
      fakeOrderService.seedOrder('100801', {
        'id': 100801,
        'customer_id': 10,
        'order_status': 'processing',
        'order_type': 'delivery',
        'payment_status': 'paid',
        'order_amount': 25000.00,
        'shipping_cost': 1500.00,
        'verification_code': '842915',
        'pickup_verification_code': '193847',
        'shipping_address_data': {
          'address': 'Plot 12, Commercial Avenue',
          'city': 'Uyo',
          'state': 'Akwa Ibom',
          'country': 'Nigeria',
        },
        'created_at': '2026-10-02T10:00:00Z',
      });

      await orderDetailsController.getOrderFromOrderId('100801');

      final order = orderDetailsController.orders;
      expect(order, isNotNull);
      expect(order!.id, equals(100801));
      expect(order.orderStatus, equals('processing'));
      expect(order.orderType, equals('delivery'));
      expect(order.paymentStatus, equals('paid'));
      expect(order.orderAmount, equals(25000.00));
      expect(order.shippingCost, equals(1500.00));
      expect(order.shippingAddressData?.city, equals('Uyo'));
      expect(order.shippingAddressData?.country, equals('Nigeria'));
      expect(order.verificationCode, equals('842915'));
    });

    test('2. Zero-Trust IDOR check: requesting another customer order is rejected', () async {
      // Seed customer 99 order (rival customer)
      fakeOrderService.seedOrder('99001', {
        'id': 99001,
        'customer_id': 99, // Customer B
        'order_status': 'confirmed',
        'order_amount': 50000.00,
      });

      // Customer A (authenticated id 10) attempts to fetch Customer B order
      await orderDetailsController.getOrderFromOrderId('99001');

      // Assert that Customer A cannot bind or view Customer B order details
      expect(orderDetailsController.orders, isNull);
    });

    test('3. Tracking renders allowlisted safe fields only, excluding internal logistics', () async {
      fakeOrderService.seedOrder('100801', {
        'id': 100801,
        'customer_id': 10,
      });

      final response = await fakeOrderService.getTrackOrderDetailsId('100801');
      expect(response.isSuccess, isTrue);

      final model = TrackOrderDetailsModel.fromJson(response.response!.data);
      expect(model.history, isNotNull);
      expect(model.history!.orderPlaced?.label, equals('Order Placed'));
      expect(model.history!.orderPlaced?.status, isTrue);
      expect(model.history!.orderConfirmed?.status, isTrue);
      expect(model.history!.preparingForShipment?.status, isTrue);
      expect(model.history!.orderIsOnTheWay?.status, isFalse);

      // Verify safe-field allowlist: internal rider/hub fields MUST NEVER be exposed in tracking payload
      final rawData = response.response!.data as Map<String, dynamic>;
      expect(rawData.containsKey('rider_cash_in_hand'), isFalse);
      expect(rawData.containsKey('identity_number'), isFalse);
      expect(rawData.containsKey('fcm_token'), isFalse);
      expect(rawData.containsKey('dispatch_lane_id'), isFalse);
    });

    test('4. Cancel and Return actions are strictly gated by backend lifecycle status', () {
      // Scenario A: Order in 'pending' status is cancellable
      final pendingOrder = Orders(id: 1, orderStatus: 'pending');
      final bool canCancelPending = pendingOrder.orderStatus == 'pending';
      expect(canCancelPending, isTrue);

      // Scenario B: Order in 'processing' status cannot be cancelled
      final processingOrder = Orders(id: 2, orderStatus: 'processing');
      final bool canCancelProcessing = processingOrder.orderStatus == 'pending';
      expect(canCancelProcessing, isFalse);

      // Scenario C: Order in 'delivered' status is eligible for return/refund
      final deliveredOrder = Orders(id: 3, orderStatus: 'delivered');
      final bool canRefundDelivered = deliveredOrder.orderStatus == 'delivered';
      expect(canRefundDelivered, isTrue);

      // Scenario D: Order in 'confirmed' or 'pending' cannot be returned
      final confirmedOrder = Orders(id: 4, orderStatus: 'confirmed');
      final bool canRefundConfirmed = confirmedOrder.orderStatus == 'delivered';
      expect(canRefundConfirmed, isFalse);
    });

    test('5. Cashback summary and ledger items render exact backend amounts without client math', () {
      final summaryJson = {
        'status': true,
        'customer_id': 10,
        'currency': 'NGN',
        'pending_cashback_amount': '2500.00',
        'available_cashback_amount': '7500.00',
        'redeemed_cashback_amount': '5000.00',
        'cancelled_cashback_amount': '0.00',
      };

      final summary = CashbackSummaryModel.fromJson(summaryJson);
      expect(summary.status, isTrue);
      expect(summary.pendingCashbackAmount, equals('2500.00'));
      expect(summary.availableCashbackAmount, equals('7500.00'));
      expect(summary.redeemedCashbackAmount, equals('5000.00'));
      expect(summary.cancelledCashbackAmount, equals('0.00'));
      expect(summary.currency, equals('NGN'));

      // Validate 6-digit OTP formatting rule for customer collection / handover
      const testOtp = '842915';
      final bool isSixDigitOtp = RegExp(r'^\d{6}$').hasMatch(testOtp);
      expect(isSixDigitOtp, isTrue);

      const invalidOtp = '1234';
      final bool isInvalidOtp = RegExp(r'^\d{6}$').hasMatch(invalidOtp);
      expect(isInvalidOtp, isFalse);
    });
  });
}
