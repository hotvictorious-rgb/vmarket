import 'dart:async';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/wallet/domain/models/delivery_wise_earned_model.dart';
import 'package:sixvalley_delivery_boy/features/wallet/domain/services/wallet_service_interface.dart';
import 'package:sixvalley_delivery_boy/features/withdraw/controllers/withdraw_controller.dart';
import 'package:sixvalley_delivery_boy/features/wallet/domain/models/deposited_model.dart';

class WalletController extends GetxController implements GetxService {
  final WalletServiceInterface walletServiceInterface;
  WalletController({required this.walletServiceInterface});

  List<Orders> _deliveryWiseEarned = [];
  List<Orders> get deliveryWiseEarned => _deliveryWiseEarned;
  List<Deposit> _depositedList = [];
  List<Deposit> get depositedList => _depositedList;
   bool _isLoading = false;
   bool get isLoading => _isLoading;



   int _selectedItem = 0;
   int get selectedItem => _selectedItem;
  String _startDate = 'dd-mm-yyyy';
  String get startDate => _startDate;
  String _endDate = 'dd-mm-yyyy';
  String get endDate => _endDate;



   void selectedItemForFilter(int index,{bool fromTop = false, bool fromNotification = false}){
     if(fromTop){
       _startDate = 'dd-mm-yyyy';
       _endDate = 'dd-mm-yyyy';
     }

     _selectedItem = index;
     if(selectedItem == 0 ){
       getOrderWiseDeliveryCharge(startDate == "dd-mm-yyyy"? '' : startDate, endDate == "dd-mm-yyyy" ? '' : endDate, 1, '');
     } else if(selectedItem == 1 ){
       Get.find<WithdrawController>().getWithdrawList(startDate == "dd-mm-yyyy" ? '' :startDate, endDate == "dd-mm-yyyy"? '' : endDate, 1, 'withdrawn', fromNotification: fromNotification);
     }else if(selectedItem == 2 ){
       Get.find<WithdrawController>().getWithdrawList(startDate == "dd-mm-yyyy" ? '' :startDate, endDate == "dd-mm-yyyy" ? '' : endDate, 1, 'pending', fromNotification: fromNotification);
     }else if(selectedItem == 3 ){
       getDepositedList(startDate == "dd-mm-yyyy" ? '' : startDate, endDate == "dd-mm-yyyy" ? '' : endDate, 1, '', fromNotification: fromNotification);
     }
     if(!fromNotification){
       update();
     }
   }



  Future getOrderWiseDeliveryCharge(String startDate, String endDate, int offset, String type, {bool isUpdate = true}) async {
    _isLoading = true;
     if(isUpdate) {
       update();
    }
    _deliveryWiseEarned = await walletServiceInterface.getDeliveryWiseEarned(startDate: startDate,endDate: endDate, offset: offset, type: type);


    _isLoading = false;
    update();
  }

  int _orderTypeFilterIndex = 0;
  int get orderTypeFilterIndex => _orderTypeFilterIndex;
  void setEarningFilterIndex(int index, {bool isUpdate = true}) {
    _orderTypeFilterIndex = index;
    if(_orderTypeFilterIndex == 0){
      getOrderWiseDeliveryCharge('', '', 1, '');
    }else if(_orderTypeFilterIndex == 1){
      getOrderWiseDeliveryCharge('', '', 1, 'TodayEarn');
    }
    else if(_orderTypeFilterIndex == 2){
      getOrderWiseDeliveryCharge('', '', 1, 'ThisWeekEarn');
    }
    else if(_orderTypeFilterIndex == 3){
      getOrderWiseDeliveryCharge('', '', 1, 'ThisMonthEarn');
    }

    if(isUpdate){
      update();
    }

  }




  Future<void> getDepositedList(String startDate, String endDate, int offset, String type, {bool reload = true,  bool fromNotification = false}) async {
    if(reload){
      _depositedList = [];
    }
    _isLoading = true;
    if(!fromNotification) {
      update();
    }
    _depositedList = await walletServiceInterface.getDepositedList(startDate: startDate,endDate: endDate, offset: offset,type: type);
    _isLoading = false;

    update();
  }



  Future <void> selectDate({String startDate = 'dd-mm-yyyy', String endDate = 'dd-mm-yyyy', bool isUpdate = true}) async {
    _startDate = startDate;
    _endDate = endDate;
    
    if(isUpdate) {
      update();
    }
  }

  void getPendingWithdrawList() {
    Get.find<WithdrawController>().getWithdrawList(startDate=="dd-mm-yyyy"? '' : startDate, endDate =="dd-mm-yyyy"? '' : endDate, 1, 'pending', reload: true);
  }


}
