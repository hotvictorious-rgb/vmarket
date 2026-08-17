import 'package:sixvalley_vendor_app/features/chat/domain/models/chat_model.dart';

class MessageModel {
  int? totalSize;
  int? limit;
  int? offset;
  bool? isActive;
  int? orderId;
  List<Message>? message;

  MessageModel({this.totalSize, this.limit, this.offset, this.isActive, this.orderId, this.message});

  MessageModel.fromJson(Map<String, dynamic> json) {
    totalSize = int.tryParse('${json['total_size']}');
    limit = int.tryParse('${json['limit']}');
    offset = int.tryParse('${json['offset']}');
    isActive = json['is_active'] ?? true;
    orderId = json['order_id'] != null ? int.tryParse('${json['order_id']}') : null;
    if (json['message'] != null) {
      message = <Message>[];
      json['message'].forEach((v) {
        message!.add(Message.fromJson(v));
      });
    }
  }

}

class Message {
  int? id;
  int? userId;
  int? deliveryManId;
  int? orderId;
  bool? isActive;
  String? message;
  bool? sentByCustomer;
  bool? sentByDeliveryMan;
  bool? sentBySeller;
  bool? seenBySeller;
  bool? seenByCustomer;
  bool? seenByDeliveryMan;
  String? createdAt;
  String? updatedAt;
  Customer? customer;
  DeliveryMan? deliveryMan;
  List<Attachment>? attachment;

  Message(
      {this.id,
        this.userId,
        this.deliveryManId,
        this.orderId,
        this.isActive,
        this.message,
        this.sentByCustomer,
        this.sentByDeliveryMan,
        this.sentBySeller,
        this.seenBySeller,
        this.seenByCustomer,
        this.seenByDeliveryMan,
        this.createdAt,
        this.updatedAt,
        this.customer,
      this.deliveryMan,
        this.attachment
      });

  Message.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    userId = json['user_id'];
    orderId = json['order_id'] != null ? int.tryParse('${json['order_id']}') : null;
    isActive = json['is_active'] ?? true;
    if(json['delivery_man_id'] != null){
      deliveryManId = int.parse(json['delivery_man_id'].toString());
    }

    message = json['message'];
    sentByCustomer = json['sent_by_customer'];
    sentByDeliveryMan = json['sent_by_delivery_man']??false;
    sentBySeller = json['sent_by_seller'];
    seenBySeller = json['seen_by_seller'];
    seenByCustomer = json['seen_by_customer'];
    seenByDeliveryMan = json['seen_by_delivery_man'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    customer = json['customer'] != null ? Customer.fromJson(json['customer']) : null;
    deliveryMan = json['delivery_man'] != null ? DeliveryMan.fromJson(json['delivery_man']) : null;
    if (json['attachment'] != null) {
      attachment = <Attachment>[];
      json['attachment'].forEach((v) {
        if(v['size'] != null){
          attachment!.add(Attachment.fromJson(v));
        }
      });
    }
  }
}


class Attachment {
  String? type;
  String? key;
  String? path;
  String? size;

  Attachment({this.type, this.key, this.path, this.size});

  Attachment.fromJson(Map<String, dynamic> json) {
    type = json['type'];
    key = json['key'];
    path = json['path'];
    size = json['size'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['type'] = type;
    data['key'] = key;
    data['path'] = path;
    data['size'] = size;
    return data;
  }
}