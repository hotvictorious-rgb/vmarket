class MessageBody {
  int? _userId;
  String? _message;
  int? _orderId;

  MessageBody({int? sellerId, String? message, int? orderId}) {
    _userId = sellerId;
    _message = message;
    _orderId = orderId;
  }

  int? get userId => _userId;
  String? get message => _message;
  int? get orderId => _orderId;

  MessageBody.fromJson(Map<String, dynamic> json) {
    _userId = json['id'];
    _message = json['message'];
    _orderId = json['order_id'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = _userId;
    data['message'] = _message;
    if (_orderId != null) {
      data['order_id'] = _orderId;
    }
    return data;
  }
}
