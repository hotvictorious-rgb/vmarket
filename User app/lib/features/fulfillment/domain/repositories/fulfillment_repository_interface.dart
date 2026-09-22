import 'package:flutter_sixvalley_ecommerce/interface/repo_interface.dart';

abstract class FulfillmentRepositoryInterface implements RepositoryInterface {
  Future checkAvailability({required int shopId, int? shippingAddressId, List<Map<String, dynamic>>? cartItems});
  Future getDeliveryFee({required int shopId, required int shippingAddressId, List<Map<String, dynamic>>? cartItems});
}
