class FulfillmentAvailabilityModel {
  bool? success;
  String? message;
  FulfillmentData? data;

  FulfillmentAvailabilityModel({this.success, this.message, this.data});

  FulfillmentAvailabilityModel.fromJson(Map<String, dynamic> json) {
    success = json['success'] ?? false;
    message = json['message'];
    data = json['data'] != null ? FulfillmentData.fromJson(json['data']) : null;
  }
}

class FulfillmentData {
  ShopLocation? shop;
  AddressLocation? address;
  FulfillmentOptions? fulfillmentOptions;

  FulfillmentData({this.shop, this.address, this.fulfillmentOptions});

  FulfillmentData.fromJson(Map<String, dynamic> json) {
    shop = json['shop'] != null ? ShopLocation.fromJson(json['shop']) : null;
    address = json['address'] != null ? AddressLocation.fromJson(json['address']) : null;
    fulfillmentOptions = json['fulfillment_options'] != null
        ? FulfillmentOptions.fromJson(json['fulfillment_options'])
        : null;
  }
}

class ShopLocation {
  int? id;
  String? name;
  String? lga;
  String? state;

  ShopLocation({this.id, this.name, this.lga, this.state});

  ShopLocation.fromJson(Map<String, dynamic> json) {
    id = json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '');
    name = json['name'];
    lga = json['lga'];
    state = json['state'];
  }
}

class AddressLocation {
  int? id;
  String? lga;
  String? state;

  AddressLocation({this.id, this.lga, this.state});

  AddressLocation.fromJson(Map<String, dynamic> json) {
    id = json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '');
    lga = json['lga'];
    state = json['state'];
  }
}

class LgaRef {
  int? id;
  String? name;
  String? state;

  LgaRef({this.id, this.name, this.state});

  LgaRef.fromJson(dynamic json) {
    if (json is String) {
      name = json;
      return;
    }
    if (json is Map<String, dynamic>) {
      id = json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '');
      name = json['name']?.toString();
      state = json['state']?.toString();
    }
  }

  String get display => [name, state].where((e) => (e ?? '').isNotEmpty).join(', ');
}

class FulfillmentOptions {
  DeliveryOption? delivery;
  PickupOption? inShopPickup;

  FulfillmentOptions({this.delivery, this.inShopPickup});

  FulfillmentOptions.fromJson(Map<String, dynamic> json) {
    delivery = json['delivery'] != null ? DeliveryOption.fromJson(json['delivery']) : null;
    inShopPickup = json['pickup'] != null ? PickupOption.fromJson(json['pickup']) : null;
  }
}

class DeliveryOption {
  bool? available;
  double? fee;
  LgaRef? originLga;
  LgaRef? destinationLga;
  String? estimatedTime;
  String? reason;
  String? message;

  DeliveryOption({this.available, this.fee, this.originLga, this.destinationLga, this.estimatedTime, this.reason, this.message});

  DeliveryOption.fromJson(Map<String, dynamic> json) {
    available = json['available'] ?? false;
    fee = json['fee'] != null ? double.tryParse(json['fee'].toString()) : null;
    originLga = json['origin_lga'] != null ? LgaRef.fromJson(json['origin_lga']) : null;
    destinationLga = json['destination_lga'] != null ? LgaRef.fromJson(json['destination_lga']) : null;
    estimatedTime = json['estimated_time']?.toString();
    reason = json['reason']?.toString();
    message = json['message']?.toString();
  }
}

class PickupOption {
  bool? available;
  bool? requiresVerification;
  String? reason;
  String? message;
  List<String>? availableTimes;
  String? earliestAvailable;

  PickupOption({this.available, this.requiresVerification, this.reason, this.message, this.availableTimes, this.earliestAvailable});

  PickupOption.fromJson(Map<String, dynamic> json) {
    available = json['available'] ?? false;
    requiresVerification = json['requires_verification'] ?? true;
    reason = json['reason']?.toString();
    message = json['message']?.toString();
    if (json['available_times'] is List) {
      availableTimes = (json['available_times'] as List).map((e) => e.toString()).toList();
    }
    earliestAvailable = json['earliest_available']?.toString();
  }
}
