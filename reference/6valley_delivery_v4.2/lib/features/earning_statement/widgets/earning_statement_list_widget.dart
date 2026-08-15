import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_loader_widget.dart';
import 'package:sixvalley_delivery_boy/features/wallet/controllers/wallet_controller.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/no_data_screen_widget.dart';
import 'package:sixvalley_delivery_boy/features/earning_statement/widgets/earning_statement_card_widget.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';

class EarningStatementListViewWidget extends StatelessWidget {
  final WalletController? walletController;
  const EarningStatementListViewWidget({Key? key, this.walletController}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Column(children: [
      !walletController!.isLoading? walletController!.deliveryWiseEarned.isNotEmpty?
      ListView.builder(
        itemCount: walletController!.deliveryWiseEarned.length,
        physics: const NeverScrollableScrollPhysics(),
        shrinkWrap: true,
        itemBuilder: (context, index)=> EarningStatementCardWidget(ordersWiseEarned: walletController!.deliveryWiseEarned[index])) :
      Padding(
        padding: EdgeInsets.only(top: Dimensions.splashLogoWidth),
        child: NoDataScreenWidget(noDataImage: Images.noTransactionAvailableIcon, noDataMessage: 'no_transaction_available'.tr),
      ) : const CustomLoaderWidget(height: 500,)
    ],);
  }
}
