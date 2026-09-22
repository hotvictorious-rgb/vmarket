
import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/services/storage_service.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';

class ThemeController with ChangeNotifier {
  final StorageService storageService;
  ThemeController({required this.storageService}) {
    _loadCurrentTheme();
  }

  bool _darkTheme = false;
  bool get darkTheme => _darkTheme;

  void toggleTheme() {
    _darkTheme = !_darkTheme;
    storageService.setBool(AppConstants.theme, _darkTheme);
    notifyListeners();
  }

  void _loadCurrentTheme() {
    _darkTheme = storageService.getBool(AppConstants.theme) ?? false;
    notifyListeners();
  }

  Color? selectedPrimaryColor;
  Color? selectedSecondaryColor;



  void setThemeColor({Color? primaryColor, Color? secondaryColor}) {
    selectedPrimaryColor = primaryColor;
    selectedPrimaryColor = secondaryColor;

    notifyListeners();
  }



}
