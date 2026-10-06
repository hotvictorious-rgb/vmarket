import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/features/live_tracking/screens/order_tracking_screen.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/features/order_details/controllers/order_details_controller.dart';
import 'package:sixvalley_delivery_boy/features/order_details/widgets/order_action_item_widget.dart';
import 'package:sixvalley_delivery_boy/features/order_details/widgets/order_current_status_change_dialog_widget.dart';
import 'package:sixvalley_delivery_boy/features/order_details/widgets/tracking_stepper_widget.dart';
import 'package:sixvalley_delivery_boy/helper/color_helper.dart';
import 'package:sixvalley_delivery_boy/theme/controllers/theme_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/animated_custom_dialog_widget.dart';
import 'package:sixvalley_delivery_boy/features/home/widgets/receiver_widget.dart';
import 'package:get/get.dart';



import 'package:sixvalley_delivery_boy/helper/navigation_helper.dart';

class OrderInfoWithDeliveryInfoWidget extends StatelessWidget {
  final OrderModel? orderModel;
  final bool fromMap;
  const OrderInfoWithDeliveryInfoWidget({Key? key, this.orderModel, this.fromMap = false}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Padding(padding:  EdgeInsets.only(bottom : Dimensions.paddingSizeSmall),
      child: GetBuilder<OrderDetailsController>(
        builder: (orderController) {
          return Container(decoration: BoxDecoration(color: Theme.of(context).cardColor,
              borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall)),
            padding:  EdgeInsets.all(Dimensions.paddingSizeSmall),

            child: Column(children: [
              Padding(padding:  EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault),
                child: Text('${'order'.tr} # ${orderModel!.id}',
                    style: rubikMedium.copyWith(fontSize: Dimensions.fontSizeExtraLarge,
                    color:  (
                      Get.find<ThemeController>().darkTheme ?
                      ColorHelper.blendColors(Colors.white, Theme.of(context).primaryColor, 0.9):
                      ColorHelper.darken(Theme.of(context).primaryColor, 0.1)
                    )
                    ))),
              Divider(height: .75,color: Theme.of(context).primaryColor.withValues(alpha:.725)),
              Padding(padding:  EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault),
                child: TrackingStepperWidget(status: orderModel!.orderStatus)),
              fromMap? const SizedBox():

              // [AI] Direct Native Google Maps Turn-by-Turn GPS Navigation
              Padding(
                padding: EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
                child: Column(
                  children: [
                    InkWell(
                      onTap: () {
                        final lat = double.tryParse(orderModel?.shippingAddress?.latitude ?? '');
                        final lng = double.tryParse(orderModel?.shippingAddress?.longitude ?? '');
                        final fullAddress = [
                          orderModel?.shippingAddress?.address,
                          orderModel?.shippingAddress?.city,
                          orderModel?.shippingAddress?.state,
                        ].where((s) => s != null && s.isNotEmpty).join(', ');

                        NavigationHelper.openGoogleMaps(
                          latitude: lat,
                          longitude: lng,
                          address: fullAddress,
                          customerName: orderModel?.shippingAddress?.contactPersonName,
                        );
                      },
                      child: Container(
                        width: double.infinity,
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFF5E17EB), Color(0xFF7C3AED)],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                          borderRadius: BorderRadius.circular(Dimensions.paddingSizeDefault),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFF5E17EB).withValues(alpha: 0.3),
                              blurRadius: 8,
                              offset: const Offset(0, 3),
                            ),
                          ],
                        ),
                        padding: EdgeInsets.symmetric(
                          horizontal: Dimensions.paddingSizeDefault,
                          vertical: Dimensions.paddingSizeSmall + 2,
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.navigation_rounded, color: Colors.white, size: 20),
                            const SizedBox(width: 8),
                            Text(
                              'Start Google Maps Navigation',
                              style: rubikMedium.copyWith(
                                color: Colors.white,
                                fontSize: Dimensions.fontSizeDefault,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    GestureDetector(
                      onTap: () => Get.to(()=> OrderLiveTrackingScreen(orderModel: orderModel)),
                      child: Container(
                        decoration: BoxDecoration(
                          color: Get.isDarkMode
                              ? Theme.of(context).cardColor
                              : Theme.of(context).primaryColor.withValues(alpha: .08),
                          borderRadius: BorderRadius.circular(Dimensions.paddingSizeLarge),
                        ),
                        padding: EdgeInsets.symmetric(
                          horizontal: Dimensions.paddingSizeExtraLarge,
                          vertical: Dimensions.paddingSizeExtraSmall + 2,
                        ),
                        child: Text(
                          'view_on_map'.tr,
                          style: rubikRegular.copyWith(
                            fontSize: Dimensions.fontSizeSmall,
                            color: Get.isDarkMode
                                ? Theme.of(context).hintColor
                                : ColorHelper.darken(Theme.of(context).primaryColor, 0.1),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),

              ReceiverWidget(orderModel: orderModel),
              (orderModel?.orderStatus == 'processing' || orderModel?.orderStatus == 'out_for_delivery') ?
              Row(children:  [
                Expanded(child: OrderActionItemWidget(icon: Images.reachedIcon,title:  'reached'.tr,
                  onTap: () => showAnimatedDialogWidget(context,  OrderStatusUpdateDialogWidget(icon: Images.reachedIcon,
                    isReschedule: true,
                    title:  'why_you_want_to_reschedule_this_delivery'.tr,
                    onYesPressed: (){
                      Get.back();
                      orderController.rescheduleOrderStatus(
                        orderId: orderModel!.id,
                        deliveryDate: orderController.dateFormat.format(orderController.startDate!).toString(),
                        cause: orderController.reasonValue,
                        context: context);
                    },),isFlip: true),)),

                 Expanded(child: OrderActionItemWidget(icon: !orderModel!.isPause! ? Images.pauseIcon : Images.resume,
                   title: !orderModel!.isPause! ? 'pause'.tr : 'resume'.tr,
                  onTap: () => showAnimatedDialogWidget(context,  OrderStatusUpdateDialogWidget(
                    isResume: orderModel!.isPause! ? true : false,
                    icon: !orderModel!.isPause! ? Images.pauseIcon : Images.resume,
                    title: !orderModel!.isPause! ? 'why_you_want_to_pause_this_delivery'.tr  : 'do_you_want_to_resume_the_delivery'.tr,
                    onYesPressed: (){
                      Get.back();
                      orderController.pauseAndResumeOrder(
                          orderId: orderModel!.id,
                          isPos: !orderModel!.isPause! ? 1: 0,
                          cause: orderController.reasonValue,
                          context: context);
                    },),isFlip: true),)),
              ]) : const SizedBox(),
            ],),);
        }
      ),
    );
  }
}
