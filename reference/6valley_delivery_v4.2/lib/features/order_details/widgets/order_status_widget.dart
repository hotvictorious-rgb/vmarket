import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:get/get.dart';

class OrderStatusWidget extends StatelessWidget {
  final OrderModel? orderModel;
  const OrderStatusWidget({Key? key, this.orderModel}) : super(key: key);

  @override
  Widget build(BuildContext context) {

    return Container(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeChat, vertical: Dimensions.paddingSizeExtraSmall),
      decoration: BoxDecoration(
        color: orderModel?.orderStatus?.tr == 'Delivered'
            ? Theme.of(context).colorScheme.onTertiaryContainer.withValues(alpha: 0.07)
            : Theme.of(context).primaryColor.withValues(alpha: 0.07),
        borderRadius: BorderRadius.circular(Dimensions.paddingSizeMin),
      ),
      child: Text(orderModel?.orderStatus?.tr ?? '', style: rubikMedium.copyWith(
        color: orderModel?.orderStatus?.tr == 'Delivered'
            ? Theme.of(context).colorScheme.onTertiaryContainer
            : Theme.of(context).primaryColor,
        fontSize: Dimensions.fontSizeSmall,
        fontWeight: FontWeight.w600,
      )),
    );
  }


}
