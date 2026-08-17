
import 'package:get/get_connect/http/src/response/response.dart';

abstract class WalletServiceInterface {
  Future<dynamic> getDeliveryWiseEarned({String? startDate, String? endDate, int? offset,String? type});
  Future<dynamic> getDepositedList({String? startDate, String? endDate, int? offset, String? type});
  Future<Response> remitCashViaPaystack({required double amount});
}