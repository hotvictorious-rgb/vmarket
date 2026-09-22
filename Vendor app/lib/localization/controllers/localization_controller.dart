import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_vendor_app/localization/models/language_model.dart';
import 'package:sixvalley_vendor_app/main.dart';
import 'package:sixvalley_vendor_app/services/storage_service.dart';
import 'package:sixvalley_vendor_app/utill/app_constants.dart';

class LocalizationController extends ChangeNotifier {
  final StorageService storageService;

  LocalizationController({required this.storageService}) {
    _loadCurrentLanguage();
  }

  int? _languageIndex;
  Locale _locale = Locale(AppConstants.languages[0].languageCode!, AppConstants.languages[0].countryCode);
  bool _isLtr = true;
  Locale get locale => _locale;
  bool get isLtr => _isLtr;
  int? get languageIndex => _languageIndex;
  List<LanguageModel> _languages = [];
  List<LanguageModel> get languages => _languages;


  void setLanguage(Locale locale, int index) {
    _locale = locale;
    _languageIndex = index;
    if(_locale.languageCode == 'ar') {
      _isLtr = false;
    }else {
      _isLtr = true;
    }
    _saveLanguage(_locale);
    Provider.of<AuthController>(Get.context!, listen: false).setCurrentLanguage(locale.countryCode == 'US'?'en': _locale.countryCode!.toLowerCase());
    notifyListeners();
  }

  Future<void> _loadCurrentLanguage() async {
    _locale = Locale(storageService.getString(AppConstants.languageCode) ?? AppConstants.languages[0].languageCode!,
        storageService.getString(AppConstants.countryCode) ?? AppConstants.languages[0].countryCode);
    for(int index=0; index<AppConstants.languages.length; index++) {
      if(AppConstants.languages[index].languageCode == _locale.languageCode) {
        _languageIndex = index;
        break;
      }
    }
    _isLtr = _locale.languageCode != 'ar';
    _languages = [];
    _languages.addAll(AppConstants.languages);
    notifyListeners();
  }

  Future<void> _saveLanguage(Locale locale) async {
    await storageService.setString(AppConstants.languageCode, locale.languageCode);
    await storageService.setString(AppConstants.countryCode, locale.countryCode!);
  }

  String? getCurrentLanguage() {
    return storageService.getString(AppConstants.countryCode == 'US'? 'en' : AppConstants.countryCode) ?? "en";
  }


}