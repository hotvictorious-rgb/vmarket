import 'package:sixvalley_vendor_app/features/splash/domain/repositories/splash_repository_interface.dart';
import 'package:sixvalley_vendor_app/features/splash/domain/services/splash_service_interface.dart';

class SplashService implements SplashServiceInterface{
  final SplashRepositoryInterface splashRepoInterface;
  SplashService({required this.splashRepoInterface});

  @override
  Future getConfig() {
   return splashRepoInterface.getConfig();
  }

  @override
  Future getBusinessPages(String type) {
    return splashRepoInterface.getBusinessPages(type);
  }

  @override
  String getCurrency() {
    return splashRepoInterface.getCurrency();
  }

  @override
  void initSharedData() {
    return splashRepoInterface.initSharedData();
  }

  @override
  void setCurrency(String currencyCode) {
   return splashRepoInterface.setCurrency(currencyCode);
  }
}