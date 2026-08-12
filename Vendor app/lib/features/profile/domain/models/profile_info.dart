import 'package:sixvalley_vendor_app/data/model/image_full_url.dart';

class ProfileInfoModel {
  int? id;
  String? fName;
  String? lName;
  String? phone;
  String? image;
  ImageFullUrl? imageFullUrl;
  String? email;
  String? password;
  String? status;
  String? rememberToken;
  String? createdAt;
  String? updatedAt;
  String? bankName;
  String? branch;
  String? accountNo;
  String? holderName;
  String? authToken;
  double? salesCommissionPercentage;
  String? gst;
  int? productCount;
  int? posActive;
  int? ordersCount;
  Wallet? wallet;
  double? minimumOrderAmount;
  double? freeOverDeliveryAmount;
  int? freeOverDeliveryAmountStatus;
  String? nin;
  String? cacNumber;
  String? kycStatus;

  ProfileInfoModel(
      {this.id,
        this.fName,
        this.lName,
        this.phone,
        this.image,
        this.imageFullUrl,
        this.email,
        this.password,
        this.status,
        this.rememberToken,
        this.createdAt,
        this.updatedAt,
        this.bankName,
        this.branch,
        this.accountNo,
        this.holderName,
        this.authToken,
        this.salesCommissionPercentage,
        this.gst,
        this.posActive,
        this.productCount,
        this.ordersCount,
        this.wallet,
        this.minimumOrderAmount,
        this.freeOverDeliveryAmount,
        this.freeOverDeliveryAmountStatus
      });

  ProfileInfoModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    fName = json['f_name'];
    lName = json['l_name'];
    phone = json['phone'];
    image = json['image'];
    email = json['email'];
    password = json['password'];
    status = json['status'];
    rememberToken = json['remember_token'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    bankName = json['bank_name'];
    branch = json['branch'];
    accountNo = json['account_no'];
    holderName = json['holder_name'];
    nin = json['nin'];
    cacNumber = json['cac_number'];
    kycStatus = json['kyc_status'];
    authToken = json['auth_token'];
    if(json['sales_commission_percentage']!=null){
      try{
        salesCommissionPercentage = (json['sales_commission_percentage']).toDouble();
      }catch(e){
        salesCommissionPercentage = double.parse(json['sales_commission_percentage'].toString());
      }


    }
    if(json['gst']!=null){
      gst = json['gst'];
    }
    posActive = int.parse(json['pos_status'].toString());
    productCount = json['product_count'];
    ordersCount = json['orders_count'];
    wallet =
    json['wallet'] != null ? Wallet.fromJson(json['wallet']) : null;
    if(json['minimum_order_amount'] != null){
      try{
        minimumOrderAmount = json['minimum_order_amount'].toDouble();
      }catch(e){
        minimumOrderAmount = double.parse(json['minimum_order_amount'].toString());
      }
    }else{
      minimumOrderAmount = 0;
    }
    if(json['free_delivery_over_amount'] != null){
      try{
        freeOverDeliveryAmount = json['free_delivery_over_amount'].toDouble();
      }catch(e){
        freeOverDeliveryAmount = double.parse(json['free_delivery_over_amount'].toString());
      }
    }else{
      freeOverDeliveryAmount = 0;
    }

    if(json['free_delivery_status'] != null){
      try{
        freeOverDeliveryAmountStatus = json['free_delivery_status'];
      }catch(e){
        freeOverDeliveryAmountStatus = int.parse(json['free_delivery_status'].toString());
      }
    }else{
      freeOverDeliveryAmountStatus = 0;
    }

    imageFullUrl = json['image_full_url'] != null
        ? ImageFullUrl.fromJson(json['image_full_url'])
        : null;
  }


}

class Wallet {
  int? id;
  double? totalEarning;
  double? withdrawn;
  String? createdAt;
  String? updatedAt;
  double? commissionGiven;
  double? pendingWithdraw;
  double? deliveryChargeEarned;
  double? collectedCash;
  double? totalTaxCollected;

  Wallet(
      {this.id,
        this.totalEarning,
        this.withdrawn,
        this.createdAt,
        this.updatedAt,
        this.commissionGiven,
        this.pendingWithdraw,
        this.deliveryChargeEarned,
        this.collectedCash,
        this.totalTaxCollected});

  Wallet.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    totalEarning = json['total_earning'] != null ? double.tryParse(json['total_earning'].toString()) : 0.0;
    withdrawn = json['withdrawn'] != null ? double.tryParse(json['withdrawn'].toString()) : 0.0;
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    commissionGiven = json['commission_given'] != null ? double.tryParse(json['commission_given'].toString()) : 0.0;
    pendingWithdraw = json['pending_withdraw'] != null ? double.tryParse(json['pending_withdraw'].toString()) : 0.0;
    deliveryChargeEarned = json['delivery_charge_earned'] != null ? double.tryParse(json['delivery_charge_earned'].toString()) : 0.0;
    collectedCash = json['collected_cash'] != null ? double.tryParse(json['collected_cash'].toString()) : 0.0;
    totalTaxCollected = json['total_tax_collected'] != null ? double.tryParse(json['total_tax_collected'].toString()) : 0.0;
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['total_earning'] = totalEarning;
    data['withdrawn'] = withdrawn;
    data['created_at'] = createdAt;
    data['updated_at'] = updatedAt;
    data['commission_given'] = commissionGiven;
    data['pending_withdraw'] = pendingWithdraw;
    data['delivery_charge_earned'] = deliveryChargeEarned;
    data['collected_cash'] = collectedCash;
    data['total_tax_collected'] = totalTaxCollected;
    return data;
  }
}
