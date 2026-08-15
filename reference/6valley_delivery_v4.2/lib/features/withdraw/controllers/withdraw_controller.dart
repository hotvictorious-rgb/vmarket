
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/wallet/controllers/wallet_controller.dart';
import 'package:sixvalley_delivery_boy/features/withdraw/domain/models/withdraw_model.dart';
import 'package:sixvalley_delivery_boy/features/withdraw/domain/services/withdraw_service_interface.dart';

class WithdrawController extends GetxController implements GetxService {
  final WithdrawServiceInterface withdrawServiceInterface;
  WithdrawController({required this.withdrawServiceInterface});

  bool _isWithdraw = false;
  bool get isWithdraw => _isWithdraw;
  List<Withdraws> _withdrawList = [];
  List<Withdraws> get withdrawList => _withdrawList;
  bool _isLoading = false;
  bool get isLoading => _isLoading;



  Future<Response> sendWithdrawRequest(String amount, String note) async {
    _isWithdraw = true;
    update();
    Response response = await withdrawServiceInterface.sendWithdrawRequest(amount: amount,note: note);
    _isWithdraw = false;
    if(response.statusText == 'OK') {
      Get.find<WalletController>().getPendingWithdrawList();
    }
    update();
    return response;
  }


  Future getWithdrawList(String startDate, String endDate, int offset, String type, {bool reload = true,  bool fromNotification = false}) async {
    if(reload){
      _withdrawList = [];
    }
    _isLoading = true;
    if(!fromNotification) {
      update();
    }
    _withdrawList = await withdrawServiceInterface.getWithdrawList(startDate: startDate,endDate: endDate, offset: offset,type: type);
    _isLoading = false;
    update();
  }


}