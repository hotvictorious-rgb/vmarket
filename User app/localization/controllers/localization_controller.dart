import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/data/datasource/remote/dio/dio_client.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/services/storage_service.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'package:provider/provider.dart';

class LocalizationController extends ChangeNotifier {
  final StorageService storageService;
  final DioClient? dioClient;

  LocalizationController({required this.storageService, required this.dioClient}) {
    _loadCurrentLanguage();
  }

  Locale _locale = Locale(AppConstants.languages[0].languageCode!, AppConstants.languages[0].countryCode);
  bool _isLtr = true;
  int? _languageIndex;

  Locale get locale => _locale;
  bool get isLtr => _isLtr;
  int? get languageIndex => _languageIndex;

  void setLanguage(Locale locale) {
    _locale = locale;
    _isLtr = _locale.languageCode != 'ar';
    dioClient!.updateHeader(null, locale.countryCode);
    Provider.of<AuthController>(Get.context!, listen: false).setCurrentLanguage(locale.countryCode == 'US'?'en': _locale.countryCode!.toLowerCase());
    for(int index=0; index<AppConstants.languages.length; index++) {
      if(AppConstants.languages[index].languageCode == locale.languageCode) {
        _languageIndex = index;
        break;
      }
    }
    _saveLanguage(_locale);
    notifyListeners();
  }

  Future<void> _loadCurrentLanguage() async {
    _locale = Locale(storageService.getString(AppConstants.languageCode) ?? AppConstants.languages[0].languageCode!,
        storageService.getString(AppConstants.countryCode) ?? AppConstants.languages[0].countryCode);
    _isLtr = _locale.languageCode != 'ar';
    for(int index=0; index<AppConstants.languages.length; index++) {
      if(AppConstants.languages[index].languageCode == locale.languageCode) {
        _languageIndex = index;
        break;
      }
    }
    notifyListeners();
  }

  Future<void> _saveLanguage(Locale locale) async {
    await storageService.setString(AppConstants.languageCode, locale.languageCode);
    await storageService.setString(AppConstants.countryCode, locale.countryCode!);
  }


  String? getCurrentLanguage() {
    return storageService.getString(AppConstants.countryCode) ?? "US";
  }
}