
import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/data/api/api_checker.dart';
import 'package:sixvalley_delivery_boy/features/maintenance/maintenance_screen.dart';
import 'package:sixvalley_delivery_boy/features/splash/domain/models/business_pages_model.dart';
import 'package:sixvalley_delivery_boy/features/splash/domain/models/config_model.dart';
import 'package:sixvalley_delivery_boy/features/splash/domain/services/splash_service_interface.dart';

class SplashController extends GetxController implements GetxService {
  final SplashServiceInterface splashServiceInterface;
  SplashController({required this.splashServiceInterface});

  ConfigModel? _configModel;
  BaseUrls? _baseUrls;
  BaseUrls? get baseUrls => _baseUrls;
  bool _firstTimeConnectionCheck = true;
  CurrencyList? _myCurrency;
  CurrencyList? _usdCurrency;
  CurrencyList? _defaultCurrency;
  CurrencyList? get myCurrency => _myCurrency;
  CurrencyList? get usdCurrency => _usdCurrency;
  CurrencyList? get defaultCurrency => _defaultCurrency;
  int? _currencyIndex;
  int? get currencyIndex => _currencyIndex;

  ConfigModel? get configModel => _configModel;
  DateTime get currentTime => DateTime.now();
  bool get firstTimeConnectionCheck => _firstTimeConnectionCheck;

  List<BusinessPageModel>? _defaultBusinessPages;
  List<BusinessPageModel>? get defaultBusinessPages => _defaultBusinessPages;



  Future<bool> getConfigData() async {
    Response response = await splashServiceInterface.getConfigData();
    bool _isSuccess = false;
    if(response.statusCode == 200) {
      _configModel = ConfigModel.fromJson(response.body);
      _baseUrls = ConfigModel.fromJson(response.body).baseUrls;
      String? _currencyCode = splashServiceInterface.getCurrency();
      for(CurrencyList currencyList in _configModel!.currencyList!) {
        if(currencyList.id == _configModel!.systemDefaultCurrency) {
          if(_currencyCode == null || _currencyCode.isEmpty) {
            _currencyCode = currencyList.code;
          }
          _defaultCurrency = currencyList;
        }
        if(currencyList.code == 'USD') {
          _usdCurrency = currencyList;
        }
      }
      getCurrencyData(_currencyCode);
      if(_configModel?.maintenanceModeData?.maintenanceStatus == 0){
        if(_configModel?.maintenanceModeData?.selectedMaintenanceSystem?.deliverymanApp == 1 ) {
          if(_configModel?.maintenanceModeData?.maintenanceTypeAndDuration?.maintenanceDuration == 'customize'){

            DateTime now = DateTime.now();
            DateTime specifiedDateTime = DateTime.parse(_configModel!.maintenanceModeData!.maintenanceTypeAndDuration!.startDate!);

            Duration difference = specifiedDateTime.difference(now);

            if(difference.inMinutes > 0 && (difference.inMinutes < 60 || difference.inMinutes == 60)){
              _startTimer(specifiedDateTime);
            }

          }
        }
      }
      _isSuccess = true;
    }else {
      ApiChecker.checkApi(response);
      _isSuccess = false;
    }
    update();
    return _isSuccess;
  }

  Future<void> getBusinessPagesList(String type) async {
    Response response = await splashServiceInterface.getBusinessPages(type);

    if (response.statusCode == 200) {
      if(type == 'default') {
        _defaultBusinessPages = [];
        response.body.forEach((data) {_defaultBusinessPages?.add(BusinessPageModel.fromJson(data));});
      }
    } else {
      ApiChecker.checkApi(response);
    }

    update();
  }


  void getCurrencyData(String? currencyCode) {
    for (var currency in _configModel!.currencyList!) {
      if(currencyCode == currency.code) {
        _myCurrency = currency;
        _currencyIndex = _configModel!.currencyList!.indexOf(currency);
        return;
      }
    }
  }




  void setCurrency(int index) {
    splashServiceInterface.setCurrency(_configModel!.currencyList![index].code!);
    getCurrencyData(_configModel!.currencyList![index].code);
    update();
  }

  Future<bool> initSharedData() {
    return splashServiceInterface.initSharedData();
  }

  Future<bool> removeSharedData() {
    return splashServiceInterface.removeSharedData();
  }

  void setFirstTimeConnectionCheck(bool isChecked) {
    _firstTimeConnectionCheck = isChecked;
  }

  bool? showIntro() {
    return splashServiceInterface.showIntro();

  }
  bool? notificationSound() {
    return splashServiceInterface.notificationSound();


  }
  void disableIntro() {
    splashServiceInterface.disableIntro();
  }
  void disableNotification() {
    splashServiceInterface.disableNotification();
    update();
  }
  void enableNotification() {
    splashServiceInterface.enableNotification();
    update();
  }


  void _startTimer (DateTime startTime){
    Timer.periodic(const Duration(seconds: 30), (Timer timer) {

      DateTime now = DateTime.now();

      if (now.isAfter(startTime) || now.isAtSameMomentAs(startTime)) {
        timer.cancel();
        Navigator.of(Get.context!).pushReplacement(MaterialPageRoute(
          builder: (_) => const MaintenanceScreen(),
          settings: const RouteSettings(name: 'MaintenanceScreen'),
        ));
      }

    });
  }
}