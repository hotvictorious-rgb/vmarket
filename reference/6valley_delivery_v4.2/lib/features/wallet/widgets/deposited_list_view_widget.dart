import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/wallet/controllers/wallet_controller.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_loader_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/no_data_screen_widget.dart';
import 'package:sixvalley_delivery_boy/features/wallet/widgets/deposited_card_widget.dart';

class DepositedListViewWidget extends StatelessWidget {
  const DepositedListViewWidget({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return GetBuilder<WalletController>(
        builder: (walletController) {
          return !walletController.isLoading? walletController.depositedList.isNotEmpty?
          ListView.builder(
              itemCount: walletController.depositedList.length,
              physics: const NeverScrollableScrollPhysics(),
              shrinkWrap: true,
              itemBuilder: (context, index) => DepositedCardWidget(deposit: walletController.depositedList[index], index: index, length: walletController.depositedList.length,)):
          const NoDataScreenWidget() : CustomLoaderWidget(height: Get.height-600,);
        }
    );
  }
}
