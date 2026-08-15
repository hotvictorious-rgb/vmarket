
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/features/order_details/widgets/customer_info_widget.dart';
import 'package:sixvalley_delivery_boy/helper/date_converter.dart';
import 'package:sixvalley_delivery_boy/helper/string_extensions.dart';
import 'package:sixvalley_delivery_boy/theme/controllers/theme_controller.dart';
import 'package:sixvalley_delivery_boy/utill/app_constants.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:timeago/timeago.dart' as timeago;

class OngoingOrderHeaderWidget extends StatelessWidget {
  final OrderModel? orderModel;
  final int? index;
  final bool isExpanded;
  const OngoingOrderHeaderWidget({Key? key, this.orderModel, this.index, this.isExpanded = false}) : super(key: key);

  @override
  Widget build(BuildContext context) {

    String timeAgo = timeago.format(DateConverter.isoStringToLocalDate(orderModel!.createdAt!));

    return Padding(padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
      child: Column(mainAxisAlignment: MainAxisAlignment.start, children: [
        Row(children: [
          Text('${'order'.tr} # ${orderModel!.id}',
              style: rubikMedium.copyWith(fontSize: Dimensions.fontSizeExtraLarge, color: Get.isDarkMode ? Theme.of(context).hintColor : Colors.black)),

          const Expanded(child: SizedBox()),

          Padding(
            padding: EdgeInsets.only(right: Dimensions.paddingSizeMin),
            child: Text(timeAgo.toCapitalized(), style: robotoRegular.copyWith(fontSize: Dimensions.fontSizeSmall),),
          ),

          SizedBox(width: Dimensions.iconSizeDefault, height: Dimensions.iconSizeDefault, child: Image.asset(Images.assignedTime, color: Theme.of(context).hintColor,)),

        ]),
         SizedBox(height: Dimensions.paddingSizeDefault),
        Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            SizedBox(width: Dimensions.iconSizeDefault, child: Image.asset(isExpanded ? Images.sellerIconExpand : Images.sellerIcon)),
             SizedBox(width: Dimensions.paddingSizeSmall),
            Text('seller'.tr,
                style: robotoBold.copyWith(fontSize: Dimensions.fontSizeDefault,
                    color: Get.isDarkMode? Theme.of(context).primaryColorLight : Theme.of(context).primaryColor))]),

          Row(children: [
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  Padding(padding:  EdgeInsets.only(left: Dimensions.paddingSizeSmall),
                      child: Container(width: Dimensions.iconSizeSmall,height: Dimensions.iconSizeSmall,
                          color: Get.find<ThemeController>().darkTheme ? Theme.of(context).colorScheme.primary : Theme.of(context).primaryColor)),

                  SizedBox(width: Dimensions.paddingSizeDefault),
                  Text(orderModel!.sellerIs == 'admin'? AppConstants.companyName: orderModel!.sellerInfo?.shop?.name?.trim()??'Shop not found',
                      style: robotoBold.copyWith(fontSize: Dimensions.fontSizeSmall, color: Get.isDarkMode ? Theme.of(context).hintColor : Colors.black))
                ],),


                Row(children: [
                  Padding(padding:  EdgeInsets.only(left: Dimensions.paddingSizeSmall),
                      child: Container(width: Dimensions.iconSizeSmall,height: Dimensions.iconSizeSmall,
                          color: Get.find<ThemeController>().darkTheme ? Theme.of(context).colorScheme.primary : Theme.of(context).primaryColor)),

                  SizedBox(width:Get.context!.width<=400? Dimensions.paddingSizeDefault : Dimensions.paddingSizeLarge),
                  Expanded(child: Text(orderModel!.sellerInfo?.shop?.address??'',
                      maxLines: 2, style: robotoMedium.copyWith(fontSize: Dimensions.fontSizeSmall, color: Get.isDarkMode ? Theme.of(context).hintColor : Theme.of(context).hintColor)))]),

              SizedBox(height: Dimensions.paddingSizeMin,),
              Padding(padding:  EdgeInsets.only(left: Dimensions.paddingSizeSmall),
                  child: Container(
                    // margin: EdgeInsets.only(top: 5),
                      decoration: BoxDecoration(borderRadius: BorderRadius.circular(20),
                          color: Theme.of(context).colorScheme.tertiary), width: Dimensions.iconSizeSmall,
                      height: Dimensions.iconSizeSmall,
                  ),
              ),
            ]),
            )])]),
         SizedBox(height: Dimensions.paddingSizeExtraSmall),

       CustomerInfoWidget(orderModel: orderModel),


        isExpanded?const SizedBox():
        Container(padding:  EdgeInsets.all(Dimensions.paddingSizeExtraSmall),
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(50),
                color:Get.isDarkMode? Theme.of(context).hintColor.withValues(alpha:.25) : Theme.of(context).primaryColor.withValues(alpha:.04)),
          child: Icon(Icons.keyboard_arrow_down,
              size: Dimensions.iconSizeMenu, color: Get.isDarkMode? Theme.of(context).hintColor : Theme.of(context).primaryColor),
        ),
      ]),
    );
  }
}