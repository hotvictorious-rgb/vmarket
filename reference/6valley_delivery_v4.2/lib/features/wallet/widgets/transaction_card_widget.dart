import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_divider_widget.dart';
import 'package:sixvalley_delivery_boy/features/wallet/domain/models/delivery_wise_earned_model.dart';
import 'package:sixvalley_delivery_boy/helper/date_converter.dart';
import 'package:sixvalley_delivery_boy/helper/price_converter.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';

class TransactionCardWidget extends StatelessWidget {
  final Orders orders;
  final int? index;
  final int? length;
  const TransactionCardWidget({Key? key, required this.orders, this.index, this.length}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Container(padding:  EdgeInsets.fromLTRB(Dimensions.paddingSizeMin,
      Dimensions.paddingSizeSmall,Dimensions.paddingSizeDefault,Dimensions.paddingSizeSmall,),
    child: Column(crossAxisAlignment: CrossAxisAlignment.start,children: [
      Row(children: [
        SizedBox(width:Dimensions.iconSizeDefault,child: Image.asset(Images.assigned)),

        Padding(padding:  EdgeInsets.only(left: Dimensions.paddingSizeSmall),
          child: Text('${'order'.tr} # ${orders.id}',
            style: rubikMedium.copyWith(color: Get.isDarkMode? Theme.of(context).hintColor.withValues(alpha:.5) :
                Theme.of(context).primaryColor,
              fontSize: Dimensions.fontSizeLarge)))]),

      Padding(padding:  EdgeInsets.fromLTRB(Dimensions.paddingSizeOverLarge,0,0,0,),
        child: Row(children: [
          Expanded(child: Text(DateConverter.isoStringToDateTimeString(orders.updatedAt!).toString(),
            style: rubikRegular.copyWith(color: Theme.of(context).hintColor,fontSize: Dimensions.fontSizeSmall),)),
          Container(decoration: BoxDecoration(borderRadius: BorderRadius.circular(50),
            color: Theme.of(context).colorScheme.onTertiaryContainer.withValues(alpha:.1)),
          padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault,vertical: Dimensions.paddingSizeExtraSmall),
            child: Row(children: [
                Text('delivery_man_fee'.tr,style: rubikRegular.copyWith(color: Theme.of(context).colorScheme.onTertiaryContainer)),
                Text(' ${PriceConverter.convertPrice(orders.deliverymanCharge)}',
                    style: rubikMedium.copyWith(color: Theme.of(context).colorScheme.onTertiaryContainer))]))])),

       Padding(padding:  EdgeInsets.fromLTRB(Dimensions.paddingSizeOverLarge,0,Dimensions.paddingSizeDefault,0,),
        child: Text(PriceConverter.convertPrice(orders.orderAmount),
            style: rubikMedium.copyWith(color: Get.isDarkMode?
            Theme.of(context).hintColor.withValues(alpha:.5) :Theme.of(context).primaryColor))),

      ((index!+1) < length!) ? Padding(padding:  EdgeInsets.only(top: Dimensions.paddingSizeSmall),
        child: CustomDividerWidget(height: .5, color: Theme.of(context).hintColor)) : const SizedBox.shrink()],),);
  }
}
