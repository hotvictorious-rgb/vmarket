class CashbackSummaryModel {
  bool? status;
  int? customerId;
  String? currency;
  String? pendingCashbackAmount;
  String? availableCashbackAmount;
  String? redeemedCashbackAmount;
  String? cancelledCashbackAmount;

  CashbackSummaryModel({
    this.status,
    this.customerId,
    this.currency,
    this.pendingCashbackAmount,
    this.availableCashbackAmount,
    this.redeemedCashbackAmount,
    this.cancelledCashbackAmount,
  });

  CashbackSummaryModel.fromJson(Map<String, dynamic> json) {
    status = json['status'];
    customerId = json['customer_id'];
    currency = json['currency'] ?? 'NGN';
    pendingCashbackAmount = json['pending_cashback_amount']?.toString() ?? '0.00';
    availableCashbackAmount = json['available_cashback_amount']?.toString() ?? '0.00';
    redeemedCashbackAmount = json['redeemed_cashback_amount']?.toString() ?? '0.00';
    cancelledCashbackAmount = json['cancelled_cashback_amount']?.toString() ?? '0.00';
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['status'] = status;
    data['customer_id'] = customerId;
    data['currency'] = currency;
    data['pending_cashback_amount'] = pendingCashbackAmount;
    data['available_cashback_amount'] = availableCashbackAmount;
    data['redeemed_cashback_amount'] = redeemedCashbackAmount;
    data['cancelled_cashback_amount'] = cancelledCashbackAmount;
    return data;
  }
}

class CashbackLedgerItem {
  int? id;
  int? orderId;
  String? merchandiseAmount;
  String? cashbackRate;
  String? cashbackAmount;
  String? status;
  String? availableAt;
  String? redeemedAt;
  int? redeemedOrderId;
  String? description;
  String? createdAt;

  CashbackLedgerItem({
    this.id,
    this.orderId,
    this.merchandiseAmount,
    this.cashbackRate,
    this.cashbackAmount,
    this.status,
    this.availableAt,
    this.redeemedAt,
    this.redeemedOrderId,
    this.description,
    this.createdAt,
  });

  CashbackLedgerItem.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    orderId = json['order_id'];
    merchandiseAmount = json['merchandise_amount']?.toString() ?? '0.00';
    cashbackRate = json['cashback_rate']?.toString() ?? '0.05';
    cashbackAmount = json['cashback_amount']?.toString() ?? '0.00';
    status = json['status'];
    availableAt = json['available_at'];
    redeemedAt = json['redeemed_at'];
    redeemedOrderId = json['redeemed_order_id'];
    description = json['description'];
    createdAt = json['created_at'];
  }
}