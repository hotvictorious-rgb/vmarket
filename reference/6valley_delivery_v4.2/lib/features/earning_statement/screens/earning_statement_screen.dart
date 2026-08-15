import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/wallet/controllers/wallet_controller.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_app_bar_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/sliver_deligate_widget.dart';
import 'package:sixvalley_delivery_boy/features/earning_statement/widgets/earning_filter_button_widget.dart';
import 'package:sixvalley_delivery_boy/features/earning_statement/widgets/earning_statement_list_widget.dart';

class EarningStatementScreen extends StatefulWidget {
  const EarningStatementScreen({Key? key}) : super(key: key);

  @override
  State<EarningStatementScreen> createState() => _EarningStatementScreenState();
}

class _EarningStatementScreenState extends State<EarningStatementScreen> {
  
  @override
  void initState() {
    Get.find<WalletController>().setEarningFilterIndex(0, isUpdate: false);
    Get.find<WalletController>().getOrderWiseDeliveryCharge('', '', 1,'', isUpdate: false);
    super.initState();
  }
  @override
  Widget build(BuildContext context) {
    Get.find<WalletController>().getOrderWiseDeliveryCharge('', '', 1,'');
    return Scaffold(
      appBar: CustomAppBarWidget(title: 'earning_statement'.tr, isBack: true),
      body: RefreshIndicator(
        onRefresh: () async{
          Get.find<WalletController>().setEarningFilterIndex(Get.find<WalletController>().orderTypeFilterIndex);
        },
        child: CustomScrollView(slivers: [
            SliverPersistentHeader(
                pinned: true,
                delegate: SliverDelegateWidget(
                    containerHeight: 80,
                    child: const EarningFilterButtonWidget())),

            SliverToBoxAdapter(child: Column(children:  [

              GetBuilder<WalletController>(
                builder: (walletController) {
                  return EarningStatementListViewWidget(walletController: walletController);
                }
              ),
            ],),)
          ],
        ),
      ),
    );
  }
}
