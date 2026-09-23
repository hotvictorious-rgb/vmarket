import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/services/storage_service.dart';
import 'package:sixvalley_delivery_boy/data/api/api_client.dart';
import 'package:sixvalley_delivery_boy/features/splash/domain/repositories/splash_repository_interface.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';

class SplashRepository implements SplashRepositoryInterface{
  ApiClient apiClient;
  final StorageService storageService;
  SplashRepository({required this.storageService, required this.apiClient});

  @override
  Future<Response> getConfigData() async {
    Response _response = await apiClient.getData(AppConstants.configUri);
    return _response;
  }

  @override
  Future<Response> getBusinessPages(String type) async {
    final response = await apiClient.getData(AppConstants.businessPagesUri+type);
    return response;
  }

  @override
  Future<bool> initSharedData() async {
    if(!storageService.containsKey(AppConstants.theme)) {
      await storageService.setBool(AppConstants.theme, false);
    }
    if(!storageService.containsKey(AppConstants.countryCode)) {
      await storageService.setString(AppConstants.countryCode, AppConstants.defaultCountryCode);
    }
    if(!storageService.containsKey(AppConstants.languageCode)) {
      await storageService.setString(AppConstants.languageCode, AppConstants.defaultLanguageCode);
    }
    if(!storageService.containsKey(AppConstants.intro)) {
      await storageService.setBool(AppConstants.intro, true);
    }

    return true;
  }

  @override
  String getCurrency() {
    return storageService.getString(AppConstants.currency) ?? '';
  }

  @override
  void setCurrency(String currencyCode) {
    storageService.setString(AppConstants.currency, currencyCode);
  }

  @override
  Future<bool> removeSharedData() async {
    await storageService.remove(AppConstants.token);
    return true;
  }

  @override
  void disableIntro() {
    storageService.setBool(AppConstants.intro, false);
  }

  @override
  bool? showIntro() {
    if(!storageService.containsKey(AppConstants.intro)) {
      storageService.setBool(AppConstants.intro, true);
    }
    return storageService.getBool(AppConstants.intro);

  }

  @override
  void disableNotification() {
    storageService.setBool(AppConstants.notificationSound, false);
  }

  @override
  void enableNotification() {
    storageService.setBool(AppConstants.notificationSound, true);
  }

  @override
  bool? notificationSound() {
    if(!storageService.containsKey(AppConstants.notificationSound)) {
      storageService.setBool(AppConstants.notificationSound, true);
    }
    return storageService.getBool(AppConstants.notificationSound);
  }

  @override
  Future add(value) {
    // TODO: implement add
    throw UnimplementedError();
  }

  @override
  Future delete(int? id) {
    // TODO: implement delete
    throw UnimplementedError();
  }

  @override
  Future get(int? id) {
    // TODO: implement get
    throw UnimplementedError();
  }

  @override
  Future getList() {
    // TODO: implement getList
    throw UnimplementedError();
  }

  @override
  Future update(Map<String, dynamic> body, int? id) {
    // TODO: implement update
    throw UnimplementedError();
  }

}