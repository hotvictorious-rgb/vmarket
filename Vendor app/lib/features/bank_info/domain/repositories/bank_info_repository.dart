import 'package:shared_preferences/shared_preferences.dart';
import 'package:sixvalley_vendor_app/data/datasource/remote/dio/dio_client.dart';
import 'package:sixvalley_vendor_app/data/datasource/remote/exception/api_error_handler.dart';
import 'package:sixvalley_vendor_app/features/profile/domain/models/profile_body.dart';
import 'package:sixvalley_vendor_app/data/model/response/base/api_response.dart';
import 'package:sixvalley_vendor_app/features/bank_info/domain/repositories/bank_info_repository_interface.dart';
import 'package:sixvalley_vendor_app/features/profile/domain/models/profile_info.dart';
import 'package:sixvalley_vendor_app/utill/app_constants.dart';
import 'package:http/http.dart' as http;

class BankInfoRepository implements BankInfoRepositoryInterface{

  final DioClient? dioClient;
  final SharedPreferences? sharedPreferences;
  BankInfoRepository({required this.dioClient, required this.sharedPreferences});

  @override
  Future<ApiResponse> chartFilterData(String? type) async {
    try {
      final response = await dioClient!.get('${AppConstants.chartFilterData}$type');
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponse> getOrderFilterData(String? type) async {
    try {
      final response = await dioClient!.get('${AppConstants.businessAnalytics}$type');
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<http.StreamedResponse> updateBank(ProfileInfoModel userInfoModel, ProfileBody seller, String token, {String? otp}) async {
    http.MultipartRequest request = http.MultipartRequest('POST', Uri.parse('${AppConstants.baseUrl}${AppConstants.sellerAndBankUpdate}'));
    request.headers.addAll(<String,String>{'Authorization': 'Bearer $token'});

    Map<String, String> fields = {};
    fields.addAll(<String, String>{
      '_method': 'put', 'bank_name': userInfoModel.bankName ?? '', 'branch': userInfoModel.branch ?? '',
      'holder_name': userInfoModel.holderName ?? '', 'account_no': userInfoModel.accountNo ?? '',
      'f_name': seller.fName ?? '', 'l_name': seller.lName ?? '', 'phone': userInfoModel.phone ?? ''
    });
    if (otp != null && otp.isNotEmpty) {
      fields['otp'] = otp;
    }
    request.fields.addAll(fields);
    http.StreamedResponse response = await request.send();
    return response;
  }

  @override
  Future<ApiResponse> getNigerianBanks() async {
    try {
      final response = await dioClient!.get(AppConstants.getNigerianBanksUri);
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponse> resolveAccount(String accountNumber, String bankCode) async {
    try {
      final response = await dioClient!.post(AppConstants.resolveAccountUri, data: {
        'account_number': accountNumber,
        'bank_code': bankCode,
      });
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future<ApiResponse> sendBankOtp(String bankName, String accountNo, String holderName) async {
    try {
      final response = await dioClient!.post(AppConstants.sendBankOtpUri, data: {
        'bank_name': bankName,
        'account_no': accountNo,
        'holder_name': holderName,
      });
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  String getBankToken() {
    return sharedPreferences!.getString(AppConstants.token) ?? "";
  }

  @override
  Future add(value) {
    // TODO: implement add
    throw UnimplementedError();
  }

  @override
  Future delete(int id) {
    // TODO: implement delete
    throw UnimplementedError();
  }

  @override
  Future get(String id) {
    // TODO: implement get
    throw UnimplementedError();
  }

  @override
  Future getList({int? offset = 1}) async {
    try {
      final response = await dioClient!.get(AppConstants.sellerUri);
      return ApiResponse.withSuccess(response);
    } catch (e) {
      return ApiResponse.withError(ApiErrorHandler.getMessage(e));
    }
  }

  @override
  Future update(Map<String, dynamic> body, int id) {
    // TODO: implement update
    throw UnimplementedError();
  }
}
