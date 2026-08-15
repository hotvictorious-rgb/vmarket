import 'dart:io';
import 'package:sixvalley_delivery_boy/features/auth/domain/models/response_model.dart';

abstract class ProfileServiceInterface {

  Future<ResponseModel> updateProfile(dynamic updateUserModel,String pass,File? file, String token);
  Future<dynamic> getProfileInfo();
  Future<dynamic> profileStatusOnnOff( int status);
  Future<dynamic> resetPassword(String? phone, String password ,String confirmPassword);
  Future<dynamic> updateBankInfo({String? bankName, String? branch, String? accountNumber, String? holderName});

}