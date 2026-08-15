import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/order/controllers/order_controller.dart';
import 'package:sixvalley_delivery_boy/features/wallet/controllers/wallet_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';

class OrderTypeButtonWidget extends StatelessWidget {
  final String text;
  final int index;
  const OrderTypeButtonWidget({Key? key, required this.text, required this.index}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return InkWell(onTap: (){
      Get.find<OrderController>().setOrderTypeIndex(index);
      Get.find<WalletController>().selectDate();
    },
      child: GetBuilder<OrderController>(builder: (order) {
        return Padding(padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeExtraSmall,
            vertical: order.orderTypeIndex == index ? Dimensions.paddingSizeDefault : Dimensions.rememberMeSizeDefault),
          child: Container(padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault,),
            alignment: Alignment.center,
            decoration: BoxDecoration(color: order.orderTypeIndex == index ?
            Get.isDarkMode ? Theme.of(context).hintColor.withValues(alpha:.5) : Theme.of(context).cardColor : Get.isDarkMode ? Theme.of(context).cardColor : Theme.of(context).primaryColor.withValues(alpha:.75),
                borderRadius: BorderRadius.circular(Dimensions.paddingSizeOverLarge)),
            child: Text(text, style: order.orderTypeIndex == index ?
            rubikMedium.copyWith(color: Get.isDarkMode ? Theme.of(context).hintColor.withValues(alpha: 1) : Theme.of(context).primaryColor) :
            rubikRegular.copyWith(color: Get.isDarkMode ? Theme.of(context).hintColor : Theme.of(context).cardColor.withValues(alpha: .7)),
            ),
          ),
        );
      },),
    );
  }
}