import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:sixvalley_delivery_boy/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_delivery_boy/features/auth/domain/models/response_model.dart';
import 'package:sixvalley_delivery_boy/features/profile/domain/services/profile_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/profile/domain/models/userinfo_model.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_snackbar_widget.dart';

class ProfileController extends GetxController implements GetxService {
  final ProfileServiceInterface profileServiceInterface;
  ProfileController({required this.profileServiceInterface});



  UserInfoModel? _profileModel;
  UserInfoModel? get profileModel => _profileModel;
  String? _profileImage;
  String? get profileImage => _profileImage;
  File? file;
  final picker = ImagePicker();
  bool _isLoading = false;
  bool get isLoading => _isLoading;


  UserInfoModel? _userInfoModel;
  UserInfoModel? get userInfoModel => _userInfoModel;

  bool _lengthCheck = false;
  bool _numberCheck = false;
  bool _uppercaseCheck = false;
  bool _lowercaseCheck = false;
  bool _spatialCheck = false;
  bool _showPassView = false;

  bool get lengthCheck => _lengthCheck;
  bool get numberCheck => _numberCheck;
  bool get uppercaseCheck => _uppercaseCheck;
  bool get lowercaseCheck => _lowercaseCheck;
  bool get spatialCheck => _spatialCheck;
  bool get showPassView => _showPassView;


  final FocusNode fNameFocus = FocusNode();
  final FocusNode lNameFocus = FocusNode();
  final FocusNode addressFocus = FocusNode();
  final FocusNode passwordFocus = FocusNode();
  final FocusNode confirmPasswordFocus = FocusNode();

  final TextEditingController firstNameController = TextEditingController();
  final TextEditingController lastNameController = TextEditingController();
  final TextEditingController addressController = TextEditingController();
  final TextEditingController emailController = TextEditingController();
  final TextEditingController passwordController = TextEditingController();
  final TextEditingController confirmPasswordController = TextEditingController();


  Future<void> getProfile({bool isUpdate = true}) async {
    _profileModel = await profileServiceInterface.getProfileInfo();
    _profileImage = _profileModel?.imageFullUrl?.path;

    if(isUpdate) {
      update();
    }
  }


  Future <void> profileStatusChange(BuildContext context, int status) async {
    ResponseModel response = await profileServiceInterface.profileStatusOnnOff(status);
    if(response.isSuccess){
      getProfile();
    }
    update();
  }

  Future <Response> resetPassword (String? phone, String password ,String confirmPassword) async {
    _isLoading = true;
    update();
    Response _response = await profileServiceInterface.resetPassword(phone, password, confirmPassword);
    _isLoading = false;
    update();
    return _response;
  }

  Future<ResponseModel> updateUserInfo(UserInfoModel updateUserModel, String pass) async {
    _isLoading = true;
    update();
    ResponseModel responseModel = await profileServiceInterface.updateProfile(updateUserModel, pass, file, Get.find<AuthController>().getUserToken());
    responseModel.isSuccess ? _userInfoModel = updateUserModel : null;
    if(responseModel.isSuccess){
      _showPassView = false;
    }
    showCustomSnackBarWidget(responseModel.message, isError: false);
    _isLoading = false;
    update();
    return responseModel;
  }

  bool _isUpdate = false;
  bool get isUpdate => _isUpdate;
  Future<Response> updateBankInfo(String bankName, String branch, String accountNumber, String holderName) async {
    _isUpdate = true;
    update();
    Response response = await profileServiceInterface.updateBankInfo(bankName: bankName, branch: branch, accountNumber: accountNumber, holderName: holderName);
    _isUpdate = false;
    update();
    return response;
  }


  void validPassCheck(String pass, {bool isUpdate = true}){
    _lengthCheck = false;
    _numberCheck = false;
    _uppercaseCheck = false;
    _lowercaseCheck = false;
    _spatialCheck = false;

    if(pass.length > 7){
      _lengthCheck = true;
    }
    if(pass.contains(RegExp(r'[a-z]'))){
      _lowercaseCheck = true;
    }
    if(pass.contains(RegExp(r'[A-Z]'))){
      _uppercaseCheck = true;
    }
    if(pass.contains(RegExp(r'[ .!@#$&*~^%]'))){
      _spatialCheck = true;
    }
    if(pass.contains(RegExp(r'[\d+]'))){
      _numberCheck = true;
    }
    if(isUpdate) {
      update();
    }
  }

  void showHidePass({bool isUpdate = true}){
    _showPassView = ! _showPassView;
    if(isUpdate) {
      update();
    }
  }


  void clearPassword() {
    passwordController.text = '';
    confirmPasswordController.text = '';
  }



  bool isPasswordValid(){
    return (_lengthCheck && _lowercaseCheck && _uppercaseCheck && _spatialCheck && _numberCheck);
  }

}