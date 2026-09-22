class PickupReservationResponse {
  bool? status;
  String? message;
  int? reservationsCount;
  List<PickupReservationModel>? reservations;

  PickupReservationResponse({
    this.status,
    this.message,
    this.reservationsCount,
    this.reservations,
  });

  PickupReservationResponse.fromJson(Map<String, dynamic> json) {
    status = json['status'];
    message = json['message'];
    reservationsCount = json['reservations_count'];
    if (json['reservations'] != null) {
      reservations = <PickupReservationModel>[];
      json['reservations'].forEach((v) {
        reservations!.add(PickupReservationModel.fromJson(v));
      });
    }
  }
}

class PickupReservationModel {
  int? id;
  String? reservationCode;
  String? idempotencyKey;
  int? customerId;
  int? sellerId;
  int? shopId;
  String? status;
  String? totalAmount;
  String? currency;
  String? expiresAt;
  PickupShopSnapshot? shop;
  List<PickupItemSnapshot>? items;

  PickupReservationModel({
    this.id,
    this.reservationCode,
    this.idempotencyKey,
    this.customerId,
    this.sellerId,
    this.shopId,
    this.status,
    this.totalAmount,
    this.currency,
    this.expiresAt,
    this.shop,
    this.items,
  });

  PickupReservationModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    reservationCode = json['reservation_code'];
    idempotencyKey = json['idempotency_key'];
    customerId = json['customer_id'];
    sellerId = json['seller_id'];
    shopId = json['shop_id'];
    status = json['status'];
    totalAmount = json['total_amount']?.toString();
    currency = json['currency'];
    expiresAt = json['expires_at'];

    // If reservation_items snapshot is present:
    dynamic itemsData = json['reservation_items'];
    if (itemsData is Map<String, dynamic>) {
      if (itemsData['shop'] != null) {
        shop = PickupShopSnapshot.fromJson(itemsData['shop']);
      }
      if (itemsData['items'] != null && itemsData['items'] is List) {
        items = <PickupItemSnapshot>[];
        itemsData['items'].forEach((v) {
          items!.add(PickupItemSnapshot.fromJson(v));
        });
      }
    } else if (json['shop'] != null) {
      shop = PickupShopSnapshot.fromJson(json['shop']);
    }
  }
}

class PickupShopSnapshot {
  int? shopId;
  int? sellerId;
  String? name;
  String? address;
  String? contact;
  String? directionGuidance;

  PickupShopSnapshot({
    this.shopId,
    this.sellerId,
    this.name,
    this.address,
    this.contact,
    this.directionGuidance,
  });

  PickupShopSnapshot.fromJson(Map<String, dynamic> json) {
    shopId = json['shop_id'];
    sellerId = json['seller_id'];
    name = json['name'];
    address = json['address'];
    contact = json['contact'];
    directionGuidance = json['direction_guidance'];
  }
}

class PickupItemSnapshot {
  int? cartId;
  int? productId;
  String? productName;
  String? productType;
  String? variant;
  int? quantity;
  String? unitPrice;
  String? discount;
  String? tax;
  String? lineTotal;

  PickupItemSnapshot({
    this.cartId,
    this.productId,
    this.productName,
    this.productType,
    this.variant,
    this.quantity,
    this.unitPrice,
    this.discount,
    this.tax,
    this.lineTotal,
  });

  PickupItemSnapshot.fromJson(Map<String, dynamic> json) {
    cartId = json['cart_id'];
    productId = json['product_id'];
    productName = json['product_name'];
    productType = json['product_type'];
    variant = json['variant'];
    quantity = json['quantity'];
    unitPrice = json['unit_price']?.toString();
    discount = json['discount']?.toString();
    tax = json['tax']?.toString();
    lineTotal = json['line_total']?.toString();
  }
}
