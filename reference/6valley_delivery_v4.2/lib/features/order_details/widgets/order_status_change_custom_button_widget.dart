import 'package:flutter/material.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_loader_widget.dart';
import 'package:sixvalley_delivery_boy/common/controllers/localization_controller.dart';
import 'package:sixvalley_delivery_boy/features/order_details/controllers/order_details_controller.dart';
import 'package:sixvalley_delivery_boy/features/order_details/screens/order_delivered_screen.dart';
import 'package:sixvalley_delivery_boy/features/order_details/widgets/camera_or_gallery_widget.dart';
import 'package:sixvalley_delivery_boy/features/order_details/widgets/slider_button_widget.dart';
import 'package:sixvalley_delivery_boy/features/order_details/widgets/verify_otp_sheet_widget.dart';
import 'package:sixvalley_delivery_boy/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:flutter/cupertino.dart';
import 'package:sixvalley_delivery_boy/features/order/controllers/order_controller.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/images.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:get/get.dart';

class OrderStatusChangeCustomButtonWidget extends StatelessWidget {
  final OrderModel? orderModel;
  final int? index;
  const OrderStatusChangeCustomButtonWidget({Key? key, this.orderModel, this.index}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    bool isLtr = Get.find<LocalizationController>().isLtr;
    const double rotateAnglePi = 3.1416;
    return (orderModel!.orderStatus == 'processing' || orderModel!.orderStatus == 'out_for_delivery') && !orderModel!.isPause! ?
    Padding(
      padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault,vertical: Dimensions.paddingSizeSmall),
      child: Directionality(
        textDirection: isLtr ? TextDirection.ltr :  TextDirection.rtl,
        child: SliderButtonWidget(
          isRtl: !isLtr,
          action:  ()  {
            if(orderModel!.orderStatus == 'processing') {
              _handleProcessingStatus(context);
            } else if(orderModel!.orderStatus == 'out_for_delivery') {
              _handleOutForDeliveryStatus(context);
            }

          },
          label: Text(orderModel!.orderStatus == 'processing'? 'swipe_to_out_for_delivery_order'.tr : 'swip_to_deliver_order'.tr,
            style: rubikMedium.copyWith(color: Theme.of(context).primaryColor, fontSize: Dimensions.fontSizeSmall),),
          dismissThresholds: 0.5,
          icon: RotationTransition(
            turns: const AlwaysStoppedAnimation(45 / 360),
            child: Center(child: isLtr ? Icon(CupertinoIcons.paperplane, size: 20, color: Theme.of(context).cardColor,) :
            Transform.rotate(angle: rotateAnglePi, child: Icon(CupertinoIcons.paperplane, size: 20, color: Theme.of(context).cardColor,)))
          ),
          radius: 100,
          width: MediaQuery.of(context).size.width-55,
          boxShadow: const BoxShadow(blurRadius: 0.0),
          buttonColor: Theme.of(context).primaryColor,
          backgroundColor: Get.isDarkMode ? Theme.of(context).hintColor : Theme.of(context).primaryColor.withValues(alpha:.05),
          baseColor: Theme.of(context).primaryColor),
      )):const SizedBox();
  }

  void _handleProcessingStatus(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => const CustomLoaderWidget(),
    );

    Get.find<OrderDetailsController>().updateOrderStatus(
      orderId: orderModel!.id,
      status: 'out_for_delivery',
      context: context,
    );

    Navigator.of(context).pop();
    Get.find<OrderController>().getCurrentOrders();
  }


  void _handleOutForDeliveryStatus(BuildContext context) {
    final splashController = Get.find<SplashController>();
    final orderDetailsController = Get.find<OrderDetailsController>();

    if (splashController.configModel?.imageUpload == 1) {
      _showImageUploadDialog(context);
    } else {
      _handleNonImageUploadFlow(context, splashController, orderDetailsController);
    }
  }

  void _showImageUploadDialog(BuildContext context) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return Dialog(
          backgroundColor: Colors.transparent,
          shadowColor: Colors.transparent,
          surfaceTintColor: Colors.transparent,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              InkWell(
                onTap: () {
                  Get.find<OrderDetailsController>().gotoEndOfPage();
                  Get.back();
                },
                child: Padding(
                  padding: EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                  child: Icon(
                    Icons.cancel_rounded,
                    color: Theme.of(context).hintColor,
                    size: 30,
                  ),
                ),
              ),

              InkWell(
                onTap: () {
                  Get.back();
                  _showCameraBottomSheet(context);
                },
                child: Container(
                  width: Get.width,
                  height: 170,
                  decoration: BoxDecoration(
                    color: Get.isDarkMode
                        ? Theme.of(context).primaryColor
                        : Theme.of(context).cardColor,
                    borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                  ),
                  child: Column(
                    children: [
                      Padding(
                        padding: EdgeInsets.all(Dimensions.paddingSizeExtraLarge),
                        child: Text(
                          'take_a_picture'.tr,
                          style: rubikMedium.copyWith(
                              color: Get.isDarkMode
                                  ? Theme.of(context).hintColor
                                  : Colors.black),
                        ),
                      ),
                      Container(
                        width: 150,
                        height: 75,
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: Get.isDarkMode
                              ? Theme.of(context).cardColor
                              : Theme.of(context).primaryColor.withValues(alpha: .125),
                          borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                        ),
                        child: Image.asset(Images.camera),
                      ),
                    ],
                  ),
                ),
              )
            ],
          ),
        );
      },
    );
  }


  void _showCameraBottomSheet(BuildContext context) {
    showModalBottomSheet<void>(
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      context: context,
      builder: (BuildContext context) {
        return Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
          child: CameraOrGalleryWidget(
            orderModel: orderModel,
            totalPrice: Get.find<OrderDetailsController>().totalPrice,
          ),
        );
      },
    );
  }


  void _handleNonImageUploadFlow(
      BuildContext context,
      SplashController splashController,
      OrderDetailsController orderDetailsController,
      ) {
    if (splashController.configModel?.orderVerification == 0) {
      if (orderModel?.paymentStatus != 'paid') {
        orderDetailsController.toggleProceedToNext();
        _showVerifyDeliverySheet(context);
      } else {
        _completeDelivery(context, orderDetailsController);
      }
    } else {
      orderDetailsController.gotoEndOfPage();
    }
  }

  void _showVerifyDeliverySheet(BuildContext context) {
    showModalBottomSheet<void>(
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      context: context,
      builder: (BuildContext context) {
        return Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
          child: VerifyDeliverySheetWidget(
            orderModel: orderModel,
            totalPrice: Get.find<OrderDetailsController>().totalPrice,
          ),
        );
      },
    );
  }

  void _completeDelivery(
      BuildContext context,
      OrderDetailsController orderDetailsController,
      ) {
    orderDetailsController.updateOrderStatus(
      orderId: orderModel!.id,
      context: context,
      status: 'delivered',
    ).then((value) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => OrderDeliveredScreen(
            orderID: orderModel!.id.toString(),
            orderModel: orderModel,
          ),
        ),
      );
    });
  }

}
