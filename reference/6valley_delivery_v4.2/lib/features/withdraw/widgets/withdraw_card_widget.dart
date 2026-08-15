import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_divider_widget.dart';
import 'package:sixvalley_delivery_boy/features/wallet/controllers/wallet_controller.dart';
import 'package:sixvalley_delivery_boy/features/withdraw/domain/models/withdraw_model.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/helper/date_converter.dart';
import 'package:sixvalley_delivery_boy/helper/price_converter.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';

class WithdrawCardWidget extends StatelessWidget {
  final Withdraws? withdraws;
  final int? index;
  final int? length;
  const WithdrawCardWidget({Key? key, this.withdraws, this.index, this.length}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Container(padding:  EdgeInsets.fromLTRB(Dimensions.paddingSizeDefault,
      Dimensions.paddingSizeSmall,Dimensions.paddingSizeDefault,Dimensions.paddingSizeSmall,),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start,children: [
        Row(children: [
          Padding(padding:  EdgeInsets.only(right: Dimensions.paddingSizeSmall),
            child: SizedBox(width:Dimensions.iconSizeDefault,
                child: Image.asset(Get.find<WalletController>().selectedItem == 1?
                Images.withdrawn : Images.pendingWithdraw))),

          Expanded(child: Text(DateConverter.isoStringToDateTimeString(withdraws!.updatedAt!).toString(),
            style: rubikRegular.copyWith(color: Theme.of(context).hintColor,
                fontSize: Dimensions.fontSizeSmall))),

          Container(decoration: BoxDecoration(borderRadius: BorderRadius.circular(50),
              color: Theme.of(context).colorScheme.onTertiaryContainer.withValues(alpha:.1)),
            padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault,vertical: Dimensions.paddingSizeExtraSmall),
            child: Text(' ${PriceConverter.convertPrice(withdraws!.amount)}',
                style: rubikMedium.copyWith(color: Get.isDarkMode?
                Theme.of(context).hintColor.withValues(alpha:.5) :
                Theme.of(context).colorScheme.onTertiaryContainer)))]),

        ((index!+1) < length!) ? Padding(padding:  EdgeInsets.only(top: Dimensions.paddingSizeLarge),
          child: CustomDividerWidget(height: .5, color: Theme.of(context).hintColor)) : const SizedBox.shrink()]));
  }
}
