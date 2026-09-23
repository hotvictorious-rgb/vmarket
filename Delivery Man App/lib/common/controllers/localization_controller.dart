import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/services/storage_service.dart';
import 'package:sixvalley_delivery_boy/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';

class LocalizationController extends GetxController implements GetxService {
  final StorageService storageService;

  LocalizationController({required this.storageService}) {
    loadCurrentLanguage();
  }

  Locale _locale = const Locale(AppConstants.defaultLanguageCode, AppConstants.defaultCountryCode);
  bool _isLtr = true;
  Locale get locale => _locale;
  bool get isLtr => _isLtr;

  void setLanguage(Locale locale) {
    Get.updateLocale(locale);
    _locale = locale;
    Get.find<AuthController>().setCurrentLanguage(_locale.countryCode == 'US' ? 'en' : _locale.languageCode);
    if(_locale.languageCode == 'ar') {
      _isLtr = false;
    }else {
      _isLtr = true;
    }
    saveLanguage(_locale);
    update();
  }

  void loadCurrentLanguage() {
    _locale = Locale(storageService.getString(AppConstants.languageCode) ?? AppConstants.defaultLanguageCode,
        storageService.getString(AppConstants.countryCode) ?? AppConstants.defaultCountryCode);
    _isLtr = _locale.languageCode != 'ar';
    update();
  }

  void saveLanguage(Locale locale) async {
    await storageService.setString(AppConstants.languageCode, locale.languageCode);
    await storageService.setString(AppConstants.countryCode, locale.countryCode!);
  }

  String? getCurrentLanguage() {
    return storageService.getString(AppConstants.languageCode) ?? AppConstants.defaultLanguageCode;
  }
}