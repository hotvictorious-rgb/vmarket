import 'dart:async';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/services/storage_service.dart';
import 'package:sixvalley_delivery_boy/data/api/api_client.dart';
import 'package:sixvalley_delivery_boy/features/auth/domain/repositories/auth_repository_interface.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';

class AuthRepository implements AuthRepositoryInterface {
  final ApiClient apiClient;
  final StorageService storageService;

  AuthRepository({required this.apiClient, required this.storageService});

  @override
  Future<Response> login(String countryCode, String phone, String password) async {
    return await apiClient.postData(AppConstants.loginUri,
        {"country_code": '+'+countryCode ,"phone": phone, "password": password});
  }

  @override
  Future<Response> setLanguageCode(String languageCode) async {
    return await apiClient.postData(AppConstants.setCurrentLanguageUri,
        {"current_language": languageCode, '_method' : 'put' });
  }

  @override
  Future<bool> saveUserToken(String token) async {
    apiClient.token = token;
    apiClient.updateHeader(token, storageService.getString(AppConstants.languageCode));
    await storageService.setString(AppConstants.token, token);
    return true;
  }

  @override
  Future<Response> updateToken() async {
    String? _deviceToken;
    if (GetPlatform.isIOS) {
      NotificationSettings settings = await FirebaseMessaging.instance.requestPermission(
        alert: true, announcement: false, badge: true, carPlay: false,
        criticalAlert: false, provisional: false, sound: true,
      );
      if(settings.authorizationStatus == AuthorizationStatus.authorized) {
        _deviceToken = await _saveDeviceToken();
        debugPrint('=========>Device Token ======$_deviceToken');
      }
    }else {
      _deviceToken = await _saveDeviceToken();
      debugPrint('=========>Device Token ======$_deviceToken');
    }
    if(!GetPlatform.isWeb) {
      FirebaseMessaging.instance.subscribeToTopic('six_valley_delivery');
    }

    return await apiClient.postData(AppConstants.tokenUri,
        {"_method": "put", "fcm_token": _deviceToken},
      headers:  {
        'Content-Type': 'application/json; charset=UTF-8',
        'Authorization': 'Bearer ${getUserToken()}'
      },
    );
  }

  Future<String?> _saveDeviceToken() async {
    String? _deviceToken = '';
    if(!GetPlatform.isWeb) {
      _deviceToken = await (FirebaseMessaging.instance.getToken());
    }
    return _deviceToken;
  }

  @override
  String getUserToken() {
    return storageService.getString(AppConstants.token) ?? "";
  }

  @override
  bool isLoggedIn() {
    return (storageService.getString(AppConstants.token) ?? "").isNotEmpty;
  }

  @override
  Future<bool> clearSharedData() async {
    if(!GetPlatform.isWeb) {
      apiClient.postData(AppConstants.tokenUri, {"_method": "put", "fcm_token": 'no'});
    }
    await storageService.remove(AppConstants.token);
    apiClient.token = null;
    return true;
  }

  @override
  Future<void> saveUserCredentials(String countryCode, String number, String password) async {
    // [AI] Only persist user email/phone and country code. Never write raw password to storage.
    await storageService.setString(AppConstants.userEmail, number);
    await storageService.setString(AppConstants.userCountryCode, countryCode);
  }

  @override
  String getUserEmail() {
    return storageService.getString(AppConstants.userEmail) ?? "";
  }

  @override
  String getUserPassword() {
    // [AI] Raw password persistence eradicated. Always return empty string.
    return "";
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

  Future<bool> clearUserEmailAndPassword() async {
    await storageService.remove(AppConstants.userPassword);
    await storageService.remove(AppConstants.userEmail);
    return true;
  }

  @override
  Future<bool> clearUserCredentials() async{
    await storageService.remove(AppConstants.userPassword);
    await storageService.remove(AppConstants.userCountryCode);
    await storageService.remove(AppConstants.userEmail);
    return true;
  }

  @override
  Future<Response> forgotPassword(String? countryCode ,String? phone) async {
    Response _response = await apiClient.postData(AppConstants.forgotPassword,
        {
          'identity': phone
        });
    return _response;
  }

  @override
  Future<Response> verifyOtp(String countryCode ,String? phone) async {
    Response _response = await apiClient.postData(AppConstants.verifyOtp,
        {
          'otp' : countryCode,
          'identity': phone
        });
    return _response;
  }

  @override
  String getUserCountryCode() {
    return storageService.getString(AppConstants.userCountryCode) ?? "";
  }
}

