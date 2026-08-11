import 'dart:async';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:sixvalley_delivery_boy/data/api/api_client.dart';
import 'package:sixvalley_delivery_boy/features/auth/domain/repositories/auth_repository_interface.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';


class AuthRepository implements AuthRepositoryInterface {
  final ApiClient apiClient;
  final SharedPreferences sharedPreferences;
  final FlutterSecureStorage secureStorage;

  static String _token = "";
  static String _userEmail = "";
  static String _userPassword = "";
  static String _userCountryCode = "";

  AuthRepository({required this.apiClient, required this.sharedPreferences, required this.secureStorage}) {
    _initStorage();
  }

  Future<void> _initStorage() async {
    String? sToken = await secureStorage.read(key: AppConstants.token);
    if (sToken == null) {
      String? oldToken = sharedPreferences.getString(AppConstants.token);
      if (oldToken != null) {
        await secureStorage.write(key: AppConstants.token, value: oldToken);
        _token = oldToken;
        await sharedPreferences.remove(AppConstants.token);
      }
    } else {
      _token = sToken;
    }

    String? sEmail = await secureStorage.read(key: AppConstants.userEmail);
    if (sEmail == null) {
      String? oldEmail = sharedPreferences.getString(AppConstants.userEmail);
      if (oldEmail != null) {
        await secureStorage.write(key: AppConstants.userEmail, value: oldEmail);
        _userEmail = oldEmail;
        await sharedPreferences.remove(AppConstants.userEmail);
      }
    } else {
      _userEmail = sEmail;
    }

    String? sPassword = await secureStorage.read(key: AppConstants.userPassword);
    if (sPassword == null) {
      String? oldPassword = sharedPreferences.getString(AppConstants.userPassword);
      if (oldPassword != null) {
        await secureStorage.write(key: AppConstants.userPassword, value: oldPassword);
        _userPassword = oldPassword;
        await sharedPreferences.remove(AppConstants.userPassword);
      }
    } else {
      _userPassword = sPassword;
    }

    String? sCountryCode = await secureStorage.read(key: AppConstants.userCountryCode);
    if (sCountryCode == null) {
      String? oldCountryCode = sharedPreferences.getString(AppConstants.userCountryCode);
      if (oldCountryCode != null) {
        await secureStorage.write(key: AppConstants.userCountryCode, value: oldCountryCode);
        _userCountryCode = oldCountryCode;
        await sharedPreferences.remove(AppConstants.userCountryCode);
      }
    } else {
      _userCountryCode = sCountryCode;
    }

    if (_token.isNotEmpty) {
      apiClient.token = _token;
      apiClient.updateHeader(_token, sharedPreferences.getString(AppConstants.languageCode));
    }
  }

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
    _token = token;
    apiClient.token = token;
    apiClient.updateHeader(token, sharedPreferences.getString(AppConstants.languageCode));
    // Store token securely in encrypted storage
    await secureStorage.write(key: AppConstants.token, value: token);
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
        'Authorization': 'Bearer $_token'
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
    return _token;
  }

  @override
  bool isLoggedIn() {
    return _token.isNotEmpty;
  }

  @override
  Future<bool> clearSharedData() async {
    if(!GetPlatform.isWeb) {
      apiClient.postData(AppConstants.tokenUri, {"_method": "put", "fcm_token": 'no'});
    }
    // Clear token from both secure storage and SharedPreferences
    await secureStorage.delete(key: AppConstants.token);
    _token = "";
    return true;
  }

  @override
  Future<void> saveUserCredentials(String countryCode, String number, String password) async {
    _userCountryCode = countryCode;
    _userEmail = number;
    _userPassword = password;
    try {
      // Store credentials securely in encrypted storage
      await secureStorage.write(key: AppConstants.userPassword, value: password);
      await secureStorage.write(key: AppConstants.userEmail, value: number);
      await secureStorage.write(key: AppConstants.userCountryCode, value: countryCode);
    } catch (e) {
      rethrow;
    }
  }

  @override
  String getUserEmail() {
    return _userEmail;
  }

  @override
  String getUserPassword() {
    return _userPassword;
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
    _userEmail = "";
    _userPassword = "";
    await secureStorage.delete(key: AppConstants.userPassword);
    await secureStorage.delete(key: AppConstants.userEmail);
    return true;
  }


  @override
  Future<bool> clearUserCredentials() async{
    _userPassword = "";
    _userCountryCode = "";
    _userEmail = "";
    await secureStorage.delete(key: AppConstants.userPassword);
    await secureStorage.delete(key: AppConstants.userCountryCode);
    await secureStorage.delete(key: AppConstants.userEmail);
    return true;
  }

  @override
  Future<Response> forgotPassword(String? countryCode ,String? phone) async {
    Response _response = await apiClient.postData(AppConstants.forgotPassword,
        {
          'country_code' : countryCode,
          'phone': phone
        });
    return _response;
  }

  @override
  Future<Response> verifyOtp(String countryCode ,String? phone) async {
    Response _response = await apiClient.postData(AppConstants.verifyOtp,
        {
          'otp' : countryCode,
          'phone': phone
        });
    return _response;
  }

  @override
  String getUserCountryCode() {
    return _userCountryCode;
  }

}

