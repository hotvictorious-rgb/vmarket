import 'dart:developer';

import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/error_response.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/show_custom_snakbar_widget.dart';
import 'package:provider/provider.dart';

class ApiChecker {
  static void checkApi(ApiResponseModel apiResponse, {bool firebaseResponse = false}) {
    if (Get.context == null) return;

    final statusCode = apiResponse.response?.statusCode;
    final errorStr = apiResponse.error?.toString() ?? '';

    if (apiResponse.error == "Failed to load data - status code: 401" || statusCode == 401) {
      Provider.of<AuthController>(Get.context!, listen: false).clearSharedData();
    } else if (statusCode == 500) {
      showCustomSnackBarWidget(getTranslated('internal_server_error', Get.context!) ?? 'Something went wrong', Get.context!, snackBarType: SnackBarType.error);
    } else if (statusCode == 503) {
      final msg = apiResponse.response?.data?['message'];
      if (msg != null && msg.toString().isNotEmpty) {
        showCustomSnackBarWidget(msg.toString(), Get.context!, snackBarType: SnackBarType.error);
      }
    } else if (statusCode == 508 || errorStr.contains('508')) {
      // [AI] Suppress intrusive popup for shared hosting 508 resource limits to avoid interrupting user
      log("Shared hosting resource limit (508) detected; gracefully handling silently in background.");
    } else if (errorStr.contains('SocketException') || errorStr.contains('Connection timeout') || errorStr.contains('Network is unreachable')) {
      // [AI] Graceful non-intrusive offline note
      log("Network connection issue: $errorStr");
    } else {
      dynamic errorResponse = apiResponse.error is String ? apiResponse.error : ErrorResponse.fromJson(apiResponse.error);
      String? errorMessage = apiResponse.error?.toString();
      if (apiResponse.error is! String && errorResponse is ErrorResponse && errorResponse.errors != null && errorResponse.errors!.isNotEmpty) {
        errorMessage = errorResponse.errors![0].message;
      }
      if (errorMessage != null && errorMessage.isNotEmpty && !errorMessage.startsWith('Failed to load data - status code: 508')) {
        showCustomSnackBarWidget(firebaseResponse ? errorResponse?.replaceAll('_', ' ') : errorMessage, Get.context!, snackBarType: SnackBarType.error);
      }
    }
  }


  static ErrorResponse getError(ApiResponseModel apiResponse){
    ErrorResponse error;

    try{
      error = ErrorResponse.fromJson(apiResponse.response?.data);
    }catch(e){
      if(apiResponse.error is String){
        error = ErrorResponse(errors: [Errors(code: '', message: apiResponse.error.toString())]);

      }else{
        error = ErrorResponse.fromJson(apiResponse.error);
      }
    }
    return error;
  }
}