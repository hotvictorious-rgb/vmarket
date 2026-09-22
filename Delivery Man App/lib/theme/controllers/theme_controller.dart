import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/services/storage_service.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';

class ThemeController extends GetxController implements GetxService {
  final StorageService storageService;
  ThemeController({required this.storageService}) {
    _loadCurrentTheme();
  }

  bool _darkTheme = false;
  bool get darkTheme => _darkTheme;

  void toggleTheme() {
    _darkTheme = !_darkTheme;
    storageService.setBool(AppConstants.theme, _darkTheme);
    update();
  }

  void _loadCurrentTheme() {
    _darkTheme = storageService.getBool(AppConstants.theme) ?? false;
    update();
  }
}
