import 'package:sixvalley_delivery_boy/data/models/image_full_url.dart';

class UserInfoModel {
  int? id;
  String? fName;
  String? lName;
  String? phone;
  String? email;
  String? image;
  ImageFullUrl? imageFullUrl;
  String? identityNumber;
  String? identityType;
  List<dynamic>? identityImage;
  List<ImageFullUrl>? identityImageFullUrl;
  int? isActive;
  String? createdAt;
  String? updatedAt;
  double? withdrawableBalance;
  double? currentBalance;
  double? cashInHand;
  double? pendingWithdraw;
  double? totalWithdraw;
  double? totalEarn;
  int? completedDelivery;
  int? totalDelivery;
  int? pauseDelivery;
  int? pendingDelivery;
  double? totalDeposit;
  String? countryCode;
  String? address;
  String? bankName;
  String? branch;
  String? accountNo;
  String? holderName;

  UserInfoModel(
      {this.id,
        this.fName,
        this.lName,
        this.phone,
        this.email,
        this.image,
        this.imageFullUrl,
        this.identityNumber,
        this.identityType,
        this.identityImage,
        this.identityImageFullUrl,
        this.isActive,
        this.createdAt,
        this.updatedAt,
        this.withdrawableBalance,
        this.currentBalance,
        this.cashInHand,
        this.pendingWithdraw,
        this.totalWithdraw,
        this.totalEarn,
        this.completedDelivery,
        this.totalDelivery,
        this.pauseDelivery,
        this.pendingDelivery,
        this.totalDeposit,
        this.address,
        this.countryCode,
        this.bankName,
        this.branch,
        this.accountNo,
        this.holderName,
      });

  UserInfoModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    fName = json['f_name'];
    lName = json['l_name'];
    phone = json['phone'];
    email = json['email'];
    image = json['image'];
    isActive = int.parse(json['is_online'].toString());
    identityNumber = json['identity_number'];
    identityType = json['identity_type'];
    if(json['identity_image'] is !String){
      identityImage = [];
      //identityImage = jsonDecode(json['identity_image']);
    }
    if (json['identity_images_full_url'] != null) {
      identityImageFullUrl = <ImageFullUrl>[];
      json['identity_images_full_url'].forEach((v) {
        identityImageFullUrl!.add(ImageFullUrl.fromJson(v));
      });
    } else {
      identityImageFullUrl = [];
    }
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    withdrawableBalance = json['withdrawable_balance'] != null ? double.tryParse(json['withdrawable_balance'].toString()) ?? 0.0 : 0.0;
    currentBalance = json['current_balance'] != null ? double.tryParse(json['current_balance'].toString()) ?? 0.0 : 0.0;
    cashInHand = json['cash_in_hand'] != null ? double.tryParse(json['cash_in_hand'].toString()) ?? 0.0 : 0.0;
    pendingWithdraw = json['pending_withdraw'] != null ? double.tryParse(json['pending_withdraw'].toString()) ?? 0.0 : 0.0;
    totalWithdraw = json['total_withdraw'] != null ? double.tryParse(json['total_withdraw'].toString()) ?? 0.0 : 0.0;
    totalEarn = json['total_earn'] != null ? double.tryParse(json['total_earn'].toString()) ?? 0.0 : 0.0;
    completedDelivery = json['completed_delivery'] != null ? int.tryParse(json['completed_delivery'].toString()) ?? 0 : 0;
    totalDelivery = json['total_delivery'] != null ? int.tryParse(json['total_delivery'].toString()) ?? 0 : 0;
    pauseDelivery = json['pause_delivery'] != null ? int.tryParse(json['pause_delivery'].toString()) ?? 0 : 0;
    pendingDelivery = json['pending_delivery'] != null ? int.tryParse(json['pending_delivery'].toString()) ?? 0 : 0;
    totalDeposit = json['total_deposit'] != null ? double.tryParse(json['total_deposit'].toString()) ?? 0.0 : 0.0;
    countryCode = json['country_code'];
    if(json['address'] != null){
      address = json['address'];
    }else{
      address = '';
    }

    if(json['bank_name'] != null){
      bankName = json['bank_name'];
    }
    if(json['branch'] != null){
      branch = json['branch'];
    }

    if(json['account_no'] != null){
      accountNo = json['account_no'];
    }
    if(json['holder_name'] != null){
      holderName = json['holder_name'];
    }

    imageFullUrl = json['image_full_url'] != null
        ? ImageFullUrl.fromJson(json['image_full_url'])
        : null;

  }

}
