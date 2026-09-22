import 'package:flutter/foundation.dart';
import 'package:sixvalley_vendor_app/services/storage_service.dart';
import 'package:sixvalley_vendor_app/utill/app_constants.dart';

class ThemeController with ChangeNotifier {
  final StorageService storageService;
  ThemeController({required this.storageService}) {
    _loadCurrentTheme();
  }

  bool _darkTheme = true;
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
}
