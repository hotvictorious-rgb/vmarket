import 'package:flutter/material.dart';
import 'package:dotted_border/dotted_border.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_asset_image_widget.dart';
import 'package:sixvalley_delivery_boy/features/order_details/controllers/order_details_controller.dart';
import 'package:sixvalley_delivery_boy/helper/date_converter.dart';
import 'package:sixvalley_delivery_boy/helper/price_converter.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';


class OrderItemInfoWidget extends StatelessWidget {
  final String? title;
  final String? info;
  final bool isProduct;
  final bool isDate;
  final bool isDeliveryCost;
  final bool isPrice;
  final bool isCount;
  final Color? textColor;
  final TextStyle? textStyle;
  final TextStyle? titleTextStyle;
  const OrderItemInfoWidget({Key? key, this.title, this.info, this.isProduct = false,
    this.isDate = false, this.isDeliveryCost = false, this.isPrice = false,
    this.isCount = false, this.textColor, this.textStyle, this.titleTextStyle}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Padding(padding:  EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          isCount ? Text(
              title!.tr + ' (*${Get.find<OrderDetailsController>().orderDetails![0].qty})',
              style: titleTextStyle ?? rubikRegular.copyWith(
                  color: textColor ?? Theme.of(context).textTheme.bodyLarge?.color?.withValues(alpha: 0.70),
                  fontSize: Dimensions.fontSizeSmall
              ),
          ) :

          isDeliveryCost?
          Text(title!.tr, style: rubikRegular.copyWith(color: Theme.of(context).textTheme.bodyLarge!.color, fontSize: Dimensions.fontSizeSmall)):
          Text(title!.tr, style: titleTextStyle ?? rubikRegular.copyWith(
              color: textColor ?? Theme.of(context).textTheme.bodyLarge?.color?.withValues(alpha: 0.70),
              fontSize: Dimensions.fontSizeSmall,
          )),
          SizedBox(width: Dimensions.paddingSizeDefault),

          isProduct?
          Container(
              decoration: BoxDecoration(borderRadius: BorderRadius.circular(Dimensions.paddingSizeDefault),
              color: Theme.of(context).primaryColor.withValues(alpha:.075)),
              child: DottedBorder(
                  color:Get.isDarkMode? Theme.of(context).hintColor.withValues(alpha:.5) : Theme.of(context).primaryColor.withValues(alpha: 0.30),
                  borderType: BorderType.RRect,
                  radius: const Radius.circular(45),
                child: Container(
                    padding:  EdgeInsets.only(
                      left: Dimensions.paddingSizeSmall,
                      right: Dimensions.paddingSizeMin,
                      top: Dimensions.paddingSizeExtraSmall,
                      bottom: Dimensions.paddingSizeExtraSmall,
                    ),
                    child: Row( children: [
                      Text('${'item'.tr} x $info',style: rubikBold.copyWith(
                          color: Get.isDarkMode ? Theme.of(context).hintColor : Theme.of(context).primaryColor,
                          fontSize: Dimensions.fontSizeSmall,
                      )),
                      SizedBox(width: Dimensions.paddingSizeSmall),

                      CustomAssetImageWidget(Images.infoCircleIcon, height: 14, width: 14, color:Get.isDarkMode ?
                      Theme.of(context).hintColor : Theme.of(context).primaryColor),
                    ])),
              )) :


          isDeliveryCost?
          DottedBorder(
            color: Theme.of(context).primaryColor,
            borderType: BorderType.RRect,
            radius: const Radius.circular(45),
            child: Container(
              color: Theme.of(context).primaryColor.withValues(alpha:.05),
              padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeExtraSmall),
              child: Row( children: [
                Text(PriceConverter.convertPrice(double.parse(info!)), style: rubikMedium.copyWith(
                  color: Get.isDarkMode ? Theme.of(context).hintColor : Colors.black,
                  fontSize: Dimensions.fontSizeSmall,
                )),
              ]),
            ),
          ) :


          isDate ?
          Directionality(
              textDirection: TextDirection.ltr,
              child: Text(DateConverter.isoStringToDateTimeString(info!),style: rubikRegular.copyWith(
                color: Get.isDarkMode ? Theme.of(context).hintColor : Theme.of(context).textTheme.bodyLarge?.color?.withValues(alpha: 0.70),
                fontSize: Dimensions.fontSizeSmall,
              )),
          ) :
          isPrice ?
          Text(PriceConverter.convertPrice(double.parse(info!)), style: textStyle ?? rubikMedium.copyWith(
            color: Get.isDarkMode ? Theme.of(context).hintColor : Theme.of(context).textTheme.bodyLarge!.color?.withValues(alpha: 0.70),
            fontSize: Dimensions.fontSizeSmall,
          )) :
          Flexible(child: Text(
              info!.tr,
              textAlign: TextAlign.right,
              maxLines: 2,
              overflow: TextOverflow.clip,
              style: textStyle ?? rubikRegular.copyWith(
                color: Get.isDarkMode ? Theme.of(context).hintColor : Colors.black,
                fontSize: Dimensions.fontSizeSmall,
              ),
          )),
        ],
      ),
    );
  }
}