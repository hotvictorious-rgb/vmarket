import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/theme/controllers/theme_controller.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/order/widgets/order_item_info_widget.dart';


class DeliveryInfoWidget extends StatelessWidget {
  final OrderModel? orderModel;
  final int? index;
  const DeliveryInfoWidget({Key? key, this.orderModel, this.index}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Container(
      padding:  EdgeInsets.symmetric(horizontal:Dimensions.rememberMeSizeDefault, vertical: Dimensions.paddingSizeMin),
      decoration: BoxDecoration(color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(Dimensions.paddingSizeChat),
        boxShadow: [BoxShadow(color: Get.find<ThemeController>().darkTheme ? Colors.black.withValues(alpha:0.10) : Colors.grey[100]!,
          blurRadius: 5, spreadRadius: 1)]),


      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [

        Row(children: [

          SizedBox(width: 20, child: Image.asset(Images.customerIcon)),

          Padding(
              padding:  EdgeInsets.symmetric(
                horizontal: Dimensions.paddingSizeSmall,
                vertical: Dimensions.paddingSizeDefault,
              ),
              child: Text('delivery_info'.tr,style: rubikMedium.copyWith(color: Theme.of(context).primaryColor,
                  fontSize: Dimensions.fontSizeLarge
              )),
          ),
        ]),

        (orderModel!.shippingAddress?.contactPersonName != null && orderModel!.shippingAddress!.contactPersonName!.isNotEmpty)
            && (orderModel!.shippingAddress?.phone != null && orderModel!.shippingAddress!.phone!.isNotEmpty)
            && (orderModel!.shippingAddress != null) ?
        Column(children: [
          OrderItemInfoWidget(title: 'name',info: orderModel!.shippingAddress?.contactPersonName?? '', textStyle: rubikMedium.copyWith(
              fontSize: Dimensions.fontSizeSmall,
              color: Theme.of(context).textTheme.bodyLarge?.color
          )),

          OrderItemInfoWidget(title: 'contact', info: orderModel!.shippingAddress?.phone ?? '', textStyle: rubikMedium.copyWith(
              fontSize: Dimensions.fontSizeSmall,
              color: Theme.of(context).textTheme.bodyLarge?.color
          )),

          OrderItemInfoWidget(title: 'location', info: orderModel!.shippingAddress != null ?
          '${orderModel!.shippingAddress?.address}, ''${orderModel!.shippingAddress?.city}, ''${orderModel!.shippingAddress?.zip}' :  '',
              textStyle: rubikMedium.copyWith(
                  fontSize: Dimensions.fontSizeSmall,
                  color: Theme.of(context).textTheme.bodyLarge?.color
              )
          ),
        ]) : const SizedBox.shrink(),

        SizedBox(height: Dimensions.paddingSizeDefault),
      ]),
    );
  }
}
