import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/features/order_details/controllers/order_details_controller.dart';
import 'package:sixvalley_delivery_boy/features/order_details/screens/order_delivered_screen.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_delivery_boy/common/basewidgets/custom_snackbar_widget.dart';

/// [AI] Doorstep Delivery Handshake Code Presentation Sheet
/// Pure Receiver-Driven Protocol: Delivery rider displays this 6-digit Secret Delivery Code
/// to the customer. The customer must snap a photo of the received parcel and enter this code
/// on their own device. Zero failover / rider bypass permitted.
class VerifyDeliverySheetWidget extends StatefulWidget {
  final OrderModel? orderModel;
  final double? totalPrice;
  const VerifyDeliverySheetWidget({Key? key, this.orderModel, this.totalPrice}) : super(key: key);
  @override
  State<VerifyDeliverySheetWidget> createState() => _VerifyDeliverySheetWidgetState();
}

class _VerifyDeliverySheetWidgetState extends State<VerifyDeliverySheetWidget> {
  Timer? _pollingTimer;
  bool _isChecking = false;

  @override
  void initState() {
    super.initState();
    final controller = Get.find<OrderDetailsController>();
    controller.getOrderDeliveryCode(widget.orderModel?.id);

    // [AI] Live 3-second status polling until customer confirms on their device
    _pollingTimer = Timer.periodic(const Duration(seconds: 3), (timer) {
      _checkCustomerVerificationStatus(silent: true);
    });
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    super.dispose();
  }

  Future<void> _checkCustomerVerificationStatus({bool silent = false}) async {
    if (_isChecking || widget.orderModel?.id == null) return;
    _isChecking = true;
    try {
      final controller = Get.find<OrderDetailsController>();
      final res = await controller.orderDetailsServiceInterface.getOrderDetails(
        orderID: widget.orderModel!.id.toString(),
      );

      if (res != null && res.statusCode == 200 && res.body != null) {
        // [AI] Check if order is now delivered
        String? currentStatus;
        if (res.body is List && (res.body as List).isNotEmpty) {
          final first = res.body[0];
          if (first is Map && first['order'] != null) {
            currentStatus = first['order']['order_status']?.toString();
          }
        }

        if (currentStatus == 'delivered') {
          _pollingTimer?.cancel();
          if (mounted) {
            showCustomSnackBarWidget('Customer confirmed delivery successfully!'.tr, isError: false);
            Navigator.of(context).pushReplacement(MaterialPageRoute(
              builder: (_) => OrderDeliveredScreen(
                orderID: widget.orderModel!.id.toString(),
                orderModel: widget.orderModel,
              ),
            ));
          }
        } else if (!silent && mounted) {
          showCustomSnackBarWidget('Waiting for customer to snap photo & enter code...'.tr, isError: false);
        }
      }
    } catch (_) {
      // Ignore background polling errors
    } finally {
      _isChecking = false;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).canvasColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: GetBuilder<OrderDetailsController>(builder: (orderController) {
        final code = orderController.deliveryCode ?? '------';
        final spacedCode = code.split('').join('  ');

        return Padding(
          padding: EdgeInsets.all(Dimensions.paddingSizeLarge),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                height: 5,
                width: 50,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(Dimensions.radiusLarge),
                  color: Theme.of(context).disabledColor.withValues(alpha: 0.5),
                ),
              ),
              SizedBox(height: Dimensions.paddingSizeLarge),

              // Title Badge
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                decoration: BoxDecoration(
                  color: Theme.of(context).primaryColor.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.3)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.verified_user_rounded, size: 18, color: Theme.of(context).primaryColor),
                    const SizedBox(width: 6),
                    Text(
                      'Customer Handshake Verification'.tr,
                      style: rubikMedium.copyWith(color: Theme.of(context).primaryColor, fontSize: 13),
                    ),
                  ],
                ),
              ),

              SizedBox(height: Dimensions.paddingSizeDefault),

              Text(
                'Show This Secret Code to Customer'.tr,
                style: rubikBold.copyWith(fontSize: Dimensions.fontSizeLarge),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: Dimensions.paddingSizeExtraSmall),

              Text(
                'The customer must snap a photo of the received parcel and enter this code on their phone to complete delivery.'
                    .tr,
                style: rubikRegular.copyWith(
                  color: Theme.of(context).disabledColor,
                  fontSize: Dimensions.fontSizeSmall,
                ),
                textAlign: TextAlign.center,
              ),

              SizedBox(height: Dimensions.paddingSizeLarge),

              // Big Code Display Card
              orderController.isLoadingCode
                  ? const Padding(
                      padding: EdgeInsets.all(24.0),
                      child: CircularProgressIndicator(),
                    )
                  : Container(
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                      decoration: BoxDecoration(
                        color: Theme.of(context).cardColor,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: Theme.of(context).primaryColor,
                          width: 2,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: Theme.of(context).primaryColor.withValues(alpha: 0.15),
                            blurRadius: 16,
                            offset: const Offset(0, 4),
                          )
                        ],
                      ),
                      child: Text(
                        spacedCode,
                        style: rubikBold.copyWith(
                          fontSize: 32,
                          letterSpacing: 4,
                          color: Theme.of(context).primaryColor,
                        ),
                      ),
                    ),

              SizedBox(height: Dimensions.paddingSizeLarge),

              // Realtime Listening Indicator
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const SizedBox(
                    width: 14,
                    height: 14,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
                  const SizedBox(width: 10),
                  Text(
                    'Listening for customer photo & confirmation...'.tr,
                    style: rubikRegular.copyWith(
                      color: Theme.of(context).disabledColor,
                      fontSize: Dimensions.fontSizeSmall,
                    ),
                  ),
                ],
              ),

              SizedBox(height: Dimensions.paddingSizeLarge),

              // Refresh Check Button
              CustomButtonWidget(
                btnTxt: 'Check Confirmation Status'.tr,
                onTap: () => _checkCustomerVerificationStatus(silent: false),
              ),

              SizedBox(height: Dimensions.paddingSizeSmall),
            ],
          ),
        );
      }),
    );
  }
}
