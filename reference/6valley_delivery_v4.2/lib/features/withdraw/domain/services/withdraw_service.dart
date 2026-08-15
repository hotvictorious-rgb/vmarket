import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_delivery_boy/data/api/api_checker.dart';
import 'package:sixvalley_delivery_boy/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_delivery_boy/features/withdraw/domain/models/withdraw_model.dart';
import 'package:sixvalley_delivery_boy/features/withdraw/domain/repositories/withdraw_repository_interfaec.dart';
import 'package:sixvalley_delivery_boy/features/withdraw/domain/services/withdraw_service_interface.dart';

class WithdrawService implements WithdrawServiceInterface {
  final WithdrawRepositoryInterface withdrawRepoInterface;
  WithdrawService({required this.withdrawRepoInterface});


  @override
  Future<Response> sendWithdrawRequest({String? amount, String? note}) async {
    Response response = await withdrawRepoInterface.sendWithdrawRequest(amount: amount, note: note);
    if (response.statusCode == 200) {
      Get.find<ProfileController>().getProfile();
      Get.back();
      String? message;
      message = response.body['message'];
      showCustomSnackBarWidget(message, isError: false);
    } else {
      ApiChecker.checkApi(response);
    }
    return response;
  }

  @override
  Future getWithdrawList({String? startDate, String? endDate, int? offset, String? type}) async {
    Response response = await withdrawRepoInterface.getWithdrawList(startDate: startDate, endDate: endDate, offset: offset, type: type);
    if (response.body != null && response.statusCode == 200) {
      return WithdrawModel.fromJson(response.body).withdraws!;
    } else {
      ApiChecker.checkApi(response);
    }
  }

}