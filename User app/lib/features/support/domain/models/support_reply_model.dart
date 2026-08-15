import 'package:flutter_sixvalley_ecommerce/data/model/image_full_url.dart';

class SupportReplyModel {
  int? id;
  String? customerMessage;
  String? adminMessage;
  String? createdAt;
  String? updatedAt;
  List<String>? attachment;
  List<ImageFullUrl>? attachmentFullUrl;
  String? adminId;

  SupportReplyModel(
      {this.id,
        this.customerMessage,
        this.adminMessage,
        this.createdAt,
        this.updatedAt,
        this.attachment,
        this.adminId,
        this.attachmentFullUrl
      });

  SupportReplyModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    customerMessage = json['customer_message'];
    adminMessage = json['admin_message'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    adminId = json['admin_id'].toString();
    // [AI] Safely parse attachment without fragile cast
    if(json['attachment'] != null && json['attachment'] is List){
      attachment = (json['attachment'] as List).map((e) => e.toString()).toList();
    }else{
      attachment = [];
    }
    if (json['attachment_full_url'] != null) {
      attachmentFullUrl = <ImageFullUrl>[];
      json['attachment_full_url'].forEach((v) {
        attachmentFullUrl!.add(ImageFullUrl.fromJson(v));
      });
    }
  }


}
