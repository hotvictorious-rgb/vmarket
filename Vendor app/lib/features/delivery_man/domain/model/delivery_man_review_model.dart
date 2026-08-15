import 'package:sixvalley_vendor_app/data/model/image_full_url.dart';

class DeliveryManReviewModel {
  int? totalSize;
  String? limit;
  String? offset;
  String? averageRating;
  List<DeliveryManReview>? reviews;

  DeliveryManReviewModel(
      {this.totalSize,
        this.limit,
        this.offset,
        this.averageRating,
        this.reviews});

  DeliveryManReviewModel.fromJson(Map<String, dynamic> json) {
    totalSize = json['total_size'];
    limit = json['limit'];
    offset = json['offset'];
    averageRating = json['average_rating'];
    if (json['reviews'] != null) {
      reviews = <DeliveryManReview>[];
      json['reviews'].forEach((v) {
        reviews!.add(DeliveryManReview.fromJson(v));
      });
    }
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['total_size'] = totalSize;
    data['limit'] = limit;
    data['offset'] = offset;
    data['average_rating'] = averageRating;
    if (reviews != null) {
      data['reviews'] = reviews!.map((v) => v.toJson()).toList();
    }
    return data;
  }
}

class DeliveryManReview {
  int? id;
  int? productId;
  int? customerId;
  int? deliveryManId;
  int? orderId;
  String? comment;
  double? rating;
  int? status;
  int? isSaved;
  String? createdAt;
  String? updatedAt;
  Customer? customer;

  DeliveryManReview(
      {this.id,
        this.productId,
        this.customerId,
        this.deliveryManId,
        this.orderId,
        this.comment,
        this.rating,
        this.status,
        this.isSaved,
        this.createdAt,
        this.updatedAt,
        this.customer
      });

  // [AI] Safely deserialize DeliveryManReview in Vendor App with type safety
  DeliveryManReview.fromJson(Map<String, dynamic> json) {
    id = int.tryParse(json['id']?.toString() ?? '');
    productId = int.tryParse(json['product_id']?.toString() ?? '');
    customerId = int.tryParse(json['customer_id']?.toString() ?? '');
    deliveryManId = int.tryParse(json['delivery_man_id']?.toString() ?? '');
    orderId = int.tryParse(json['order_id']?.toString() ?? '');
    comment = json['comment'];
    if(json['rating'] != null){
      rating = double.tryParse(json['rating'].toString()) ?? 0.0;
    }else{
      rating = 0;
    }

    status = int.tryParse(json['status']?.toString() ?? '');
    isSaved = (json['is_saved'] == true || json['is_saved'] == 1 || json['is_saved'] == '1') ? 1 : 0;
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    customer = json['customer'] != null
        ? Customer.fromJson(json['customer'])
        : null;
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['product_id'] = productId;
    data['customer_id'] = customerId;
    data['delivery_man_id'] = deliveryManId;
    data['order_id'] = orderId;
    data['comment'] = comment;
    data['rating'] = rating;
    data['status'] = status;
    data['is_saved'] = isSaved;
    data['created_at'] = createdAt;
    data['updated_at'] = updatedAt;
    if (customer != null) {
      data['customer'] = customer!.toJson();
    }
    return data;
  }
}

class Customer {

  String? fName;
  String? lName;
  String? image;
  ImageFullUrl? imageFullUrl;


  Customer(
      {
        this.fName,
        this.lName,
        this.image,
        this.imageFullUrl,
       });

  Customer.fromJson(Map<String, dynamic> json) {
    fName = json['f_name'];
    lName = json['l_name'];
    image = json['image'];
    imageFullUrl = json['image_full_url'] != null
      ? ImageFullUrl.fromJson(json['image_full_url'])
      : null;
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};

    data['f_name'] = fName;
    data['l_name'] = lName;
    data['image'] = image;
    return data;
  }
}
