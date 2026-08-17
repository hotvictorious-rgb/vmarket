
class MessageBody {
  int? _id;
  String? _message;
  int? _orderId;

  MessageBody({int? id, String? message, int? orderId}) {
    _id = id;
    _message = message;
    _orderId = orderId;
  }

  int? get id => _id;
  String? get message => _message;
  int? get orderId => _orderId;

  MessageBody.fromJson(Map<String, dynamic> json) {
    _id = json['id'];
    _message = json['message'];
    _orderId = json['order_id'] != null ? int.tryParse('${json['order_id']}') : null;
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = _id;
    data['message'] = _message;
    if (_orderId != null) {
      data['order_id'] = _orderId;
    }
    return data;
  }
}
