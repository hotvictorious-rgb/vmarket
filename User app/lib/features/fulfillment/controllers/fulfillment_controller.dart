import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/domain/models/fulfillment_availability_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/fulfillment/domain/services/fulfillment_service_interface.dart';

class FulfillmentController with ChangeNotifier {
  final FulfillmentServiceInterface fulfillmentServiceInterface;

  FulfillmentController({required this.fulfillmentServiceInterface});

  final Map<int, FulfillmentAvailabilityModel?> _fulfillmentByShop = {};
  final Map<int, String> _fulfillmentChoice = {}; // 'delivery' | 'pickup'
  bool _isLoading = false;

  Map<int, FulfillmentAvailabilityModel?> get fulfillmentByShop => _fulfillmentByShop;
  Map<int, String> get fulfillmentChoice => _fulfillmentChoice;
  bool get isLoading => _isLoading;

  Future<void> checkForAllShops(List<int> shopIds, int? addressId, {List<Map<String, dynamic>>? cartItems}) async {
    _isLoading = true;
    notifyListeners();

    _fulfillmentByShop.clear();

    for (int shopId in shopIds) {
      ApiResponseModel apiResponse = await fulfillmentServiceInterface.checkAvailability(
        shopId: shopId,
        shippingAddressId: addressId,
        cartItems: cartItems,
      );

      if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
        final model = FulfillmentAvailabilityModel.fromJson(apiResponse.response!.data);
        _fulfillmentByShop[shopId] = model;

        // Auto-select based on backend response: delivery first if available, else pickup if available
        if (model.data?.fulfillmentOptions?.delivery?.available == true) {
          _fulfillmentChoice[shopId] = 'delivery';
        } else if (model.data?.fulfillmentOptions?.inShopPickup?.available == true) {
          _fulfillmentChoice[shopId] = 'pickup';
        } else {
          _fulfillmentChoice[shopId] = 'unavailable';
        }
      } else {
        _fulfillmentByShop[shopId] = null;
        _fulfillmentChoice[shopId] = 'unavailable';
      }
    }

    _isLoading = false;
    notifyListeners();
  }

  void setFulfillmentChoice(int shopId, String choice) {
    _fulfillmentChoice[shopId] = choice;
    notifyListeners();
  }

  bool isDeliveryAvailable(int shopId) {
    return _fulfillmentByShop[shopId]?.data?.fulfillmentOptions?.delivery?.available ?? false;
  }

  bool isPickupAvailable(int shopId) {
    return _fulfillmentByShop[shopId]?.data?.fulfillmentOptions?.inShopPickup?.available ?? false;
  }

  double? getDeliveryFee(int shopId) {
    return _fulfillmentByShop[shopId]?.data?.fulfillmentOptions?.delivery?.fee;
  }

  double getTotalDeliveryFee() {
    double total = 0.0;
    _fulfillmentChoice.forEach((shopId, choice) {
      if (choice == 'delivery') {
        total += getDeliveryFee(shopId) ?? 0.0;
      }
    });
    return total;
  }

  void clear() {
    _fulfillmentByShop.clear();
    _fulfillmentChoice.clear();
    _isLoading = false;
  }
}
