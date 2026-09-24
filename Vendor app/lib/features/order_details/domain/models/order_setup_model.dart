class OrderSetupModel {
  int? orderId;
  String? orderStatus;

  OrderSetupModel({
    this.orderId,
    this.orderStatus,
  });

  factory OrderSetupModel.fromJson(Map<String, dynamic> json) {
    return OrderSetupModel(
      orderId: json['order_id'],
      orderStatus: json['order_status'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'order_id': orderId,
      'order_status': orderStatus,
    };
  }

  void clear() {
    orderId = null;
    orderStatus = null;
  }
}


