import 'package:sixvalley_delivery_boy/data/models/image_full_url.dart';

class MessageModel {
  int? totalSize;
  int? limit;
  int? offset;
  List<Message>? message;

  MessageModel({this.totalSize, this.limit, this.offset, this.message});

  MessageModel.fromJson(Map<String, dynamic> json) {
    totalSize = int.tryParse('${json['total_size']}');
    limit = int.tryParse('${json['limit']}');
    offset = int.tryParse('${json['offset']}');
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
  String? message;
  bool? sentByCustomer;
  bool? sentBySeller;
  bool? sentByAdmin;
  bool? seenByDeliveryMan;
  bool? sentByDeliveryMan;
  String? createdAt;
  Customer? customer;
  SellerInfo? sellerInfo;
  List<Attachment>? attachment;

  Message(
      {this.id,
        this.message,
        this.sentByCustomer,
        this.sentBySeller,
        this.sentByAdmin,
        this.seenByDeliveryMan,
        this.sentByDeliveryMan,
        this.createdAt,
        this.customer,
        this.sellerInfo,
        this.attachment
      });

  Message.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    message = json['message'];
    sentByCustomer = json['sent_by_customer'] ?? false;
    sentBySeller = json['sent_by_seller'] ?? false;
    sentByAdmin = json['sent_by_admin'] ?? false;
    seenByDeliveryMan = json['seen_by_delivery_man']??false;
    createdAt = json['created_at'];
    customer = json['customer'] != null ? Customer.fromJson(json['customer']) : null;
    sentByDeliveryMan = json['sent_by_delivery_man']??false;
    sellerInfo = json['seller_info'] != null ? SellerInfo.fromJson(json['seller_info']) : null;
    if (json['attachment'] != null) {
      attachment = <Attachment>[];
      json['attachment'].forEach((v) {
        attachment!.add(Attachment.fromJson(v));
      });
    }
  }
}

class Customer {
  int? id;
  String? fName;
  String? lName;
  String? phone;
  String? image;
  ImageFullUrl? imageFullUrl;
  String? email;


  Customer(
      {this.id,
        this.fName,
        this.lName,
        this.phone,
        this.image,
        this.imageFullUrl,
        this.email,
       });

  Customer.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    fName = json['f_name'];
    lName = json['l_name'];
    phone = json['phone'];
    image = json['image'];
    imageFullUrl = json['image_full_url'] != null
      ? ImageFullUrl.fromJson(json['image_full_url'])
      : null;
    email = json['email'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['f_name'] = fName;
    data['l_name'] = lName;
    data['phone'] = phone;
    data['image'] = image;
    data['email'] = email;

    return data;
  }
}

class SellerInfo {
  List<Shops>? shops;

  SellerInfo(
      {this.shops});

  SellerInfo.fromJson(Map<String, dynamic> json) {
    if (json['shops'] != null) {
      shops = <Shops>[];
      json['shops'].forEach((v) {
        shops!.add(Shops.fromJson(v));
      });
    }
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    if (shops != null) {
      data['shops'] = shops!.map((v) => v.toJson()).toList();
    }
    return data;
  }
}

class Shops {
  int? id;
  int? sellerId;
  String? name;
  String? image;
  ImageFullUrl? imageFullUrl;

  Shops(
      {this.id,
        this.sellerId,
        this.name,
        this.image,
        this.imageFullUrl
       });

  Shops.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    if(json['seller_id'] != null){
      sellerId = int.parse(json['seller_id'].toString());
    }
    name = json['name'];
    image = json['image'];
    imageFullUrl = json['image_full_url'] != null
      ? ImageFullUrl.fromJson(json['image_full_url'])
      : null;
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['seller_id'] = sellerId;
    data['name'] = name;
    data['image'] = image;
    return data;
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