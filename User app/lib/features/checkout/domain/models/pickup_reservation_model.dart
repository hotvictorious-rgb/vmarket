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
  String? createdAt;
  int? orderId;
  PickupShopSnapshot? shop;
  List<PickupItemSnapshot>? items;
  // [AI] Backend-supplied cashback promise — app MUST display this, never calculate locally
  PickupCashbackToEarn? cashbackToEarn;

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
    this.createdAt,
    this.orderId,
    this.shop,
    this.items,
    this.cashbackToEarn,
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
    createdAt = json['created_at'];
    orderId = json['order_id'];

    // [AI] Priority 1: New enriched API response shape — shop_snapshot top-level key
    if (json['shop_snapshot'] != null && json['shop_snapshot'] is Map<String, dynamic>) {
      shop = PickupShopSnapshot.fromEnrichedJson(json['shop_snapshot']);
    }
    // [AI] Priority 2: New enriched API response — items top-level
    if (json['items'] != null && json['items'] is List) {
      items = <PickupItemSnapshot>[];
      json['items'].forEach((v) {
        items!.add(PickupItemSnapshot.fromJson(v));
      });
    }
    // [AI] Legacy fallback: reservation_items snapshot structure (pre-V1 enrichment)
    if (shop == null) {
      dynamic itemsData = json['reservation_items'];
      if (itemsData is Map<String, dynamic>) {
        if (itemsData['shop'] != null) {
          shop = PickupShopSnapshot.fromJson(itemsData['shop']);
        }
        if (items == null && itemsData['items'] != null && itemsData['items'] is List) {
          items = <PickupItemSnapshot>[];
          itemsData['items'].forEach((v) {
            items!.add(PickupItemSnapshot.fromJson(v));
          });
        }
      } else if (json['shop'] != null) {
        shop = PickupShopSnapshot.fromJson(json['shop']);
      }
    }

    // [AI] Cashback to earn — from backend-enriched response
    if (json['cashback_to_earn'] != null && json['cashback_to_earn'] is Map<String, dynamic>) {
      cashbackToEarn = PickupCashbackToEarn.fromJson(json['cashback_to_earn']);
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

  // [AI] New enriched response shape (flat keys from controller mapping)
  PickupShopSnapshot.fromEnrichedJson(Map<String, dynamic> json) {
    shopId = json['shop_id'];
    name = json['shop_name'];
    address = json['shop_address'];
    sellerId = null;
    contact = null;
    directionGuidance = null;
  }
}

// [AI] Cashback promised by backend for this reservation — displayed as earn badge in UI
class PickupCashbackToEarn {
  double? percent;
  String? estimatedNaira;

  PickupCashbackToEarn({this.percent, this.estimatedNaira});

  PickupCashbackToEarn.fromJson(Map<String, dynamic> json) {
    percent = double.tryParse(json['percent']?.toString() ?? '0');
    estimatedNaira = json['estimated_naira']?.toString();
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
