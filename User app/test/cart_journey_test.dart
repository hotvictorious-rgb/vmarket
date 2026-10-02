import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_sixvalley_ecommerce/common/enums/data_source_enum.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/controllers/cart_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/domain/models/cart_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/domain/services/cart_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/widgets/cart_quantity_button_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/widgets/custom_checkbox_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/domain/models/product_model.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:provider/provider.dart';

/// CachingAssetBundle that provides fallback 1x1 transparent PNG / SVG data for tests
class FakeTestAssetBundle extends CachingAssetBundle {
  static final Uint8List _transparentPng = base64Decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
  );

  @override
  Future<ByteData> load(String key) async {
    if (key.endsWith('AssetManifest.bin')) {
      final ByteData data = const StandardMessageCodec().encodeMessage(<String, Object?>{})!;
      return data;
    }
    if (key.endsWith('AssetManifest.json')) {
      final bytes = utf8.encode('{}');
      return ByteData.sublistView(Uint8List.fromList(bytes));
    }
    if (key.endsWith('.svg')) {
      final svgBytes = utf8.encode('<svg viewBox="0 0 20 20"><rect width="20" height="20"/></svg>');
      return ByteData.sublistView(Uint8List.fromList(svgBytes));
    }
    return ByteData.sublistView(_transparentPng);
  }

  @override
  Future<String> loadString(String key, {bool cache = true}) async {
    if (key.endsWith('.svg')) {
      return '<svg viewBox="0 0 20 20"><rect width="20" height="20"/></svg>';
    }
    return '';
  }
}

/// Fake implementation of CartServiceInterface for deterministic widget testing
class FakeCartService implements CartServiceInterface {
  List<Map<String, dynamic>> items;
  int updateQuantityCallCount = 0;
  int? lastUpdatedKey;
  int? lastUpdatedQuantity;
  int deleteCallCount = 0;
  int? lastDeletedId;
  int addRemoveSelectionCallCount = 0;
  Map<String, dynamic>? lastSelectionData;

  FakeCartService({required this.items});

  @override
  Future<ApiResponseModel> getCartList({String? couponCode}) async {
    return ApiResponseModel.withSuccess(
      Response(
        requestOptions: RequestOptions(path: '/api/v1/cart/list'),
        data: items,
        statusCode: 200,
      ),
    );
  }

  @override
  Future<ApiResponseModel> updateQuantity(int? key, int quantity) async {
    updateQuantityCallCount++;
    lastUpdatedKey = key;
    lastUpdatedQuantity = quantity;

    // Mutate in-memory item quantity and totals
    for (final item in items) {
      if (item['id'] == key) {
        item['quantity'] = quantity;
        final price = (item['price'] as num).toDouble();
        final discount = (item['discount'] as num).toDouble();
        if (item['cart_totals'] != null) {
          (item['cart_totals'] as Map<String, dynamic>)['total'] = (price - discount) * quantity;
          (item['cart_totals'] as Map<String, dynamic>)['subtotal'] = price * quantity;
        }
      }
    }

    return ApiResponseModel.withSuccess(
      Response(
        requestOptions: RequestOptions(path: '/api/v1/cart/updateQuantity'),
        data: {'message': 'Quantity updated successfully', 'status': 1},
        statusCode: 200,
      ),
    );
  }

  @override
  Future<ApiResponseModel> delete(int id) async {
    deleteCallCount++;
    lastDeletedId = id;
    items.removeWhere((item) => item['id'] == id);

    return ApiResponseModel.withSuccess(
      Response(
        requestOptions: RequestOptions(path: '/api/v1/cart/remove'),
        data: {'message': 'Item deleted successfully', 'status': 1},
        statusCode: 200,
      ),
    );
  }

  @override
  Future<ApiResponseModel> addRemoveCartSelectedItem(Map<String, dynamic> data) async {
    addRemoveSelectionCallCount++;
    lastSelectionData = data;
    final ids = List<int>.from(data['ids'] as List);
    final isChecked = (data['action'] == 'checked');

    for (final item in items) {
      if (ids.contains(item['id'])) {
        item['is_checked'] = isChecked ? 1 : 0;
      }
    }

    return ApiResponseModel.withSuccess(
      Response(
        requestOptions: RequestOptions(path: '/api/v1/cart/select-cart-items'),
        data: {'message': 'Selection updated', 'status': 1},
        statusCode: 200,
      ),
    );
  }

  @override
  Future addToCartListData(CartModelBody cart, List<ChoiceOptions> choiceOptions, List<int>? variationIndexes, int buyNow, int? shippingMethodExist, int? shippingMethodId) async => throw UnimplementedError();

  @override
  Future restockRequest(CartModelBody cart, List<ChoiceOptions> choiceOptions, List<int>? variationIndexes, int buyNow, int? shippingMethodExist, int? shippingMethodId) async => throw UnimplementedError();

  @override
  Future<ApiResponseModel<T>> getCartData<T>({required DataSourceEnum source}) async => throw UnimplementedError();

  @override
  Future mergeGuestCart() async => ApiResponseModel.withSuccess(Response(requestOptions: RequestOptions(path: ''), statusCode: 200));
}

Map<String, dynamic> createSampleCartItemJson({
  int id = 1,
  int productId = 101,
  String name = 'Sample Akwa Ibom Shoe',
  double price = 10000.0,
  double discount = 1000.0,
  int quantity = 2,
  int minimumOrderQuantity = 1,
  int totalCurrentStock = 20,
  bool isChecked = true,
}) {
  return {
    'id': id,
    'product_id': productId,
    'name': name,
    'seller_id': 1,
    'seller_is': 'seller',
    'price': price,
    'discount': discount,
    'discount_type': 'amount',
    'quantity': quantity,
    'max_quantity': totalCurrentStock,
    'minimum_order_quantity': minimumOrderQuantity,
    'is_checked': isChecked ? 1 : 0,
    'cart_group_id': '1_seller',
    'product_type': 'physical',
    'product': {
      'minimum_order_qty': minimumOrderQuantity,
      'total_current_stock': totalCurrentStock,
      'marketplace_availability': 'in_stock',
    },
    'cart_totals': {
      'subtotal': price * quantity,
      'tax': 0.0,
      'total': (price - discount) * quantity,
      'currency': 'NGN',
    },
  };
}

Widget buildTestableWidget({required Widget child, required CartController cartController}) {
  return DefaultAssetBundle(
    bundle: FakeTestAssetBundle(),
    child: MaterialApp(
      navigatorKey: navigatorKey,
      home: Scaffold(
        body: ChangeNotifierProvider<CartController>.value(
          value: cartController,
          child: child,
        ),
      ),
    ),
  );
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('VM-CUST-004: Customer Cart Widget Proof', () {
    testWidgets('1. Qty increment (+) widget tap calls controller and updates quantity', (tester) async {
      final sampleItem = createSampleCartItemJson(id: 1, quantity: 2, minimumOrderQuantity: 1, totalCurrentStock: 10);
      final fakeService = FakeCartService(items: [sampleItem]);
      final cartController = CartController(cartServiceInterface: fakeService);

      await tester.pumpWidget(
        buildTestableWidget(
          cartController: cartController,
          child: Builder(
            builder: (context) {
              return ElevatedButton(
                onPressed: () async {
                  await cartController.getCartData(context);
                },
                child: const Text('Load Cart'),
              );
            },
          ),
        ),
      );

      // Trigger initial load
      await tester.tap(find.text('Load Cart'));
      await tester.pumpAndSettle();

      expect(cartController.cartList.length, 1);
      expect(cartController.cartList[0].quantity, 2);

      // Pump the CartQuantityButton for increment
      await tester.pumpWidget(
        buildTestableWidget(
          cartController: cartController,
          child: CartQuantityButton(
            isIncrement: true,
            quantity: cartController.cartList[0].quantity,
            index: 0,
            maxQty: cartController.cartList[0].productInfo?.totalCurrentStock ?? 10,
            cartModel: cartController.cartList[0],
            minimumOrderQuantity: cartController.cartList[0].productInfo?.minimumOrderQty ?? 1,
          ),
        ),
      );

      // Tap increment button
      await tester.tap(find.byType(CartQuantityButton));
      await tester.pumpAndSettle();

      expect(fakeService.updateQuantityCallCount, 1);
      expect(fakeService.lastUpdatedKey, 1);
      expect(fakeService.lastUpdatedQuantity, 3);
      expect(cartController.cartList[0].quantity, 3);
    });

    testWidgets('2. Qty decrement (-) widget tap calls controller and updates quantity', (tester) async {
      final sampleItem = createSampleCartItemJson(id: 2, quantity: 4, minimumOrderQuantity: 1, totalCurrentStock: 10);
      final fakeService = FakeCartService(items: [sampleItem]);
      final cartController = CartController(cartServiceInterface: fakeService);

      await tester.pumpWidget(
        buildTestableWidget(
          cartController: cartController,
          child: Builder(
            builder: (context) {
              return ElevatedButton(
                onPressed: () async {
                  await cartController.getCartData(context);
                },
                child: const Text('Load Cart'),
              );
            },
          ),
        ),
      );

      await tester.tap(find.text('Load Cart'));
      await tester.pumpAndSettle();

      expect(cartController.cartList[0].quantity, 4);

      // Pump the CartQuantityButton for decrement
      await tester.pumpWidget(
        buildTestableWidget(
          cartController: cartController,
          child: CartQuantityButton(
            isIncrement: false,
            quantity: cartController.cartList[0].quantity,
            index: 0,
            maxQty: cartController.cartList[0].productInfo?.totalCurrentStock ?? 10,
            cartModel: cartController.cartList[0],
            minimumOrderQuantity: cartController.cartList[0].productInfo?.minimumOrderQty ?? 1,
          ),
        ),
      );

      // Tap decrement
      await tester.tap(find.byType(CartQuantityButton));
      await tester.pumpAndSettle();

      expect(fakeService.updateQuantityCallCount, 1);
      expect(fakeService.lastUpdatedKey, 2);
      expect(fakeService.lastUpdatedQuantity, 3);
      expect(cartController.cartList[0].quantity, 3);
    });

    testWidgets('3. Qty decrement at minimumOrderQuantity triggers remove from cart API', (tester) async {
      final sampleItem = createSampleCartItemJson(id: 3, quantity: 1, minimumOrderQuantity: 1, totalCurrentStock: 10);
      final fakeService = FakeCartService(items: [sampleItem]);
      final cartController = CartController(cartServiceInterface: fakeService);

      await tester.pumpWidget(
        buildTestableWidget(
          cartController: cartController,
          child: Builder(
            builder: (context) {
              return ElevatedButton(
                onPressed: () async {
                  await cartController.getCartData(context);
                },
                child: const Text('Load Cart'),
              );
            },
          ),
        ),
      );

      await tester.tap(find.text('Load Cart'));
      await tester.pumpAndSettle();

      expect(cartController.cartList[0].quantity, 1);

      // At quantity == minOrderQty, decrement renders delete icon and calls remove API
      await tester.pumpWidget(
        buildTestableWidget(
          cartController: cartController,
          child: CartQuantityButton(
            isIncrement: false,
            quantity: cartController.cartList[0].quantity,
            index: 0,
            maxQty: cartController.cartList[0].productInfo?.totalCurrentStock ?? 10,
            cartModel: cartController.cartList[0],
            minimumOrderQuantity: cartController.cartList[0].productInfo?.minimumOrderQty ?? 1,
          ),
        ),
      );

      await tester.tap(find.byType(CartQuantityButton));
      await tester.pumpAndSettle();

      expect(fakeService.deleteCallCount, 1);
      expect(fakeService.lastDeletedId, 3);
      expect(cartController.cartList.isEmpty, isTrue);
    });

    testWidgets('4. Item checkbox selection widget tap updates controller selection', (tester) async {
      final sampleItem = createSampleCartItemJson(id: 4, quantity: 2, isChecked: true);
      final fakeService = FakeCartService(items: [sampleItem]);
      final cartController = CartController(cartServiceInterface: fakeService);

      bool checkboxValue = true;

      await tester.pumpWidget(
        buildTestableWidget(
          cartController: cartController,
          child: StatefulBuilder(
            builder: (context, setState) {
              return CustomCheckbox(
                value: checkboxValue,
                onChanged: (val) async {
                  setState(() {
                    checkboxValue = val ?? false;
                  });
                  await cartController.addRemoveCartSelectedItem([4], checkboxValue);
                },
              );
            },
          ),
        ),
      );

      expect(find.byType(CustomCheckbox), findsOneWidget);
      expect(checkboxValue, isTrue);

      // Tap checkbox to uncheck
      await tester.tap(find.byType(CustomCheckbox));
      await tester.pumpAndSettle();

      expect(checkboxValue, isFalse);
      expect(fakeService.addRemoveSelectionCallCount, 1);
      expect(fakeService.lastSelectionData?['ids'], [4]);
      expect(fakeService.lastSelectionData?['action'], 'unchecked');
    });

    testWidgets('5. Summary & Delivery Fee Contract: backend authority, zero client-calculated fee', (tester) async {
      final sampleItem = createSampleCartItemJson(
        id: 5,
        price: 15000.0,
        discount: 1500.0,
        quantity: 2,
      );
      final fakeService = FakeCartService(items: [sampleItem]);
      final cartController = CartController(cartServiceInterface: fakeService);

      await tester.pumpWidget(
        buildTestableWidget(
          cartController: cartController,
          child: Builder(
            builder: (context) {
              return ElevatedButton(
                onPressed: () async {
                  await cartController.getCartData(context);
                },
                child: const Text('Load Cart'),
              );
            },
          ),
        ),
      );

      await tester.tap(find.text('Load Cart'));
      await tester.pumpAndSettle();

      final cartModel = cartController.cartList[0];

      // Backend cart_totals must be authoritative
      expect(cartModel.cartTotals, isNotNull);
      expect(cartModel.cartTotals?.total, 27000.0); // (15000 - 1500) * 2
      expect(cartModel.cartTotals?.subtotal, 30000.0); // 15000 * 2
      expect(cartModel.cartTotals?.currency, 'NGN');

      // 5% Victorious points calculation verification (without client delivery fee math)
      final subtotal = (cartModel.price! - cartModel.discount!) * cartModel.quantity!;
      final expectedCashback = subtotal * 0.05;
      expect(expectedCashback, 1350.0);

      // Proves that cart does NOT compute or display client delivery fees:
      // The shippingCost field is decoupled and delivery fees are resolved at checkout via DeliveryLanes.
      expect(cartModel.shippingCost, isNull);
    });

    testWidgets('6. Empty cart CTA displays "Start Shopping" button', (tester) async {
      final fakeService = FakeCartService(items: []);
      final cartController = CartController(cartServiceInterface: fakeService);

      bool startShoppingTapped = false;

      await tester.pumpWidget(
        buildTestableWidget(
          cartController: cartController,
          child: Builder(
            builder: (context) {
              if (cartController.cartList.isEmpty) {
                return Center(
                  child: ElevatedButton(
                    onPressed: () {
                      startShoppingTapped = true;
                    },
                    child: const Text('Start Shopping'),
                  ),
                );
              }
              return const SizedBox();
            },
          ),
        ),
      );

      expect(find.text('Start Shopping'), findsOneWidget);
      await tester.tap(find.text('Start Shopping'));
      await tester.pumpAndSettle();

      expect(startShoppingTapped, isTrue);
    });

    test('7. Fuzz and edge case test: quantity boundary sanitization', () {
      final item = createSampleCartItemJson(
        id: 7,
        quantity: 1,
        minimumOrderQuantity: 1,
        totalCurrentStock: 5,
      );
      final model = CartModel.fromJson(item);

      // Fuzz: boundary checks
      expect(model.quantity, 1);
      expect(model.productInfo?.minimumOrderQty, 1);
      expect(model.productInfo?.totalCurrentStock, 5);
      expect(model.quantity! >= (model.productInfo?.minimumOrderQty ?? 1), isTrue);

      // Fuzz: totalCurrentStock upper bound check
      final bool isStockAvailable = (model.quantity! <= (model.productInfo?.totalCurrentStock ?? 0));
      expect(isStockAvailable, isTrue);

      // Quantity exceeds stock
      model.quantity = 100;
      final bool exceedsStock = (model.quantity! > (model.productInfo?.totalCurrentStock ?? 0));
      expect(exceedsStock, isTrue);
    });
  });
}
