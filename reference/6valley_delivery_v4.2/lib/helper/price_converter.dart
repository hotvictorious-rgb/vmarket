import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/splash/controllers/splash_controller.dart';

class PriceConverter {

  static String convertPrice(double? price, {double? discount, String? discountType,
    int asFixed = 2}) {
    if(discount != null && discountType != null){
      if(discountType == 'amount') {
        price = price! - discount;
      }else if(discountType == 'percent') {
        price = price! - ((discount / 100) * price);
      }
    }
    final bool _singleCurrency = Get.find<SplashController>().configModel!.currencyModel == 'single_currency';
    bool inRight = Get.find<SplashController>().configModel!.currencySymbolPosition == 'right';


    return '${inRight ? '' : Get.find<SplashController>().myCurrency!.symbol} '
      '${(_singleCurrency ? price: price! * Get.find<SplashController>().myCurrency!.exchangeRate!
      * (1/Get.find<SplashController>().usdCurrency!.exchangeRate!))!.toStringAsFixed(Get.find<SplashController>().configModel!.decimalPointSetting??1).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (Match m) => '${m[1]},')} ${inRight ? Get.find<SplashController>().myCurrency!.symbol : ''}';
  }

  static String convertPriceWithoutSymbol(BuildContext context, double? price, {double? discount, String? discountType}) {
    if(discount != null && discountType != null){
      if(discountType == 'amount' || discountType == 'flat') {
        price = price! - discount;
      }else if(discountType == 'percent' || discountType == 'percentage') {
        price = price! - ((discount / 100) * price);
      }
    }
    final splashProvider = Get.find<SplashController>();
    bool singleCurrency = splashProvider.configModel!.currencyModel == 'single_currency';

    return (singleCurrency? price : price!
        * splashProvider.myCurrency!.exchangeRate!
        * (1 / splashProvider.usdCurrency!.exchangeRate!))!.toStringAsFixed(splashProvider.configModel!.decimalPointSettings!);
  }

  static double convertWithDiscount(double price, double discount, String discountType) {
    if(discountType == 'amount') {
      price = price - discount;
    }else if(discountType == 'percent') {
      price = price - ((discount / 100) * price);
    }
    return price;
  }

  static double calculation(double amount, double discount, String type, int quantity) {
    double calculatedAmount = 0;
    if(type == 'amount') {
      calculatedAmount = discount * quantity;
    }else if(type == 'percent') {
      calculatedAmount = (discount / 100) * (amount * quantity);
    }
    return calculatedAmount;
  }

  static String percentageCalculation(String price, String discount, String discountType) {
    return '$discount${discountType == 'percent' ? '%' : Get.find<SplashController>().myCurrency!.symbol} OFF';
  }
}