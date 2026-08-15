import 'dart:io';
import 'package:get/get_connect/http/src/response/response.dart';
import 'package:sixvalley_delivery_boy/interface/repository_interface.dart';

abstract class ProfileRepositoryInterface implements RepositoryInterface{

  Future<dynamic> updateProfile(dynamic updateUserModel,String pass,File? file, String token);
  Future<Response> getProfileInfo();
  Future<Response> profileStatusOnnOff( int status);
  Future<Response> resetPassword(String? phone, String password ,String confirmPassword);
  Future<Response> updateBankInfo({String? bankName, String? branch, String? accountNumber, String? holderName});
}