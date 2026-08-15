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
    if(json['withdrawable_balance'] != null){
      try{
        withdrawableBalance = json['withdrawable_balance'].toDouble();
      }catch(e){
        withdrawableBalance = double.parse(json['withdrawable_balance']);
      }

    }else{
      withdrawableBalance = 0;
    }
    if(json['current_balance'] != null){
      try{
        currentBalance = json['current_balance'].toDouble();
      }catch(e){
        currentBalance = double.parse(json['current_balance']);
      }
    }else{
      currentBalance = 0;
    }
    if(json['cash_in_hand'] != null){
      try{
        cashInHand = json['cash_in_hand'].toDouble();
      }catch(e){
        cashInHand = double.parse(json['cash_in_hand']);
      }
    }else{
      cashInHand = 0;
    }
    if(json['pending_withdraw'] != null){
      try{
        pendingWithdraw = json['pending_withdraw'].toDouble();
      }catch(e){
        pendingWithdraw = double.parse(json['pending_withdraw']);
      }
    }else{
      pendingWithdraw = 0;
    }
    if(json['total_withdraw'] != null){
      try{
        totalWithdraw = json['total_withdraw'].toDouble();
      }catch(e){
        totalWithdraw = double.parse(json['total_withdraw']);
      }
    }else{
      totalWithdraw = 0;
    }

    if(json['total_earn'] != null){
      totalEarn = json['total_earn'].toDouble();
    }else{
      totalEarn = 0;
    }
    if(json['completed_delivery'] != null){
      completedDelivery = json['completed_delivery'];
    }else{
      completedDelivery = 0;
    }
    if(json['total_delivery'] != null){
      totalDelivery = json['total_delivery'];
    }else{
      totalDelivery = 0;
    }
    if(json['pause_delivery'] != null){
      pauseDelivery = json['pause_delivery'];
    }else{
      pauseDelivery = 0;
    }
    if(json['pending_delivery'] != null){
      pendingDelivery = json['pending_delivery'];
    }else{
      pendingDelivery = 0;
    }
    if(json['total_deposit'] != null){
      try{
        totalDeposit = json['total_deposit'].toDouble();
      }catch(e){
        totalDeposit = double.parse(json['total_deposit']);
      }
    }else{
      totalDeposit = 0;
    }
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
