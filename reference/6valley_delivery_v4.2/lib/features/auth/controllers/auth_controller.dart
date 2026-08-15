import 'dart:async';
import 'dart:io';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:sixvalley_delivery_boy/common/controllers/localization_controller.dart';
import 'package:sixvalley_delivery_boy/features/auth/domain/services/auth_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/auth/domain/models/response_model.dart';

class AuthController extends GetxController implements GetxService {
  final AuthServiceInterface authServiceInterface;
  AuthController({required this.authServiceInterface}) ;


  final bool _notification = true;
  XFile? _pickedFile;
  final String _loginErrorMessage = '';
  String get loginErrorMessage => _loginErrorMessage;
  bool _willPhoneNumberVerificationButtonLoading = false;
  bool get willPhoneNumberVerificationButtonLoading => _willPhoneNumberVerificationButtonLoading;
  bool _isLoading = false;
  bool get isLoading => _isLoading;
  bool get notification => _notification;
  XFile? get pickedFile => _pickedFile;
  String _countryDialCode = "+1";
  String get countryCode => _countryDialCode;

  File? file;
  final picker = ImagePicker();

  int _selectionTabIndex = 1;
  int get selectionTabIndex =>_selectionTabIndex;


  void updateCountryDialCode (String value,{bool isUpdate = true}){
    _countryDialCode = value;
    if(isUpdate){
      update();
    }
  }

  void choose() async {
    final pickedFile = await picker.pickImage(source: ImageSource.gallery, imageQuality: 50, maxHeight: 500, maxWidth: 500);
    if (pickedFile != null) {
      file = File(pickedFile.path);
    }
    update();
  }

  void setIndexForTabBar(int index, {bool notify = true}){
    _selectionTabIndex = index;
    if(notify){
      update();
    }
  }


  Future<ResponseModel> login(String countryCode, String phone, String password) async {
    _isLoading = true;
    update();
    ResponseModel responseModel = await authServiceInterface.login(countryCode ,phone, password);
    _isLoading = false;
    setCurrentLanguage(Get.find<LocalizationController>().getCurrentLanguage() ?? 'en');
    update();
    return responseModel;
  }



  Future<void> setCurrentLanguage(String currentLanguage) async {
    await authServiceInterface.setLanguageCode(currentLanguage);
  }

  void pickImage() async {
    _pickedFile = await ImagePicker().pickImage(source: ImageSource.gallery);
    update();
  }


  Future<void> updateToken() async {
    await authServiceInterface.updateToken();
  }


  String _verificationCode = '';
  String get verificationCode => _verificationCode;

  void updateVerificationCode(String query) {
    _verificationCode = query;
    update();
  }


  bool _isActiveRememberMe = false;

  bool get isActiveRememberMe => _isActiveRememberMe;

  void toggleRememberMe() {
    _isActiveRememberMe = !_isActiveRememberMe;
    update();
  }

  bool isLoggedIn() {
    return authServiceInterface.isLoggedIn();
  }

  Future<bool> clearSharedData() async {
    return await authServiceInterface.clearSharedData();
  }

  void saveUserCredentials(String countryCode, String number, String password) {
    authServiceInterface.saveUserCredentials(countryCode, number, password);
  }


  String getUserEmail() {
    return authServiceInterface.getUserEmail();
  }

  String getUserPassword() {
    return authServiceInterface.getUserPassword();
  }

  String? getUserCountryCode() {
    return authServiceInterface.getUserCountryCode();
  }

  Future<bool> clearUserEmailAndPassword() async {
    return authServiceInterface.clearUserCredentials();
  }


  String getUserToken() {
    return authServiceInterface.getUserToken();
  }


  void initData() {
    _pickedFile = null;
  }

  Future <Response> forgotPassword(String? countryCode, String? phone) async {
    _isLoading = true;
    update();
    Response _response = await authServiceInterface.forgotPassword(countryCode, phone);

    _isLoading = false;
    update();
    return _response;
  }


  Future <Response> verifyOtp (String otp ,String? phone) async {
    _willPhoneNumberVerificationButtonLoading = true;
    update();
    Response _response = await authServiceInterface.verifyOtp(otp, phone);
    _willPhoneNumberVerificationButtonLoading = false;
    update();
    return _response;
  }


}