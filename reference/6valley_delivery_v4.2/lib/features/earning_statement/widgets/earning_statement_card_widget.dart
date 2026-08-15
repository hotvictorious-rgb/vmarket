import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_asset_image_widget.dart';
import 'package:sixvalley_delivery_boy/features/order_details/screens/order_details_screen.dart';
import 'package:sixvalley_delivery_boy/features/wallet/domain/models/delivery_wise_earned_model.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart' as order;
import 'package:sixvalley_delivery_boy/helper/date_converter.dart';
import 'package:sixvalley_delivery_boy/helper/price_converter.dart';
import 'package:sixvalley_delivery_boy/theme/controllers/theme_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_action_button_widget.dart';

class EarningStatementCardWidget extends StatelessWidget {
  final Orders? ordersWiseEarned;
  const EarningStatementCardWidget({Key? key, this.ordersWiseEarned}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding:  EdgeInsets.fromLTRB( Dimensions.paddingSizeDefault, Dimensions.paddingSizeSmall, Dimensions.paddingSizeDefault,0),
      child: Container(decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(Dimensions.paddingSizeExtraSmall),
        color: Theme.of(context).cardColor,
        boxShadow: [BoxShadow(color: Get.find<ThemeController>().darkTheme ? Colors.black.withValues(alpha:0.10) : Colors.grey[100]!,
            blurRadius: 5, spreadRadius: 1, offset: const Offset(0,2))],),
        padding:  EdgeInsets.all(Dimensions.paddingSizeSmall),
        child: Column(crossAxisAlignment: CrossAxisAlignment.end,
          children: [Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
            Text('${'order'.tr}# ${ordersWiseEarned!.id}',
              style: rubikMedium.copyWith(fontSize: Dimensions.fontSizeDefault),),
            Row(children: [Padding(padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeSmall),
              child: const SizedBox(width: 20, child: CustomAssetImageWidget(Images.cash))),
              Text('${'by'.tr} ${'cash'.tr}',
                style: rubikMedium.copyWith(color: Get.isDarkMode?
                Theme.of(context).hintColor.withValues(alpha:.5) :Theme.of(context).primaryColor))])],),

            Padding(padding: EdgeInsets.symmetric(vertical: Dimensions.fontSizeExtraSmall),
              child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                Row(children: [SizedBox(width: 20, child: Image.asset(Images.calenderIcon)),
                   Padding(padding:  EdgeInsets.only(left: Dimensions.paddingSizeDefault),
                    child: Text(DateConverter.isoStringToLocalDateOnly(ordersWiseEarned!.createdAt!)))]),
                 Text(PriceConverter.convertPrice(ordersWiseEarned!.deliverymanCharge))])),

             CustomActionButtonWidget(title: 'view_details',
               onTap: (){
               order.SellerInfo sellerInfo = order.SellerInfo(
                 id: ordersWiseEarned!.seller!.id,
                 email: ordersWiseEarned!.seller!.email,
                 phone: ordersWiseEarned!.seller!.phone,
                 shop: ordersWiseEarned!.seller!.shop);
               order.OrderModel orderModel = order.OrderModel(
                 id: ordersWiseEarned!.id,
                 orderStatus: ordersWiseEarned!.orderStatus,
                 shippingAddress: ordersWiseEarned!.shippingAddressData,
                 deliveryManCharge: ordersWiseEarned!.deliverymanCharge,
                 discountAmount: ordersWiseEarned!.discountAmount,
                 shippingCost: ordersWiseEarned!.shippingCost,
                 updatedAt: ordersWiseEarned!.updatedAt,
                 paymentMethod: ordersWiseEarned!.paymentMethod,
                 sellerInfo: sellerInfo,
                 customer: ordersWiseEarned!.customer);
                 Get.to(()=> OrderDetailsScreen(orderModel: orderModel, fromNotification: false));
               },),

      ],),),
    );
  }
}
