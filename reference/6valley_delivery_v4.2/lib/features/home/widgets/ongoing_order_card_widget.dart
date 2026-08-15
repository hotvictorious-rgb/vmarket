import 'package:expandable/expandable.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/live_tracking/screens/order_tracking_screen.dart';
import 'package:sixvalley_delivery_boy/features/order/controllers/order_controller.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/features/order_details/screens/order_details_screen.dart';
import 'package:sixvalley_delivery_boy/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_delivery_boy/helper/date_converter.dart';
import 'package:sixvalley_delivery_boy/theme/controllers/theme_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/features/home/widgets/on_going_order_header_widget.dart';
import 'package:sixvalley_delivery_boy/features/home/widgets/receiver_widget.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';



class OnGoingOrderWidget extends StatelessWidget {
  final OrderModel? orderModel;
  final int? index;
  const OnGoingOrderWidget({Key? key, this.orderModel, this.index}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Padding(padding:  EdgeInsets.fromLTRB(0, 0, 0, Dimensions.paddingSizeSmall),
      child: Container(decoration: BoxDecoration(color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(Dimensions.paddingSizeDefault),
        boxShadow:  [BoxShadow(color: Get.find<ThemeController>().darkTheme ? Colors.black.withValues(alpha:0.10) : Colors.grey[100]!,
            blurRadius: 15, spreadRadius: 0, offset: const Offset(0,2))],),
        padding:  EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault),

        child: ExpandableNotifier(
          initialExpanded: index == 0 ? true : false,
          child: Column(children: [
              Expandable(collapsed: ExpandableButton(
                  child: Column(children: [
                    OngoingOrderHeaderWidget(orderModel: orderModel,index: index)])),

                expanded: Column(children: [

                      InkWell(onTap: (){
                            Get.to(()=>OrderDetailsScreen(orderModel: orderModel, fromNotification: false));
                            Get.find<OrderController>().selectedOrderLatLng(orderModel?.shippingAddress?.latitude??'23',
                                orderModel?.shippingAddress?.longitude??'90');
                            },
                          child: OngoingOrderHeaderWidget(orderModel: orderModel, index: index, isExpanded: true)),

                      Padding(padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [

                          Row(mainAxisAlignment: MainAxisAlignment.start, children: [

                            SizedBox(width: Dimensions.iconSizeDefault, child: Image.asset(Images.calenderIcon,
                                color:Get.isDarkMode? Theme.of(context).hintColor.withValues(alpha:.25) :
                                Theme.of(context).primaryColor.withValues(alpha:.5))),
                            SizedBox(width: Dimensions.paddingSizeSmall),
                            Text('${'assigned'.tr} : ', style: rubikRegular.copyWith(fontSize: Dimensions.fontSizeSmall),),
                            Text(DateConverter.isoStringToLocalDateOnly(orderModel!.createdAt!),
                                style: rubikRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Get.isDarkMode ? Theme.of(context).hintColor : Colors.black))]),


                          orderModel!.expectedDate != null?
                          Padding(padding:  EdgeInsets.only(top: Dimensions.paddingSizeExtraSmall),
                            child: Row(children: [
                              SizedBox(width: Dimensions.iconSizeDefault, child: Image.asset(Images.calenderIcon,
                                  color: Get.isDarkMode? Theme.of(context).hintColor.withValues(alpha:.25) :
                                  Theme.of(context).primaryColor.withValues(alpha:.5))),
                              SizedBox(width: Dimensions.paddingSizeSmall),
                              Text('${'expected_date'.tr} : ', style: rubikRegular.copyWith(fontSize: Dimensions.fontSizeSmall),),
                              Text(orderModel!.expectedDate??'', style: rubikRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Get.isDarkMode ? Theme.of(context).hintColor : Colors.black))]),
                          ) : const SizedBox(),
                        ])),


                      SizedBox(height: Dimensions.paddingSizeSmall,),
                      Get.find<SplashController>().configModel!.mapApiStatus == 1 ?
                      GestureDetector(onTap: () => Get.to(()=> OrderLiveTrackingScreen(orderModel: orderModel)),
                        child: Container(height: Get.width/3,
                          padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                          child: Image.asset(Images.previewMap, fit: BoxFit.fill))) : const SizedBox(),

                      ReceiverWidget(orderModel: orderModel),

                      ExpandableButton(child: Container(decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(50),
                          color: Get.isDarkMode? Theme.of(context).hintColor.withValues(alpha:.25) : Theme.of(context).primaryColor.withValues(alpha:.08),),
                          padding:  EdgeInsets.all(Dimensions.paddingSizeExtraSmall),
                          child:  Icon(Icons.keyboard_arrow_up, size: Dimensions.iconSizeMenu, color: Get.isDarkMode? Theme.of(context).hintColor : Theme.of(context).primaryColor),
                      ),),
                ]),

              ),
            ],
          ),
        ),
      ),
    );
  }
}





