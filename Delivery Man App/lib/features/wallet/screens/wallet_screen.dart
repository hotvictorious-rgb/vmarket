import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_single_child_list_widget.dart';
import 'package:sixvalley_delivery_boy/features/dashboard/screens/dashboard_screen.dart';
import 'package:sixvalley_delivery_boy/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_delivery_boy/features/wallet/controllers/wallet_controller.dart';
import 'package:sixvalley_delivery_boy/features/wallet/domain/models/transaction_type_model.dart';
import 'package:sixvalley_delivery_boy/helper/color_helper.dart';
import 'package:sixvalley_delivery_boy/theme/controllers/theme_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_app_bar_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/sliver_deligate_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_delivery_boy/features/wallet/widgets/remit_cash_bottom_sheet_widget.dart';
import 'package:sixvalley_delivery_boy/helper/price_converter.dart';
import 'package:sixvalley_delivery_boy/features/wallet/widgets/deposited_list_view_widget.dart';
import 'package:sixvalley_delivery_boy/features/wallet/widgets/transaction_list_view_widget.dart';
import 'package:sixvalley_delivery_boy/features/wallet/widgets/transaction_search_filter_widget.dart';
import 'package:sixvalley_delivery_boy/features/wallet/widgets/transaction_type_card_widget.dart';
import 'package:sixvalley_delivery_boy/features/wallet/widgets/wallet_withdraw_send_card_widget.dart';
import 'package:sixvalley_delivery_boy/features/withdraw/widgets/withdraw_list_view_widget.dart';


class WalletScreen extends StatefulWidget {
  final bool fromNotification;
  final int? selectedIndex;
  final bool fromProfile;
  const WalletScreen({Key? key, required this.fromNotification, this.selectedIndex, this.fromProfile = false}) : super(key: key);

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  final ScrollController _scrollController = ScrollController();

  final List<TransactionTypeModel> _transactionTypes = [
    TransactionTypeModel(Images.delivery, 'delivery_charge_earned', Get.find<ProfileController>().profileModel?.totalEarn ?? 0, 0),
    TransactionTypeModel(Images.withdrawn, 'withdrawn', Get.find<ProfileController>().profileModel?.totalWithdraw ?? 0, 1),
    TransactionTypeModel(Images.pendingWithdraw, 'pending_withdrawn', Get.find<ProfileController>().profileModel?.pendingWithdraw ?? 0, 2),
    TransactionTypeModel(Images.deposit, 'already_deposited', Get.find<ProfileController>().profileModel?.totalDeposit ?? 0, 3),
  ];

  @override
  void initState() {

    Get.find<WalletController>().selectDate(isUpdate: false);

    Get.find<WalletController>().getOrderWiseDeliveryCharge('', '', 1,'', isUpdate: false);
    if(widget.fromNotification) {
      if(Get.find<ProfileController>().profileModel == null){
        Get.find<ProfileController>().getProfile();
      }
      Get.find<WalletController>().selectedItemForFilter(widget.selectedIndex ?? 0, fromTop: true, fromNotification: true);
    }

    super.initState();
  }




  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (canPop, _) async {
        if(canPop) return;
        
        if(widget.fromNotification) {
          Get.to(()=> const DashboardScreen(pageIndex: 0));
        }

        if(widget.fromProfile){
          Get.to(()=> const DashboardScreen(pageIndex: 4));
        }
      },
      child: Scaffold(
        resizeToAvoidBottomInset: false,
        appBar: CustomAppBarWidget(
          title: 'my_wallet'.tr, isBack: true,
          onTap: (){
            if(widget.fromNotification) {
              Get.to(()=> const DashboardScreen(pageIndex: 0));
            } else {
              Get.back();
            }
          },
        ),
        body: SafeArea(
          child: CustomScrollView(
            controller: _scrollController,
            slivers: [
              SliverPersistentHeader(
                pinned: true,
                delegate: SliverDelegateWidget(
                  containerHeight: 200,
                    child: const WalletSendWithdrawCardWidget(),
                ),
              ),


              SliverToBoxAdapter(
                  child: Padding(
                    padding:  EdgeInsets.symmetric(horizontal: Dimensions.rememberMeSizeDefault),
                    child: GetBuilder<WalletController>(
                      builder: (walletController) {
                        String title = walletController.selectedItem == 0?
                        _transactionTypes[0].title:
                        walletController.selectedItem == 1?
                        _transactionTypes[1].title:
                        walletController.selectedItem == 2?
                        _transactionTypes[2].title:
                        _transactionTypes[3].title;

                        return Column(crossAxisAlignment: CrossAxisAlignment.start, children:  [

                          GetBuilder<ProfileController>(
                            builder: (profileController) {
                              final double cashInHand = profileController.profileModel?.cashInHand ?? 0.0;
                              return Container(
                                margin: EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                                padding: EdgeInsets.all(Dimensions.paddingSizeDefault),
                                decoration: BoxDecoration(
                                  color: Theme.of(context).cardColor,
                                  borderRadius: BorderRadius.circular(Dimensions.paddingSizeDefault),
                                  border: Border.all(
                                    color: const Color(0xFF4A148C).withValues(alpha: 0.25),
                                    width: 1.2,
                                  ),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Get.find<ThemeController>().darkTheme
                                          ? Colors.black.withValues(alpha: 0.10)
                                          : Colors.grey[100]!,
                                      blurRadius: 5,
                                      spreadRadius: 1,
                                    )
                                  ],
                                ),
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            children: [
                                              Container(
                                                padding: const EdgeInsets.all(6),
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFF4A148C).withValues(alpha: 0.1),
                                                  shape: BoxShape.circle,
                                                ),
                                                child: const Icon(Icons.payments_rounded, color: Color(0xFF4A148C), size: 18),
                                              ),
                                              const SizedBox(width: 8),
                                              Text(
                                                'Cash in Hand'.tr,
                                                style: rubikMedium.copyWith(
                                                  fontSize: Dimensions.fontSizeSmall,
                                                  color: Get.isDarkMode ? Colors.white70 : Colors.black87,
                                                ),
                                              ),
                                            ],
                                          ),
                                          const SizedBox(height: 6),
                                          Text(
                                            PriceConverter.convertPrice(cashInHand),
                                            style: rubikBold.copyWith(
                                              fontSize: 20,
                                              color: const Color(0xFF4A148C),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    InkWell(
                                      onTap: () {
                                        if (cashInHand > 0) {
                                          Get.bottomSheet(
                                            const RemitCashBottomSheetWidget(),
                                            isScrollControlled: true,
                                            backgroundColor: Colors.transparent,
                                          );
                                        } else {
                                          showCustomSnackBarWidget('No cash in hand to remit'.tr);
                                        }
                                      },
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFF4A148C),
                                          borderRadius: BorderRadius.circular(Dimensions.paddingSizeExtraLarge),
                                          boxShadow: [
                                            BoxShadow(
                                              color: const Color(0xFF4A148C).withValues(alpha: 0.3),
                                              blurRadius: 4,
                                              offset: const Offset(0, 2),
                                            )
                                          ],
                                        ),
                                        child: Row(
                                          children: [
                                            const Icon(Icons.send_rounded, color: Color(0xFFFFD700), size: 16),
                                            const SizedBox(width: 6),
                                            Text(
                                              'Remit via Paystack'.tr,
                                              style: rubikBold.copyWith(
                                                color: Colors.white,
                                                fontSize: Dimensions.fontSizeSmall,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),

                          CustomSingleChildListWidget(
                            scrollDirection: Axis.horizontal,
                            itemCount: _transactionTypes.length,
                            itemBuilder: (int index) {

                            return GestureDetector(
                              onTap: (){
                                walletController.selectedItemForFilter(index, fromTop: true);
                              },
                              child: Padding(
                                padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeExtraSmall),
                                child: TransactionCardWidget(transactionTypeModel: _transactionTypes[index], selectedIndex: walletController.selectedItem),
                              ));
                            },
                           ),

                          const DeliverySearchFilterWidget(fromOrderHistory: false),

                          Padding(
                            padding:  EdgeInsets.fromLTRB(0, 0, Dimensions.paddingSizeDefault, Dimensions.paddingSizeDefault),
                            child: Text(title.tr, style: rubikMedium.copyWith(fontSize: Dimensions.fontSizeLarge, color: (
                              Get.find<ThemeController>().darkTheme ?
                              ColorHelper.blendColors(Colors.white, Theme.of(context).primaryColor, 0.9) :
                              ColorHelper.darken(Theme.of(context).primaryColor, 0.1)
                            ))),
                          ),
                          walletController.selectedItem == 0
                              ? const TransactionListViewWidget()
                              : walletController.selectedItem == 3
                              ? const DepositedListViewWidget()
                              : const WithdrawListViewWidget()

                        ]);
                      }
                    ),
                  )
              )
            ],
          ),
        ),
      ),
    );
  }
}


