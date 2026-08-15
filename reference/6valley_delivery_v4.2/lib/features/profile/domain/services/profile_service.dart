import 'dart:convert';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_delivery_boy/data/api/api_checker.dart';
import 'package:sixvalley_delivery_boy/features/auth/domain/models/response_model.dart';
import 'package:sixvalley_delivery_boy/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_delivery_boy/features/profile/domain/models/userinfo_model.dart';
import 'package:sixvalley_delivery_boy/features/profile/domain/repositories/profile_repository_interface.dart';
import 'package:sixvalley_delivery_boy/features/profile/domain/services/profile_service_interface.dart';
import 'package:http/http.dart' as http;

class ProfileService implements ProfileServiceInterface{
  ProfileRepositoryInterface profileRepoInterface;

  ProfileService({required this.profileRepoInterface});


  @override
  Future getProfileInfo() async{
    Response response = await profileRepoInterface.getProfileInfo();
    if (response.statusCode == 200) {
      return UserInfoModel.fromJson(response.body);
    } else {
      ApiChecker.checkApi(response);
    }
  }


  @override
  Future profileStatusOnnOff(int status) async {
    Response _response = await profileRepoInterface.profileStatusOnnOff(status);
    if (_response.statusCode == 200) {
      Get.back();
      return ResponseModel(true, '');
    } else {
      ApiChecker.checkApi(_response);
    }
  }

  @override
  Future<Response> resetPassword(String? phone, String password, String confirmPassword) async{
    Response _response = await profileRepoInterface.resetPassword(phone, password, confirmPassword);
    if (_response.statusCode == 200) {
      showCustomSnackBarWidget('password_reset_successfully'.tr, isError: false);

    } else {
      ApiChecker.checkApi(_response);
    }
    return _response;
  }


  @override
  Future<ResponseModel> updateProfile(updateUserModel, pass, file, String token) async {
    http.StreamedResponse response = await profileRepoInterface.updateProfile(updateUserModel, pass, file, token);
    if (response.statusCode == 200) {
      Get.find<ProfileController>().getProfile();
      Map map = jsonDecode(await response.stream.bytesToString());
      String? message = map["message"];
      return ResponseModel(true, message);
    } else {

      return ResponseModel(false, '${response.statusCode} ${response.reasonPhrase}');
    }
  }

  @override
  Future<Response> updateBankInfo({String? bankName, String? branch, String? accountNumber, String? holderName}) async{
    Response response = await profileRepoInterface.updateBankInfo(bankName: bankName, branch: branch, accountNumber: accountNumber, holderName: holderName);

    if (response.statusCode == 200) {
      Get.find<ProfileController>().getProfile();
      Get.back();
      String? message;
      message = response.body['message'];
      showCustomSnackBarWidget(message, isError: false);
      return response;
    } else {
      ApiChecker.checkApi(response);
      return response;
    }
  }

}